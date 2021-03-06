<?php

// Нужно для модификатора в шаблоне
require_once('inc/clients/geoip-place.php');

class bors_admin_reports_load extends bors_admin_page
{
	function title() { return ec('Загрузка системы на ').date('d.m.Y H:i'); }

	function body_data()
	{
		$dbh = new driver_mysql(config('bors_local_db'));

		$period = time() - $dbh->select('bors_access_log', 'MIN(access_time)', array());

		return array(
			'total_time' => $dbh->select('bors_access_log', 'SUM(operation_time)', array()),
			'period' => $period,

			'max_cpu_by_user' => $dbh->select_array('bors_access_log',
				'GROUP_CONCAT(DISTINCT user_ip ORDER BY user_ip ASC SEPARATOR "<br/>\n") AS user_ip, user_id, count(user_ip) as cnt, sum(operation_time) as su, is_bot, is_crowler AS is_crawler, user_agent',
				array(
					'group' => 'COALESCE(`is_bot`,`user_ip`)',
					'order' => '-su',
					'limit' => 20,
				)
			),

			'max_cpu_by_classes' => $dbh->select_array('bors_access_log',
				'class_name, count(class_name) as cnt, sum(operation_time) as su, MAX(`server_uri`) AS `uri`, MAX(`referer`) AS `referer`',
				array('group'=>'class_name',
					'order' => '-su',
					'limit' => 20,
				)
			),

			'max_cpu_by_combine' => $dbh->select_array('bors_access_log',
				'GROUP_CONCAT(DISTINCT user_ip ORDER BY user_ip ASC SEPARATOR "<br/>\n") AS user_ip, class_name, user_id, count(*) as cnt, sum(operation_time) as su, is_bot, user_agent',
				array(
					'group' => 'COALESCE(`is_bot`,`user_ip`),class_name',
					'order' => '-su',
					'limit' => 20,
				)
			),

			'can_see_ip' => object_property(bors()->user(), 'is_coordinator'),
		);
	}

//	function cache_static() { return bors()->user() ? 0 : rand(60, 120); }
}
