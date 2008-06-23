<?php

class BorsXml
{
	private $parser;
	private $pointer;
	var $dom = array();
	
	function __construct()
	{
		$this->pointer = &$this->dom;
		$this->parser = xml_parser_create();
		xml_set_object($this->parser, $this);
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_element_handler($this->parser, "tag_open", "tag_close");
		xml_set_character_data_handler($this->parser, "cdata");
	}

	function parse($data)
	{
		xml_parse($this->parser, $data);
	}

	function tag_open($parser, $tag, $attributes)
	{
		$attributes['_tag'] = $tag;
		$attributes['_parent'] = &$this->pointer;

		$this->pointer[$tag][] = &$attributes;

		$this->pointer = &$attributes;
	}

	function cdata($parser, $cdata)
	{
		if(empty($this->pointer['cdata']))
			$this->pointer['cdata'] = html_entity_decode(&$cdata);
		else
			$this->pointer['cdata'] .= html_entity_decode(&$cdata);
	}

	function tag_close($parser, $tag)
	{
		$parent = &$this->pointer['_parent'];
		unset($this->pointer['_parent']);
		unset($this->pointer['_tag']);

		$this->pointer = &$parent;
	}
}