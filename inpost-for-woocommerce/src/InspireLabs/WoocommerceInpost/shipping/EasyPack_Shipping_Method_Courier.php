<?php

namespace InspireLabs\WoocommerceInpost\shipping;

use InspireLabs\WoocommerceInpost\EasyPack;
use InspireLabs\WoocommerceInpost\EasyPack_Helper;
use Exception;
use InspireLabs\WoocommerceInpost\EasyPack_API;
use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Model;
use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Parcel_Dimensions_Model;
use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Parcel_Model;
use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Parcel_Weight_Model;
use ReflectionException;
use InspireLabs\WoocommerceInpost\EmailFilters\TrackingInfoEmail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'EasyPack_Shipping_Method_Courier' ) ) {
	class EasyPack_Shipping_Method_Courier extends EasyPack_Shippng_Parcel_Machines {

		const WP_AJAX_ACTION_CREATE = 'courier_create_package';

		const SERVICE_ID = ShipX_Shipment_Model::SERVICE_INPOST_COURIER_STANDARD;

		const NONCE_ACTION = self::SERVICE_ID;

		/**
		 * Constructor for shipping class
		 *
		 * @access public
		 * @return void
		 */
		public function __construct( $instance_id = 0 ) {
			$this->init_form_fields();
			$this->instance_id  = absint( $instance_id );
			$this->supports     = array(
				'shipping-zones',
				'instance-settings',
			);
			$this->id           = 'easypack_shipping_courier';
			$this->method_title = __( 'InPost Courier', 'woocommerce-inpost' );
			$this->init();
		}

		public function generate_rates_html( $key, $data ) {
			$rates = EasyPack_Helper()->get_saved_method_rates( $this->id, $this->instance_id );
			ob_start();
			include 'views/html-rates-courier.php';

			return ob_get_clean();
		}

		public function init_form_fields() {

			$settings = array(
				array(
					'title'       => __( 'General settings', 'woocommerce-inpost' ),
					'type'        => 'title',
					'description' => '',
					'id'          => 'section_general_settings',
				),
				'logo_upload'                            => array(
					'name'  => __( 'Change logo', '' ),
					'title' => __( 'Upload custom logo', 'woocommerce-inpost' ),
					'type'  => 'logo_upload',
					'id'    => 'logo_upload',
				),
				'title'                                  => array(
					'title'             => __( 'Method title', 'woocommerce-inpost' ),
					'type'              => 'text',
					'default'           => __( 'InPost Courier', 'woocommerce-inpost' ),
					'custom_attributes' => array( 'required' => 'required' ),
					'desc_tip'          => false,
				),
				'sms'                                    => array(
					'title'       => __( 'SMS notifications', 'woocommerce-inpost' ),
					'label'       => '',
					'type'        => 'checkbox',
					'description' => __( '', 'woocommerce-inpost' ),
					'default'     => 'no',
					'desc_tip'    => true,
				),
				'email'                                  => array(
					'title'       => __( 'Email notifications', 'woocommerce-inpost' ),
					'label'       => '',
					'type'        => 'checkbox',
					'description' => __( '', 'woocommerce-inpost' ),
					'default'     => 'no',
					'desc_tip'    => true,
				),
				'insurance_inpost_pl'                    => array(
					'title'       => __( 'Insurance', 'woocommerce-inpost' ),
					'label'       => __( 'Set from order amount', 'woocommerce-inpost' ),
					'type'        => 'checkbox',
					'description' => __( '', 'woocommerce-inpost' ),
					'default'     => 'no',
					'desc_tip'    => true,
				),
				'insurance_value_inpost_pl'              => array(
					'title'             => __( 'Default insurance amount', 'woocommerce-inpost' ),
					'type'              => 'number',
					'custom_attributes' => array(
						'step' => 'any',
						'min'  => '0',
					),
					'default'           => '',
					'desc_tip'          => false,
					'placeholder'       => '0.00',
				),
				'free_shipping_cost'                     => array(
					'title'             => __( 'Free shipping', 'woocommerce-inpost' ),
					'type'              => 'number',
					'custom_attributes' => array(
						'step' => 'any',
						'min'  => '0',
					),
					'default'           => '',
					'desc_tip'          => __(
						'Enter the amount of the contract, from which shipping will be free (does not include virtual products).',
						'woocommerce-inpost'
					),
					'placeholder'       => '0.00',
				),
				'show_free_shipping_label'               => array(
					'title'       => __( '', 'woocommerce-inpost' ),
					'label'       => __( 'Add label (free) to the end of title of shipping method', 'woocommerce-inpost' ),
					'type'        => 'checkbox',
					'description' => __( '', 'woocommerce-inpost' ),
					'default'     => 'yes',
					'desc_tip'    => true,
				),
				'apply_minimum_order_rule_before_coupon' => array(
					'title'       => __( 'Coupons discounts', 'woocommerce' ),
					'label'       => __( 'Apply minimum order rule before coupon discount', 'woocommerce' ),
					'type'        => 'checkbox',
					'description' => __( 'If checked, free shipping would be available based on pre-discount order amount.', 'woocommerce' ),
					'default'     => 'no',
					'desc_tip'    => true,
				),
				'flat_rate'                              => array(
					'title'   => __( 'Flat rate', 'woocommerce-inpost' ),
					'type'    => 'checkbox',
					'label'   => __( 'Set a flat-rate shipping fee for the entire order.', 'woocommerce-inpost' ),
					'class'   => 'easypack_flat_rate',
					'default' => 'yes',
				),
				'cost_per_order'                         => array(
					'title'             => __( 'Cost per order', 'woocommerce-inpost' ),
					'type'              => 'number',
					'custom_attributes' => array(
						'step' => 'any',
						'min'  => '0',
					),
					'class'             => 'easypack_cost_per_order',
					'default'           => '',
					'desc_tip'          => __(
						'Set a flat-rate shipping for all orders',
						'woocommerce-inpost'
					),
					'placeholder'       => '0.00',
				),
				'tax_status'                             => array(
					'title'   => __( 'Tax status', 'woocommerce' ),
					'type'    => 'select',
					'class'   => 'wc-enhanced-select',
					'default' => 'none',
					'options' => array(
						'none'    => _x( 'None', 'Tax status', 'woocommerce-inpost' ),
						'taxable' => __( 'Taxable', 'woocommerce-inpost' ),
					),
				),
				'based_on'                               => array(
					'title'    => esc_html__( 'Based on', 'woocommerce-inpost' ),
					'type'     => 'select',
					'desc_tip' => esc_html__(
						'Select the method of calculating shipping cost. If the cost of shipping is to be calculated based on the weight of the cart and the products do not have a defined weight, the cost will be calculated incorrectly.',
						'woocommerce-inpost'
					),
					'class'    => 'wc-enhanced-select easypack_based_on',
					'options'  => array(
						'price'       => esc_html__( 'Price', 'woocommerce-inpost' ),
						'weight'      => esc_html__( 'Weight', 'woocommerce-inpost' ),
						'product_qty' => esc_html__( 'Products qty', 'woocommerce-inpost' ),
					),
				),
				'rates'                                  => array(
					'title'    => '',
					'type'     => 'rates',
					'class'    => 'easypack_rates',
					'default'  => '',
					'desc_tip' => '',
				),
			);

			$settings = $this->add_shipping_classes_settings( $settings );

			$this->form_fields          = $settings;
			$this->instance_form_fields = $settings;
		}


		public function process_admin_options() {
			parent::process_admin_options();
			EasyPack_API()->clear_cache();
		}

		public function save_post( $post_id ) {
			if ( ! isset( $_POST['wp_nonce'] ) ) {
				return;
			}
			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $_POST['wp_nonce'], self::NONCE_ACTION ) ) {
				return;
			}
			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			$status = get_post_meta( $post_id, '_easypack_status', true );
			if ( $status == '' ) {
				$status = 'new';
			}

			if ( $status == 'new' ) {

				EasyPack_Helper()->set_data_to_order_meta( $_POST, $post_id );
			}
		}

		public function order_metabox( $post ) {
			self::order_metabox_content( $post );
		}

		public function woocommerce_review_order_after_shipping() {
		}




		/**
		 * @return ShipX_Shipment_Model
		 */
		public static function ajax_create_shipment_model() {
			$shipmentService = EasyPack::EasyPack()->get_shipment_service();

			$order_id = sanitize_text_field( $_POST['order_id'] );
			$order    = wc_get_order( $order_id );

			$cod_amount       = null;
			$insurance_amount = '';
			$reference_number = '';
			$send_method      = '';
			$parcels          = array();

			$courier_parcel_data = array();

			// if Bulk create shipments
			if ( isset( $_POST['action'] ) && $_POST['action'] === 'easypack_bulk_create_shipments' ) {

				$courier_parcel_data = EasyPack_Helper()->get_dimensions_for_courier_shipments( $order_id );

				$parcels = get_post_meta( $order_id, '_easypack_parcels', true )
					? get_post_meta( $order_id, '_easypack_parcels', true )
					: array( get_option( 'easypack_default_package_size' ) );

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
					: 'courier';

			} else {

				$courier_parcel_data = array(
					'length'       => isset( $_POST['parcel_length'] ) ? sanitize_text_field( $_POST['parcel_length'] ) : '',
					'width'        => isset( $_POST['parcel_width'] ) ? sanitize_text_field( $_POST['parcel_width'] ) : '',
					'height'       => isset( $_POST['parcel_height'] ) ? sanitize_text_field( $_POST['parcel_height'] ) : '',
					'weight'       => isset( $_POST['parcel_weight'] ) ? sanitize_text_field( $_POST['parcel_weight'] ) : '',
					'non_standard' => isset( $_POST['parcel_non_standard'] ) ? sanitize_text_field( $_POST['parcel_non_standard'] ) : '',
				);

				if ( isset( $_POST['insurance_amounts'] ) && is_array( $_POST['insurance_amounts'] ) ) {
					$insurance_amounts = array_map( 'sanitize_text_field', $_POST['insurance_amounts'] );

					if ( isset( $insurance_amounts[0] ) && is_numeric( $insurance_amounts[0] ) && floatval( $insurance_amounts[0] ) > 0 ) {
						$insurance_amount = $insurance_amounts[0];
					}
				}

				$send_method = isset( $_POST['send_method'] )
					? sanitize_text_field( $_POST['send_method'] )
					: 'courier';

				$reference_number = isset( $_POST['reference_number'] )
					? sanitize_text_field( $_POST['reference_number'] )
					: $order_id;

				$parcels = array();
				if ( isset( $_POST['parcel_mode'] ) && $_POST['parcel_mode'] === 'wielopaki' ) {
					if ( isset( $_POST['parcels'] ) && is_array( $_POST['parcels'] ) ) {
						foreach ( $_POST['parcels'] as $i => $p ) {
							$parcels[ $i ]['id']                   = isset( $p['id'] ) ? sanitize_text_field( $p['id'] ) : '';
							$parcels[ $i ]['is_non_standard']      = isset( $p['is_non_standard'] ) ? sanitize_text_field( $p['is_non_standard'] ) : '';
							$parcels[ $i ]['weight']['amount']     = isset( $p['weight']['amount'] ) ? sanitize_text_field( $p['weight']['amount'] ) : '';
							$parcels[ $i ]['dimensions']['length'] = isset( $p['dimensions']['length'] ) ? sanitize_text_field( $p['dimensions']['length'] ) : '';
							$parcels[ $i ]['dimensions']['width']  = isset( $p['dimensions']['width'] ) ? sanitize_text_field( $p['dimensions']['width'] ) : '';
							$parcels[ $i ]['dimensions']['height'] = isset( $p['dimensions']['height'] ) ? sanitize_text_field( $p['dimensions']['height'] ) : '';
						}
					}
				}
			}

			$shipment = $shipmentService->create_shipment_object_by_shiping_data(
				$parcels,
				(int) $order_id,
				$send_method,
				self::SERVICE_ID,
				$courier_parcel_data,
				null,
				$cod_amount,
				$insurance_amount,
				$reference_number,
				null
			);
			$shipment->getInternalData()->setOrderId( (int) $order_id );

			return $shipment;
		}

		/**
		 * @param bool $courier
		 *
		 * @throws ReflectionException
		 */
		public static function ajax_create_package( $courier = false ) {
			$ret = array( 'status' => 'ok' );

			$shipment_model = self::ajax_create_shipment_model();

			$order_id         = $shipment_model->getInternalData()->getOrderId();
			$shipment_service = EasyPack::EasyPack()->get_shipment_service();
			$shipment_array   = $shipment_service->shipment_to_array( $shipment_model );
			$status_service   = EasyPack::EasyPack()->get_shipment_status_service();
			$label_url        = '';

			$shipment_data = array();

			if ( empty( $shipment_array['receiver']['address']['country_code'] ) ) {
				$shipment_array['receiver']['address']['country_code'] = 'PL';
			}


			try {

				$response = EasyPack_API()->customer_parcel_create( $shipment_array );

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
				$ret['message'] = __( 'There are some errors. Please fix it: <br>', 'woocommerce-inpost' ) . EasyPack_API()->translate_error( $e->getMessage() );
			}

			if ( $ret['status'] == 'ok' ) {
				$order        = wc_get_order( $order_id );
				$tracking_url = EasyPack_Helper()->get_tracking_url();

				$order->add_order_note(
					__( 'Shipment created', 'woocommerce-inpost' ),
					false
				);

				EasyPack_Helper()->set_order_status_completed( $order_id );

				if ( isset( $_POST['action'] ) && $_POST['action'] === 'easypack_bulk_create_shipments' ) {
					if ( isset( $shipment_data['tracking'] ) && ! empty( $shipment_data['tracking'] ) ) {
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

                if ( 'yes' === get_option( 'easypack_delivery_notice' ) ) {
                    wp_schedule_single_event(
                        time() + 60,
                        'send_tracking_numbers_email',
                        array( $order_id )
                    );
                }

			}
			echo json_encode( $ret );
			wp_die();
		}


		/**
		 * @param                           $post
		 * @param bool                      $output
		 *
		 * @param ShipX_Shipment_Model|null $shipment
		 *
		 * @return string
		 */
		public static function order_metabox_content(
			$post,
			$output = true,
			$shipment = null,
			$additional_package = false
		) {
			$wp_ajax_action_create = self::WP_AJAX_ACTION_CREATE;
			if ( ! $output ) {
				ob_start();
			}
			$shipment_service = EasyPack::EasyPack()->get_shipment_service();

			if ( is_a( $post, 'WC_Order' ) ) {
				$order_id = $post->get_id();
			} else {
				$order_id = $post->ID;
			}

			if ( false === $shipment instanceof ShipX_Shipment_Model ) {
				$shipment = $shipment_service->get_shipment_by_order_id( $order_id );
			}

			if ( $shipment instanceof ShipX_Shipment_Model
				&& false === $shipment_service->is_shipment_match_to_current_api( $shipment )
			) {
				wp_nonce_field( self::NONCE_ACTION, 'wp_nonce' );
				$wrong_api_env = true;
				include 'views/html-order-metabox-courier.php';
				if ( ! $output ) {
					$out = ob_get_clean();

					return $out;
				}

				return '';
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

				$parcel_machine_id = $shipment->getCustomAttributes()->getTargetPoint();
				$send_method       = $shipment->getCustomAttributes()->getSendingMethod();
				$disabled          = true;
			} else {
				$package_sizes_display = EasyPack()->get_package_sizes_display();
				$parcels               = array();
				$parcel                = new ShipX_Shipment_Parcel_Model();
				$dimensions            = self::get_single_product_dimensions( $order_id );

				$parcel->setDimensions( $dimensions );
				$weight = new ShipX_Shipment_Parcel_Weight_Model();
				$parcel->setWeight( $weight );

				$parcel->setTemplate( get_option( 'easypack_default_package_size', 'small' ) );
				$parcels[] = $parcel;

				$parcel_machine_from_order = $order->get_meta( '_parcel_machine_id' );
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
			if ( EasyPack_API()->getCountry() === EasyPack_API::COUNTRY_PL ) {
				$send_methods = array(
					'courier' => __( 'Courier', 'woocommerce-inpost' ),
					'pop'     => __( 'POP', 'woocommerce-inpost' ),
				);
			} else {
				$send_methods = array(
					'parcel_machine' => __( 'Parcel locker', 'woocommerce-inpost' ),
				);
			}
			$selected_service = $shipment_service->get_customer_service_name_by_id( self::SERVICE_ID );

			include 'views/html-order-metabox-courier.php';

			wp_nonce_field( self::NONCE_ACTION, 'wp_nonce' );
			if ( ! $output ) {
				$out = ob_get_clean();

				return $out;
			}
		}
	}
}
