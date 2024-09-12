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
 * Class WC_WeArePlanet_Order_Reference.
 * This class handles the database setup and migration.
 *
 * @class WC_WeArePlanet_Order_Reference
 */
class WC_WeArePlanet_Order_Reference {
	const WEAREPLANET_ORDER_ID = 'order_id';
	const WEAREPLANET_ORDER_NUMBER = 'order_number';
}
