<?php

class CTHooks {

	/**
	 * Some HTML&JS code for JavaScript test 
	 * @param HTMLForm $form
	 * @return bool
	 */
    public static function onShowEditForm( $editPage ) {
        global $wgCTSubmitTimeLabel;

        $editPage->editFormTextBottom = CTBody::AddJSCode();
        
        $_SESSION[$wgCTSubmitTimeLabel] = time();
        
        return true;
    }
	/**
	 * Some HTML&JS code for JavaScript test 
	 * @param HTMLForm $form
	 * @return bool
	 */
    public static function onUserCreateForm( &$template ) {
        global $wgCTSubmitTimeLabel;
        
        $template->set( 'header', CTBody::AddJSCode() );

        $_SESSION[$wgCTSubmitTimeLabel] = time();
        
        return true;
    }
	
    /**
	 * Edit spam test 
	 * @return bool
	 */
	public static function onEditFilter (  $editor, $text, $section, &$error, $summary ) {
        global $wgCTAccessKey, $wgCTServerURL, $wgRequest, $wgCTAgent, $wgCTExtName;
        
        $allowEdit = true;

        // Skip antispam test if editor member of special group
        if ( $editor->getArticle()->getContext()->getUser()->isAllowed('cleantalk-bypass') ) {
            return $allowEdit;
        }

        // The facility in which to store the query parameters
        $ctRequest = new CleantalkRequest();

        $ctRequest->auth_key = $wgCTAccessKey;
        $ctRequest->sender_email = $editor->getArticle()->getContext()->getUser()->mEmail; 
        $ctRequest->sender_nickname = $editor->getArticle()->getContext()->getUser()->mName;
        $ctRequest->message = $text; 
        $ctRequest->agent = $wgCTAgent;
        $ctRequest->sender_ip = $wgRequest->getIP(); 
        $ctRequest->js_on = CTBody::JSTest(); 
        $ctRequest->submit_time = CTBody::SubmitTimeTest(); 

        $ct = new Cleantalk();
        $ct->server_url = $wgCTServerURL;

        // Check
        $ctResult = $ct->isAllowMessage($ctRequest);

        // Allow edit if we have any API errors
        /*if ( $ctResult->errno != 0 ) {
            return $allowEdit;
        }*/
        if ( $ctResult->errno != 0 ) 
        {
        	if(CTBody::JSTest()!=1)
        	{
        		$ctResult->allow=0;
        		$ctResult->comment = "Forbidden. Please, enable Javascript.";
        		$allowEdit = false;
        	}
        	else
        	{
        		$ctResult->allow=1;
        		$allowEdit = true;
        	}
        }

        // Disallow edit with CleanTalk comment 
        if (!$allowEdit || $ctResult->allow == 0 && ($ctResult->spam || $ctResult->stop_queue)) {
            $error = $ctResult->comment;
            
            // Converting links to wikitext format
            $error = preg_replace("(<a\shref=\"([^\s]+)\".+>([a-f0-9]+)</a>)", "[$1 $2]", $error);
            
            $error = Html::openElement( 'div', array( 'class' => 'errorbox' ) ) .
               $error . 
               Html::closeElement( 'div' ) . "\n" .
               Html::element( 'br', array( 'clear' => 'all' ) ) . "\n";
        }
       
        if ($ctResult->inactive === 1) {
            CTBody::SendAdminEmail( $wgCTExtName, $ctResult->comment );
        }

        return $allowEdit;
	}
    
    /**
	 * Account spam test 
	 * @return bool
	 */
	public static function onAbortNewAccount ( $user, &$message ) {
        global $wgCTAccessKey, $wgCTServerURL, $wgRequest, $wgCTAgent, $wgCTExtName;
        
        $allowAccount = true;
        
        // The facility in which to store the query parameters
        $ctRequest = new CleantalkRequest();

        $ctRequest->auth_key = $wgCTAccessKey;
        $ctRequest->sender_email = $user->mEmail; 
        $ctRequest->sender_nickname = $user->mName; 
        $ctRequest->agent = $wgCTAgent;
        $ctRequest->sender_ip = $wgRequest->getIP(); 
        $ctRequest->js_on = CTBody::JSTest(); 
        $ctRequest->submit_time = CTBody::SubmitTimeTest(); 

        $ct = new Cleantalk();
        $ct->server_url = $wgCTServerURL;

        // Check
        $ctResult = $ct->isAllowUser($ctRequest);

        // Allow account if we have any API errors
        if ( $ctResult->errno != 0 ) 
        {
        	if(CTBody::JSTest()!=1)
        	{
        		$ctResult->allow=0;
        		$ctResult->comment = "Forbidden. Please, enable Javascript.";
        	}
        	else
        	{
        		$ctResult->allow=1;
        	}
        }

        // Disallow account with CleanTalk comment 
        if ($ctResult->allow == 0) {
            $allowAccount = false;
            $message = $ctResult->comment;
        }
        
        if ($ctResult->inactive === 1) {
            CTBody::SendAdminEmail( $wgCTExtName, $ctResult->comment );
        }

        return $allowAccount;
	}
}
