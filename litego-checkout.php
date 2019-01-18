<?php
/**
 * Plugin Name: LiteGo.io plugin for Woocommerce
 * Plugin URI: https://github.com/litegoio/litego-plugin-for-woocommerce
 * Description: A gateway to pay with the Bitcoin Lightning Network through the Litego API.
 * Version: 1.1.0
 * Author: Anastasiia Sierykh (LiteGo.io)
 * Author URI: https://litego.io
 * Requires at least: 4.7
 * Tested up to: 5.0.3
 * License: Apache-2.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('WC_LITEGO_PLUGIN_FILE', __FILE__);

if ( ! class_exists( 'WC_Litego' ) ) {
    class WC_Litego {

        /**
         * @var Singleton The reference the *Singleton* instance of this class
         */
        private static $instance;

        /**
         * @var Reference to logging class.
         */
        private static $log;

        /**
         * Returns the *Singleton* instance of this class.
         *
         * @return Singleton The *Singleton* instance.
         */
        public static function get_instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Private clone method to prevent cloning of the instance of the
         * *Singleton* instance.
         *
         * @return void
         */
        private function __clone() {}

        /**
         * Private unserialize method to prevent unserializing of the *Singleton*
         * instance.
         *
         * @return void
         */
        private function __wakeup() {}

        /**
         * Protected constructor to prevent creating a new instance of the
         * *Singleton* via the `new` operator from outside of this class.
         */
        private function __construct() {
            add_action('plugins_loaded', array($this, 'init'));
        }

        /**
         * Init the plugin after plugins_loaded so environment variables are set.
         *
         * @since 1.0.0
         * @version 4.0.0
         */
        public function init() {
            //set locale
            load_plugin_textdomain( 'woo-litego', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

            if ( ! class_exists( 'WooCommerce' ) ) {
                echo '<div class="notice notice-error">' . sprintf( esc_html__( 'LiteGo.io requires WooCommerce to be installed and active. You can download %s here.', 'woocommerce-litego' ),
                        '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
                return;
            }

            // Init the gateway itself
            $this->init_gateways();

        }


        /**
         * Initialize the gateway. Called very early - in the context of the plugins_loaded action
         *
         * @since 1.0.0
         */
        public function init_gateways () {
            if (!class_exists('WC_Payment_Gateway')) {
                return;
            }

            //litego lib
            include_once( dirname( __FILE__ ) . '/includes/class-wc-litego-api.php' );

            include_once(dirname(__FILE__) . '/includes/class-wc-gateway-litego.php');

            add_filter('woocommerce_payment_gateways', array($this, 'add_gateways'));

            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_action_links'));
        }

        /**
         * Add the gateways to WooCommerce
         */
        public function add_gateways ( $methods ) {
            $methods[] = 'WC_Litego_Gateway';
            return $methods;
        }

        public function plugin_action_links( $links ) {
            $plugin_links = array(
                '<a href="admin.php?page=wc-settings&tab=checkout&section=litego">' . esc_html__( 'Settings', 'woo-litego' ) . '</a>',
                '<a href="mailto:litego@litego.io">' . esc_html__( 'Support', 'woo-litego' ) . '</a>'
            );
            return array_merge( $plugin_links, $links );
        }

        /**
         * Logging
         *
         * @param $message
         */
        public static function log($message) {
            if (empty(self::$log)) {
                self::$log = new WC_Logger();
            }

            self::$log->add('woo-litego', $message);

            if (defined('WP_DEBUG') && WP_DEBUG)
            {
                error_log('litego: ' . $message);
            }
        }
    }

    WC_Litego::get_instance();
}
?>
