<?php
/**
 *
 * WC_WeArePlanet_Service_Token Class
 *
 * WeArePlanet
 * This plugin will add support for all WeArePlanet payments methods and connect the WeArePlanet servers to your WooCommerce webshop (https://www.weareplanet.com/).
 *
 * @category Class
 * @package  WeArePlanet
 * @author   Planet Merchant Services Ltd (https://www.weareplanet.com)
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

use WeArePlanet\Sdk\Model\TokenVersion;

if ( ! defined( 'ABSPATH' ) ) {
	exit(); // Exit if accessed directly.
}
/**
 * This service provides functions to deal with WeArePlanet tokens.
 *
 * @class WC_WeArePlanet_Service_Token
 */
class WC_WeArePlanet_Service_Token extends WC_WeArePlanet_Service_Abstract {

	/**
	 * The token API service.
	 *
	 * @var \WeArePlanet\Sdk\Service\TokenService
	 */
	private $token_service;

	/**
	 * The token version API service.
	 *
	 * @var \WeArePlanet\Sdk\Service\TokenVersionService
	 */
	private $token_version_service;

	/**
	 * Update token version.
	 *
	 * @param mixed $space_id space id.
	 * @param mixed $token_version_id token version id.
	 * @return void
	 * @throws \WeArePlanet\Sdk\ApiException ApiException.
	 * @throws \WeArePlanet\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \WeArePlanet\Sdk\VersioningException VersioningException.
	 */
	public function update_token_version( $space_id, $token_version_id ) {
		$token_version = $this->get_token_version_service()->read( $space_id, $token_version_id );
		$this->update_info( $space_id, $token_version );
	}

	/**
	 * Update token.
	 *
	 * @param mixed $space_id space id.
	 * @param mixed $token_id token id.
	 * @return void
	 * @throws \WeArePlanet\Sdk\ApiException ApiException.
	 * @throws \WeArePlanet\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \WeArePlanet\Sdk\VersioningException VersioningException.
	 */
	public function update_token( $space_id, $token_id ) {
		$query  = new \WeArePlanet\Sdk\Model\EntityQuery();
		$filter = new \WeArePlanet\Sdk\Model\EntityQueryFilter();
		$filter->setType( \WeArePlanet\Sdk\Model\EntityQueryFilterType::_AND );
		$filter->setChildren(
			array(
				$this->create_entity_filter( 'token.id', $token_id ),
				$this->create_entity_filter( 'state', \WeArePlanet\Sdk\Model\TokenVersionState::ACTIVE ),
			)
		);
		$query->setFilter( $filter );
		$query->setNumberOfEntities( 1 );
		$token_versions = $this->get_token_version_service()->search( $space_id, $query );
		if ( ! empty( $token_versions ) ) {
			$this->update_info( $space_id, current( $token_versions ) );
		} else {
			$info = WC_WeArePlanet_Entity_Token_Info::load_by_token( $space_id, $token_id );
			if ( $info->get_id() ) {
				$info->delete();
			}
		}
	}

	/**
	 * Update info.
	 *
	 * @param mixed        $space_id space id.
	 * @param TokenVersion $token_version token version.
	 * @return void
	 * @throws mixed Exception Exception.
	 */
	protected function update_info( $space_id, TokenVersion $token_version ) {
		$info = WC_WeArePlanet_Entity_Token_Info::load_by_token( $space_id, $token_version->getToken()->getId() );
		if ( ! in_array(
			$token_version->getToken()->getState(),
			array(
				\WeArePlanet\Sdk\Model\CreationEntityState::ACTIVE,
				\WeArePlanet\Sdk\Model\CreationEntityState::INACTIVE,
			),
			true
		) ) {
			if ( $info->get_id() ) {
				$info->delete();
			}
			return;
		}

		$info->set_customer_id( $token_version->getToken()->getCustomerId() );
		$info->set_name( $token_version->getName() );

		$payment_method = WC_WeArePlanet_Entity_Method_Configuration::load_by_configuration(
			$space_id,
			$token_version->getPaymentConnectorConfiguration()->getPaymentMethodConfiguration()->getId()
		);
		$info->set_payment_method_id( $payment_method->get_id() );
		$info->set_connector_id( $token_version->getPaymentConnectorConfiguration()->getConnector() );

		$info->set_space_id( $space_id );
		$info->set_state( $token_version->getToken()->getState() );
		$info->set_token_id( $token_version->getToken()->getId() );
		$info->save();
	}

	/**
	 * Delete Token.
	 *
	 * @param mixed $space_id space id.
	 * @param mixed $token_id token id.
	 * @return void
	 * @throws \WeArePlanet\Sdk\ApiException ApiException.
	 * @throws \WeArePlanet\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \WeArePlanet\Sdk\VersioningException VersioningException.
	 */
	public function delete_token( $space_id, $token_id ) {
		$this->get_token_service()->delete( $space_id, $token_id );
	}

	/**
	 * Returns the token API service.
	 *
	 * @return \WeArePlanet\Sdk\Service\TokenService TokenService.
	 */
	protected function get_token_service() {
		if ( null === $this->token_service ) {
			$this->token_service = new \WeArePlanet\Sdk\Service\TokenService( WC_WeArePlanet_Helper::instance()->get_api_client() );
		}

		return $this->token_service;
	}

	/**
	 * Returns the token version API service.
	 *
	 * @return \WeArePlanet\Sdk\Service\TokenVersionService
	 */
	protected function get_token_version_service() {
		if ( null === $this->token_version_service ) {
			$this->token_version_service = new \WeArePlanet\Sdk\Service\TokenVersionService( WC_WeArePlanet_Helper::instance()->get_api_client() );
		}

		return $this->token_version_service;
	}
}
