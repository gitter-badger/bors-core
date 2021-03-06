<?php

	// Возвращает false при ошибке показа объекта
	// true - если была какая-то отработка и требуется прекратить дальнейшую работу.
	// Иначе - строку с результатом для вывода.
	function bors_object_show($obj)
	{
		if(!$obj)
			return false;

		if(!is_object($obj))
		{
			debug_hidden_log('__error_non_object', "Non object ".$obj);
			return false;
		}

		$page = $obj->set_page($obj->arg('page'));

		@header("Status: 200 OK");
		if(config('bors.version_show'))
		{
			@header("X-Bors-object-class: {$obj->class_name()}");
			@header("X-Bors-object-id: {$obj->id()}");
		}

		if($go = $obj->attr('redirect_to'))
			return go($go);

		$processed = $obj->pre_parse();
		if($processed === true)
		{
			if(config('debug_header_trace'))
				@header('X-Bors-show-has-preparsed: Yes');
			return true;
		}

		if(!empty($_GET) && !$obj->get('skip_auto_forms'))
		{
			require_once('inc/bors/form_save.php');
			$processed = bors_form_save($obj);
			if($processed === true)
			{
				if(config('debug_header_trace'))
					@header('X-Bors-show-form-saved: Yes');
				return true;
			}
		}

		$access_object = $obj->access();
		if(!$access_object)
			bors_throw("Can't load access_engine ({$obj->access_engine()}?) for class {$obj->debug_title()}");

		if(!$access_object->can_read())
		{
			template_noindex();

			if(bors()->user())
			{
				$msg = ec("Извините, ").bors()->user()->title()
					.ec(", у Вас нет доступа к этому ресурсу ")
					."[<a href=\"/users/do-logout\">выйти</a>]";
			}
			else
				$msg = ec("Извините, гость, у Вас нет доступа к этому ресурсу");

			if($access_object->get('login_redirect') && !bors()->user())
				return go('/_bors/login?ref='.$obj->url());

			return empty($GLOBALS['cms']['error_show']) ? bors_message($msg . "
				<!--
				object to read = '{$obj->debug_title()}'
				object to read file = '{$obj->get('class_file')}'
				access engine  = '{$access_object->debug_title()}'
				access object->can_read() == false
				access engine target = '".object_property($access_object->id(), 'debug_title')."
				class_file = ".(method_exists($access_object, 'class_file') ? $access_object->class_file() : 'none')."
				object.config = ".object_property($obj->config(), 'debug_title')."

".debug_trace(0, false, 0)."
			-->", array('template' => object_property($obj, 'template'))) : true;
		}

		if(config('debug.execute_trace'))
			debug_execute_trace("{$obj->debug_title_short()}->pre_show()");

		if(!(bors()->main_object()))
			bors()->set_main_object($obj);

		$processed = $obj->pre_show();
		if($processed === true)
		{
			if(config('debug_header_trace'))
				@header('X-Bors-show-pre-show: Yes');
			return true;
		}

		$modify_time = max($obj->modify_time(), $obj->get('compile_time'));
		$last_modify_string = @gmdate('D, d M Y H:i:s', $modify_time ? $modify_time : time()).' GMT';
   	    @header ('Last-Modified: '.$last_modify_string);

		// [HTTP_IF_MODIFIED_SINCE] => Mon, 27 Jul 2009 19:03:37 GMT
		// [If-Modified-Since] => Mon, 27 Jul 2009 19:03:37 GMT
		if(!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && config('ims_enabled'))
		{
			$check_date = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
			if($check_date && $modify_time && ($check_date >= $modify_time))
			{
				@header('HTTP/1.1 304 Not Modified');
				return bors_exit();
			}
		}

		$called_url = urldecode(preg_replace('/\?.*$/', '', $obj->called_url()));
		$target_url = urldecode(preg_replace('/\?.*$/', '', $obj->url_ex($page)));
//		if(config('is_developer')) echo "{$obj->debug_title()}:<br/>.called={$obj->called_url()},<br/>target={$target_url} && called={$called_url} && {$obj->_auto_redirect()}<br/>";
		if($obj->called_url()
				&& !preg_match('!'.preg_quote($target_url).'$!', $called_url)
				&& $obj->_auto_redirect()
				&& $target_url != 'called'
		)
		{
			bors()->tmp_go_obj = $obj;
			return go($obj->url_ex($page), true);
		}

		if($processed === false)
		{
			if(empty($GLOBALS['main_uri']))
				$GLOBALS['main_uri'] = preg_replace('!:\d+/!', '/', $obj->url());
			else
				debug_hidden_log('___222', "main uri already set to '{$GLOBALS['main_uri']}' while try set to '{$obj->url()}'");

			if(config('debug.execute_trace'))
				debug_execute_trace("{$obj->debug_title_short()}->content()");

			$content = $obj->content();
		}
		else
			$content = $processed;

		if($content === false)
			return false;

		$access_object = $obj->access();
		if(!$access_object)
			debug_exit("Can't load access_engine ({$obj->access_engine()}?) for class {$obj->debug_title()}");

		if(!$access_object->can_read())
			return empty($GLOBALS['cms']['error_show']) ? bors_message(ec("Извините, у Вас нет доступа к этому ресурсу [2]\n<!-- $access_object, class_file = {$access_object->class_file()}-->")) : true;


		if($obj->cache_static())
		{
//			Так... С этим не так всё просто. Пример: темы топиков. Они кешируются и игнорируют изменения
//			Видимо, нужно вводить отдельный параметр.
//			@header('Expires: '.@gmdate('D, d M Y H:i:s', $obj->cache_static() + time()).' GMT');
//			@header('Cache-Control: max-age='.$obj->cache_static());
			@header('ETag: "'.md5($obj->internal_uri().$modify_time).'"');
		}

		set_session_var('success_message', NULL);
		set_session_var('notice_message', NULL);
		set_session_var('error_message', NULL);
		set_session_var('error_fields', NULL);

		return $content;
	}

/*
	Создание объекта, если нужно, в виде статической копии
*/

function bors_object_create($obj, $page = NULL)
{
	if(!$obj)
		return NULL;

	$page = $obj->set_page($page ?: $obj->args('page'));

	$processed = $obj->pre_parse();
	if($processed === true)
		return NULL;

	$processed = $obj->pre_show();
	if($processed === true)
		return NULL;

	if($obj->called_url() && !preg_match('!'.preg_quote($obj->url_ex($page)).'$!', $obj->called_url()))
		return NULL;

	if($processed === false)
	{
		bors()->set_main_object($obj, true);
		unset($GLOBALS['cms']['templates']);
		$GLOBALS['main_uri'] = preg_replace('!:\d+/!', '', $obj->url_ex($obj->page()));

		$obj->set_attr('recreate_on_content', true);

		return $obj->content();
	}

	return NULL;
}
