<?php

// URLs processing
// Global vars: none
//
// (c) Balancer 2003-2010
// 07.06.04 0.1.2 исправлена обработка ссылок, "упирающихся" в тег, например, <li> http://www.ru/<li>
// 08.06.04 0.1.3 если сервер теперь не возвращает кодировку, считается, что она - Windows-1251
// 28.06.04 0.1.4 исправления выделения ссылок, заканчивающихся [, |, ] и т.п.
// 01.08.04 0.1.5 * выкидываются теги, если они включены в заголовок
// 10.08.04 0.1.6 * ограничен размер закачиваемой части (если это поддерживается сервером) первыми четырьмя килобайтами
// 09.09.04 0.1.7 + внешние ссылки - класс external. Внутренние - по имени
// 11.01.05 0.1.8 * обработка редиректов перенесена на модуль HTTP_Request. Введены таймауты.
// 12.01.05 0.1.9 + Введена кодировка по умолчанию и её запрос у сервера.
// 17.01.07 0.1.10 * Исправление нового формата HTTP_Request

    // Константы

    // кодировка, запрашиваемая по умолчанию
    $GLOBALS['lcml_request_charset_default'] = 'windows-1251';

    function lcml_strip_url($url)
    {
		return url_truncate($url, 70);
    }

    function lcml_urls_title($url, $snip = false, $line = NULL)
    {
        if(class_exists('Cache') && !config('lcml_cache_disable_full'))
        {
            $cache = new Cache();
            if($cache->get('url_titles-v4-'.$snip, $url))
                return $cache->last();
            else
                return $cache->set(lcml_urls_title_nocache($url, $snip, $line), 7*86400);
        }
        else
            return lcml_urls_title_nocache($url, $snip, $line);
    }

    function lcml_urls_title_nocache($url, $snip=false, $line = NULL)
    {
		static $parsed = array();
		if(!empty($parsed[$url][$snip][$line]))
			return $url;

		static $in_box_entered = false;

		$parsed[$url][$snip][$line] = true;

		$original_url = $url;
		$anchor = "";

		if(preg_match("!^(.+)#(.+?)$!", $url, $m))
		{
			$url = $m[1];
			$anchor = $m[2];
		}

		$pure_url = $url;
		$query = "";

		if(preg_match("!^(.+?)\?(.+)$!", $url, $m))
		{
			$pure_url = $m[1];
			$query = $m[2];
		}

		$url_data = url_parse($pure_url);
		$external = @$url_data['local'] ? '' : ' class="external"';

		if(bors_exec_time() < config('lcml.timeout', 30))
		{
			if(config('lcml_balancer'))
			{
				//TODO: придумать хук вместо хардкода
				if(in_array($url_data['host'], array(
					'airbase.ru', 'balabot.balancer.ru', 'balancer.ru', 'www.balancer.ru', 'la2.balancer.ru',
					'bors.balancer.ru', 'forums.airbase.ru', 'forums.balancer.ru',
					'wrk.ru', 'www.wrk.ru', 'la2.wrk.ru')))
				{
					$anchor = NULL;
					$obj = bors_load_uri($pure_url);

					if(!$obj)
						$obj = bors_load_uri($url);

					if($obj)
//						return "<a href=\"$original_url\">{$obj->title()}</a>";
						return $obj->titled_link_in_container();
				}
			}

			if(!$in_box_entered && $snip && class_exists('airbase_external_link'))
			{
				$link = airbase_external_link::find_or_register($original_url);
				$in_box_entered = true;
				$html = lcml($link->bbshort());
				$in_box_entered = false;
				return $html;
			}
		}

		$blacklist = $external;
		if(preg_match('!'.config('seo_domains_whitelist_regexp', @$_SERVER['HTTP_HOST']).'!', $url_data['host']))
			$blacklist = false;

		if(preg_match("!/[^/]+\.[^/]+$!", $pure_url) 
				&& !preg_match("!\.(html|htm|phtml|shtml|jsp|pl|php|php4|php5|cgi)$!i", $pure_url)
				&& !preg_match("!^http://[^/]+/?$!i", $pure_url)
		)
			return "<a ".($blacklist ? 'rel="nofollow" ' : '')."href=\"{$original_url}\"$external>".lcml_strip_url($original_url)."</a>";


		if(!function_exists('curl_init') || bors_exec_time() > config('lcml.timeout', 30))
			return "<a ".($blacklist ? 'rel="nofollow" ' : '')."href=\"{$original_url}\"$external>".lcml_strip_url($original_url)."</a>";

		$data = bors_lib_http::get($url);

        if(preg_match("!<title[^>]*>(.+?)</title>!is", $data, $m))
        {
        	$title = $m[1];
			$title = bors_substr(trim(preg_replace("!\s+!"," ", str_replace("\n"," ", strip_tags($title)))), 0, 256);
			if(!$title)
				$title = $url;

            return "<a ".($blacklist ? 'rel="nofollow" ' : '')."href=\"{$original_url}\"$external>{$title}</a>";
        }

        return "<a ".($blacklist ? 'rel="nofollow" ' : '')."href=\"{$original_url}\"$external>".lcml_strip_url($original_url)."</a>";
    }

    function lcml_urls($txt)
    {
		if(config('lcml_post_urls_disable'))
			return $txt;

		// Если у нас есть список явно разрешённых тегов, то по умолчанию
		// всё остальное запрещено. Проверяем, разрешены ли такие автоссылки явно
		$taglist = config('lcml_tags_enabled');
		if($taglist && empty($taglist['post_urls']))
			return $txt;

        $txt = preg_replace_callback("!(?<=^|\n)\s*(https?://\S+)\s*(?=\n|$)!si", function($m) { return lcml_urls_title($m[1], true, 131);}, $txt);

        $txt = preg_replace_callback("!\[(http://[^\s\|\]]+?)\]!i", function($m) { return lcml_urls_title($m[1]);}, $txt);
        $txt = preg_replace_callback("!\[(www\.[^\s\|\]]+?)\]!i", function($m) { return lcml_urls_title('http://'.$m[1]);}, $txt);

        $txt = preg_replace("!\[(ftp://[^\s\|\]]+?)\]!i","<a href=\"$1\" class=\"external\">$1</a>",$txt);
        $txt = preg_replace("!\[(ftp\.[^\s\|\]]+?)\]!i","<a href=\"ftp://$1\" class=\"external\">$1</a>",$txt);
		$txt = preg_replace_callback('!(?<=\s|^)(http://\S+(\S*\(\S*\))+)(?=\s|$)!sm', function($m) { return lcml_urls_title($m[1]);}, $txt);
        $txt = preg_replace_callback("!(?<=\s|^|\()(https?://[^\s<>\|\[\]\<\>]+)(\)|\.|,|\!|\-|:)(?=\s|$)!i", function($m) { return lcml_urls_title($m[1]).$m[2];}, $txt);
        $txt = preg_replace_callback("!(?<=\s|^|\()(https?://[^\s<>\|\[\]\<\>]+)(?=\s|$)!i", function($m) { return lcml_urls_title($m[1], false, 139);}, $txt);
        $txt = preg_replace_callback("!(?<=\s|^| \()(www\.[^\s<>\|\[\]\<\>]+)(\)|\.|,|\!|\-|:)(?=\s|$)!i", function($m) { return lcml_urls_title('http://'.$m[1]).$m[2];}, $txt);
        $txt = preg_replace_callback("!(?<=\s|^| \()(www\.[^\s<>\|\[\]\<\>]+)(?=\s|$)!i", function($m) { return lcml_urls_title('http://' . $m[1]);}, $txt);

        $txt = preg_replace("!(?<=\s|^|\()(ftp://[^\s<>\|\[\]\<\>]+)(\)|\.|,|\!|\-)(?=\s|$)!i","<a href=\"$1\" class=\"external\">$1</a>$2",$txt);
        $txt = preg_replace("!(?<=\s|^|\()(ftp://[^\s<>\|\[\]\<\>]+)(?=\s|$)!i","<a href=\"$1\" class=\"external\">$1</a>",$txt);
        $txt = preg_replace("!(?<=\s|^|\()(ftp\.[^\s<>\|\[\]\<\>]+)(\)|\.|,|\!|\-)(?=\s|$)!i","<a href=\"ftp://$1\" class=\"external\">$1</a>$2",$txt);
        $txt = preg_replace("!(?<=\s|^|\()(ftp\.[^\s<>\|\[\]\<\>]+)(?=\s|$)!i","<a href=\"ftp://$1\" class=\"external\">$1</a>",$txt);

        return $txt;
    }

//    echo lcml_urls("http://lenta.ru/economy/2005/01/11/ibm/");
