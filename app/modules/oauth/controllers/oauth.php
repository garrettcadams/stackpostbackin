<?php
defined('BASEPATH') OR exit('No direct script access allowed');
 
class oauth extends MX_Controller {

	public function __construct(){
		parent::__construct();
		$this->load->model(get_class($this).'_model', 'model');

		if(session("uid") && segment(2) != 'logout'){
			redirect(cn());
		}
	}

	public function login(){
		$data = array();
		$this->template->set_layout('oauth_page');
		$this->template->build('signin', $data);
	}

	public function logout(){
		unset_session("uid");
		delete_cookie("mid");
		redirect(cn(''));
	}

	public function signup(){
		if(!get_option("singup_enable", 1)){
			redirect(cn("oauth/login"));
		}
		$data = array();
		$this->template->set_layout('oauth_page');
		$this->template->build('signup', $data);
	}

	public function forgot_password(){
		$data = array();
		$this->template->set_layout('oauth_page');
		$this->template->build('forgot_password', $data);
	}

	public function ajax_forgot_password(){
		$email = post("email");

		if($email == ""){
			ms(array(
				"status"  => "error",
				"message" => lang("please_enter_email")
			));
		}

		if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
		  	ms(array(
				"status"  => "error",
				"message" => lang("email_address_in_invalid_format")
			));
		}

		$user = $this->model->get("*", USERS, "email = '{$email}'");
		if(!empty($user)){
			$this->model->send_email(get_option("email_forgot_password_subject", ""), get_option("email_forgot_password_content", ""), $user->id);

			ms(array(
				"status" => "success",
				"message" => lang('email_address_does')
			));
		}else{
			ms(array(
				"status" => "error",
				"message" => lang('email_address_does_not_exist')
			));
		}
	}

	public function reset_password(){
		$user = $this->model->get("*", USERS, "reset_key = '".segment(3)."'");
		if(!empty($user)){
			$data = array();
			$this->template->set_layout('oauth_page');
			$this->template->build('change_password', $data);
		}else{
			redirect(cn("oauth/login"));
		}
	}

	public function ajax_change_password(){
		$reset_key = post("reset_key");
		$password = post("password");
		$confirm_password = post("confirm_password");
		$user = $this->model->get("*", USERS, "reset_key = '".$reset_key."'");

		if(!empty($user)){
			if(strlen($password) < 6){
				ms(array(
					"status"  => "error",
					"message" => lang("password_must_be_greater_than_5_characters")
				));
			}

			if($password != $confirm_password){
				ms(array(
					"status"  => "error",
					"message" => lang("password_does_not_match_the_confirm_password")
				));
			}

			$this->db->update(USERS, array(
				"password" => md5($password),
				"reset_key" => ids(),
			), array("id" => $user->id));

			ms(array(
				"status"  => "success",
				"message" => lang("successfully")
			));
		}else{
			redirect(cn("oauth/login"));
		}
 	}

	public function activation(){
		$user = $this->model->get("*", USERS, "activation_key = '".segment(3)."'");
		if(!empty($user)){
			$this->db->update(USERS, array("status" => 1, "activation_key" => "1"), array("id" => $user->id));
			if(get_option("email_welcome_enable", 1)){
				$this->model->send_email(get_option("email_new_customers_subject", ""), get_option("email_new_customers_content", ""), $user->id);
			}

			$this->template->set_layout('oauth_page');
			$this->template->build('activation_successfully');
		}else{
			redirect(cn("oauth/login"));
		}
	}

	public function ajax_register(){
		$ids      = post("ids");
		$fullname = post("fullname");
		$email    = post("email");
		$password = post("password");
		$timezone = post("timezone");
		$confirm_password = post("confirm_password");

		if($fullname == ""){
			ms(array(
				"status"  => "error",
				"message" => lang("please_enter_fullname")
			));
		}

		if($email == ""){
			ms(array(
				"status"  => "error",
				"message" => lang("please_enter_email")
			));
		}

		if(!filter_var(post("email"), FILTER_VALIDATE_EMAIL)){
		  	ms(array(
				"status"  => "error",
				"message" => lang("email_address_in_invalid_format")
			));
		}

		//
		$user_check = $this->model->get("id", USERS, "email = '{$email}'");
		if(!empty($user_check)){
			ms(array(
				"status"  => "error",
				"message" => lang("this_email_already_exists")
			));
		}

		if($password == ""){
			ms(array(
				"status"  => "error",
				"message" => lang("please_enter_password")
			));
		}

		if(strlen($password) <= 5){
			ms(array(
				"status"  => "error",
				"message" => lang("password_must_be_greater_than_5_characters")
			));
		}

		if($password != $confirm_password){
			ms(array(
				"status"  => "error",
				"message" => lang("password_does_not_match_the_confirm_password")
			));
		}

		$check_timezone = 0;
		foreach (tz_list() as $key => $value) {
			if($timezone == $value['zone']){
				$check_timezone = 1;
			}
		}

		if(!$check_timezone){
			ms(array(
				"status"  => "error",
				"message" => lang('timezone_is_required')
			));
		}

		$package = $this->model->get('*', PACKAGES, "type = 1");

		if(empty($package)){
			ms(array(
				"status"  => "error",
				"message" => lang('currently_we_do_not_provide_free_package')
			));
		}

		$data = array(
			"fullname"        => $fullname,
			"email"           => $email,
			"timezone"        => $timezone,
			"package"         => $package->id,
			"permission"      => $package->permission,
			"activation_key"  => ids(),
			"reset_key"       => ids(),
			"expiration_date" => date("Y-m-d", strtotime(NOW." + ".$package->trial_day." days")),
			"status"          => get_option("singup_verify_email_enable", 1)?0:1,
			"changed"         => NOW
		);

		$data["ids"]        = ids();
		$data["login_type"] = "direct";
		$data["password"]   = md5($password);
		$data["created"]    = NOW;
		$this->db->insert(USERS, $data);
		$uid = $this->db->insert_id();

		if(get_option("singup_verify_email_enable", 1)){
			$this->model->send_email(get_option("email_activation_subject", ""), get_option("email_activation_content", ""), $uid);

			ms(array(
				"status"  => "success",
				"message" => lang('thank_you_please_check_your_email_to_activate_your_subscription')
			));
		}else{
			if(get_option("email_welcome_enable", 1)){
				$this->model->send_email(get_option("email_new_customers_subject", ""), get_option("email_new_customers_content", ""), $uid);
			}
		}

		ms(array(
			"status"  => "success",
			"message" => lang("successfully")
		));
	}

	public function ajax_login(){
		$email = post("email");
		$password = md5(post("password"));
		$remember = post("remember");

		if($email == ""){
			ms(array(
				"status"  => "error",
				"message" => lang('email_is_required')
			));
		}

		if($password == ""){
			ms(array(
				"status"  => "error",
				"message" => lang('password_is_required')
			));
		}

		$user = $this->model->get("id,status,ids", USERS, "email = '$email' AND password = '$password'");

		if(!empty($user)){

			if($user->status != 1){
				ms(array(
					"status"  => "error",
					"message" => lang('your_account_is_not_activated')
				));
			}
			
			set_session("uid", $user->id);
			$this->model->history_ip($user->id);
			if($remember){
				set_cookie("mid", encrypt_encode($user->ids), 1209600);
			}

			ms(array(
				"status"  => "success",
				"message" => lang('login_successfully')
			));

		}else{
			ms(array(
				"status"  => "error",
				"message" => lang('the_email_address_you_entered_does_not_match_any_account')
			));
		}
	}

	public function facebook(){
		$this->load->library('facebook_oauth');

		$fb = new Facebook_oauth(get_option('facebook_oauth_app_id', ''), get_option('facebook_oauth_app_secret', ''));
		if(get("code")){
			$fb->get_access_token();
			$user_info = $fb->get_user_info();

			if(is_object($user_info)){
				$user = $this->model->get("*", USERS, "email = '{$user_info->email}'");
				if(empty($user)){
					$data = array(
						'ids' => ids(),
						'login_type' => "facebook",
						'fullname' => $user_info->name,
						'email' => $user_info->email,
						'password' => ids(),
						'reset_key' => ids(),
						'status' => 1,
						'changed' => NOW,
						'created' => NOW
					);

					//Set Package
					$package = $this->model->get("*", PACKAGES, "type = 1");
					if(!empty($package)){
						$data['package'] = $package->id;
						$data['permission'] = $package->permission;
						$data['expiration_date'] = date('Y-m-d', strtotime("+".$package->trial_day." days"));
					}

					$this->db->insert(USERS, $data);
					$uid = $this->db->insert_id();
					$this->model->history_ip($uid);
					set_session("uid", $uid);
					
				}else{
					$this->model->history_ip($user->id);
					set_session("uid", $user->id);
				}

				redirect(PATH);
			}else{
				redirect($loginurl);
			}
		}else{
			$loginurl = $fb->create_login_url();
			redirect($loginurl);
		}
	}

	public function google(){
		$this->load->library('google_oauth');
		$gg = new Google_oauth(get_option('google_oauth_client_id', ''), get_option('google_oauth_client_secret', ''));
		$loginurl = $gg->create_login_url();
		if(get("code")){
			$access_token = $gg->get_access_token();
			$user_info = $gg->get_user_info();

			if(is_object($user_info)){
				$user = $this->model->get("*", USERS, "email = '{$user_info->email}'");
				if(empty($user)){
					$data = array(
						'ids' => ids(),
						'login_type' => "google",
						'fullname' => $user_info->name,
						'email' => $user_info->email,
						'password' => ids(),
						'reset_key' => ids(),
						'status' => 1,
						'changed' => NOW,
						'created' => NOW
					);

					//Set Package
					$package = $this->model->get("*", PACKAGES, "type = 1");
					if(!empty($package)){
						$data['package'] = $package->id;
						$data['permission'] = $package->permission;
						$data['expiration_date'] = date('Y-m-d', strtotime("+".$package->trial_day." days"));
					}

					$this->db->insert(USERS, $data);
					$uid = $this->db->insert_id();
					$this->model->history_ip($uid);
					set_session("uid", $uid);
					
				}else{
					$this->model->history_ip($user->id);
					set_session("uid", $user->id);
				}

				redirect(PATH);
			}else{
				redirect($loginurl);
			}
		}else{
			redirect($loginurl);
		}
	}

	public function twitter(){
		$this->load->library('twitter_oauth');
		$tw = new Twitter_oauth(get_option('twitter_oauth_client_id', ''), get_option('twitter_oauth_client_secret', ''));
		$access_token = $tw->get_access_token();
		$tw->set_access_token(json_encode($access_token));

		$user_info = $tw->get_user_info();
		if(is_object($user_info)){
			$user = $this->model->get("*", USERS, "email = '{$user_info->email}'");
			if(empty($user)){
				$data = array(
					'ids' => ids(),
					'login_type' => "twitter",
					'fullname' => $user_info->name,
					'email' => $user_info->email,
					'password' => ids(),
					'reset_key' => ids(),
					'status' => 1,
					'changed' => NOW,
					'created' => NOW
				);

				//Set Package
				$package = $this->model->get("*", PACKAGES, "type = 1");
				if(!empty($package)){
					$data['package'] = $package->id;
					$data['permission'] = $package->permission;
					$data['expiration_date'] = date('Y-m-d', strtotime("+".$package->trial_day." days"));
				}

				$this->db->insert(USERS, $data);
				$uid = $this->db->insert_id();
				$this->model->history_ip($uid);
				set_session("uid", $uid);
				
			}else{
				$this->model->history_ip($user->id);
				set_session("uid", $user->id);
			}

			redirect(PATH);
		}else{
			$loginurl = $tw->login_url();
			redirect($loginurl);
		}
	}

	public function twitter_oauth(){
		$this->load->library('twitter_oauth');
		$tw = new Twitter_oauth(get_option('twitter_oauth_client_id', ''), get_option('twitter_oauth_client_secret', ''), TRUE);
		$loginurl = $tw->login_url();

		redirect($loginurl);
	}
}