<?php

bors_url_submap(array(
	'/ => _main',
	'/delete/\?object=([^&]+).* => bors_tools_delete(1)',
	'/mark/delete/\?object=([^&]+).* => _mark_delete(1)',
	'/edit\-smart/\?object=([^&]+).* => _edit_smart(1)',
	'/edit\-smart/ => _edit_smart',
	'/append/child\?object=([^&]*) => _append_child(1)',
	'/edit/page\?object=([^&]+) => _edit_page(1)',
	'/property\?object=([^&]*) => _property(1)',
	'/visibility\?act=(show|hide)&object=([^&]*) => _visibility(2)',
	'/reports/ => _reports_main',
	'/reports/load/ => _reports_load',
	'/cross_unlink\?.* => _cross_unlink',
));