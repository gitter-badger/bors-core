<?php

/*
	Полезные ссылки:

	Индексный доступ к Multibyte-строкам на PHP или изучение ООП на практике
	http://habrahabr.ru/post/165107/

*/

class blib_array extends blib_object implements ArrayAccess, Iterator
{
	function __construct($init_value = NULL)
	{
		if(is_array($init_value))
			return $this->_value = $init_value;

		$this->_value = array();
	}

	static function factory($array = NULL) { return new blib_array($array); }

	function is_array() { return true; }
	function to_array() { return $this->_value; }
	function keys() { return array_keys($this->_value); }
	function values() { return self::factory(array_values($this->_value)); }
	function count() { return count($this->_value); }

	function append($x) { $this->_value[] = $x; return $this; }

	function set($key, $value) { $this->_value[$key] = $value; return $this; }

	function get($key, $default = NULL)
	{
		if(!array_key_exists($key, $this->_value))
			return $default;

		return $this->_value[$key];
	}

	function first()
	{
		return reset($this->_value);
	}

	function append_array(blib_array $array)
	{
		$this->_value += $array->value();
		return $this;
	}

	function append_assoc(blib_array $array)
	{
		$this->_value = array_merge($this->_value, $array->value());
		return $this;
	}

	function map($function)
	{
		$this->_value = array_map($function, $this->_value);
		return $this;
	}

	function filter($callback = NULL)
	{
		if($callback)
			$this->_value = array_filter($this->_value, $callback);
		else
			$this->_value = array_filter($this->_value);
		return $this;
	}

	function filter_keys($callback)
	{
		$this->_value = array_intersect_key($this->_value,
			array_flip(array_filter(array_keys($this->_value), $callback))
		);

		return $this;
	}

	function filter_clone($callback = "")
	{
		return self::factory(array_filter($this->_value, $callback));
	}

	function clone_class($new_class_name)
	{
//		echo "Clone to $new_class_name<br/>\n";
		return new $new_class_name($this->to_array());
	}

	function pgrep($regexp)
	{
		$this->_value = array_filter($this->_value, function($x) use ($regexp) { return preg_match($regexp, $x); } );
		return $this;
	}

	function unique()
	{
		$this->_value = array_unique($this->_value);
		return $this;
	}

	function print_d()
	{
		echo "Class ".get_class($this)."<br/>\n";
		print_dd($this->_value);
		return $this;
	}

	function each($function)
	{
		foreach($this->_value as &$x)
			$function($x);

		return $this;
	}

	function load_by_ids($class_name)
	{
		return b2::find($class_name)->in('id', $this->_value)->all();
	}

	function pop()
	{
		return array_pop($this->_value);
	}

	function shift()
	{
		return array_shift($this->_value);
	}

	function nshift($n)
	{
		$x = array_slice($this->_value, 0, $n);
		$this->_value = array_slice($this->_value, $n);

		return self::factory($x);
	}

	function nslice($n)
	{
		$result = array();
		while(($x = $this->nshift($n)) && !$x->is_empty())
			$result[] = $x;

		return self::factory($result);
	}

	function range($start, $stop = NULL, $step = 1)
	{
		if(is_null($stop))
		{
			$stop = $start - 1;
			$start = 0;
		}

		$this->_value = range($start, $stop, $step);
		return $this;
	}

	function __toString()
	{
		return blib_string::factory(print_r($this->_value, true))->__toString();
	}

	function join($delimiter)
	{
		return blib_string::factory(join($delimiter, $this->_value));
	}

	function json()
	{
		return json_encode($this->_value);
	}

	// Извлечь из каждого объекта массива свойство $property_name
	// и вернуть массив результатов
	function extract($property_name)
	{
		$result = array();
		foreach($this->_value as $x)
			$result[] = $x->get($property_name);

		$this->_value = $result;

		return $this;
	}

	/* Реализация методов интерфейса ArrayAccess */
	public function offsetExists($key) { return array_key_exists($key, $this->_value); }

	public function offsetSet($key, $value)
	{
		if(is_null($key))
			$this->_value[] = $value;
		else
			$this->_value[$key] = $value;
	}

	public function offsetUnset($key) { unset($this->_value[$key]); }

	public function offsetGet($key) { return array_key_exists($key, $this->_value) ? $this->_value[$key] : NULL; }
	/* Конец реализации методов интерфейса ArrayAccess */

	// Реализация методов итератора
	private $_i_key;
	private $_i_val;

    public function key() { return $this->_i_key; }
    public function current() { return $this->_i_val; }
    public function next() { if($x = each($this->_value)) list($this->_i_key, $this->_i_val) = $x; else $this->_i_key = NULL; }
    public function rewind() { reset($this->_value); $this->next(); }
    public function valid() { return !is_null($this->_i_key); }


	// Убрать полученные ключи из заданного массива
	function unset_keys_in(&$keys)
	{
		$keys = array_diff($keys, array_keys($this->_value));
		return $this;
	}

	// Добавить ключи в заданный массив
	function add_keys_to(&$keys)
	{
		$keys = array_merge($keys, array_keys($this->_value));
		return $this;
	}

	/*
		Загрузить свойства, взятые из эквивалентных объектов
		другого класса. Вместо MySQL INNER JOIN.
	*/
	function prop_join($class_name, $conditions = array())
	{
		$conditions['id IN'] = $this->keys();
		// Ищем все присоединяемые объекты с такими же ID, как у нас
		foreach(b2::find($class_name, $conditions)->all() as $x)
		{
			if($obj = $this->get($x->id()))
				$obj->_set_prop_joined($x);
		}
	}

	static function __unit_test($suite)
	{
		$x = blib_array::factory(array(1, 2, 3));
		$x->map(create_function('$x', 'return $x*$x;'));
		$x->each(create_function('&$x', '$x = $x*$x;'));
		$suite->assertEquals('1 16 81', $x->join(' '));
		$suite->assertEquals('[1,16,81]', $x->json());

		/* Тестирование интерфейса ArrayAccess */
		$suite->assertEquals(1, $x[0]);
		$suite->assertEquals(16, $x[1]);
		$suite->assertEquals(81, $x[2]);
		$suite->assertNull(@$x[3]);
		$suite->assertTrue($x->offsetExists(2));
		$suite->assertFalse($x->offsetExists(3));

		$s0 = 0;
		$s1 = 0;
		$pos = 0;
		foreach($x as $k => $v)
		{
			$suite->assertEquals($pos++, print_r($k, true));
//			var_dump($k, $v);
//			ob_flush();
			$s0 += $k;
			$s1 += $v;
		}

		$suite->assertEquals(3,  $s0);
		$suite->assertEquals(98, $s1);

		$s1 = 0;
		foreach($x as $v)
			$s1 += $v;

		$suite->assertEquals(98, $s1);

		$s0 = 0;
		$s1 = 0;
		foreach(blib_array::factory(array('5' => 1, '7' => 2, 3)) as $k => $v)
		{
			$s0 += $k;
			$s1 += $v;
		}

		$suite->assertEquals(20, $s0);
		$suite->assertEquals(6,  $s1);

		$x = self::factory(array(1,2,3,4,5,6,7,8,9,10));
//		var_dump($x->filter_clone(create_function('$x', 'return $x%2;'))->to_array());
//		ob_flush();
		$suite->assertEquals('1,3,5,7,9', $x->filter_clone(create_function('$x', 'return $x%2;'))->join(',')->to_string());
		$suite->assertEquals('1,2,3,4,5', $x->filter_clone(create_function('$x', 'return $x < 6;'))->join(',')->to_string());
		$suite->assertEquals('1 2 3 4 5 6 7 8 9 10', $x->join(' ')->to_string());

		$x = blib_array::factory(range(1,100));
		$suite->assertEquals(range(1,100,2), $x->filter_clone(function($x) { return $x%2; })->values()->to_array());
		$suite->assertEquals(range(2,100,2), $x->filter_keys(function($k) { return $k%2; })->values()->to_array());
	}
}
