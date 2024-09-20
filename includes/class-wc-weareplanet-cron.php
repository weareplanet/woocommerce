<?php
/**
 *
 * WC_WeArePlanet_Cron Class
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
 * Class WC_WeArePlanet_Cron.
 *
 * @class WC_WeArePlanet_Cron
 */
/**
 * This class handles the cron jobs
 */
class WC_WeArePlanet_Cron {

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action(
			'cron_schedules',
			array(
				__CLASS__,
				'add_custom_cron_schedule',
			),
			5
		);
	}

	/**
	 * Add cron schedule.
	 *
	 * @param  array $schedules schedules.
	 * @return array
	 */
	public static function add_custom_cron_schedule( $schedules ) {
		$schedules['five_minutes'] = array(
			'interval' => 300,
			'display'  => __( 'Every Five Minutes' ),
		);
		return $schedules;
	}

	/**
	 * Activate the cron.
	 */
	public static function activate() {
		if ( ! wp_next_scheduled( 'weareplanet_five_minutes_cron' ) ) {
			wp_schedule_event( time(), 'five_minutes', 'weareplanet_five_minutes_cron' );
		}
	}

	/**
	 * Deactivate the cron.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'weareplanet_five_minutes_cron' );
	}
}
WC_WeArePlanet_Cron::init();
