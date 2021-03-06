<?php

class bors_forms_saver extends bors_object_simple
{
	function save($form_object, $data, $files)
	{
		config_set('orm.auto.cache_attr_skip', true);

		if(!empty($data['time_vars']))
			bors_lib_time::parse_form($data);

//		if(config('is_developer')) { echo "On input {$form_object->debug_title()}:"; print_dd($data); print_dd($files); bors_exit(); }
//		echo "Data:"; print_dd($data);

		// Переводим строковые 'NULL' для *_id в реальный NULL
		foreach($data as $key => $value)
			if(preg_match('/_id/', $key) && $value == 'NULL')
				$data[$key] = NULL;

		$form = bors_load($data['form_class_name'], @$data['form_object_id']);

		$object = NULL;
		if(!empty($data['class_name']))
		{
			if(!empty($data['object_id']))	// Был передан ID, пытаемся загрузить
				$object = bors_load($data['class_name'], $data['object_id']);

			if(!$object) // Если не было объекта или нужно создать новый
				$object = object_new($data['class_name']);

			if(!$object) // Так и не получилось создать
				return bors_throw(ec("Не получается создать объект для сохранения ")."{$data['class_name']}({$data['object_id']})");
		}

		if(!$object)
		{
			$object = $form;
			$use_form = true;
		}
		else
			$use_form = false;

		// Проверяем не обрабатывает ли свои сохранения объект сам.
		if($object->pre_action($data) === true)
			return true;

		if(!empty($data['saver_prepare_classes']))
		{
			foreach(explode(',', $data['saver_prepare_classes']) as $cn)
				if(true === call_user_func(array($cn, 'saver_prepare'), $data))
					return true;
		}

		// Проверяем доступ
		if(!$object->access())
			return bors_message(ec("Не заданы режимы доступа класса ").get_class($object)."; access_engine=".$object->access_engine());

		if(!$object->access()->can_action(@$data['act'], $data))
			return bors_message::error(ec("Извините, у Вас недостаточно прав доступа для проведения операций с этим ресурсом"),
				array(
					'sysinfo' => "class = ".get_class($object).",<br/>\naccess class = ".($object->access_engine())
						."/".get_class($object->access()).", method = can_action(".@$data['act'].")",
				)
			);

		if(preg_match('/^\w+$/', @$data['act']))
			$method = '_'.$data['act'];
		elseif(empty($data['subaction']))
			$method = '';
		else
			$method = '_'.addslashes($data['subaction']);

		if(method_exists($object, $method = 'on_action'.$method))
			if($ret = $object->$method($data))
				return $ret;

		$object->pre_set($data);

		// Чистим служебные переменные
		$file_vars  = popval($data, 'file_vars');
		$go  = popval($data, 'go');
		$uri = popval($data, 'uri');
		$class_name = popval($data, 'class_name');

		set_session_form_data($data);

		if(($ret = $object->check_data($data)))
			return $ret;

		if(!$object->set_fields($data, true))
			return true;

//		echo "Data ="; print_dd($data); echo "<b style='color:red'>Cahnged fields =</b>"; var_dump($object->changed_fields); echo "has changed = "; var_dump($object->has_changed()); exit();

		$was_new = false;

		// Создаём новый объект, если это требуется
		if(!$object->id() && !$use_form)
		{
			$object->new_instance($data);
			$object->on_new_instance($data);
			$was_new = true;
		}

		if($file_vars)
			self::load_files($object, $data, $files, $file_vars);

		if(!empty($data['bind_to']) && preg_match('!^(\w+)://(\d+)!', $data['bind_to'], $m))
			$object->add_cross($m[1], $m[2], intval(@$data['bind_order']));

//		var_dump($object->has_changed()); exit();
		if($was_new || $object->has_changed())
		{
			$object->set_modify_time(time(), true);
			$object->set_last_editor_id(bors()->user_id(), true);
			$object->set_last_editor_ip(bors()->client()->ip());
			$object->set_last_editor_ua(bors()->client()->agent());

			$object->post_set($data);

			add_session_message(ec('Данные успешно сохранены'), array('type' => 'success'));

			if($was_new)
				$object->set_owner_id(bors()->user_id(), true);
		}

		// Чистим данные форм сессии, чтобы не мусорить дальше
		clear_session_form_data();

		if(method_exists($object, 'post_save'))
			$object->post_save($data);

		if(($memcache_instance = config('memcached_instance')))
		{
			$hash = 'bors_v'.config('memcached_tag').'_'.$this->class_name().'://'.$this->id();
			$memcache_instance->set($hash, serialize($this), 0, 0);
		}

		$go = defval($data, 'go', $go);

		if(!$go)
			$go = 'admin_parent';

		if($go)
		{
			require_once('inc/navigation.php');

			switch($go)
			{
				case 'newpage':
					return go($form_object->url());
				case 'newpage_admin':
					return go($object->admin_url());
				case 'newpage_edit_parent':
				case 'admin_parent':
					if($p = $object->get('admin_parent_url'))
						return go($p);
					if($p = $form_object->get('admin_parent_url'))
						return go($p);
					if($p = bors_load_uri($form_object->admin_url(1)))
					{
						$p = $p->parents();
						return go($p[0]);
					}
					if($ps = $form_object->parents())
					{
						return go($ps[0]);
					}
					return go($form_object->url());
			}

			if($object)
			{
				$go = str_replace('%OBJECT_ID%', $object->id(), $go);
				$go = str_replace('%OBJECT_URL%', $object->url(), $go);
			}

			if($form_object)
			{
				$go = str_replace('%OBJECT_ID%', $form_object->id(), $go);
				$go = str_replace('%OBJECT_URL%', $form_object->url(), $go);
			}

			return go($go);
		}

		return false;
	}

	function load_files($object, &$data, &$files, $file_vars)
	{
		/**
			Возможные варианты передачи данных о файле.
				Полный: file_vars: image=default_image_class_name(default_image_id)
				Короткий: file_vars: image1,image2...
		*/

//		echo "Сохранение файлов для {$object->debug_title()}"; print_dd($data); print_dd($files);

		foreach(explode(',', $file_vars) as $f)
		{
//			echo "File var '$f'<br/>";
			$method_name = NULL;

			// Метод, возвращающий объект старого файла, используется
			// 		для удаления старых версий
			$object_file_method = NULL;

			// Поле объекта, куда записывается имя класса файла
			$file_class_name_field = NULL;

			// Массивы файлов грузим как одиночные файлы
			if(preg_match('/^(\w+)\[\]$/', $f, $m))
				$f = $m[1];

			if(preg_match('/^\w+$/', $f))
			{
				// Это простое указание имени файла.
				// Обработчик загрузки целиком на совести самого объекта
				// Используются методы upload_<file_name>_file($file_data, $object_data)

				if(!method_exists($object, $method_name = "upload_{$f}_file") && !method_exists($object, $method_name = "upload_file"))
				{
					debug_hidden_log('errors.forms.files', $msg = "Undefined upload method '$method_name' for {$object}");
					bors_exit($msg);
				}

				$file_name = $f;			// Собственное имя файла
				$file_class_name = NULL;		// Имя поля объекта, где хранится класс файла
				$file_id_field = NULL;				// Имя поля объекта, где хранится id файла
			}
			elseif(preg_match('/^(\w+)=(\w+)\((\w+)\)$/', $f, $m))
			{
				$file_name = $file_field = $m[1];	// Собственное имя файла
				$file_class_name = $m[2];		// Имя поля объекта, где хранится класс файла
				$file_id_field = $m[3];				// Имя поля объекта, где хранится id файла
			}
			elseif(preg_match('!^(\w+)=(\w+)/(\w+)\((\w+)/(\w+)\)$!', $f, $m))
			{
				$file_name = $file_field = $m[1];	// Собственное имя файла, оно же имя поля объекта файла
				$file_class_name_field = $m[2];		// Имя поля объекта, где хранится имя класс файла
				$file_class_id_field = $m[3];		// Имя поля объекта, где хранится id класса файла
				$file_class_name = $m[4];			// Имя класса файла по умолчанию
				$file_id_field = $m[5];				// Имя поля объекта, где хранится id файла
			}
			else
			{
				debug_hidden_log('errors.forms.files', $msg = "Unknown file var format: '$f' for {$object}");
				bors_throw($msg);
			}

			// Удаляем старый файл, если есть пометка к его удалению.
			if(!empty($data['file_'.$file_name.'_delete_do']))
			{
				//TODO: исправить. Продумать отличие записей:
				//	attach=bors_attach(attach_id)
				//	attach=attach_class_name(attach_id)
				$old_file = $object->get($file_class_name_field);
//				echo "f=$f; file_class_name_field=$file_class_name_field; ".$file_name; var_dump($data); exit();
				if($old_file)
					$old_file->delete();
				elseif($old_file = $object->get($file_name))
					$old_file->delete();

				if(method_exists($object, $remove_method = "remove_{$file_name}_file"))
					$object->$remove_method($data);

				if($file_class_name_field)
					$object->set($file_class_name_field, NULL, false);
				if($file_id_field)
					$object->set($file_id_field, NULL, true);
			}

			$file_data = $files[$file_name];
			if(empty($file_data['tmp_name'])) // Файл не загружался. Вызываем после проверки на удаление.
				continue;

			$file_data['upload_name']  = $file_name;

			if($method_name) // Обработка вынесена в конец, чтобы корректно обработать удаление выше.
			{
				$object->$method_name($file_data, $data);
				continue;
			}

			$file_data = @$files[$file_name];
			if(!$file_data)
			{
				debug_hidden_log('errors_form', "Empty file data for {$f}");
				bors_exit("Empty file data for {$f}");
			}

/*
				Пустая загрузка выглядит так:
				Array (
				    [image] => Array (
	    		        [name] =>
    	        		[type] =>
		        	    [tmp_name] =>
        		    	[error] => 4
			            [size] => 0
    			    ))
*/
			if(empty($file_data['tmp_name']))
				continue;

			if(is_array($file_data['tmp_name'])) // Загружается массив файлов
			{
/*
				Массив файлов: Array (
		    		[image] => Array (
		            	[name] => Array (
		                    [0] => SYDNEYOP.JPG )
			            [type] => Array (
		                    [0] => image/jpeg )
			            [tmp_name] => Array (
		                    [0] => /tmp/phpYgwIOZ )
			            [error] => Array (
		                    [0] => 0 )
			            [size] => Array (
		                    [0] => 46345 )
		        	))
*/
				print_d($data); print_d($files);
				bors_exit('Загрузка массивов ещё не реализована');
			}
/*
			Одиночные файлы: Array (
			    [image] => Array (
       			    [name] => GOLDGATE.JPG
            		[type] => image/jpeg
	    	        [tmp_name] => /tmp/phpfBfZdS
    			    [error] => 0
        			[size] => 52767
			))
*/

			$old_file = NULL;
			if($object_file_method)
				$old_file = $object->get($object_file_method);
			elseif($file_id_field)
				$old_file = bors_load($file_class_name, $file_id_field);

			if($old_file)
			{
				$old_file->set('parent_class_id', NULL, true);
				$old_file->set('parent_object_id', NULL, true);
			}

//			echo "file_name = $file_name, class_name = $file_class_name, id_field = $file_id_field, file_class_name_field=$file_class_name_field";
//			var_dump($data); exit();
			$file_data['upload_dir'] = popval($data, "{$file_name}___upload_dir");
			$file_data['no_subdirs'] = popval($data, "{$file_name}___no_subdirs");
			$file_data['link_type']  = popval($data, "{$file_name}___link_type");
//			echo popval($data, "{$file_name}___parent");
			$file_data['parent'] = bors_load_uri(popval($data, "{$file_name}___parent"));
			$file = new $file_class_name(NULL);
			$file->upload($file_data);
			if(!file_exists($file->file_name_with_path()))
				bors_throw(ec('Не могу сохранить файл ').$file." ({$file->file_name_with_path()})");

			if($file_class_name_field)
				$object->set($file_class_name_field, $file->extends_class_name(), true);
			if($file_class_id_field)
				$object->set($file_class_id_field, $file->extends_class_id(), true);
			$object->set($file_id_field, $file->id(), true);
			$file->set('parent_class_id', $object->class_id(), true);
			$file->set('parent_class_name', $object->extends_class_name(), true);
			$file->set('parent_object_id', $object->id(), true);

//			var_dump($data);
//			var_dump($file_data);
//			var_dump($file->data);
		} /* end foreach */
	}
}
