# LiteGo.io plugin for Woocommerce

LiteGo.io plugin for Woocommerce plugin is a payment gateway for the Litego API, which enables payments with the Bitcoin Ligthning Network.
This plugin also offers a test mode to make payments with TESTNET coins.

## Installation

1. Download the latest release of the plugin
2. Then install it on your wordpress project. Note: you should have wordpress shop plugin Woocommerce 3+ and HTTPS enabled.
3. Once installed, activate it.

## Configuration

1. Please create an account on Litego https://litego.io
2. Copy the account API merchant ID and secret key (Account > Settings > API Keys).
3. Go to the plugin settings page (Woocommerce > Settings > Payments > Litego Gateway for Lightning) in your store admin page and save your Litego API merchant ID and secret key (Live Secret Key field).
4. Get the Shop Webhook URL from plugin and save it to the Webhook tab (Account > Settings > Web Hooks) of your Litego account. This will enable payment notifications on your store.

## Test on the sandbox
1. Use TESTNET mode in your Litego account.
2. Copy the account API merchant ID and secret key (Account > Settings > API Keys) from your Litego account in TESTNET mode.
3. Enable the Test mode in the plugin settings page and enter the sandbox API parameters: merchant ID and secret key (Test mode Secret Key field).
4. Save the Shop Webhook URL in the Webhook settings (Account > Settings > Web Hooks) in TESTNET mode of your Litego account.
Note: Don't use TESTNET mode parameters in a live store with real products because payments will be held in Testnet coins.
