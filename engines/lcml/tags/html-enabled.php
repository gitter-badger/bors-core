<?php
    foreach(explode(" ","b big i s strong sub sup small u xmp") as $tag)
		eval("function lp_$tag(\$txt){return '<$tag>'.lcml(\$txt).'</$tag>';}");

    foreach(explode(" ","br hr") as $tag)
		eval("function lt_$tag(){return '<$tag />';}");

	function lp_html_iframe($inner, $params)
	{
		if(@$params['width'] < 200)
			$params['width'] = 200;
		if(@$params['height'] < 200)
			$params['height'] = 200;

		$params['src'] = html_entity_decode(@$params['src']);
		
		return "<iframe ".make_enabled_params($params, 'width height frameborder scrolling marginheight marginwidth src').">$inner</iframe>";
	}

/*
function lp_style($inner, $params)
{
	return "<style ".make_enabled_params($params, 'type').">$inner</style>";
}
*/
//TODO: сделать проверку на наличие активного кода в стилях.
function lp_style($inner, $params)
{
		return "<style type=\"text/css\">{$inner}</style>";
}

function lp_table($inner, $params)
{
	if(empty($params['class']) && !empty($params['border']))
	{
		unset($params['border']);
		$params['class'] = 'btab';
	}
	return "<table ".make_enabled_params($params, 'cellpadding cellspacing class style border').">".lcml($inner)."</table>";
}
