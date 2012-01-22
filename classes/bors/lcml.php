<?php

class bors_lcml
{
	private $_params = array();
	private static $data;
	private $output_type	= 'html';
	private $input_type		= 'bb_code';

	function __construct($params = array())
	{
		$this->_params = $params;
		bors_lcml::init();
	}

	function p($key, $def = NULL) { return empty($this->_params[$key]) ? $def : $this->_params[$key]; }
	function set_p($key, $value) { $this->_params[$key] = $value; return $this; }

	private static function memcache()
	{
		static $mch = NULL;
		if($mch)
			return $mch;

		return $mch = new BorsMemCache();
	}

	static function init()
	{
		if(!empty(bors_lcml::$data))
			return;

		bors_lcml::$data['pre_functions'] = array();
		bors_lcml::actions_load('pre', bors_lcml::$data['pre_functions']);

		bors_lcml::$data['post_functions'] = array();
		bors_lcml::actions_load('post', bors_lcml::$data['post_functions']);

		bors_lcml::$data['post_whole_functions'] = array();
		bors_lcml::actions_load('post-whole', bors_lcml::$data['post_whole_functions']);

		bors_lcml::actions_load('tags');

		if(config('lcml_sharp_markup'))
			bors_lcml::actions_load('sharp');
	}

	private static function actions_load($rel_dir, &$functions = array())
	{
		foreach(bors_dirs() as $base_dir)
			bors_lcml::_actions_load(secure_path($base_dir.'/engines/lcml/'.$rel_dir), $functions);
	}

	private static function _actions_load($dir, &$functions = array())
	{
        if(!is_dir($dir))
			return;

		$files = self::memcache()->get('lcml_actions_'.@$_SERVER['HTTP_HOST'].'_3:'.$dir);
		if(!$files)
		{
	        $files = array();

    	    if($dh = opendir($dir)) 
        	    while(($file = readdir($dh)) !== false)
            	    if(is_file($dir.'/'.$file))
                	    $files[] = $file;

	        closedir($dh);

			sort($files);
			self::memcache()->set($files);
		}

        foreach($files as $file) 
        {
            if(preg_match("!(.+)\.php$!", $file, $m))
            {
                include_once("$dir/$file");

                $fn = "lcml_".($ffn=substr($file, 3, -4));
				$functions[] = $fn;
            }
        }
	}

	private function functions_do($functions, $text)
	{
		$fns_list_enabled  = config('lcml_functions_enabled',  array());
		$fns_list_disabled = config('lcml_functions_disabled', array());

		foreach($functions as $fn)
		{
			$original = $text;

//			if(config('is_developer'))echo "$fn('$text')<br/><br/>";
			if((!$fns_list_enabled || in_array($fn, $fns_list_enabled))
				&& !in_array($fn, $fns_list_disabled)
			)
				$text = $fn($text, $this);

			if(!trim($text) && trim($original))
				debug_hidden_log('lcml-error', "Drop on $fn convert '$original'");
		}

		return $text;
	}


	private $params;
	function set_params($params) { $this->params = $params; }

	function parse($text, $params = array())
	{
		$params = array_merge($this->params, $params);

		$text = str_replace("\r", '', $text);

		if(!trim($text))
			return '';

		$need_prepare = popval($this->_params, 'prepare');

		if($this->_params['level'] == 1
			&& !config('lcml_cache_disable')
			&& config('cache_engine')
			&& empty($params['nocache'])
		)
		{
			$cache = new Cache();
			if($cache->get('lcml-cache-v10', $text) && 0)
				return $cache->last();
		}
		else
			$cache = NULL;

		$GLOBALS['lcml']['params'] = $this->_params;
		$GLOBALS['lcml']['params']['html_disable'] = $this->p('html_disable');
		$GLOBALS['lcml']['cr_type'] = $this->p('cr_type');

		if($this->_params['level'] == 1 || $need_prepare)
			$text = $this->functions_do(bors_lcml::$data['pre_functions'], $text);

		if($this->_params['level'] == 1)
			$this->output_type = popval($params, 'output_type', 'html');

		$mask = str_repeat('.', bors_strlen($text));

		// ******* Собственно, главная часть — обработка тэгов *******
		$text = lcml_tags($text, $mask, $this);

		if($this->p('only_tags'))
			return $cache ? $cache->set($text, 86400) : $text;

		if(config('lcml_sharp_markup'))
		{
			require_once('engines/lcml/sharp.php');
			$text = lcml_sharp($text, $mask);
		}

		$result = "";
		$start = 0;
		$can_modif = true;

		for($i=0, $stop=bors_strlen($text); $i<$stop; $i++)
		{
			if($mask[$i] == 'X')
			{
				if($can_modif)
				{
					if($start != $i)
						$result .= bors_lcml::functions_do(bors_lcml::$data['post_functions'], bors_substr($text, $start, $i-$start));

					$start = $i;
					$can_modif = false;
				}
			}
			else
			{
				if(!$can_modif)
				{
					$result .= bors_substr($text, $start, $i-$start);
					$start = $i;
					$can_modif = true;
				}
			}
		}

		if($start < bors_strlen($text))
		{
//			if(config('is_developer')) var_dump($can_modif, $this->_params['level'], $start, bors_strlen($text), $text);
			// Внимание! Уровень 1 тут добавлять нельзя. Проблема:
			// [quote]...lcml-код... [/quote]
			// Внутри quote level > 1, но после обработки для всего блока quote can_modif в маске == false
			// Нужно искать некорректный вызов post-функций в других местах. При правильном проектировании
			// этот вызов может быть только один раз, потом — блокируется.

			if($can_modif/* && $this->_params['level'] == 1*/)
				$result .= $this->functions_do(bors_lcml::$data['post_functions'], bors_substr($text, $start, bors_strlen($text) - $start));
			else
				$result .= bors_substr($text, $start, bors_strlen($text) - $start);
		}

		$text = $result;

		if($this->_params['level'] == 1)
			$text = $this->functions_do(bors_lcml::$data['post_whole_functions'], $text);

		return $cache ? $cache->set($text, 86400) : $text;
	}

	function output_type() { return $this->output_type; }

	static function __unit_test($suite)
	{
		// Одиночные теги тестируются в соответствующих классах. Так что нам тут их проверять не надо
		// нужно проверять сочетания.
		$code = '[b][i]italic-bold[/i][/b]';
		$suite->assertEquals('<strong><i>italic-bold</i></strong>', lcml($code));

		$code = '[http://balancer.ru Сайт расходящихся тропок]';
		$suite->assertRegexp('#<a.+href="http://balancer.ru".+>Сайт расходящихся тропок</a>#', lcml($code));
//		Упс. Не работает. Сделать не прямой парсинг, а подмену тэга вначале, в зависимости от типа ссылки, [url или [img
//		$code = '[http://balancer.ru|[b]Сайт расходящихся тропок[/b]]';
//		$suite->assertRegexp('#<a.+href="http://balancer.ru".+>Сайт расходящихся тропок</a>#', lcml($code));

		$code = '[url http://balancer.ru|[b]Сайт расходящихся тропок[/b]]';
		$suite->assertRegexp('#<a.+href="http://balancer.ru".+><strong>Сайт расходящихся тропок</strong></a>#', lcml($code));

		$code = '[url=http://balancer.ru]Сайт расходящихся тропок[/url]';
		$suite->assertRegexp('#<a.+href="http://balancer.ru".+>Сайт расходящихся тропок</a>#', lcml($code));

		$code = '[b]Сайт расходящихся тропок: [url="http://balancer.ru"][/b]';
		$suite->assertRegexp('#<strong>Сайт расходящихся тропок: <a.+href="http://balancer.ru".+>balancer.ru</a></strong>#', lcml($code));

		// Внутренние ошибочные теги не парсятся
		$code = '[b][i]italic[/b]bold[/i]';
		$suite->assertEquals('<strong>[i]italic</strong>bold[/i]', lcml($code));

		// Переводы строк.
		$code = "Раз, два, три, четыре, пять\nВышел зайчик погулять";
		$suite->assertEquals("Раз, два, три, четыре, пять<br />\nВышел зайчик погулять", lcml($code)); //?WTF? Это же не BB.

		// Проверки, использующие специфичные локальне ресурсы balancer.ru
		if(config('is_balancer_ru_tests'))
		{
			$code = '[url=http://balancer.ru/forum/punbb/viewtopic.php?pid=1248520#p1248520][img]http://balancer.ru/cache/img/forums/0708/468x468/1024x768-img_0599.jpg[/img][/url]';
			$suite->assertEquals("===", lcml($code));
		}
	}
}