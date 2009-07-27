<?php

include_once('../config.php');
include_once(BORS_CORE.'/config.php');

main($argv);

function main($argv)
{
	$class_name = $argv[2];

//	$class_file = secure_path(class_include($argv[1]));
//	$src = file_get_contents($class_file);

	$cls = new $class_name(NULL);
	foreach($cls->main_table_fields() as $property => $db_field)
	{
		if(is_numeric($property))
			$property = $db_field;

		if($property == 'id')
			continue;

		if(!method_exists($cls, $property))
			echo "function {$property}() { return @\$this->data['{$property}']; }\n";
		if(!method_exists($cls, "set_{$property}"))
			echo "function set_{$property}(\$v, \$dbup) { return \$this->set('{$property}', \$v, \$dbup); }\n";
	}
}