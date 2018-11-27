<div class="wc_litego_payment">
  <h2><?php echo __('Please proceed to the payment', 'woo-litego') ?> <img src="<?php echo WC_HTTPS::force_https_url(plugins_url('/assets/img/spinner.gif', WC_LITEGO_PLUGIN_FILE)) ?>" /></h2>
  <ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
      <li class="woocommerce-order-overview__order order">
          <p><?php echo __('Amount to pay with Lightning:', 'woo-litego') ?></p>
          <strong><?php echo esc_html(number_format($amount_satoshi / (1000 * 100 * 1000), 8)) ?> BTC</strong>
      </li>
      <li class="woocommerce-order-overview__order order"><p><?php echo __('Scan this QR code with a Lightning wallet:', 'woo-litego') ?></p>
          <div id="wc_litego_payrequest_qrcode"></div>
      </li>
      <li class="woocommerce-order-overview__order order"><p><?php echo __('Or use the raw invoice:', 'woo-litego') ?></p>
        <textarea class="wc_litego_payrequest"><?php echo esc_html($payment_request) ?></textarea>
      </li>
  </ul>
    <?php if ($this->show_howto) {
        echo '<div class="wc_litego_payment_howto"><strong>' . __('How do I pay this?', 'woo-litego') . '</strong>
        <div class="wc_litego_walletslist">' . ($this->howto) . '</div></div>';
    } ?>
</div>
<br/>
<script src="<?php echo WC_HTTPS::force_https_url(plugins_url('/assets/js/jquery.qrcode.min.js', WC_LITEGO_PLUGIN_FILE)) ?>"></script>
<script>
(function($) {

    $('#wc_litego_payrequest_qrcode').qrcode({
        width: 200,
        height: 200,
        text: "<?php echo $payment_request ?>"
    });
  
    const ping_interval = 5 * 1000;

    function wait_for_payment() {
        $.get('/?wc-api=WC_Gateway_Litego', { id: <?php echo $order->get_id() ?>, order_key: '<?php echo $order->get_order_key() ?>' })
        .success((code, state, res) => {
            if (res.responseJSON === true) {
                document.location = <?php echo json_encode($order->get_checkout_order_received_url()) ?>
            } else {
                setTimeout(wait_for_payment, ping_interval);
            }
        })
        .fail(res => {
            wait_for_payment();
        })
    }

    wait_for_payment();
})(jQuery);
</script>
