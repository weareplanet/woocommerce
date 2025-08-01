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
 * WC_WeArePlanet_Helper Class.
 */
class WC_WeArePlanet_Helper {

	const WEAREPLANET_SHOP_SYSTEM = 'x-meta-shop-system';
	const WEAREPLANET_SHOP_SYSTEM_VERSION = 'x-meta-shop-system-version';
	const WEAREPLANET_SHOP_SYSTEM_AND_VERSION = 'x-meta-shop-system-and-version';
	const WEAREPLANET_CHECKOUT_VERSION = 'x-checkout-type';
	const WEAREPLANET_CHECKOUT_TYPE_BLOCKS = 'blocks';
	const WEAREPLANET_CHECKOUT_TYPE_LEGACY = 'legacy';
	const WEAREPLANET_PLUGIN_VERSION = 'x-meta-plugin-version';

	/**
	 * Instance.
	 *
	 * @var mixed $instance instance.
	 */
	private static $instance;

	/**
	 * Api client.
	 *
	 * @var mixed $api_client api client.
	 */
	private $api_client;

	/**
	 * Construct.
	 */
	private function __construct() {}

	/**
	 * Instance.
	 *
	 * @return WC_WeArePlanet_Helper
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Delete provider transients.
	 *
	 * @return void
	 */
	public function delete_provider_transients() {
		$transients = array(
			'wc_weareplanet_currencies',
			'wc_weareplanet_label_description_groups',
			'wc_weareplanet_label_descriptions',
			'wc_weareplanet_languages',
			'wc_weareplanet_payment_connectors',
			'wc_weareplanet_payment_methods',
		);
		foreach ( $transients as $transient ) {
			delete_transient( $transient );
		}
	}

	/**
	 * Get api client.
	 *
	 * @throws Exception Exception.
	 * @return \WeArePlanet\Sdk\ApiClient
	 */
	public function get_api_client() {
		if ( null === $this->api_client ) {
			$user_id = get_option( WooCommerce_WeArePlanet::WEAREPLANET_CK_APP_USER_ID );
			$user_key = get_option( WooCommerce_WeArePlanet::WEAREPLANET_CK_APP_USER_KEY );
			if ( ! empty( $user_id ) && ! empty( $user_key ) ) {
				$this->api_client = new \WeArePlanet\Sdk\ApiClient( $user_id, $user_key );
				$this->api_client->setBasePath( rtrim( $this->get_base_gateway_url(), '/' ) . '/api' );
				foreach ( self::get_default_header_data() as $key => $value ) {
					$this->api_client->addDefaultHeader( $key, $value );
				}
			} else {
				throw new Exception( esc_html__( 'The API access data is incomplete.', 'woo-weareplanet' ) );
			}
		}
		return $this->api_client;
	}

	/**
	 * Reset api client.
	 *
	 * @return void
	 */
	public function reset_api_client() {
		$this->api_client = null;
	}

	/**
	 * Returns the base URL to the gateway.
	 *
	 * @return string
	 */
	public function get_base_gateway_url() {
		return get_option( 'wc_weareplanet_base_gateway_url', 'https://paymentshub.weareplanet.com/' );
	}


	/**
	 * Translate.
	 *
	 * @param mixed $translated_string translated string.
	 * @param mixed $language language.
	 * @return mixed|null
	 */
	public function translate( $translated_string, $language = null ) {
		if ( empty( $language ) ) {
			$language = $this->get_cleaned_locale();
		}
		if ( isset( $translated_string[ $language ] ) ) {
			return $translated_string[ $language ];
		}

		try {
			$language_provider = WC_WeArePlanet_Provider_Language::instance();
			$primary_language = $language_provider->find_primary( $language );
			if ( $primary_language && isset( $translated_string[ $primary_language->getIetfCode() ] ) ) {
				return $translated_string[ $primary_language->getIetfCode() ];
			}
		} catch ( Exception $e ) {
			return null;
		}

		if ( isset( $translated_string['en-US'] ) ) {
			return $translated_string['en-US'];
		}

		return null;
	}

	/**
	 * Returns the URL to a resource on WeArePlanet in the given context (space, space view, language).
	 *
	 * @param string $base base.
	 * @param string $path path.
	 * @param string $language language.
	 * @param int    $space_id space id.
	 * @param int    $space_view_id space view id.
	 * @return string
	 */
	public function get_resource_url( $base, $path, $language = null, $space_id = null, $space_view_id = null ) {
		if ( empty( $base ) ) {
			$url = $this->get_base_gateway_url();
		} else {
			$url = $base;
		}
		$url = rtrim( $url, '/' );

		if ( ! empty( $language ) ) {
			$url .= '/' . str_replace( '_', '-', $language );
		}

		if ( ! empty( $space_id ) ) {
			$url .= '/s/' . $space_id;
		}

		if ( ! empty( $space_view_id ) ) {
			$url .= '/' . $space_view_id;
		}

		$url .= '/resource/' . $path;
		return $url;
	}

	/**
	 * Returns the fraction digits of the given currency.
	 *
	 * @param string $currency_code currency code.
	 * @return int
	 */
	public function get_currency_fraction_digits( $currency_code ) {
		$currency_provider = WC_WeArePlanet_Provider_Currency::instance();
		$currency = $currency_provider->find( $currency_code );
		if ( $currency ) {
			return $currency->getFractionDigits();
		} else {
			return 2;
		}
	}

	/**
	 * Get total amount including tax.
	 *
	 * @param array $line_items line items.
	 * @param bool  $exclude_discounts exclude discounts.
	 * @return int
	 */
	public function get_total_amount_including_tax( array $line_items, bool $exclude_discounts = false ) {
		$sum = 0;
		foreach ( $line_items as $line_item ) {
			$type = $line_item->getType();
			$name = $line_item->getName();

			if ( $exclude_discounts && \WeArePlanet\Sdk\Model\LineItemType::DISCOUNT === $type
				&& strpos( $name, WC_WeArePlanet_Packages_Coupon_Discount::WEAREPLANET_COUPON ) !== false
			) {
				// convert negative values to positive in order to be able to subtract it.
				$sum -= abs( $line_item->getAmountIncludingTax() );
			} else {
				$sum += abs( $line_item->getAmountIncludingTax() );
			}
		}
		return $sum;
	}

	/**
	 * Cleanup line items.
	 *
	 * @param array $line_items line items.
	 * @param mixed $expected_sum expected sum.
	 * @param mixed $currency currency.
	 * @return \WeArePlanet\Sdk\Model\LineItemCreate[]
	 * @throws WC_WeArePlanet_Exception_Invalid_Transaction_Amount WC_WeArePlanet_Exception_Invalid_Transaction_Amount.
	 */
	public function cleanup_line_items( array $line_items, $expected_sum, $currency, bool $is_recurrent = false ) {
		// Check if coupon is applied to order depending whether new order is created from session, or existing order.
		if ( $is_recurrent ) {
			$has_coupons = apply_filters( 'wc_weareplanet_packages_coupon_line_items_have_coupon_discounts', $line_items, $currency );
		} else {
			$has_coupons = apply_filters( 'wc_weareplanet_packages_coupon_cart_has_coupon_discounts_applied', $currency ); //phpcs:ignore
		}
		// ensure that the effective sum coincides with the total discounted by the coupons.
		$effective_sum = $this->round_amount( $this->get_total_amount_including_tax( $line_items, $has_coupons ), $currency );
		$rounded_expected_sum = $this->round_amount( $expected_sum, $currency );

		if ( $has_coupons ) {
			$result = apply_filters( 'wc_weareplanet_packages_coupon_process_line_items_with_coupons', $line_items, $expected_sum, $currency ); //phpcs:ignore
			$line_items = $result['line_items_cleaned'];
			$effective_sum = $result['effective_sum'];
			$rounded_expected_sum = $this->round_amount( $expected_sum, $currency );
		}

		$inconsistent_amount = $rounded_expected_sum - $effective_sum;
		if ( 0 !== (int) $inconsistent_amount ) {
			$enforce_consistency = get_option( WooCommerce_WeArePlanet::WEAREPLANET_CK_ENFORCE_CONSISTENCY );
			switch ( $enforce_consistency ) {
				case 'no':
					$line_item = new \WeArePlanet\Sdk\Model\LineItemCreate();
					$line_item->setAmountIncludingTax( $this->round_amount( $inconsistent_amount, $currency ) );
					$line_item->setName( esc_html__( 'Adjustment', 'woo-weareplanet' ) );
					$line_item->setQuantity( 1 );
					$line_item->setSku( 'adjustment' );
					$line_item->setUniqueId( 'adjustment' );
					$line_item->setShippingRequired( false );
					$line_item->setType( $enforce_consistency > 0 ? \WeArePlanet\Sdk\Model\LineItemType::FEE : \WeArePlanet\Sdk\Model\LineItemType::DISCOUNT );
					$line_items[] = $line_item;
					break;
				default:
					throw new WC_WeArePlanet_Exception_Invalid_Transaction_Amount( esc_html( $effective_sum ), esc_html( $rounded_expected_sum ) );
			}
		}
		return $this->ensure_unique_ids( $line_items );
	}


	/**
	 * Ensure unique ids.
	 *
	 * @param array $line_items line items.
	 * @return array
	 * @throws Exception Exception.
	 */
	public function ensure_unique_ids( array $line_items ) {
		$unique_ids = array();
		foreach ( $line_items as $line_item ) {
			$unique_id = $line_item->getUniqueId();
			if ( empty( $unique_id ) ) {
				$unique_id = preg_replace( '/[^a-z0-9]/', '', strtolower( $line_item->getSku() ) );
			}
			if ( empty( $unique_id ) ) {
				throw new Exception( 'There is an invoice item without unique id.' );
			}
			if ( isset( $unique_ids[ $unique_id ] ) ) {
				$backup = $unique_id;
				$unique_id = $unique_id . '_' . $unique_ids[ $unique_id ];
				++$unique_ids[ $backup ];
			} else {
				$unique_ids[ $unique_id ] = 1;
			}

			$line_item->setUniqueId( $unique_id );
		}

		return $line_items;
	}


	/**
	 * Get reduction amount.
	 *
	 * @param array $line_items line items.
	 * @param array $reductions reductions.
	 * @return float|int
	 */
	public function get_reduction_amount( array $line_items, array $reductions ) {
		$line_item_map = array();
		foreach ( $line_items as $line_item ) {
			$line_item_map[ $line_item->getUniqueId() ] = $line_item;
		}

		$amount = 0;
		foreach ( $reductions as $reduction ) {
			$line_item = $line_item_map[ $reduction->getLineItemUniqueId() ];
			$amount += $line_item->getUnitPriceIncludingTax() * $reduction->getQuantityReduction();
			$amount += $reduction->getUnitPriceReduction() * ( $line_item->getQuantity() - $reduction->getQuantityReduction() );
		}

		return $amount;
	}

	/**
	 * Round amount.
	 *
	 * @param mixed $amount amount.
	 * @param mixed $currency_code currency code.
	 * @return float
	 */
	public function round_amount( $amount, $currency_code ) {
		return round( $amount, $this->get_currency_fraction_digits( $currency_code ) );
	}

	/**
	 * Get current cart id.
	 *
	 * @return array|mixed|string
	 * @throws Exception Exception.
	 */
	public function get_current_cart_id() {
		$session_handler = WC()->session;
		if ( null === $session_handler ) {
			throw new Exception( 'No session available.' );
		}
		$current_cart_id = $session_handler->get( 'weareplanet_current_cart_id', null );
		if ( null === $current_cart_id ) {
			$current_cart_id = WC_WeArePlanet_Unique_Id::get_uuid();
			$session_handler->set( 'weareplanet_current_cart_id', $current_cart_id );
		}
		return $current_cart_id;
	}

	/**
	 * Destroy current cart id.
	 *
	 * @return void
	 */
	public function destroy_current_cart_id() {
		$session_handler = WC()->session;
		$session_handler->set( 'weareplanet_current_cart_id', null );
	}

	/**
	 * Create a lock to prevent concurrency.
	 *
	 * @param int $space_id space id.
	 * @param int $transaction_id transaction id.
	 */
	public function lock_by_transaction_id( $space_id, $transaction_id ) {
		global $wpdb;

		$locked_at = gmdate( 'Y-m-d H:i:s' );
		$table_transaction_info = $wpdb->prefix . 'weareplanet_transaction_info';
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Values are escaped in $wpdb->prepare.
		$wpdb->query(
			$wpdb->prepare(
				"SELECT * FROM $table_transaction_info WHERE transaction_id = %d and space_id = %d FOR UPDATE",
				$transaction_id,
				$space_id
			)
		);

		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Values are escaped in $wpdb->prepare.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $table_transaction_info
					SET locked_at = %s
					WHERE transaction_id = %d AND space_id = %d",
				$locked_at,
				$transaction_id,
				$space_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare.
	}


	/**
	 * Get cleaned locale.
	 *
	 * @param mixed $use_default use default.
	 * @return string|null
	 */
	public function get_cleaned_locale( $use_default = true ) {
		$language_string = get_locale();
		return $this->get_clean_locale_for_string( $language_string, $use_default );
	}


	/**
	 * Get clean locale for string.
	 *
	 * @param mixed $language_string lanugage string.
	 * @param mixed $use_default use default.
	 * @return string|null
	 */
	public function get_clean_locale_for_string( $language_string, $use_default ) {
		$language_string = str_replace( '_', '-', $language_string );
		$language = false;
		if ( strlen( $language_string ) >= 5 ) {
			// We assume it was a long ietf code, check if it exists.
			$language = WC_WeArePlanet_Provider_Language::instance()->find( $language_string );
			if ( ! $language && strpos( $language_string, '-' ) !== false ) {
				$language_parts = explode( '-', $language_string );
				array_pop( $language_parts );
				while ( ! $language && ! empty( $language_parts ) ) {
					$language = WC_WeArePlanet_Provider_Language::instance()->find( implode( '-', $language_parts ) );
					array_pop( $language_parts );
				}
			}
		}
		if ( ! $language ) {
			if ( strpos( $language_string, '-' ) !== false ) {
				$language_string = strtolower( substr( $language_string, 0, strpos( $language_string, '-' ) ) );
			}
			$language = WC_WeArePlanet_Provider_Language::instance()->find_by_iso_code( $language_string );
		}
		// We did not find anything, so fall back.
		if ( ! $language ) {
			if ( $use_default ) {
				return 'en-US';
			}
			return null;
		}
		return $language->getIetfCode();
	}


	/**
	 * Try to parse date.
	 *
	 * @param mixed $date_string date string.
	 * @return DateTime|false
	 */
	public function try_to_parse_date( $date_string ) {
		$date_of_birth = false;
		$custom_date_of_birth_format = apply_filters( 'wc_weareplanet_custom_date_of_birth_format', '' ); //phpcs:ignore
		if ( ! empty( $custom_date_of_birth_format ) ) {
			$date_of_birth = DateTime::createFromFormat( $custom_date_of_birth_format, $date_string );
		} else {
			$date_of_birth = DateTime::createFromFormat( 'd.m.Y', $date_string );
			if ( ! $date_of_birth ) {
				$date_of_birth = DateTime::createFromFormat( 'd-m-Y', $date_string );
			}
			if ( ! $date_of_birth ) {
				$date_of_birth = DateTime::createFromFormat( 'm/d/Y', $date_string );
			}
			if ( ! $date_of_birth ) {
				$date_of_birth = DateTime::createFromFormat( 'Y-m-d', $date_string );
			}
			if ( ! $date_of_birth ) {
				$date_of_birth = DateTime::createFromFormat( 'Y/m/d', $date_string );
			}
		}
		return $date_of_birth;
	}

	/**
	 * Start database transaction.
	 *
	 * @return void
	 */
	public function start_database_transaction() {
		global $wpdb;
		$wpdb->query( 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED' ); //phpcs:ignore
		wc_transaction_query( 'start' ); //phpcs:ignore
	}

	/**
	 * Commit database transaction.
	 *
	 * @return void
	 */
	public function commit_database_transaction() {
		wc_transaction_query( 'commit' ); //phpcs:ignore
	}

	/**
	 * Rollback database trnsaction.
	 *
	 * @return void
	 */
	public function rollback_database_transaction() {
		wc_transaction_query( 'rollback' ); //phpcs:ignore
	}

	/**
	 * Maybe restock items for order.
	 *
	 * @param WC_Order $order order.
	 * @return void
	 * @throws Exception Exception.
	 */
	public function maybe_restock_items_for_order( WC_Order $order ) {

		if ( version_compare( '3.5.0', WC_VERSION, '>' ) ) {
			$data_store = WC_Data_Store::load( 'order' );
			if ( $data_store->get_stock_reduced( $order->get_id() ) ) {
				$this->restock_items_for_order( $order );
				$data_store->set_stock_reduced( $order->get_id(), false );
			}
		} else {
			wc_maybe_increase_stock_levels( $order );
		}
	}

	/**
	 * Restock items for order.
	 *
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function restock_items_for_order( WC_Order $order ) {
		if (
			'yes' === get_option( 'woocommerce_manage_stock' )
			&& $order && apply_filters( 'weareplanet_can_increase_order_stock', true, $order )//phpcs:ignore
			&& count( $order->get_items() ) > 0
		) {

			foreach ( $order->get_items() as $item ) {
					$product = $item->get_product();
				if ( $item->is_type( 'line_item' ) && $product && $product->managing_stock() ) {
					//phpcs:ignore
					$qty = apply_filters( 'woocommerce_order_item_quantity', $item->get_quantity(), $order, $item );
					$item_name = esc_attr( $product->get_formatted_name() );
					$new_stock = wc_update_product_stock( $product, $qty, 'increase' );
					if ( ! is_wp_error( $new_stock ) ) {
						/* translators: 1: item name 2: old stock quantity 3: new stock quantity */
						$order->add_order_note( sprintf( esc_html__( '%1$s stock increased from %2$s to %3$s.', 'woo-weareplanet' ), $item_name, $new_stock - $qty, $new_stock ) );
					}
				}
			}
			do_action( 'wc_weareplanet_restocked_order', $order ); //phpcs:ignore
		}
	}

	/**
	 * Retrieve the default header data.
	 *
	 * @return array Default header data.
	 */
	protected static function get_default_header_data() {
		$version = WC_VERSION;

		$shop_version = str_replace( 'v', '', $version );
		$plugin_version = '3.3.17';
		list ($major_version, $minor_version) = explode( '.', $shop_version, 3 );
		return array(
			self::WEAREPLANET_SHOP_SYSTEM => 'woocommerce',
			self::WEAREPLANET_SHOP_SYSTEM_VERSION => $shop_version,
			self::WEAREPLANET_SHOP_SYSTEM_AND_VERSION => 'woocommerce-' . $major_version . '.' . $minor_version,
			self::WEAREPLANET_PLUGIN_VERSION => $plugin_version,
		);
	}

	/**
	* Get WooCommerce order statuses in JSON format.
	*
	* This method retrieves the WooCommerce order statuses, applies any filters,
	* and returns them as an array with structured data.
	*
	* @return array[] An array of WooCommerce order statuses, where each status is represented
	*                 as an associative array containing:
	*                 - 'key' (string)   : The order status key.
	*                 - 'label' (string) : The human-readable label for the status.
	*                 - 'type' (string)  : The type of status ('core' if it starts with 'wc-', otherwise 'custom').
	*/
	public function get_woocommerce_order_statuses_json() {
		$woocommerce_statuses = apply_filters( 'weareplanet_woocommerce_statuses', array() );
		$excluded_statuses = array(
			'wc-wearep-manual',
			'wc-wearep-redirected',
			'wc-wearep-waiting',
			'wc-pending',
			'wc-processing',
			'wc-on-hold',
			'wc-completed',
			'wc-cancelled',
			'wc-refunded',
			'wc-failed',
			'wc-trash',
			'wc-checkout-draft'
		);

		return array_map( function( $key, $value ) use ( $excluded_statuses ) {
				return array(
					'key'  => $key,
					'label' => ucfirst( $value ),
					'type' => in_array( $key, $excluded_statuses, true ) ? 'core' : 'custom',
				);
			},
			array_keys( $woocommerce_statuses ),
			$woocommerce_statuses
		);
	}

	/**
	 * @param $order
	 * @return void
	 */
	public static function set_virtual_zero_total_orders_to_complete( $order ) {
		if ( 'yes' === get_option( WooCommerce_WeArePlanet::WEAREPLANET_CK_CHANGE_ORDER_STATUS ) 
		&& $order->get_total() <= 0 && self::is_order_virtual( $order ) ) {
			$order->update_status( 'completed' );
		}
	}

	/**
	 * @param $order
	 * @return bool
	 */
	public static function is_order_virtual( $order ) {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order ) {
			return false;
		}

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( ! $product || ! $product->is_virtual() ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Update WooCommerce order status to "Pre-Order" if any product is out of stock or marked as pre-order.
	 *
	 * @param WC_Order $order        WooCommerce order object.
	 */
	public static function update_order_status_for_preorder_if_needed( $order ) {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order ) {
			return;
		}

		$is_preorder = false;
		$is_out_of_stock = false;
		$order_statuses = wc_get_order_statuses();

		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}
			$is_preorder_wc = (
			  class_exists( 'WC_Pre_Orders' )
			  && function_exists( 'wc_pre_orders_product_is_pre_order' )
			  && wc_pre_orders_product_is_pre_order( $product )
			);
			$is_preorder_yith = ( 'yes' === $product->get_meta( '_ywpo_preorder' ) );

			if ( $is_preorder_wc || $is_preorder_yith ) {
				// Check if order is marked as pre-ordered
				if ( 'yes' !== $order->get_meta( '_order_has_preorder' ) ) {
					return;
				}

				// Check if Yith Pre-Order plugin setting
				if ( 'default' === get_option( 'ywpo_order_status', 'default' ) ) {
					return;
				}
				$is_preorder = true;
				break;
			}

			// Check if product is not out of stock. Edge case: sometimes get_stock_quantity is not set, but then items are available
			if ( ( $product->get_stock_quantity() ?? 1 ) < 1 ) {
				$is_out_of_stock = true;
				break;
			}
		}

		if ( !$is_preorder && !$is_out_of_stock ) {
			return;
		}

		$preorder_status_slug = null;
		foreach ( $order_statuses as $status_slug => $status_label ) {
			if ( strpos( $status_slug, 'pre-ord' ) !== false || strpos( $status_slug, 'preorder' ) !== false ) {
				$preorder_status_slug = str_replace( 'wc-', '', $status_slug );
				break;
			}
		}

		if ( $preorder_status_slug ) {

			if ( $order->get_status() === $preorder_status_slug ) {
				return;
			}

			$order->update_status(
			  $preorder_status_slug,
			  __( 'Product is on pre-order. Status set automatically.', 'woo-weareplanet' )
			);

			$order->add_order_note(
			  __( 'Order status automatically set to pre-order.', 'woo-weareplanet' )
			);
		}
	}



}
