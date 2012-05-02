<?php

function http_get($url)
{
	$ch = curl_init($url);
	curl_setopt_array($ch, array(
		CURLOPT_TIMEOUT => 15,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 5,
		CURLOPT_ENCODING => 'gzip, deflate',
//		CURLOPT_RANGE => '0-4095',
//		CURLOPT_REFERER => $original_url,
		CURLOPT_AUTOREFERER => true,
//		CURLOPT_HTTPHEADER => $header,
		CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; FunWebProducts; .NET CLR 1.1.4322; .NET CLR 2.0.50727)',
		CURLOPT_RETURNTRANSFER => true,
	));

//	if(preg_match("!lenta\.ru!", $url))
//		curl_setopt($ch, CURLOPT_PROXY, 'home.balancer.ru:3128');

	$data = curl_exec($ch);

//	print_r($data);

	curl_close($ch);

	return $data;
}

function http_get_content($url, $raw = false, $max_length = false)
{

	$original_url = $url;
	$anchor = "";

	if(preg_match("!^(.+?)#(.+)$!", $url, $m))
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

	if(preg_match("/\.(pdf|zip|rar|djvu|mp3|avi|mkv|mov|mvi|qt)$/i", $pure_url))
		return "";

	$header = array();
	if(($cs = config('lcml_request_charset_default')))
		$header[] = "Accept-Charset: utf-8, ".$cs;
	else
		$header[] = "Accept-Charset: utf-8";
	$header[] = "Accept-Language: ru, en";

	bors_function_include('debug/timing_start');
	bors_function_include('debug/timing_stop');
	debug_timing_start('http-get: '.$url);
	debug_timing_start('http-get-total');
	$ch = curl_init($url);

	curl_setopt_array($ch, array(
		CURLOPT_TIMEOUT => 5,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 5,
		CURLOPT_ENCODING => 'gzip,deflate',
		CURLOPT_REFERER => $original_url,
		CURLOPT_AUTOREFERER => true,
		CURLOPT_HTTPHEADER => $header,
		CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/534.13 (KHTML, like Gecko) Chrome/9.0.597.94 Safari/534.13',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_HEADER => true,
	));


//TODO: сделать перебор разных UA при ошибке
//		CURLOPT_USERAGENT => 'Googlebot/2.1 (+http://www.google.com/bot.html)',
//		CURLOPT_RANGE => '0-4095',

	if(config('proxy.force_regexp') && preg_match(config('proxy.force_regexp'), $url))
		curl_setopt($ch, CURLOPT_PROXY, 'balancer.endofinternet.net:3128');

	$data = curl_exec($ch);

//	if(1||config('is_developer')) { var_dump($data); exit(); }
//	if(config('is_developer')) { exit('stop: "'.$url.'"'); }

	if($data === false)
	{
		echo '<small><i>[1] Curl error: ' . curl_error($ch) . '</i></small><br/>';
//		echo debug_trace();
//		if(config('is_developer')) { var_dump($data); exit(); }
		return '';
	}

	$adat = explode("\n", $data);

//	if(config('is_developer')) { var_dump(file_get_contents($url)); }

	$pos = 0;
	$header = '';
	for($i=0; $i<count($adat); $i++)
	{
		$pos += strlen($adat[$i])+1;
		if(!trim($adat[$i]))
		{
			$header = join("\n", array_slice($adat, 0, $i-1));
			$data   = join("\n", array_slice($adat, $i+1));
			break;
		}
	}

	$data = trim($data);
//	$data = trim(curl_redir_exec($ch));

	$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

//	if(config('is_developer')) { var_dump($header); var_dump($data); }

    if(preg_match("!charset\s*=\s*(\S+)!i", $content_type, $m))
        $charset = $m[1];
    elseif(preg_match("!<\?xml\s+version=\S+\s+encoding\s*=\s*\"(.+?)\"!i", $data, $m))
        $charset = $m[1];
    elseif(preg_match("!(Microsoft\-IIS|X\-Powered\-By: ASP\.NET)!", $header))
        $charset = 'windows-1251';
	else
        $charset = '';

	curl_close($ch);

	debug_timing_stop('http-get-total');
	debug_timing_stop('http-get: '.$url);
	if($raw)
		return $data;

	if(empty($charset))
	{
        if(preg_match("!<meta\s+http\-equiv\s*=\s*(\"|')Content\-Type(\"|')[^>]+charset\s*=\s*(.+?)(\"|')!i", $data, $m))
	        $charset = $m[3];
		elseif(preg_match("!<meta[^>]+charset\s*=\s*(.+?)(\"|')!i", $data, $m))
	        $charset = $m[1];
	}

    if(!$charset)
		$charset = config('lcml_request_charset_default', 'WINDOWS-1251');
/*
	if(config('is_developer'))
	{
		echo "url = '$url'";
		echo "Content-type = '$content_type'<br/>";
		echo "charset = '$charset'<br/>";
		echo print_d(substr($data, 0, 1000));
		print_d(iconv($charset, config('internal_charset').'//IGNORE', $data));
	}
*/
//	var_dump($charset, $data);

	if($charset)
		$data = iconv($charset, config('internal_charset').'//IGNORE', $data);

    return $data;
}

function http_get_ex($url, $raw = true)
{
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

	if(preg_match("/\.(pdf|zip|rar|djvu|mp3|avi|mkv|mov|mvi|qt)$/i", $pure_url))
		return "";

	$header = array();
	if(($cs = config('lcml_request_charset_default')))
		$header[] = "Accept-Charset: ".$cs;
	$header[] = "Accept-Language: ru, en";

	$timeout = 15;
	if(preg_match('/(livejournal.com|imageshack.us|upload.wikimedia.org|www.defencetalk.com|radikal.ru)/', $url))
		$timeout = 40;

	if(preg_match('/\.gif$/i', $url)) // Возможно — большая анимация
		$timeout = 60;

	$ch = curl_init($url);
	curl_setopt_array($ch, array(
		CURLOPT_TIMEOUT => $timeout,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 5,
		CURLOPT_ENCODING => 'gzip,deflate',
//		CURLOPT_RANGE => '0-4095',
		CURLOPT_REFERER => $original_url,
		CURLOPT_AUTOREFERER => true,
		CURLOPT_HTTPHEADER => $header,
//TODO: сделать перебор разных UA при ошибке
//		CURLOPT_USERAGENT => 'Googlebot/2.1 (+http://www.google.com/bot.html)',
		CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/534.13 (KHTML, like Gecko) Chrome/9.0.597.94 Safari/534.13',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => false,
	));

	if(config('proxy.force_regexp') && preg_match(config('proxy.force_regexp'), $url))
		curl_setopt($ch, CURLOPT_PROXY, 'balancer.endofinternet.net:3128');

	$data = curl_exec($ch);

	if($data === false)
	{
		//TODO: оформить хорошо. Например, отправить отложенную задачу по пересчёту
		//И выше есть такой же блок.
		$err_str = curl_error($ch);
//		if(config('is_developer')) { var_dump($url, $pure_url, $raw, $data, $err_str); exit(); }
		echo '[2] Curl error: ' . $err_str;
		return '';
	}

	if(!$raw)
		$data = trim($data);

	$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
//	echo "<xmp>"; print_r($data); echo "</xmp>";

    if(!$raw && preg_match("!charset=(\S+)!i", $content_type, $m))
        $charset = $m[1];
    else
        $charset = '';

	curl_close($ch);

	if(!$raw)
	{
		if(empty($charset))
		{
        	if(preg_match("!<meta http\-equiv=\"Content\-Type\"[^>]+charset=(.+?)\"!i", $data, $m))
	        	$charset = $m[1];
			elseif(preg_match("!<meta[^>]+charset=(.+?)\"!i", $data, $m))
		        $charset = $m[1];
		}

    	if(!$charset)
			$charset = config('lcml_request_charset_default');

		if($charset)
			$data = iconv($charset, config('internal_charset').'//IGNORE', $data);
	}

    return array('content' => $data, 'content_type' => $content_type);
}

function query_explode($query_string)
{
	$data = array();
	foreach(explode('&', $query_string) as $pair)
	{
		if(preg_match('/^(.+)=(.+)$/', $pair, $m))
		{
			if(preg_match('/^(\w+)\[\]$/', $m[1], $mm))
				$data[urldecode($mm[1])][] = urldecode($m[2]);
			else
				$data[urldecode($m[1])] = urldecode($m[2]);
		}
		else
			$data[urldecode($pair)] = NULL;
	}

	return $data;
}

if(!function_exists('curl_setopt_array'))
{
   function curl_setopt_array(&$ch, $curl_options)
   {
       foreach($curl_options as $option => $value)
           if(!curl_setopt($ch, $option, $value))
               return false;

       return true;
   }
}
