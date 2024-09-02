<?php
/**
 *
 * WC_WeArePlanet_Service_Webhook Class
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
 * This service handles webhooks.
 */
class WC_WeArePlanet_Service_Webhook extends WC_WeArePlanet_Service_Abstract {

	const WEAREPLANET_MANUAL_TASK = 1487165678181;
	const WEAREPLANET_PAYMENT_METHOD_CONFIGURATION = 1472041857405;
	const WEAREPLANET_TRANSACTION = 1472041829003;
	const WEAREPLANET_DELIVERY_INDICATION = 1472041819799;
	const WEAREPLANET_TRANSACTION_INVOICE = 1472041816898;
	const WEAREPLANET_TRANSACTION_COMPLETION = 1472041831364;
	const WEAREPLANET_TRANSACTION_VOID = 1472041867364;
	const WEAREPLANET_REFUND = 1472041839405;
	const WEAREPLANET_TOKEN = 1472041806455;
	const WEAREPLANET_TOKEN_VERSION = 1472041811051;

	/**
	 * The webhook listener API service.
	 *
	 * @var \WeArePlanet\Sdk\Service\WebhookListenerService
	 */
	private $webhook_listener_service;

	/**
	 * The webhook url API service.
	 *
	 * @var \WeArePlanet\Sdk\Service\WebhookUrlService
	 */
	private $webhook_url_service;


	/**
	 * Webhook entities.
	 *
	 * @var array
	 */
	private $webhook_entities = array();

	/**
	 * Construct.
	 *
	 * Constructor to register the webhook entites.
	 */
	public function __construct() {
		$this->init_webhook_entities();
	}
	
	/**
	 * Initializes webhook entities with their specific configurations.
         */
	private function init_webhook_entities() {
		$this->webhook_entities[self::WEAREPLANET_MANUAL_TASK] = new WC_WeArePlanet_Webhook_Entity(
			self::WEAREPLANET_MANUAL_TASK,
			'Manual Task',
			array(
				\WeArePlanet\Sdk\Model\ManualTaskState::DONE,
				\WeArePlanet\Sdk\Model\ManualTaskState::EXPIRED,
				\WeArePlanet\Sdk\Model\ManualTaskState::OPEN,
			),
			'WC_WeArePlanet_Webhook_Manual_Task'
		);
		$this->webhook_entities[self::WEAREPLANET_PAYMENT_METHOD_CONFIGURATION] = new WC_WeArePlanet_Webhook_Entity(
			self::WEAREPLANET_PAYMENT_METHOD_CONFIGURATION,
			'Payment Method Configuration',
			array(
				\WeArePlanet\Sdk\Model\CreationEntityState::ACTIVE,
				\WeArePlanet\Sdk\Model\CreationEntityState::DELETED,
				\WeArePlanet\Sdk\Model\CreationEntityState::DELETING,
				\WeArePlanet\Sdk\Model\CreationEntityState::INACTIVE,
			),
			'WC_WeArePlanet_Webhook_Method_Configuration',
			true
		);
		$this->webhook_entities[self::WEAREPLANET_TRANSACTION] = new WC_WeArePlanet_Webhook_Entity(
			self::WEAREPLANET_TRANSACTION,
			'Transaction',
			array(
				\WeArePlanet\Sdk\Model\TransactionState::CONFIRMED,
				\WeArePlanet\Sdk\Model\TransactionState::AUTHORIZED,
				\WeArePlanet\Sdk\Model\TransactionState::DECLINE,
				\WeArePlanet\Sdk\Model\TransactionState::FAILED,
				\WeArePlanet\Sdk\Model\TransactionState::FULFILL,
				\WeArePlanet\Sdk\Model\TransactionState::VOIDED,
				\WeArePlanet\Sdk\Model\TransactionState::COMPLETED,
				\WeArePlanet\Sdk\Model\TransactionState::PROCESSING,
			),
			'WC_WeArePlanet_Webhook_Transaction'
		);
		$this->webhook_entities[self::WEAREPLANET_DELIVERY_INDICATION] = new WC_WeArePlanet_Webhook_Entity(
			self::WEAREPLANET_DELIVERY_INDICATION,
			'Delivery Indication',
			array(
				\WeArePlanet\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED,
			),
			'WC_WeArePlanet_Webhook_Delivery_Indication'
		);

		$this->webhook_entities[self::WEAREPLANET_TRANSACTION_INVOICE] = new WC_WeArePlanet_Webhook_Entity(
			self::WEAREPLANET_TRANSACTION_INVOICE,
			'Transaction Invoice',
			array(
				\WeArePlanet\Sdk\Model\TransactionInvoiceState::NOT_APPLICABLE,
				\WeArePlanet\Sdk\Model\TransactionInvoiceState::PAID,
				\WeArePlanet\Sdk\Model\TransactionInvoiceState::DERECOGNIZED,
			),
			'WC_WeArePlanet_Webhook_Transaction_Invoice'
		);

		$this->webhook_entities[self::WEAREPLANET_TRANSACTION_COMPLETION] = new WC_WeArePlanet_Webhook_Entity(
			self::WEAREPLANET_TRANSACTION_COMPLETION,
			'Transaction Completion',
			array(
				\WeArePlanet\Sdk\Model\TransactionCompletionState::FAILED,
				\WeArePlanet\Sdk\Model\TransactionCompletionState::SUCCESSFUL,
			),
			'WC_WeArePlanet_Webhook_Transaction_Completion'
		);

		$this->webhook_entities[self::WEAREPLANET_TRANSACTION_VOID] = new WC_WeArePlanet_Webhook_Entity(
			self::WEAREPLANET_TRANSACTION_VOID,
			'Transaction Void',
			array(
				\WeArePlanet\Sdk\Model\TransactionVoidState::FAILED,
				\WeArePlanet\Sdk\Model\TransactionVoidState::SUCCESSFUL,
			),
			'WC_WeArePlanet_Webhook_Transaction_Void'
		);

		$this->webhook_entities[self::WEAREPLANET_REFUND] = new WC_WeArePlanet_Webhook_Entity(
			self::WEAREPLANET_REFUND,
			'Refund',
			array(
				\WeArePlanet\Sdk\Model\RefundState::FAILED,
				\WeArePlanet\Sdk\Model\RefundState::SUCCESSFUL,
			),
			'WC_WeArePlanet_Webhook_Refund'
		);
		$this->webhook_entities[self::WEAREPLANET_TOKEN] = new WC_WeArePlanet_Webhook_Entity(
			self::WEAREPLANET_TOKEN,
			'Token',
			array(
				\WeArePlanet\Sdk\Model\CreationEntityState::ACTIVE,
				\WeArePlanet\Sdk\Model\CreationEntityState::DELETED,
				\WeArePlanet\Sdk\Model\CreationEntityState::DELETING,
				\WeArePlanet\Sdk\Model\CreationEntityState::INACTIVE,
			),
			'WC_WeArePlanet_Webhook_Token'
		);
		$this->webhook_entities[self::WEAREPLANET_TOKEN_VERSION] = new WC_WeArePlanet_Webhook_Entity(
			self::WEAREPLANET_TOKEN_VERSION,
			'Token Version',
			array(
				\WeArePlanet\Sdk\Model\TokenVersionState::ACTIVE,
				\WeArePlanet\Sdk\Model\TokenVersionState::OBSOLETE,
			),
			'WC_WeArePlanet_Webhook_Token_Version'
		);
	}

	/**
	 * Installs the necessary webhooks in WeArePlanet.
	 */
	public function install() {
		$space_id = get_option( WooCommerce_WeArePlanet::CK_SPACE_ID );
		if ( ! empty( $space_id ) ) {
			$webhook_url = $this->get_webhook_url( $space_id );
			if ( null == $webhook_url ) {
				$webhook_url = $this->create_webhook_url( $space_id );
			}
			$existing_listeners = $this->get_webhook_listeners( $space_id, $webhook_url );
			foreach ( $this->webhook_entities as $webhook_entity ) {
				/* @var WC_WeArePlanet_Webhook_Entity $webhook_entity */
				$exists = false;
				foreach ( $existing_listeners as $existing_listener ) {
					if ( $existing_listener->getEntity() == $webhook_entity->get_id() ) {
						$exists = true;
					}
				}
				if ( ! $exists ) {
					$this->create_webhook_listener( $webhook_entity, $space_id, $webhook_url );
				}
			}
		}
	}

	/**
	 * Get the webhook entity for a specific ID or throws an exception if not found.
	 *
	 * @param mixed $id The ID of the webhook entity to retrieve.
	 * @return WC_WeArePlanet_Webhook_Entity The webhook entity associated with the given ID.
	 * @throws Exception If the webhook entity cannot be found.
	 */
	public function get_webhook_entity_for_id( $id ) {
		if ( !isset( $this->webhook_entities[ $id ] ) ) {
			throw new Exception( sprintf( 'Could not retrieve webhook model for listener entity id: %s', $id ) );
		}
		
		return $this->webhook_entities[ $id ];
	}

	/**
	 * Create a webhook listener.
	 *
	 * @param WC_WeArePlanet_Webhook_Entity     $entity entity.
	 * @param int                                         $space_id space id.
	 * @param \WeArePlanet\Sdk\Model\WebhookUrl $webhook_url webhook url.
	 *
	 * @return \WeArePlanet\Sdk\Model\WebhookListenerCreate
	 * @throws \Exception Exception.
	 */
	protected function create_webhook_listener( WC_WeArePlanet_Webhook_Entity $entity, $space_id, \WeArePlanet\Sdk\Model\WebhookUrl $webhook_url ) {
		$webhook_listener = new \WeArePlanet\Sdk\Model\WebhookListenerCreate();
		$webhook_listener->setEntity( $entity->get_id() );
		$webhook_listener->setEntityStates( $entity->get_states() );
		$webhook_listener->setName( 'Woocommerce ' . $entity->get_name() );
		$webhook_listener->setState( \WeArePlanet\Sdk\Model\CreationEntityState::ACTIVE );
		$webhook_listener->setUrl( $webhook_url->getId() );
		$webhook_listener->setNotifyEveryChange( $entity->is_notify_every_change() );
		$webhook_listener->setEnablePayloadSignatureAndState( true );
		return $this->get_webhook_listener_service()->create( $space_id, $webhook_listener );
	}

	/**
	 * Returns the existing webhook listeners.
	 *
	 * @param int                                         $space_id space id.
	 * @param \WeArePlanet\Sdk\Model\WebhookUrl $webhook_url webhook url.
	 *
	 * @return \WeArePlanet\Sdk\Model\WebhookListener[]
	 * @throws \Exception Exception.
	 */
	protected function get_webhook_listeners( $space_id, \WeArePlanet\Sdk\Model\WebhookUrl $webhook_url ) {
		$query = new \WeArePlanet\Sdk\Model\EntityQuery();
		$filter = new \WeArePlanet\Sdk\Model\EntityQueryFilter();
		$filter->setType( \WeArePlanet\Sdk\Model\EntityQueryFilterType::_AND );
		$filter->setChildren(
			array(
				$this->create_entity_filter( 'state', \WeArePlanet\Sdk\Model\CreationEntityState::ACTIVE ),
				$this->create_entity_filter( 'url.id', $webhook_url->getId() ),
			)
		);
		$query->setFilter( $filter );
		return $this->get_webhook_listener_service()->search( $space_id, $query );
	}

	/**
	 * Creates a webhook url.
	 *
	 * @param int $space_id space id.
	 *
	 * @return \WeArePlanet\Sdk\Model\WebhookUrlCreate
	 * @throws \Exception Exception.
	 */
	protected function create_webhook_url( $space_id ) {
		$webhook_url = new \WeArePlanet\Sdk\Model\WebhookUrlCreate();
		$webhook_url->setUrl( $this->get_url() );
		$webhook_url->setState( \WeArePlanet\Sdk\Model\CreationEntityState::ACTIVE );
		$webhook_url->setName( 'Woocommerce' );
		return $this->get_webhook_url_service()->create( $space_id, $webhook_url );
	}

	/**
	 * Returns the existing webhook url if there is one.
	 *
	 * @param int $space_id space id.
	 *
	 * @return \WeArePlanet\Sdk\Model\WebhookUrl
	 * @throws \Exception Exception.
	 */
	protected function get_webhook_url( $space_id ) {
		$query = new \WeArePlanet\Sdk\Model\EntityQuery();
		$filter = new \WeArePlanet\Sdk\Model\EntityQueryFilter();
		$filter->setType( \WeArePlanet\Sdk\Model\EntityQueryFilterType::_AND );
		$filter->setChildren(
			array(
				$this->create_entity_filter( 'state', \WeArePlanet\Sdk\Model\CreationEntityState::ACTIVE ),
				$this->create_entity_filter( 'url', $this->get_url() ),
			)
		);
		$query->setFilter( $filter );
		$query->setNumberOfEntities( 1 );
		$result = $this->get_webhook_url_service()->search( $space_id, $query );
		if ( ! empty( $result ) ) {
			return $result[0];
		} else {
			return null;
		}
	}

	/**
	 * Returns the webhook endpoint URL.
	 *
	 * @return string
	 */
	protected function get_url() {
		return add_query_arg( 'wc-api', 'weareplanet_webhook', home_url( '/' ) );
	}

	/**
	 * Returns the webhook listener API service.
	 *
	 * @return \WeArePlanet\Sdk\Service\WebhookListenerService
	 * @throws \Exception Exception.
	 */
	protected function get_webhook_listener_service() {
		if ( null == $this->webhook_listener_service ) {
			$this->webhook_listener_service = new \WeArePlanet\Sdk\Service\WebhookListenerService( WC_WeArePlanet_Helper::instance()->get_api_client() );
		}
		return $this->webhook_listener_service;
	}

	/**
	 * Returns the webhook url API service.
	 *
	 * @return \WeArePlanet\Sdk\Service\WebhookUrlService
	 * @throws \Exception Exception.
	 */
	protected function get_webhook_url_service() {
		if ( null == $this->webhook_url_service ) {
			$this->webhook_url_service = new \WeArePlanet\Sdk\Service\WebhookUrlService( WC_WeArePlanet_Helper::instance()->get_api_client() );
		}
		return $this->webhook_url_service;
	}
}
