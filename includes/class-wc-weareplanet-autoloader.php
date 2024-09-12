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

/**
 * Class WC_WeArePlanet_Autoloader.
 * This is the autoloader for WeArePlanet classes.
 *
 * @class WC_WeArePlanet_Autoloader
 */
class WC_WeArePlanet_Autoloader {

	/**
	 * Path to the includes directory.
	 *
	 * @var string
	 */
	private $include_path = '';

	/**
	 * The Constructor.
	 */
	public function __construct() {
		spl_autoload_register(
			array(
				$this,
				'autoload',
			)
		);
		$this->include_path = WC_WEAREPLANET_ABSPATH . 'includes/';
	}

	/**
	 * Take a class name and turn it into a file name.
	 *
	 * @param  string $class_file class.
	 * @return string
	 */
	private function get_file_name_from_class( $class_file ) {
		$class = preg_replace( '/(?<!^)[A-Z]/', '-$0', $class_file );
		return 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
	}

	/**
	 * Include a class file.
	 *
	 * @param  string $path path.
	 * @return bool successful or not
	 */
	private function load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			include_once $path;
			return true;
		}
		return false;
	}

	/**
	 * Auto-load WC WeArePlanet classes on demand to reduce memory consumption.
	 *
	 * @param string $class_file class.
	 */
	public function autoload( $class_file ) {
		$class = strtolower( $class_file );

		if ( 0 !== strpos( $class, 'wc_weareplanet' ) ) {
			return;
		}

		$file = $this->get_file_name_from_class( $class );
		$path = '';

		if ( strpos( $class, 'wc_weareplanet_service' ) === 0 ) {
			$path = $this->include_path . 'service/';
		} elseif ( strpos( $class, 'wc_weareplanet_entity' ) === 0 ) {
			$path = $this->include_path . 'entity/';
		} elseif ( strpos( $class, 'wc_weareplanet_provider' ) === 0 ) {
			$path = $this->include_path . 'provider/';
		} elseif ( strpos( $class, 'wc_weareplanet_webhook' ) === 0 ) {
			if ( strpos( $class, 'strategy' ) !== false ) {
				$path = $this->include_path . 'webhook/strategies/';
			} else {
				$path = $this->include_path . 'webhook/';
			}
		} elseif ( strpos( $class, 'wc_weareplanet_exception' ) === 0 ) {
			$path = $this->include_path . 'exception/';
		} elseif ( strpos( $class, 'wc_weareplanet_admin' ) === 0 ) {
			$path = $this->include_path . 'admin/';
		}

		if ( empty( $path ) || ! $this->load_file( $path . $file ) ) {
			$this->load_file( $this->include_path . $file );
		}
	}
}

new WC_WeArePlanet_Autoloader();
