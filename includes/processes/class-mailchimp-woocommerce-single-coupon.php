<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 10/6/17
 * Time: 11:14 AM
 */
class MailChimp_WooCommerce_SingleCoupon extends WP_Async_Request
{
    protected $action = "mailchimp_woocommerce_single_coupon";

    /**
     * @return null
     */
    public function handle()
    {
        $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : null;

        try {

            if (empty($post_id)) {
                mailchimp_error('promo_code.failure', "could not process coupon {$post_id}");
                return;
            }

            $api = mailchimp_get_api();
            $store_id = mailchimp_get_store_id();

            $transformer = new MailChimp_WooCommerce_Transform_Coupons();
            $code = $transformer->transform($post_id);

            $api->addPromoRule($store_id, $code->getAttachedPromoRule(), true);
            $api->addPromoCodeForRule($store_id, $code->getAttachedPromoRule(), $code, true);

            mailchimp_log('promo_code.update', "updated promo code {$code->getCode()}");
        } catch (\Exception $e) {
            $promo_code = isset($code) ? "code {$code->getCode()}" : "id {$post_id}";
            mailchimp_error('promo_code.error', mailchimp_error_trace($e, "error updating promo {$promo_code}"));
        }
    }
}
