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
 * This entity holds data about a WeArePlanet payment method.
 *
 * @method int get_id()
 * @method string get_state()
 * @method void set_state(string $state)
 * @method int get_space_id()
 * @method void set_space_id(int $id)
 * @method int get_configuration_id()
 * @method void set_configuration_id(int $id)
 * @method string get_configuration_name()
 * @method void set_configuration_name(string $name)
 * @method string[] get_title()
 * @method void set_title(string[] $title)
 * @method string[] get_description()
 * @method void set_description(string[] $description)
 * @method string get_image()
 * @method void set_image(string $image)
 * @method string get_image_base()
 * @method void set_image_base(string $image_base)
 */
class WC_WeArePlanet_Entity_Method_Configuration extends WC_WeArePlanet_Entity_Abstract {
	const WEAREPLANET_STATE_ACTIVE = 'active';
	const WEAREPLANET_STATE_INACTIVE = 'inactive';
	const WEAREPLANET_STATE_HIDDEN = 'hidden';

	/**
	 * Get field definition.
	 *
	 * @return array
	 */
	protected static function get_field_definition() {
		return array(
			'state' => WC_WeArePlanet_Entity_Resource_Type::WEAREPLANET_STRING,
			'space_id' => WC_WeArePlanet_Entity_Resource_Type::WEAREPLANET_INTEGER,
			'configuration_id' => WC_WeArePlanet_Entity_Resource_Type::WEAREPLANET_INTEGER,
			'configuration_name' => WC_WeArePlanet_Entity_Resource_Type::WEAREPLANET_STRING,
			'title' => WC_WeArePlanet_Entity_Resource_Type::WEAREPLANET_OBJECT,
			'description' => WC_WeArePlanet_Entity_Resource_Type::WEAREPLANET_OBJECT,
			'image' => WC_WeArePlanet_Entity_Resource_Type::WEAREPLANET_STRING,
			'image_base' => WC_WeArePlanet_Entity_Resource_Type::WEAREPLANET_STRING,

		);
	}

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	protected static function get_table_name() {
		return 'weareplanet_method_config';
	}

	/**
	 * Load by configuration.
	 *
	 * @param mixed $space_id space id.
	 * @param mixed $configuration_id configuration id.
	 * @return WC_WeArePlanet_Entity_Method_Configuration
	 */
	public static function load_by_configuration( $space_id, $configuration_id ) {
		global $wpdb;

		$table = $wpdb->prefix . self::get_table_name();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Values are escaped in $wpdb->prepare.
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE space_id = %d AND configuration_id = %d",
				$space_id,
				$configuration_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare.

		if ( null !== $result ) {
			return new self( $result );
		}
		return new self();
	}

	/**
	 * Load by states and space id.
	 *
	 * @param mixed $space_id space id.
	 * @param array $states states.
	 * @return array
	 */
	public static function load_by_states_and_space_id( $space_id, array $states ) {
		global $wpdb;
		if ( empty( $states ) ) {
			return array();
		}
		$replace = '';

		$states_count = count( $states );

		for ( $i = 0; $i < $states_count; $i++ ) {
			$replace .= '%s, ';
		}
		$replace = rtrim( $replace, ', ' );

		$values = array_merge( array( $space_id ), $states );

		$query = 'SELECT * FROM ' . $wpdb->prefix . self::get_table_name() . ' WHERE space_id = %d AND state IN (' . $replace . ')';
		$result = array();

		try {
			// phpcs:ignore
			$db_results = $wpdb->get_results( $wpdb->prepare( $query, $values ), ARRAY_A );
			if ( is_array( $db_results ) ) {
				foreach ( $db_results as $object_values ) {
					$result[] = new static( $object_values );
				}
			}
		} catch ( Exception $e ) {
			return $result;
		}
		return $result;
	}
}
