﻿=== SMSPILOT.RU WooCommerce ===
Contributors: sergey.shuchkin@gmail.com
Tags: woo commerce, woocommerce, ecommerce, sms, sms notification
Requires at least: 3.8
Tested up to: 6.4
Stable tag: 1.48
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

		
SMS уведомления о заказах WooCommerce через шлюз SMSPILOT.RU

== Description ==

SMS уведомления о заказах через сайт SMSPILOT.RU для Wordpress WooCommerce.
Из коробки работает отправка SMS продавцу о новом заказе, звонок продавцу о новом заказе, SMS продавцу о смене статуса,
SMS покупателю с подтверждением заказа, SMS покупателю о смене статуса заказа.
Поддерживается вставка информации о заказе и покупателе: продукты, количество, цена, имя, телефон, адрес, произвольные поля (трек-номер).

[САЙТ](https://smspilot.ru/woocommerce.php) | [Поддержка](https://smspilot.ru/support.php)

== Installation ==
1. Убедитесь что у вас установлена последняя версия плагина [WooCommerce](http://www.woothemes.com/woocommerce)
2. Есть 3 варианта установки:
    2.1 Через каталог плагинов:
        * в административной панели перейдите на страницу *Плагины* и нажмите *Добавить новый*
        * найдите плагин SMSPILOT.RU WooCommerce
        * нажмите кнопку *Установить*
    2 Через консоль:
        * скачайте плагин здесь https://smspilot.ru/woocommerce.php
        * в административной панели перейдите на страницу *Плагины* и нажмите *Добавить новый*.
        * перейдите на вкладку *Загрузить*, нажмите Обзор и выберите архив с плагином. Нажмите *Установить*.
    3 По FTP:
        * скачайте плагин здесь https://smspilot.ru/woocommerce.php
        * распакуйте архив и загрузите по FTP "smspilot-woocommerce" в папку ваш-домен/wp-content/plugins
        * в административной панели перейдите на страницу *Плагины* и нажмите *Установить* рядом с появившемся плагином
3. После того, как плагин будет установлен, нажмите *Активировать плагин*.
4. Наведите курсор на пункт меню *WooCommerce* и выберите *SMSPILOT.RU*
5. В настройках введите [API-ключ](https://smspilot.ru/my-settings.php), телефон продавца, имя отправителя (не обязательно).
6. Если это необходимо, то укажите статусы для каждого вида сообщений и текст SMS
7. Нажмите кнопку Сохранить. Можно нажать кнопку для сохранения и отправки тестовой SMS на телефон продавца.

== Screenshots ==
1. Окно настройки плагина

== Custom usage ==
Пример оптравки SMS в других частях wordpress

smspilot_send('79087964781','test');

== Changelog ==
= 1.48 =
+ добавлены шаблонные переменные
{KEY} - внутренний код заказа
{VIEW_URL}, {EDIT_URL}, {CANCEL_URL} - ссылки на просмотр/изменение/отмену заказа в ЛК покупателя
{PAY_URL} - ссылка на оплату заказа

= 1.47 =
+ звонок продавцу о новом заказе
+ протестировано на WP 6.4

= 1.46 =
- исправлена отправка дубликатов SMS в результате неправильно проставленных галочек статусов в настройках плагина
+ протестировано на WP 6.0.3
= 1.45 =
- исправлено дублирование SKU/Артикула
+ протестировано на WP 6.0.3
= 1.44 =
+ протестировано на WP 6.0
= 1.43 =
+ протестировано на новой версии WP
= 1.42 =
- названия шаблонов
= 1.42 =
+ secure update: esc_html
= 1.41 =
+ secure update: short tags replaced, used esc_attr, curl replaced by wp_remote_post
= 1.4 =
+ secure update, sanitize_text_field, esc_html
= 1.33 =
+ SSL
= 1.32 =
+ Дополнительный вариант SMS покупателю
= 1.31 =
+ текст последней ошибки отображается в настройках плагина
= 1.30 =
- удаление из текста SMS неизвестных полей {VAR}
= 1.29 =
+ вставка значений произвольных полей, например {Трек-номер}, чувствительно к регистру символов
= 1.28 =
- wptexturesize там не нужен
= 1.27 =
- корректно извлекаем комментарий пользователя
= 1.26 =
- Автоопределение транспорта curl или fsocket, корректное тестовое сообщение
= 1.25 =
- SMS нескольким продавцам
= 1.24 =
- удалем лишние пробелы, сокращаем текст, чтобы уложиться в 670 символов
= 1.23 =
- исправлены теги {FIRSTNAME}, {LASTNAME}, {CITY}, {ADDRESS}
= 1.22 =
- При деактивации теперь не слетают настройки
+ Работа через сокеты, этот метод стабильнее
+ Приоритет обработки изменён на минимум, чтобы дать возможность отработать другим плагинам
= 1.20 =
* Релиз
= 1.21 =
* очистка списка заказа от html тегов
= 1.20 =
* Релиз