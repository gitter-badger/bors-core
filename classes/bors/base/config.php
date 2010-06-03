<?php

class base_config extends base_empty
{
	function __construct(&$object)
	{
		parent::__construct($object);

		foreach($this->config_data() as $key => $value)
			$object->set($key, $value, false);
	}

	function template_init()
	{
		$object = $this->id();
		foreach($this->template_data() as $key => $value)
		{
			if(strpos($key, '['))
				$object->add_template_data_array($key, $value);
			else
				$object->add_template_data($key, $value);
		}

		foreach($this->template_data_array() as $key => $value)
			$object->add_template_data_array($key, $value);
	}

	function config_data() { return array(); }
	function template_data_array() { return array(); }

	function template_data()
	{
		$data = array(
			'success_message' => session_var('success_message'),
			'notice_message'  => session_var('notice_message'),
			'error_message'   => session_var('error_message'),
		);

		set_session_var('success_message', NULL);
		set_session_var('notice_message', NULL);
		set_session_var('error_message', NULL);
		return $data;
	}
}
