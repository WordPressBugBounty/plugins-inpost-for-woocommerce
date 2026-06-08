<?php
/**
 * EasyPack AJAX
 */

namespace InspireLabs\WoocommerceInpost;

use Exception;
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
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shipping_Parcel_Machines_Economy_COD;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shipping_Parcel_Machines_Weekend_COD;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shippng_Parcel_Machines;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shippng_Parcel_Machines_COD;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shipping_Parcel_Machines_Weekend;
use InspireLabs\WoocommerceInpost\shipping\EasyPack_Shipping_Parcel_Machines_Economy;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'EasyPack_AJAX' ) ) :

	/**
	 * EasyPack AJAX actions
	 */
	class EasyPack_AJAX {

		/**
		 * Ajax handler
		 */
		public static function init() {
			add_action( 'wp_ajax_easypack', array( __CLASS__, 'ajax_easypack' ) );
			add_action( 'admin_head', array( __CLASS__, 'wp_footer_easypack_nonce' ) );
			add_action( 'wp_ajax_inpost_save_to_wc_session', array( __CLASS__, 'save_to_wc_session' ) );
			add_action( 'wp_ajax_nopriv_inpost_save_to_wc_session', array( __CLASS__, 'save_to_wc_session' ) );
			add_action( 'wp_ajax_posting_confirmation_request', array( __CLASS__, 'posting_confirmation_request_callback' ) );
			add_action( 'wp_ajax_update_locker_from_typ_page', array( __CLASS__, 'update_locker_from_typ_page_callback' ) );
			add_action( 'wp_ajax_nopriv_update_locker_from_typ_page', array( __CLASS__, 'update_locker_from_typ_page_callback' ) );
		}

		/**
		 * Add nonce value
		 *
		 * @return void
		 */
		public static function wp_footer_easypack_nonce(): void {
			?>
			<script type="text/javascript">
				var easypack_nonce = '<?php echo esc_attr( wp_create_nonce( 'easypack_nonce' ) ); ?>';
			</script>
			<?php
		}

		/**
		 * Sort ajax actions callbacks
		 *
		 * @return void
		 * @throws \ReflectionException ReflectionException.
		 */
		public static function ajax_easypack(): void {
			check_ajax_referer( 'easypack_nonce', 'security' );

			if ( ! self::user_can_manage_inpost_admin_actions() ) {
				wp_send_json_error( 'forbidden', 403 );
			}

			if ( isset( $_POST['easypack_action'] ) ) {
				$action = sanitize_text_field( wp_unslash( $_POST['easypack_action'] ) );

				if ( 'create_additional_package' === $action ) {
					self::create_additional_package();
				}

				if ( 'change_first_package' === $action ) {
					self::change_first_package();
				}

				if ( 'inpost_pl_metabox_paczkomat' === $action ) {
					self::save_order_metabox_paczkomat();
				}

				if ( 'dispatch_point' === $action ) {
					self::dispatch_point();
				}
				if ( 'parcel_machines_create_package' === $action ) {
					self::parcel_machines_create_package();
				}
				if ( 'parcel_machines_weekend_create_package' === $action ) {
					self::parcel_machines_weekend_create_package();
				}
				if ( 'parcel_machines_weekend_create_package_cod' === $action ) {
					self::parcel_machines_weekend_create_package_cod();
				}
				if ( 'parcel_machines_cancel_package' === $action ) {
					self::parcel_machines_cancel_package();
				}
				if ( 'courier_c2c_create_package_cod' === $action ) {
					self::courier_c2c_create_package_cod();
				}
				if ( 'parcel_machines_economy' === $action ) {
					self::parcel_machines_economy_create_package();
				}
				if ( 'parcel_machines_economy_cod' === $action ) {
					self::parcel_machines_economy_cod_create_package();
				}
				if ( 'parcel_machines_cod_create_package' === $action ) {
					self::parcel_machines_cod_create_package();
				}
				if ( 'esmartmix_create_package' === $action ) {
					self::esmartmix_create_package();
				}
				if ( 'courier_create_package' === $action ) {
					self::courier_create_package();
				}
				if ( 'courier_c2c_create_package' === $action ) {
					self::courier_c2c_create_package();
				}
				if ( 'courier_lse_create_package' === $action ) {
					self::courier_lse_create_package();
				}
				if ( 'courier_lse_create_package_cod' === $action ) {
					self::courier_lse_create_package_cod();
				}
				if ( 'courier_local_standard_create_package' === $action ) {
					self::courier_local_standard_create_package();
				}
				if ( 'courier_local_standard_cod_create_package' === $action ) {
					self::courier_local_standard_cod_create_package();
				}
				if ( 'courier_local_express_create_package' === $action ) {
					self::courier_local_express_create_package();
				}
				if ( 'courier_local_express_cod_create_package' === $action ) {
					self::courier_local_express_cod_create_package();
				}
				if ( 'courier_palette_create_package' === $action ) {
					self::courier_palette_create_package();
				}
				if ( 'courier_palette_cod_create_package' === $action ) {
					self::courier_palette_cod_create_package();
				}
				if ( 'courier_cod_create_package' === $action ) {
					self::courier_cod_create_package();
				}
				if ( 'parcel_machines_cod_cancel_package' === $action ) {
					self::parcel_machines_cod_cancel_package();
				}

				if ( 'easypack_create_bulk_labels' === $action ) {

					if ( isset( $_POST['order_ids'] ) ) {

						$helper = EasyPack_Helper::EasyPack_Helper();

						$data_string   = sanitize_text_field( wp_unslash( $_POST['order_ids'] ) );
						$order_ids_arr = json_decode( stripslashes( $data_string ), true );
						$validated_ids = array();

						if ( is_array( $order_ids_arr ) ) {
							$validated_ids = $helper->validate_order_ids_before_get_labels_from_api( $order_ids_arr );
						}

						if ( ! empty( $validated_ids ) ) {
							// this function echo pdf or zip string.
							$helper->print_stickers( false, $validated_ids );
							die;

						} else {
							echo wp_json_encode(
								array(
									'details' => esc_html__( 'Check your selection.', 'inpost-for-woocommerce' ),
								)
							);
							die;
						}
					}

					echo wp_json_encode( array( 'details' => esc_html__( 'There are some validation errors.', 'inpost-for-woocommerce' ) ) );
					die;
				}

				if ( 'easypack_create_additional_label' === $action ) {

					if ( isset( $_POST['inpost_id'] ) ) {
						$inpost_id = sanitize_text_field( wp_unslash( $_POST['inpost_id'] ) );
						if ( ! empty( $inpost_id ) ) {
							// this function echo pdf or zip string.
							EasyPack_Helper::EasyPack_Helper()->print_sticker_by_inpost_id( $inpost_id );
							die;
						} else {
							echo wp_json_encode( array( 'details' => esc_html__( 'Check your selection.', 'inpost-for-woocommerce' ) ) );
							die;
						}
					}

					echo wp_json_encode( array( 'details' => esc_html__( 'There are some validation errors.', 'inpost-for-woocommerce' ) ) );
					die;
				}
			}
		}

		/**
		 * Dispatch point
		 *
		 * @return void
		 */
		public static function dispatch_point() {

			$dispatch_point_name = isset( $_POST['dispatch_point_name'] )
					? sanitize_text_field( wp_unslash( $_POST['dispatch_point_name'] ) )
					: '';

			if ( ! empty( $dispatch_point_name ) ) {
				try {
					$dispatch_point = EasyPack_API()->dispatch_point( $dispatch_point_name );
					echo wp_json_encode( $dispatch_point );
				} catch ( Exception $e ) {
					echo 0;
				}
			}
			wp_die();
		}

		/**
		 * Parcel machines create package
		 *
		 * @return void
		 * @throws \ReflectionException ReflectionException.
		 */
		public static function parcel_machines_create_package() {
			EasyPack_Shippng_Parcel_Machines::ajax_create_package();
		}

		/**
		 * Parcel machines cancel package
		 *
		 * @return void
		 */
		public static function parcel_machines_cancel_package() {
			EasyPack_Shippng_Parcel_Machines::ajax_cancel_package();
		}

		/**
		 * Parcel machines COD create package
		 *
		 * @return void
		 * @throws \ReflectionException ReflectionException.
		 */
		public static function parcel_machines_cod_create_package() {
			EasyPack_Shippng_Parcel_Machines_COD::ajax_create_package();
		}

		/**
		 * Parcel machines weekend create package
		 *
		 * @return void
		 * @throws \ReflectionException ReflectionException.
		 */
		public static function parcel_machines_weekend_create_package() {
			EasyPack_Shipping_Parcel_Machines_Weekend::ajax_create_package();
		}

		/**
		 * Parcel machines weekend COD create package
		 *
		 * @return void
		 * @throws \ReflectionException ReflectionException.
		 */
		public static function parcel_machines_weekend_create_package_cod() {
			EasyPack_Shipping_Parcel_Machines_Weekend_COD::ajax_create_package();
		}

		/**
		 * Parcel machines economy create package
		 *
		 * @return void
		 * @throws \ReflectionException ReflectionException.
		 */
		public static function parcel_machines_economy_create_package() {
			EasyPack_Shipping_Parcel_Machines_Economy::ajax_create_package();
		}

		/**
		 * Parcel machines economy COD create package
		 *
		 * @return void
		 * @throws \ReflectionException ReflectionException.
		 */
		public static function parcel_machines_economy_cod_create_package() {
			EasyPack_Shipping_Parcel_Machines_Economy_COD::ajax_create_package();
		}

		/**
		 * Courier create package
		 *
		 * @throws \ReflectionException ReflectionException.
		 */
		public static function courier_create_package() {
			EasyPack_Shipping_Method_Courier::ajax_create_package();
		}

		/**
		 * Esmartmix create package
		 *
		 * @throws \ReflectionException ReflectionException.
		 */
		public static function esmartmix_create_package() {
			EasyPack_Shipping_Method_EsmartMix::ajax_create_package();
		}

		/**
		 * Courier C2C create package COD
		 *
		 * @throws \ReflectionException ReflectionException.
		 */
		public static function courier_c2c_create_package_cod() {
			EasyPack_Shipping_Method_Courier_C2C_COD::ajax_create_package();
		}

		/**
		 * Courier C2C create package
		 *
		 * @throws \ReflectionException ReflectionException.
		 */
		public static function courier_c2c_create_package() {
			EasyPack_Shipping_Method_Courier_C2C::ajax_create_package();
		}

		/**
		 * Courier LSE create package
		 *
		 * @throws \ReflectionException ReflectionException.
		 */
		public static function courier_lse_create_package() {
			EasyPack_Shipping_Method_Courier_LSE::ajax_create_package();
		}

		/**
		 * Courier LSE create package COD
		 *
		 * @throws \ReflectionException ReflectionException.
		 */
		public static function courier_lse_create_package_cod() {
			EasyPack_Shipping_Method_Courier_LSE_COD::ajax_create_package();
		}

		/**
		 * Courier local standard create package
		 *
		 * @throws \ReflectionException ReflectionException.
		 */
		public static function courier_local_standard_create_package() {
			EasyPack_Shipping_Method_Courier_Local_Standard::ajax_create_package();
		}

		/**
		 * Courier local standard COD create package
		 *
		 * @throws \ReflectionException ReflectionException.
		 */
		public static function courier_local_standard_cod_create_package() {
			EasyPack_Shipping_Method_Courier_Local_Standard_COD::ajax_create_package();
		}

		/**
		 * Courier local express create package
		 *
		 * @throws \ReflectionException ReflectionException.
		 */
		public static function courier_local_express_create_package() {
			EasyPack_Shipping_Method_Courier_Local_Express::ajax_create_package();
		}

		/**
		 * Courier local express COD create package
		 *
		 * @return void
		 * @throws \ReflectionException ReflectionException.
		 */
		public static function courier_local_express_cod_create_package() {
			EasyPack_Shipping_Method_Courier_Local_Express_COD::ajax_create_package();
		}

		/**
		 * Courier palette create package
		 *
		 * @throws \ReflectionException ReflectionException.
		 */
		public static function courier_palette_create_package() {
			EasyPack_Shipping_Method_Courier_Palette::ajax_create_package();
		}

		/**
		 * Courier palette COD create package
		 *
		 * @throws \ReflectionException ReflectionException.
		 */
		public static function courier_palette_cod_create_package() {
			EasyPack_Shipping_Method_Courier_Palette_COD::ajax_create_package();
		}

		/**
		 * Courier COD create package
		 *
		 * @throws \ReflectionException ReflectionException.
		 */
		public static function courier_cod_create_package() {
			EasyPack_Shipping_Method_Courier_COD::ajax_create_package();
		}

		/**
		 * Parcel machines COD cancel package
		 *
		 * @return void
		 */
		public static function parcel_machines_cod_cancel_package() {
			EasyPack_Shippng_Parcel_Machines_COD::ajax_cancel_package();
		}


		/**
		 * Create additional package
		 *
		 * @return void
		 */
		public static function create_additional_package() {

			$shipping_method = isset( $_POST['easypack_additional_package_method_id'] )
					? sanitize_text_field( wp_unslash( $_POST['easypack_additional_package_method_id'] ) )
					: '';
			$order_id        = isset( $_POST['order_id'] )
					? sanitize_text_field( wp_unslash( $_POST['order_id'] ) )
					: '';

			if ( empty( $shipping_method ) || empty( $order_id ) ) {
				$return_content = array(
					'status'  => 'bad',
					'message' => esc_html__( 'Empty shipping method or order_id', 'inpost-for-woocommerce' ),
				);
				echo wp_json_encode( $return_content );
				wp_die();
			}

			try {

				$shipping_method_class_name = EasyPack_Helper()->get_class_name_by_shipping_id( $shipping_method );

				if ( empty( $shipping_method_class_name ) ) {
					$inpost_method_name         = EasyPack_Helper()->get_method_linked_to_fs_by_instance_id( $shipping_method );
					$shipping_method_class_name = EasyPack_Helper()->get_class_name_by_shipping_id( $inpost_method_name );
				}

				if ( empty( $shipping_method_class_name ) ) {
					$return_content = array(
						'status'  => 'bad',
						'message' => esc_html__( 'Shipping method not found', 'inpost-for-woocommerce' ),
					);
					echo wp_json_encode( $return_content );
					wp_die();
				}

				$class_with_namespace = 'InspireLabs\WoocommerceInpost\shipping\\' . $shipping_method_class_name;

				if ( class_exists( $class_with_namespace ) ) {
					$class_instance = new $class_with_namespace();

					$ret['content'] = $class_instance::order_metabox_content( get_post( $order_id ), false, null, true );
					$ret['status']  = 'ok';

					echo wp_json_encode( $ret );
					wp_die();

				} else {
					$return_content = array(
						'status'  => 'bad',
						'message' => esc_html__( 'Error occured', 'inpost-for-woocommerce' ),
					);
					echo wp_json_encode( $return_content );
					wp_die();
				}
			} catch ( Exception $e ) {
				echo 0;
			}
			wp_die();
		}


		/**
		 * Change metabox output for order metabox
		 *
		 * @return void
		 */
		public static function change_first_package() {

			$shipping_method_instance_id = sanitize_text_field( wp_unslash( $_POST['easypack_change_first_shipment_method_id'] ) );
			$order_id                    = sanitize_text_field( wp_unslash( $_POST['order_id'] ) );

			if ( empty( $shipping_method_instance_id ) || empty( $order_id ) ) {
				$return_content = array(
					'status'  => 'bad',
					'message' => esc_html__( 'Empty order_id or selected InPost method', 'inpost-for-woocommerce' ),
				);
				echo wp_json_encode( $return_content );
				wp_die();
			}

			try {

				$shipping_method_class_name = EasyPack_Helper()->get_class_name_by_shipping_id( $shipping_method_instance_id );

				$inpost_method_name = null;

				if ( empty( $shipping_method_class_name ) ) {
					$inpost_method_name         = EasyPack_Helper()->get_method_linked_to_fs_by_instance_id( $shipping_method_instance_id );
					$shipping_method_class_name = EasyPack_Helper()->get_class_name_by_shipping_id( $inpost_method_name );
				}

				if ( empty( $shipping_method_class_name ) ) {
					$return_content = array(
						'status'  => 'bad',
						'message' => esc_html__( 'Shipping method not found', 'inpost-for-woocommerce' ),
					);
					echo wp_json_encode( $return_content );
					wp_die();
				}

				$class_with_namespace = 'InspireLabs\WoocommerceInpost\shipping\\' . $shipping_method_class_name;

				if ( class_exists( $class_with_namespace ) ) {
					$class_instance = new $class_with_namespace();

					$ret['content'] = $class_instance::order_metabox_content( get_post( $order_id ), false, null );
					$ret['status']  = 'ok';

					if ( is_numeric( $shipping_method_instance_id ) ) {
						if ( class_exists( 'WC_Shipping_Zones' ) ) {
							$shipping_method = \WC_Shipping_Zones::get_shipping_method( $shipping_method_instance_id );
							// Get the shipping method ID from the instance object.
							if ( $shipping_method ) {
								$shipping_method_id = $shipping_method->id;
								$order              = wc_get_order( $order_id );
								if ( $order ) {
									$order->update_meta_data( '_inpost_pl_metabox_shipping_method_instance_id', $shipping_method_instance_id );
									$order->update_meta_data( '_inpost_pl_metabox_shipping_method', $shipping_method_id );
									$order->save();
								}
							}
						}
					}

					echo wp_json_encode( $ret );
					wp_die();

				} else {
					$return_content = array(
						'status'  => 'bad',
						'message' => esc_html__( 'Error occured', 'inpost-for-woocommerce' ),
					);
					echo wp_json_encode( $return_content );
					wp_die();
				}
			} catch ( Exception $e ) {
				echo 0;
			}
			wp_die();
		}



		/**
		 * Ajax handler to save paczkomat number into WC session
		 */
		public static function save_to_wc_session(): void {
			self::save_point_to_wc_session();
		}


		/**
		 * Save paczkomat point number into WC session
		 *
		 * @return void
		 */
		public static function save_point_to_wc_session() {

			check_ajax_referer( 'easypack_nonce', 'security' );

			if ( ! empty( $_POST['key'] ) && 'inpost_pl_wc_paczkomat' === $_POST['key'] ) {

				$key   = sanitize_text_field( wp_unslash( $_POST['key'] ) );
				$value = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';

				if ( is_object( WC() ) && property_exists( WC(), 'session' ) ) {
					WC()->session->set( $key, $value );
					wp_send_json_success( 'Data saved to session' );

				} else {
					wp_send_json_error( 'WC session not available' );

				}
			}

			wp_die();
		}


		/**
		 * AJAX callback to handle posting confirmation request for multiple shipments.
		 *
		 * Validates nonce and sanitizes order IDs, retrieves InPost shipment IDs
		 * for each order, and generates posting confirmation PDF for the
		 * collected shipment identifiers.
		 *
		 * @return void Outputs PDF or handles errors.
		 */
		public static function posting_confirmation_request_callback(): void {

			check_ajax_referer( 'easypack-shipment-manager', 'nonce' );

			if ( ! self::user_can_manage_inpost_admin_actions() ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'inpost-for-woocommerce' ), 403 );
			}

			$orders = isset( $_POST['parcels'] ) ? (array) $_POST['parcels'] : array();
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

			EasyPack_Helper()->post_confirmation_pdf( $shipment_ids );
		}



		/**
		 * AJAX callback to update locker selection from TYP page.
		 *
		 * Validates nonce and input data, retrieves order object, updates
		 * parcel machine ID and description metadata, saves changes,
		 * and returns JSON success or error response.
		 *
		 * @return void Outputs JSON response and exits.
		 */
		public static function update_locker_from_typ_page_callback() {

			check_ajax_referer( 'easypack_nonce', 'security' );

			if ( empty( $_POST['inpost_pl_locker'] ) || empty( $_POST['order_id'] ) || empty( $_POST['order_key'] ) ) {
				wp_send_json_error( 'missing_params' );
			}

			$order_id  = (int) wp_unslash( $_POST['order_id'] );
			$order_key = sanitize_text_field( wp_unslash( $_POST['order_key'] ) );
			$order     = wc_get_order( $order_id );

			if ( ! $order || is_wp_error( $order ) ) {
				wp_send_json_error( 'invalid_order' );
			}

			if ( ! hash_equals( (string) $order->get_order_key(), (string) $order_key ) ) {
				wp_send_json_error( 'forbidden' );
			}

			if ( ! in_array( $order->get_status(), array( 'pending', 'processing', 'on-hold' ), true ) ) {
				wp_send_json_error( 'order_finalised' );
			}

			$new_locker = sanitize_text_field( wp_unslash( $_POST['inpost_pl_locker'] ) );
			$locker_desc = ! empty( $_POST['inpost_pl_locker_desc'] )
				? sanitize_text_field( wp_unslash( $_POST['inpost_pl_locker_desc'] ) )
				: null;

			if ( ! empty( $new_locker ) ) {
				$order->update_meta_data( '_parcel_machine_id', $new_locker );
				if ( ! empty( $locker_desc ) ) {
					$order->update_meta_data( '_parcel_machine_desc', $locker_desc );
				}
				$order->save();
				wp_send_json_success( 'locker_updated' );
			}

			wp_send_json_error( 'invalid_locker' );
		}


		/**
		 * Saves selected InPost parcel locker code to order metadata via AJAX.
		 *
		 * Validates nonce and user permissions before processing. Retrieves parcel locker
		 * code and order ID from POST data, sanitizes inputs, and updates order metadata.
		 * Sends JSON success response on successful save, error response otherwise.
		 *
		 * @return void Outputs JSON response and terminates execution. Returns early if validation fails or required data is missing.
		 */
		public static function save_order_metabox_paczkomat() {

			check_ajax_referer( 'easypack_nonce', 'security' );

			$can_update_services = is_admin() && ( current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' ) );

			if ( ! $can_update_services ) {
				wp_send_json_error();
			}

			if ( empty( $_POST['point_code'] ) || empty( $_POST['order_id'] ) ) {
				return;
			}

			$order_id = sanitize_text_field( wp_unslash( $_POST['order_id'] ) );

			$order = wc_get_order( $order_id );

			if ( ! $order || is_wp_error( $order ) ) {
				return;
			}

			$paczkomat_id = sanitize_text_field( wp_unslash( $_POST['point_code'] ) );

			if ( ! empty( $paczkomat_id ) ) {
				$order->update_meta_data( '_parcel_machine_id', $paczkomat_id );
				$order->save();
				wp_send_json_success( 'point locker code saved' );
			}

			wp_send_json_error();
		}

		/**
		 * Whether the current user may run admin-only InPost AJAX (orders, shipments, labels).
		 *
		 * Not used for nopriv handlers (e.g. update_locker_from_typ_page, save_to_wc_session).
		 *
		 * @return bool
		 */
		private static function user_can_manage_inpost_admin_actions(): bool {
			return current_user_can( 'manage_woocommerce' )
				|| current_user_can( 'edit_shop_orders' )
				|| current_user_can( 'manage_options' );
		}
	}

endif;

