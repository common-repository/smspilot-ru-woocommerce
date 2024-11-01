<?php /** @noinspection PhpMissingReturnTypeInspection */
/*
Plugin Name: SMSPILOT.RU WooCommerce
Description: SMS уведомления о заказах WooCommerce через шлюз SMSPILOT.RU
Version: 1.48
Author: SMSPILOT.RU
Author URI: https://smspilot.ru
Plugin URI: https://smspilot.ru/woocommerce.php
*/
if (!is_callable('is_plugin_active')) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
if (is_plugin_active('woocommerce/woocommerce.php')) {
	add_action('plugins_loaded', 'smspilot_woocommerce::load');
}

register_activation_hook( __FILE__, 'smspilot_woocommerce::activate' );

// smspilot_send( '79087964781', 'test');
function smspilot_send( $phone, $message )
{
    return (new smspilot_woocommerce())->send( $phone, $message );

}


class smspilot_woocommerce {

	public static function load() {
		$_this = new self();
		add_action( 'admin_menu', array($_this,'admin_menu') );
		add_action( 'woocommerce_new_order', array($_this,'status_changed') );
		add_action( 'woocommerce_order_status_changed', array($_this, 'status_changed'), 10, 3 );
		return $_this;
	}


	public static function activate()
	{
		register_uninstall_hook( __FILE__, 'smspilot_woocommerce::uninstall' );
	}

	public static function uninstall() {

	    delete_option('smspilot_apikey');
	    delete_option('smspilot_sender');
	    delete_option('smspilot_vendor_phone');
		delete_option('smspilot_vendor_status1');
	    delete_option('smspilot_vendor_msg1');
        delete_option('smspilot_vendor_voice1');
	    delete_option('smspilot_vendor_status2');
		delete_option('smspilot_vendor_msg2');
		delete_option('smspilot_shopper_status1');
		delete_option('smspilot_shopper_msg1');
		delete_option('smspilot_shopper_status2');
		delete_option('smspilot_shopper_msg2');
		delete_option('smspilot_last_error');
	}
	
	public function admin_menu() {
		add_submenu_page('woocommerce', 'SMS оповещения о заказах через SMSPILOT.RU', 'SMSPILOT.RU', 'manage_woocommerce', 'smspilot_settings', array(&$this,'options'));
	}
	private function params()
	{
		return array(
			'apikey' => get_option('smspilot_apikey'),
			'sender' => get_option('smspilot_sender'),
			'vendor_phone' => get_option('smspilot_vendor_phone'),
			'vendor_status1' => get_option('smspilot_vendor_status1','processing'),
			'vendor_msg1' => get_option('smspilot_vendor_msg1', 'Поступил заказ на сумму {SUM}. Номер заказа {NUM}'),
            'vendor_voice1' => get_option('smspilot_vendor_voice1'),
			'vendor_status2' => get_option('smspilot_vendor_status2','cancelled,failed'),
			'vendor_msg2' => get_option('smspilot_vendor_msg2','Статус заказа изменился на {NEW_STATUS}. Номер заказа {NUM}'),
			'shopper_status1' => get_option('smspilot_shopper_status1', 'processing'),
			'shopper_msg1' => get_option('smspilot_shopper_msg1','Ваш заказ на сумму {SUM} принят. Номер заказа {NUM}'),
			'shopper_status2' => get_option('smspilot_shopper_status2','completed'),
			'shopper_msg2' => get_option('smspilot_shopper_msg2','Статус вашего заказа изменился на {NEW_STATUS}. Номер заказа {NUM}'),
			'shopper_status3' => get_option('smspilot_shopper_status3',''),
			'shopper_msg3' => get_option('smspilot_shopper_msg3','')

		);
	}
	public function options() {

		$p = $this->params();
		$test_result = '';
		$message = '';
		$vendor_phone = '';

		if ( isset($_POST['apikey']) ) {
			foreach( $p as $k => $vv ) {
				$v = '';
				if (isset($_POST[$k])) {
					if ( is_string($_POST[$k]) ) {
						$v = sanitize_text_field( $_POST[ $k ] );
					} else if ( is_array($_POST[$k]) ) {
						$v = sanitize_text_field( implode(',', $_POST[$k]) );
					}
				}
				update_option('smspilot_' . $k, $v);
			}

			if ( isset($_POST['test']) ) {
				$data = array(
					'%s' => '1234.56',
					'%n' => '7890',
					'{SUM}' => '1234.56',
					'{FSUM}' => '1234.56 руб.',
					'{NUM}' => '7890',
                    '{KEY}' => 'abcdefgh',
					'{ITEMS}' => 'Название товара: 2x150=300',
					'{EMAIL}' => 'pokupatel@mail.ru',
					'{PHONE}' => '+79000000000',
					'{FIRSTNAME}' => 'Сергей',
					'{LASTNAME}' => 'Смирнов',
					'{CITY}' => 'г. Омск',
					'{ADDRESS}' => 'ул. Ленина, д. 1, кв. 2',
					'{BLOGNAME}' => get_bloginfo('name'),
					'{OLD_STATUS}' => 'Обработка',
					'{NEW_STATUS}' => 'Выполнен',
					'{COMMENT}' => 'Код домофона 123, после обеда',
                    '{VIEW_URL}' => '__view_url__',
                    '{EDIT_URL}' => '__edit_url__',
                    '{CANCEL_URL}' => '__cancel_url__',
                    '{PAY_URL}' => '__pay_url__',
					'{' => '*',
					'}' => '*',
				);
				$vendor_phone = sanitize_text_field( $_POST['vendor_phone'] );
				$message = str_replace( array_keys($data), array_values($data), sanitize_text_field( $_POST['vendor_msg1'] ) );
				$test_result = $this->send( $vendor_phone, $message );
                if ($v = get_option('smspilot_vendor_voice1')) {
                    $this->send( $vendor_phone, $message, $v === 'F' ? 'GOLOS' : 'GOLOSM');
                }

			} else {
				wp_redirect(admin_url('admin.php?page=smspilot_settings&status=updated'));
				return;
			}
			$p = $this->params();
		}


		/* pending Order received (unpaid)
failed – Payment failed or was declined (unpaid). Note that this status may not show immediately and instead show as Pending until verified (i.e., PayPal)
processing – Payment received and stock has been reduced – the order is awaiting fulfillment. All product orders require processing, except those that are Digital and Downloadable.
completed – Order fulfilled and complete – requires no further action
on-hold – Awaiting payment – stock is reduced, but you need to confirm payment
cancelled – Cancelled by an admin or the customer – no further action required
refunded – Refunded by an admin – no further action required */


		$msg = array(
			array( 'SMS продавцу о новом заказе', 'vendor_status1', 'vendor_msg1' ),
			array( 'SMS продавцу о смене статуса', 'vendor_status2', 'vendor_msg2' ),
			array( 'SMS покупателю о подтверждении заказа', 'shopper_status1', 'shopper_msg1' ),
			array( 'SMS покупателю о смене статуса', 'shopper_status2', 'shopper_msg2' ),
			array( 'SMS покупателю о смене статуса (дополнительно)', 'shopper_status3', 'shopper_msg3' )
		);
?>
		<div class="wrap woocommerce">
			<form method="post" id="mainform" action="<?php echo admin_url('admin.php?page=smspilot_settings') ?>">
				<h2>SMS оповещения о заказах через SMSPILOT.RU</h2>
				<p><a href="https://smspilot.ru/my.php" target="_blank">Личный кабинет</a> | <a href="https://smspilot.ru/my-report.php" target="_blank">Отчеты о доставке</a></p>
				<?php
					if ( is_numeric( $test_result ) ) {
						printf(
							'<p style="color: green">Сообщение: <code>%s</code> отправлено на %s, код <a href="https://smspilot.ru/my-report.php?view=sms&search=[%s]" target="_blank" title="Отчет о доставке, откроется в новой вкладке">%s</a></p>',
							esc_html( $message ),
							esc_html( $vendor_phone ),
							esc_html( $test_result ),
							esc_html( $test_result )
						);
					} elseif ( $test_result !== '' ) {
						printf(
							'<p style="color: red">Сообщение: <code>%s</code> НЕ отправлено на %s, причина:<br/>%s</p>',
							esc_html( $message ),
							esc_html( $vendor_phone ),
							esc_html( $test_result )
						);
					}
				?>
				<?php echo (isset($_GET['status']) && $_GET['status'] === 'updated' ) ? '<p style="color: green">Настройки обновлены</p>' : '' ?>
				<?php echo ( $last_e = get_option('smspilot_last_error')) ? '<p>Последняя ошибка:<br/>'.esc_html( $last_e ).'</p>' : '' ?>
				<table class="form-table">
					<tr><th><label for="apikey">API-ключ</label></th><td><input title="64-символьный ключ доступа к сайту SMSPILOT.RU" required name="apikey" id="apikey" value="<?php echo esc_attr( $p['apikey'] ) ?>" size="64" /><br/>
					<small>Замените API-ключ на свой,  <a href="https://smspilot.ru/my-settings.php" target="_blank">https://smspilot.ru/my-settings.php</a></small></td></tr>
					<tr><th><label>Баланс</label></th><td><?php

							if ( $p['apikey'] ) {
								$json = $this->_post( array('balance' => 'rur', 'format' => 'json', 'apikey' => $p['apikey']) );
                                if ( $j = json_decode($json, false)) {
									if ( isset($j->error) ) {
										printf(
											'<em style="color: red">%s</em',
											esc_html( $j->error->description_ru )
										);
									} else {
										printf(
											'%s&nbsp;&nbsp;|&nbsp;&nbsp;<a href="https://smspilot.ru/my-order.php" target="_blank">Пополнить баланс</a>',
											esc_html( $j->balance )
										);
									}
								} else {
									printf( 'JSON error %s', esc_html( $json ) );
								}
							} else {
								echo '<span style="color: red">Нужно ввести API-ключ</span>';
							}
							?></td></tr>
					<tr><th><label for="sender">Имя отправителя</label></th><td><input name="sender" id="sender" value="<?php echo esc_attr( $p['sender'] ) ?>" /><br/>
					<small>Список доступных имен <a href="https://smspilot.ru/my-sender.php" target="_blank">https://smspilot.ru/my-sender.php</a>, можно оставить пустым.</small></td></tr>
					<tr><th><label for="vendor_phone">Телефон продавца</label></th><td><input required name="vendor_phone" id="vendor_phone" value="<?php echo esc_attr( $p['vendor_phone'] )  ?>" size="40" /><br/><small>Например, 79089876543, можно указать несколько через запятую.</small></td></tr>
					<tr><th><label>Шаблоны SMS</label></th><td><p>Если меняете текст здесь, то добавьте нужный <a href="https://smspilot.ru/my-template.php" target="_blank" title="Откроется в новой вкладке">шаблон в личном кабинете SMSPILOT.RU</a>.<br/>Бизнес-клиентам сервиса это делать не обязательно.</p>
						</td></tr>
					<?php foreach( $msg as $m) { ?>
					<tr><th><label for="<?php echo esc_attr( $m[2] ) ?>"><?php echo esc_html( $m[0] ) ?></label></th><td>
					<?php printf( 'Статус: %s', $this->_checkboxes( $m[1], $p[ $m[1] ] ) ) ?><br/>
					Текст: <input name="<?php echo esc_attr( $m[2] ) ?>" id="<?php echo esc_attr( $m[2] ) ?>" value="<?php echo esc_attr( $p[ $m[2] ] )  ?>" size="80" />
					</td>
					</tr>
					<?php
                        if ($m[1] === 'vendor_status1') { ?>
                    <tr><th><label for="vendor_voice1">Звонок продавцу о новом заказе</label>
                        </th><td><select name="vendor_voice1" id="vendor_voice1">
                                <option value=""<?= $p['vendor_voice1'] ? '' : ' selected="selected"' ?>>Нет</option>
                                <option value="F"<?= $p['vendor_voice1'] === 'F' ? ' selected="selected"' : '' ?>>Приятным женским голосом</option>
                                <option value="M"<?= $p['vendor_voice1'] === 'M' ? ' selected="selected"' : '' ?>>Мужским голосом</option>
                            </select><br/><small>Такие звонки могут отмечаться как нежелательные, добавьте номера в исключения</small></td></tr>
                            <?php
                        }
                    } ?>
					<tr><th><label>Можно вставить</label></th><td>
						<pre><code>{NUM} - номер заказа, {FNUM} - №номерзаказа, {KEY} - внутренний код заказа, {SUM} - сумма заказа, {FSUM} - суммазаказа руб., {EMAIL} - эл.почта покупателя,
{PHONE} - телефон покупателя, {FIRSTNAME} - имя покупателя, {LASTNAME} - фамилия покупателя,
{CITY} - город доставки, {ADDRESS} - адрес доставки, {BLOGNAME} - название блога/магазина,
{OLD_STATUS} - старый статус, {NEW_STATUS} - новый статус, {ITEMS} - список заказанных товаров
{COMMENT} - комментарий покупателя к заказу
{VIEW_URL}, {EDIT_URL}, {CANCEL_URL} - ссылки на просмотр/изменение/отмену в ЛК покупателя,
{PAY_URL} - ссылка на оплату

<strong>{Произвольное поле}</strong> - вставка значения произвольного поля, которое вы или плагины добавили к заказу, например, {post_tracking_number} или {ems_tracking_number} если установлен плагин <a href="https://ru.wordpress.org/plugins/russian-post-and-ems-for-woocommerce/" target="_blank">Почта России и EMS для WooCommerce</a> . Чувствительно к регистру символов!</code></pre></td></tr>
				</table>
				<br>
				<input type="submit" class="button-primary" value="Сохранить">&nbsp;&nbsp;
				<input type="submit" class="button-secondary" name="test" value="Сохранить и отправить тестовую SMS на телефон продавца" />
			</form>
		</div>
<?php
	}
	private function _checkboxes( $name, $selected )
	{
		$selected = explode(',',$selected);

		$r = '';
		foreach( wc_get_order_statuses() as $k => $v ) {
			$k = substr( $k, 3 );
			$r .= '<label><input type="checkbox" name="'.$name.'[]"'.( in_array( $k, $selected, true ) ? ' checked="checked"' : '').' value="'.$k.'" /> '.$v.'</label>&nbsp;&nbsp;';
		}
		return $r;
	}

	public function status_changed($order_id, $old_status = 'pending', $new_status = 'pending')
	{

		$p = $this->params();

		if ( $p['apikey'] ) {

			$o = new WC_Order($order_id);

			// SMS to admin
			if ( strpos($p['vendor_status1'], $new_status) !== false ) { // new order
				$this->_send( $p['vendor_phone'], $p['vendor_msg1'], $o, $old_status, $new_status );
			} elseif ( strpos($p['vendor_status2'], $new_status) !== false ) { // new status
				$this->_send( $p['vendor_phone'], $p['vendor_msg2'], $o, $old_status, $new_status );
			}
			// SMS to shopper
			if ( strpos($p['shopper_status1'], $new_status) !== false ) { // confirmed order
				$this->_send( $o->get_billing_phone(), $p['shopper_msg1'], $o, $old_status, $new_status );
			} elseif ( strpos($p['shopper_status2'], $new_status) !== false ) { // new status
				$this->_send( $o->get_billing_phone(), $p['shopper_msg2'], $o, $old_status, $new_status );
			} elseif ( strpos($p['shopper_status3'], $new_status) !== false ) { // new status alt
				$this->_send( $o->get_billing_phone(), $p['shopper_msg3'], $o, $old_status, $new_status );
			}
		}
	}

	/**
	 * @param $phone
	 * @param $message
	 * @param $order WC_Order
	 * @param $old_status
	 * @param $new_status
	 *
	 * @return void
	 */
	private function _send($phone, $message, $order, $old_status, $new_status ) {
		if ( ! $phone || ! $message ) {
			return;
		}

//		file_put_contents( __FILE__.'.log', print_r( $order, true) );

		$search  = array(
			'{NUM}',
            '{KEY}',
			'{FNUM}',
			'{SUM}',
			'{FSUM}',
			'{EMAIL}',
			'{PHONE}',
			'{FIRSTNAME}',
			'{LASTNAME}',
			'{CITY}',
			'{ADDRESS}',
			'{BLOGNAME}',
			'{OLD_STATUS}',
			'{NEW_STATUS}',
			'{COMMENT}',
            '{VIEW_URL}',
            '{EDIT_URL}',
            '{CANCEL_URL}',
            '{PAY_URL}',
		);
		$replace = array(
			$order->get_order_number(),
            $order->get_order_key(),
			'№' . $order->get_order_number(),
			$order->get_total(),
			strip_tags( $order->get_formatted_order_total( false, false ) ),
			$order->get_billing_email(),
			$order->get_billing_phone(),
			($s = $order->get_shipping_first_name()) ? $s : $order->get_billing_first_name(),
			($s = $order->get_shipping_last_name()) ? $s : $order->get_billing_last_name(),
			($s = $order->get_shipping_city()) ? $s : $order->get_shipping_city(),
			trim(
				(($s = $order->get_shipping_address_1()) ? $s : $order->get_billing_address_1())
				.' '
				.(($s = $order->get_shipping_address_2()) ? $s : $order->get_billing_address_2())
			),
			get_option( 'blogname' ),
			wc_get_order_status_name( $old_status ),
			wc_get_order_status_name( $new_status ),
			$order->get_customer_note(),
            $order->get_view_order_url(),
            $order->get_edit_order_url(),
            $order->get_cancel_order_url(),
            $order->get_checkout_payment_url(),
		);
		if ( strpos( $message, '{ITEMS}' ) !== false ) {

			$items     = $order->get_items();
			$items_str = '';
			foreach ( $items as $i ) {
				/* @var $i WC_Order_Item_Product */
                $name = $i['name'];
				if ( ( $_p = $i->get_product() ) && ( $sku = $_p->get_sku() ) ) {
					$name = $sku . ' ' . $name;
				}
				$items_str .= "\n" . $name . ': ' . $i['qty'] . 'x' . $order->get_item_total( $i ) . '=' . $order->get_line_total( $i );
			}
			$sh = $order->get_shipping_methods();
			foreach ( $sh as $i ) {
				$items_str .= "\n" . __( 'Shipping', 'woocommerce' ) . ': ' . $i['name'] . '=' . $i['cost'];
			}
			$items_str .= "\n";

			$search[]  = '{ITEMS}';
			$replace[] = strip_tags( $items_str );
		}

		if ( $meta = get_post_meta( $order->get_id() ) ) {
			foreach( $meta as $k => $v ) {
				$search[] = '{'.$k.'}';
				$replace[] = $v[0];
			}
		}


		foreach ( $replace as $k => $v ) {
			$replace[ $k ] = html_entity_decode( $v );
		}
		$message = str_replace( $search, $replace, $message );
		$message = preg_replace('/\s?\{[^}]+\}/','', $message ); // remove unknown {VAR}
		$message = trim( $message );
		$message = mb_substr( $message, 0, 670 );

		$this->send( $phone, $message );
        if ($v = get_option('smspilot_vendor_voice1')) {
            $this->send( $phone, $message, $v === 'F' ? 'GOLOS' : 'GOLOSM');
        }
	}
	public function send( $phone, $message, $sender = null) {
		if (!$phone || !$message) {
			return false;
		}
        $sender = $sender ?: get_option('smspilot_sender');
		$json = $this->_post( array(
			'send' => $message,
			'to' => $phone,
			'from' => $sender,
			'apikey' => get_option('smspilot_apikey'),
			'source_id' => 8,
			'format' => 'json'
		));
        if ( $json && ( $j = json_decode($json, false ) ) ) {
			if (isset($j->error)) {
				update_option('smspilot_last_error', gmdate('Y-m-d H:i:s').' - '.$phone.':'.$message.' - '.$j->error->description_ru);
				return $j->error->description_ru;
			}
			return (int) $j->send[0]->server_id;
		}
		return 'Ошибка JSON: '.$json;
	}
	private function _post( $data ) {
		$args = array(
			'timeout'  => 10,
		    'body'     => $data
		);
		$result = wp_remote_post( 'https://smspilot.ru/api.php', $args );

		if ( ! is_wp_error( $result ) ) {
			return wp_remote_retrieve_body( $result );
		}
		return false;
	}
}