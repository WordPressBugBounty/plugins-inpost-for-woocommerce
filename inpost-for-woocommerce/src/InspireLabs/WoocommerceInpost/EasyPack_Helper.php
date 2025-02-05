<?php

namespace InspireLabs\WoocommerceInpost;

use InspireLabs\WoocommerceInpost\EasyPack;
use Exception;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shippng_Parcel_Machines;
use InspireLabs\WoocommerceInpost\shipx\models\courier_pickup\ShipX_Dispatch_Order_Model;
use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Model;
use Requests_Utility_CaseInsensitiveDictionary;
use WC_Shipping_Method;


/**
 * EasyPack Helper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'EasyPack_Helper' ) ) :

	class EasyPack_Helper {

		protected static $instance;
		protected static $css_embeded;
		
		private $cached_zones = array();

		public function __construct() {
			add_filter( 'query_vars', array( $this, 'query_vars' ) );

			add_action( 'woocommerce_before_my_account', array( $this, 'woocommerce_before_my_account' ) );
			add_filter( 'woocommerce_screen_ids', array( $this, 'woocommerce_screen_ids' ) );
			
			add_action( 'woocommerce_shipping_zone_method_added', array( $this, 'clear_zones_cache' ) );
            add_action( 'woocommerce_shipping_zone_method_deleted', array( $this, 'clear_zones_cache' ) );
            add_action( 'woocommerce_shipping_zone_method_status_toggled', array( $this, 'clear_zones_cache' ) );
		}

		public static function EasyPack_Helper() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}



		public function print_sticker_by_inpost_id( $inpost_id ) {
			$ret = array( 'status' => 'ok' );

			try {
				$results = EasyPack_API()->customer_shipments_labels( array( $inpost_id ) );

				if ( ! isset( $results['headers'] ) ) {
					return;

				} else {
					$headers = $results['headers'];
				}

				/**
				 * @var Requests_Utility_CaseInsensitiveDictionary $headers
				 */

				header(
					sprintf(
						'Content-type:%s',
						$headers->getAll()['content-type']
					)
				);

				echo $results['body'];
				die();

			} catch ( Exception $e ) {
				$ret['status']  = 'error';
				$ret['message'] = $e->getMessage();
				wp_die( esc_html__( 'Error while creating manifest:  ', 'woocommerce-inpost' ) . esc_html( $e->getMessage() ) );

			}
		}


		public function print_stickers(
			$return_stickers = false,
			$orders = null
		) {
			$ret = array( 'status' => 'ok' );

			if ( null === $orders ) {
				$orders = isset( $_POST['easypack_parcel'] ) ? (array) $_POST['easypack_parcel'] : array();
				$orders = array_map( 'sanitize_text_field', $orders );
			}

			$selected_shipments_ids = array();
			$shipment_service       = EasyPack()->get_shipment_service();

			if ( ! empty( $orders ) ) {

				if ( is_array( $orders ) ) {
					foreach ( $orders as $order ) {
						$inpost_internal_data = $shipment_service->get_shipment_by_order_id( (int) $order );

						if ( $inpost_internal_data && is_object( $inpost_internal_data ) ) {
							$selected_shipments_ids[] = $inpost_internal_data->getInternalData()->getInpostId();
						}

						// check for additional packages.
						$existed_additional_packages = EasyPack_Helper()->get_saved_additional_packages( $order );
						if ( is_array( $existed_additional_packages ) && ! empty( $existed_additional_packages ) ) {
							foreach ( $existed_additional_packages as $additional_package ) {
								if ( is_array( $additional_package ) ) {
									foreach ( $additional_package as $key => $data ) {
										if ( isset( $data['inpost_id'] ) && ! empty( $data['inpost_id'] ) ) {
											$selected_shipments_ids[] = $data['inpost_id'];
										}
									}
								}
							}
						}
					}
				} else {

					$inpost_internal_data = $shipment_service->get_shipment_by_order_id( (int) $orders );

					if ( $inpost_internal_data && is_object( $inpost_internal_data ) ) {
						$selected_shipments_ids[] = $inpost_internal_data->getInternalData()->getInpostId();
					}

					// check for additional packages.
					$existed_additional_packages = EasyPack_Helper()->get_saved_additional_packages( $orders );
					if ( is_array( $existed_additional_packages ) && ! empty( $existed_additional_packages ) ) {
						foreach ( $existed_additional_packages as $additional_package ) {
							if ( is_array( $additional_package ) ) {
								foreach ( $additional_package as $key => $data ) {
									if ( isset( $data['inpost_id'] ) && ! empty( $data['inpost_id'] ) ) {
										$selected_shipments_ids[] = $data['inpost_id'];
									}
								}
							}
						}
					}
				}
			}

			try {
				if ( true === $return_stickers ) {
					$results
						= EasyPack_API()->customer_shipments_return_labels( $selected_shipments_ids );
				} elseif ( is_array( $selected_shipments_ids ) && count( $selected_shipments_ids ) > 1 ) {

						$results = EasyPack_API()->customer_shipments_labels( $selected_shipments_ids );
				} elseif ( isset( $selected_shipments_ids[0] ) && ! empty( $selected_shipments_ids[0] ) ) {
						$results = EasyPack_API()->customer_parcel_sticker( $selected_shipments_ids[0] );
				}

				if ( ! isset( $results['headers'] ) ) {
					if ( isset( $_POST['easypack_action'] ) && $_POST['easypack_action'] === 'easypack_create_bulk_labels' ) {

						\wc_get_logger()->debug( 'Inpost IDs for labels: ', array( 'source' => 'inpost-label-log' ) );
						\wc_get_logger()->debug( print_r( $selected_shipments_ids, true ), array( 'source' => 'inpost-label-log' ) );
						\wc_get_logger()->debug( 'API response: ', array( 'source' => 'inpost-label-log' ) );
						\wc_get_logger()->debug( print_r( $results, true ), array( 'source' => 'inpost-label-log' ) );

						echo json_encode(
							array(
								'status'  => isset( $results['status'] ) ? $results['status'] : 'Błąd',
								'details' => isset( $results['details'] ) ? $results['details'] : 'Opóźnienie odpowiedzi API - odśwież stronę i spróbuj ponownie (lub później)',
							)
						);
					}
					return;

				} else {
					$headers = $results['headers'];
				}

				/**
				 * @var Requests_Utility_CaseInsensitiveDictionary $headers
				 */

				header(
					sprintf(
						'Content-type:%s',
						$headers->getAll()['content-type']
					)
				);

				echo $results['body'];
				die();

			} catch ( Exception $e ) {
				$ret['status']  = 'error';
				$ret['message'] = $e->getMessage();
				wp_die( esc_html__( 'Error while creating manifest:  ', 'woocommerce-inpost' ) . esc_html( $e->getMessage() ) );

			}
		}


		public function post_confirmation_pdf( $shipment_ids ) {
			$ret = array( 'status' => 'ok' );

			try {

				$results = EasyPack_API()->get_post_confirmations( $shipment_ids );

				if ( isset( $results['headers'] ) ) {
					header(
						sprintf(
							'Content-type:%s',
							$results['headers']['data']['content-type']
						)
					);

					echo $results['body'];
					wp_die();

				} else {

					\wc_get_logger()->debug( 'Inpost post_confirmation_pdf: ', array( 'source' => 'inpost-debug-log' ) );
					\wc_get_logger()->debug( print_r( $shipment_ids, true ), array( 'source' => 'inpost-debug-log' ) );
					\wc_get_logger()->debug( print_r( $results, true ), array( 'source' => 'inpost-debug-log' ) );

					if ( isset( $results['error'] ) ) {
						$error_message = '';
						if ( isset( $results['message'] ) ) {
							$error_message = esc_html( $results['message'] );
						} else {
							$error_message = esc_html( $results['error'] );
						}
						wp_die( $error_message );
					}
				}
			} catch ( Exception $e ) {
				$ret['status']  = 'error';
				$ret['message'] = $e->getMessage();
				wp_die( esc_html__( 'Error while creating manifest PDF: ', 'woocommerce-inpost' ) . esc_html( $e->getMessage() ) );

			}
		}



		/**
		 * Allow for custom query variables
		 */
		public function query_vars( $query_vars ) {
			$query_vars[] = 'easypack_download';

			return $query_vars;
		}


		/**
		 * @param string|null $country
		 *
		 * @return string
		 */
		public function get_tracking_url( $country = null ) {
			if ( null === $country ) {
				if ( EasyPack_API()->is_pl() ) {
					return 'https://inpost.pl/sledzenie-przesylek?number=';
				}

				return 'https://tracking.inpost.co.uk/';
			}

			if ( EasyPack_API()->normalize_country_code_for_inpost( $country )
				=== EasyPack_API::COUNTRY_PL
			) {
				return 'https://inpost.pl/sledzenie-przesylek?number=';
			}

			return 'https://tracking.inpost.co.uk/';
		}

		public function get_weight_option( $weight, $options ) {
			$ret     = - 1;
			$options = array_reverse( $options, true );
			foreach ( $options as $val => $option ) {
				if ( floatval( $weight ) <= floatval( $val ) ) {
					$ret = $val;
				}
			}

			return $ret;
		}


		function woocommerce_before_my_account() {
			if ( get_option( 'easypack_returns_page' )
				&& trim( get_option( 'easypack_returns_page' ) ) !== ''
			) {
				$page = get_page( get_option( 'easypack_returns_page' ) );
				if ( $page ) {
					$img_src = EasyPack()->getPluginImages()
								. 'logo/small/white.png';
					$args    = array(
						'returns_page'       => get_page_link( get_option( 'easypack_returns_page' ) ),
						'returns_page_title' => $page->post_title,
						'img_src'            => $img_src,
					);
					wc_get_template(
						'myaccount/before-my-account.php',
						$args,
						'',
						plugin_dir_path( EasyPack()->getPluginFilePath() )
							. 'templates/'
					);
				}
			}
		}

		function woocommerce_screen_ids( $screen_ids ) {
			$screen_ids[] = 'inpost_page_easypack_shipment';

			return $screen_ids;
		}

		/**
		 * Check if at least one physical product exists in cart
		 *
		 * @param array $cart_contents
		 *
		 * @return bool
		 */
		public function physical_goods_in_cart( $cart_contents ) {
			$res = false;

			if ( ! empty( $cart_contents ) ) {
				foreach ( $cart_contents as $cart_item_key => $cart_item ) {
					// if variation in cart.
					if ( $cart_item['variation_id'] ) {

						$variant = wc_get_product( $cart_item['variation_id'] );
						if ( ! $variant->is_virtual() /* && ! $variant->is_downloadable() */ ) {
							$res = true;
							break;
						}
					} else {
						// simple product.
						$_product = wc_get_product( $cart_item['product_id'] );
						if ( ! $_product->is_virtual() /* && ! $_product->is_downloadable() */ ) {
							$res = true;
							break;
						}
					}
				}
			}

			return $res;
		}

		public function validate_method_name( $method_name ) {
			if ( stripos( $method_name, ':' ) ) {
				return trim( explode( ':', $method_name )[0] );
			}
			return $method_name;
		}

		public function validate_method_instance_id( $method_name ) {
			if ( stripos( $method_name, ':' ) ) {
				return trim( explode( ':', $method_name )[1] );
			}
			return null;
		}


		/**
		 * Convert size from model data to letter symbol (A,B,C)
		 *
		 * @param string $size
		 *
		 * @return string
		 */
		public function convert_size_to_symbol( $size ) {
			if ( 'small' === $size ) {
				return 'A';
			}
			if ( 'medium' === $size ) {
				return 'B';
			}
			if ( 'large' === $size ) {
				return 'C';
			}
			if ( 'xlarge' === $size ) {
				return 'D';
			}

			return $size; // for Kurier shipments with dimensions.
		}


		public function get_saved_method_rates( $id, $instance_id ) {

			$rates = get_option( 'woocommerce_' . $id . '_' . $instance_id . '_rates' );
			// backward compatibility.
			if ( ! $rates ) {
				$rates = get_option( 'woocommerce_' . $id . '_rates', array() );
			}

			return is_array( $rates ) ? $rates : array();
		}


		/**
		 * Integration with plugin "Flexible shipping"
		 *
		 * @return boolean
		 */
		public function is_flexible_shipping_activated() {
			$plugin_file    = 'flexible-shipping/flexible-shipping.php';
			$active_plugins = (array) get_option( 'active_plugins', array() );

			if ( is_multisite() ) {
				$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
			}

			return in_array( $plugin_file, $active_plugins ) || array_key_exists( $plugin_file, $active_plugins );
		}

		/**
		 * Integration with plugin "Flexible shipping" (get shipping method linked in FS settings)
		 *
		 * @param mixed $chosen_shipping_methods $chosen_shipping_methods.
		 *
		 * @return string
		 */
		public function get_method_linked_to_fs( $chosen_shipping_methods ) {
			$method_linked_to_fs = '';			
			
			if ( ! empty( $chosen_shipping_methods ) && is_array( $chosen_shipping_methods ) ) {
				foreach ( $chosen_shipping_methods as $shipping_method ) {
					if ( 0 === strpos( $shipping_method, 'flexible_shipping_single' ) ) {

						$shipping_method_instance_id = $this->validate_method_instance_id( $shipping_method );
						if ( isset( $shipping_method_instance_id ) ) {
							$method_linked_to_fs = $this->get_method_linked_to_fs_by_instance_id( $shipping_method_instance_id );
						}
					}
				}
			}

			return $method_linked_to_fs;
		}


		/**
		 * Integration with plugin "Flexible shipping" (get shipping method linked in FS settings)
		 *
		 * @param string $instance_id $instance_id.
		 *
		 * @return string
		 */
		public function get_method_linked_to_fs_by_instance_id( $instance_id ) {
			$method_linked_to_fs      = '';
			$shipping_method_settings = get_option( 'woocommerce_flexible_shipping_single_' . $instance_id . '_settings' );

			if ( isset( $shipping_method_settings['fs_inpost_pl_method'] ) && ! empty( $shipping_method_settings['fs_inpost_pl_method'] ) ) {
				$method_linked_to_fs = $shipping_method_settings['fs_inpost_pl_method'];
			}

			return $method_linked_to_fs;
		}


		public function get_class_name_by_shipping_id( $shipping_id ) {

			$class_name = '';

			// if we get instance_id of shipping method.
			if ( is_numeric( $shipping_id ) ) {
				if ( class_exists( 'WC_Shipping_Zones' ) ) {
					$shipping_method = \WC_Shipping_Zones::get_shipping_method( $shipping_id );
					// Get the shipping method ID from the instance object.
					if ( $shipping_method ) {
						$shipping_id = $shipping_method->id;
					}
				}
			}

			switch ( $shipping_id ) {
				case 'easypack_parcel_machines_economy':
					$class_name = 'EasyPack_Shipping_Parcel_Machines_Economy';
					break;
				case 'easypack_parcel_machines_economy_cod':
					$class_name = 'EasyPack_Shipping_Parcel_Machines_Economy_COD';
					break;
				case 'easypack_shipping_courier_c2c_cod':
					$class_name = 'EasyPack_Shipping_Method_Courier_C2C_COD';
					break;
				case 'easypack_shipping_courier_c2c':
					$class_name = 'EasyPack_Shipping_Method_Courier_C2C';
					break;
				case 'easypack_parcel_machines':
					$class_name = 'EasyPack_Shippng_Parcel_Machines';
					break;
				case 'easypack_parcel_machines_cod':
					$class_name = 'EasyPack_Shippng_Parcel_Machines_COD';
					break;
				case 'easypack_parcel_machines_weekend':
					$class_name = 'EasyPack_Shipping_Parcel_Machines_Weekend';
					break;
                case 'easypack_parcel_machines_weekend_cod':
                    $class_name = 'EasyPack_Shipping_Parcel_Machines_Weekend_COD';
                    break;
				case 'easypack_shipping_courier':
					$class_name = 'EasyPack_Shipping_Method_Courier';
					break;
				case 'easypack_cod_shipping_courier':
					$class_name = 'EasyPack_Shipping_Method_Courier_COD';
					break;
				case 'easypack_shipping_courier_local_express':
					$class_name = 'EasyPack_Shipping_Method_Courier_Local_Express';
					break;
				case 'easypack_shipping_courier_le_cod':
					$class_name = 'EasyPack_Shipping_Method_Courier_Local_Express_COD';
					break;
				case 'easypack_shipping_courier_local_standard':
					$class_name = 'EasyPack_Shipping_Method_Courier_Local_Standard';
					break;
				case 'easypack_shipping_courier_local_standard_cod':
					$class_name = 'EasyPack_Shipping_Method_Courier_Local_Standard_COD';
					break;
				case 'easypack_shipping_courier_lse':
					$class_name = 'EasyPack_Shipping_Method_Courier_LSE';
					break;
				case 'easypack_shipping_courier_lse_cod':
					$class_name = 'EasyPack_Shipping_Method_Courier_LSE_COD';
					break;
				case 'easypack_shipping_courier_palette':
					$class_name = 'EasyPack_Shipping_Method_Courier_Palette';
					break;
				case 'courier_palette_cod_create_package':
					$class_name = 'EasyPack_Shipping_Method_Courier_Palette_COD';
					break;
				case 'easypack_shipping_esmartmix':
					$class_name = 'EasyPack_Shipping_Method_EsmartMix';
					break;
			}

			return $class_name;
		}


		/**
		 * Inline CSS for button
		 *
		 * @return void
		 */
		public function include_inline_css() {
			add_action(
				'wp_head',
				function () {
					$custom_button_color = get_option( 'easypack_custom_button_css' );

					if ( ! empty( $custom_button_color ) && ! self::$css_embeded ) {

						$easypack_button_settings_css = '';

						if ( isset( $custom_button_color ) ) {
							$custom_button_color           = sanitize_text_field( $custom_button_color );
							$easypack_button_settings_css .= '.easypack_show_geowidget {
                                  background:  ' . $custom_button_color . ' !important;
                                }';
						}
						self::$css_embeded = true;
						echo wp_kses( "<style>$easypack_button_settings_css</style>", array( 'style' => array() ) );
					}
				},
				100
			);
		}

		/**
		 * Save data to order meta from action on Edit order page
		 *
		 * @return void
		 */
		public function set_data_to_order_meta( $_post, $id ) {

			/*
			$order = wc_get_order( $id );

			if ( ! $order || is_wp_error( $order ) ) {
				return;
			}

			$parcel_machine_id   = isset( $_post['parcel_machine_id'] ) ? sanitize_text_field( $_post['parcel_machine_id'] ) : '';
			$parcel_machine_desc = isset( $_post['parcel_machine_desc'] ) ? sanitize_text_field( $_post['parcel_machine_desc'] ) : '';

			if ( ! empty( $parcel_machine_id ) ) {
				$order->update_meta_data( '_parcel_machine_id', $parcel_machine_id );
			}
			if ( ! empty( $parcel_machine_desc ) ) {
				$order->update_meta_data( '_parcel_machine_desc', $parcel_machine_desc );
			}

			$parcel_length       = isset( $_post['parcel_length'] ) ? sanitize_text_field( $_post['parcel_length'] ) : '';
			$parcel_width        = isset( $_post['parcel_width'] ) ? sanitize_text_field( $_post['parcel_width'] ) : '';
			$parcel_height       = isset( $_post['parcel_height'] ) ? sanitize_text_field( $_post['parcel_height'] ) : '';
			$parcel_weight       = isset( $_post['parcel_weight'] ) ? sanitize_text_field( $_post['parcel_weight'] ) : '';
			$parcel_non_standard = isset( $_post['parcel_non_standard'] ) ? sanitize_text_field( $_post['parcel_non_standard'] ) : '';
			$insurance           = isset( $_post['insurance_amounts'][0] ) ? sanitize_text_field( $_post['insurance_amounts'][0] ) : '';

			if ( ! empty( $parcel_length ) ) {
				$order->update_meta_data( '_easypack_parcel_length', $parcel_length );
			}

			if ( ! empty( $parcel_width ) ) {
				$order->update_meta_data( '_easypack_parcel_width', $parcel_width );
			}

			if ( ! empty( $parcel_height ) ) {
				$order->update_meta_data( '_easypack_parcel_height', $parcel_height );
			}

			if ( ! empty( $parcel_weight ) ) {
				$order->update_meta_data( '_easypack_parcel_weight', $parcel_weight );
			}

			if ( ! empty( $insurance ) ) {
				$order->update_meta_data( '_easypack_parcel_insurance', $insurance );
			} else {
				$insurance_default = get_option( 'easypack_insurance_amount_default', '0.00' );
				$order->update_meta_data( '_easypack_parcel_insurance', $insurance_default );
			}

			if ( ! empty( $parcel_non_standard ) ) {
				$order->update_meta_data( '_easypack_parcel_non_standard', $parcel_non_standard );
			}

			$parcels = isset( $_post['parcel'] ) ? (array) $_post['parcel'] : array();
			$parcels = array_map( 'sanitize_text_field', $parcels );

			$cod_amounts = isset( $_post['cod_amount'] ) ? (array) $_post['cod_amount'] : array();
			$cod_amounts = array_map( 'sanitize_text_field', $cod_amounts );

			$easypack_pacels = array();
			if ( ! empty( $parcels ) ) {

				foreach ( $parcels as $key => $parcel ) {
					if ( isset( $cod_amounts[ $key ] ) ) {
						$easypack_pacels[] = array(
							'package_size' => $parcel,
							'cod_amount'   => $cod_amounts[ $key ],
						);
					} else {
						$easypack_pacels[] = array(
							'package_size' => $parcel,
						);
					}
				}
			}
			$order->update_meta_data( '_easypack_parcels', $easypack_pacels );

			$easypack_ref_number = isset( $_post['reference_number'] ) ? sanitize_text_field( $_POST['reference_number'] ) : $id;
			if ( ! empty( $easypack_ref_number ) ) {
				$order->update_meta_data( '_reference_number', $easypack_ref_number );
			}

			$easypack_send_method = isset( $_post['easypack_send_method'] ) ? sanitize_text_field( $_POST['easypack_send_method'] ) : '';
			if ( ! empty( $easypack_send_method ) ) {
				$order->update_meta_data( '_easypack_send_method', $easypack_send_method );
			}

			$commercial_product_identifier = isset( $_post['commercial_product_identifier'] ) ? sanitize_text_field( $_POST['commercial_product_identifier'] ) : '';
			if ( ! empty( $commercial_product_identifier ) ) {
				$order->update_meta_data( '_commercial_product_identifier', $commercial_product_identifier );
			}

			$order->save();
			*/
		}


		/**
		 * Get save data from order meta if user used "Update order" button
		 *
		 * @return array
		 */
		public function get_courier_parcel_data_from_order_meta( $id ) {
			$data['length']       = '';
			$data['width']        = '';
			$data['height']       = '';
			$data['weight']       = '';
			$data['non_standard'] = '';

			$data['length'] = get_post_meta( $id, '_easypack_parcel_length', true )
				? get_post_meta( $id, '_easypack_parcel_length', true )
				: '';

			$data['width'] = get_post_meta( $id, '_easypack_parcel_width', true )
				? get_post_meta( $id, '_easypack_parcel_width', true )
				: '';

			$data['height'] = get_post_meta( $id, '_easypack_parcel_height', true )
				? get_post_meta( $id, '_easypack_parcel_height', true )
				: '';

			$data['weight'] = get_post_meta( $id, '_easypack_parcel_weight', true )
				? get_post_meta( $id, '_easypack_parcel_weight', true )
				: '';

			$data['non_standard'] = get_post_meta( $id, '_easypack_parcel_non_standard', true )
				? get_post_meta( $id, '_easypack_parcel_non_standard', true )
				: 'no';

			return $data;
		}


		public function get_dimensions_for_courier_shipments( $post_id ) {

			if ( 'yes' === get_option( 'easypack_set_default_courier_dimensions' ) ) {
				$dimensions = get_option( 'easypack_default_courier_dimensions' );
			} else {
				$dimensions = $this->get_courier_parcel_data_from_order_meta( $post_id );
			}

			if ( ! empty( $dimensions['length'] )
				&& ! empty( $dimensions['width'] )
				&& ! empty( $dimensions['height'] )
				&& ! empty( $dimensions['weight'] )
			) {
				// if all data was saved in meta.
				return $dimensions;
			}

			// otherwise try to get dimension for single product from product settings.
			$order = wc_get_order( $post_id );
			$items = $order->get_items();

			if ( count( $items ) > 1 ) {
				return;
			}

			foreach ( $order->get_items() as $item_id => $item ) {
				$product_id = $item->get_product_id();

				if ( $item->get_quantity() > 1 ) {
					return;
				}

				$product = wc_get_product( $product_id );
				if ( is_object( $product ) && ! is_wp_error( $product ) ) {

					$dimensions['height'] = ! empty( $dimensions['height'] ) ? $dimensions['height'] : (float) $product->get_height() * 10;
					$dimensions['width']  = ! empty( $dimensions['width'] ) ? $dimensions['width'] : (float) $product->get_width() * 10;
					$dimensions['length'] = ! empty( $dimensions['length'] ) ? $dimensions['length'] : (float) $product->get_length() * 10;

					if ( ! empty( $dimensions['height'] )
						&& ! empty( $dimensions['width'] )
						&& ! empty( $dimensions['length'] ) ) {
						$dimensions['weight'] = ! empty( $dimensions['weight'] )
							? $dimensions['weight']
							: EasyPack_Helper()->get_order_weight( $order );
					}
				}
			}

			return $dimensions;
		}



		public function is_courier_service_by_id( $shipment_id ) {

			if ( in_array(
				$shipment_id,
				array(
					'easypack_shipping_courier',
					'easypack_cod_shipping_courier',
					'easypack_shipping_courier_local_express',
					'easypack_shipping_courier_le_cod',
					'easypack_shipping_courier_local_standard',
					'easypack_shipping_courier_local_standard_cod',
					'easypack_shipping_courier_lse',
					'easypack_shipping_courier_lse_cod',
					'easypack_shipping_courier_palette',
					'easypack_shipping_courier_palette_cod',
					'easypack_shipping_esmartmix',
				)
			)
			) {
				return true;
			}

			return false;
		}


		public function get_order_weight( $order ) {
			$weight = 0;
			if ( count( $order->get_items() ) > 0 ) {
				foreach ( $order->get_items() as $item ) {
					if ( isset( $item['product_id'] ) && $item['product_id'] > 0 ) {
						if ( is_object( $item ) ) {
							$_product = $item->get_product();
							if ( ! $_product->is_virtual() ) {
								$weight += (float) $_product->get_weight() * (int) $item['qty'];
							}
						}
					}
				}
			}

			return $weight;
		}


		public function is_required_pages_for_modal() {
			global $pagenow, $post_type;
			$current_screen = get_current_screen();

			if ( 'shop_order' === $post_type ) {
				if ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) {
					return true;
				}
			}

			if ( 'shop_order_placehold' === $post_type ) {
				return true;
			}

			if ( is_a( $current_screen, 'WP_Screen' ) && 'woocommerce_page_wc-orders' === $current_screen->id ) {
				return true;
			}

			if ( is_checkout() || has_block( 'woocommerce/checkout' ) || get_option( 'easypack_debug_mode_enqueue_scripts' ) === 'yes' ) {
				return true;
			}

			if ( is_a( $current_screen, 'WP_Screen' ) && 'inpost_page_easypack_shipment' === $current_screen->id ) {
				return true;
			}

			if ( is_a( $current_screen, 'WP_Screen' ) && 'woocommerce_page_wc-settings' === $current_screen->id ) {
				if ( isset( $_GET['tab'] ) && 'easypack_general' === $_GET['tab'] ) {
					return true;
				}
			}

			return false;
		}


		/**
		 * Get configured Inpost methods
		 *
		 * @return array
		 */
		public function get_inpost_methods(): array {

			$configured_shipping_methods = array();

			//$delivery_zones = \WC_Shipping_Zones::get_zones();
			
			$delivery_zones = $this->get_cached_zones();

			foreach ( (array) $delivery_zones as $key => $the_zone ) {
				if ( isset( $the_zone['shipping_methods'] ) ) {
					foreach ( $the_zone['shipping_methods'] as $configured_method ) {
						if ( 0 === strpos( $configured_method->id, 'easypack_' ) ) {
							$configured_shipping_methods[ $configured_method->instance_id ]['user_title']           = $configured_method->title;
							$configured_shipping_methods[ $configured_method->instance_id ]['method_title']         = $configured_method->id;
							$configured_shipping_methods[ $configured_method->instance_id ]['method_title_with_id'] = $configured_method->id . ':' . $configured_method->instance_id;
							$configured_shipping_methods[ $configured_method->instance_id ]['inpost_title']         = $configured_method->id;

							if ( 0 === strpos( $configured_method->id, 'easypack_parcel_machines' ) ) {
								$configured_shipping_methods[ $configured_method->instance_id ]['need_map'] = 1;
							}
						} elseif ( 0 === strpos( $configured_method->id, 'flexible_shipping' ) ) {
							// Integration with Flexible Shipping.
							$linked_method = EasyPack_Helper()->get_method_linked_to_fs_by_instance_id( $configured_method->instance_id );
							if ( 0 === strpos( $linked_method, 'easypack_' ) ) {
								$configured_shipping_methods[ $configured_method->instance_id ]['user_title']           = $configured_method->title;
								$configured_shipping_methods[ $configured_method->instance_id ]['method_title']         = $configured_method->id;
								$configured_shipping_methods[ $configured_method->instance_id ]['method_title_with_id'] = $configured_method->id . ':' . $configured_method->instance_id;
								$configured_shipping_methods[ $configured_method->instance_id ]['inpost_title']         = $linked_method;

								if ( 0 === strpos( $linked_method, 'easypack_parcel_machines' ) ) {
									$configured_shipping_methods[ $configured_method->instance_id ]['need_map'] = 1;
								}
							}
						}
					}
				}
			}

			return $configured_shipping_methods;
		}


		/**
		 * Set order status completed
		 *
		 * @param int $order_id $order_id.
		 * @return void
		 */
		public function set_order_status_completed( $order_id ) {

			if ( get_option( 'easypack_set_order_completed' ) === 'yes' ) {
				$order = wc_get_order( $order_id );
				if ( $order ) {
					$current_order_status = $order->get_status();
					if ( 'completed' !== $current_order_status ) {
						$order->update_status( 'wc-completed' );
					}
				}
			}
		}


		public function get_parcel_size_from_settings( $order_id ) {

			$size = 'small';

			$tem_array = array();

			$order = wc_get_order( $order_id );

			$shipping_method = '';

			if ( $order && ! is_wp_error( $order ) ) {

				$shipping_methods = $order->get_shipping_methods();

				if ( ! empty( $shipping_methods ) && is_array( $shipping_methods ) ) {
					foreach ( $shipping_methods as $method ) {
						$shipping_method = $method->get_method_id();
					}
				}

				$items = $order->get_items();

				if ( ! empty( $items ) ) {

					foreach ( $items as $item ) {

						// Compatibility for woocommerce 3+.
						$product_id = version_compare( WC_VERSION, '3.0', '<' ) ? $item['product_id'] : $item->get_product_id();

						$size_from_product = get_post_meta( $product_id, 'woo_inpost_parcel_dimensions', true );

						if ( ! empty( $size_from_product ) ) {
							$tem_array[] = $size_from_product;
						}
					}
				}
			}

			// define size as biggest value among the products in the order.
			if ( ! empty( $tem_array ) ) {

				$tem_array = array_unique( $tem_array );

				if ( in_array( 'large', $tem_array, true ) ) {
					return 'large';
				} elseif ( in_array( 'medium', $tem_array, true ) ) {
					return 'medium';
				} elseif ( in_array( 'small', $tem_array, true ) ) {
					return 'small';
				}

				// or get size from global settings.
			} elseif ( ! empty( $shipping_method ) ) {

				if ( 'easypack_shipping_courier_c2c' === $shipping_method
						|| 'easypack_shipping_courier_c2c_cod' === $shipping_method
					) {
					$size = get_option( 'easypack_default_package_size_c2c' );
				} else {
					$size = get_option( 'easypack_default_package_size' );
				}
			} else {
				$size = get_option( 'easypack_default_package_size' );
			}

			return $size;
		}


		public function get_active_shipping_methods(): array {

			$configured_shipping_methods = array();
			$delivery_zones              = \WC_Shipping_Zones::get_zones();
			foreach ( (array) $delivery_zones as $key => $the_zone ) {
				// only for zone "PL"
				if ( is_array( $the_zone['shipping_methods'] ) ) {
					foreach ( $the_zone['shipping_methods'] as $configured_method ) {
						// only active methods
						if ( isset( $configured_method->enabled ) && 'yes' === $configured_method->enabled ) {
							if ( 0 === strpos( $configured_method->id, 'easypack_' ) ) {
								$configured_shipping_methods[ $configured_method->instance_id ] = $configured_method->title;

							} elseif ( 0 === strpos( $configured_method->id, 'flexible_shipping' ) ) {
								// Integration with Flexible Shipping
								$linked_method = EasyPack_Helper()->get_method_linked_to_fs_by_instance_id( $configured_method->instance_id );
								if ( 0 === strpos( $linked_method, 'easypack_' ) ) {
									$configured_shipping_methods[ $configured_method->instance_id ] = $configured_method->title;
								}
							}
						}
					}
				}
			}

			return $configured_shipping_methods;
		}


		public function get_saved_additional_packages( $order_id ) {

			$additional_packages = isset( get_post_meta( $order_id )['_easypack_additional_packages'][0] )
				? get_post_meta( $order_id )['_easypack_additional_packages'][0]
				: get_post_meta( $order_id, '_easypack_additional_packages', true );

			if ( ! empty( $additional_packages ) ) {
				if ( is_array( $additional_packages ) ) {
					return $additional_packages;
				} else {
					return is_array( unserialize( $additional_packages ) ) ? unserialize( $additional_packages ) : array();
				}
			}

			return array();
		}


		public function print_posting_confirmation() {
			$orders = isset( $_POST['easypack_parcel'] ) ? (array) $_POST['easypack_parcel'] : array();
			$orders = array_map( 'sanitize_text_field', $orders );

			if ( empty( $orders ) ) {
				return;
			}

			$shipment_service = EasyPack()->get_shipment_service();
			$shipment_ids     = array();

			foreach ( $orders as $order ) {
				$inpost_internal_data = $shipment_service->get_shipment_by_order_id( (int) $order );

				if ( $inpost_internal_data && is_object( $inpost_internal_data ) ) {
					$shipment_ids[] = $inpost_internal_data->getInternalData()->getInpostId();
				}
			}

			$this->post_confirmation_pdf( $shipment_ids );
		}


		/**
		 * Check if used any Sequential Order Numbers
		 *
		 * @param int $order_id $order_id.
		 * @return mixed
		 */
		public function get_maybe_custom_reference_number( $order_id ) {

			$reference_number = $order_id;

			if ( class_exists( 'Wt_Advanced_Order_Number' ) || class_exists( 'WC_Seq_Order_Number' ) ) {
				$order = wc_get_order( $order_id );
				if ( $order && ! is_wp_error( $order ) ) {
					if ( ! empty( $order->get_meta( '_order_number' ) ) ) {
						$reference_number = $order->get_meta( '_order_number' );
					}
				}
			}

			return $reference_number;
		}


		/**
		 * Validate if orders have inpost statuses or inpost IDs
		 *
		 * @param array $arr $arr.
		 * @return array
		 */
		public function validate_order_ids_before_get_labels_from_api( array $arr ): array {
			// we need validate chosen orders if they already has status which is allowing to get labels.
			$validated_ids = array();

			foreach ( $arr as $order_id ) {

				$is_tracking_exists = get_post_meta( $order_id, '_easypack_parcel_tracking', true );
				if ( ! $is_tracking_exists ) {
					$order = wc_get_order( $order_id );
					if ( $order && ! is_wp_error( $order ) ) {
						$is_tracking_exists = $order->get_meta( '_easypack_parcel_tracking' );
					}
				}

				if ( ! empty( $is_tracking_exists ) ) {
					$validated_ids[] = $order_id;
				} else {

					// $shipment = $order->get_meta( '_shipx_shipment_object' );
					$shipment = get_post_meta( $order_id, '_shipx_shipment_object', true );

					if ( ! $shipment instanceof ShipX_Shipment_Model ) {
						if ( 'yes' === get_option( 'woocommerce_custom_orders_table_enabled' ) ) {
							$from_order_meta_raw = isset( get_post_meta( $order_id )['_shipx_shipment_object'][0] )
								? get_post_meta( $order_id )['_shipx_shipment_object'][0]
								: '';

							if ( ! empty( $from_order_meta_raw ) ) {
								$shipment = unserialize( $from_order_meta_raw );
							}
						}
					}

					if ( is_object( $shipment ) && $shipment instanceof ShipX_Shipment_Model ) {
						$inpost_id = $shipment->getInternalData()->getInpostId();
						if ( ! empty( $inpost_id ) ) {
							$validated_ids[] = $order_id;
						}
					}
				}
			}

			return $validated_ids;
		}



		/**
		 * Get insurance amount
		 *
		 * @param int $order_id $order_id.
		 *
		 * @return false|float
		 */
		public function get_insurance_amount( $order_id ) {

			/**
			 * Priority to get insurance:
			 * 1) from shipping method settings if method linked to FS.
			 * 2) from shipping method settings.
			 * 3) from old settings (deleted already).
			 */

			$insurance_amount = false;

			$order = wc_get_order( $order_id );

			if ( ! $order || is_wp_error( $order ) ) {
				return false;
			}

			$shipping_method_name = '';
			$method_instance_id   = '';

			foreach ( $order->get_shipping_methods() as $shipping_method ) {
				$method_instance_id   = $shipping_method->get_instance_id();
				$shipping_method_name = $shipping_method->get_method_id();
			}

			$shipping_method_settings_name = 'woocommerce_' . $shipping_method_name . '_' . $method_instance_id . '_settings';

			$shipping_method_settings = get_option( $shipping_method_settings_name );

			if ( $this->is_flexible_shipping_activated() ) {
				if ( isset( $shipping_method_settings['fs_insurance_inpost_pl'] ) && 'yes' === $shipping_method_settings['fs_insurance_inpost_pl'] ) {
					$insurance_amount = $order->get_total();
				}
				if ( isset( $shipping_method_settings['fs_insurance_inpost_pl'] ) && 'no' === $shipping_method_settings['fs_insurance_inpost_pl'] ) {
					$insurance_amount = floatval( $shipping_method_settings['fs_insurance_value_inpost_pl'] );
				}
			}

			if ( is_bool( $insurance_amount ) && ! $insurance_amount ) {

				if ( ! empty( $shipping_method_settings['insurance_inpost_pl'] ) ) {
					if ( 'yes' === $shipping_method_settings['insurance_inpost_pl'] ) {
						$insurance_amount = $order->get_total();
					}
					if ( 'no' === $shipping_method_settings['insurance_inpost_pl'] ) {
						$insurance_amount = floatval( $shipping_method_settings['insurance_value_inpost_pl'] );
					}
				} else {
					// From old general settings.
					$insurance_mode = get_option( 'easypack_insurance_amount_mode', '2' );
					if ( '1' === $insurance_mode ) {
						$insurance_amount = $order->get_total();
					}
					if ( '2' === $insurance_mode ) {
						$insurance_amount = floatval( get_option( 'easypack_insurance_amount_default' ) );
					}
				}
			}

			return $insurance_amount ? $insurance_amount : 0.00;
		}
		
		
		
		public function is_admin_orders_or_plugin_settings_related_page() {
			
			if( ! is_admin() ) {
                return;
            }
			

            if( ! function_exists( 'get_current_screen' ) ) {
                return;
            }		
			

            global $pagenow, $post_type, $post;
            $current_screen = get_current_screen();

            $order_id = null;

            if ( 'shop_order' === $post_type || 'shop_order_placehold' === $post_type || 'shop_order' === $current_screen->post_type ) {
                if( isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
                    if ( isset($_GET['page']) && 'wc-orders' === $_GET['page'] ) {
                        $order_id = $_GET['id'] ? $_GET['id'] : null;
                    } elseif ( isset( $_GET['post'] ) && is_numeric( $_GET['post'] ) ) {
                        $order_id = $_GET['post'];
                    }
                }
            }

            if( $order_id ) {
                $order = wc_get_order( $order_id );
                if( $order && is_a( $order, 'WC_Order' ) ) {
                    
                    if( ! empty( $order->get_meta( '_parcel_machine_id' ) ) ) {                        
                        return true;
                    } else if( ! empty( get_post_meta( $order_id, '_parcel_machine_id', true ) ) ) {
						return true;
					}
                }
            }

            if ( is_a( $current_screen, 'WP_Screen' ) && 'woocommerce_page_wc-settings' === $current_screen->id ) {
                if ( isset( $_GET['tab'] ) && 'easypack_general' === $_GET['tab'] ) {
                    return true;
                }
            }

            return false;

        }
		
		
		
		public function clear_zones_cache() {
            $this->cached_zones = null;
        }
		
		

        private function get_cached_zones() {
            if ( empty( $this->cached_zones ) ) {
                if( class_exists('WC_Shipping_Zones') ) {
                    $this->cached_zones = \WC_Shipping_Zones::get_zones();
                }
            }
            return $this->cached_zones;
        }
		
		
		
		
	}


endif;
