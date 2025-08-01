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
 * Class WC_WeArePlanet_Admin_Settings_Page.
 * Adds WeArePlanet settings to WooCommerce Settings Tabs
 *
 * @class WC_WeArePlanet_Admin_Settings_Page
 */
class WC_WeArePlanet_Admin_Settings_Page extends WC_Settings_Page {

	/**
	 * Adds Hooks to output and save settings
	 */
	public function __construct() {
		$this->id = 'weareplanet';
		$this->label = __( 'WeArePlanet', 'woo-weareplanet' );

		add_filter(
			'woocommerce_settings_tabs_array',
			array(
				$this,
				'add_settings_page',
			),
			20
		);

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'woocommerce_update_options_' . $this->id, array( $this, 'update_settings' ) );
		add_action( 'woocommerce_admin_field_weareplanet_order_statuses_table', array( $this, 'order_statuses_table' ) );
		add_action( 'woocommerce_admin_field_weareplanet_links', array( $this, 'output_links' ) );

		parent::__construct();
	}

	/**
	 * Add Settings Tab
	 *
	 * @param mixed $settings_tabs settings_tabs.
	 * @return mixed $settings_tabs
	 */
	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs[ $this->id ] = 'WeArePlanet';
		return $settings_tabs;
	}

	/**
	 * Get all sections for this page, both the own ones and the ones defined via filters.
	 * This method overrides the parent method to add the own sections to the list of sections.
	 *
	 * @return array
	 */
	protected function get_own_sections() {
		return array(
			'' => esc_html__( 'General Settings', 'woo-weareplanet' ),
			'weareplanet_order_statuses_settings' => esc_html__( 'Order Statuses Settings', 'woo-weareplanet' ),
		);
	}

	/**
	 * Get settings for the default section.
	 *
	 * This method returns the settings array for the default section (i.e., when the section ID is empty).
	 * Originally, the get_settings() method returned the settings for the "Options" section when the supplied
	 * $current_section was an empty string.
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section() {
		return $this->get_default_settings();
	}

	/**
	 * Get settings for the order status settings section.
	 *
	 * This method returns the settings array for the "Order Status Settings" section.
	 * This replaces the original behavior of get_settings() which returned the "Options" section when the
	 * supplied $current_section was empty.
	 *
	 * @return array
	 */
	protected function get_settings_for_weareplanet_order_statuses_settings_section() {
		return $this->get_order_status_settings();
	}

	/**
	 * Get settings for the add new order status section.
	 *
	 * This method returns the settings array for the "Add New Order Status" section.
	 * This replaces the original behavior of get_settings() which returned the "Options" section when the
	 * supplied $current_section was empty.
	 *
	 * @return array
	 */
	protected function get_settings_for_weareplanet_order_statuses_section() {
		global $current_section, $hide_save_button;
		$hide_save_button = true;
		return $this->get_order_statuses();
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save() {
		$this->save_settings_for_current_section();
		$this->do_update_options_action();
	}

	/**
	 * Update Settings
	 *
	 * @return void
	 * @throws Exception Exception.
	 */
	public function update_settings() {
		WC_WeArePlanet_Helper::instance()->reset_api_client();
		$user_id  = get_option( WooCommerce_WeArePlanet::WEAREPLANET_CK_APP_USER_ID );
		$user_key = get_option( WooCommerce_WeArePlanet::WEAREPLANET_CK_APP_USER_KEY );
		if ( ! ( empty( $user_id ) || empty( $user_key ) ) ) {
			$error_message = '';
			try {
				WC_WeArePlanet_Service_Method_Configuration::instance()->synchronize();
			} catch ( \Exception $e ) {
				WooCommerce_WeArePlanet::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
				WooCommerce_WeArePlanet::instance()->log( $e->getTraceAsString(), WC_Log_Levels::DEBUG );
				$error_message = esc_html__( 'Could not update payment method configuration.', 'woo-weareplanet' );
				WC_Admin_Settings::add_error( $error_message );
			}
			try {
				WC_WeArePlanet_Service_Webhook::instance()->install();
			} catch ( \Exception $e ) {
				WooCommerce_WeArePlanet::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
				WooCommerce_WeArePlanet::instance()->log( $e->getTraceAsString(), WC_Log_Levels::DEBUG );
				$error_message = esc_html__( 'Could not install webhooks, please check if the feature is active in your space.', 'woo-weareplanet' );
				WC_Admin_Settings::add_error( $error_message );
			}
			try {
				WC_WeArePlanet_Service_Manual_Task::instance()->update();
			} catch ( \Exception $e ) {
				WooCommerce_WeArePlanet::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
				WooCommerce_WeArePlanet::instance()->log( $e->getTraceAsString(), WC_Log_Levels::DEBUG );
				$error_message = esc_html__( 'Could not update the manual task list.', 'woo-weareplanet' );
				WC_Admin_Settings::add_error( $error_message );
			}
			try {
				do_action( 'wc_weareplanet_settings_changed' );
			} catch ( \Exception $e ) {
				WooCommerce_WeArePlanet::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
				WooCommerce_WeArePlanet::instance()->log( $e->getTraceAsString(), WC_Log_Levels::DEBUG );
				$error_message = $e->getMessage();
				WC_Admin_Settings::add_error( $error_message );
			}

			if ( wc_tax_enabled() && ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) ) {
				if ( 'yes' === get_option( WooCommerce_WeArePlanet::WEAREPLANET_CK_ENFORCE_CONSISTENCY ) ) {
					$error_message = esc_html__( "'WooCommerce > Settings > WeArePlanet > Enforce Consistency' and 'WooCommerce > Settings > Tax > Rounding' are both enabled. Please disable at least one of them.", 'woo-weareplanet' );
					WC_Admin_Settings::add_error( $error_message );
					WooCommerce_WeArePlanet::instance()->log( $error_message, WC_Log_Levels::ERROR );
				}
			}

			if ( ! empty( $error_message ) ) {
				$error_message = esc_html__( 'Please check your credentials and grant the application user the necessary rights (Account Admin) for your space.', 'woo-weareplanet' );
				WC_Admin_Settings::add_error( $error_message );
			}
			WC_WeArePlanet_Helper::instance()->delete_provider_transients();
		}
	}

	/**
	 * Output Links
	 *
	 * @param mixed $value value.
	 * @return void
	 */
	public function output_links( $value ) {
		foreach ( $value['links'] as $url => $text ) {
			echo '<a href="' . esc_url( $url ) . '" class="page-title-action">' . esc_html( $text ) . '</a>';
		}
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_default_settings() {
		$settings = array(
			array(
				'links' => array(
					'https://plugin-documentation.weareplanet.com/weareplanet/woocommerce/3.3.17/docs/en/documentation.html' => esc_html__( 'Documentation', 'woo-weareplanet' ),
					'https://www.weareplanet.com/contact/sales' => esc_html__( 'Sign Up', 'woo-weareplanet' ),
				),
				'type'  => 'weareplanet_links',
			),

			array(
				'title' => esc_html__( 'General Settings', 'woo-weareplanet' ),
				'desc'  =>
					esc_html__(
						'Enter your application user credentials and space id, if you don\'t have an account already sign up above.',
						'woo-weareplanet'
					),
				'type'  => 'title',
				'id' => 'general_options',
			),

			array(
				'title' => esc_html__( 'Space Id', 'woo-weareplanet' ),
				'id' => WooCommerce_WeArePlanet::WEAREPLANET_CK_SPACE_ID,
				'type' => 'text',
				'css' => 'min-width:300px;',
				'desc' => esc_html__( '(required)', 'woo-weareplanet' ),
			),

			array(
				'title' => esc_html__( 'User Id', 'woo-weareplanet' ),
				'desc_tip' => esc_html__( 'The user needs to have full permissions in the space this shop is linked to.', 'woo-weareplanet' ),
				'id' => WooCommerce_WeArePlanet::WEAREPLANET_CK_APP_USER_ID,
				'type' => 'text',
				'css' => 'min-width:300px;',
				'desc' => esc_html__( '(required)', 'woo-weareplanet' ),
			),

			array(
				'title' => esc_html__( 'Authentication Key', 'woo-weareplanet' ),
				'id' => WooCommerce_WeArePlanet::WEAREPLANET_CK_APP_USER_KEY,
				'type' => 'password',
				'css' => 'min-width:300px;',
				'desc' => esc_html__( '(required)', 'woo-weareplanet' ),
			),

			array(
				'type' => 'sectionend',
				'id' => 'general_options',
			),

			array(
				'title' => esc_html__( 'Email Options', 'woo-weareplanet' ),
				'type' => 'title',
				'id' => 'email_options',
			),

			array(
				'title' => esc_html__( 'Send Order Email', 'woo-weareplanet' ),
				'desc' => esc_html__( 'Enable this option to send order confirmation emails directly from WooCommerce instead of the Portal.', 'woo-weareplanet' ),
				'id' => WooCommerce_WeArePlanet::WEAREPLANET_CK_SHOP_EMAIL,
				'type' => 'checkbox',
				'default' => 'yes',
				'css' => 'min-width:300px;',
			),

			array(
				'type' => 'sectionend',
				'id' => 'email_options',
			),

			array(
				'title' => esc_html__( 'Document Options', 'woo-weareplanet' ),
				'type' => 'title',
				'id' => 'document_options',
			),

			array(
				'title' => esc_html__( 'Invoice Download', 'woo-weareplanet' ),
				'desc' => esc_html__( 'Enable this setting to allow customers to download invoices directly from your shop.', 'woo-weareplanet' ),
				'id' => WooCommerce_WeArePlanet::WEAREPLANET_CK_CUSTOMER_INVOICE,
				'type' => 'checkbox',
				'default' => 'yes',
				'css' => 'min-width:300px;',
			),
			array(
				'title' => esc_html__( 'Packing Slip Download', 'woo-weareplanet' ),
				'desc' => esc_html__( 'Enable this setting to allow customers to download Packing Slip directly from your shop.', 'woo-weareplanet' ),
				'id' => WooCommerce_WeArePlanet::WEAREPLANET_CK_CUSTOMER_PACKING,
				'type' => 'checkbox',
				'default' => 'yes',
				'css' => 'min-width:300px;',
			),

			array(
				'type' => 'sectionend',
				'id' => 'document_options',
			),

			array(
				'title' => esc_html__( 'Space View Options', 'woo-weareplanet' ),
				'type' => 'title',
				'id' => 'space_view_options',
			),

			array(
				'title' => esc_html__( 'Space View Id', 'woo-weareplanet' ),
				'desc_tip' => esc_html__( 'This field allows you to apply custom styling to the payment form and payment page.\nThe styling is defined in your Space settings in the Portal.', 'woo-weareplanet' ),
				'id' => WooCommerce_WeArePlanet::WEAREPLANET_CK_SPACE_VIEW_ID,
				'type' => 'number',
				'css' => 'min-width:300px;',
			),

			array(
				'type' => 'sectionend',
				'id' => 'space_view_options',
			),

			array(
				'title' => esc_html__( 'Integration Options', 'woo-weareplanet' ),
				'type' => 'title',
				'id' => 'integration_options',
			),

			array(
				'title' => esc_html__( 'Integration Type', 'woo-weareplanet' ),
				'desc_tip' => esc_html__(
			    <<<TEXT
				The Integration Options setting determines how the payment form is displayed during the WooCommerce checkout process. Choose the option that best suits your store’s needs:
				
				IFrame: Embeds the payment form directly within the WooCommerce checkout page for a seamless experience.
				
				Lightbox: Opens a secure popup window for customers to complete their payment without leaving the checkout page.
				
				Payment Page: Redirects customers to a dedicated payment page hosted by the payment provider.
				TEXT
				, 'woo-weareplanet' ),
				'id'  => WooCommerce_WeArePlanet::WEAREPLANET_CK_INTEGRATION,
				'type' => 'select',
				'css' => 'min-width:300px;',
				'default' => WC_WeArePlanet_Integration::WEAREPLANET_PAYMENTPAGE,
				'options' => array(
					WC_WeArePlanet_Integration::WEAREPLANET_IFRAME => $this->format_display_string( esc_html__( 'iframe', 'woo-weareplanet' ) ),
					WC_WeArePlanet_Integration::WEAREPLANET_LIGHTBOX  => $this->format_display_string( esc_html__( 'lightbox', 'woo-weareplanet' ) ),
					WC_WeArePlanet_Integration::WEAREPLANET_PAYMENTPAGE => $this->format_display_string( esc_html__( 'payment page', 'woo-weareplanet' ) ),
				),
			),

			array(
				'type' => 'sectionend',
				'id' => 'integration_options',
			),

			array(
				'title' => esc_html__( 'Line Items Options', 'woo-weareplanet' ),
				'type' => 'title',
				'id' => 'line_items_options',
			),

		  array(
			'title' => esc_html__( 'Enforce Consistency', 'woo-weareplanet' ),
			'desc' => esc_html__( 'Enable this setting to require that the sum of all line items matches the order total value.', 'woo-weareplanet' ),
			'desc_tip' => esc_html__(
			  <<<TEXT
WooCommerce calculates taxes at the line-item level, which may result in minor discrepancies (typically a few cents) between the order's total tax and the displayed price. This occurs due to rounding differences during individual line-item calculations.

If the "Enforce consistency" setting is enabled, the portal will automatically reject orders with such discrepancies. To avoid payment processing issues, we recommend disabling this setting unless strict tax total validation is required.
TEXT
			  , 'woo-weareplanet' ),
			'id' => WooCommerce_WeArePlanet::WEAREPLANET_CK_ENFORCE_CONSISTENCY,
			'type' => 'checkbox',
			'default' => 'yes',
			'css' => 'min-width:300px;',
		  ),

			array(
				'type' => 'sectionend',
				'id' => 'line_items_options',
			),

			array(
				'title' => esc_html__( 'Reference Options', 'woo-weareplanet' ),
				'type' => 'title',
				'id' => 'reference_options',
			),

			array(
				'title' => esc_html__( 'Order Reference Type', 'woo-weareplanet' ),
				'desc_tip' => esc_html__(
				  'Choose how orders are uniquely identified when sent to the portal. This reference ensures orders can be tracked and reconciled between systems. We recommend to use Order ID unless your workflow requires custom identifiers.',
				  'woo-weareplanet'
				),
				'id' => WooCommerce_WeArePlanet::WEAREPLANET_CK_ORDER_REFERENCE,
				'type' => 'select',
				'css' => 'min-width:300px;',
				'default' => WC_WeArePlanet_Order_Reference::WEAREPLANET_ORDER_ID,
				'options' => array(
					WC_WeArePlanet_Order_Reference::WEAREPLANET_ORDER_ID => $this->format_display_string( esc_html__( 'order_id', 'woo-weareplanet' ) ),
					WC_WeArePlanet_Order_Reference::WEAREPLANET_ORDER_NUMBER  => $this->format_display_string( esc_html__( 'order_number', 'woo-weareplanet' ) ),
				),
			),

			array(
				'type' => 'sectionend',
				'id' => 'reference_options',
			),

			array(
				'title' => esc_html__( 'Virtual Order Status Options', 'woo-weareplanet' ),
				'type' => 'title',
				'id' => 'order_status_options',
			),

			array(
				'title' => esc_html__( 'Change Virtual Order Status On Completion', 'woo-weareplanet' ),
				'desc' => esc_html__( 'Enable this setting to automatically set order\'s status to "Completed" when they consist entirely out of virtual products and order\'s total is 0.', 'woo-weareplanet' ),
				'id' => WooCommerce_WeArePlanet::WEAREPLANET_CK_CHANGE_ORDER_STATUS,
				'type' => 'checkbox',
				'default' => 'yes',
				'css' => 'min-width:300px;',
			),

			array(
				'type' => 'sectionend',
				'id' => 'order_status_options',
			),
		);

		return apply_filters( 'weareplanet_settings', $settings );
	}

	/**
	 * Returns the configuration for the Order Status section.
	 *
	 * @return array
	 */
	public function get_order_status_settings() {
		$settings = array(
			array(
				'title' => __( 'Order Status Settings', 'woo-weareplanet' ),
				'type' => 'title',
				'id' => 'order_status_mapping_options',
				'desc' => __( 'Map WooCommerce Order Statuses to WeArePlanet Transaction Statuses to ensure seamless integration and consistent order tracking across both platforms.', 'woo-weareplanet' ) . '
						<table class="form-table" style="width: 100%;">
							<thead>
								<tr style="">
									<th scope="row" class="titledesc">' . __( 'WeArePlanet payment status', 'woo-weareplanet' ) . '</th>
									<th class="forminp forminp-select" style="width: 100%;"><label style="margin: 0 10px;">' . __( 'WooCommerce Order Status', 'woo-weareplanet' ) . '</label></th>
								</tr>
							</thead>
						</table>',
			),
		);

		$woocommerce_statuses = apply_filters( 'weareplanet_woocommerce_statuses', array() );
		$weareplanet_statuses = apply_filters( 'weareplanet_order_statuses', array() );
		$default_mappings = apply_filters( 'weareplanet_default_order_status_mappings', array() );

		foreach ( $weareplanet_statuses as $status_key => $status_label ) {
			$default_mapped_status = isset( $default_mappings[ $status_key ] ) ? $default_mappings[ $status_key ] : '';

			$settings[] = array(
				'title' => __( $status_label, 'woo-weareplanet' ), // phpcs:ignore
				'id' => WC_WeArePlanet_Order_Status_Adapter::WEAREPLANET_ORDER_STATUS_MAPPING_PREFIX . $status_key,
				'type' => 'select',
				'options' => array_map( function ( $status ) {
						return __( $status, 'woo-weareplanet' ); // phpcs:ignore
					},
					$woocommerce_statuses
				),
				/* translators: %s: replaces string */
				'default' => sprintf( __( '%s', 'woo-weareplanet' ), $default_mapped_status ), // phpcs:ignore
				'desc' => sprintf( __( 'Set a custom WooCommerce order status to be applied automatically when a transaction is in the %s state.', 'woo-weareplanet' ), strtolower( __( $status_label, 'woo-weareplanet' ) ) ), // phpcs:ignore
			);
		}

		$settings[] = array(
			'type' => 'sectionend',
			'id' => 'status_mapping_options',
		);

		return apply_filters( 'weareplanet_custom_order_statuses_settings', $settings );
	}

	/**
	 * Output order statuses section.
	 */
	public function get_order_statuses() {
		$settings = array(
			array( 'type' => 'weareplanet_order_statuses_table' ),
			array(
				'type' => 'sectionend',
				'id'   => 'order_statuses_section',
			),
		);

		return apply_filters( 'weareplanet_custom_order_statuses', $settings );
	}

	/**
	 * Output order statuses section.
	 */
	public function order_statuses_table() {
		// Extendable columns to show on the order statuses screen.
		$order_statuses_columns = apply_filters(
			'weareplanet_order_statuses_columns',
			array(
				'weareplanet-order-status-key' => __( 'Status key', 'woo-weareplanet' ),
				'weareplanet-order-status-label' => __( 'Label', 'woo-weareplanet' ),
				'weareplanet-order-status-type' => __( 'Type', 'woo-weareplanet' ),
			)
		);

		require_once WC_WEAREPLANET_ABSPATH . '/views/admin-settings/html-admin-page-order-statuses.php';
	}

	/**
	 * Format Display String
	 *
	 * @param string $display_string display string.
	 * @return string
	 */
	private function format_display_string( $display_string ) {
		return ucwords( str_replace( '_', ' ', $display_string ) );
	}


	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		global $current_section;

		$version = WC_WEAREPLANET_REQUIRED_WC_MAXIMUM_VERSION;

		// Register scripts.
		wp_register_script(
			'weareplanet-order-statuses',
			WooCommerce_WeArePlanet::instance()->plugin_url() . '/assets/js/admin/order-statuses.js',
			array(
				'jquery',
				'wp-util',
				'underscore',
				'backbone',
				'wc-backbone-modal'
			),
			$version,
			array( 'in_footer' => false )
		);
		wp_enqueue_script( 'weareplanet-order-statuses' );

		$localized_object = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'statuses' => WC_WeArePlanet_Helper::instance()->get_woocommerce_order_statuses_json(),
			'default_order_status' => array(
				'key' => '',
				'label' => '',
				'type' => '',
			),
			'weareplanet_order_statuses_nonce' => wp_create_nonce( 'weareplanet_order_statuses_nonce' ),
			'strings' => array(
				'custom_order_status_prefix' => WC_WeArePlanet_Order_Status_Adapter::WEAREPLANET_CUSTOM_ORDER_STATUS_PREFIX,
				'unload_confirmation_msg' => __( 'Your changed data will be lost if you leave this page without saving.', 'woo-weareplanet' ),
				'save_failed' => __( 'Your changes were not saved. Please retry.', 'woo-weareplanet' ),
				'delete_confirmation_msg' => __( 'Are you sure you want to delete this custom status?', 'woo-weareplanet' ),
				'characters_remaining' => __( 'characters remaining', 'woo-weareplanet' ),
			),
		);
		wp_localize_script(
			'weareplanet-order-statuses',
			'weareplanetOrderStatusesLocalizeScript',
			$localized_object
		);
	}
}
