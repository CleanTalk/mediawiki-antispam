<?php
require_once('CleantalkHelper.php' );
/*
 * CleanTalk SpamFireWall base class
 * Compatible only with Wordpress.
 * Version 2.0-wp
 * author Cleantalk team (welcome@cleantalk.org)
 * copyright (C) 2014 CleanTalk team (http://cleantalk.org)
 * license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 * see https://github.com/CleanTalk/php-antispam
*/

class CleantalkSFW extends CleantalkHelper
{
	public $ip = 0;
	public $ip_str = '';
	public $ip_array = Array();
	public $ip_str_array = Array();
	public $blocked_ip = '';
	public $passed_ip = '';
	public $result = false;

	//Database variables
	private $table_prefix;
	private $db;
	private $query;
	private $db_result;
	private $db_result_data = array();

	public function __construct()
	{
		$this->table_prefix = "";
		$this->db = wfGetDB(DB_MASTER);
	}

	public function unversal_query($query, $straight_query = false)
	{
		if($straight_query){
			$this->db_result = $this->db->query($query);
		}
		else
			$this->query = $query;
	}

	public function unversal_fetch()
	{
		$this->db_result_data = $this->db_result->fetchRow();
	}

	public function unversal_fetch_all()
	{
		while ($row = $this->db_result->fetchRow()){
			$this->db_result_data[] = $row;
		}
	}

	public function get_db_result_data()
    {
	    return $this->db_result_data;
    }


	/*
	*	Getting arrays of IP (REMOTE_ADDR, X-Forwarded-For, X-Real-Ip, Cf_Connecting_Ip)
	*	reutrns array('remote_addr' => 'val', ['x_forwarded_for' => 'val', ['x_real_ip' => 'val', ['cloud_flare' => 'val']]])
	*/
	static public function ip_get($ips_input = array('real', 'remote_addr', 'x_forwarded_for', 'x_real_ip', 'cloud_flare'), $v4_only = true){

		$result = (array)parent::ip_get($ips_input, $v4_only);

		$result = !empty($result) ? $result : array();

		if(isset($_GET['sfw_test_ip'])){
			if(self::ip_validate($_GET['sfw_test_ip']) !== false)
				$result['sfw_test'] = $_GET['sfw_test_ip'];
		}

		return $result;

	}

	/*
	*	Checks IP via Database
	*/
	public function check_ip(){

		foreach($this->ip_array as $current_ip){

			$query = "SELECT 
				COUNT(network) AS cnt
				FROM ".$this->table_prefix."cleantalk_sfw
				WHERE network = ".sprintf("%u", ip2long($current_ip))." & mask";
			$this->unversal_query($query,true);
			$this->unversal_fetch();

			if($this->db_result_data['cnt']){
				$this->result = true;
				$this->blocked_ip = $current_ip;
			}else{
				$this->passed_ip = $current_ip;
			}
		}
	}

	/*
	*	Add entry to SFW log
	*/
	public function sfw_update_logs($ip, $result){

		if($ip === NULL || $result === NULL){
			return;
		}

		$blocked = ($result == 'blocked' ? ' + 1' : '');
		$time = time();

		$query = "INSERT INTO ".$this->table_prefix."cleantalk_sfw_logs
		SET 
			ip = '$ip',
			all_entries = 1,
			blocked_entries = 1,
			entries_timestamp = '".intval($time)."'
		ON DUPLICATE KEY 
		UPDATE 
			all_entries = all_entries + 1,
			blocked_entries = blocked_entries".strval($blocked).",
			entries_timestamp = '".intval($time)."'";

		$this->unversal_query($query,true);
	}

	/*
	* Updates SFW local base
	*
	* return mixed true || array('error' => true, 'error_string' => STRING)
	*/
	public function sfw_update($ct_key){

		$result = self::api_method__get_2s_blacklists_db($ct_key);

		if(empty($result['error'])){

			$this->unversal_query("TRUNCATE TABLE ".$this->table_prefix."cleantalk_sfw",true);

			// Cast result to int
			foreach($result as $value){
				$value[0] = intval($value[0]);
				$value[1] = intval($value[1]);
			} unset($value);

			$query="INSERT INTO ".$this->table_prefix."cleantalk_sfw VALUES ";
			for($i=0, $arr_count = count($result); $i < $arr_count; $i++){
				if($i == count($result)-1){
					$query.="(".$result[$i][0].",".$result[$i][1].")";
				}else{
					$query.="(".$result[$i][0].",".$result[$i][1]."), ";
				}
			}
			$this->unversal_query($query,true);

			return true;

		}else{
			return $result;
		}
	}

	/*
	* Sends and wipe SFW log
	*
	* returns mixed true || array('error' => true, 'error_string' => STRING)
	*/
	public function send_logs($ct_key){

		//Getting logs
		$query = "SELECT * FROM ".$this->table_prefix."cleantalk_sfw_logs";
		$this->unversal_query($query,true);
		$this->unversal_fetch_all();

		if(count($this->db_result_data)){

			//Compile logs
			$data = array();
			foreach($this->db_result_data as $key => $value){
			    $ip = isset($value['ip']) ? trim($value['ip']) : '';
                $all_entries = isset($value['all_entries']) ? $value['all_entries'] : 0;
                $blocked_entries_diff = isset($value['blocked_entries']) && isset($value['all_entries']) ? $value['all_entries'] - $value['blocked_entries'] : 0;
                $entries_timestamp = isset($value['entries_timestamp']) ? $value['entries_timestamp'] : time();
				$data[] = array($ip, $all_entries, $blocked_entries_diff, $entries_timestamp);
			}
			unset($key, $value);

			//Sending the request
			$result = self::api_method__sfw_logs($ct_key, $data);

			//Checking answer and deleting all lines from the table
			if(empty($result['error'])){
				if($result['rows'] == count($data)){
					$this->unversal_query("TRUNCATE TABLE ".$this->table_prefix."cleantalk_sfw_logs",true);
					return true;
				}
			}else{
				return $result;
			}

		}else{
			return array('error' => true, 'error_string' => 'NO_LOGS_TO_SEND');
		}
	}

	/*
	* Shows DIE page
	*
	* Stops script executing
	*/
	public function sfw_die($api_key, $cookie_prefix = '', $cookie_domain = ''){

		// File exists?
		if(file_exists(dirname(__FILE__) . '/sfw_die_page.html')){
			$sfw_die_page = file_get_contents(dirname(__FILE__) . '/sfw_die_page.html');
		}else{
			die("IP BLACKLISTED");
		}

		// Service info
		$sfw_die_page = str_replace('{REMOTE_ADDRESS}', $this->blocked_ip, $sfw_die_page);
		$sfw_die_page = str_replace('{REQUEST_URI}', $_SERVER['REQUEST_URI'], $sfw_die_page);
		$sfw_die_page = str_replace('{SFW_COOKIE}', md5($this->blocked_ip.$api_key), $sfw_die_page);

		// Headers
		if(headers_sent() === false){
			header('Expires: '.date(DATE_RFC822, mktime(0, 0, 0, 1, 1, 1971)));
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: post-check=0, pre-check=0', FALSE);
			header('Pragma: no-cache');
			header("HTTP/1.0 403 Forbidden");
			$sfw_die_page = str_replace('{GENERATED}', "", $sfw_die_page);
		}else{
			$sfw_die_page = str_replace('{GENERATED}', "<h2 class='second'>The page was generated at&nbsp;".date("D, d M Y H:i:s")."</h2>",$sfw_die_page);
		}
		
		die($sfw_die_page);

	}
}
