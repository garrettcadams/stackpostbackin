<?php

if(!function_exists('lang_builder')){
    function lang_builder($dir){
    	$CI = &get_instance();
    	$languages = $CI->model->fetch("*", LANGUAGE, "code = 'en'");
    	$lang_currrent = array();
    	foreach ($languages as $key => $language) {
    		$lang_currrent[$language->slug] = $language->text;
    	}

    	if(!isset($lang)){
    		$lang = array();
    	}

        $ffs = scandir($dir);

        unset($ffs[array_search('.', $ffs, true)]);
        unset($ffs[array_search('..', $ffs, true)]);

        // prevent empty ordered elements
        if (count($ffs) < 1)
            return;

        foreach($ffs as $ff){
        	if(stripos($ff, "_lang.php")){
        		include $dir."/".$ff;
        		foreach ($lang as $key => $text) {
        			if(!array_key_exists($key, $lang_currrent) && $key != ""){
        				$CI->db->insert(LANGUAGE, array(
        					"ids" => ids(),
        					"code" => "en",
        					"slug" => $key,
        					"text" => $text
        				));
        			}
        		}
        	}
            
            if(!stripos($dir, "system")){
            	if(is_dir($dir.'/'.$ff)) lang_builder($dir.'/'.$ff);
        	}
        }
    }
}

function set_language($code){
    $CI = &get_instance();
    $language = $CI->db->select("*")->where("code", $code)->get(LANGUAGE_LIST)->row();

    if(empty($language) OR $language->code == "en")
    {
        $language = $CI->db->select("*")->where("code", "en")->get(LANGUAGE_LIST)->row();
    }

    set_session("lang_default", json_encode($language));

    return json_decode(session("lang_default"));
}

function get_default_language(){
    $check_default_language = json_decode(session("lang_default"));
    if(!is_object($check_default_language)){
        $CI = &get_instance();
        $language = $CI->db->select("*")->where("is_default", 1)->get(LANGUAGE_LIST)->row();

        if(empty($language) OR $language->code == "en")
        {
            $language = $CI->db->select("*")->where("code", "en")->get(LANGUAGE_LIST)->row();
        }

        set_session("lang_default", json_encode($language));
    }

    return json_decode(session("lang_default"));
}


if(!function_exists('lang')){
    function lang($key = ""){
        $lang_default = get_default_language();
        $CI = &get_instance();
        if(empty($lang_default)) return $CI->lang->line($key);

        $lang = $CI->db->select("*")->where("code", $lang_default->code)->where("slug", $key)->get(LANGUAGE)->row();
        if(!empty($lang)){
            return $lang->text;
        }else{
            return $CI->lang->line($key);
        }
        
    }
}