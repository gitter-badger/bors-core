<?php

class bors_pages_module_paginated_items extends bors_module
{
	function body_data()
	{
		$items = $this->args('items');
		$data = $this->args('table_columns');

		$table_classes = array('btab', 'w100p');

		if($ajax_sortable = $this->args('ajax_sortable'))
		{
			jquery_tablesorter::on("'.tablesorter'");
			$table_classes[] = 'tablesorter';
		}

		if(!$data)
		{
			if($class_name = $this->args('class'))
				$foo = new $class_name(NULL);
			else
				$foo = @$items[0];

			if($foo)
			{
				if($this->args('is_admin_list'))
					$data = $foo->get('item_list_admin_fields');

				if(!$data)
					$data = $foo->get('item_list_fields');

				if(!$data)
				{
					if($this->args('is_admin_list'))
						$data = array(
							'admin()->imaged_titled_link()' => ec('Название'),
							'id' => ec('ID'),
						);
					else
						$data = array(
							'title' => ec('Название'),
							'id' => ec('ID'),
						);
				}
			}
		}

		$body_data = array(
			'item_list_fields' => $data,
			'items' => $items,
		) + parent::body_data();

		$more = false;
		if(($limit = $this->args('more_limit')) && count($items) >= $limit)
		{
			$item = $items[0];
			if($this->args('more') == 'search')
				$more = dirname($item->url()).'/search/?q='.bors()->request()->data('q');
		}

		$body_data['more'] = $more;
		$body_data['table_classes'] = join(' ', $table_classes);

		return $body_data;
	}

	function make_sortable_th($property, $title)
	{
		$sorts = $this->args('sort', array());
		if(!($sort_key = @$sorts[$property]))
			return "<th>$title</th>";

		$current_sort = bors()->request()->data_parse('signed_names', 'sort');
		if(preg_match('/^(.+)\*$/', $sort_key, $m))
		{
			$sort_key = $m[1];
			$is_default = true;
		}
		else
			$is_default = false;

		if($is_default && !$current_sort)
			$current_sort = $sort_key;

		$sort = bors_lib_orm::reverse_sign($sort_key, $current_sort);

		$sign = bors_lib_orm::property_sign($sort);
		if($is_default && $sort_key == $sort)
			$sort = NULL;

		$url = bors()->request()->url();

		$url = bors_lib_urls::replace_query($url, 'sort', $sort);

		bors_lib_orm::property_sign($current_sort, true);
		bors_lib_orm::property_sign($sort_key, true);
		if($current_sort != $sort_key)
			$sort_class = 'sort_ascdesc';
		else
			$sort_class = $sign == '-' ? 'sort_asc' : 'sort_desc';

		return "<th class=\"$sort_class\"><a href=\"{$url}\">$title</a></th>";
	}
}
