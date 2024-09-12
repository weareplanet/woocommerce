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
 * Defines the different resource types
 */
interface WC_WeArePlanet_Entity_Resource_Type {
	const WEAREPLANET_STRING = 'string';
	const WEAREPLANET_DATETIME = 'datetime';
	const WEAREPLANET_INTEGER = 'integer';
	const WEAREPLANET_BOOLEAN = 'boolean';
	const WEAREPLANET_OBJECT = 'object';
	const WEAREPLANET_DECIMAL = 'decimal';
}
