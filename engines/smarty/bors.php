<?php  

require_once("bors_smarty_common.php");

function template_assign_bors_object($obj, $template = NULL)
{
		require_once('smarty/Smarty.class.php');
		$smarty = &new Smarty;
		require('smarty-register.php');

		$smarty->compile_dir = config('cache_dir').'/smarty-templates_c/';
		$smarty->plugins_dir = dirname(__FILE__).'/plugins/';
		$smarty->cache_dir   = config('cache_dir').'/smarty-cache/';

		if(!file_exists($smarty->compile_dir))
			mkdir($smarty->compile_dir, 0775, true);
		if(!file_exists($smarty->cache_dir))
			@mkdir($smarty->cache_dir, 0775, true);

		$caching = !$obj->is_cache_disabled() && config('templates_cache_disabled') !== true;
			
		$smarty->caching = false;// $caching;
		$smarty->compile_check = true; 
		$smarty->php_handling = SMARTY_PHP_QUOTE; //SMARTY_PHP_PASSTHRU;
		$smarty->security = false;
		$smarty->cache_modified_check = true;
		$smarty->cache_lifetime = 86400*7;

//		$smarty->assign("views_average", sprintf("%.1f",86400*$views/($views_last-$views_first+1)));
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
		
		foreach(split(' ', $obj->template_vars()) as $var)
			$smarty->assign($var, $obj->$var());
		
		foreach(split(' ', $obj->template_local_vars()) as $var)
			$smarty->assign($var, $obj->$var());
		

		$template = smarty_template($template ? $template : $obj->template());
		$smarty->template_dir = dirname(preg_replace("!^xfile:!", "", $template));
		$smarty->assign("page_template", $template);
		
		if(!empty($GLOBALS['cms']['templates']['data']))
            foreach($GLOBALS['cms']['templates']['data'] as $key => $value)
			{
//				echo "assign data $key to $value<br />";
       	        $smarty->assign($key, $value);
			}

//		if(!$caching)
//			$smarty->clear_cache($template, $obj->url());

		if(!empty($GLOBALS['stat']['start_microtime']))
		{
		    list($usec, $sec) = explode(" ",microtime());
   	        $smarty->assign("make_time", sprintf("%.3f", ((float)$usec + (float)$sec) - $GLOBALS['stat']['start_microtime']));

		}

//		echo "*** queries_time = {$GLOBALS['stat']['queries_time']}<br />\n";
		$smarty->assign("queries_time", sprintf("%.3f", @$GLOBALS['stat']['queries_time']));
		$smarty->assign("queries", @$GLOBALS['global_db_queries']);

//		echo "Template=$template, caching=$caching";
//		echo "is cached=".$smarty->is_cached($template);
		$out = $smarty->fetch($template);
//		$out = $smarty->fetch($template);
//		echo "is cached=".$smarty->is_cached($template);

/*		$out = preg_replace("!<\?php(.+?)\?>!es", "do_php(stripslashes('$1'))", $out); */

		return $out;
	}
