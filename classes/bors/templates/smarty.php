<?php

require_once(config('smarty_path').'/Smarty.class.php');

class bors_templates_smarty extends bors_templates_abstract
{
	function render()
	{
		$template_file = $this->full_path();
		if(!$template_file)
			return ec("Ошибка: не найден файл шаблона");

		$smarty = &new Smarty;
		require('smarty-register.php');

		$smarty->compile_dir = secure_path(config('cache_dir').'/smarty/templates_c/');
//		$smarty->use_sub_dirs = true;
		$smarty->plugins_dir = array();
		foreach(bors_dirs() as $dir)
			$smarty->plugins_dir[] = $dir.'/engines/smarty/plugins';

		$smarty->plugins_dir[] = 'plugins';

		$smarty->cache_dir   = secure_path(config('cache_dir').'/smarty/cache/');

		if(!@file_exists($smarty->compile_dir))
			@mkpath($smarty->compile_dir, 0777);
		@chmod($smarty->compile_dir, 0777);
		if(!@file_exists($smarty->cache_dir))
			@mkpath($smarty->cache_dir, 0777);
		@chmod($smarty->cache_dir, 0777);

		$obj = $this->object();

		$caching = !$obj->is_cache_disabled() && config('templates_cache_disabled') !== true;

		$smarty->caching = false;// $caching;
		$smarty->compile_check = true; 
		$smarty->php_handling = SMARTY_PHP_QUOTE; //SMARTY_PHP_PASSTHRU;
		$smarty->security = false;
		$smarty->cache_modified_check = true;
		$smarty->cache_lifetime = 86400*7;

//	$smarty->assign("views_average", sprintf("%.1f",86400*$views/($views_last-$views_first+1)));
		$smarty->assign("main_uri", @$GLOBALS['main_uri']);
		$smarty->assign("now", time());
		$smarty->assign("ref", @$_SERVER['HTTP_REFERER']);
		$smarty->assign("this", $obj);

		//TODO: убрать user_id и user_name в старых шаблонах.
		$me = bors()->user();
		$smarty->assign("me", $me);
		if($me)
		{
			$smarty->assign("my_id", $me->id());
			$smarty->assign("my_name", $me->title());
		}

//		foreach(explode(' ', $obj->template_vars()) as $var)
//			$smarty->assign($var, $obj->$var());

		foreach(explode(' ', $obj->template_local_vars()) as $var)
			$smarty->assign($var, $obj->$var());

		foreach($obj->local_template_data_array() as $var => $value)
			$smarty->assign($var, $value);

//		$template = smarty_template($template ? $template : $obj->template());
		$template = $this->full_path();
		if(!$smarty->template_exists($template))
			$template = smarty_template($template);

		if(!$smarty->template_exists($template))
			return "Not existing template {$template} for $obj<br />";

		$smarty->template_dir = dirname(preg_replace("!^xfile:!", "", $template));
		$smarty->assign("page_template", $template);

		if(!empty($GLOBALS['cms']['templates']['data']))
			foreach($GLOBALS['cms']['templates']['data'] as $key => $value)
			{
//				echo "assign data '$key' = '$value'<br />";
				$smarty->assign($key, $value);
			}

//		if(!$caching)
//			$smarty->clear_cache($template, $obj->url());

/*		if($global)
		{
			foreach($obj->global_template_data_array() as $var => $value)
				$smarty->assign($var, $value);

			foreach($obj->global_template_data_set() as $var => $value)
				$smarty->assign($var, $value);
		}
*/
		$out = $smarty->fetch($template);

		$out = preg_replace("!<\?php(.+?)\?>!es", "do_php(stripq('$1'))", $out);

		return $out;
	}

	function full_path()
	{
		echo "Get tpl {$this->template}<br/>\n";
		return parent::full_path();
	}
}