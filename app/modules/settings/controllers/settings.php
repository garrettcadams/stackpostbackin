<?php
defined('BASEPATH') OR exit('No direct script access allowed');
 
class settings extends MX_Controller {

	public function __construct(){
		parent::__construct();
		$this->load->model(get_class($this).'_model', 'model');
	}

	public function general(){
		$data = array();
		$this->template->build('index', $data);
	}

	public function ajax_settings(){
		$data = $this->input->post();
		if(is_array($data)){
			foreach ($data as $key => $value) {
				update_option($key, $value);
			}
		}

		ms(array(
        	"status"  => "success",
        	"message" => lang('update_successfully'),
        ));
	}

	public function social(){
		$data = array();
		$this->template->build('social', $data);
	}

	public function ajax_social_settings(){
		$data = $this->input->post();
		if(is_array($data)){
			foreach ($data as $key => $value) {
				update_option($key, $value);
			}
		}

		ms(array(
        	"status"  => "success",
        	"message" => lang('update_successfully')
        ));
	}

	public function ajax_general_settings(){
		$data = $this->input->post();
		if(is_array($data)){
			foreach ($data as $key => $value) {
				update_setting($key, $value);
			}
		}

		ms(array(
        	"status"  => "success",
        	"message" => lang('update_successfully')
        ));
	}
	
}