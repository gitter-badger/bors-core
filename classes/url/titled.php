<?php

global $bors_url_titled_cache;
$bors_url_titled_cache = array();

class url_titled extends url_base
{
	function url_ex($args)
	{
		if(is_array($args))
			$page = defval($args, 'page');
		else
			$page = $args;

		global $bors_url_titled_cache;
		$obj = $this->id();

		if(!is_object($obj))
			debug_exit("Unknown url_titled '{$this->id()}'");

		if(preg_match("!^http://!", $obj->id()))
			return $obj->id();

		if($page === NULL)
			$page = $obj->page();

		@list($prefix, $prefix_lp, $suffix) = @$bors_url_titled_cache[$obj->internal_uri()];
		if(!$prefix)
		{
			require_once("inc/urls.php");
			$prefix    = $obj->base_url().strftime("%Y/%m/", $obj->create_time());
			$prefix_lp = $obj->base_url().strftime("%Y/%m/", $obj->modify_time());

			$uri_name = $obj->uri_name();
			if(strlen($uri_name) > 3)
				$uri_name .= '-';

			$infix = $uri_name.$obj->id();

			$prefix .= $infix;
			$prefix_lp .= $infix;

			if(!($suffix = substr(translite_uri_simple($obj->title()), 0, 60)))
				$suffix = '~';

			$suffix = '--'.$suffix;

			$bors_url_titled_cache[$obj->internal_uri()] = array($prefix, $prefix_lp, $suffix);
		}

		$lp = $obj->total_pages() == $page;
		$uri = $lp ? $prefix_lp : $prefix;

		if($page && $page != 1 && $page != -1)
			$uri .= ",$page";

		return $uri . $suffix . ($lp ? '.'.$obj->modify_time()%10000 : '') . ".html";
	}
}
