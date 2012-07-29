<?php

class bors_core_object_defaults
{
	// airbase_common_forum => airbase
	static function project_name($object) { return array_shift(explode('_', $object->class_name())); }
//	static function access_name($object) { return bors_plural(array_pop(explode('_', $object->class_name()))); }

	static function _obsolete_project_name($object)
	{
		if($name = config('project.name'))
			return $name;

		$class_file = $object->class_file();
		// "/data/var/www/ru/wrk/ucrm/bors-site/classes/ucrm/projects/main.yaml"
		$name = preg_replace('!^.+/(\w+)/bors-site/.+$!', '$1', $class_file);

		if(!preg_match('/^\w+$/', $name))
			bors_throw(ec('Не задано имя проекта и не получилось его вычислить через class_file=').$class_file.ec(' для класса ').$object->class_name());

		return $name;
	}

	// ucrm_company_type => company
	static function section_name($object)
	{
		$class_file = $object->class_file();
		// "/data/var/www/ru/wrk/ucrm/bors-site/classes/ucrm/projects/main.yaml"
		$name = preg_replace('!^.+/bors-site/classes/'.$object->project_name().'(/admin)?/(\w+)/.+$!', '$2', $class_file);

		if(!preg_match('/^\w+$/', $name))
		{
			// "/data/var/www/ru/wrk/ucrm/bors-site/classes/ucrm/person.yaml"
			$name = bors_plural(preg_replace('!^.+/bors-site/classes/'.$object->project_name().'(/admin)?/(\w+)\.\w+.+$!', '$2', $class_file));
		}

		if(!preg_match('/^\w+$/', $name))
			bors_throw(ec('Не задано имя раздела сайта и не получилось его вычислить через class_file=').$class_file);

		return $name;
	}

	static function config_class($object)
	{
		return join('_', array_filter(array($object->project_name(), $object->section_name(), 'config')));
	}

	static function item_name($class_name)
	{
		return array_pop(explode('_', $class_name));
	}
}