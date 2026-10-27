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
 * Backwards compatibility stub for plugin versions up to 3.4.7.
 *
 * Up to 3.4.7 the whitelabel packages were post processed by whitelabel_release.sh, which stripped the
 * WC_ prefix from every class and the wc- prefix from every file name. From 3.4.8 on the packages keep
 * the prefixes, so WeArePlanet_Service_Manual_Task and the file it used to live in are gone.
 *
 * WordPress runs the old plugin code from memory while the new files are already on disk, and
 * WooCommerce_WeArePlanet::migrate_plugin_data_on_activation() of those old versions reads
 * WeArePlanet_Service_Manual_Task::WEAREPLANET_CONFIG_KEY. The class is autoloaded,
 * not eagerly required, so without this file the autoloader of the old version finds nothing and the
 * update dies with "Class WeArePlanet_Service_Manual_Task not found", leaving the shop behind
 * a stale .maintenance file.
 *
 * This stub only has to answer the few calls the old code can still make during that single request.
 * It is never loaded by this version: the current autoloader only resolves wc_weareplanet*
 * classes. Do not rename or remove it while merchants can still update from 3.4.7 or older.
 *
 * It deliberately sits in includes/ and not in includes/service/, where the real service class lives.
 * The autoloaders up to 3.4.7 look in includes/service/ first and fall back to includes/, so both
 * places work for them, but whitelabel_release.sh (still wired into the packagePlugin gradle task)
 * renames includes/service/class-wc-<brand>-service-manual-task.php to exactly the name this stub
 * would carry. In includes/ the two can never overwrite each other.
 *
 * Never require this file directly. In a package whose prefixes were stripped, includes/service/
 * holds the real service class under the very same class name, and only the autoloader, which looks
 * at includes/service/ first, keeps the real one winning over this stub.
 *
 * @deprecated Only kept to survive an update from 3.4.7 or older.
 */
// The second argument keeps class_exists() from asking the autoloader for a class this file is about
// to declare itself: the only question a declaration guard has is whether it is already in memory.
if ( ! class_exists( 'WeArePlanet_Service_Manual_Task', false ) ) {
	class WeArePlanet_Service_Manual_Task {

		/**
		 * Option that holds the number of open manual tasks, as it was named up to 3.4.7.
		 */
		const WEAREPLANET_CONFIG_KEY = 'weareplanet_manual_task';

		/**
		 * The single instance of the class.
		 *
		 * @var WeArePlanet_Service_Manual_Task
		 */
		private static $instance = null;

		/**
		 * Returns the instance of this stub.
		 *
		 * @return WeArePlanet_Service_Manual_Task
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Returns the number of open manual tasks.
		 *
		 * @return int
		 */
		public function get_number_of_manual_tasks() {
			return (int) get_option( self::WEAREPLANET_CONFIG_KEY, 0 );
		}

		/**
		 * Updating the manual tasks needs the API client of the running version, so this does nothing.
		 * The current version refreshes the count on its next webhook or settings save.
		 *
		 * @return array
		 */
		public function update() {
			return array();
		}
	}
}
