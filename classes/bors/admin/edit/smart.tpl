<ul>
<li>Объект: {$object->class_title()} <i>{$object->titled_link()}</i>, ID: {$object->id()}</li>
{if $object->create_time(true)}<li>Создан: {$object->create_time()|short_time}{if $object->get('owner')} пользователем {$object->owner()->admin()->imaged_titled_link()}{/if}</li>{/if}
{if $object->create_time(true) != $object->modify_time(true) && $object->modify_time(true)}
<li>Изменён {$object->modify_time()|short_time}{if $object->last_editor()} пользователем {$object->last_editor()->admin()->imaged_titled_link()}{/if}</li>
{/if}
{foreach from=$object->admin_additional_info() key="title" item="value"}
<li>{$title}: {$value}</li>
{/foreach}
</ul>

{form action=$this->url() class=$object->class_name() id=$object->id() fields=$auto_fields}
{foreach from=$fields key="title" item="x"}
{assign var="desc" value=$x.origin}
{assign var="ptype" value=$x.type}
{assign var="pargs" value=$x.args}
{explode vars="field_name,type" value=$desc}
{explode vars="type,arg" value=$ptype delim="="}
{explode vars="arg,args" value=$arg delim=":"}
<tr><th align="right" width="200">{$title}</th><td>
{if $type == ''}{input name=$field_name class="w100p"}{/if}
{if $type == 'input'}{input name=$field_name class="w100p" size=$arg}{/if}
{if $type == 'dropdown'}{dropdown name=$field_name list=$arg args=$args class="w100p"}{/if}
{if $type == 'checkbox'}{checkbox name=$field_name}{/if}
{if $type == 'textarea'}{textarea name=$field_name rows=$arg class="w100p"}{/if}
{if $type == 'image'}{bors_object_load class="bors_image" id=$object|get:$field_name var="img"}{$img|get:thumbnail:$arg|get:html_code}{/if}
{if $ptype == 'input_date'}{input_date name=$field_name can_drop=1 year_min=1900 time=$pargs.time}{/if}
</td></tr>
{/foreach}
{if $object->get('smart_form_append')}
{$object->get('smart_form_append')}
{/if}
{submit value="Сохранить"}

{foreach from=$smarty.get item="v" key="k"}
{hidden name=$k value=$v}
{/foreach}

{go value=$referer}
{/form}

{if $items}
{foreach from=$items item="list" key="title"}
<h2>{$title|htmlspecialchars}</h2>
<ul>
{foreach from=$list item="x"}
<li>{$x->admin()->imaged_titled_link()}</li>
{/foreach}
</ul>
{/foreach}
{/if}

{if $cross}
<h4>Связанные материалы:</h4>
<ul>
{foreach from=$cross item="x"}
<li>{$x->target()->class_title()}&nbsp;{$x->target()->admin()->imaged_titled_link()}&nbsp;{$x->admin()->imaged_link('unlink', 'unlink.gif', 'Убрать связь')}</li>
{/foreach}
</ul>
{/if}

{if $object->id() && $object->access()->can_delete()}
<div align="center">
[ <b>{$object->admin()->imaged_delete_link(true)}</b> ]
</div>
{/if}
