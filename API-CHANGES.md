Изменения в API фреймворка
==========================

2014-10-19
----------
 * Прекращается поддержка устаревшего base_object. Заменить на bors_object.

2014-10-04
----------
 * Снос древнего legacy. Вместо global_template_data_set() и global_data() используется page_data()
 * Вместо local_template_data_set() и local_data() — body_data()

2014-09-23
----------
 * Свойства ```right_menu_links``` переименованы в ```side_menu```

2013-07-08
----------
 * Рефакторинг: полное разделение функционала метода url($page=NULL) на url() и url_ex($page). Связано с ужесточением контроля аргументов в PHP 5.4. Теперь $this->url() всегда равен $this->url_ex($this->page())
 * Рефакторинг: окончательный отказ от древних функций. main_db() в пользу db_name(), main_table() в пользу table_name() и main_table_fields() в table_fields().

2013-04-29
----------
 * Ключ списков, указывающих значение по умолчанию, переименован с 'default' в '*default'.

2013-01-31
----------
 * Окончательный снос Smarty2
 * Окончательный отказ от main_db() и main_table() в пользу db_name() и table_name()

2013-01-30
----------
 * Все loaded() переименованы в is_loaded()
 * Все set_loaded() переименованы в set_is_loaded()
 * init(false) теперь просто nop
 * init(true) переименован в data_load()
 * снос устаревшего storage_db_mysql_smart и связанных с ним тестов

2011-09-22
----------
Проверить все файлы *.tpl.php — на них сейчас работает автоопределение шаблонизатора тела страницы
и у них теперь более высокий приоритет, чем у *.html (smarty)! Пройти поиском и посмотреть, нет ли
одноимённых *.html.

2011-09-20
----------
По возможности все древние bors_cross методы заменить на bors_link.
Дублирование непарных связей через bors-core/classes/bors/link.pair-updates.sql
Варианты замены:
 — bors_get_cross_objs($object, $to_class) -> bors_link::objects($object)
 — bors_get_cross_ids($this, $to_class) -> bors_link::object_ids()
 — bors_add_cross() -> bors_link::link()
 — bors_add_cross_obj -> bors_link::link_objects
 — bors_remove_cross_pair() -> bors_link::drop_target($o1, $o2)/bors_link::drop($c1,$i1,$c2,$i2)

2011-09-14
----------
Системная переменная 'system.use_sessions' переименована в 'system.session.skip'.
Соответственно, изменена и логика. По умолчанию сессии всегда включены.

2011-07-06
----------
Исправлена Smarty-функция {input_image}. Проверить параметры и работоспособность!

2011-06-30
----------
Найти потомков base_xml_array и поменять, если нужно, устаревшие local_data() на body_data()

2011-06-23
----------

Строгая логика функций очистки кеша.
$object->cache_clean() — очистка кеша объекта и всех зависящих от него объектов
$object->cache_clean_self() — очистка кеша _только_ объекта
cache_depends_on() — задать кеш-группы, от которых зависит данный объект
cache_provides() — задать кеш-группы, которые обеспечивает данный объект
cache_children() — конкретные объекты, которые нужно чистить при очистке данного

Для устранения неоднозначностей методы extends_class() переименовываются в extends_class_name().
Регексп для поиска: extends_class[^_]

2011-06-18
----------

Переименование редких имён классов:
storage_fs_htsu -> bors_storage_htsu

2011-02-15
----------

Полностью сносим древние методы fields_map_db() в пользу fields(). Но
лучше и их владельцев переписать на использование bors_storage_mysql.
Также сносятся в пользу:
main_db_storage() -> main_db()
main_table_storage() -> main_table()

Также стоит проверить на всякий случай отсутствие в table_fields()
левых «=> array('field' =>», так как поле 'field' никогда
не поддерживалось. Вместо него используется 'name'.

2010-07-22
----------

Для единообразия и внятности bors-core/config.php меняет имя на init.php и выполняет функцию полной инициализации
системы. Но лучше его без надобности не вызывать, всё равно будет включен через bors-core/main.php
В проектах, где так было сделано изначально ничего не изменится.

config/default.php теперь переносится в config.php

Поиск по: for i in $(locate bors-loader.php|grep '/var/www/'); do echo $i; cat $i|grep config.php; done

2010-07-05
----------
Это только подготовка, из API ещё не снесено.
 * Давно пора снести, так что просто напоминание в копилку: проверить во всех проектах и заменить 
   'function data_providers()'.
 * Снести все вызовы base_empty->load_attr().
 * Перенести всё из base_empty в bors_object.

2010-07-01
----------

 * В связи с унификацией формата вызова все функции templates_XXXX(), служащие для прописи стандартных значений
   шаблонов изменены на template_XXXX(). Поиск по "templates_.*\("
 * Отменено иcпользование переменной шаблона $use_jQuery. Вместо неё используйте теперь template_jquery() в
   pre_show() или т.п. Искать по '$use_jQuery'.

2010-06-25
----------

 * После недавней модификации метода loaded() нужно проверить, чтобы нигде в качестве возвращаемого значения не использовались объекты.
   Искать тупо по "function loaded()".

2010-06-24
----------

 * Метод title() теперь работает без параметра $exact = false. Вместо этого используйте новый метод title_true().
   Поиск по 'title(true)'.
