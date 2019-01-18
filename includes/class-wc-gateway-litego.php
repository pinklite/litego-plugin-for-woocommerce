<?php
if (!defined('ABSPATH')) {
	exit;
}

class WC_Litego_Gateway extends WC_Payment_Gateway {
    /**
     * Litego checkout image
     *
     * @var string
     */
    public $litego_checkout_image;

    /**
     * API merchant id
     *
     * @var string
     */
    public $merchant_id;

    /**
     * API access secret key
     *
     * @var string
     */
    public $secret_key;

    /**
     * API access secret key
     *
     * @var string
     */
    public $auth_token;

    /**
     * API access secret key
     *
     * @var string
     */
    public $refresh_token;

    /**
     * Is test mode active?
     *
     * @var bool
     */
    public $testmode;


    /**
	 * Is logging enabled?
	 *
	 * @var bool
	 */
	public $logging;


	public $litegoApi;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id                   = 'litego';
        $this->method_title         = __('LiteGo.io Gateway for Lightning Network Payments', 'woo-litego');
		$this->method_description   = __('Have your customers generate a Lightning Network Payment Request. Your shop is notified as soon when the payment is received.', 'woo-litego');
		$this->has_fields           = true;

		// Load the form fields.
		$this->init_form_fields();

		// Get setting values.
		$this->title                  = $this->get_option('title');
		$this->description            = $this->get_option('description');
		$this->show_howto             = 'yes' === $this->get_option('show_howto');
		$this->howto            	  = $this->get_option('howto');
		$this->enabled                = $this->get_option('enabled');
        $this->testmode               = 'yes' === $this->get_option('testmode');
        $this->merchant_id            = $this->testmode ? $this->get_option( 'test_merchant_id' ) : $this->get_option( 'merchant_id' );
        $this->secret_key             = $this->testmode ? $this->get_option( 'test_secret_key' ) : $this->get_option( 'secret_key' );
		$this->logging                = 'yes' === $this->get_option('logging');
		$this->order_button_text      = __('Pay with LiteGo.io Lightning', 'woo-litego');
        $this->auth_token             = $this->get_option("auth_token");
        $this->refresh_token          = $this->get_option("refresh_token");

        //initialize litego API class
		$this->litegoApi = new WC_Litego_Api($this->is_testmode_on() ? "test" : "live");

		// Hooks.
        add_action('admin_notices', array( $this, 'admin_notices'));
		wp_register_style( 'wc_litego_css', WC_HTTPS::force_https_url(plugins_url('/assets/css/wc_litego.css', WC_LITEGO_PLUGIN_FILE)));

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_receipt_' . $this->id, array($this, 'show_payment'));
		add_action('woocommerce_thankyou_' . $this->id, array($this, 'show_payment'));
		add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);

		// listen for http requests on /?wc-api=wc_gateway_litego
		add_action('woocommerce_api_wc_gateway_litego', array($this, 'litego_webhook_endpoint'));

	}

    /**
     * Notify the user about plugin warnings
     */
    public function admin_notices() {
        if ('no' === $this->enabled) {
            return;
        }

        // SSL warning
        if (!wc_checkout_is_https()) {
            echo '<div class="notice notice-warning"><p>' . sprintf(__('LiteGo.io is enabled, but the checkout process is not secure. Make sure that you have a valid SSL certificate.', 'woo-litego')) . '</p></div>';
        }

        if (get_woocommerce_currency() !== 'BTC') {
            echo '<div class="error"><p>' . sprintf(__('LiteGo.io is disabled because it only supports the Bitcoin currency, and your store is currently using %s.', 'woo-litego'), get_woocommerce_currency()) . '</p></div>';
        }

        $setting_link = admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $this->id);
        if ($this->testmode) {
            echo sprintf(__('<div class="notice notice-warning"><p><strong>The Lightning LiteGo.io plugin is <a href="%s">in TEST mode</a>, payments with this gateway use TESTNET coins which hold no value!</strong></p></div>'), $setting_link);
        } else {
            if (empty($this->secret_key) && !(isset($_GET['page'], $_GET['section']) && 'wc-settings' === $_GET['page'] && 'litego' === $_GET['section'])) {
                echo "<div class='notice notice-warning'><p><strong>";
                echo sprintf(__('The Lightning LiteGo.io plugin is almost ready. To get started, <a href="%s">set your live secret api key</a>.', 'woo-litego'), $setting_link);
                echo "</strong></p></div>";
            }
        }
    }

	/**
	 * Add content to the WC emails.
	 *
	 * @access public
	 * @param WC_Order $order
	 * @param bool $sent_to_admin
	 * @param bool $plain_text
	 */
	public function email_instructions ($order, $sent_to_admin, $plain_text = false) {
		if (!$sent_to_admin && $this->id === $order->get_payment_method() && $order->has_status('pending')) {
			echo wpautop(wptexturize('Thanks for your trust.')) . PHP_EOL;
		}
	}

	/**
	 * Amount to pay, in satoshis
	 */
	public function get_amount_satoshi ($total) {
		return $total * 1000 * 100000;
	}

	/**
	 * Check if this gateway is enabled.
	 * 
	 * - SSL must be activated and shop currency is bitcoin
	 * - testing mode is ON and test_secret_key is set
	 *   OR
	 *   testing mode is off and live_secret_key is set
	 */
	public function is_available() {
		if ('yes' === $this->enabled) {
			if (get_woocommerce_currency() !== 'BTC') {
				return false;
			}
			if ($this->is_mainnet_on()) {
				return true;
			}
			if ($this->is_testmode_on()) {
				return true;
			}
		}
		return false;
	}
	
	private function is_mainnet_on() {
		return !empty($this->secret_key) && !$this->testmode;
	}
	
	private function is_testmode_on() {
		return $this->testmode && !empty($this->secret_key);
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields = require( dirname( __FILE__ ) . '/admin/litego-settings.php' );
	}

    /**
     * Reset auth_token/refresh_token if we change smth in the backend settings
     *
     * @return mixed
     */
	public function process_admin_options() {
        $this->init_settings();

        $this->settings['auth_token'] = "";
        $this->settings['refresh_token'] = "";

        $post_data = $this->get_post_data();

        foreach ( $this->get_form_fields() as $key => $field ) {
            if ( 'title' !== $this->get_field_type( $field ) ) {
                try {
                    $this->settings[ $key ] = $this->get_field_value( $key, $field, $post_data );
                } catch ( Exception $e ) {
                    $this->add_error( $e->getMessage() );
                }
            }
        }

        return update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'yes' );
    }

	/**
	 * Process the payment
	 */
	public function process_payment($order_id) {
		try {
			$order = wc_get_order($order_id);
			// Handle payment.
			if ($order->get_total() > 0) {
				// Make the request.
				$response = $this->create_charge($this->generate_payment_request($order));
				if (is_wp_error($response)) {
					throw new Exception((isset($localized_messages[$response->get_error_code()]) ? $localized_messages[$response->get_error_code()] : $response->get_error_message()));
				}
				// Process valid response.
				$this->process_response($response, $order);
			} else {
				// if order value is 0, we can bypass the payment step and directly complete the order
				$order->payment_complete();
			}
			// Remove cart.
			WC()->cart->empty_cart();
			return array('result' => 'success', 'redirect' => $this->get_return_url($order));
		} catch (Exception $e) {
			wc_add_notice($e->getMessage(), 'error' );
			$this->log(sprintf(__('Error: %s', 'woo-litego'), $e->getMessage()));
			return array('result' => 'fail', 'redirect' => '');
		}
	}

	/**
	 * Generate the request for the payment.
	 * @param  WC_Order $order
	 * @return array()
	 */
	protected function generate_payment_request($order) {
		$post_data                = array();
		$post_data['currency']    = strtolower($order->get_currency() ? $order->get_currency() : get_woocommerce_currency());
		$post_data['amount']      = $this->get_amount_satoshi($order->get_total(), $post_data['currency']);
		$post_data['description'] = sprintf(__('%s - Order %s', 'woo-litego' ), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES), $order->get_order_number());

		return $post_data;
	}

	public function process_response($response, $order) {
		// Store charge data
		update_post_meta($order->get_id(), '_litego_charge_id', $response['id']);
		update_post_meta($order->get_id(), '_litego_payment_hash', $response['payment_hash']);
		update_post_meta($order->get_id(), '_litego_payment_request', $response['payment_request']);
		update_post_meta($order->get_id(), '_litego_amount_satoshi', $response['amount_satoshi']);
		update_post_meta($order->get_id(), '_transaction_id', $response['id']);
		// add a note to the order
		$message = sprintf(__('Lightning payment is pending (charge: %s)', 'woo-litego'), $response['id']);
		$order->add_order_note($message);

		return $response;
	}

	/**
	 * Output for the order received page.
	 */
	public function show_payment($order_id) {
		if ($order_id) {
			$order = wc_get_order($order_id);
			wp_enqueue_style('wc_litego_css');
			$payment_request = get_post_meta($order->get_id(), '_litego_payment_request', true);
			$payment_hash = get_post_meta($order->get_id(), '_litego_payment_hash', true);
			$amount_satoshi = get_post_meta($order->get_id(), '_litego_amount_satoshi', true);
			if ($order->needs_payment()) {
				require __DIR__.'/litego_payment_details.php';
			} elseif ($order->has_status(array('processing', 'completed'))) {
				require __DIR__.'/litego_payment_success.php';
			}
		}
	}

	/**
	 * Retrieve an order from a Litego charge id.
	 */
	private function get_order_for_charge($charge_id) {
		global $wpdb;
		// Faster than get_posts()
		$order_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_litego_charge_id' AND meta_value = %s", $charge_id));
		if ($order_id > 0) {
			$order = wc_get_order($order_id);
			$this->log(sprintf(__('found order=%s', 'woo-litego'), json_encode($order)));
			return $order;
		}
		$this->log(sprintf(__('order could not be found for charge=%s', 'woo-litego'), $charge_id));
		return false;
	}

	/**
	 * Listens to POST and GET http requests, to either complete an order if Litego acknowledges the payment
	 * of an order, or to check if an order is completed.
	 */
	public function litego_webhook_endpoint() {
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$body = json_decode(file_get_contents('php://input'));
			if (isset($body->invoiceId)) {
				$charge_id = sanitize_text_field($body->invoiceId);
				$this->log(sprintf(__('received charge=%s payment notification', 'woo-litego'), $charge_id));
				$order = $this->get_order_for_charge($charge_id);

				if ($order !== false) {
					if ($order->has_status('pending')) {
						$verification = $this->get_charge($charge_id);
						if ($verification['paid']) {
							$order->payment_complete();
							$this->log(sprintf(__('order has been completed, paid by charge %s.', 'woo-litego'), $charge_id));
						} else {
							$this->log(sprintf(__('order=%s with charge=%s does not exist in Litego or has not been paid yet', 'woo-litego'), $order->get_id(), $charge_id));
						}
					} else {
						$this->log(sprintf(__('order=%s has already been paid', 'woo-litego'), $order->get_id()));
					}
				}
			} else {
				$this->log(sprintf(__('received incorrect notification that will be ignored', 'woo-litego')));
			}
		} else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			if (isset($_GET['id']) && isset($_GET['order_key'])) {
				$order_id = sanitize_text_field($_GET['id']);
				$order_key = sanitize_text_field($_GET['order_key']);
				$order_id_check = wc_get_order_id_by_order_key($order_key);
				if ($order_id_check != 0 && $order_id == $order_id_check) {
					$order = wc_get_order($order_id);
					if ($order->is_paid()) {
						wp_send_json(true);
					} else {
						wp_send_json(false);
					}
				} else {
					wp_send_json(false);
				}
			} else {
				wp_send_json(false);
			}
		}
		exit();
	}

	/**
     * Send a charge creation request to Litego
     *
     * @param array $request
     * @param string $api
     * @return array|WP_Error
     */
    public function create_charge($body) {
        //create a charge
            $result = $this->litegoApi->createCharge(
                $this->auth_token,
                $body['description'],
                $body['amount']
            );

        //reauthenticate first
        if($result['error'] and $result['error_name'] == "Forbidden") {
            $auth = $this->litegoApi->reauthenticate($this->refresh_token, $this->merchant_id, $this->secret_key);
            $this->updateTokens($auth);

            $result = $this->litegoApi->createCharge(
                $this->auth_token,
                $body['description'],
                $body['amount']
            );

        }

        if($result['error'])
        {
            $this->log("Error Response: " . print_r( $result['error_name'] .": ". $result['error_message'], true));
            if ($result['error_name'] == "SendCoinsError") {
                return new WP_Error('litego_error', __('Payment is too large, max payment allowed is 0.04294967 BTC', 'woo-litego'));
            } else {
                return new WP_Error('litego_error', __('There was a problem connecting to the payment gateway.', 'woo-litego'));
            }

        }

        return $result;
    }

    private function updateTokens($authData) {
        $options = get_option('woocommerce_litego_settings');
        $options['auth_token'] = $authData['auth_token'];
        $options['refresh_token'] = $authData['refresh_token'];
        update_option('woocommerce_litego_settings', $options);
        $this->auth_token = $authData['auth_token'];
        $this->refresh_token = $authData['refresh_token'];
    }

	/**
	 * Retrieve a charge from Litego
	 *
	 * @param array $request
	 * @param string $api
	 * @return array|WP_Error
	 */
	public function get_charge($charge_id) {
		$this->log($this->endpoint . ' GET charge=' . $charge_id);

        $result = $this->litegoApi->getCharge(
            $this->auth_token,
            $charge_id
        );

        //reauthenticate first
        if($result['error'] and $result['error_name'] == "Forbidden") {
            $auth = $this->litegoApi->reauthenticate($this->refresh_token, $this->merchant_id, $this->secret_key);
            $this->updateTokens($auth);

            $result = $this->litegoApi->getCharge(
                $this->auth_token,
                $charge_id
            );

        }

        if($result['error'])
        {
            $this->log("Error Response: " . print_r( $result['error_name'] .": ". $result['error_message'], true));
            return new WP_Error('litego_error', __('There was a problem connecting to the payment gateway.', 'woo-litego'));
        }

        return $result;
	}
	
	private function log($message) {
		if ($this->logging) WC_Litego::log($message);
	}

}
