<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@mailchimp.com
 * Date: 7/15/16
 * Time: 11:42 AM
 */
class MailChimp_WooCommerce_Cart_Update extends WP_Async_Request
{
    protected $action = "mailchimp_woocommerce_cart_update";

    public $unique_id;
    public $email;
    public $previous_email;
    public $campaign_id;
    public $cart_data;
    public $ip_address;

    /**
     * @return bool
     */
    public function handle()
    {
        $this->unique_id = isset($_POST['unique_id']) ? $_POST['unique_id'] : null;
        $this->email = isset($_POST['email']) ? $_POST['email'] : null;
        $this->campaign_id = isset($_POST['campaign_id']) ? $_POST['campaign_id'] : null;
        $this->cart_data = isset($_POST['cart_data']) ? $_POST['cart_data'] : null;
        $this->ip_address = isset($_POST['ip_address']) ? $_POST['ip_address'] : null;

        if (($result = $this->process())) {
            mailchimp_log('ac.success', 'Added', array('api_response' => $result->toArray()));
        }

        return false;
    }

    /**
     * @return bool|MailChimp_WooCommerce_Cart
     */
    public function process()
    {
        try {
            $options = get_option('mailchimp-woocommerce', array());
            $store_id = mailchimp_get_store_id();

            if (!empty($store_id) && is_array($options) && isset($options['mailchimp_api_key'])) {

                $api = new MailChimp_WooCommerce_MailChimpApi($options['mailchimp_api_key']);

                // delete it and the add it back.
                $api->deleteCartByID($store_id, $this->unique_id);

                // if they emptied the cart ignore it.
                if (!is_array($this->cart_data) || empty($this->cart_data)) {
                    return false;
                }

                $checkout_url = wc_get_checkout_url();

                if (mailchimp_string_contains($checkout_url, '?')) {
                    $checkout_url .= '&mc_cart_id='.$this->unique_id;
                } else {
                    $checkout_url .= '?mc_cart_id='.$this->unique_id;
                }

                $customer = new MailChimp_WooCommerce_Customer();
                $customer->setId($this->unique_id);
                $customer->setEmailAddress($this->email);
                $customer->setOptInStatus(false);

                $cart = new MailChimp_WooCommerce_Cart();
                $cart->setId($this->unique_id);
                $cart->setCampaignID($this->campaign_id);
                $cart->setCheckoutUrl($checkout_url);
                $cart->setCurrencyCode(isset($options['store_currency_code']) ? $options['store_currency_code'] : 'USD');

                $cart->setCustomer($customer);

                $order_total = 0;
                $products = array();

                foreach ($this->cart_data as $hash => $item) {
                    try {
                        $line = $this->transformLineItem($hash, $item);
                        $cart->addItem($line);
                        $order_total += ($item['quantity'] * $line->getPrice());
                        $products[] = $line;
                    } catch (\Exception $e) {}
                }

                if (empty($products)) {
                    return false;
                }

                $cart->setOrderTotal($order_total);

                try {
                    // if the post is successful we're all good.
                    $api->addCart($store_id, $cart, false);

                    mailchimp_log('abandoned_cart.success', "email: {$customer->getEmailAddress()} :: checkout_url: $checkout_url");

                } catch (\Exception $e) {

                    mailchimp_error('abandoned_cart.error', "email: {$customer->getEmailAddress()} :: attempting product update :: {$e->getMessage()}");

                    // if we have an error it's most likely due to a product not being found.
                    // let's loop through each item, verify that we have the product or not.
                    // if not, we will add it.
                    foreach ($products as $item) {
                        /** @var MailChimp_WooCommerce_LineItem $item */
                        $transformer = new MailChimp_WooCommerce_Single_Product();
                        $transformer->product_id = $item->getProductID();

                        if (!$transformer->api()->getStoreProduct($store_id, $item->getProductId())) {
                            $transformer->process();
                        }
                    }

                    // if the post is successful we're all good.
                    $api->addCart($store_id, $cart, false);

                    mailchimp_log('abandoned_cart.success', "email: {$customer->getEmailAddress()}");
                }
            }

        } catch (\Exception $e) {
            update_option('mailchimp-woocommerce-cart-error', $e->getMessage());
            mailchimp_error('abandoned_cart.error', $e);
        }

        return false;
    }

    /**
     * @param string $hash
     * @param $item
     * @return MailChimp_WooCommerce_LineItem
     */
    protected function transformLineItem($hash, $item)
    {
        $product = wc_get_product($item['product_id']);
        $price = $product ? $product->get_price() : 0;

        $line = new MailChimp_WooCommerce_LineItem();
        $line->setId($hash);
        $line->setProductId($item['product_id']);

        if (isset($item['variation_id']) && $item['variation_id'] > 0) {
            $line->setProductVariantId($item['variation_id']);
        } else {
            $line->setProductVariantId($item['product_id']);
        }

        $line->setQuantity($item['quantity']);
        $line->setPrice($price);

        return $line;
    }
}
