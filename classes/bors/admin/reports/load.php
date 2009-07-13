<?php

class bors_admin_reports_load extends base_page
{
	function title() { return ec('Загрузка системы'); }
	function config_class() { return config('admin_config_class'); }

	function local_data()
	{
		$dbh = new driver_mysql(config('bors_core_db'));
		return array(
			'max_cpu_by_user' => $dbh->select_array('bors_access_log',
				'user_ip, user_id, count(user_ip) as cnt, sum(operation_time) as su, is_bot, user_agent',
				array('group'=>'user_ip',
					'order' => '-su',
					'limit' => 20,
				)
			),
			'max_cpu_by_classes' => $dbh->select_array('bors_access_log',
				'class_name, count(class_name) as cnt, sum(operation_time) as su',
				array('group'=>'class_name',
					'order' => '-su',
					'limit' => 20,
				)
			),
		);
	}
}