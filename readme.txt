=== WeArePlanet ===
Contributors: Planet Merchant Services Ltd
Tags: woocommerce WeArePlanet, woocommerce, WeArePlanet, payment, e-commerce, webshop, psp, invoice, packing slips, pdf, customer invoice, processing
Requires at least: 4.7
Tested up to: 6.7
Stable tag: 3.3.17
License: Apache-2.0
License URI: http://www.apache.org/licenses/LICENSE-2.0

Accept payments in WooCommerce with WeArePlanet.

== Description ==

Website: [https://www.weareplanet.com/](https://www.weareplanet.com/)

The plugin offers an easy and convenient way to accept credit cards and all
other payment methods listed below fast and securely. The payment forms will be fully integrated in your checkout
and for credit cards there is no redirection to a payment page needed anymore. The pages are by default mobile optimized but
the look and feel can be changed according the merchants needs.

This plugin will add support for all WeArePlanet payments methods and connect the WeArePlanet servers to your WooCommerce webshop.
To use this extension, a WeArePlanet account is required. Sign up on [WeArePlanet](https://www.weareplanet.com/contact/sales).

== Documentation ==

Additional documentation for this plugin is available [here](https://plugin-documentation.weareplanet.com/weareplanet/woocommerce/3.3.17/docs/en/documentation.html).

== External Services ==

This plugin includes an internal script to manage device verification within the WooCommerce store environment. 

The script helps ensure session consistency and transaction security.

- **Service Name:** WeArePlanet Device Verification Script
- **Purpose:** To track device sessions and enhance security during checkout and payment processing.
- **Data Sent:**
  - **Cookie Name:** `wc_whitelabelname_device_id`
  - **Data Stored in Cookie:** A unique device identifier (hashed value).
  - **When the Cookie is Set:** The cookie is set when the checkout page is accessed and updated during payment processing.
  - **Where the Data is Processed:** All operations occur locally within the WooCommerce store and are not transmitted to external services.
- **Conditions for Use:** The cookie is only set if the customer initiates a checkout session.

No personal data is sent to third-party services; all information remains within the WooCommerce store for internal verification purposes.

== Support ==

Support queries can be issued on the [WeArePlanet support site](https://paymentshub.weareplanet.com/space/select?target=/support).

== Privacy Policy ==

Enquiries about our privacy policy can be made on the [WeArePlanet privacy policies site](https://www.weareplanet.com/privacy-policy).

== Terms of use ==

Enquiries about our terms of use can be made on the [WeArePlanet terms of use site](https://www.datatrans.ch/en/terms-conditions).

== Installation ==

= Minimum Requirements =

* PHP version 5.6 or greater
* WordPress 4.7 up to 6.6
* WooCommerce 3.0.0 up to 9.8.5

= Automatic installation =

1. Install the plugin via Plugins -> New plugin. Search for 'WeArePlanet'.
2. Activate the 'WeArePlanet' plugin through the 'Plugins' menu in WordPress
3. Set your WeArePlanet credentials at WooCommerce -> Settings -> WeArePlanet (or use the *Settings* link in the Plugins overview)
4. You're done, the active payment methods should be visible in the checkout of your webshop.

= Manual installation =

1. Unpack the downloaded package.
2. Upload the directory to the `/wp-content/plugins/` directory
3. Activate the 'WeArePlanet' plugin through the 'Plugins' menu in WordPress
4. Set your credentials at WooCommerce -> Settings -> WeArePlanet (or use the *Settings* link in the Plugins overview)
5. You're done, the active payment methods should be visible in the checkout of your webshop.


== Changelog ==


= 3.3.17 - July 30th 2025 =
- [Bugfix] Fix issue with calling a renamed function
- [Tested Against] PHP 8.2
- [Tested Against] Wordpress 6.7
- [Tested Against] Woocommerce 10.0.4
- [Tested Against] PHP SDK 4.8.0
