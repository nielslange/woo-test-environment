<?php
/**
 * WooCommerce_Blocks_Testing_Environment
 *
 * @package WordPress
 */

/**
 * Bail early when WP_CLI is not active.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Manage WooCommerce Blocks testing environment.
 */
class WooCommerce_Blocks_Testing_Environment extends WP_CLI_Command {
	/**
	 * Set up WooCommerce Blocks Testing Environment.
	 */
	public function setup() {
		// Bail early when running command within a multisite.
		if ( is_multisite() ) {
			WP_CLI::error( 'Multisite is not supported!' );
		}

		WP_CLI::log( 'Set up WooCommerce Blocks Testing Environment ...' );

		$this->setUpPlugins();
		$this->setUpThemes();
		$this->importProducts();
		$this->setUpPages();
		$this->setUpPosts();
		$this->setUpTermsPages();
		$this->setUpShipping();
		$this->setUpPayments();

		WP_CLI::success( 'WooCommerce Blocks Testing Environment successfully set up.' );
	}

	/**
	 * Set up plugins.
	 */
	private function setUpPlugins() {
		// WP_CLI::runcommand('plugin install wordpress-importer --activate');
	}

	/**
	 * Set up themes.
	 */
	private function setUpThemes() {
		// WP_CLI::runcommand('theme install storefront --activate');
		// WP_CLI::runcommand('theme install tt1-blocks');
	}

	/**
	 * Import products.
	 */
	private function importProducts() {
		// WP_CLI::runcommand('import wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=skip');
	}

	/**
	 * Set up pages (cart & checkout block).
	 */
	private function setUpPages() {
		// WP_CLI::runcommand();
	}

	/**
	 * Set up posts (all other blocks).
	 */
	private function setUpPosts() {
		// WP_CLI::runcommand();
	}

	/**
	 * Set up terms pages.
	 */
	private function setUpTermsPages() {
		// WP_CLI::runcommand();
	}

	/**
	 * Set up shipping.
	 */
	private function setUpShipping() {
		// WP_CLI::runcommand();
	}

	/**
	 * Set up payments.
	 */
	private function setUpPayments() {
		// WP_CLI::runcommand();
	}

	/**
	 * Tear down WooCommerce Blocks Testing Environment.
	 */
	public function teardown() {
		WP_CLI::log( 'Tear down WooCommerce Blocks Testing Environment ...' );
		WP_CLI::success( 'WooCommerce Blocks Testing Environment successfully teared down.' );
	}
}

WP_CLI::add_command( 'woo-test-environment', 'WooCommerce_Blocks_Testing_Environment' );
