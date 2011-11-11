<?php

class bors_tools_search extends base_page
{
	function class_file() { return __FILE__; } // не удалять, шаблон в субклассах.
	function template() { template_noindex(); return 'forum/_header.html'; }

	function parents()
	{
		if(empty($_GET['t']))
			return array('/tools/', '/forum/');
		else
			return array(object_load('forum_topic', $_GET['t']));
	}

	function pre_parse()
	{
//		$url = $this->url();
		$url = bors()->request()->url();
		$clean_url = url_clean_params($url);
//		echo "'$url' => '$clean_url'<br/>";
		if($url != $clean_url)
			return go($clean_url);

		return parent::pre_parse();
	}

	function title() { return ec('Поиск по форуму'); }
	function nav_name() { return ec('поиск'); }
	function total_items() { return 0; }
	function q() { return ''; }
	function f()
	{
		$f = @$_GET['f'];
		if(!is_array($f))
			$f = explode(',', urldecode($f));

		return $f;
	}
	function t() { return ''; }
	function s() { return 't'; }
	function x() { return false; }
	function u() { return ''; }
	function w() { return 'q'; }

	function access() { return $this; }
	function can_action() { return true; }
	function can_read() { return true; }

	function url() { return '/tools/search/'; }
	// Для исправной работы старых кривых ссылоки вида http://balancer.ru/tools/search/result/?q=%D1%82%D1%8D%D0%BC2%D1%83&w=a&s=r&class_name=bors_tools_search
	function skip_save() { return true; }
}
