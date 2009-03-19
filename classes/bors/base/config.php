<?php

class base_config extends base_empty
{
	function __construct(&$object)
	{
//		echo "Config $object<br />\n";

		parent::__construct($object);
	
//		print_d($this->config_data());
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
	function template_data() { return array(); }
	function template_data_array() { return array(); }
}
