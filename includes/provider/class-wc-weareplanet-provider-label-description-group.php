<?php
/**
 *
 * WC_WeArePlanet_Provider_Label_Description_Group Class
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
 * Provider of label descriptor group information from the gateway.
 */
class WC_WeArePlanet_Provider_Label_Description_Group extends WC_WeArePlanet_Provider_Abstract {

	/**
	 * Construct.
	 */
	protected function __construct() {
		parent::__construct( 'wc_weareplanet_label_description_groups' );
	}

	/**
	 * Returns the label descriptor group by the given code.
	 *
	 * @param int $id Id.
	 * @return \WeArePlanet\Sdk\Model\LabelDescriptorGroup
	 */
	public function find( $id ) {
		return parent::find( $id );
	}

	/**
	 * Returns a list of label descriptor groups.
	 *
	 * @return \WeArePlanet\Sdk\Model\LabelDescriptorGroup[]
	 */
	public function get_all() {
		return parent::get_all();
	}

	/**
	 * Fetch data.
	 *
	 * @return array|\WeArePlanet\Sdk\Model\LabelDescriptorGroup[]
	 * @throws \WeArePlanet\Sdk\ApiException ApiException.
	 * @throws \WeArePlanet\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \WeArePlanet\Sdk\VersioningException VersioningException.
	 */
	protected function fetch_data() {
		$label_description_group_service = new \WeArePlanet\Sdk\Service\LabelDescriptionGroupService( WC_WeArePlanet_Helper::instance()->get_api_client() );
		return $label_description_group_service->all();
	}

	/**
	 * Get id.
	 *
	 * @param mixed $entry entry.
	 * @return int|string
	 */
	protected function get_id( $entry ) {
		/* @var \WeArePlanet\Sdk\Model\LabelDescriptorGroup $entry */
		return $entry->getId();
	}
}
