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
	 * Product attribute IDs.
	 *
	 * @var array $attribute_ids An array that contains the product attribute IDs.
	 */
	private $attribute_ids = array();

	/**
	 * Set up WooCommerce Blocks Testing Environment.
	 *
	 * ## OPTIONS
	 *
	 * [--blocks[=<version|url>]]
	 * : The desired WooCommerce Blocks version to install.
	 * --blocks: will install the latest version.
	 * --blocks=<version>: will install the specified plugin version.
	 * --blocks=<url>: will install the plugin from the specified URL.
	 *
	 * [--gutenberg]
	 * : Whether to install and activate the Gutenberg plugin.
	 *
	 * [--theme=<theme>]
	 * : The desired WordPress theme to install and activate.
	 *
	 * [--stripe]
	 * : Whether to install, activate and configure the Stripe plugin.
	 *
	 * ## EXAMPLES
	 *
	 *    # Installing WooCommerce only
	 *    $ wp woo-test-environment setup
	 *
	 *    # Installing WooCommerce and WooCommerce Blocks
	 *    $ wp woo-test-environment setup --blocks
	 *
	 *    # Installing WooCommerce, WooCommerce Blocks and Gutenberg
	 *    $ wp woo-test-environment setup --blocks --gutenberg
	 *
	 *    # Installing WooCommerce and WooCommerce Blocks 7.3.0
	 *    $ wp woo-test-environment setup --blocks=7.3.0
	 *
	 *    # Installing WooCommerce and WooCommerce Blocks via URL
	 *    $ wp woo-test-environment setup --blocks=https://github.com/woocommerce/woocommerce-blocks/releases/download/v7.8.2/woo-gutenberg-products-block.zip
	 *
	 *    # Installing WooCommerce and Storefront
	 *    $ wp woo-test-environment setup --theme=storefront
	 *
	 *    # Installing WooCommerce and Stripe
	 *    $ wp woo-test-environment setup --stripe
	 *
	 *    # Installing WooCommerce, WooCommerce Blocks, Gutenberg, Stripe and Storefront
	 *    $ wp woo-test-environment setup --blocks --gutenberg --stripe --theme=storefront
	 *
	 * @param array $args An array with optional arguments.
	 * @param array $assoc_args An array with optional arguments.
	 *
	 * @return void
	 */
	public function setup( array $args, array $assoc_args ) {
		// Bail early when running command within a multisite.
		if ( is_multisite() ) {
			WP_CLI::error( 'Multisite is not supported!' );
		}

		WP_CLI::log( 'Set up WooCommerce Blocks Testing Environment ...' );

		$this->setupPlugins( $assoc_args );
		$this->setupThemes( $assoc_args );
		$this->emptySite();
		$this->setupProducts();
		$this->setupPages();
		$this->setupPosts();
		$this->setupSidebar();
		$this->setupShipping();
		$this->setupPayments( $assoc_args );
		$this->setupTax();
		$this->setupCoupons();
		$this->setupReviews();
		$this->setupPermalinks();

		WP_CLI::success( 'WooCommerce Blocks Testing Environment successfully set up.' );
	}

	/**
	 * Tear down WooCommerce Blocks Testing Environment.
	 *
	 * @return void
	 */
	public function teardown() {
		WP_CLI::log( 'Tear down WooCommerce Blocks Testing Environment ...' );

		$this->tearDownTax();
		$this->tearDownShipping();
		$this->tearDownPlugins();
		$this->tearDownThemes();
		$this->emptySite();

		WP_CLI::success( 'WooCommerce Blocks Testing Environment successfully teared down.' );
	}

	/**
	 * Empty site.
	 *
	 * @return void
	 */
	private function emptySite() {
		WP_CLI::runcommand( 'site empty --yes' );
	}

	/**
	 * Set up plugins.
	 *
	 * @param array $assoc_args An array with optional arguments.
	 *
	 * @return void
	 */
	private function setupPlugins( $assoc_args ) {
		if ( ! isWcBetaTesterPluginActive() ) {
			WP_CLI::runcommand( 'plugin install woocommerce --activate' );
		}

		WP_CLI::runcommand( 'plugin install wordpress-importer --activate' );

		if ( isset( $assoc_args['gutenberg'] ) ) {
			WP_CLI::runcommand( 'plugin install gutenberg --activate' );
		}

		$this->installWoocommerceBlocksPlugin( $assoc_args );
	}

	/**
	 * Tear down plugins.
	 *
	 * @return void
	 */
	private function tearDownPlugins() {
		WP_CLI::runcommand( 'plugin deactivate --all --uninstall' );
	}

	/**
	 * Set up themes.
	 *
	 * @param array $assoc_args An array with optional arguments.
	 *
	 * @return void
	 */
	private function setupThemes( $assoc_args ) {
		// Install and activate a certain WordPress theme, if desired.
		if ( isset( $assoc_args['theme'] ) ) {
			WP_CLI::runcommand( "theme install {$assoc_args['theme']} --activate" );
		}
	}

	/**
	 * Tear down themes.
	 *
	 * @return void
	 */
	private function tearDownThemes() {
		WP_CLI::runcommand( 'theme activate twentytwentyfour' );
		WP_CLI::runcommand( 'theme delete --all' );
	}

	/**
	 * Import products.
	 *
	 * @return void
	 */
	private function setupProducts() {
		$options = array(
			'return' => true,
			'parse'  => 'json',
		);
		$results = WP_CLI::runcommand( 'wc product_attribute list --format=json --user=1', $options );

		if ( empty( $results ) ) {
			$this->attribute_ids['pa_color'] = WP_CLI::runcommand( 'wc product_attribute create --name=Color --slug=pa_color --user=1 --porcelain', $options );
			$this->attribute_ids['pa_size']  = WP_CLI::runcommand( 'wc product_attribute create --name=Size --slug=pa_size --user=1 --porcelain', $options );
		}

		WP_CLI::runcommand( 'import wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=skip' );
		WP_CLI::runcommand( 'wc tool run regenerate_product_lookup_tables --user=1' );
	}

	/**
	 * Set up pages (cart & checkout block).
	 *
	 * @return void
	 */
	private function setupPages() {
		// Create shop page with Products (Beta) block.
		WP_CLI::runcommand( 'post create --menu_order=0 --post_type=page --post_status=publish --post_title=\'Products (Beta)\' --post_content=\'<!-- wp:columns {"align":"wide"} --><div class="wp-block-columns alignwide"><!-- wp:column {"width":"33.33%"} --><div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:woocommerce/filter-wrapper {"filterType":"active-filters","heading":"Active filters"} --><div class="wp-block-woocommerce-filter-wrapper"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Active filters</h3><!-- /wp:heading --><!-- wp:woocommerce/active-filters {"heading":"","lock":{"remove":true}} --><div class="wp-block-woocommerce-active-filters is-loading"><span aria-hidden="true" class="wc-block-active-filters__placeholder"></span></div><!-- /wp:woocommerce/active-filters --></div><!-- /wp:woocommerce/filter-wrapper --><!-- wp:woocommerce/filter-wrapper {"filterType":"stock-filter"} --><div class="wp-block-woocommerce-filter-wrapper"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Filter by stock status</h3><!-- /wp:heading --><!-- wp:woocommerce/stock-filter {"showCounts":true,"heading":"","lock":{"remove":true}} --><div class="wp-block-woocommerce-stock-filter is-loading"></div><!-- /wp:woocommerce/stock-filter --></div><!-- /wp:woocommerce/filter-wrapper --><!-- wp:woocommerce/filter-wrapper {"filterType":"price-filter"} --><div class="wp-block-woocommerce-filter-wrapper"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Filter by price</h3><!-- /wp:heading --><!-- wp:woocommerce/price-filter {"heading":"","lock":{"remove":true}} --><div class="wp-block-woocommerce-price-filter is-loading"><span aria-hidden="true" class="wc-block-product-categories__placeholder"></span></div><!-- /wp:woocommerce/price-filter --></div><!-- /wp:woocommerce/filter-wrapper --><!-- wp:woocommerce/filter-wrapper {"filterType":"rating-filter"} --><div class="wp-block-woocommerce-filter-wrapper"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Filter by rating</h3><!-- /wp:heading --><!-- wp:woocommerce/rating-filter {"showCounts":true,"lock":{"remove":true}} --><div class="wp-block-woocommerce-rating-filter is-loading"></div><!-- /wp:woocommerce/rating-filter --></div><!-- /wp:woocommerce/filter-wrapper --><!-- wp:woocommerce/filter-wrapper {"filterType":"attribute-filter","heading":"Filter by attribute"} --><div class="wp-block-woocommerce-filter-wrapper"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Filter by attribute</h3><!-- /wp:heading --><!-- wp:woocommerce/attribute-filter {"attributeId":7,"heading":"","lock":{"remove":true}} --><div class="wp-block-woocommerce-attribute-filter is-loading"></div><!-- /wp:woocommerce/attribute-filter --></div><!-- /wp:woocommerce/filter-wrapper --><!-- wp:woocommerce/filter-wrapper {"filterType":"attribute-filter","heading":"Filter by attribute"} --><div class="wp-block-woocommerce-filter-wrapper"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Filter by attribute</h3><!-- /wp:heading --><!-- wp:woocommerce/attribute-filter {"attributeId":8,"heading":"","lock":{"remove":true}} --><div class="wp-block-woocommerce-attribute-filter is-loading"></div><!-- /wp:woocommerce/attribute-filter --></div><!-- /wp:woocommerce/filter-wrapper --></div><!-- /wp:column --><!-- wp:column {"width":"66.66%"} --><div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:query {"queryId":1,"query":{"perPage":"8","pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":false,"__woocommerceAttributes":[],"__woocommerceStockStatus":["instock","outofstock","onbackorder"]},"namespace":"woocommerce/product-query"} --><div class="wp-block-query"><!-- wp:post-template {"className":"products-block-post-template","layout":{"type":"grid","columnCount":4},"__woocommerceNamespace":"woocommerce/product-query/product-template"} --><!-- wp:woocommerce/product-image {"imageSizing":"thumbnail","isDescendentOfQueryLoop":true} /--><!-- wp:post-title {"textAlign":"center","level":3,"isLink":true,"style":{"spacing":{"margin":{"bottom":"0.75rem","top":"0"}}},"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-query/product-title"} /--><!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small"} /--><!-- wp:woocommerce/product-button {"textAlign":"center","isDescendentOfQueryLoop":true,"fontSize":"small"} /--><!-- /wp:post-template --><!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} --><!-- wp:query-pagination-previous /--><!-- wp:query-pagination-numbers /--><!-- wp:query-pagination-next /--><!-- /wp:query-pagination --><!-- wp:query-no-results --><!-- wp:paragraph {"placeholder":"Add text or blocks that will display when a query returns no results."} --><p></p><!-- /wp:paragraph --><!-- /wp:query-no-results --></div><!-- /wp:query --></div><!-- /wp:column --></div><!-- /wp:columns -->\'' );

		// Create (default) Shop page.
		WP_CLI::runcommand( 'post create --post_type=page --post_status=publish --post_title=\'Classic Shop\' --post_parent=$(wp post list --title="Products (Beta)" --post_type=page --field=ID)' );
		WP_CLI::runcommand( 'option update woocommerce_shop_page_id $(wp post list --title="Classic Shop" --post_type=page --field=ID)' );

		// Create page with the All Products block.
		WP_CLI::runcommand( 'post create --post_type=page --post_status=publish --post_title=\'All Products\' --post_parent=$(wp post list --title="Products (Beta)" --post_type=page --field=ID) --post_content=\'<!-- wp:columns {"align":"wide"} --><div class="wp-block-columns alignwide"><!-- wp:column {"width":"33.33%"} --><div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:woocommerce/filter-wrapper {"filterType":"active-filters","heading":"Active filters"} --><div class="wp-block-woocommerce-filter-wrapper"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Active filters</h3><!-- /wp:heading --><!-- wp:woocommerce/active-filters {"heading":"","lock":{"remove":true}} --><div class="wp-block-woocommerce-active-filters is-loading"><span aria-hidden="true" class="wc-block-active-filters__placeholder"></span></div><!-- /wp:woocommerce/active-filters --></div><!-- /wp:woocommerce/filter-wrapper --><!-- wp:woocommerce/filter-wrapper {"filterType":"stock-filter","heading":"Filter by stock status"} --><div class="wp-block-woocommerce-filter-wrapper"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Filter by stock status</h3><!-- /wp:heading --><!-- wp:woocommerce/stock-filter {"heading":"","lock":{"remove":true}} --><div class="wp-block-woocommerce-stock-filter is-loading"></div><!-- /wp:woocommerce/stock-filter --></div><!-- /wp:woocommerce/filter-wrapper --><!-- wp:woocommerce/filter-wrapper {"filterType":"price-filter","heading":"Filter by price"} --><div class="wp-block-woocommerce-filter-wrapper"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Filter by price</h3><!-- /wp:heading --><!-- wp:woocommerce/price-filter {"heading":"","lock":{"remove":true}} --><div class="wp-block-woocommerce-price-filter is-loading"><span aria-hidden="true" class="wc-block-product-categories__placeholder"></span></div><!-- /wp:woocommerce/price-filter --></div><!-- /wp:woocommerce/filter-wrapper --><!-- wp:woocommerce/filter-wrapper {"filterType":"rating-filter","heading":"Filter by rating"} --><div class="wp-block-woocommerce-filter-wrapper"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Filter by rating</h3><!-- /wp:heading --><!-- wp:woocommerce/rating-filter {"showCounts":true,"lock":{"remove":true}} --><div class="wp-block-woocommerce-rating-filter is-loading"></div><!-- /wp:woocommerce/rating-filter --></div><!-- /wp:woocommerce/filter-wrapper --><!-- wp:woocommerce/filter-wrapper {"filterType":"attribute-filter","heading":"Filter by attribute"} --><div class="wp-block-woocommerce-filter-wrapper"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Filter by attribute</h3><!-- /wp:heading --><!-- wp:woocommerce/attribute-filter {"attributeId":7,"heading":"","lock":{"remove":true}} --><div class="wp-block-woocommerce-attribute-filter is-loading"></div><!-- /wp:woocommerce/attribute-filter --></div><!-- /wp:woocommerce/filter-wrapper --><!-- wp:woocommerce/filter-wrapper {"filterType":"attribute-filter","heading":"Filter by attribute"} --><div class="wp-block-woocommerce-filter-wrapper"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Filter by attribute</h3><!-- /wp:heading --><!-- wp:woocommerce/attribute-filter {"attributeId":8,"heading":"","lock":{"remove":true}} --><div class="wp-block-woocommerce-attribute-filter is-loading"></div><!-- /wp:woocommerce/attribute-filter --></div><!-- /wp:woocommerce/filter-wrapper --></div><!-- /wp:column --><!-- wp:column {"width":"66.66%"} --><div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:woocommerce/all-products {"columns":4,"rows":4,"alignButtons":false,"contentVisibility":{"orderBy":true},"orderby":"date","layoutConfig":[["woocommerce/product-image",{"imageSizing":"thumbnail"}],["woocommerce/product-title"],["woocommerce/product-price"],["woocommerce/product-rating"],["woocommerce/product-button"]]} --><div class="wp-block-woocommerce-all-products wc-block-all-products" data-attributes="{&quot;alignButtons&quot;:false,&quot;columns&quot;:4,&quot;contentVisibility&quot;:{&quot;orderBy&quot;:true},&quot;isPreview&quot;:false,&quot;layoutConfig&quot;:[[&quot;woocommerce/product-image&quot;,{&quot;imageSizing&quot;:&quot;thumbnail&quot;}],[&quot;woocommerce/product-title&quot;],[&quot;woocommerce/product-price&quot;],[&quot;woocommerce/product-rating&quot;],[&quot;woocommerce/product-button&quot;]],&quot;orderby&quot;:&quot;date&quot;,&quot;rows&quot;:4}"></div><!-- /wp:woocommerce/all-products --></div><!-- /wp:column --></div><!-- /wp:columns -->\'' );

		// Create page with the Cart block.
		WP_CLI::runcommand( 'post create --menu_order=1 --post_type=page --post_status=publish --post_title=\'Cart\' --post_content=\'<!-- wp:woocommerce/cart --><div class="wp-block-woocommerce-cart is-loading"><!-- wp:woocommerce/filled-cart-block --><div class="wp-block-woocommerce-filled-cart-block"><!-- wp:woocommerce/cart-items-block --><div class="wp-block-woocommerce-cart-items-block"><!-- wp:woocommerce/cart-line-items-block --><div class="wp-block-woocommerce-cart-line-items-block"></div><!-- /wp:woocommerce/cart-line-items-block --></div><!-- /wp:woocommerce/cart-items-block --><!-- wp:woocommerce/cart-totals-block --><div class="wp-block-woocommerce-cart-totals-block"><!-- wp:woocommerce/cart-order-summary-block --><div class="wp-block-woocommerce-cart-order-summary-block"></div><!-- /wp:woocommerce/cart-order-summary-block --><!-- wp:woocommerce/cart-express-payment-block --><div class="wp-block-woocommerce-cart-express-payment-block"></div><!-- /wp:woocommerce/cart-express-payment-block --><!-- wp:woocommerce/proceed-to-checkout-block --><div class="wp-block-woocommerce-proceed-to-checkout-block"></div><!-- /wp:woocommerce/proceed-to-checkout-block --><!-- wp:woocommerce/cart-accepted-payment-methods-block --><div class="wp-block-woocommerce-cart-accepted-payment-methods-block"></div><!-- /wp:woocommerce/cart-accepted-payment-methods-block --></div><!-- /wp:woocommerce/cart-totals-block --></div><!-- /wp:woocommerce/filled-cart-block --><!-- wp:woocommerce/empty-cart-block --><div class="wp-block-woocommerce-empty-cart-block"><!-- wp:image {"align":"center","sizeSlug":"small"} --><div class="wp-block-image"><figure class="aligncenter size-small"><img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzgiIGhlaWdodD0iMzgiIHZpZXdCb3g9IjAgMCAzOCAzOCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTE5IDBDOC41MDQwMyAwIDAgOC41MDQwMyAwIDE5QzAgMjkuNDk2IDguNTA0MDMgMzggMTkgMzhDMjkuNDk2IDM4IDM4IDI5LjQ5NiAzOCAxOUMzOCA4LjUwNDAzIDI5LjQ5NiAwIDE5IDBaTTI1LjEyOSAxMi44NzFDMjYuNDg1MSAxMi44NzEgMjcuNTgwNiAxMy45NjY1IDI3LjU4MDYgMTUuMzIyNkMyNy41ODA2IDE2LjY3ODYgMjYuNDg1MSAxNy43NzQyIDI1LjEyOSAxNy43NzQyQzIzLjc3MyAxNy43NzQyIDIyLjY3NzQgMTYuNjc4NiAyMi42Nzc0IDE1LjMyMjZDMjIuNjc3NCAxMy45NjY1IDIzLjc3MyAxMi44NzEgMjUuMTI5IDEyLjg3MVpNMTEuNjQ1MiAzMS4yNTgxQzkuNjE0OTIgMzEuMjU4MSA3Ljk2Nzc0IDI5LjY0OTIgNy45Njc3NCAyNy42NTczQzcuOTY3NzQgMjYuMTI1IDEwLjE1MTIgMjMuMDI5OCAxMS4xNTQ4IDIxLjY5NjhDMTEuNCAyMS4zNjczIDExLjg5MDMgMjEuMzY3MyAxMi4xMzU1IDIxLjY5NjhDMTMuMTM5MSAyMy4wMjk4IDE1LjMyMjYgMjYuMTI1IDE1LjMyMjYgMjcuNjU3M0MxNS4zMjI2IDI5LjY0OTIgMTMuNjc1NCAzMS4yNTgxIDExLjY0NTIgMzEuMjU4MVpNMTIuODcxIDE3Ljc3NDJDMTEuNTE0OSAxNy43NzQyIDEwLjQxOTQgMTYuNjc4NiAxMC40MTk0IDE1LjMyMjZDMTAuNDE5NCAxMy45NjY1IDExLjUxNDkgMTIuODcxIDEyLjg3MSAxMi44NzFDMTQuMjI3IDEyLjg3MSAxNS4zMjI2IDEzLjk2NjUgMTUuMzIyNiAxNS4zMjI2QzE1LjMyMjYgMTYuNjc4NiAxNC4yMjcgMTcuNzc0MiAxMi44NzEgMTcuNzc0MlpNMjUuOTEwNSAyOS41ODc5QzI0LjE5NDQgMjcuNTM0NyAyMS42NzM4IDI2LjM1NDggMTkgMjYuMzU0OEMxNy4zNzU4IDI2LjM1NDggMTcuMzc1OCAyMy45MDMyIDE5IDIzLjkwMzJDMjIuNDAxNiAyMy45MDMyIDI1LjYxMTcgMjUuNDA0OCAyNy43ODc1IDI4LjAyNUMyOC44NDQ4IDI5LjI4MTUgMjYuOTI5NCAzMC44MjE0IDI1LjkxMDUgMjkuNTg3OVoiIGZpbGw9ImJsYWNrIi8+Cjwvc3ZnPgo=" alt=""/></figure></div><!-- /wp:image --><!-- wp:heading {"textAlign":"center","className":"wc-block-cart__empty-cart__title"} --><h2 class="has-text-align-center wc-block-cart__empty-cart__title">Your cart is currently empty!</h2><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><a href="/shop/">Browse store</a>.</p><!-- /wp:paragraph --><!-- wp:separator {"className":"is-style-dots"} --><hr class="wp-block-separator is-style-dots"/><!-- /wp:separator --><!-- wp:heading {"textAlign":"center"} --><h2 class="has-text-align-center">New in store</h2><!-- /wp:heading --><!-- wp:woocommerce/product-new {"rows":1} /--></div><!-- /wp:woocommerce/empty-cart-block --></div><!-- /wp:woocommerce/cart -->\'' );
		WP_CLI::runcommand( 'option update woocommerce_cart_page_id $(wp post list --title="Cart" --post_type=page --field=ID)' );

		// Create page with the Checkout block.
		WP_CLI::runcommand( 'post create --menu_order=2 --post_type=page --post_status=publish --post_title=\'Checkout\' --post_content=\'<!-- wp:woocommerce/checkout --><div class="wp-block-woocommerce-checkout wc-block-checkout is-loading"><!-- wp:woocommerce/checkout-fields-block --><div class="wp-block-woocommerce-checkout-fields-block"><!-- wp:woocommerce/checkout-express-payment-block --><div class="wp-block-woocommerce-checkout-express-payment-block"></div><!-- /wp:woocommerce/checkout-express-payment-block --><!-- wp:woocommerce/checkout-contact-information-block --><div class="wp-block-woocommerce-checkout-contact-information-block"></div><!-- /wp:woocommerce/checkout-contact-information-block --><!-- wp:woocommerce/checkout-shipping-address-block --><div class="wp-block-woocommerce-checkout-shipping-address-block"></div><!-- /wp:woocommerce/checkout-shipping-address-block --><!-- wp:woocommerce/checkout-billing-address-block --><div class="wp-block-woocommerce-checkout-billing-address-block"></div><!-- /wp:woocommerce/checkout-billing-address-block --><!-- wp:woocommerce/checkout-shipping-methods-block --><div class="wp-block-woocommerce-checkout-shipping-methods-block"></div><!-- /wp:woocommerce/checkout-shipping-methods-block --><!-- wp:woocommerce/checkout-payment-block --><div class="wp-block-woocommerce-checkout-payment-block"></div><!-- /wp:woocommerce/checkout-payment-block --><!-- wp:woocommerce/checkout-order-note-block --><div class="wp-block-woocommerce-checkout-order-note-block"></div><!-- /wp:woocommerce/checkout-order-note-block --><!-- wp:woocommerce/checkout-terms-block --><div class="wp-block-woocommerce-checkout-terms-block"></div><!-- /wp:woocommerce/checkout-terms-block --><!-- wp:woocommerce/checkout-actions-block --><div class="wp-block-woocommerce-checkout-actions-block"></div><!-- /wp:woocommerce/checkout-actions-block --></div><!-- /wp:woocommerce/checkout-fields-block --><!-- wp:woocommerce/checkout-totals-block --><div class="wp-block-woocommerce-checkout-totals-block"><!-- wp:woocommerce/checkout-order-summary-block --><div class="wp-block-woocommerce-checkout-order-summary-block"></div><!-- /wp:woocommerce/checkout-order-summary-block --></div><!-- /wp:woocommerce/checkout-totals-block --></div><!-- /wp:woocommerce/checkout -->\'' );
		WP_CLI::runcommand( 'option update woocommerce_checkout_page_id $(wp post list --title="Checkout" --post_type=page --field=ID)' );

		// Create page with the classic cart shortcode.
		WP_CLI::runcommand( 'post create --post_type=page --post_status=publish --post_title=\'Classic Cart Shortcode\' --post_parent=$(wp post list --title="Cart" --post_type=page --field=ID) --post_content=\'<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->\'' );

		if ( $this->isWoocommerceBlocksActive() ) {
			// Create page with the classic cart block.
			WP_CLI::runcommand( 'post create --post_type=page --post_status=publish --post_title=\'Classic Cart Block\' --post_parent=$(wp post list --title="Cart" --post_type=page --field=ID) --post_content=\'<!-- wp:woocommerce/classic-shortcode /-->\'' );
		}

		// Create page with the classic checkout shortcode.
		WP_CLI::runcommand( 'post create --post_type=page --post_status=publish --post_title=\'Classic Checkout Shortcode\' --post_parent=$(wp post list --title="Checkout" --post_type=page --field=ID) --post_content=\'<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->\'' );

		if ( $this->isWoocommerceBlocksActive() ) {
			// Create page with the classic checkout block.
			WP_CLI::runcommand( 'post create --post_type=page --post_status=publish --post_title=\'Classic Checkout Block\' --post_parent=$(wp post list --title="Checkout" --post_type=page --field=ID) --post_content=\'<!-- wp:woocommerce/classic-shortcode {"shortcode":"checkout"} /-->\'' );
		}

		// Create page with the My Account shortcode.
		WP_CLI::runcommand( 'post create --menu_order=3 --post_type=page --post_status=publish --post_title=\'My Account\' --post_content=\'<!-- wp:shortcode -->[woocommerce_my_account]<!-- /wp:shortcode -->\'' );
		WP_CLI::runcommand( 'option update woocommerce_myaccount_page_id $(wp post list --title="My Account" --post_type=page --field=ID)' );

		// Create Terms page.
		WP_CLI::runcommand( 'post create --menu_order=4 --post_type=page --post_status=publish --post_title=\'Terms\'' );
		WP_CLI::runcommand( 'option update woocommerce_terms_page_id $(wp post list --title="Terms" --post_type=page --field=ID)' );

		// Create Privacy page.
		WP_CLI::runcommand( 'post create --menu_order=3 --post_type=page --post_status=publish --post_title=\'Privacy\'' );
		WP_CLI::runcommand( 'option update wp_page_for_privacy_policy $(wp post list --title="Privacy" --post_type=page --field=ID)' );
	}

	/**
	 * Set up posts (all other blocks).
	 *
	 * @return void
	 */
	private function setupPosts() {
		// Create All Reviews post.
		WP_CLI::runcommand( 'post create --post_status=publish --post_title=\'All Reviews\' --post_content=\'<!-- wp:woocommerce/all-reviews --><div class="wp-block-woocommerce-all-reviews wc-block-all-reviews has-image has-name has-date has-rating has-content has-product-name" data-image-type="reviewer" data-orderby="most-recent" data-reviews-on-page-load="10" data-reviews-on-load-more="10" data-show-load-more="true" data-show-orderby="true"></div><!-- /wp:woocommerce/all-reviews -->\'' );

		// Create Active Product Filters post.
		if ( $this->attribute_ids['pa_color'] && $this->attribute_ids['pa_size'] ) {
			WP_CLI::runcommand(
				'post create --post_status=publish --post_title=\'Active Product Filters\' --post_content=\'<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column {"width":"33.33%"} --><div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:woocommerce/active-filters --><div class="wp-block-woocommerce-active-filters is-loading" data-display-style="list" data-heading="Active filters" data-heading-level="3"><span aria-hidden="true" class="wc-block-active-product-filters__placeholder"></span></div><!-- /wp:woocommerce/active-filters --><!-- wp:woocommerce/price-filter --><div class="wp-block-woocommerce-price-filter is-loading" data-showinputfields="true" data-showfilterbutton="false" data-heading="Filter by price" data-heading-level="3"><span aria-hidden="true" class="wc-block-product-categories__placeholder"></span></div><!-- /wp:woocommerce/price-filter --><!-- wp:woocommerce/attribute-filter {"attributeId":' . $this->attribute_ids['pa_color'] . ',"heading":"Filter by Color"} --><div class="wp-block-woocommerce-attribute-filter is-loading" data-attribute-id="' . $this->attribute_ids['pa_color'] . '" data-show-counts="true" data-query-type="or" data-heading="Filter by Color" data-heading-level="3"><span aria-hidden="true" class="wc-block-product-attribute-filter__placeholder"></span></div><!-- /wp:woocommerce/attribute-filter --><!-- wp:woocommerce/attribute-filter {"attributeId":' . $this->attribute_ids['pa_size'] . ',"heading":"Filter by Size"} --><div class="wp-block-woocommerce-attribute-filter is-loading" data-attribute-id="' . $this->attribute_ids['pa_size'] . '" data-show-counts="true" data-query-type="or" data-heading="Filter by Size" data-heading-level="3"><span aria-hidden="true" class="wc-block-product-attribute-filter__placeholder"></span></div><!-- /wp:woocommerce/attribute-filter --><!-- wp:woocommerce/stock-filter --><div class="wp-block-woocommerce-stock-filter is-loading" data-show-counts="true" data-heading="Filter by stock status" data-heading-level="3"><span aria-hidden="true" class="wc-block-product-stock-filter__placeholder"></span></div><!-- /wp:woocommerce/stock-filter --></div><!-- /wp:column --><!-- wp:column {"width":"66.66%"} --><div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:woocommerce/all-products {"columns":3,"rows":3,"alignButtons":false,"contentVisibility":{"orderBy":true},"orderby":"date","layoutConfig":[["woocommerce/product-image",{"imageSizing":"cropped"}],["woocommerce/product-title"],["woocommerce/product-price"],["woocommerce/product-rating"],["woocommerce/product-button"]]} --><div class="wp-block-woocommerce-all-products wc-block-all-products" data-attributes="{&quot;alignButtons&quot;:false,&quot;columns&quot;:3,&quot;contentVisibility&quot;:{&quot;orderBy&quot;:true},&quot;isPreview&quot;:false,&quot;layoutConfig&quot;:[[&quot;woocommerce/product-image&quot;,{&quot;imageSizing&quot;:&quot;cropped&quot;}],[&quot;woocommerce/product-title&quot;],[&quot;woocommerce/product-price&quot;],[&quot;woocommerce/product-rating&quot;],[&quot;woocommerce/product-button&quot;]],&quot;orderby&quot;:&quot;date&quot;,&quot;rows&quot;:3}"></div><!-- /wp:woocommerce/all-products --></div><!-- /wp:column --></div><!-- /wp:columns -->\''
			);
		} else {
			WP_CLI::runcommand( 'post create --post_status=publish --post_title=\'Active Product Filters\' --post_content=\'<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column {"width":"33.33%"} --><div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:woocommerce/active-filters --><div class="wp-block-woocommerce-active-filters is-loading" data-display-style="list" data-heading="Active filters" data-heading-level="3"><span aria-hidden="true" class="wc-block-active-product-filters__placeholder"></span></div><!-- /wp:woocommerce/active-filters --><!-- wp:woocommerce/price-filter --><div class="wp-block-woocommerce-price-filter is-loading" data-showinputfields="true" data-showfilterbutton="false" data-heading="Filter by price" data-heading-level="3"><span aria-hidden="true" class="wc-block-product-categories__placeholder"></span></div><!-- /wp:woocommerce/price-filter --><!-- wp:woocommerce/stock-filter --><div class="wp-block-woocommerce-stock-filter is-loading" data-show-counts="true" data-heading="Filter by stock status" data-heading-level="3"><span aria-hidden="true" class="wc-block-product-stock-filter__placeholder"></span></div><!-- /wp:woocommerce/stock-filter --></div><!-- /wp:column --><!-- wp:column {"width":"66.66%"} --><div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:woocommerce/all-products {"columns":3,"rows":3,"alignButtons":false,"contentVisibility":{"orderBy":true},"orderby":"date","layoutConfig":[["woocommerce/product-image",{"imageSizing":"cropped"}],["woocommerce/product-title"],["woocommerce/product-price"],["woocommerce/product-rating"],["woocommerce/product-button"]]} --><div class="wp-block-woocommerce-all-products wc-block-all-products" data-attributes="{&quot;alignButtons&quot;:false,&quot;columns&quot;:3,&quot;contentVisibility&quot;:{&quot;orderBy&quot;:true},&quot;isPreview&quot;:false,&quot;layoutConfig&quot;:[[&quot;woocommerce/product-image&quot;,{&quot;imageSizing&quot;:&quot;cropped&quot;}],[&quot;woocommerce/product-title&quot;],[&quot;woocommerce/product-price&quot;],[&quot;woocommerce/product-rating&quot;],[&quot;woocommerce/product-button&quot;]],&quot;orderby&quot;:&quot;date&quot;,&quot;rows&quot;:3}"></div><!-- /wp:woocommerce/all-products --></div><!-- /wp:column --></div><!-- /wp:columns -->\'' );
		}

		// Create Best Selling Products post.
		WP_CLI::runcommand( 'post create --post_status=publish --post_title=\'Best Selling Products\' --post_content=\'<!-- wp:woocommerce/product-best-sellers /-->\'' );

		// Create Featured Category post.
		WP_CLI::runcommand( 'post create --post_status=publish --post_title=\'Featured Category\' --post_content=\'<!-- wp:woocommerce/featured-category {"editMode":false,"categoryId":20} --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"align":"center"} --><div class="wp-block-button aligncenter"><a class="wp-block-button__link" href="https://wpcli.local/product-category/clothing/">Shop now</a></div><!-- /wp:button --></div><!-- /wp:buttons --><!-- /wp:woocommerce/featured-category -->\'' );

		// Create Featured Product post.
		WP_CLI::runcommand( 'post create --post_status=publish --post_title=\'Featured Product\' --post_content=\'<!-- wp:woocommerce/featured-product {"editMode":false,"productId":10} --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"align":"center"} --><div class="wp-block-button aligncenter"><a class="wp-block-button__link" href="https://wpcli.local/product/beanie/">Shop now</a></div><!-- /wp:button --></div><!-- /wp:buttons --><!-- /wp:woocommerce/featured-product -->\'' );

		// Create Hand-picked Products post.
		WP_CLI::runcommand( 'post create --post_status=publish --post_title=\'Hand-picked Products\' --post_content=\'<!-- wp:woocommerce/handpicked-products {"editMode":false,"products":[11,12,13]} /-->\'' );

		// Create Newest Products post.
		WP_CLI::runcommand( 'post create --post_status=publish --post_title=\'Newest Products\' --post_content=\'<!-- wp:woocommerce/product-new /-->\'' );

		// Create On Sale Products post.
		WP_CLI::runcommand( 'post create --post_status=publish --post_title=\'On Sale Products\' --post_content=\'<!-- wp:woocommerce/product-on-sale /-->\'' );

		// Create Products by Category post.
		WP_CLI::runcommand( 'post create --post_status=publish --post_title=\'Products by Category\' --post_content=\'<!-- wp:woocommerce/product-category {"categories":[17]} /-->\'' );

		// Create Products by Tag post.
		WP_CLI::runcommand( 'post create --post_status=publish --post_title=\'Products by Tag\' --post_content=\'<!-- wp:woocommerce/product-tag /-->\'' );

		// Create Product Categories List post.
		WP_CLI::runcommand( 'post create --post_status=publish --post_title=\'Product Categories List\' --post_content=\'<!-- wp:woocommerce/product-categories /-->\'' );

		// Create Product Search post.
		WP_CLI::runcommand( 'post create --post_status=publish --post_title=\'Product Search\' --post_content=\'<!-- wp:woocommerce/product-search {"formId":"wc-block-product-search-3"} /-->\'' );

		// Create Reviews by Category post.
		WP_CLI::runcommand( 'post create --post_status=publish --post_title=\'Reviews by Category\' --post_content=\'<!-- wp:woocommerce/reviews-by-category {"editMode":false,"categoryIds":[18]} --><div class="wp-block-woocommerce-reviews-by-category wc-block-reviews-by-category has-image has-name has-date has-rating has-content has-product-name" data-image-type="reviewer" data-orderby="most-recent" data-reviews-on-page-load="10" data-reviews-on-load-more="10" data-show-load-more="true" data-show-orderby="true" data-category-ids="18"></div><!-- /wp:woocommerce/reviews-by-category -->\'' );

		// Create Reviews by Product post.
		WP_CLI::runcommand( 'post create --post_status=publish --post_title=\'Reviews by Product\' --post_content=\'<!-- wp:woocommerce/reviews-by-product {"editMode":false,"productId":27} --><div class="wp-block-woocommerce-reviews-by-product wc-block-reviews-by-product has-image has-name has-date has-rating has-content" data-image-type="reviewer" data-orderby="most-recent" data-reviews-on-page-load="10" data-reviews-on-load-more="10" data-show-load-more="true" data-show-orderby="true" data-product-id="27"></div><!-- /wp:woocommerce/reviews-by-product -->\'' );

		// Create Top Rated Products post.
		WP_CLI::runcommand( 'post create --post_status=publish --post_title=\'Top Rated Products\' --post_content=\'<!-- wp:woocommerce/product-top-rated /-->\'' );
	}

	/**
	 * Set up sidebar.
	 *
	 * @return void
	 */
	private function setupSidebar() {
		$sidebars = WP_CLI::runcommand( 'sidebar list --fields=id --format=ids', array( 'return' => true ) );
		if ( strpos( $sidebars, 'sidebar-1' ) !== false ) {
			WP_CLI::runcommand( 'widget reset --all', array( 'exit_error' => false ) );
			WP_CLI::runcommand( 'widget add block sidebar-1 --content=\'<!-- wp:latest-posts {"postsToShow":20,"order":"asc","orderBy":"title"} /-->\'', array( 'exit_error' => false ) );
		}
	}

	/**
	 * Set up shipping.
	 *
	 * @return void
	 */
	private function setupShipping() {
		WP_CLI::runcommand( 'wc shipping_zone_method create 0 --order=1 --enabled=true --settings=\'{"title":"Flat rate shipping", "cost": "10"}\' --method_id=flat_rate --user=1' );
		WP_CLI::runcommand( 'wc shipping_zone_method create 0 --order=2 --enabled=true --settings=\'{"title":"Free shipping"}\' --method_id=free_shipping --user=1' );

		if ( false === get_option( 'woocommerce_pickup_location_settings' ) ) {
			WP_CLI::runcommand( 'option add woocommerce_pickup_location_settings \'{"enabled":"yes","title":"Local Pickup","tax_status":"taxable","cost":""}\' --format=json' );
		}

		if ( false === get_option( 'pickup_location_pickup_locations' ) ) {
			WP_CLI::runcommand( 'option add pickup_location_pickup_locations \'[{"name":"Automattic Inc.","address":{"address_1":"60 29th Street Suite 343","city":"San Francisco","state":"CA","postcode":"94110","country":"US"},"details":"","enabled":true},{"name":"Aut O\u2019Mattic A8C Ireland Ltd","address":{"address_1":"25 Herbert Pl","city":"Dublin","state":"D","postcode":"D02 AY86","country":"IE"},"details":"","enabled":true}]\' --format=json' );
		}
	}

	/**
	 * Tear down shopping.
	 *
	 * @return void
	 */
	private function tearDownShipping() {
		$options = array(
			'return' => true,
			'parse'  => 'json',
		);
		$results = WP_CLI::runcommand( 'wc shipping_zone_method list 0 --field=instance_id --format=json --user=admin', $options );
		foreach ( $results as $value ) {
			WP_CLI::runcommand( 'wc shipping_zone_method delete 0 ' . $value . ' --zone_id=0 --instance_id=' . $value . ' --force=true --user=admin' );
		}

		WP_CLI::runcommand( 'option delete woocommerce_pickup_location_settings' );
		WP_CLI::runcommand( 'option delete pickup_location_pickup_locations' );
	}

	/**
	 * Set up payments.
	 *
	 * @param array $assoc_args An array with optional arguments.
	 *
	 * @return void
	 */
	private function setupPayments( $assoc_args ) {
		WP_CLI::runcommand( 'option set --format=json woocommerce_cod_settings \'{"enabled":"yes","title":"Cash on delivery","description":"Cash on delivery description","instructions":"Cash on delivery instructions"}\'' );
		WP_CLI::runcommand( 'option set --format=json woocommerce_bacs_settings \'{"enabled":"yes","title":"Direct bank transfer","description":"Direct bank transfer description","instructions":"Direct bank transfer instructions"}\'' );
		WP_CLI::runcommand( 'option set --format=json woocommerce_cheque_settings \'{"enabled":"yes","title":"Check payments","description":"Check payments description","instructions":"Check payments instructions"}\'' );

		if ( isset( $assoc_args['stripe'] ) ) {
			$this->setupStripeGateway();
		}
	}

	/**
	 * Set up tax.
	 *
	 * @return void
	 */
	private function setupTax() {
		WP_CLI::runcommand( 'option set woocommerce_calc_taxes yes' );
		WP_CLI::runcommand( 'wc tax create --rate=10 --class=standard --user=1' );
		WP_CLI::runcommand( 'wc tax create --rate=5 --class=reduced-rate --user=1' );
		WP_CLI::runcommand( 'wc tax create --rate=0 --class=zero-rate --user=1' );
	}

	/**
	 * Tear down tax.
	 *
	 * @return void
	 */
	private function tearDownTax() {
		$options = array(
			'return' => true,
			'parse'  => 'json',
		);
		$results = WP_CLI::runcommand( 'wc tax list --format=json --field=id  --user=1', $options );

		foreach ( $results as $value ) {
			WP_CLI::runcommand( 'wc tax delete ' . $value . ' --force=true --user=1' );
		}
	}

	/**
	 * Set up coupons.
	 *
	 * @return void
	 */
	private function setupCoupons() {
		WP_CLI::runcommand( 'wc shop_coupon create --code=coupon --amount=10 --discount_type=percent --user=1' );
	}

	/**
	 * Set up coupons.
	 *
	 * @return void
	 */
	private function setupReviews() {
		// @todo: Implement reviews
		// WP_CLI::runcommand();
	}

	/**
	 * Set up payments.
	 *
	 * @return void
	 */
	private function setupPermalinks() {
		WP_CLI::runcommand( 'rewrite structure "/%postname%/"' );
		WP_CLI::runcommand( 'rewrite flush' );
	}

	/**
	 * Checks whether the WooCommerce Blocks plugin is enabled or not.
	 *
	 * @return bool
	 */
	private function isWoocommerceBlocksActive(): bool {
		$result_old_slug = WP_CLI::runcommand(
			'plugin is-active woo-gutenberg-products-block',
			array(
				'exit_error' => false,
				'return'     => 'all',
			)
		);

		$result_new_slug = WP_CLI::runcommand(
			'plugin is-active woocommerce-gutenberg-products-block',
			array(
				'exit_error' => false,
				'return'     => 'all',
			)
		);

		return 0 === $result_old_slug->return_code || 0 === $result_new_slug->return_code;
	}

	/**
	 * Checks whether the WooCommerce Beta Tester plugin is enabled or not.
	 *
	 * @return bool
	 */
	private function isWcBetaTesterPluginActive(): bool {
		$active_plugins = get_option( 'active_plugins' );

		foreach ( $active_plugins as $plugin ) {
			if ( 0 === strpos( $plugin, 'wc_beta_tester_live_branch_' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Installs WooCommerce Blocks plugin.
	 *
	 * @param array $assoc_args An array with optional arguments.
	 *
	 * @return void
	 */
	private function installWoocommerceBlocksPlugin( array $assoc_args ) {
		if ( ! isset( $assoc_args['blocks'] ) ) {
			return;
		}

		$plugin_dir = WP_PLUGIN_DIR . '/woo-gutenberg-products-block';
		if ( file_exists( $plugin_dir ) ) {
			$this->deleteDirectory( $plugin_dir );
		}

		if ( true === $assoc_args['blocks'] ) {
			WP_CLI::runcommand( 'plugin install woo-gutenberg-products-block --activate' );
			return;
		}

		$url = '';
		if ( $this->isReleaseVersion( $assoc_args['blocks'] ) ) {
			$url = "https://github.com/woocommerce/woocommerce-gutenberg-products-block/releases/download/v{$assoc_args['blocks']}/woo-gutenberg-products-block.zip";
		} elseif ( $this->isUrl( $assoc_args['blocks'] ) ) {
			$url = $assoc_args['blocks'];
		}

		if ( ! $url ) {
			return;
		}

		try {
			WP_CLI::runcommand( "plugin install {$url} --activate" );
		} catch ( \Throwable $th ) {
			WP_CLI::error( "WooCommerce Blocks release {$assoc_args['block']} could not be installed!" );
			WP_CLI::error( $th );
		}
	}

	/**
	 * Deletes a directory recursively.
	 *
	 * @param string $dir The directory to delete.
	 *
	 * @return bool
	 */
	private function deleteDirectory( $dir ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		WP_Filesystem();
		global $wp_filesystem;

		if ( ! $wp_filesystem->exists( $dir ) ) {
			return true;
		}

		if ( ! $wp_filesystem->is_dir( $dir ) ) {
			return $wp_filesystem->delete( $dir );
		}

		foreach ( $wp_filesystem->dirlist( $dir ) as $item => $details ) {
			if ( ! $this->deleteDirectory( $dir . DIRECTORY_SEPARATOR . $item ) ) {
				return false;
			}
		}

		return $wp_filesystem->rmdir( $dir );
	}


	/**
	 * Checks if the given string is a release version.
	 *
	 * @param string $version The version to check.
	 *
	 * @return false|int
	 */
	private function isReleaseVersion( $version ) {
		return preg_match( '/^\d.\d.\d$/', $version );
	}

	/**
	 * Checks if the given string is a URL.
	 *
	 * @param string $url The URL to check.
	 *
	 * @return bool
	 */
	private function isUrl( $url ): bool {
		return filter_var( $url, FILTER_VALIDATE_URL );
	}

	/**
	 * Sets up the WooCommerce Stripe gateway.
	 */
	private function setupStripeGateway() {
		WP_CLI::runcommand( 'option delete woocommerce_stripe_settings' );

		WP_CLI::log( 'Setting up Stripe testing account...' );
		WP_CLI::runcommand( 'plugin install woocommerce-gateway-stripe --activate' );

		$publishable_key = $this->getInput( 'Enter your test publishable key: ' );
		if ( empty( $publishable_key ) ) {
			WP_CLI::warning( 'Empty publishable key, skipping Stripe configuration.' );
			return;
		}

		$secret_key = $this->getHiddenInput( 'Enter your test secret key: ' );
		if ( empty( $secret_key ) ) {
			WP_CLI::warning( 'Empty secret key, skipping Stripe configuration.' );
			return;
		}

		WP_CLI::log( "\n" );
		WP_CLI::runcommand( 'option add --format=json woocommerce_stripe_settings \'{"enabled":"yes", "testmode":"yes", "test_publishable_key":"' . $publishable_key . '", "test_secret_key":"' . $secret_key . '"}\'' );
	}

	/**
	 * Gets user input from command line.
	 *
	 * @param string $prompt Prompt to display.
	 *
	 * @return string
	 */
	private function getInput( $prompt ) {
		WP_CLI::log( $prompt );

		return trim( fgets( STDIN ) );
	}

	/**
	 * Gets user hidden input from command line.
	 *
	 * @param string $prompt Prompt to display.
	 *
	 * @return string */
	private function getHiddenInput( $prompt ) {
		system( 'stty -echo' ); // phpcs:ignore
		$response = $this->getInput( $prompt );
		system( 'stty echo' ); // phpcs:ignore

		return $response;
	}
}

WP_CLI::add_command( 'woo-test-environment', 'WooCommerce_Blocks_Testing_Environment' );
