<?php

class CTBody {
    /**
     * Builds MD5 secret hash for JavaScript test (self::JSTest) 
     * @return string 
     */
    public static function getJSChallenge() {
        global $wgCTAccessKey, $wgEmergencyContact;

        return md5( $wgCTAccessKey . '+' . $wgEmergencyContact ); 
    } 
    
    /**
     * Tests hidden field falue for secret hash 
     * @return 0|1|null 
     */
    public static function JSTest() {

        if(isset($_COOKIE['ct_checkjs'])){
            if($_COOKIE['ct_checkjs'] == self::getJSChallenge())
                return 1;
            else
                return 0;
         }else{
            return null;
         }
    } 
    /**
     * Cookie test 
     * @return 
     */
    public static function ctSetCookie() {

        global $wgCTAccessKey;
        
        if( headers_sent() ) {
            return;
        }
        
        // Cookie names to validate
        $cookie_test_value = array(
            'cookies_names' => array(),
            'check_value' => $wgCTAccessKey,
        );

        // Pervious referer
        if(!empty($_SERVER['HTTP_REFERER'])){
            self::apbct_cookie__set( 'ct_prev_referer', $_SERVER['HTTP_REFERER'], 0, '/', $_SERVER['HTTP_HOST'], false, true, 'Lax' );
            $cookie_test_value['cookies_names'][] = 'ct_prev_referer';
            $cookie_test_value['check_value'] .= $_SERVER['HTTP_REFERER'];
        }
        
        // Cookies test
        $cookie_test_value['check_value'] = md5($cookie_test_value['check_value']);
        self::apbct_cookie__set( 'ct_cookies_test', json_encode($cookie_test_value), 0, '/', $_SERVER['HTTP_HOST'], false, true, 'Lax' );
    }
    public static function ctTestCookie()
    {
        global $wgCTAccessKey;
        if(isset($_COOKIE['ct_cookies_test'])){
            
            $cookie_test = json_decode(stripslashes($_COOKIE['ct_cookies_test']), true);
            
            $check_srting = $wgCTAccessKey;
            foreach($cookie_test['cookies_names'] as $cookie_name){
                $check_srting .= isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : '';
            } unset($cokie_name);
            
            if($cookie_test['check_value'] == md5($check_srting)){
                return 1;
            }else{
                return 0;
            }
        }else{
            return null;
        }        
    } 
    public static function createSFWTables()
    {
        $dbr = wfGetDB(DB_MASTER);

        $dbr->query("CREATE TABLE IF NOT EXISTS `cleantalk_sfw` (
            `network` int(11) unsigned NOT NULL,
            `mask` int(11) unsigned NOT NULL,
            INDEX (  `network` ,  `mask` )
            ) ENGINE = MYISAM ;");  

        $dbr->query("CREATE TABLE IF NOT EXISTS `cleantalk_sfw_logs` (
            `ip` varchar(15) NOT NULL,
            `all_entries` int(11) NOT NULL,
            `blocked_entries` int(11) NOT NULL,  
            `entries_timestamp` int(11) NOT NULL,   
            PRIMARY KEY `ip` (`ip`)
            ) ENGINE=MyISAM;");

        $dbr->query("CREATE TABLE IF NOT EXISTS `cleantalk_sfw_settings` (
            `setting_name` varchar(128) NOT NULL,
            `setting_value` int(24) NOT NULL
            ) ENGINE = MyISAM;");
     
    } 
    public static function onSpamCheck($method, $params)
    {
        global $wgCTAccessKey, $wgCTServerURL, $wgCTAgent;

        $result = null;

        $ct = new Cleantalk();
        $ct->server_url = $wgCTServerURL;
        
        $ct_request = new CleantalkRequest;

        foreach ($params as $k => $v) {
            $ct_request->$k = $v;
        }
        $ct_request->auth_key = $wgCTAccessKey;
        $ct_request->agent = $wgCTAgent; 
        $ct_request->submit_time = isset($_COOKIE['ct_ps_timestamp']) ? time() - intval($_COOKIE['ct_ps_timestamp']) : 0; 
        $ct_request->sender_ip = CleantalkHelper::ip_get(array('real'), false);
        $ct_request->x_forwarded_for = CleantalkHelper::ip_get(array('x_forwarded_for'), false);
        $ct_request->x_real_ip       = CleantalkHelper::ip_get(array('x_real_ip'), false);
        $ct_request->js_on = CTBody::JSTest();         
        $ct_request->sender_info=json_encode(
        Array(
            'page_url' => htmlspecialchars(@$_SERVER['SERVER_NAME'].@$_SERVER['REQUEST_URI']),
            'REFFERRER' => $_SERVER['HTTP_REFERER'],
            'USER_AGENT' => $_SERVER['HTTP_USER_AGENT'],
            'cookies_enabled' => CTBody::ctTestCookie(),            
            'REFFERRER_PREVIOUS' => isset($_COOKIE['ct_prev_referer'])?$_COOKIE['ct_prev_referer']:0,
            'mouse_cursor_positions' => isset($_COOKIE['ct_pointer_data'])          ? json_decode(stripslashes($_COOKIE['ct_pointer_data']), true) : null,
            'js_timezone'            => isset($_COOKIE['ct_timezone'])              ? $_COOKIE['ct_timezone']             : null,
            'key_press_timestamp'    => isset($_COOKIE['ct_fkp_timestamp'])         ? $_COOKIE['ct_fkp_timestamp']        : null,
            'page_set_timestamp'     => isset($_COOKIE['ct_ps_timestamp'])          ? $_COOKIE['ct_ps_timestamp']         : null,            
        ));  
        switch ($method) {
            case 'check_message':
                $result = $ct->isAllowMessage($ct_request);
                break;
            case 'send_feedback':
                $result = $ct->sendFeedback($ct_request);
                break;
            case 'check_newuser':
                $result = $ct->isAllowUser($ct_request);
                break;
            default:
                return NULL;
        } 
        return $result;                              
    }          
    /**
     * Adds hidden field to form for JavaScript test 
     * @return string 
     */
    public static function AddJSCode() {
        global $wgCTExtName;
        
        $html = '<script>
        var ct_checkjs_val = \''.self::getJSChallenge().'\',
            d = new Date() 
            ctTimeMs = new Date().getTime(),
            ctMouseEventTimerFlag = true, //Reading interval flag
            ctMouseData = "[",
            ctMouseDataCounter = 0;
        
        function ctSetCookie(c_name, value) {
            document.cookie = c_name + "=" + encodeURIComponent(value) + "; path=/; samesite=lax;";
        }
        
        ctSetCookie("ct_ps_timestamp", Math.floor(new Date().getTime()/1000));
        ctSetCookie("ct_fkp_timestamp", "0");
        ctSetCookie("ct_pointer_data", "0");
        ctSetCookie("ct_timezone", "0");
        ctSetCookie("ct_checkjs", ct_checkjs_val);        
        setTimeout(function(){
            ctSetCookie("ct_timezone", d.getTimezoneOffset()/60*(-1));
        },1000);
        
        //Reading interval
        var ctMouseReadInterval = setInterval(function(){
                ctMouseEventTimerFlag = true;
            }, 150);
            
        //Writting interval
        var ctMouseWriteDataInterval = setInterval(function(){
                var ctMouseDataToSend = ctMouseData.slice(0,-1).concat("]");
                ctSetCookie("ct_pointer_data", ctMouseDataToSend);
            }, 1200);
        
        //Stop observing function
        function ctMouseStopData(){
            if(typeof window.addEventListener == "function")
                window.removeEventListener("mousemove", ctFunctionMouseMove);
            else
                window.detachEvent("onmousemove", ctFunctionMouseMove);
            clearInterval(ctMouseReadInterval);
            clearInterval(ctMouseWriteDataInterval);                
        }
        
        //Logging mouse position each 300 ms
        var ctFunctionMouseMove = function output(event){
            if(ctMouseEventTimerFlag == true){
                var mouseDate = new Date();
                ctMouseData += "[" + Math.round(event.pageY) + "," + Math.round(event.pageX) + "," + Math.round(mouseDate.getTime() - ctTimeMs) + "],";
                ctMouseDataCounter++;
                ctMouseEventTimerFlag = false;
                if(ctMouseDataCounter >= 100)
                    ctMouseStopData();
            }
        }
        
        //Stop key listening function
        function ctKeyStopStopListening(){
            if(typeof window.addEventListener == "function"){
                window.removeEventListener("mousedown", ctFunctionFirstKey);
                window.removeEventListener("keydown", ctFunctionFirstKey);
            }else{
                window.detachEvent("mousedown", ctFunctionFirstKey);
                window.detachEvent("keydown", ctFunctionFirstKey);
            }
        }
        
        //Writing first key press timestamp
        var ctFunctionFirstKey = function output(event){
            var KeyTimestamp = Math.floor(new Date().getTime()/1000);
            ctSetCookie("ct_fkp_timestamp", KeyTimestamp);
            ctKeyStopStopListening();
        }

        if(typeof window.addEventListener == "function"){
            window.addEventListener("mousemove", ctFunctionMouseMove);
            window.addEventListener("mousedown", ctFunctionFirstKey);
            window.addEventListener("keydown", ctFunctionFirstKey);
        }else{
            window.attachEvent("onmousemove", ctFunctionMouseMove);
            window.attachEvent("mousedown", ctFunctionFirstKey);
            window.attachEvent("keydown", ctFunctionFirstKey);
        }</script>';
        
        $html .= '<noscript><p><b>Please enable JavaScript to pass antispam protection!</b><br />Here are the instructions how to enable JavaScript in your web browser <a href="http://www.enable-javascript.com" rel="nofollow" target="_blank">http://www.enable-javascript.com</a>.<br />' . $wgCTExtName . '.</p></noscript>';

        return $html;
    }

    /**
     * Sends email notificatioins to admins 
     * @return bool 
     */
    public static function SendAdminEmail( $title, $body ) {
        global $wgCTAdminAccountId, $wgCTAdminNotificaionInteval;

        $sfw = new CleantalkSFW();
        $settings = CTBody::ctGetSettings( $sfw );

        if ( $settings )
        {
            if (!isset($settings['lastAdminNotificaionSent']))
            {
                $settings['lastAdminNotificaionSent'] = time();
                CTBody::ctWriteSettings( $sfw, $settings );
            }
            // Skip notification if permitted interval doesn't exhaust
            if ( isset( $settings['lastAdminNotificaionSent'] ) && time() - $settings['lastAdminNotificaionSent'] < $wgCTAdminNotificaionInteval ) {
                return false;
            }

            $u = User::newFromId( $wgCTAdminAccountId );

            $status = $u->sendMail( $title , $body );

            if ( $status->isGood() ) {
                $settings['lastAdminNotificaionSent'] = time();
                CTBody::ctWriteSettings( $sfw, $settings );
            }
            return $status->isGood();
        }

        return false;

    }


    /**
     * Get settings from DB instead Antispam.store.dat file
     *
     * @param CleantalkSFW $sfw
     * @return array|bool(false)
     */
    public static function ctGetSettings(CleantalkSFW $sfw )
    {

        $get_settings = 'SELECT * FROM `cleantalk_sfw_settings`';
        $sfw->unversal_query( $get_settings, true );
        $sfw->unversal_fetch_all();
        $settings_from_db = $sfw->get_db_result_data();

        $settings = array();
        foreach( $settings_from_db as $key => $value ) {
            $settings[$value['setting_name']] = $value['setting_value'];
        }

        return $settings;

    }

    /**
     * Write settings to DB instead Antispam.store.dat file
     *
     * @param $settings
     */
    public static function ctWriteSettings( CleantalkSFW $sfw, $settings )
    {
        if( is_array($settings) && !empty($settings) ) {

            foreach( $settings as $setting_name => $setting_value ) {
                $delete_row = 'DELETE FROM `cleantalk_sfw_settings` WHERE `setting_name` = \'' . $setting_name . '\'';
                $sfw->unversal_query( $delete_row, true );
                $write_setting = 'INSERT INTO `cleantalk_sfw_settings` VALUES (\'' . $setting_name . '\', \'' . $setting_value . '\')';
                $sfw->unversal_query( $write_setting, true );
            }

        } else {
            return;
        }
        return;
    }

    public static function apbct_cookie__set($name, $value = '', $expires = 0, $path = '/', $domain = null, $secure = false, $httponly = false, $samesite = null ){

        // For PHP 7.3+ and above
        if( version_compare( phpversion(), '7.3.0', '>=' ) ){

            $params = array(
                'expires'  => $expires,
                'path'     => $path,
                'domain'   => $domain,
                'secure'   => $secure,
                'httponly' => $httponly,
            );

            if($samesite)
                $params['samesite'] = $samesite;

            setcookie( $name, $value, $params );

        // For PHP 5.6 - 7.2
        } else {

            setcookie( $name, $value, $expires, $path, $domain, $secure, $httponly );

        }

    }

}
