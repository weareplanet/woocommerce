<?php
/**
 * Plugin Name: WeArePlanet
 * Author: Planet Merchant Services Ltd
 * Text Domain: weareplanet
 * Domain Path: /languages/
 *
 * WeArePlanet
 * This plugin will add support for all WeArePlanet payments methods and connect the WeArePlanet servers to your WooCommerce webshop (https://www.weareplanet.com/).
 *
 * @category Class
 * @package  WeArePlanet
 * @author   Planet Merchant Services Ltd (https://www.weareplanet.com)
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WP_CLI' ) && ! class_exists( 'WC_WeArePlanet_Commands' ) ) {

    /**
     * Class WC_WeArePlanet_Commands.
     * This class contains custom commands for WeArePlanet.
     *
     * @class WC_WeArePlanet_Commands
     */
    class WC_WeArePlanet_Commands {

        /**
         * Register commands.
         */
        public static function init() {
            WP_CLI::add_command(
                'weareplanet settings init',
                array(
                    __CLASS__,
                    'settings_init'
                )
            );
            WP_CLI::add_command(
                'weareplanet webhooks install',
                array(
                    __CLASS__,
                    'webhooks_install'
                )
            );
            WP_CLI::add_command(
                'weareplanet payment-methods sync',
                array(
                    __CLASS__,
                    'payment_methods_sync'
                )
            );
        }

        /**
         * Initialize WeArePlanet settings.
         * It doesn't reset settings to default, it sets default settings if they haven't been initialized yet.
         *
         * ## EXAMPLE
         *
         *     $ wp weareplanet settings init
         *
         * @param array $args WP-CLI positional arguments.
         * @param array $assoc_args WP-CLI associative arguments.
         */
        public static function settings_init( $args, $assoc_args ) {
            try {
                $default_settings = WC_WeArePlanet_Helper::instance()->get_default_settings();
                foreach ( $default_settings as $setting => $value ) {
                    $current_setting = get_option( $setting, false );
                    if ( $current_setting === false ) {
                        update_option( $setting, $value );
                    }
                }
                WP_CLI::success( "Settings initialized." );
            } catch ( \Exception $e ) {
                WooCommerce_WeArePlanet::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
                WP_CLI::error( "Failed to initialize settings: " . $e->getMessage() );
            }
        }

        /**
         * Create webhook URL and webhook listeners in the portal for WeArePlanet.
         *
         * ## EXAMPLE
         *
         *     $ wp weareplanet webhooks install
         *
         * @param array $args WP-CLI positional arguments.
         * @param array $assoc_args WP-CLI associative arguments.
         */
        public static function webhooks_install( $args, $assoc_args ) {
            try {
                WC_WeArePlanet_Helper::instance()->reset_api_client();
                WC_WeArePlanet_Service_Webhook::instance()->install();
                WP_CLI::success( "Webhooks installed." );
            } catch ( \Exception $e ) {
                WooCommerce_WeArePlanet::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
                WP_CLI::error( "Failed to install webhooks: " . $e->getMessage() );
            }
        }

        /**
         * Synchronizes payment methods in the WeArePlanet from the portal.
         *
         * ## EXAMPLE
         *
         *     $ wp weareplanet payment-methods sync
         *
         * @param array $args WP-CLI positional arguments.
         * @param array $assoc_args WP-CLI associative arguments.
         */
        public static function payment_methods_sync( $args, $assoc_args ) {
            try {
                WC_WeArePlanet_Helper::instance()->reset_api_client();
                WC_WeArePlanet_Service_Method_Configuration::instance()->synchronize();
                WC_WeArePlanet_Helper::instance()->delete_provider_transients();
                WP_CLI::success( "Payment methods synchronized." );
            } catch ( \Exception $e ) {
                WooCommerce_WeArePlanet::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
                WP_CLI::error( "Failed to synchronize payment methods: " . $e->getMessage() );
            }
        }
    }
}

WC_WeArePlanet_Commands::init();