<?php
if (!defined('ABSPATH')) {
	exit;
}

return apply_filters('wc_litego_settings',
	array(
		'enabled' => array(
			'title'       => __('Enable/Disable', 'woo-litego'),
			'label'       => __('Enable LiteGo.io', 'woo-litego'),
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no'
		),
        'title' => array(
            'title'       => __('Title', 'woo-litego'),
            'type'        => 'text',
            'description' => __('This controls the name of the LiteGo.io gateway which the user sees during checkout.', 'woo-litego'),
            'default'     => __('Lightning Payment (LiteGo.io)', 'woo-litego'),
            'desc_tip'    => true,
        ),
        'description' => array(
            'title'       => __('Description', 'woo-litego'),
            'type'        => 'text',
            'description' => __('This controls the description of the gateway which the user sees during checkout.', 'woo-litego'),
            'default'     => __('Pay instantly with the Bitcoin Lightning Network.', 'woo-litego'),
            'desc_tip'    => true,
        ),
        'webhook' => array(
            'title'       => __('Your shop webhook', 'woocommerce-strike'),
            'type'        => 'text',
            'custom_attributes' => array('readonly' => 'readonly'),
            'description' => __('Save this url in your <a href="https://litego.io/settings#webhook" target="_blank">LiteGo.io settings</a> so that LiteGo.io can notify your shop when a payment is received.', 'woo-litego'),
            'default'     => __(home_url('/') . '?wc-api=WC_Gateway_Litego', 'woo-litego'),
            'desc_tip'    => false,
        ),
        'merchant_id' => array(
            'title'       => __('Mainnet Merchant ID', 'woo-litego'),
            'type'        => 'text',
            'description' => __('Merchant ID for LiteGo.io environment. It is provided by <a target="_blank" href="https://litego.io/settings#api">LiteGo.io</a>  (in User > Settings > API KEY).', 'woo-litego'),
            'default'     => '',
            'desc_tip'    => false,
        ),
        'secret_key' => array(
            'title'       => __('Mainnet Secret Key', 'woo-litego'),
            'type'        => 'text',
            'description' => __('This secret key is provided by <a target="_blank" href="https://litego.io/settings#api">LiteGo.io</a> (in User > Settings > API KEY).', 'woo-litego'),
            'default'     => '',
            'desc_tip'    => false,
        ),
        'testmode' => array(
            'title'       => __('Enable sandbox mode', 'woo-litego'),
            'label'       => __('Payments will be made over Testnet, coins hold no value.', 'woo-litego'),
            'type'        => 'checkbox',
            'description' => __('If you enable test mode, you will interact with the <strong>Testnet</strong> LiteGo.io API, and payments will be made for Testnet. Don\'t use this in production!!', 'woo-litego'),
            'default'     => 'no'
        ),
        'test_merchant_id' => array(
            'title'       => __('Sandbox Merchant ID', 'woo-litego'),
            'type'        => 'text',
            'description' => __('Merchant ID for LiteGo.io  <strong>Testnet</strong> environment. It is provided by <a target="_blank" href="https://litego.io/settings#api">LiteGo.io</a>  (in User > Settings > API KEY).', 'woo-litego'),
            'default'     => '',
            'desc_tip'    => false,
        ),
        'test_secret_key' => array(
            'title'       => __('Sandbox Secret Key', 'woo-litego'),
            'type'        => 'text',
            'description' => __('Use the secret key provided by the  <strong>Testnet</strong> Litego environment', 'woo-litego'),
            'default'     => '',
            'desc_tip'    => false,
        ),
        'checkout_page' => array(
            'title' 			=> __('Checkout page labels', 'woo-litego'),
            'type'  			=> 'title',
            'description' => __('This section controls the labels that are displayed to your customers.', 'woo-litego')
        ),
        'logging' => array(
			'title'       => __('Logging', 'woo-litego'),
			'label'       => __('Log debug messages', 'woo-litego'),
			'type'        => 'checkbox',
			'description' => __('Save debug messages to the WooCommerce System Status log.', 'woo-litego'),
			'default'     => 'no',
			'desc_tip'    => true,
		),
		'show_howto' => array(
			'title'       => __('Show Help', 'woo-litego'),
			'label'       => __('Show `How To Pay with Lightning` help message.', 'woo-litego'),
			'type'        => 'checkbox',
			'description' => __('Display a help message to the user when he\'s paying with Lightning.', 'woo-litego'),
			'default'     => 'yes',
			'desc_tip'    => true,
		),
		'howto' => array(
			'title'       => __('Help message', 'woo-litego'),
			'type'        => 'textarea',
			'description' => __('This controls the <em>`How To Pay with Lightning`</em> help message which the user sees during checkout.', 'woo-litego'),
			'default'     => __('There are several lightning apps on the market, including app for mobile phones.<br />For android, you can use <a href="https://play.google.com/store/apps/details?id=fr.acinq.eclair.wallet.mainnet2">Eclair Wallet</a> or <a href="https://play.google.com/store/apps/details?id=com.lightning.walletapp">Anton Kumaigorodski\'s Lightning Wallet</a>.', 'woo-litego'),
			'desc_tip'    => false,
		),
	)
);
