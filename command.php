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
	 *
	 * @param array $args An array with optional arguments.
	 * @param array $assoc_args An array with optional arguments.
	 * @return string Success or error message.
	 *
	 * ## OPTIONS
	 *
	 * [--version=<version>]
	 * : The desired WooCommerce Blocks release to install.
	 *
	 * [--gutenberg=<true>]
	 * : Whether to install and activate the Gutenberg plugin.
	 *
	 * [--theme=<theme>]
	 * : The desired WordPress theme to install and activate.
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp woo-test-environment setup --version=7.3.0
	 */
	public function setup( $args, $assoc_args ) {
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
		$this->setupPayments();
		$this->setupTax();
		$this->setupCoupons();
		$this->setupReviews();
		$this->setupPermalinks();

		WP_CLI::success( 'WooCommerce Blocks Testing Environment successfully set up.' );
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
	 * @return void
	 */
	private function setupPlugins( $assoc_args ) {
		// Install and activate a certain WooCommerce Blocks release, if desired.
		if ( isset( $assoc_args['version'] ) && preg_match( '/\d.\d.\d/', $assoc_args['version'] ) ) {
			try {
				$plugin = "https://github.com/woocommerce/woocommerce-gutenberg-products-block/releases/download/v{$assoc_args['version']}/woo-gutenberg-products-block.zip";
				WP_CLI::runcommand( "plugin install {$plugin} --activate" );
			} catch ( \Throwable $th ) {
				WP_CLI::error( "WooCommerce Blocks release {$assoc_args['version']} could not be installed!" );
				WP_CLI::error( $th );
			}
		}

		// Install and activate the Gutenberg plugin, if desired.
		if ( isset( $assoc_args['gutenberg'] ) && true === $assoc_args['gutenberg'] ) {
			WP_CLI::runcommand( 'plugin install gutenberg --activate' );
		}

		WP_CLI::runcommand( 'plugin install woocommerce --activate' );
		WP_CLI::runcommand( 'plugin install wordpress-importer --activate' );
	}

	/**
	 * Set up themes.
	 *
	 * @param array $assoc_args An array with optional arguments.
	 * @return void
	 */
	private function setupThemes( $assoc_args ) {
		// Install and activate a certain WordPress theme, if desired.
		if ( isset( $assoc_args['theme'] ) ) {
			WP_CLI::runcommand( "theme install {$assoc_args['theme']} --activate" );
		}
	}

	/**
	 * Import products.
	 *
	 * @return void
	 */
	private function setupProducts() {
		WP_CLI::runcommand( 'import wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=skip' );
	}

	/**
	 * Set up pages (cart & checkout block).
	 *
	 * @return void
	 */
	private function setupPages() {
		// Create Shop page with block.
		WP_CLI::runcommand( 'post create --menu_order=0 --post_type=page --post_status=publish --post_title=\'Shop\' --post_content=\'<!-- wp:woocommerce/all-products {"columns":3,"rows":3,"alignButtons":false,"contentVisibility":{"orderBy":true},"orderby":"date","layoutConfig":[["woocommerce/product-image",{"imageSizing":"cropped"}],["woocommerce/product-title"],["woocommerce/product-price"],["woocommerce/product-rating"],["woocommerce/product-button"]]} --><div class="wp-block-woocommerce-all-products wc-block-all-products" data-attributes="{&quot;alignButtons&quot;:false,&quot;columns&quot;:3,&quot;contentVisibility&quot;:{&quot;orderBy&quot;:true},&quot;isPreview&quot;:false,&quot;layoutConfig&quot;:[[&quot;woocommerce/product-image&quot;,{&quot;imageSizing&quot;:&quot;cropped&quot;}],[&quot;woocommerce/product-title&quot;],[&quot;woocommerce/product-price&quot;],[&quot;woocommerce/product-rating&quot;],[&quot;woocommerce/product-button&quot;]],&quot;orderby&quot;:&quot;date&quot;,&quot;rows&quot;:3}"></div><!-- /wp:woocommerce/all-products -->\'' );

		// Create (default) Shop page.
		WP_CLI::runcommand( 'post create --post_type=page --post_status=publish --post_title=\'Classic Shop\' --post_parent=$(wp post list --title="Shop" --post_type=page --field=ID)' );
		WP_CLI::runcommand( 'option update woocommerce_shop_page_id $(wp post list --title="Classic Shop" --post_type=page --field=ID)' );

		if ( $this->is_woocommerce_blocks_active() ) {
			// Create Cart page with block.
			WP_CLI::runcommand( 'post create --menu_order=1 --post_type=page --post_status=publish --post_title=\'Cart\' --post_content=\'<!-- wp:woocommerce/cart --><div class="wp-block-woocommerce-cart is-loading"><!-- wp:woocommerce/filled-cart-block --><div class="wp-block-woocommerce-filled-cart-block"><!-- wp:woocommerce/cart-items-block --><div class="wp-block-woocommerce-cart-items-block"><!-- wp:woocommerce/cart-line-items-block --><div class="wp-block-woocommerce-cart-line-items-block"></div><!-- /wp:woocommerce/cart-line-items-block --></div><!-- /wp:woocommerce/cart-items-block --><!-- wp:woocommerce/cart-totals-block --><div class="wp-block-woocommerce-cart-totals-block"><!-- wp:woocommerce/cart-order-summary-block --><div class="wp-block-woocommerce-cart-order-summary-block"></div><!-- /wp:woocommerce/cart-order-summary-block --><!-- wp:woocommerce/cart-express-payment-block --><div class="wp-block-woocommerce-cart-express-payment-block"></div><!-- /wp:woocommerce/cart-express-payment-block --><!-- wp:woocommerce/proceed-to-checkout-block --><div class="wp-block-woocommerce-proceed-to-checkout-block"></div><!-- /wp:woocommerce/proceed-to-checkout-block --><!-- wp:woocommerce/cart-accepted-payment-methods-block --><div class="wp-block-woocommerce-cart-accepted-payment-methods-block"></div><!-- /wp:woocommerce/cart-accepted-payment-methods-block --></div><!-- /wp:woocommerce/cart-totals-block --></div><!-- /wp:woocommerce/filled-cart-block --><!-- wp:woocommerce/empty-cart-block --><div class="wp-block-woocommerce-empty-cart-block"><!-- wp:image {"align":"center","sizeSlug":"small"} --><div class="wp-block-image"><figure class="aligncenter size-small"><img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzgiIGhlaWdodD0iMzgiIHZpZXdCb3g9IjAgMCAzOCAzOCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTE5IDBDOC41MDQwMyAwIDAgOC41MDQwMyAwIDE5QzAgMjkuNDk2IDguNTA0MDMgMzggMTkgMzhDMjkuNDk2IDM4IDM4IDI5LjQ5NiAzOCAxOUMzOCA4LjUwNDAzIDI5LjQ5NiAwIDE5IDBaTTI1LjEyOSAxMi44NzFDMjYuNDg1MSAxMi44NzEgMjcuNTgwNiAxMy45NjY1IDI3LjU4MDYgMTUuMzIyNkMyNy41ODA2IDE2LjY3ODYgMjYuNDg1MSAxNy43NzQyIDI1LjEyOSAxNy43NzQyQzIzLjc3MyAxNy43NzQyIDIyLjY3NzQgMTYuNjc4NiAyMi42Nzc0IDE1LjMyMjZDMjIuNjc3NCAxMy45NjY1IDIzLjc3MyAxMi44NzEgMjUuMTI5IDEyLjg3MVpNMTEuNjQ1MiAzMS4yNTgxQzkuNjE0OTIgMzEuMjU4MSA3Ljk2Nzc0IDI5LjY0OTIgNy45Njc3NCAyNy42NTczQzcuOTY3NzQgMjYuMTI1IDEwLjE1MTIgMjMuMDI5OCAxMS4xNTQ4IDIxLjY5NjhDMTEuNCAyMS4zNjczIDExLjg5MDMgMjEuMzY3MyAxMi4xMzU1IDIxLjY5NjhDMTMuMTM5MSAyMy4wMjk4IDE1LjMyMjYgMjYuMTI1IDE1LjMyMjYgMjcuNjU3M0MxNS4zMjI2IDI5LjY0OTIgMTMuNjc1NCAzMS4yNTgxIDExLjY0NTIgMzEuMjU4MVpNMTIuODcxIDE3Ljc3NDJDMTEuNTE0OSAxNy43NzQyIDEwLjQxOTQgMTYuNjc4NiAxMC40MTk0IDE1LjMyMjZDMTAuNDE5NCAxMy45NjY1IDExLjUxNDkgMTIuODcxIDEyLjg3MSAxMi44NzFDMTQuMjI3IDEyLjg3MSAxNS4zMjI2IDEzLjk2NjUgMTUuMzIyNiAxNS4zMjI2QzE1LjMyMjYgMTYuNjc4NiAxNC4yMjcgMTcuNzc0MiAxMi44NzEgMTcuNzc0MlpNMjUuOTEwNSAyOS41ODc5QzI0LjE5NDQgMjcuNTM0NyAyMS42NzM4IDI2LjM1NDggMTkgMjYuMzU0OEMxNy4zNzU4IDI2LjM1NDggMTcuMzc1OCAyMy45MDMyIDE5IDIzLjkwMzJDMjIuNDAxNiAyMy45MDMyIDI1LjYxMTcgMjUuNDA0OCAyNy43ODc1IDI4LjAyNUMyOC44NDQ4IDI5LjI4MTUgMjYuOTI5NCAzMC44MjE0IDI1LjkxMDUgMjkuNTg3OVoiIGZpbGw9ImJsYWNrIi8+Cjwvc3ZnPgo=" alt=""/></figure></div><!-- /wp:image --><!-- wp:heading {"textAlign":"center","className":"wc-block-cart__empty-cart__title"} --><h2 class="has-text-align-center wc-block-cart__empty-cart__title">Your cart is currently empty!</h2><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><a href="http://localhost:8889/shop/">Browse store</a>.</p><!-- /wp:paragraph --><!-- wp:separator {"className":"is-style-dots"} --><hr class="wp-block-separator is-style-dots"/><!-- /wp:separator --><!-- wp:heading {"textAlign":"center"} --><h2 class="has-text-align-center">New in store</h2><!-- /wp:heading --><!-- wp:woocommerce/product-new {"rows":1} /--></div><!-- /wp:woocommerce/empty-cart-block --></div><!-- /wp:woocommerce/cart -->\'' );
			WP_CLI::runcommand( 'option update woocommerce_cart_page_id $(wp post list --title="Cart" --post_type=page --field=ID)' );

			// Create Checkout page with block.
			WP_CLI::runcommand( 'post create --menu_order=2 --post_type=page --post_status=publish --post_title=\'Checkout\' --post_content=\'<!-- wp:woocommerce/checkout --><div class="wp-block-woocommerce-checkout wc-block-checkout is-loading"><!-- wp:woocommerce/checkout-fields-block --><div class="wp-block-woocommerce-checkout-fields-block"><!-- wp:woocommerce/checkout-express-payment-block --><div class="wp-block-woocommerce-checkout-express-payment-block"></div><!-- /wp:woocommerce/checkout-express-payment-block --><!-- wp:woocommerce/checkout-contact-information-block --><div class="wp-block-woocommerce-checkout-contact-information-block"></div><!-- /wp:woocommerce/checkout-contact-information-block --><!-- wp:woocommerce/checkout-shipping-address-block --><div class="wp-block-woocommerce-checkout-shipping-address-block"></div><!-- /wp:woocommerce/checkout-shipping-address-block --><!-- wp:woocommerce/checkout-billing-address-block --><div class="wp-block-woocommerce-checkout-billing-address-block"></div><!-- /wp:woocommerce/checkout-billing-address-block --><!-- wp:woocommerce/checkout-shipping-methods-block --><div class="wp-block-woocommerce-checkout-shipping-methods-block"></div><!-- /wp:woocommerce/checkout-shipping-methods-block --><!-- wp:woocommerce/checkout-payment-block --><div class="wp-block-woocommerce-checkout-payment-block"></div><!-- /wp:woocommerce/checkout-payment-block --><!-- wp:woocommerce/checkout-order-note-block --><div class="wp-block-woocommerce-checkout-order-note-block"></div><!-- /wp:woocommerce/checkout-order-note-block --><!-- wp:woocommerce/checkout-terms-block --><div class="wp-block-woocommerce-checkout-terms-block"></div><!-- /wp:woocommerce/checkout-terms-block --><!-- wp:woocommerce/checkout-actions-block --><div class="wp-block-woocommerce-checkout-actions-block"></div><!-- /wp:woocommerce/checkout-actions-block --></div><!-- /wp:woocommerce/checkout-fields-block --><!-- wp:woocommerce/checkout-totals-block --><div class="wp-block-woocommerce-checkout-totals-block"><!-- wp:woocommerce/checkout-order-summary-block --><div class="wp-block-woocommerce-checkout-order-summary-block"></div><!-- /wp:woocommerce/checkout-order-summary-block --></div><!-- /wp:woocommerce/checkout-totals-block --></div><!-- /wp:woocommerce/checkout -->\'' );
			WP_CLI::runcommand( 'option update woocommerce_checkout_page_id $(wp post list --title="Checkout" --post_type=page --field=ID)' );
		}

		// Create Cart page with shortcode.
		WP_CLI::runcommand( 'post create --post_type=page --post_status=publish --post_title=\'Classic Cart\' --post_content=\'<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->\' --post_parent=$(wp post list --title="Cart" --post_type=page --field=ID)' );

		// Create Checkout page with shortcode.
		WP_CLI::runcommand( 'post create --post_type=page --post_status=publish --post_title=\'Classic Checkout\' --post_content=\'<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->\' --post_parent=$(wp post list --title="Checkout" --post_type=page --field=ID)' );

		// Create My Account page with shortcode.
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
		WP_CLI::runcommand( 'post create --post_status=publish --post_title=\'Active Product Filters\' --post_content=\'<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column {"width":"33.33%"} --><div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:woocommerce/active-filters --><div class="wp-block-woocommerce-active-filters is-loading" data-display-style="list" data-heading="Active filters" data-heading-level="3"><span aria-hidden="true" class="wc-block-active-product-filters__placeholder"></span></div><!-- /wp:woocommerce/active-filters --><!-- wp:woocommerce/price-filter --><div class="wp-block-woocommerce-price-filter is-loading" data-showinputfields="true" data-showfilterbutton="false" data-heading="Filter by price" data-heading-level="3"><span aria-hidden="true" class="wc-block-product-categories__placeholder"></span></div><!-- /wp:woocommerce/price-filter --><!-- wp:woocommerce/stock-filter {"heading":"Filter by stock status"} --><div class="wp-block-woocommerce-stock-filter is-loading" data-show-counts="true" data-heading="Filter by stock statu" data-heading-level="3"><span aria-hidden="true" class="wc-block-product-stock-filter__placeholder"></span></div><!-- /wp:woocommerce/stock-filter --></div><!-- /wp:column --><!-- wp:column {"width":"66.66%"} --><div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:woocommerce/all-products {"columns":3,"rows":3,"alignButtons":false,"contentVisibility":{"orderBy":true},"orderby":"date","layoutConfig":[["woocommerce/product-image",{"imageSizing":"cropped"}],["woocommerce/product-title"],["woocommerce/product-price"],["woocommerce/product-rating"],["woocommerce/product-button"]]} --><div class="wp-block-woocommerce-all-products wc-block-all-products" data-attributes="{&quot;alignButtons&quot;:false,&quot;columns&quot;:3,&quot;contentVisibility&quot;:{&quot;orderBy&quot;:true},&quot;isPreview&quot;:false,&quot;layoutConfig&quot;:[[&quot;woocommerce/product-image&quot;,{&quot;imageSizing&quot;:&quot;cropped&quot;}],[&quot;woocommerce/product-title&quot;],[&quot;woocommerce/product-price&quot;],[&quot;woocommerce/product-rating&quot;],[&quot;woocommerce/product-button&quot;]],&quot;orderby&quot;:&quot;date&quot;,&quot;rows&quot;:3}"></div><!-- /wp:woocommerce/all-products --></div><!-- /wp:column --></div><!-- /wp:columns -->\'' );

		// Create Best Selling Products post.
		WP_CLI::runcommand( 'post create --post_status=publish --post_title=\'Best Selling Products\' --post_content=\'<!-- wp:woocommerce/product-best-sellers /-->\'' );

		// Create Featured Category post.
		WP_CLI::runcommand( 'post create --post_status=publish --post_title=\'Featured Category\' --post_content=\'<!-- wp:woocommerce/featured-category {"editMode":false,"categoryId":20} --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"align":"center"} --><div class="wp-block-button aligncenter"><a class="wp-block-button__link" href="http://wpcli.local/product-category/clothing/">Shop now</a></div><!-- /wp:button --></div><!-- /wp:buttons --><!-- /wp:woocommerce/featured-category -->\'' );

		// Create Featured Product post.
		WP_CLI::runcommand( 'post create --post_status=publish --post_title=\'Featured Product\' --post_content=\'<!-- wp:woocommerce/featured-product {"editMode":false,"productId":10} --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"align":"center"} --><div class="wp-block-button aligncenter"><a class="wp-block-button__link" href="http://wpcli.local/product/beanie/">Shop now</a></div><!-- /wp:button --></div><!-- /wp:buttons --><!-- /wp:woocommerce/featured-product -->\'' );

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
		WP_CLI::runcommand( 'widget reset --all' );
		WP_CLI::runcommand( 'widget add block sidebar-1 --content=\'<!-- wp:latest-posts {"postsToShow":20,"order":"asc","orderBy":"title"} /-->\'' );
	}

	/**
	 * Set up shipping.
	 *
	 * @return void
	 */
	private function setupShipping() {
		WP_CLI::runcommand( 'wc shipping_zone_method create 0 --order=1 --enabled=true --settings=\'{"title":"Flat rate shipping", "cost": "10"}\' --method_id=flat_rate --user=1' );
		WP_CLI::runcommand( 'wc shipping_zone_method create 0 --order=2 --enabled=true --settings=\'{"title":"Free shipping"}\' --method_id=free_shipping --user=1' );
	}

	/**
	 * Set up payments.
	 *
	 * @return void
	 */
	private function setupPayments() {
		WP_CLI::runcommand( 'option set --format=json woocommerce_cod_settings \'{"enabled":"yes","title":"Cash on delivery","description":"Cash on delivery description","instructions":"Cash on delivery instructions"}\'' );
		WP_CLI::runcommand( 'option set --format=json woocommerce_bacs_settings \'{"enabled":"yes","title":"Direct bank transfer","description":"Direct bank transfer description","instructions":"Direct bank transfer instructions"}\'' );
		WP_CLI::runcommand( 'option set --format=json woocommerce_cheque_settings \'{"enabled":"yes","title":"Check payments","description":"Check payments description","instructions":"Check payments instructions"}\'' );
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
	 * Tear down WooCommerce Blocks Testing Environment.
	 *
	 * @return void
	 */
	public function teardown() {
		WP_CLI::log( 'Tear down WooCommerce Blocks Testing Environment ...' );

		$this->emptySite();
		$this->tearDownPlugins();
		$this->tearDownThemes();

		WP_CLI::success( 'WooCommerce Blocks Testing Environment successfully teared down.' );
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
	 * Tear down themes.
	 *
	 * @return void
	 */
	private function tearDownThemes() {
		WP_CLI::runcommand( 'theme activate twentytwentytwo' );
		WP_CLI::runcommand( 'theme delete --all' );
	}

	/**
	 * Checks whether the WooCommerce Blocks plugin is enabled or not.
	 *
	 * @return bool
	 */
	public function is_woocommerce_blocks_active(): bool {
		$result = WP_CLI::runcommand(
			'plugin is-active woo-gutenberg-products-block',
			array(
				'exit_error' => false,
				'return'     => 'all',
			)
		);

		return $result->return_code === 0;
	}
}

WP_CLI::add_command( 'woo-test-environment', 'WooCommerce_Blocks_Testing_Environment' );
