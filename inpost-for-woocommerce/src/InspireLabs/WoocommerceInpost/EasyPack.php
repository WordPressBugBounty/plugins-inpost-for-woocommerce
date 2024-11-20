<?php

namespace InspireLabs\WoocommerceInpost;

use Exception;
use InspireLabs\WoocommerceInpost\admin\EasyPack_Custom_Product_List_Table;
use InspireLabs\WoocommerceInpost\EasyPack_Helper;
use InspireLabs\WoocommerceInpost\admin\Alerts;
use InspireLabs\WoocommerceInpost\admin\EasyPack_Product_Shipping_Method_Selector;
use InspireLabs\WoocommerceInpost\admin\EasyPack_Settings_General;
use InspireLabs\WoocommerceInpost\EmailFilters\NewOrderEmail;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shipping_Method_Courier;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shipping_Method_Courier_C2C;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shipping_Method_Courier_C2C_COD;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shipping_Method_Courier_COD;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shipping_Method_Courier_Local_Express;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shipping_Method_Courier_Local_Express_COD;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shipping_Method_Courier_Local_Standard;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shipping_Method_Courier_Local_Standard_COD;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shipping_Method_Courier_LSE;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shipping_Method_Courier_LSE_COD;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shipping_Method_Courier_Palette;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shipping_Method_Courier_Palette_COD;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shipping_Method_EsmartMix;
use InspireLabs\WoocommerceInpost\shipping\Easypack_Shipping_Rates;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shippng_Parcel_Machines;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shippng_Parcel_Machines_COD;
use InspireLabs\WoocommerceInpost\shipx\services\courier_pickup\ShipX_Courier_Pickup_Service;
use InspireLabs\WoocommerceInpost\shipx\services\organization\ShipX_Organization_Service;
use InspireLabs\WoocommerceInpost\shipx\services\shipment\ShipX_Shipment_Price_Calculator_Service;
use InspireLabs\WoocommerceInpost\shipx\services\shipment\ShipX_Shipment_Service;
use InspireLabs\WoocommerceInpost\shipx\services\shipment\ShipX_Shipment_Status_Service;
use WC_Order;
use WC_Shipping_Method;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shipping_Parcel_Machines_Weekend;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shipping_Parcel_Machines_Economy;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shipping_Parcel_Machines_Economy_COD;
use InspireLabs\WoocommerceInpost\EasyPackBulkOrders;
use function DebugQuickLook\Formatting\wrap_warning_types;
use Automattic\WooCommerce\Utilities\OrderUtil;


class EasyPack extends inspire_Plugin4 {

	const LABELS_DIRECTORY = __DIR__ . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'labels';

	const CLASSES_DIRECTORY = __DIR__ . DIRECTORY_SEPARATOR . 'classes';

	const ATTRIBUTE_PREFIX = 'woo_inpost';

	const ENVIRONMENT_PRODUCTION = 'production';

	const ENVIRONMENT_SANDBOX = 'sandbox';


	public static $instance;

	public static $text_domain = 'woocommerce-inpost';

	protected $_pluginNamespace = 'woocommerce-inpost';

	/**
	 * @var WC_Shipping_Method[]
	 */
	protected $shipping_methods = array();

	protected $settings;

	/**
	 * @var string
	 */
	private static $environment;

	private static $assets_js_uri;
	private static $assets_css_uri;


	/**
	 * Get labels uri
	 *
	 * @return string
	 */
	public static function getLabelsUri(): string {
		return plugins_url() . '/woo-inpost/web/labels/';
	}

	public function __construct() {
		parent::__construct();
		add_action( 'plugins_loaded', array( $this, 'init_easypack' ), 100 );
		add_action( 'woocommerce_init', array( $this, 'add_settings_to_flexible_shipping' ) );
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_filter( 'woocommerce_package_rates', array( $this, 'check_paczka_weekend_fs_settings' ), 10, 2 );

		add_action(
			'init',
			function () {
				if ( 'yes' === get_option( 'easypack_debug_mode' ) ) {
					if ( current_user_can( 'administrator' ) ) {
						$this->init_shipping_methods();
					}
				} else {
					$this->init_shipping_methods();
				}
			}
		);
	}

	/**
	 * Get assets JS uri
	 *
	 * @return string
	 */
	public static function get_assets_js_uri(): string {
		return self::$assets_js_uri;
	}

	/**
	 * Get assets CSS uri
	 *
	 * @return string
	 */
	public static function get_assets_css_uri(): string {
		return self::$assets_css_uri;
	}

	public static function EasyPack(): EasyPack {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init_easypack() {
		$this->setup_environment();
		self::$assets_js_uri  = $this->getPluginJs();
		self::$assets_css_uri = $this->getPluginCss();
		( new Geowidget_v5() )->init_assets();
		$this->init_alerts();

		add_filter( 'woocommerce_get_settings_pages', array( $this, 'woocommerce_get_settings_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 75 );
		// add_action( 'woocommerce_checkout_after_order_review', [ $this, 'woocommerce_checkout_after_order_review' ] );
		add_filter( 'woocommerce_order_item_display_meta_value', array( $this, 'change_order_item_meta_value' ), 20, 3 );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'clear_wc_shipping_cache' ) );
		add_action( 'woocommerce_add_to_cart', array( $this, 'clear_wc_shipping_cache' ) );
		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'clear_wc_shipping_cache' ) );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'clear_wc_shipping_cache' ) );
		add_filter( 'woocommerce_locate_template', array( $this, 'easypack_woo_templates' ), 1, 3 );

		// integration Products table start.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_product_table_assets' ), 100 );
		add_action( 'wp_ajax_inpost_product_table', array( $this, 'inpost_product_table_callback' ) );

		add_action(
			'admin_menu',
			function () {
				( new EasyPack_Custom_Product_List_Table() );
				$product_table = new EasyPack_Custom_Product_List_Table();
				add_submenu_page(
					'inpost',
					__( 'Products Settings', 'woocommerce-inpost' ),
					__( 'Products Settings', 'woocommerce-inpost' ),
					'view_woocommerce_reports',
					'easypack_product_settings',
					array( $product_table, 'render_custom_product_settings_page' )
				);
			},
			9999
		);
		// integration Products table end.

		try {
			( new Easypack_Shipping_Rates() )->init();
			( new EasyPackBulkOrders() )->hooks();
			( new EasyPackCoupons() )->hooks();

			// integration with Woocommerce blocks start.
			add_action(
				'woocommerce_blocks_checkout_block_registration',
				function ( $integration_registry ) {
					$integration_registry->register( new EasyPackWooBlocks() );
				}
			);

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_block_script' ), 100 );

			add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'block_checkout_save_parcel_locker_in_order_meta' ), 10, 2 );
			// integration with Woocommerce blocks end.

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 75 );
			add_filter( 'woocommerce_shipping_methods', array( $this, 'woocommerce_shipping_methods' ), 1000 );

			add_filter( 'woocommerce_shipping_packages', array( $this, 'woocommerce_shipping_packages' ), 1000 );
			add_filter( 'woocommerce_package_rates', array( $this, 'filter_shipping_methods' ), PHP_INT_MAX );
			add_filter( 'woocommerce_get_order_item_totals', array( $this, 'show_parcel_machine_in_order_details' ), 2, 100 );
			$this->init_email_filters();
			( new EasyPack_Product_Shipping_Method_Selector() )->handle_product_edit_hooks();
		} catch ( Exception $exception ) {
			\wc_get_logger()->debug( 'INPOST Exception: ', array( 'source' => 'inpost-log' ) );
			\wc_get_logger()->debug( print_r( $exception->getMessage(), true ), array( 'source' => 'inpost-log' ) );

		}
	}

	public function woocommerce_checkout_after_order_review() {
		echo '<input type="hidden" id="parcel_machine_id"
                     name="parcel_machine_id" class="parcel_machine_id"/>
            <input type="hidden" id="parcel_machine_desc"
                   name="parcel_machine_desc" class="parcel_machine_desc"/>';
	}

	/**
	 * Get environment
	 *
	 * @return string
	 */
	public static function get_environment(): string {
		return self::$environment;
	}

	/**
	 * Setup environment
	 *
	 * @return void
	 */
	private function setup_environment() {
		if ( self::ENVIRONMENT_SANDBOX === get_option( 'easypack_api_environment' ) ) {
			self::$environment = self::ENVIRONMENT_SANDBOX;
		} else {
			self::$environment = self::ENVIRONMENT_PRODUCTION;
		}
	}

	private function init_email_filters() {
		( new NewOrderEmail() )->init();
	}

	private function init_alerts() {
		$alerts = new Alerts();
	}

	/**
	 * Show parcel machine in order details
	 *
	 * @param array    $items $items.
	 *
	 * @param WC_Order $wcOrder $wcOrder.
	 *
	 * @return array
	 */
	public function show_parcel_machine_in_order_details( $items, $wcOrder ) {

		$parcel_desc       = html_entity_decode( $wcOrder->get_meta( '_parcel_machine_desc' ) );
		$parcel_machine_id = html_entity_decode( $wcOrder->get_meta( '_parcel_machine_id' ) );

		$parcel_locker_methods = array(
			'easypack_parcel_machines',
			'easypack_parcel_machines_cod',
			'easypack_parcel_machines_economy',
			'easypack_parcel_machines_economy_cod',
			'easypack_parcel_machines_weekend',
		);

		$fs_method_name = get_post_meta( $wcOrder->get_id(), '_fs_easypack_method_name', true );

		$shipping_method_id = '';

		foreach ( $wcOrder->get_items( 'shipping' ) as $item_id => $item ) {
			$shipping_method_id = $item->get_method_id(); // The method ID.
		}

		if ( in_array( $shipping_method_id, $parcel_locker_methods )
			|| ( isset( $fs_method_name ) && in_array( $fs_method_name, $parcel_locker_methods ) ) ) {

			if ( isset( $items['shipping'] ) && ! empty( $parcel_machine_id ) ) {
				$items['shipping']['value']
					.= sprintf(
						'<br>%1s:<br><span class="ep-chosen-parcel-machine">%1s</span><br><span class="italic">%3s</span>',
						esc_html__( 'Selected parcel machine', 'woocommerce-inpost' ),
						esc_attr( $parcel_machine_id ),
						esc_html( $parcel_desc )
					);
			}
		}

		return $items;
	}


	public function init_shipping_methods() {

		$main_methods = array(
			'inpost_locker_standard',
			'inpost_courier_palette',
			'inpost_courier_standard',
			'inpost_courier_c2c',
			'inpost_courier_express_1000',
			'inpost_courier_express_1200',
			'inpost_courier_express_1700',
			'inpost_locker_economy',
			'inpost_courier_alcohol',
		);

		$stored_organization = get_option( 'woo_inpost_organisation' );

		$servicesAllowed = array();

		// get Shipping methods from stored settings.
		if ( ! empty( $stored_organization ) && is_array( $stored_organization ) ) {
			if ( isset( $stored_organization['services'] ) && is_array( $stored_organization['services'] ) ) {
				foreach ( $main_methods as $service ) {
					if ( in_array( $service, $stored_organization['services'] ) ) {
						$servicesAllowed[] = $service;
					}
				}
			}
		}

		// trying to connect to API during 60 seconds and save data to settings or show special message.
		if ( empty( $servicesAllowed ) || ! is_array( $servicesAllowed ) ) {

			$now                 = time();
			$limit_time_to_retry = 60 + (int) get_option( 'easypack_api_limit_connection', 0 ); // saved during saving API key.

			if ( $limit_time_to_retry > $now ) {
				// try to connect to API only for 60 sec to avoid make website slow.
				$this->get_or_update_data_from_api();
			} elseif ( ! empty( get_option( 'easypack_organization_id' ) )
					&& ! empty( get_option( 'easypack_token' ) )
				) {

					$alerts = new Alerts();
					$error  = sprintf(
						'%s <a target="_blank" href="https://inpost.pl/formularz-wsparcie">%s</a>',
						__( 'We were unable to connect to the API within 60 seconds. Please try to re-save settings later or', 'woocommerce-inpost' ),
						__( 'contact to support', 'woocommerce-inpost' )
					);

					$alerts->add_error( $error );
			}
		}

		if ( is_array( $servicesAllowed ) && ! empty( $servicesAllowed ) ) {
			if ( in_array( EasyPack_Shippng_Parcel_Machines::SERVICE_ID, $servicesAllowed ) ) {
				$easyPack_Shippng_Parcel_Machines = new EasyPack_Shippng_Parcel_Machines();
				$this->shipping_methods[]         = $easyPack_Shippng_Parcel_Machines;

				$easyPack_Shippng_Parcel_Machines_Weekend = new EasyPack_Shipping_Parcel_Machines_Weekend();
				$this->shipping_methods[]                 = $easyPack_Shippng_Parcel_Machines_Weekend;
			}

			if ( in_array( EasyPack_Shipping_Parcel_Machines_Economy::SERVICE_ID, $servicesAllowed ) ) {
				$easyPack_Shippng_Parcel_Machines_Economy = new EasyPack_Shipping_Parcel_Machines_Economy();
				$this->shipping_methods[]                 = $easyPack_Shippng_Parcel_Machines_Economy;

				$easyPack_Shippng_Parcel_Machines_Economy_COD = new EasyPack_Shipping_Parcel_Machines_Economy_COD();
				$this->shipping_methods[]                     = $easyPack_Shippng_Parcel_Machines_Economy_COD;
			}

			if ( in_array( EasyPack_Shipping_Method_EsmartMix::SERVICE_ID, $servicesAllowed ) ) {
				$easyPack_Shipping_Method_EsmartMix = new EasyPack_Shipping_Method_EsmartMix();
				$this->shipping_methods[]           = $easyPack_Shipping_Method_EsmartMix;
			}

			if ( in_array( EasyPack_Shippng_Parcel_Machines_COD::SERVICE_ID, $servicesAllowed ) ) {
				$easyPack_Shippng_Parcel_Machines_Cod = new EasyPack_Shippng_Parcel_Machines_COD();
				$this->shipping_methods[]             = $easyPack_Shippng_Parcel_Machines_Cod;

				if ( in_array( EasyPack_Shipping_Method_Courier_Local_Standard::SERVICE_ID, $servicesAllowed ) ) {
					$shipping_Method_Courier_local_standard = new EasyPack_Shipping_Method_Courier_Local_Standard();
					$this->shipping_methods[]               = $shipping_Method_Courier_local_standard;
				}

				if ( in_array( EasyPack_Shipping_Method_Courier_LSE_COD::SERVICE_ID, $servicesAllowed ) ) {
					$shipping_Method_Courier_LSE_COD = new EasyPack_Shipping_Method_Courier_LSE_COD();
					$this->shipping_methods[]        = $shipping_Method_Courier_LSE_COD;
				}

				if ( in_array( EasyPack_Shipping_Method_Courier_COD::SERVICE_ID, $servicesAllowed ) ) {
					$shipping_Method_Courier_COD = new EasyPack_Shipping_Method_Courier_COD();
					$this->shipping_methods[]    = $shipping_Method_Courier_COD;
				}

				if ( in_array( EasyPack_Shipping_Method_Courier::SERVICE_ID, $servicesAllowed ) ) {
					$shipping_Method_Courier  = new EasyPack_Shipping_Method_Courier();
					$this->shipping_methods[] = $shipping_Method_Courier;
				}

				if ( in_array( EasyPack_Shipping_Method_Courier_LSE::SERVICE_ID, $servicesAllowed ) ) {
					$shipping_Method_Courier_LSE = new EasyPack_Shipping_Method_Courier_LSE();
					$this->shipping_methods[]    = $shipping_Method_Courier_LSE;
				}

				if ( in_array( EasyPack_Shipping_Method_Courier_Local_Standard_COD::SERVICE_ID, $servicesAllowed ) ) {
					$shipping_Method_Courier_local_standard_cod = new EasyPack_Shipping_Method_Courier_Local_Standard_COD();
					$this->shipping_methods[]                   = $shipping_Method_Courier_local_standard_cod;
				}

				if ( in_array( EasyPack_Shipping_Method_Courier_Local_Express::SERVICE_ID, $servicesAllowed ) ) {
					$shipping_Method_Courier_local_express = new EasyPack_Shipping_Method_Courier_Local_Express();
					$this->shipping_methods[]              = $shipping_Method_Courier_local_express;
				}

				if ( in_array( EasyPack_Shipping_Method_Courier_Local_Express_COD::SERVICE_ID, $servicesAllowed ) ) {
					$shipping_Method_Courier_local_express_cod = new EasyPack_Shipping_Method_Courier_Local_Express_COD();
					$this->shipping_methods[]                  = $shipping_Method_Courier_local_express_cod;
				}

				if ( in_array( EasyPack_Shipping_Method_Courier_Palette::SERVICE_ID, $servicesAllowed ) ) {
					$shipping_Method_Courier_Palette = new EasyPack_Shipping_Method_Courier_Palette();
					$this->shipping_methods[]        = $shipping_Method_Courier_Palette;
				}

				if ( in_array( EasyPack_Shipping_Method_Courier_Palette_COD::SERVICE_ID, $servicesAllowed ) ) {
					$shipping_Method_Courier_Palette_Cod = new EasyPack_Shipping_Method_Courier_Palette_COD();
					$this->shipping_methods[]            = $shipping_Method_Courier_Palette_Cod;
				}

				if ( in_array( EasyPack_Shipping_Method_Courier_C2C::SERVICE_ID, $servicesAllowed ) ) {
					$shipping_Method_Courier_c2c = new EasyPack_Shipping_Method_Courier_C2C();
					$this->shipping_methods[]    = $shipping_Method_Courier_c2c;

					$shipping_Method_Courier_c2c_cod = new EasyPack_Shipping_Method_Courier_C2C_COD();
					$this->shipping_methods[]        = $shipping_Method_Courier_c2c_cod;
				}
			}
		}

		EasyPack_Product_Shipping_Method_Selector::$inpost_methods = $this->shipping_methods;
	}

	public function woocommerce_shipping_methods( $methods ) {

		foreach ( $this->shipping_methods as $shipping_method ) {

			$methods[ $shipping_method->id ] = get_class( $shipping_method );
		}

		return $methods;
	}

	public function woocommerce_shipping_packages( $packages ) {

		$methods_allowed_by_cart = array();

		if ( is_object( WC()->session ) ) {
			$cart = WC()->cart->cart_contents;

			if ( ! empty( $cart ) && is_array( $cart ) ) {
				$methods_allowed_by_cart = ( new EasyPack_Product_Shipping_Method_Selector() )->get_methods_allowed_by_cart( $cart );
			}
		}

		$rates_allowed = array();
		$rates         = array();

		if ( isset( $packages[0]['rates'] ) ) {
			$rates = $packages[0]['rates'];
		}

		if ( is_array( $rates ) && ! empty( $rates ) ) {
			foreach ( $rates as $k => $rate_object ) {
				// if Flexible shipping is active we need check if some our Easypack methods is linked to FS.
				if ( EasyPack_Helper()->is_flexible_shipping_activated() ) {
					$linked_method = '';
					$instance_id   = '';

					if ( stripos( $k, ':' ) ) {
						$instance_id = trim( explode( ':', $k )[1] );
					}
					// check if some easypack method linked to Flexible Shipping.
					if ( ! empty( $instance_id ) ) {
						$linked_method = EasyPack_Helper()->get_method_linked_to_fs_by_instance_id( $instance_id );
					}

					if ( 0 === strpos( $k, 'flexible_shipping' ) ) {
						// check if linked FS methods are allowed for all products in cart.
						if ( 0 === strpos( $linked_method, 'easypack_' ) ) {
							if ( in_array( $k, $methods_allowed_by_cart ) ) {
								$rates_allowed[ $k ] = $rate_object;
							}
						}
					}
				}
				// if FS is not active or for not integrated methods.
				if ( 0 === strpos( $k, 'easypack_' ) ) {
					if ( in_array( $k, $methods_allowed_by_cart ) ) {
						$rates_allowed[ $k ] = $rate_object;
					}
				} else {
					$rates_allowed[ $k ] = $rate_object;
				}
			}
		}

		if ( ! empty( $rates_allowed ) ) {
			$packages[0]['rates'] = $rates_allowed;
		}

		return $packages;
	}

	public function woocommerce_get_settings_pages( $woocommerce_settings ) {
		new EasyPack_Settings_General();
		return $woocommerce_settings;
	}

	public function get_package_sizes() {
		return array(
			'small'  => __( 'A 8 x 38 x 64 cm', 'woocommerce-inpost' ),
			'medium' => __( 'B 19 x 38 x 64 cm', 'woocommerce-inpost' ),
			'large'  => __( 'C 41 x 38 x 64 cm', 'woocommerce-inpost' ),
		);
	}

	public function get_package_sizes_xlarge() {
		return array(
			'small'  => __( 'A 8 x 38 x 64 cm', 'woocommerce-inpost' ),
			'medium' => __( 'B 19 x 38 x 64 cm', 'woocommerce-inpost' ),
			'large'  => __( 'C 41 x 38 x 64 cm', 'woocommerce-inpost' ),
			'xlarge' => __( 'D 50 x 50 x 80 cm', 'woocommerce-inpost' ),
		);
	}

	public function get_package_sizes_display() {
		return array(
			'small'  => __( 'A', 'woocommerce-inpost' ),
			'medium' => __( 'B', 'woocommerce-inpost' ),
			'large'  => __( 'C', 'woocommerce-inpost' ),
		);
	}

	public function get_package_weights_parcel_machines() {
		return array(
			'1'  => __( '1 kg', 'woocommerce-inpost' ),
			'2'  => __( '2 kg', 'woocommerce-inpost' ),
			'5'  => __( '5 kg', 'woocommerce-inpost' ),
			'10' => __( '10 kg', 'woocommerce-inpost' ),
			'15' => __( '15 kg', 'woocommerce-inpost' ),
			'20' => __( '20 kg', 'woocommerce-inpost' ),
		);
	}

	public function get_package_weights_courier() {
		return array(
			'1'  => __( '1 kg', 'woocommerce-inpost' ),
			'2'  => __( '2 kg', 'woocommerce-inpost' ),
			'5'  => __( '5 kg', 'woocommerce-inpost' ),
			'10' => __( '10 kg', 'woocommerce-inpost' ),
			'15' => __( '15 kg', 'woocommerce-inpost' ),
			'20' => __( '20 kg', 'woocommerce-inpost' ),
			'25' => __( '25 kg', 'woocommerce-inpost' ),
		);
	}

	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'woocommerce-inpost',
			false,
			dirname( plugin_basename( WOOCOMMERCE_INPOST_PLUGIN_FILE ) ) . '/languages/'
		);
	}

	function getTemplatePathFull() {
		return implode( '/', array( $this->_pluginPath, $this->getTemplatePath() ) );
	}


	public function enqueue_scripts() {
		if ( is_cart() || is_checkout() || has_block( 'woocommerce/checkout' ) || 'yes' === get_option( 'easypack_debug_mode_enqueue_scripts' ) ) {
			wp_enqueue_style( 'easypack-front', $this->getPluginCss() . 'front.css' );
		}

		if ( is_checkout() || has_block( 'woocommerce/checkout' ) || get_option( 'easypack_debug_mode_enqueue_scripts' ) === 'yes' ) {
			wp_enqueue_style( 'easypack-jbox-css', $this->getPluginCss() . 'jBox.all.min.css', array(), WOOCOMMERCE_INPOST_PL_PLUGIN_VERSION );
			wp_enqueue_script(
				'easypack-jquery-modal',
				$this->getPluginJs() . 'jBox.all.min.js',
				array( 'jquery' ),
				WOOCOMMERCE_INPOST_PL_PLUGIN_VERSION,
				array( 'in_footer' => true )
			);

			if ( get_option( 'easypack_js_map_button' ) === 'yes' && ! has_block( 'woocommerce/checkout' ) ) {
				wp_enqueue_script(
					'easypack-front-js',
					$this->getPluginJs() . 'front.js',
					array( 'jquery' ),
					WOOCOMMERCE_INPOST_PL_PLUGIN_VERSION,
					array( 'in_footer' => true )
				);
				wp_localize_script(
					'easypack-front-js',
					'easypack_front_map',
					array(
						'button_text1'       => __( 'Select Parcel Locker', 'woocommerce-inpost' ),
						'button_text2'       => __( 'Change Parcel Locker', 'woocommerce-inpost' ),
						'selected_text'      => __( 'Selected parcel locker:', 'woocommerce-inpost' ),
						'geowidget_v5_token' => self::ENVIRONMENT_SANDBOX === self::get_environment()
							? get_option( 'easypack_geowidget_sandbox_token' )
							: get_option( 'easypack_geowidget_production_token' ),
						'inpost_methods'     => EasyPack_Helper()->get_inpost_methods(),
					)
				);
			}
		}
	}

	function enqueue_admin_scripts() {

		$current_screen = get_current_screen();

		$admin_css_path     = $this->_pluginPath . '/resources/assets/css/admin.css';
		$admin_css_path_ver = file_exists( $admin_css_path ) ? filemtime( $admin_css_path ) : WOOCOMMERCE_INPOST_PL_PLUGIN_VERSION;

		if ( is_a( $current_screen, 'WP_Screen' ) && 'inpost_page_easypack_shipment' === $current_screen->id ) {
			wp_enqueue_style( 'easypack-admin', $this->getPluginCss() . 'admin.css', array(), $admin_css_path_ver );
		}

		if ( 'yes' === get_option( 'woocommerce_custom_orders_table_enabled' ) ) {
			// HPOS usage is enabled.
			$post_id = isset( $_GET['id'] ) ? sanitize_text_field( $_GET['id'] ) : null;

			if ( $post_id ) {
				$post_type = get_post_type( $post_id );

				if ( 'shop_order_placehold' === $post_type || 'shop_order' === $post_type ) {
					wp_enqueue_style( 'easypack-admin', $this->getPluginCss() . 'admin.css', array(), $admin_css_path_ver );
				}
			} elseif ( is_a( $current_screen, 'WP_Screen' ) && 'woocommerce_page_wc-orders' === $current_screen->id ) {

					wp_enqueue_style( 'easypack-admin', $this->getPluginCss() . 'admin.css', array(), $admin_css_path_ver );
			}
		} else {

			$post_id = isset( $_GET['post'] ) ? sanitize_text_field( $_GET['post'] ) : null;

			if ( $post_id ) {
				$post_type = get_post_type( $post_id );

				if ( 'shop_order_placehold' === $post_type || 'shop_order' === $post_type ) {
					wp_enqueue_style( 'easypack-admin', $this->getPluginCss() . 'admin.css', array(), $admin_css_path_ver );
				}
			}
		}

		if ( EasyPack_Helper()->is_required_pages_for_modal() ) {
			wp_enqueue_style( 'easypack-admin-modal', $this->getPluginCss() . 'modal.css' );
			wp_enqueue_style( 'easypack-jbox-css', $this->getPluginCss() . 'jBox.all.min.css' );
		}

		wp_enqueue_script( 'easypack-admin', $this->getPluginJs() . 'admin.js', array( 'jquery' ) );
		wp_localize_script(
			'easypack-admin',
			'easypack_settings',
			array(
				'default_logo' => EasyPack()->getPluginImages() . 'logo/small/white.png',
			)
		);
		wp_enqueue_media(); // logo upload dependency

		if ( EasyPack_Helper()->is_required_pages_for_modal() ) {
			wp_enqueue_script( 'easypack-admin-modal', $this->getPluginJs() . 'modal.js', array( 'jquery' ) );
			wp_enqueue_script( 'easypack-jquery-modal', $this->getPluginJs() . 'jBox.all.min.js', array( 'jquery' ) );
		}

		if ( is_a( $current_screen, 'WP_Screen' ) && 'woocommerce_page_wc-settings' === $current_screen->id ) {
			if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'easypack_general' ) {
				wp_register_script(
					'easypack-admin-settings-page',
					$this->getPluginJs() . 'admin-settings-page.js',
					array( 'jquery' ),
					'',
					true
				);

				wp_localize_script(
					'easypack-admin-settings-page',
					'easypack_settings',
					array(
						'change_country_alert' => __( 'Are you sure to change the country?', 'woocommerce-inpost' ),
						'debug_notice'         => __(
							'Does not work simultaneously with the option \'JS mode of map button\'',
							'woocommerce-inpost'
						),
					)
				);

				wp_enqueue_script( 'easypack-admin-settings-page' );

				wp_enqueue_style( 'easypack-admin', $this->getPluginCss() . 'admin.css', array(), $admin_css_path_ver );
			}
		}

		if ( is_a( $current_screen, 'WP_Screen' ) && 'woocommerce_page_wc-settings' === $current_screen->id ) {
			if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'shipping' && isset( $_GET['instance_id'] ) ) {
				wp_register_script(
					'easypack-shipping-method-settings',
					$this->getPluginJs() . 'shipping-settings-page.js',
					array( 'jquery' ),
					'',
					true
				);

				wp_enqueue_script( 'easypack-shipping-method-settings' );

				wp_enqueue_style( 'easypack-admin', $this->getPluginCss() . 'admin.css', array(), $admin_css_path_ver );
			}
		}
	}


	/**
	 * @return ShipX_Shipment_Service
	 */
	public function get_shipment_service() {
		return new ShipX_Shipment_Service();
	}

	/**
	 * @return ShipX_Organization_Service
	 */
	public function get_organization_service() {
		return new ShipX_Organization_Service();
	}

	/**
	 * @return ShipX_Shipment_Price_Calculator_Service
	 */
	public function get_shipment_price_calculator_service() {
		return new ShipX_Shipment_Price_Calculator_Service();
	}

	/**
	 * @return ShipX_Courier_Pickup_Service
	 */
	public function get_courier_pickup_service() {
		return new ShipX_Courier_Pickup_Service();
	}

	/**
	 * @return ShipX_Shipment_Status_Service
	 */
	public function get_shipment_status_service() {
		return new ShipX_Shipment_Status_Service();
	}


	/**
	 * Replace custom logo link of shipping method for correct view
	 */
	public function change_order_item_meta_value( $value, $meta, $item ) {

		if ( $item === null ) {
			return $value;
		}

		if ( is_admin() && $item->get_type() === 'shipping' && $meta->key === 'logo' ) {
			if ( ! empty( $value ) ) {
				$value = '<img style="width: 60px; height: auto; background-size: cover;" src="' . esc_url( $value ) . '">';
			}
		}
		return $value;
	}

	/**
	 * Clear WC shipping methods cache
	 */
	public function clear_wc_shipping_cache() {
		\WC_Cache_Helper::get_transient_version( 'shipping', true );
	}

	/**
	 * Define path to Woocommerce templates in our plugin
	 */
	public function easypack_woo_templates( $template, $template_name, $template_path ) {
		global $woocommerce;
		$_template = $template;
		if ( ! $template_path ) {
			$template_path = $woocommerce->template_url;
		}

		$plugin_templates_path = untrailingslashit( EasyPack()->getPluginFullPath() ) . '/resources/templates/';

		$template = locate_template(
			array(
				$template_path . $template_name,
				$template_name,
			)
		);

		if ( ! $template && file_exists( $plugin_templates_path . $template_name ) ) {
			$template = $plugin_templates_path . $template_name;
		}

		if ( ! $template ) {
			$template = $_template;
		}

		return $template;
	}


	public function get_package_sizes_gabaryt() {
		return array(
			'small'  => __( 'Size A (8 x 38 x 64 cm)', 'woocommerce-inpost' ),
			'medium' => __( 'Size B (19 x 38 x 64 cm)', 'woocommerce-inpost' ),
			'large'  => __( 'Size C (41 x 38 x 64 cm)', 'woocommerce-inpost' ),
		);
	}


	private function get_or_update_data_from_api() {
		try {
			$organization_service = self::EasyPack()->get_organization_service();
			$organization         = $organization_service->query_organisation();
			if ( ! is_object( $organization ) ) {
				throw new Exception( 'Query organisation failed' );
			}
		} catch ( Exception $e ) {
			error_log( $e->getMessage() );
		}
	}


	public function enqueue_block_script() {
		if ( ( is_checkout() && has_block( 'woocommerce/checkout' ) ) || has_block( 'woocommerce/checkout' ) || 'yes' === get_option( 'easypack_debug_mode_enqueue_scripts' ) ) {

			$front_blocks_js_path = WOOCOMMERCE_INPOST_PLUGIN_DIR . '/resources/assets/js/front-blocks.js';
			wp_enqueue_script(
				'easypack-front-blocks-js',
				$this->getPluginJs() . 'front-blocks.js',
				array( 'jquery' ),
				file_exists( $front_blocks_js_path ) ? filemtime( $front_blocks_js_path ) : '1.4.6',
			);
			wp_localize_script(
				'easypack-front-blocks-js',
				'easypack_block',
				array(
					'button_text1'       => __( 'Select Parcel Locker', 'woocommerce-inpost' ),
					'button_text2'       => __( 'Change Parcel Locker', 'woocommerce-inpost' ),
					'phone_text'         => __( 'Phone number (required)', 'woocommerce-inpost' ),
					'geowidget_v5_token' => self::ENVIRONMENT_SANDBOX === self::get_environment()
						? get_option( 'easypack_geowidget_sandbox_token' )
						: get_option( 'easypack_geowidget_production_token' ),
				)
			);

		}
	}


	public function block_checkout_save_parcel_locker_in_order_meta( $order, $request ) {

		if ( ! $order ) {
			return;
		}

		$shipping_method_id = null;

		foreach ( $order->get_items( 'shipping' ) as $item_id => $item ) {
			$shipping_method_id          = $item->get_method_id();
			$shipping_method_instance_id = $item->get_instance_id();
		}

		$request_body = json_decode( $request->get_body(), true );

		if ( isset( $request_body['extensions']['inpost']['inpost-parcel-locker-id'] )
			&& ! empty( $request_body['extensions']['inpost']['inpost-parcel-locker-id'] ) ) {

			$parcel_machine_id = sanitize_text_field( $request_body['extensions']['inpost']['inpost-parcel-locker-id'] );

			update_post_meta( $order->get_ID(), '_parcel_machine_id', $parcel_machine_id );
			$order->update_meta_data( '_parcel_machine_id', $parcel_machine_id );
			$order->save();

			if ( EasyPack_Helper()->is_flexible_shipping_activated() ) {
				foreach ( $order->get_shipping_methods() as $shipping_method ) {
					$fs_instance_id = $shipping_method->get_instance_id();
				}

				$fs_method_name = EasyPack_Helper()->get_method_linked_to_fs_by_instance_id( $fs_instance_id );
				if ( ! empty( $fs_method_name ) ) {
					update_post_meta( $order->get_ID(), '_fs_easypack_method_name', $fs_method_name );
				}
			}
		}
	}



	public function filter_shipping_methods( $rates ) {

		global $woocommerce;

		// API doesn't accept COD amount > 5000.
		$methods_to_disable = array(
			'easypack_parcel_machines_economy_cod',
			'easypack_parcel_machines_cod',
			'easypack_shipping_courier_c2c_cod',
			'easypack_cod_shipping_courier',
		);

		$order_total_amount = floatval( $woocommerce->cart->cart_contents_total ) + floatval( $woocommerce->cart->tax_total );

		if ( $order_total_amount > 5000 ) {
			foreach ( $rates as $rate_key => $rate ) {
				if ( in_array( $rate->method_id, $methods_to_disable, true ) ) {
					unset( $rates[ $rate_key ] );
				}

				if ( EasyPack_Helper()->is_flexible_shipping_activated() ) {
					$linked_method = '';
					$instance_id   = $rate->instance_id;
					// check if some easypack method linked to Flexible Shipping.
					if ( ! empty( $instance_id ) ) {
						$linked_method = EasyPack_Helper()->get_method_linked_to_fs_by_instance_id( $instance_id );
					}

					if ( ! empty( $linked_method ) && 0 === strpos( $rate_key, 'flexible_shipping' ) ) {
						if ( 0 === strpos( $linked_method, 'easypack_' ) ) {
							if ( in_array( $linked_method, $methods_to_disable, true ) ) {
								unset( $rates[ $rate_key ] );
							}
						}
					}
				}
			}
		}

		return $rates;
	}


	public function inpost_product_table_callback() {

		check_ajax_referer( 'inpost_product_table', 'security' );

		if ( isset( $_POST['product_data'] ) && is_array( $_POST['product_data'] ) && isset( $_POST['product_id'] ) ) {
			$product_id = sanitize_text_field( wp_unslash( $_POST['product_id'] ) );

			$wc_product = wc_get_product( $product_id );

			if ( ! $wc_product || is_wp_error( $wc_product ) ) {
				$error = 'Product #' . $product_id . ' does not exist';
				wp_send_json( array( 'error' => $error ) );
				wp_die();
			}

			$locker_size_meta_key     = self::ATTRIBUTE_PREFIX . '_parcel_dimensions';
			$allowed_methods_meta_key = self::ATTRIBUTE_PREFIX . '_shipping_methods_allowed';
			if ( isset( $_POST['product_data']['allowed_methods'] ) ) {
				$allowed_methods = array_map( 'sanitize_text_field', wp_unslash( $_POST['product_data']['allowed_methods'] ) );
				update_post_meta( $product_id, $allowed_methods_meta_key, $allowed_methods );
			} else {
				$allowed_methods = array();
				update_post_meta( $product_id, $allowed_methods_meta_key, $allowed_methods );
			}
			if ( isset( $_POST['product_data']['locker_size'] ) && ! empty( $_POST['product_data']['locker_size'] ) ) {
				$locker_size = sanitize_text_field( wp_unslash( $_POST['product_data']['locker_size'] ) );
				update_post_meta( $product_id, $locker_size_meta_key, $locker_size );
			}
		}

		if ( isset( $_POST['all_data'] ) ) {
			$data_return = $_POST['all_data'];
		} else {
			wp_send_json( array( 'error' => esc_html__( 'Error in data', 'woocommerce-inpost' ) ) );
		}

		echo wp_json_encode( $data_return );
		wp_die();
	}

	function enqueue_product_table_assets() {

		$current_screen = get_current_screen();

		if ( is_a( $current_screen, 'WP_Screen' ) && 'inpost_page_easypack_product_settings' === $current_screen->id ) {

			$plugin_data            = new EasyPack();
			$product_table_css_path = dirname( WOOCOMMERCE_INPOST_PLUGIN_FILE ) . '/resources/assets/css/product-table.css';

			$product_table_script     = dirname( WOOCOMMERCE_INPOST_PLUGIN_FILE ) . '/resources/js/product-table.js';
			$product_table_script_ver = file_exists( $product_table_script ) ? filemtime( $product_table_script ) : WOOCOMMERCE_INPOST_PL_PLUGIN_VERSION;
			wp_enqueue_script(
				'easypack-product-table',
				$plugin_data->getPluginJs() . 'product-table.js',
				array( 'jquery' ),
				$product_table_script_ver,
				true
			);
			wp_localize_script(
				'easypack-product-table',
				'inpost_product_table',
				array(
					'nonce'     => wp_create_nonce( 'inpost_product_table' ),
					'admin_url' => admin_url( 'admin-ajax.php' ),
				)
			);

			$product_table_css_ver = file_exists( $product_table_css_path ) ? filemtime( $product_table_css_path ) : WOOCOMMERCE_INPOST_PL_PLUGIN_VERSION;
			wp_enqueue_style( 'easypack-product-table', $this->getPluginCss() . 'product-table.css', array(), $product_table_css_ver );
		}
	}


	/**
	 * Add custom fields to each shipping method.
	 */
	public function add_settings_to_flexible_shipping() {

		if ( EasyPack_Helper()->is_flexible_shipping_activated() ) {
			$shipping_methods = WC()->shipping->get_shipping_methods();
			foreach ( $shipping_methods as $shipping_method ) {
				if ( 'flexible_shipping_single' === $shipping_method->id ) {
					add_filter( 'woocommerce_shipping_instance_form_fields_' . $shipping_method->id, array( $this, 'add_map_field' ) );
				}
			}
		}
	}


	public function add_map_field( $settings ): array {

		$has_intergrations = false;

		$find_key = '';
		$i        = 1;
		foreach ( $settings as $key => $setting ) {

			// divide fs settings array and put our settings before this field.
			if ( 'method_integration' === $key ) {
				$find_key          = $i;
				$has_intergrations = true;
			}
			++$i;
		}
		// show our field near with native integration field.
		if ( $has_intergrations ) {

			$position = (int) $find_key - 1;

			$settings_begin = array_slice( $settings, 0, $position, true );

			$addtitional_settings = $this->settings_block_for_flexible_shipping();

			$settings_end = array_slice( $settings, $position, count( $settings ) - $position, true );

			$new_settings = array_merge( $settings_begin, $addtitional_settings, $settings_end );

			return $new_settings;

		} else {

			return $settings + $this->settings_block_for_flexible_shipping();
		}
	}


	public function check_paczka_weekend_fs_settings( $rates, $package ): array {

		if ( ! EasyPack_Helper()->is_flexible_shipping_activated() ) {
			return $rates;
		}

		foreach ( $rates as $rate_key => $rate ) {
			if ( 'easypack_parcel_machines_weekend' === EasyPack_Helper()->get_method_linked_to_fs_by_instance_id( $rate->instance_id ) ) {

				$paczka_weekend = new EasyPack_Shipping_Parcel_Machines_Weekend();
				if ( ! $paczka_weekend->check_allowed_interval_for_weekend( $rate->instance_id ) ) {
					unset( $rates[ $rate_key ] ); // hide Paczka w Weekend if not match into time interval.
				}
			}
		}

		return $rates;
	}


	public function settings_block_for_flexible_shipping(): array {
		$settings = array();
		if ( EasyPack_Helper()->is_flexible_shipping_activated() ) {
			$settings['fs_inpost_pl_method'] = array(
				'title'   => esc_html__( "Integration with 'InPost PL' plugin", 'woocommerce-inpost' ),
				'type'    => 'select',
				'default' => 'all',
				'options' => array(
					'0'                                 => esc_html__( 'None', 'woocommerce-inpost' ),
					'easypack_parcel_machines'          => esc_html__( 'InPost Locker 24/7', 'woocommerce-inpost' ),
					'easypack_parcel_machines_cod'      => esc_html__( 'InPost Locker 24/7 COD', 'woocommerce-inpost' ),
					'easypack_shipping_courier_c2c'     => esc_html__( 'InPost Courier C2C', 'woocommerce-inpost' ),
					'easypack_shipping_courier_c2c_cod' => esc_html__( 'InPost Courier C2C COD', 'woocommerce-inpost' ),
					'easypack_parcel_machines_weekend'  => esc_html__( 'InPost Locker Weekend', 'woocommerce-inpost' ),
					'easypack_shipping_courier'         => esc_html__( 'InPost Courier', 'woocommerce-inpost' ),
					'easypack_cod_shipping_courier'     => esc_html__( 'InPost Courier COD', 'woocommerce-inpost' ),
					'easypack_shipping_courier_local_express' => esc_html__( 'InPost Courier Local Express', 'woocommerce-inpost' ),
					'easypack_shipping_courier_le_cod'  => esc_html__( 'InPost Courier Local Express COD', 'woocommerce-inpost' ),
					'easypack_shipping_courier_local_standard' => esc_html__( 'InPost Courier Local Standard', 'woocommerce-inpost' ),
					'easypack_shipping_courier_local_standard_cod' => esc_html__( 'InPost Courier Local Standard COD', 'woocommerce-inpost' ),
					'easypack_shipping_courier_lse'     => esc_html__( 'InPost Courier Local Super Express', 'woocommerce-inpost' ),
					'easypack_shipping_courier_lse_cod' => esc_html__( 'InPost Courier Local Super Express COD', 'woocommerce-inpost' ),
					'easypack_shipping_courier_palette' => esc_html__( 'InPost Courier Palette', 'woocommerce-inpost' ),
					'easypack_shipping_courier_palette_cod' => esc_html__( 'InPost Courier Palette COD', 'woocommerce-inpost' ),

				),
			);

			$settings['fs_inpost_pl_weekend_day_from'] = array(
				'title'   => esc_html__( 'Available from day of week', 'woocommerce' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select fs-inpost-pl-weekend',
				'default' => '4',
				'options' => array(
					'1' => esc_html__( 'Monday', 'woocommerce-inpost' ),
					'2' => esc_html__( 'Tuesday', 'woocommerce-inpost' ),
					'3' => esc_html__( 'Wednesday', 'woocommerce-inpost' ),
					'4' => esc_html__( 'Thursday', 'woocommerce-inpost' ),
					'5' => esc_html__( 'Friday', 'woocommerce-inpost' ),
					'6' => esc_html__( 'Saturday', 'woocommerce-inpost' ),
				),
			);

			$settings['fs_inpost_pl_weekend_hour_from'] = array(
				'title'    => esc_html__( 'Available from hour', 'woocommerce-inpost' ),
				'type'     => 'time',
				'default'  => '',
				'desc_tip' => false,
				'class'    => 'fs-inpost-pl-weekend',
			);

			$settings['fs_inpost_pl_weekend_day_to'] = array(
				'title'   => esc_html__( 'Available to day of week', 'woocommerce' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select fs-inpost-pl-weekend',
				'default' => '5',
				'options' => array(
					'1' => esc_html__( 'Monday', 'woocommerce-inpost' ),
					'2' => esc_html__( 'Tuesday', 'woocommerce-inpost' ),
					'3' => esc_html__( 'Wednesday', 'woocommerce-inpost' ),
					'4' => esc_html__( 'Thursday', 'woocommerce-inpost' ),
					'5' => esc_html__( 'Friday', 'woocommerce-inpost' ),
					'6' => esc_html__( 'Saturday', 'woocommerce-inpost' ),
				),
			);

			$settings['fs_inpost_pl_weekend_hour_to'] = array(
				'title'    => esc_html__( 'Available to hour', 'woocommerce-inpost' ),
				'type'     => 'time',
				'default'  => '',
				'desc_tip' => false,
				'class'    => 'fs-inpost-pl-weekend',
			);
		}

		return $settings;
	}
}
