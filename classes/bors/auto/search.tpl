{form method="get" action=$this->action_url() class_name="NULL" uri="NULL" form_class_name="NULL"}
<table class="w100p null"><tr>
<td>{input name="q" value=$this->query() class="w100p"}</td>
<td width="10">{submit value="Найти"}</td>
</tr></table>
{/form}

{if $this->query() && $items}
{$this->layout()->mod('pagination')}

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
<tr>
	{foreach $item_fields as $prop_name => $prop_title}
		<td>{$x->get($prop_name)}</td>
	{/foreach}
</tr>
{/foreach}
</tbody>
</table>

{$this->layout()->mod('pagination')}
{/if}
