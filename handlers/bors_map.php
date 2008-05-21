<?php
    $map = array(
		'.*/\d{4}/\d{1,2}/\d{1,2}/topic\-(\d+)\-rss\.xml => forum_topic_rss(1)',
		'.*/\d{4}/\d{1,2}/topic\-(\d+)\-rss\.xml => forum_topic_rss(1)',
		'.* => page_fs_separate(url)',
		'.* => base_page_hts(url)',
		'/do-login/? => common_do_login',
		
		'/admin/tools/delete/\?object=([^&]+).* => bors_tools_delete(1)',
		'/admin/tools/delete/\?(.+) => bors_tools_delete(1)',

		'/admin/\?object=([^&]+).* => bors_admin_main(1)',
		'/admin/edit/\?object=([^&]+).* => bors_admin_edit(1)',
		'/admin/logout/ => bors_admin_logout',
	);
