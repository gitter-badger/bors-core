<?php

function bors_bot_detect($user_agent)
{
	foreach(array(
			'Begun Robot Crawler' => 'Begun Robot Crawler',
			'DotBot' => 'DotBot',		// Mozilla/5.0 (compatible; DotBot/1.1; http://www.dotnetdotcom.org/, crawler@dotnetdotcom.org)
			'google' => 'Googlebot',	// Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)
			'msnbot' => 'MSN', 			// msnbot/2.0b (+http://search.msn.com/msnbot.htm)
			'Nigma' => 'Nigma',
			'princeton crawler' => 'princeton crawler',	// nu_tch-princeton/Nu_tch-1.0-dev (princeton crawler for cass project; http://www.cs.princeton.edu/cass/; zhewang a_t cs ddot princeton dot edu)
			'rambler' => 'Rambler',
			'OOZBOT' => 'OOZBOT', 		// OOZBOT/0.20 ( http://www.setooz.com/oozbot.html ; agentname at setooz dot_com )
			'ovalebot' => 'ovalebot',	// ovalebot3.ovale.ru facepage
			'Tagoobot' => 'Tagoobot',	// Mozilla/5.0 (compatible; Tagoobot/3.0; +http://www.tagoo.ru)
			'TurnitinBot' => 'TurnitinBot', // TurnitinBot/2.1 (http://www.turnitin.com/robot/crawlerinfo.html)
			'YaDirectBot' => 'YandexDirect',	// YaDirectBot/1.0
			'VoilaBot' => 'VoilaBot',	// Mozilla/5.0 (Windows; U; Windows NT 5.1; fr; rv:1.8.1) VoilaBot BETA 1.2 (support.voilabot@orange-ftgroup.com)
			'yahoo' => 'Yahoo',
			'yandex' => 'Yandex',
			'Yanga' => 'Yanga',

			'Nutch'	=> 'Nutch',			// gh-index-bot/Nutch-1.0 (GH Web Search.; lucene.apache.org; gh_email at someplace dot com)
			'Gigabot' => 'Gigabot',		// Gigabot/3.0 (http://www.gigablast.com/spider.html)
			'Exabot' => 'Exabot',		// Mozilla/5.0 (compatible; Exabot-Images/3.0; +http://www.exabot.com/go/robot)
			'MLBot'	=> 'MLBot',			// MLBot (www.metadatalabs.com/mlbot)
			'Twiceler' => 'Twiceler',	// Mozilla/5.0 (Twiceler-0.9 http://www.cuil.com/twiceler/robot.html)
			'Yeti' => 'Yeti',			// Yeti/1.0 (NHN Corp.; http://help.naver.com/robots/)
			'YoudaoBot' => 'YoudaoBot',	// Mozilla/5.0 (compatible; YoudaoBot/1.0; http://www.youdao.com/help/webmaster/spider/; )
			'robotgenius' => 'robotgenius', // robotgenius (http://robotgenius.net)
			'LexxeBot' => 'LexxeBot',	// LexxeBot/1.0 (lexxebot@lexxe.com)
			'Snapbot' => 'Snapbot',		// Snapbot/1.0 (Snap Shots, +http://www.snap.com)
			'Mail.Ru' => 'Mail.Ru',		// Mail.Ru/1.0
			'NaverBot' => 'NaverBot',	// Mozilla/4.0 (compatible; NaverBot/1.0; http://help.naver.com/customer_webtxt_02.jsp)
			'MJ12bot' => 'Majestic12Bot',	// Mozilla/5.0 (compatible; MJ12bot/v1.2.5; http://www.majestic12.co.uk/bot.php?+)
			'SurveyBot' => 'SurveyBot',	// 64.246.165.190, Mozilla/5.0 (Windows; U; Windows NT 5.1; en; rv:1.9.0.13) Gecko/2009073022 Firefox/3.5.2 (.NET CLR 3.5.30729) SurveyBot/2.3 (DomainTools)
			'Falconsbot' => 'Falconsbot',	// 219.219.127.4, Mozilla/5.0 (compatible; Falconsbot; +http://ws.nju.edu.cn/falcons/)
			'bingbot' => 'BingBot',		// 207.46.195.234, Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)
			'Speedy Spider' => 'EntirewebBot',
			'WebAlta' => 'WebAlta',
		) as $pattern => $bot)
	{
		if(preg_match("!".$pattern."!i", $user_agent))
			return $bot;
	}

	if(preg_match("/bot|crowler|spider/i", $user_agent))
	{
		debug_hidden_log('_need_append_data', 'unknown bot detectd');
		return 'Unknown bot';
	}

	return false;
}

function bors_client_analyze()
{
	global $client;
	$client['is_bot'] = bors_bot_detect(@$_SERVER['HTTP_USER_AGENT']);
}

function bors_client_info_short($ip, $ua = '')
{
	$info = array();

	include_once('inc/clients/geoip-place.php');
	include_once('inc/browsers.php');
	if(function_exists('geoip_flag'))
		$info[] = geoip_flag($ip);

	return join('', $info).bors_browser_images($ua);
}

function im_client_detect($client_id, $type)
{
	if(preg_match('/purple/i', $client_id))
		return array('Pidgin', NULL);

	if(preg_match('/gajim/i', $client_id))
		return array('Gajim', NULL);

	if(preg_match('/qip/i', $client_id))
		return array('QIP', 'Windows');

	switch($type)
	{
		case 'jabber':
		case 'xmpp':
			return array('Jabber', NULL);
	}

	return array(NULL, NULL);
}

function im_client_image($client_name)
{
	if(!$client_name)
		return NULL;

	switch($client_name)
	{
		case 'Pidgin':
			return 'http://s.wrk.ru/i16/im/pidgin.png';
		case 'Jabber':
			return 'http://s.wrk.ru/i16/im/jabber.jpg';
		case 'Gajim':
			return 'http://s.wrk.ru/i16/im/gajim.png';
		case 'QIP':
			return 'http://s.wrk.ru/i16/im/qipinfium.png';
	}

	debug_hidden_log('append_data', "Unknown IM type $name for $client_id (of $type)");
	return NULL;
}

function os_image($os_name)
{
	switch($os_name)
	{
		case 'Linux':
			return '/bors-shared/images/os/linux.gif';
		case 'FreeBSD':
			return '/bors-shared/images/os/freebsd.png';
		case 'MacOSX':
			return '/bors-shared/images/os/macos.gif';
		case 'iPhone':
			return '/bors-shared/images/os/iphone.gif';
		case 'Symbian':
			return '/bors-shared/images/os/symbian.gif';
		case 'J2ME':
			return '/bors-shared/images/os/java.gif';
		case 'OS/2':
			return '/bors-shared/images/os/os2.gif';
		case 'PocketPC':
		case 'J2ME':
				break;
		case 'WindowsVista':
		case 'WindowsXP':
		case 'Windows2000':
		case 'Windows98':
		case 'Windows98':
		case 'Windows':
			return '/bors-shared/images/os/windows.gif';
			break;
		default:
	}

	return NULL;
}
