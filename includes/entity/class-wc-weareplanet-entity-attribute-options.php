<?php
/**
 *
 * WC_WeArePlanet_Entity_Attribute_Options Class
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
 * Class WC_WeArePlanet_Entity_Attribute_Options.
 *
 * @class WC_WeArePlanet_Entity_Attribute_Options
 */
/**
 * This entity holds data about a the product attribute options.
 *
 * @method int get_id()
 * @method int get_attribute_id()
 * @method void set_attribute_id(int $id)
 * @method boolean get_send()
 * @method void set_send(boolean $send)
 */
class WC_WeArePlanet_Entity_Attribute_Options extends WC_WeArePlanet_Entity_Abstract {
	/**
	 * Get field definition.
	 */
	protected static function get_field_definition() {
		return array(
			'attribute_id' => WC_WeArePlanet_Entity_Resource_Type::INTEGER,
			'send' => WC_WeArePlanet_Entity_Resource_Type::BOOLEAN,
		);
	}

	/**
	 * Get base fields.
	 */
	protected static function get_base_fields() {
		return array(
			'id' => WC_WeArePlanet_Entity_Resource_Type::INTEGER,
		);
	}

	/**
	 * Get table name.
	 */
	protected static function get_table_name() {
		return 'wc_weareplanet_attribute_options';
	}

	/**
	 * Prepare base fields for storage.
	 *
	 * @param array $data_array data array.
	 * @param array $type_array type array.
	 */
	protected function prepare_base_fields_for_storage( &$data_array, &$type_array ) {

	}

	/**
	 * Load attribute by ID.
	 *
	 * @param mixed $attribute_id attribute id.
	 */
	public static function load_by_attribute_id( $attribute_id ) {
		global $wpdb;
		$result = $wpdb->get_row(
			// phpcs:ignore
			$wpdb->prepare(
				'SELECT * FROM %1$s WHERE attribute_id = %2$d',
				$wpdb->prefix . self::get_table_name(),
				$attribute_id
			),
			ARRAY_A
		);
		if ( null !== $result ) {
			return new self( $result );
		}
		return new self();
	}

}
