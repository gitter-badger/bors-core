<?php

class bors_cross_types extends base_list
{
	function named_list()
	{
		return array(
			'0' => ec('Не задано'),
			'1' => ec('Автоматический'),
			'2' => ec('Упоминается'),
			'3' => ec('Посвящается'),
			'4' => ec('Удалено'), // Сохраняется для подавления автоматических добавлений.
		);
	}
}