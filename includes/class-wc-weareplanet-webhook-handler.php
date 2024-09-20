<?php
/**
 *
 * WC_WeArePlanet_Unique_Id Class
 *
 * WeArePlanet
 * This plugin will add support for all WeArePlanet payments methods and connect the WeArePlanet servers to your WooCommerce webshop (https://www.weareplanet.com/).
 *
 * @category Class
 * @package  WeArePlanet
 * @author   Planet Merchant Services Ltd (https://www.weareplanet.com)
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
/**
 * Class WC_WeArePlanet_Unique_Id.
 *
 * @class WC_WeArePlanet_Unique_Id
 */
/**
 * This class handles the webhooks of WeArePlanet
 */
class WC_WeArePlanet_Webhook_Handler {

	/**
	 * Initialise
	 */
	public static function init() {
		add_action(
			'woocommerce_api_weareplanet_webhook',
			array(
				__CLASS__,
				'process',
			)
		);
	}

	/**
	 * Handle webhook errors.
	 *
	 * @param mixed $errno error number.
	 * @param mixed $errstr error string.
	 * @param mixed $errfile error file.
	 * @param mixed $errline error line.
	 *
	 * @throws ErrorException ErrorException.
	 */
	public static function handle_webhook_errors( $errno, $errstr, $errfile, $errline ) {
		$fatal = E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_PARSE;
		if ( $errno & $fatal ) {
			throw new ErrorException( $errstr, $errno, E_ERROR, $errfile, $errline );
		}
		return false;
	}

	/**
	 * Process the webhook call.
	 */
	public static function process() {
		$webhook_service = WC_WeArePlanet_Service_Webhook::instance();

		// We set the status to 500, so if we encounter a state where the process crashes the webhook is marked as failed.
		header( 'HTTP/1.1 500 Internal Server Error' );
		$request_body = trim( file_get_contents( 'php://input' ) );
		set_error_handler( array( __CLASS__, 'handle_webhook_errors' ) );
		try {
			$request = new WC_WeArePlanet_Webhook_Request( json_decode( $request_body ) );
			$webhook_model = $webhook_service->get_webhook_entity_for_id( $request->get_listener_entity_id() );
			if ( null === $webhook_model ) {
				WooCommerce_WeArePlanet::instance()->log( sprintf( 'Could not retrieve webhook model for listener entity id: %s', $request->get_listener_entity_id() ), WC_Log_Levels::ERROR );
			    	// phpcs:ignore
			    	echo esc_html__( sprintf( 'Could not retrieve webhook model for listener entity id: %s', $request->get_listener_entity_id() ) );
				exit();

			}
			$webhook_handler_class_name = $webhook_model->get_handler_class_name();
			$webhook_handler = $webhook_handler_class_name::instance();
			$webhook_handler->process( $request );
			header( 'HTTP/1.1 200 OK' );
		} catch ( Exception $e ) {
			WooCommerce_WeArePlanet::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
		    	// phpcs:ignore
			echo esc_textarea($e->getMessage());
			exit();
		}
		exit();
	}
}
WC_WeArePlanet_Webhook_Handler::init();
