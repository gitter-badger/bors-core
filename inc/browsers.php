<?php

function get_browser_info($user_agent)
{
	$os = "";
	if(preg_match("!Linux!", $user_agent))
		$os = "Linux";
	elseif(preg_match("!Windows CE; PPC!", $user_agent))
		$os = "PocketPC";
	elseif(preg_match("!J2ME!", $user_agent))
		$os = "J2ME";
	elseif(preg_match("!Windows NT 6.0!", $user_agent))
		$os = "WindowsVista";
	elseif(preg_match("!Windows NT 5.(1|2)!", $user_agent))
		$os = "WindowsXP";
	elseif(preg_match("!Windows NT 5.0!", $user_agent))
		$os = "Windows2000";
	elseif(preg_match("!Windows 98!", $user_agent))
		$os = "Windows98";
	elseif(preg_match("!Win98!", $user_agent))
		$os = "Windows98";
	elseif(preg_match("!Windows!i", $user_agent))
		$os = "Windows";

	$browser="";
	if(preg_match("!Opera!", $user_agent))
		$browser="Opera";
	if(preg_match("!Konqueror!", $user_agent))
		$browser="Konqueror";
	elseif(preg_match("!SeaMonkey!", $user_agent))
		$browser = "SeaMonkey";
	elseif(preg_match("!Firefox!", $user_agent))
		$browser = "Firefox";
	elseif(preg_match("!Gecko!", $user_agent))
		$browser = "Gecko";
	elseif(preg_match("!MSIE!", $user_agent))
		$browser = "MSIE";

	if(preg_match("!Akregator!", $user_agent))
	{
		$browser = "Akregator";
		$os = "Linux";
	}

	if(preg_match("!Yahoo!", $user_agent))
	{
		$browser = "YahooBot";
		$os = "YahooBot";
	}

	if(preg_match("!Rambler!", $user_agent))
	{
		$browser = "RamblerBot";
		$os = "RamblerBot";
	}

	if(preg_match("!Googlebot!", $user_agent))
	{
		$browser = "GoogleBot";
		$os = "GoogleBot";
	}

	if(preg_match("!msnbot!", $user_agent))
	{
		$browser = "MSNBot";
		$os = "MSNBot";
	}

	if(preg_match("!WebAlta!", $user_agent))
	{
		$browser = "WebAltaBot";
		$os = "WebAltaBot";
	}

	if(preg_match("!Anonymouse.org!", $user_agent))
	{
		$browser = "Anonymouse.org";
		$os = "Anonymouse.org";
	}

	if(preg_match("!libwww-perl!", $user_agent))
	{
		$browser = "libwww-perl";
		$os = "libwww-perl";
	}

	if(preg_match("!Download Master!", $user_agent))
	{
		$browser = "Download Master";
		$os = "Windows";
	}

	if(preg_match("!Yandex!", $user_agent))
	{
		$browser = "YandexBot";
		$os = "YandexBot";
	}

	return array($os, $browser);
}