<?php

if( stristr( $_SERVER['REQUEST_URI'], '/edd-helpscout-api/customer_info' ) !== false ) {

    /**
     * Class EDD_HelpScout_API_Endpoint
     *
     * Creates an endpoint for EDD HelpScout requests
     * Halves the memory consumption and runtime of all remote requests
     */
    class EDD_HelpScout_API_Endpoint {

        public function __construct() {

            // set constant to use later on
            define( 'EDD_SL_DOING_API', true );

            // disable cronjobs for this request
            define('DISABLE_WP_CRON', true);

            // prevent session query caused by EDD if not set already
            if( ! defined( 'EDD_USE_PHP_SESSIONS' ) ) {
                define( 'EDD_USE_PHP_SESSIONS', true );
            }

            // filter active plugins
            add_filter( 'option_active_plugins', array( $this, 'filter_active_plugins' ) );

            // disable loading of any widgets
            add_filter( 'after_setup_theme', array( $this, 'disable_widgets' ) );

            // throw error if a result hasn't been returned on init:99
            add_action( 'init', array( $this, 'throw_api_error' ), 99 );

            // Customizations
            add_action( 'edd_helpscout_after_order_list_item', array( $this, 'extend_orders_view' ), 10, 2 );
            add_action( 'edd_helpscout_after_subscription_list_item', array( $this, 'extend_subscriptions_view' ) );
        }

        /**
         * Extend orders view
         * - Display Quaderno invoice link
         */
        public function extend_orders_view( $order, $helpscout_data ) {
            //echo '<pre>'; print_r( $order ); echo '</pre>';

            if ( ! isset( $order['id'] ) )
                return;

            $payment = new EDD_Payment( $order['id'] );
            $quaderno_url = $payment->get_meta( '_quaderno_url' );
            ob_start();
            ?>
            <li class="c-sb-list-item">
                <span class="c-sb-list-item__text t-tx-charcoal-500" style="font-size:12px;">
                    <i class="icon-doc" style="margin-top: -4px;"></i><?php esc_html_e( 'Quaderno Invoice', 'edd-quaderno' ); ?>:&nbsp;
                    <?php if ( ! empty( $quaderno_url ) ) { ?>
                        <a href="<?php echo esc_url( $quaderno_url ); ?>" target="_blank" rel="nofollow"><?php esc_html_e( 'View', 'edd-quaderno' ); ?></a>
                    <?php } else { ?>
                        N/A
                    <?php } ?>
                </span>
            </li>
            <?php
            echo ob_get_clean();
        }

        /**
         * Subscriptions view
         * - Display billing cycle
         */
        public function extend_subscriptions_view( $subscription ) {
            ob_start();
            //echo '<pre>'; print_r( $subscription ); echo '</pre>';
            //echo '<pre>'; print_r( $EDD_Subscription ); echo '</pre>';
            $EDD_Subscription = new EDD_Subscription( $subscription['id'] );

            $currency_code = edd_get_payment_currency_code( $EDD_Subscription->parent_payment_id );
            $frequency     = EDD_Recurring()->get_pretty_subscription_frequency( $EDD_Subscription->period );
            $initial       = edd_currency_filter( edd_format_amount( $EDD_Subscription->initial_amount ), $currency_code );
            $billing       = edd_currency_filter( edd_format_amount( $EDD_Subscription->recurring_amount ), $currency_code ) . ' / ' . $frequency;
            ?>
            <li class="c-sb-list-item">
                <span class="c-sb-list-item__text t-tx-charcoal-300" style="font-size:11px;"><?php _e( 'Billing Cycle:', 'edd-recurring' ); ?> <?php echo sprintf(
                    /* translators: %1$s Initial subscription amount. %2$s Billing cycle amount and cycle length */
                        _x( '%1$s then %2$s', 'edd-recurring' ), esc_html__( $initial ), esc_html__( $billing )
                    ) ?></span>
            </li>
            <?php
            echo ob_get_clean();
        }

        /**
         * Disable all widgets
         */
        public function disable_widgets() {
            remove_all_actions( 'widgets_init' );
        }

        /**
         * For all requests to the EDD HelpScout API, we only need to load necessary plugins
         *
         * @param $active_plugins
         *
         * @return array
         */
        public function filter_active_plugins( $active_plugins ) {
            $active_plugins = array(
                'easy-digital-downloads/easy-digital-downloads.php',
                'easy-digital-downloads-pro/easy-digital-downloads.php',
                'edd-software-licensing/edd-software-licenses.php',
                'edd-all-access/edd-all-access.php',
                'edd-helpscout/edd-helpscout.php',
                'edd-recurring/edd-recurring.php',
                'edd-sl-variable-price-limits/edd-sl-variable-price-limits.php'
            );

            return $active_plugins;
        }

        /**
         * By now, the EDD SL API should have sent a response and died.
         *
         * If the request reaches this hook callback, die.
         */
        public function throw_api_error() {

            $this->send_header( '400 Bad Request' );

            $this->send_response(
                array(
                    'status' => 'error',
                    'message' => 'Something went wrong.'
                )
            );
        }


        /**
         * @param string $header
         *
         * Send a header
         */
        private function send_header( $header ) {
            header( $_SERVER['SERVER_PROTOCOL'] . ' ' . $header );
        }

        /**
         * Send a JSON response
         *
         * @param array $response
         */
        private function send_response( $response ) {
            // set correct Content Type header
            header( 'Content-Type: application/json' );
            echo json_encode( $response );
            die();
        }
    }

    new EDD_HelpScout_API_Endpoint;

    /**
     * Override get_current_user_info to prevent an user query
     *
     * @return bool
     */
    function get_currentuserinfo() {
        wp_set_current_user( 0 );
        return false;
    }

}
