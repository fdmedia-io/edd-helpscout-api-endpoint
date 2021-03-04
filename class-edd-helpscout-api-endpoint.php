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
