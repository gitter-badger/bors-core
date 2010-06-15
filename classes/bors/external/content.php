<?php

class bors_external_content extends base_object_db
{
	function main_table() { return 'external_content'; }
	function main_table_fields()
	{
		return array(
			'id',
			'create_time',
			'www' => array('field' => 'url', 'title' => ec('Ссылка на источник')),
			'content_raw' => array('field' => 'content', 'title' => ec('Полное содержание источника'), 'type' => 'text'),
			'title' => array('title' => ec('Извлечённый заголовок')),
		);
	}

	static function load($url)
	{
		$x = objects_first('bors_external_content', array('www' => $url));
		if($x)
		{
//			print_d($x->content_raw());
			return $x->content_raw();
		}

//		require_once('inc/http.php');
		$content = http_get_content($url);

		$x = object_new_instance('bors_external_content', array(
			'www' => $url,
			'content_raw' => $content,
		));

		$x->store();
		return $content;
	}
}