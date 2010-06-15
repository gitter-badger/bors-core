<?php

function geoip_place($ip)
{
	list($cc, $cn, $city) = geoip_info($ip);

	if($cc)
	{
		$res = "$cn";
		if($city)
			$res .= ", $city";
	}
	else
		$res = "";

	return $res;
}

function geoip_flag($ip)
{
	list($cc, $cn, $city) = geoip_info($ip);

	if(!$cc)
		return '';

	$alt = "$cn";
	if($city)
		$alt .= ", $city";

	$file = bors_lower($cc).".gif";
//	if(!file_exists("/var/www/balancer.ru/htdocs/img/flags/$file"))
//		$file = "-.gif";

	$res = '<img src="http://balancer.ru/img/flags/'.$file.'" class="flag" title="'.addslashes($alt).'" alt="'.$cc.'"/>';
	return $res;
}

/**
	Возвращает массив информации о клиенте по его IP:
	@return array($country_code, $country_name, $city_name)
*/

function geoip_info($ip)
{
	if(!$ip)
		return array('','','');

	require_once(BORS_3RD_PARTY."/geoip/geoip.inc");
	require_once(BORS_3RD_PARTY."/geoip/geoipcity.inc");

	$ch = new Cache();
	if($ch->get("users-geoip-info", $ip))
		0;//return $ch->last();

	$cc = '';
	if(file_exists(($gf = BORS_3RD_PARTY."/geoip/GeoIPCity.dat")))
	{
		$gi = geoip_open($gf, GEOIP_STANDARD);

		$record = geoip_record_by_addr($gi, $ip);
		$cc = $record->country_code;
		$cn = $record->country_name;
		$cin = $record->city;
		geoip_close($gi);
	}

	if(!$cc && file_exists(($gf = BORS_3RD_PARTY."/geoip/GeoLiteCity.dat")))
	{
		$gi = geoip_open($gf, GEOIP_STANDARD);

		$record = geoip_record_by_addr($gi, $ip);
		$cc = $record->country_code;
		$cn = $record->country_name;
		$cin = $record->city;
		geoip_close($gi);
	}

	if(!$cc && file_exists(($gf = "/usr/share/GeoIP/GeoIP.dat")))
	{
		$gi = geoip_open($gf, GEOIP_STANDARD);
		$cc = geoip_country_code_by_addr($gi, $ip);
		$cn = geoip_country_name_by_addr($gi, $ip);
		$cin = "";
		geoip_close($gi);
	}

	if(!$cc && file_exists(($gf = BORS_3RD_PARTY."/geoip/GeoIP.dat")))
	{
		$gi = geoip_open($gf, GEOIP_STANDARD);
		$cc = geoip_country_code_by_addr($gi, $ip);
		$cn = geoip_country_name_by_addr($gi, $ip);
		$cin = "";
		geoip_close($gi);
	}

	$cin = iconv('ISO-8859-1', 'utf-8', $cin);

	return $ch->set(array($cc, $cn, $cin), -3600);
}