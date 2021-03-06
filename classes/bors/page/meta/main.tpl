{$this->layout()->mod('pagination')}

{block name="items_list"}
<table class="btab w100p">
<thead>
<tr>
{foreach $item_fields as $prop_name => $prop_title}
	{$this->make_sortable_th($prop_name, $prop_title)}
{/foreach}
</tr>
</thead>
<tbody>
{foreach from=$items item="x"}
<tr{if $x->get('items_list_table_row_class')} class="{join(' ', $x->get('items_list_table_row_class'))}"{/if}>
	{foreach $item_fields as $prop_name => $prop_title}
		<td>{$x->get($prop_name)}</td>
	{/foreach}
</tr>
{/foreach}
</tbody>
</table>
{/block}

{$this->layout()->mod('pagination')}
