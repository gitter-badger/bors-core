<?php

function lcml_external_code($text)
{
	// YouTube код и ссылки
	if(!config('lcml_external_parse_youtube_disable'))
	{
		$text = preg_replace('!/watch\?gl=RU&v=!', '/watch?v=', $text);
		$text = preg_replace('!<object[^>]+><param[^>]+value="http://www\.youtube\.com/v/([^&?]+).*?</object>!s', "\n[youtube]$1[/youtube]\n", $text);
		$text = preg_replace('!(^|\s)https?://www\.youtube\.com/watch\?v=([\w\-]+)&\S+(\s|$)!im', "\n[youtube]$2[/youtube]\n", $text);
		$text = preg_replace('!(^|\s)https?://www\.youtube\.com/watch\?v=([\w\-]+)(\s|$)!m', "\n[youtube]$2[/youtube]\n", $text);
		// http://www.youtube.com/watch?v=TXxcR3qgyYQ&playnext=1&list=PL21AA194D7FBBA2D9
		// https://www.youtube.com/watch?v=21El16OPZoc
		$text = preg_replace('!(^|\s)https?://www.youtube.com/watch?v=([^&]+)&playnext=\d+&list=\w+(\s|$)!m', "\n[youtube]$2[/youtube]\n", $text);
		$text = preg_replace('!(^|\s)http://youtu\.be/([^&/]+?)(\s|$)!m', "\n[youtube]$2[/youtube]\n", $text);
	}

	$text = preg_replace('!(^|\s)http://rutube\.ru/tracks/\d+\.html\?v=(\w+)(\s|$)!m', "\n[rutube]$2[/rutube]\n", $text);
	$text = preg_replace('!(^|\s)(http://rutube\.ru/tracks/(\d+)\.html\?kot=\d)(\s|$)!m', "\n[rutube original_url=\"$2\"]$3[/rutube]\n", $text);

	$text = preg_replace('!(^|\s)http://prostopleer.com/tracks/(\w+)(\s|$)!m', "\n[prostopleer]$2[/prostopleer]\n", $text);

	// PicasaWeb
	// http://picasaweb.google.com/lh/photo/Ds6wIz_ClELVCBg84Q7-6Q?feat=directlink
	$text = preg_replace('!(^|\s)https?://picasaweb.google.(com|ru)/lh/photo/([\w\-]+)\?feat=directlink($|\s)!m', "\n[picasa]$3[/picasa]\n", $text);
	$text = preg_replace('!(^|\s)https?://picasaweb.google.(com|ru)/lh/photo/([\w\-]+)(\s+|$)!m', "\n[picasa]$3[/picasa]\n", $text);

	// pics.livejournal.com
	$text = preg_replace("!((^|\s|\n)http://pics\.livejournal\.com/(\w+)/pic/(\w+)(\s|\n|$))!m", "\n[img $1]\n", $text);

	// http://r-img.fotki.yandex.ru/get/5300/alex-hedin.86/0_575e1_d75048a8_orig
	// http://img-fotki.yandex.ru/get/4400/alex-hedin.86/0_575dc_805f7c4e_orig
	// http://img-fotki.yandex.ru/get/5004/balancer73.f/0_4cc96_94922bd7_XL
	$text = preg_replace("!((^|\s|\n)http://[^/]+fotki\.yandex\.ru/get/\d+/[^/]+/\w+_(orig|XL)(\s|\n|$))!m", "\n[img $1]\n", $text);

	$text = preg_replace('!(<script type="text/javascript" src="http://googlepage.googlepages.com/player.js"></script>)!ise', 'save_format("\1")', $text);

	return $text;
}
