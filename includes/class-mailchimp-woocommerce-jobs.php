<?php

class MailChimp_Woocommerce_Jobs
{
    protected static $booted = false;

    protected static $jobs = array();

    /**
     * MailChimp_Woocommerce_Jobs constructor.
     */
    public function __construct()
    {
        static::boot();
    }

    /**
     * @param $class
     * @return bool|mixed
     */
    public static function find($class)
    {
        foreach (static::$jobs as $job) {
            if ($class instanceof $job) return $job;
        }
        return false;
    }

    public static function boot()
    {
        if (static::$booted) return;

        static::$jobs = array(
            'mailchimp_woocommerce_process_coupons' => new MailChimp_WooCommerce_Process_Coupons(),
            'mailchimp_woocommerce_single_coupon' => new MailChimp_WooCommerce_SingleCoupon(),

            'mailchimp_woocommerce_process_orders' => new MailChimp_WooCommerce_Process_Orders(),
            'mailchimp_woocommerce_single_order' => new MailChimp_WooCommerce_Single_Order(),

            'mailchimp_woocommerce_process_products' => new MailChimp_WooCommerce_Process_Products(),
            'mailchimp_woocommerce_single_product' => new MailChimp_WooCommerce_Single_Product(),

            'mailchimp_woocommerce_subscriber' => new MailChimp_WooCommerce_User_Submit(),
            'mailchimp_woocommerce_cart_update' => new MailChimp_WooCommerce_Cart_Update(),
        );

        static::$booted = true;
    }

    /**
     * @return MailChimp_WooCommerce_Process_Coupons
     */
    public static function getProcessCouponsHandler()
    {
        return static::$jobs['mailchimp_woocommerce_process_coupons'];
    }

    /**
     * @return MailChimp_WooCommerce_SingleCoupon
     */
    public static function getProcessSingleCouponHandler()
    {
        return static::$jobs['mailchimp_woocommerce_single_coupon'];
    }

    /**
     * @return MailChimp_WooCommerce_Process_Orders
     */
    public static function getProcessOrdersHandler()
    {
        return static::$jobs['mailchimp_woocommerce_process_orders'];
    }

    /**
     * @return MailChimp_WooCommerce_Single_Order
     */
    public static function getProcessSingleOrderHandler()
    {
        return static::$jobs['mailchimp_woocommerce_single_order'];
    }

    /**
     * @return MailChimp_WooCommerce_Process_Products
     */
    public static function getProcessProductsHandler()
    {
        return static::$jobs['mailchimp_woocommerce_process_products'];
    }

    /**
     * @return MailChimp_WooCommerce_Single_Product
     */
    public static function getProcessSingleProductHandler()
    {
        return static::$jobs['mailchimp_woocommerce_single_product'];
    }

    /**
     * @return MailChimp_WooCommerce_Cart_Update
     */
    public static function getCartUpdateHandler()
    {
        return static::$jobs['mailchimp_woocommerce_cart_update'];
    }

    /**
     * @return MailChimp_WooCommerce_User_Submit
     */
    public static function getUserSubmitHandler()
    {
        return static::$jobs['mailchimp_woocommerce_subscriber'];
    }
}
