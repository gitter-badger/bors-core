<?php

class base_xml_array extends base_page
{
	function render_engine() { return 'base_xml_array'; }
	function output_charset() { return 'utf-8'; }

	function render($obj)
	{
		header("Content-Type: application/xml; charset=utf-8");
#		require_once('inc/xml/array2xml.php');
#		return array2xml($obj->local_data(), 'data', NULL, $obj->internal_charset());
		require_once("class.array2xml2array.php");

		$array2XML = new CArray2xml2array();

		$array2XML->setArray(array('data' => $obj->local_data()));
		return $array2XML->array2xml('data');
	}

	//TODO: Реализовать статическое кеширование файлов, отличных от index.html / text/html
	function cache_static() { return 0; }
	function index_file() { return 'index.xml'; }
	function use_temporary_static_file() { return false; }
}