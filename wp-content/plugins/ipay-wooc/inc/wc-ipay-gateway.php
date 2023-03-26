<?php
class WC_Ipay_Gateway extends WC_Payment_Gateway {

	public $data_pay;

	public $setting_pay;

	/**
	 * Class constructor, more about it in Step 3
	 */
	public function __construct() {
		require_once plugin_dir_path(__DIR__).'ipay/IpaySdk.php';

		$this->id = 'ipay_gate_maks'; // ID платёжног шлюза
		$this->icon = ''; // URL иконки, которая будет отображаться на странице оформления заказа рядом с этим методом оплаты
		$this->has_fields = false; // если нужна собственная форма ввода полей карты
		$this->method_title = 'Платёжный шлюз от Миши';
		$this->method_description = 'Описание платёжного шлюза от Миши'; // будет отображаться в админке
	 
		// платёжные плагины могут поддерживать подписки, сохранённые карты, возвраты
		// но в пределах этого урока начнём с простых платежей, хотя в виде ниже будет чуть подробнее и о другом
		$this->supports = array(
			'products'
		);
	 
		// тут хранятся все поля настроек
		$this->init_form_fields();
	 
		// инициализируем настройки
		$this->init_settings();

		// название шлюза
		$this->title = $this->get_option( 'title' );
		// описание
		$this->description = $this->get_option( 'description' );
		// включен или выключен
		$this->enabled = $this->get_option( 'enabled' );
		// работает в тестовом режиме (sandbox) или нет
		$this->testmode = 'yes' === $this->get_option( 'testmode' );
		// и естественно отдельные ключи для тестового и рабочего режима шлюза
		$this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
		$this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );

		// Хук для сохранения всех настроек, как видите, можно еще создать собственный метод process_admin_options() и закастомить всё
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	 
		// Если будет генерировать токен из данных карты, то по-любому нужно будет подключать какой-то JS
		// add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
	 
		// ну и хук тоже можете тут зарегистрировать. Хук обратного вызова
		// add_action( 'woocommerce_api_ipay-call', array( $this, 'ipay_webhook' ) );
	}

/**
	 * Plugin options, we deal with it in Step 3 too
	 */
	public function init_form_fields(){
		$this->form_fields = array(
			'enabled' => array(
				'title'       => 'Включен/Выключен',
				'label'       => 'Включить Мишин платёжный плагин',
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title' => array(
				'title'       => 'Заголовок',
				'type'        => 'text',
				'description' => 'Это то, что пользователь увидит как название метода оплаты на странице оформления заказа.',
				'default'     => 'Оплатить картой',
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => 'Описание',
				'type'        => 'textarea',
				'description' => 'Описание этого метода оплаты, которое будет отображаться пользователю на странице оформления заказа.',
				'default'     => 'Оплатите при помощи карты легко и быстро.',
			),
			'testmode' => array(
				'title'       => 'Тестовый режим',
				'label'       => 'Включить тестовый режим',
				'type'        => 'checkbox',
				'description' => 'Хотите сначала протестировать с тестовыми ключами API?',
				'default'     => 'yes',
				'desc_tip'    => true,
			),
			'mch_id' => array(
				'title'       => 'Идентификатор мерчанта',
				'type'        => 'text'
			),
			'sign_key' => array(
				'title'       => 'Ключ мерчанта',
				'type'        => 'text'
			),
			'service_data_receiver' => array(
				'title'       => 'Получатель',
				'type'        => 'text',
			),
			'service_data_ZKPO' => array(
				'title'       => 'ЕДРПО',
				'type'        => 'text',
			),
			'service_data_bank' => array(
				'title'       => 'Название банка',
				'type'        => 'text',
			),
			'service_data_acc' => array(
				'title'       => 'IBAN',
				'type'        => 'text',
			),
			'service_data_text' => array(
				'title'       => 'Назначение',
				'type'        => 'text',
			),
			'success_page' => array(
				'title'       => 'Страница успеха',
				'type'        => 'text',
				'default'     => home_url('/').'checkout/order-received/',
			),
			'error_page' => array(
				'title'       => 'Страница ошибки',
				'type'        => 'text',
				'default'     => home_url('/').'error/',
			),
			'pey_title' => array(
				'title'       => 'Шапка платежа',
				'type'        => 'text',
				'default'     => 'Платіж абонента %s',
				'desc_tip'    => true,
				'description' => '%s - заменится на имя покупателя',
			),

			'cron_time' => array(
				'title'       => 'Время обновления в минутах',
				'type'        => 'text',
				'default'     => 60,
			),
		);
	}

/**
 * You will need it if you want your custom credit card form, Step 4 is about it
 */
	// public function payment_fields() {

			 
	// }

/*
 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
 */
	// public function payment_scripts() {


	// }

	/*
	 * Fields validation, more in Step 5
 	*/
	// public function validate_fields() {


	// }

	/*
 * We're processing the payments here, everything about it is in Step 5
 */
	public function process_payment( $order_id ) {

		global $woocommerce;
		$order = new WC_Order( $order_id );
		$pay = $this->this_pay_method($order);

		if($pay['status'] == 'error') {
			wc_add_notice( __('Payment error: ', ''). $pay['message'], 'error' );
			return;
		}

		if($pay['status'] == 'success') {
			// Mark as on-hold (we're awaiting the cheque)
			$order->update_status('on-hold');
			if(!empty($pay['pid'])) {
				$order->update_meta_data( 'pid', $pay['pid']);
				$order->save_meta_data();

				$order->add_order_note(__('Платеж iPay: '.$pay['pid']));
			}

			// Remove cart
			$woocommerce->cart->empty_cart();			

			// Return thankyou redirect 
			return array(
			   'result' => 'success',
			   'redirect' => $pay['url'],
			);
		}
	}

	public function this_pay_method($order) {

		// require_once plugin_dir_path(__DIR__).'ipay/IpaySdk.php';

		if($order->get_currency() != 'USD') {
			return ['status'=>'error', 'message'=>'error currency'];
		}

		$order_info = $this->getOrderInfo($order);

		$this->setDataPay();

		$ipay = new payment\ipay\IpaySdk($this->setting_pay);
		$ipay->setInfo($this->data_pay, $order_info);
		$payment_json = json_encode($ipay->response());
		$payment = (json_decode($payment_json, true));
		// print_r($payment); exit;
		if($payment['status'] == 1) {

			return ['status'=>'success', 'url'=>$payment['url'], 'pid'=>$payment['pid']];
		}
		
		
	}

	public function setDataPay() {

		$this->setting_pay = [
			'mch_id' => $this->get_option( 'mch_id' ),
			'sign_key' => $this->get_option( 'sign_key' ),
			'testmode' => $this->get_option( 'testmode' ),
			'callback_url' => [
				'success_page' => $this->get_option( 'success_page' ),
				'error_page' => $this->get_option( 'error_page' ),
			]
		];

		$this->data_pay = [
			'service_data_receiver' => $this->get_option( 'service_data_receiver' ),
			'service_data_ZKPO' => $this->get_option( 'service_data_ZKPO' ),
			'service_data_bank' => $this->get_option( 'service_data_bank' ),
			'service_data_acc' => $this->get_option( 'service_data_acc' ),
			'service_data_text' => $this->get_option( 'service_data_text' ),
		];
	}

	public function getOrderInfo($order) {

		$address = $order->get_address();

		return [
			'OrderID' => $order->get_id(),
			'PersonID' => $order->get_user_id() ?? '',
			'amount' => $order->get_total(),
			'dogovor' => $order->get_id(),
			'pey_title' => $this->get_option('pey_title') ?? '', 
			'name' => $address['first_name'] ?? '',
			'last_name' => $address['last_name'] ?? '',
			'phone' => $address['phone'] ?? '',
			'email' => $address['email'] ?? ''
		];
	}

/*
 * In case you need a webhook, like PayPal IPN etc
 */
	public function ipay_webhook() {
		echo 123;
		exit;
	}

}