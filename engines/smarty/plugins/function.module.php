<?php

function smarty_function_module($params, &$smarty)
{
	
	if(empty($params['name']))
	{
		foreach(explode(' ', 'class id page') as $name)
		{
			$$name = @$params[$name];
			unset($params[$name]);
		}
			
		$obj = object_load($class, $id, $params);

		$params['page'] = $page;
		
		if(!$id)
			$id = bors()->main_object();
			
		$obj = object_load('module_'.$class, $id, $params);
		
		//TODO: убрать?
		if(!$obj)
			$obj = object_load("module_{$class}", $id, $params);

		if(!$obj)
			return "Can't load module '$class' nor 'module_{$class}'";

		return $obj->body();
	}

	$name = $params['name'].".php";
	foreach($params as $key=>$val)
		$GLOBALS['module_data'][$key] = $val;
		
	ob_start();
	include("modules/$name");
	$res = ob_get_contents();
	ob_end_clean();
	return $res;
}
