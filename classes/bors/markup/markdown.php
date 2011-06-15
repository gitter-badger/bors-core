<?php

class bors_markup_markdown extends base_object
{
	static function factory($text = NULL)
	{
		$md = new bors_markup_markdown(NULL);
		if($text)
			$md->set_source($text, false);

		return $md;
	}

	function set_source($source, $update)
	{
		parent::set_source($source, $update);
//		if(!$this->title_true())
		{
			list($title, $text) = self::title_text_extract($source);
			if($title)
				$this->set_title($title, false);

			$this->set_text($text, false);
		}

		return $source;
	}

	static function title_text_extract($text)
	{
		if(preg_match('/^(.+?)\n={3,}\n(.*)$/s', $text, $m))
			return array(trim($m[1]), trim($m[2]));

		return array(NULL, $text);
	}

	function text()
	{
		$text = parent::text();
		$text = preg_replace('/<(http:.+?)>/', '$1', $text);
		$text = preg_replace('/<([^>]+@[^>]+)>/', '$1', $text);
		return $text;
	}

	function title()
	{
		list($title, $text) = self::title_text_extract($this->source());

		return $title;
	}

	function html()
	{
		list($title, $text) = self::title_text_extract($this->source());

		return self::parse($text);
	}


	static function parse($text)
	{
		require_once(config('markdown_include'));
		return Markdown($text);
	}
}
