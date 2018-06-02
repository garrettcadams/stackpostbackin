<?php
class MY_Model extends CI_Model
{
	function __construct(){
		parent::__construct();
		// Load the Database Module REQUIRED for this to work.
		$this->load->database();//Without it -> Message: Undefined property: XXXController::$db
	}
	
	function fetch($select = "*", $table = "", $where = "", $order = "", $by = "DESC", $start = -1, $limit = 0, $return_array = false)
	{
		$this->db->select($select);
		if($where != "")
		{
			$this->db->where($where);
		}
		if($order != "" && (strtolower($by) == "desc" || strtolower($by) == "asc"))
		{
			if($order == 'rand'){
				$this->db->order_by('rand()');
			}else{
				$this->db->order_by($order, $by);
			}
		}
		
		if((int)$start >= 0 && (int)$limit > 0)
		{
			$this->db->limit($limit, $start);
		}
		#Query
		$query = $this->db->get($table);
		if($return_array){
			$result = $query->result_array();
		} else {
			$result = $query->result();
		}
		$query->free_result();
		return $result;
	}	
	
	function get($select = "*", $table = "", $where = "", $order = "", $by = "DESC", $return_array = false)
	{
		$this->db->select($select);
		if($where != "")
		{
			$this->db->where($where);
		}
		if($order != "" && (strtolower($by) == "desc" || strtolower($by) == "asc"))
		{
			if($order == 'rand'){
				$this->db->order_by('rand()');
			}else{
				$this->db->order_by($order, $by);
			}
		}		
		#Query
		$query = $this->db->get($table);
		if($return_array){
			$result = $query->row_array();
		} else {
			$result = $query->row();
		}
		$query->free_result();

		return $result;
	}

	function schedule_report($table, $status=2){
		$value_string = "";
		$date_string = "";

		$date_list = array();
		$date = strtotime(date('Y-m-d', strtotime(NOW)));
		for ($i=29; $i >= 0; $i--) { 
			$left_date = $date - 86400 * $i;
			$date_list[date('Y-m-d', $left_date)] = 0;
		}

		//Get data
		$query = $this->db->query("SELECT COUNT(status) as count, DATE(time_post) as time_post FROM ".$table." WHERE status = '{$status}' AND uid = '".session("uid")."' AND time_post > NOW() - INTERVAL 30 DAY GROUP BY DATE(".$table.".time_post);");
		if($query->result()){
			

			foreach ($query->result() as $key => $value) {
				if(isset($date_list[$value->time_post])){
					$date_list[$value->time_post] = $value->count;
				}
			}

			
		}

		foreach ($date_list as $date => $value) {
			$value_string .= "{$value},";
			$date_string .= "'{$date}',";
		}

		$value_string = "[".substr($value_string, 0, -1)."]";
		$date_string  = "[".substr($date_string, 0, -1)."]";

		return (object)array(
			"value" => $value_string,
			"date" => $date_string
		);
		
	}

	function update_status($table, $ids, $check_user){
		if(!empty(post())){
			if($check_user){
				$where = array("uid" => session("uid"));
			}else{
				$where = array();
			}

			$item = $this->model->get("id, status", $table, "ids = '{$ids}'");
			if(!empty($item)){
				$where["id"] = $item->id;
				$status = $item->status == 0?1:0;
				$tag = $item->status == 0?"tag-success":"tag-danger";
				$text = $item->status == 0?lang("enable"):lang("disable");
				$this->db->update($table, array('status' => $status), $where);
				ms(array(
					"status"  => "success",
					"tag"     => $tag,
					"text"    => $text,
					"message" => lang("update_status_successfully")
				));
			}else{
				ms(array(
					"status"  => "error",
					"message" => lang("cannot_update_status_please_try_again")
				));
			}
		}else{
			load_404();
		}
		
	}

	function delete($table, $ids, $check_user){
		if(!empty(post())){
			if($check_user){
				$where = array("uid" => session("uid"));
			}else{
				$where = array();
			}

			if(!$ids){
				ms(array(
					"status"  => "error",
					"message" => lang("select_an_item_to_delete")
				));
			}

			if(is_array($ids)){
				foreach ($ids as $key => $id) {
					$where["ids"] = $id;
					$this->db->delete($table, $where);
				}
				
				ms(array(
					"status"  => "success",
					"message" => lang("delete_successfully")
				));
			}else{
				$item = $this->model->get("id", $table, "ids = '{$ids}'");
				if(!empty($item)){
					$where["id"] = $item->id;
					$this->db->delete($table, $where);
					ms(array(
						"status"  => "success",
						"message" => lang("delete_successfully")
					));
				}else{
					ms(array(
						"status"  => "error",
						"message" => lang("delete_failed_please_try_again")
					));
				}
			}
		}else{
			load_404();
		}
	}
	
	function history_ip($userid){
		$user = $this->model->get("id, history_ip", USERS, "id = '{$userid}'");	
		if(!empty($user)){
			$history_ip_old = (array)json_decode($user->history_ip);
			$history_ip = ($history_ip_old == "")?array():$history_ip_old;
			/*$finder = get_curl("http://ip-api.com/json");
			$finder = json_decode($finder);*/
			$history_ip[] = get_client_ip();

			if(count($history_ip) >= 10){
				array_shift($history_ip);
			}

			$this->db->update(USERS, array('history_ip' => json_encode($history_ip)), "id = '{$userid}'");
		}	
	}			

	function send_email($subject, $content, $userid){
		$user = $this->db->select("*")->from(USERS)->where("id", $userid)->get()->row();
		$package = $this->db->select("*")->from(PACKAGES)->where("type", 1)->get()->row();
		if(!empty($user)){
			//Send email
			$subject = nl2br($subject);
			$content = nl2br($content);

			$now = (int)strtotime(date("Y-m-d", strtotime(NOW)));
			$expiration_date = (int)strtotime($user->expiration_date);
			$days_left = ($expiration_date - $now)/(60*60*24);

			//Replace Subject
			$subject = str_replace("{full_name}", $user->fullname, $subject);
			$subject = str_replace("{days_left}", $days_left, $subject);
			$subject = str_replace("{expiration_date}", convert_date($user->expiration_date), $subject);
			$subject = str_replace("{trial_days}", $package->trial_day, $subject);
			$subject = str_replace("{email}", $user->email, $subject);
			$subject = str_replace("{activation_link}", cn("oauth/activation/".$user->activation_key), $subject);
			$subject = str_replace("{recovery_password_link}", cn("oauth/activation/".$user->reset_key), $subject);
			$subject = str_replace("{website_link}", cn(""), $subject);
			$subject = str_replace("{website_name}", get_option("website_title", "Stackposts - Social Marketing Tool"), $subject);

			//
			$content = str_replace("{full_name}", $user->fullname, $content);
			$content = str_replace("{days_left}", $days_left, $content);
			$content = str_replace("{expiration_date}", convert_date($user->expiration_date), $content);
			$content = str_replace("{trial_days}", $package->trial_day, $content);
			$content = str_replace("{email}", $user->email, $content);
			$content = str_replace("{activation_link}", cn("oauth/activation/".$user->activation_key), $content);
			$content = str_replace("{recovery_password_link}", cn("oauth/reset_password/".$user->reset_key), $content);
			$content = str_replace("{website_link}", cn(""), $content);
			$content = str_replace("{website_name}", get_option("website_title", "Stackposts - Social Marketing Tool"), $content);


			$template = Modules::run("email/template");
			$template = str_replace("{content}", $content, $template);

			$config = array(
				"mailtype" => "html",
				"charset" => "utf-8"
			);

			if(get_option("email_protocol_type", "mail") == "smtp" && get_option("email_smtp_server", "") != "" && get_option("email_smtp_port", "") != ""){

				$config['protocol'] = get_option("email_protocol_type", "mail");
				$config['smtp_host'] = get_option("email_smtp_server", "");
				$config['smtp_port'] = get_option("email_smtp_port", "");

				if(get_option("email_smtp_username", "") != "") $config['smtp_user'] = get_option("email_smtp_username", "");
				if(get_option("email_smtp_password", "") != "") $config['smtp_pass'] = get_option("email_smtp_password", "");
				if(get_option("email_smtp_encryption", "") != "")$config['smtp_crypto'] = get_option("email_smtp_encryption", "");
			}

			$this->load->library('email', $config);
			$this->email->from(get_option('email_from', '')?get_option('email_from', ''):"do-not-reply@gmail.com", get_option('email_name', '')?get_option('email_name', ''):get_option('website_title', 'Social Planer - Social Marketing Tool'));
			$this->email->to($user->email);
			$this->email->subject($subject);
			$this->email->message($template);

			@$this->email->send();

		}
	}
}
