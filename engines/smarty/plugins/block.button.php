<?php

function smarty_block_button($params, $content, &$smarty)
{
    if($content)
	{
		extract($params);
		if(empty($url))
			echo "<button type=\"button\" class=\"btn btn-default btn-primary\">{$content}</button>\n";
		else
			echo "<a href=\"{$url}\" class=\"btn btn-default btn-primary\">{$content}</a>\n";
    }
}
