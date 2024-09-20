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
 * This exception indicating an error with the transaction amount
 */
class WC_WeArePlanet_Exception_Invalid_Transaction_Amount extends Exception {


	/**
	 * Item total.
	 *
	 * @var mixed $item_total item total.
	 */
	private $item_total;

	/**
	 * Order total.
	 *
	 * @var mixed $order_total order total.
	 */
	private $order_total;

	/**
	 * Construct.
	 *
	 * @param mixed $item_total item total.
	 * @param mixed $order_total order total.
	 */
	public function __construct( $item_total, $order_total ) {
		parent::__construct( "The item total '" . $item_total . "' does not match the order total '" . $order_total . "'." );
		$this->item_total = $item_total;
		$this->order_total = $order_total;
	}

	/**
	 * Get item total.
	 *
	 * @return mixed
	 */
	public function get_item_total() {
		return $this->item_total;
	}

	/**
	 * Get order total.
	 *
	 * @return mixed
	 */
	public function get_order_total() {
		return $this->order_total;
	}
}
