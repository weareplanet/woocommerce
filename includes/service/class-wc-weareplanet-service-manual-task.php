<?php
/**
 *
 * WC_WeArePlanet_Service_Manual_Task Class
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
 * This service provides methods to handle manual tasks.
 */
class WC_WeArePlanet_Service_Manual_Task extends WC_WeArePlanet_Service_Abstract {
	const CONFIG_KEY = 'wc_weareplanet_manual_task';

	/**
	 * Returns the number of open manual tasks.
	 *
	 * @return int
	 */
	public function get_number_of_manual_tasks() {
		return get_option( self::CONFIG_KEY, 0 );
	}

	/**
	 * Updates the number of open manual tasks.
	 *
	 * @return int
	 */
	public function update() {
		$number_of_manual_tasks = 0;
		$manual_task_service = new \WeArePlanet\Sdk\Service\ManualTaskService( WC_WeArePlanet_Helper::instance()->get_api_client() );

		$space_id = get_option( WooCommerce_WeArePlanet::CK_SPACE_ID );
		if ( ! empty( $space_id ) ) {
			$number_of_manual_tasks = $manual_task_service->count(
				$space_id,
				$this->create_entity_filter( 'state', \WeArePlanet\Sdk\Model\ManualTaskState::OPEN )
			);
			update_option( self::CONFIG_KEY, $number_of_manual_tasks );
		}

		return $number_of_manual_tasks;
	}
}
