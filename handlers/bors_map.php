<?php

$map = array(
	'/cache(/.*/\d*x\d*/[^/]+\.(jpe?g|png|gif)) => bors_image_autothumb(1)',
	'/cache(/.*/\d*x\d*\([^)]+\)/[^/]+\.(jpe?g|png|gif)) => bors_image_autothumb(1)',

	'/do\-login/ => common_do_login',
	'/users/do\-login => common_do_login',

	'/users/do\-logout => common_do_logout',
	'/user/cookie\-hash\-update\.bas\?(\w+) => user_cookieHashUpdate(1)',
	'/actions/do\-logout/ => common_do_logout',

	'.*/\d{4}/\d{1,2}/\d{1,2}/topic\-(\d+)\-rss\.xml => forum_topic_rss(1)',
	'.*/\d{4}/\d{1,2}/topic\-(\d+)\-rss\.xml => forum_topic_rss(1)',
	'.* => page_fs_xml(url)',
	'.* => page_fs_separate(url)',
//	'.* => page_db(url)',
	'.* => base_page_hts(url)',
	'.* => auto_object_php(url)',
	'(/_bors/admin/)\?object=([^&]+).* => bors_admin_main(1)',
	'/admin/delete/\?object=([^&]+).* => bors_tools_delete(1)',
	'/admin/mark/delete/\?object=([^&]+).* => bors_admin_mark_delete(1)',
	'/_bors/admin/edit\-smart/\?object=([^&]+) => bors_admin_edit_smart(1)',
	'/_bors/admin/edit\-smart/ => bors_admin_edit_smart',
	'/admin/cross_unlink\?.* => bors_admin_cross_unlink',

	'/_bors/ => bors_admin_main',
	'/_bors/admin/append/child\?object=([^&]*) => bors_admin_append_child(1)',
	'/_bors/admin/edit/page\?object=([^&]+) => bors_admin_edit_page(1)',
	'/_bors/admin/property\?object=([^&]*) => bors_admin_property(1)',
	'/_bors/admin/visibility\?act=(show|hide)&object=([^&]*) => bors_admin_visibility(2)',
	'/_bors/users/do\-login\.bas => bors_admin_users_login',
	'/_bors/users/do\-logout\.bas => bors_admin_users_logout',

//	Заглушки для удобства.
	'/login/? => bors_admin_users_login',
	'.*/\?login => bors_admin_users_login',

	'(.*/)\?new => bors_admin_append_new(1)',

	'/___/ => bors_ext_admin_main',
	'/___/core/ => bors_ext_admin_core_main',
	'(/___/core/)edit/\?object=(\w+) => bors_ext_admin_core_edit(2)',

	'/admin/tools/cache_drop/\?object=(.*) => bors_admin_tools_clean(1)',
	'/admin/tools/set\-sort\-order/ => bors_admin_tools_setsortorder',
	'/admin/tools/set\-default/ => bors_admin_tools_setdefault',

	'/_bors/admin/\?object=([^&]+).* => bors_admin_main(1)',
	'/admin/edit/\?object=([^&]+).* => bors_admin_edit(1)',
	'/admin/clean/\?object=([^&]+).* => bors_admin_tools_clean(1)',
	'/admin/login/ => bors_admin_login',
	'/admin/logout/ => bors_admin_logout',

	'/admin/image/append => bors_admin_image_append',
);
