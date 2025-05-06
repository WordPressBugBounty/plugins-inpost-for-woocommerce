<?php
/**
 * Paczka w Weekend COD
 */

namespace InspireLabs\WoocommerceInpost\shipping;

use InspireLabs\WoocommerceInpost\EasyPack;
use InspireLabs\WoocommerceInpost\EasyPack_Helper;
use Exception;
use InspireLabs\WoocommerceInpost\EasyPack_API;
use InspireLabs\WoocommerceInpost\Geowidget_v5;
use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Model;
use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Parcel_Dimensions_Model;
use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Parcel_Model;
use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Parcel_Weight_Model;
use ReflectionException;
use InspireLabs\WoocommerceInpost\EmailFilters\TrackingInfoEmail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

if ( ! class_exists( 'EasyPack_Shipping_Parcel_Machines_Weekend_COD' ) ) {
	/**
	 * EasyPack_Shipping_Parcel_Machines_Weekend_COD
	 */
	class EasyPack_Shipping_Parcel_Machines_Weekend_COD extends EasyPack_Shipping_Parcel_Machines_Weekend {

		/**
		 * SERVICE_ID
		 */
		const SERVICE_ID = ShipX_Shipment_Model::SERVICE_INPOST_LOCKER_WEEKEND;

		/**
		 * NONCE_ACTION
		 */
		const NONCE_ACTION = self::SERVICE_ID;


		/**
		 * Constructor for shipping class
		 *
		 * @param int $instance_id Shipping method instance.
		 *
		 * @access public
		 * @return void
		 */
		public function __construct( $instance_id = 0 ) {
			parent::__construct();
			$this->init_form_fields();
			$this->instance_id        = absint( $instance_id );
			$this->supports           = array(
				'shipping-zones',
				'instance-settings',
			);
			$this->id                 = 'easypack_parcel_machines_weekend_cod';
			$this->method_description
				= esc_html__( 'Allow customers to pick up orders in weekend.', 'woocommerce-inpost' );
			$this->method_title       = esc_html__( 'InPost Locker Weekend COD', 'woocommerce-inpost' );
			$this->init();
		}


		/**
		 * Init form fields
		 *
		 * @return void
		 */
		public function init_form_fields(): void {

			$settings = array(
				array(
					'title'       => esc_html__( 'General settings', 'woocommerce-inpost' ),
					'type'        => 'title',
					'description' => '',
					'id'          => 'section_general_settings',
				),
				'logo_upload'                            => array(
					'name'  => esc_html__( 'Change logo', 'woocommerce-inpost' ),
					'title' => esc_html__( 'Upload custom logo', 'woocommerce-inpost' ),
					'type'  => 'logo_upload',
					'id'    => 'logo_upload',
				),
				'title'                                  => array(
					'title'    => esc_html__( 'Method title', 'woocommerce-inpost' ),
					'type'     => 'text',
					'default'  => esc_html__( 'InPost Locker Weekend COD', 'woocommerce-inpost' ),
					'desc_tip' => false,
				),
				'insurance_inpost_pl'                    => array(
					'title'       => esc_html__( 'Insurance', 'woocommerce-inpost' ),
					'label'       => esc_html__( 'Set from order amount', 'woocommerce-inpost' ),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no',
					'desc_tip'    => true,
				),
				'insurance_value_inpost_pl'              => array(
					'title'             => esc_html__( 'Default insurance amount', 'woocommerce-inpost' ),
					'type'              => 'number',
					'custom_attributes' => array(
						'step' => 'any',
						'min'  => '0',
					),
					'default'           => '',
					'desc_tip'          => false,
					'placeholder'       => '0.00',
				),

				array(
					'title'       => esc_html__( 'Time settings', 'woocommerce-inpost' ),
					'type'        => 'title',
					'description' => '',
					'id'          => 'section_easypack_weekend_settings',
				),

				'day_from'                               => array(
					'title'   => esc_html__( 'Available from day of week', 'woocommerce-inpost' ),
					'type'    => 'select',
					'class'   => 'wc-enhanced-select',
					'default' => '4',
					'options' => array(
						'1' => esc_html__( 'Monday', 'woocommerce-inpost' ),
						'2' => esc_html__( 'Tuesday', 'woocommerce-inpost' ),
						'3' => esc_html__( 'Wednesday', 'woocommerce-inpost' ),
						'4' => esc_html__( 'Thursday', 'woocommerce-inpost' ),
						'5' => esc_html__( 'Friday', 'woocommerce-inpost' ),
						'6' => esc_html__( 'Saturday', 'woocommerce-inpost' ),
						'7' => esc_html__( 'Sunday', 'woocommerce-inpost' ),
					),
				),

				'hour_from'                              => array(
					'title'    => esc_html__( 'Available from hour', 'woocommerce-inpost' ),
					'type'     => 'time',
					'default'  => '',
					'desc_tip' => false,
				),

				'day_to'                                 => array(
					'title'   => esc_html__( 'Available to day of week', 'woocommerce-inpost' ),
					'type'    => 'select',
					'class'   => 'wc-enhanced-select',
					'default' => '5',
					'options' => array(
						'1' => esc_html__( 'Monday', 'woocommerce-inpost' ),
						'2' => esc_html__( 'Tuesday', 'woocommerce-inpost' ),
						'3' => esc_html__( 'Wednesday', 'woocommerce-inpost' ),
						'4' => esc_html__( 'Thursday', 'woocommerce-inpost' ),
						'5' => esc_html__( 'Friday', 'woocommerce-inpost' ),
						'6' => esc_html__( 'Saturday', 'woocommerce-inpost' ),
						'7' => esc_html__( 'Sunday', 'woocommerce-inpost' ),
					),
				),

				'hour_to'                                => array(
					'title'    => esc_html__( 'Available to hour', 'woocommerce-inpost' ),
					'type'     => 'time',
					'default'  => '',
					'desc_tip' => false,
				),

				array(
					'title'       => esc_html__( 'Price settings', 'woocommerce-inpost' ),
					'type'        => 'title',
					'description' => '',
					'id'          => 'section_easypack_weekend_price_settings',
				),

				'free_shipping_cost'                     => array(
					'title'             => esc_html__( 'Free shipping', 'woocommerce-inpost' ),
					'type'              => 'number',
					'custom_attributes' => array(
						'step' => 'any',
						'min'  => '0',
					),
					'default'           => '',
					'desc_tip'          => esc_html__(
						'Enter the amount of the order from which the shipping will be free (does not include virtual products). ',
						'woocommerce-inpost'
					),
					'placeholder'       => '0.00',
				),
				'show_free_shipping_label'               => array(
					'title'       => '',
					'label'       => esc_html__( 'Add label (free) to the end of title of shipping method', 'woocommerce-inpost' ),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'yes',
					'desc_tip'    => true,
				),
				'apply_minimum_order_rule_before_coupon' => array(
					'title'       => esc_html__( 'Coupons discounts', 'woocommerce' ),
					'label'       => esc_html__( 'Apply minimum order rule before coupon discount', 'woocommerce' ),
					'type'        => 'checkbox',
					'description' => esc_html__( 'If checked, free shipping would be available based on pre-discount order amount.', 'woocommerce' ),
					'default'     => 'no',
					'desc_tip'    => true,
				),
				'flat_rate'                              => array(
					'title'   => __( 'Flat rate', 'woocommerce-inpost' ),
					'type'    => 'checkbox',
					'label'   => esc_html__( 'Set a flat-rate shipping fee for the entire order.', 'woocommerce-inpost' ),
					'class'   => 'easypack_flat_rate',
					'default' => 'yes',
				),
				'cost_per_order'                         => array(
					'title'             => esc_html__( 'Cost per order', 'woocommerce-inpost' ),
					'type'              => 'number',
					'custom_attributes' => array(
						'step' => 'any',
						'min'  => '0',
					),
					'class'             => 'easypack_cost_per_order',
					'default'           => '',
					'desc_tip'          => esc_html__(
						'Set a flat-rate shipping for all orders',
						'woocommerce-inpost'
					),
					'placeholder'       => '0.00',
				),
				'tax_status'                             => array(
					'title'   => esc_html__( 'Tax status', 'woocommerce' ),
					'type'    => 'select',
					'class'   => 'wc-enhanced-select',
					'default' => 'none',
					'options' => array(
						'none'    => _x( 'None', 'Tax status', 'woocommerce-inpost' ),
						'taxable' => esc_html__( 'Taxable', 'woocommerce-inpost' ),
					),
				),

				array(
					'title'       => esc_html__( 'Rates table', 'woocommerce-inpost' ),
					'type'        => 'title',
					'description' => '',
					'id'          => 'section_general_settings',
				),
				'based_on'                               => array(
					'title'       => esc_html__( 'Based on', 'woocommerce-inpost' ),
					'type'        => 'select',
					'desc_tip'    => esc_html__(
						'Select the method of calculating shipping cost. If the cost of shipping is to be calculated based on the weight of the cart and the products do not have a defined weight, the cost will be calculated incorrectly.',
						'woocommerce-inpost'
					),
					'description' => sprintf(
						'<b id="easypack_dimensions_warning" style="color:red;display:none">%1s</b> %1s',
						esc_html__( 'Attention!', 'woocommerce-inpost' ),
						esc_html__( 'Set the dimension in the settings of each product. The default value is size \'A\'', 'woocommerce-inpost' )
					),
					'class'       => 'wc-enhanced-select easypack_based_on',
					'options'     => array(
						'price'  => esc_html__( 'Price', 'woocommerce-inpost' ),
						'weight' => esc_html__( 'Weight', 'woocommerce-inpost' ),
                        'product_qty' => esc_html__( 'Products qty', 'woocommerce-inpost' ),
						'size'   => esc_html__( 'Size (A, B, C)', 'woocommerce-inpost' ),
					),
				),
				'rates'                                  => array(
					'title'    => '',
					'type'     => 'rates',
					'class'    => 'easypack_rates',
					'default'  => '',
					'desc_tip' => '',
				),

				'gabaryt_a'                              => array(
					'title'             => esc_html__( 'Size A', 'woocommerce-inpost' ),
					'type'              => 'number',
					'custom_attributes' => array(
						'step' => 'any',
						'min'  => '0',
					),
					'class'             => 'easypack_gabaryt_a',
					'default'           => '',
					'desc_tip'          => esc_html__( 'Set a flat-rate shipping for size A', 'woocommerce-inpost' ),
					'placeholder'       => '0.00',
				),

				'gabaryt_b'                              => array(
					'title'             => esc_html__( 'Size B', 'woocommerce-inpost' ),
					'type'              => 'number',
					'custom_attributes' => array(
						'step' => 'any',
						'min'  => '0',
					),
					'class'             => 'easypack_gabaryt_b',
					'default'           => '',
					'desc_tip'          => esc_html__( 'Set a flat-rate shipping for size B', 'woocommerce-inpost' ),
					'placeholder'       => '0.00',
				),

				'gabaryt_c'                              => array(
					'title'             => esc_html__( 'Size C', 'woocommerce-inpost' ),
					'type'              => 'number',
					'custom_attributes' => array(
						'step' => 'any',
						'min'  => '0',
					),
					'class'             => 'easypack_gabaryt_c',
					'default'           => '',
					'desc_tip'          => esc_html__( 'Set a flat-rate shipping for size C', 'woocommerce-inpost' ),
					'placeholder'       => '0.00',
				),
			);

			$settings = $this->add_shipping_classes_settings( $settings );

			$this->instance_form_fields = $settings;
			$this->form_fields          = $settings;
		}


		/**
		 *  Ajax create shipment model
		 *
		 * @return ShipX_Shipment_Model
		 */
		public static function ajax_create_shipment_model() {

			$shipmentService = EasyPack::EasyPack()->get_shipment_service();

			$order_id = sanitize_text_field( wp_unslash( $_POST['order_id'] ) );

			$order        = wc_get_order( $order_id );
			$order_amount = '';
			if ( is_object( $order ) ) {
				$order_amount = $order->get_total();
			}

			$insurance_amount = '';
			$reference_number = '';
			$send_method      = '';
			$parcels          = array();

			// if Bulk create shipments.
			if ( isset( $_POST['action'] ) && 'easypack_bulk_create_shipments' === $_POST['action'] ) {

				$parcels = array();

				if ( 'easypack_bulk_create_shipments_A' === $_POST['locker_size'] ) {
					$parcels = array( 'small' );
				} elseif ( 'easypack_bulk_create_shipments_B' === $_POST['locker_size'] ) {
					$parcels = array( 'medium' );
				} elseif ( 'easypack_bulk_create_shipments_C' === $_POST['locker_size'] ) {
					$parcels = array( 'large' );
				} else {
					$parcels = get_post_meta( $order_id, '_easypack_parcels', true )
						? get_post_meta( $order_id, '_easypack_parcels', true )
						: array( Easypack_Helper()->get_parcel_size_from_settings( $order_id ) );
				}

				$parcel_machine_id = get_post_meta( $order_id, '_parcel_machine_id', true );

				$cod_amount = $parcels[0]['cod_amount'] ?? $order_amount;

				$insurance_amount = EasyPack_Helper()->get_insurance_amount( $order_id );

				$reference_number = EasyPack_Helper()->get_maybe_custom_reference_number( $order_id );

				if ( 'yes' === get_option( 'easypack_add_order_note' ) ) {
					$order_note = '';
					$order      = wc_get_order( $order_id );
					if ( $order && ! is_wp_error( $order ) && is_object( $order ) ) {
						$order_note = $order->get_customer_note();
					}
					$reference_number = $reference_number . ' ' . $order_note;
				}

				$send_method = get_post_meta( $order_id, '_easypack_send_method', true )
					? get_post_meta( $order_id, '_easypack_send_method', true )
					: get_option( 'easypack_default_send_method' );

			} else {

				$parcel_machine_id = isset( $_POST['parcel_machine_id'] )
					? sanitize_text_field( wp_unslash( $_POST['parcel_machine_id'] ) ) : '';

				if ( isset( $_POST['insurance_amounts'] ) && is_array( $_POST['insurance_amounts'] ) ) {
					$insurance_amounts = array_map( 'sanitize_text_field', $_POST['insurance_amounts'] );

					if ( isset( $insurance_amounts[0] ) && is_numeric( $insurance_amounts[0] ) && floatval( $insurance_amounts[0] ) > 0 ) {
						$insurance_amount = $insurance_amounts[0];
					}
				}

				$cod_amounts = isset( $_POST['cod_amounts'] )
					? array_map( 'sanitize_text_field', $_POST['cod_amounts'] )
					: null;
				$cod_amount  = isset( $cod_amounts[0] ) ? $cod_amounts[0] : $order_amount;

				$send_method = isset( $_POST['send_method'] )
					? sanitize_text_field( $_POST['send_method'] )
					: 'parcel_machine';

				$reference_number = isset( $_POST['reference_number'] )
					? sanitize_text_field( $_POST['reference_number'] )
					: $order_id;

				$parcels = isset( $_POST['parcels'] )
					? array_map( 'sanitize_text_field', $_POST['parcels'] )
					: array( get_option( 'easypack_default_package_size' ) );
			}

			$shipment = $shipmentService->create_shipment_object_by_shiping_data(
				$parcels,
				(int) $order_id,
				$send_method,
				self::SERVICE_ID,
				array(),
				$parcel_machine_id,
				$cod_amount,
				$insurance_amount,
				$reference_number,
				null
			);
			$shipment->getInternalData()->setOrderId( (int) $order_id );

			return $shipment;
		}


		/**
		 * Metabox in order details
		 *
		 * @param \WC_Order | \WP_Post $post $post.
		 * @param bool                 $output $output.
		 * @param null                 $shipment $shipment.
		 * @param bool                 $additional_package $additional_package.
		 *
		 * @return string
		 */
		public static function order_metabox_content(
			$post,
			$output = true,
			$shipment = null,
			$additional_package = false
		): string {

			$out = '';

			if ( ! $output ) {
				ob_start();
			}
			$shipment_service = EasyPack::EasyPack()->get_shipment_service();

			if ( is_a( $post, 'WC_Order' ) ) {
				$order_id = $post->get_id();
			} else {
				$order_id = $post->ID;
			}

			$geowidget_config = ( new Geowidget_v5() )->get_pickup_delivery_configuration( 'easypack_parcel_machines_weekend_cod' );
			if ( false === $shipment instanceof ShipX_Shipment_Model ) {
				$shipment = $shipment_service->get_shipment_by_order_id( $order_id );
			}

			if ( $shipment instanceof ShipX_Shipment_Model
				&& false === $shipment_service->is_shipment_match_to_current_api( $shipment )
			) {
				wp_nonce_field( self::NONCE_ACTION, 'wp_nonce' );
				$wrong_api_env = true;
				include 'views/html-order-matabox-parcel-machines-weekend-cod.php';
				if ( ! $output ) {
					$out = ob_get_clean();

					return $out;
				}

				return $out;
			}
			$wrong_api_env = false;

			$order = wc_get_order( $order_id );

			if ( null !== $shipment && ! $additional_package ) {
				$parcels      = $shipment->getParcels();
				$tracking_url = $shipment->getInternalData()->getTrackingNumber();
				$stickers_url = $shipment->getInternalData()->getLabelUrl();

				$api_status_update_response = array();

				if ( true === $output ) {
					$status_srv                 = EasyPack()->get_shipment_status_service();
					$api_status_update_response = $status_srv->refreshStatus( $shipment );
				}

				$status            = $shipment->getInternalData()->getStatus();
				$parcel_machine_id = $shipment->getCustomAttributes()->getTargetPoint();
				$send_method       = $shipment->getCustomAttributes()->getSendingMethod();
				$disabled          = true;
			} else {
				$package_sizes_display = EasyPack()->get_package_sizes_display();
				$parcels               = array();
				$parcel                = new ShipX_Shipment_Parcel_Model();
				$parcel->setTemplate( get_option( 'easypack_default_package_size', 'small' ) );
				$parcels[] = $parcel;

				$parcel_machine_from_order = get_post_meta( $order_id, '_parcel_machine_id', true );
				$parcel_machine_id         = ! empty( $parcel_machine_from_order )
					? $parcel_machine_from_order
					: get_option( 'easypack_default_machine_id' );

				$tracking_url = false;
				$status       = 'new';
				$send_method  = get_option( 'easypack_default_send_method', 'parcel_machine' );
				$disabled     = false;
			}
			$package_sizes = EasyPack()->get_package_sizes();

			$send_method_disabled = false;

			$send_methods = array(
				'parcel_machine' => esc_html__( 'Parcel locker', 'woocommerce-inpost' ),
				'courier'        => esc_html__( 'Courier', 'woocommerce-inpost' ),
			);

			$selected_service = $shipment_service->get_customer_service_name_by_id( self::SERVICE_ID );
			include 'views/html-order-matabox-parcel-machines-weekend-cod.php';

			wp_nonce_field( self::NONCE_ACTION, 'wp_nonce' );
			if ( ! $output ) {
				$out = ob_get_clean();

				return $out;
			}

			return $out;
		}

		/**
		 * Add order metabox
		 *
		 * @param string               $post_type Post type.
		 * @param \WC_Order | \WP_Post $post $post.
		 *
		 * @return void
		 */
		public function add_meta_boxes( $post_type, $post ) {

			$order_id = null;

			if ( 'yes' === get_option( 'woocommerce_custom_orders_table_enabled' ) ) {
				// HPOS usage is enabled.
				if ( is_a( $post, 'WC_Order' ) ) {
					$order_id = $post->get_id();
				}
			} else {
				// Traditional orders are in use.
				if ( is_object( $post ) && $post->post_type == 'shop_order' ) {
					$order_id = $post->ID;
				}
			}

			if ( $order_id ) {
				$order = wc_get_order( $order_id );
				// show metabox only for matched shipping method (plus Flexible shipping integration).
				$fs_method_name = get_post_meta( $order_id, '_fs_easypack_method_name', true );

				if ( $order->has_shipping_method( $this->id ) || $fs_method_name === $this->id ) {
					add_meta_box(
						'easypack_parcel_machines',
						__( 'InPost', 'woocommerce-inpost' )
						. $this->get_logo(),
						array( $this, 'order_metabox' ),
						null,
						'side',
						'default'
					);
				}
			}
		}

		/**
		 * Order metabox
		 *
		 * @param \WC_Order | \WP_Post $post $post.
		 *
		 * @return void
		 */
		public function order_metabox( $post ) {

			self::order_metabox_content( $post );
		}


		/**
		 * Get PWW logo
		 *
		 * @return string
		 */
		public function get_logo(): string {

			$custom_logo = null;

			if ( empty( $custom_logo ) ) {
				return '<img style="height:22px; float:right;" src="' . untrailingslashit( EasyPack()->getPluginImages() . 'logo/inpost-paczka-w-weekend.png" />' );
			} else {
				return '<img style="height:22px; float:right;" src="' . untrailingslashit( $custom_logo );
			}
		}

		/**
		 * Ajax create package Weekend COD
		 *
		 * @param bool $courier $courier.
		 *
		 * @throws ReflectionException ReflectionException.
		 */
		public static function ajax_create_package( $courier = false ) {
			$ret = array( 'status' => 'ok' );

			$shipment_model = self::ajax_create_shipment_model();

			$order_id         = $shipment_model->getInternalData()->getOrderId();
			$shipment_service = EasyPack::EasyPack()->get_shipment_service();
			$shipment_array   = $shipment_service->shipment_to_array( $shipment_model );
			$status_service   = EasyPack::EasyPack()->get_shipment_status_service();
			$label_url        = '';

			// required parameter for Paczka w Weekend.
			$shipment_array['end_of_week_collection'] = true;
			
			if( empty( $shipment_array['receiver']['address']['country_code'] ) ) {
                $shipment_array['receiver']['address']['country_code'] = 'PL';
            }

			$shipment_data = array();

            \wc_get_logger()->debug( 'PWW COD TO API: ', array( 'source' => 'pww-cod-log' ) );
            \wc_get_logger()->debug( print_r( $shipment_array, true), array( 'source' => 'pww-cod-log' ) );
            //die();

			try {

				$response = EasyPack_API()->customer_parcel_create( $shipment_array );

                \wc_get_logger()->debug( 'PWW COD API RESP: ', array( 'source' => 'pww-cod-log' ) );
                \wc_get_logger()->debug( print_r( $response, true), array( 'source' => 'pww-cod-log' ) );

				$shipment_data = self::save_to_order_meta(
					$order_id,
					$shipment_model,
					$shipment_service,
					$status_service,
					$shipment_array,
					$response
				);

			} catch ( Exception $e ) {
				$ret['status']  = 'error';
				$ret['message'] = esc_html__( 'There are some errors. Please fix it: <br>', 'woocommerce-inpost' ) . EasyPack_API()->translate_error( $e->getMessage() );
			}

			if ( 'ok' === $ret['status'] ) {
				$order        = wc_get_order( $order_id );
				$tracking_url = EasyPack_Helper()->get_tracking_url();

				$order->add_order_note(
					esc_html__( 'Shipment created', 'woocommerce-inpost' ),
					false
				);

				EasyPack_Helper()->set_order_status_completed( $order_id );

				if ( isset( $_POST['action'] ) && 'easypack_bulk_create_shipments' === $_POST['action'] ) {
					if ( ! empty( $shipment_data['tracking'] ) ) {
						$ret['tracking_number'] = $shipment_data['tracking'];
					} else {
						$ret['api_status'] = $status_service->getStatusDescription( $response['status'] );
					}
				} else {
					$ret['content'] = self::order_metabox_content( get_post( $order_id ), false, $shipment_model );
					if ( isset( $shipment_data['tracking'] ) && ! empty( $shipment_data['tracking'] ) ) {
						$ret['tracking_number'] = $shipment_data['tracking'];
						$ret['inpost_id']       = $shipment_data['inpost_id'];
					}
					$ret['api_status'] = $shipment_data['status'];
					$ret['ref_number'] = $shipment_array['reference'];
					$ret['service']    = $shipment_data['service'];
				}

				if ( isset( $shipment_data['tracking'] ) && ! empty( $shipment_data['tracking'] ) ) {
					( new TrackingInfoEmail() )->send_tracking_info_email( $order, $tracking_url, $shipment_data['tracking'] );
				}
			}
			echo wp_json_encode( $ret );
			wp_die();
		}
	}
}
