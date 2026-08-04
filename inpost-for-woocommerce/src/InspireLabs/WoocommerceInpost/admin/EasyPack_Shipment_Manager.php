<?php

namespace InspireLabs\WoocommerceInpost\admin;

use InspireLabs\WoocommerceInpost\EasyPack;
use Exception;
use InspireLabs\WoocommerceInpost\EasyPack_API;
use InspireLabs\WoocommerceInpost\EasyPack_Helper;

/**
 * EasyPack Shipment Manager
 *
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'EasyPack_Shipment_Manager' ) ) :

	/**
	 * EasyPack_Shipment_Manager
	 */
	class EasyPack_Shipment_Manager {

		const NONCE_ACTION = 'easypack_shipment_manager';
		const NONCE_FIELD  = 'easypack_shipment_nonce';

		/**
		 *
		 */
		public static function init() {
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
			add_action( 'init', array( __CLASS__, 'print_stickers' ) );
		}

		public static function print_stickers() {
			$action = self::get_label_request_action();

			if ( null === $action ) {
				return;
			}

			if ( ! current_user_can( 'view_woocommerce_reports' ) ) {
				wp_die(
					esc_html__( 'Sorry, you are not allowed to download InPost labels.', 'inpost-for-woocommerce' ),
					esc_html__( 'Forbidden', 'inpost-for-woocommerce' ),
					array( 'response' => 403 )
				);
			}

			check_admin_referer( self::NONCE_ACTION, self::NONCE_FIELD );

			if ( 'stickers' === $action ) {
				EasyPack_Helper::EasyPack_Helper()->print_stickers();
			}

			if ( 'stickers_ret' === $action ) {
				EasyPack_Helper::EasyPack_Helper()->print_stickers( true );
			}

			if ( 'single' === $action ) {
				$sticker_order_id = isset( $_POST['get_sticker_order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['get_sticker_order_id'] ) ) : '';
				if ( ! empty( $sticker_order_id ) ) {
					EasyPack_Helper::EasyPack_Helper()->print_stickers( false, $sticker_order_id );
				}
			}

			if ( 'single_ret' === $action ) {
				$sticker_order_id = isset( $_POST['get_sticker_order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['get_sticker_order_id'] ) ) : '';
				if ( ! empty( $sticker_order_id ) ) {
					EasyPack_Helper::EasyPack_Helper()->print_stickers( true, $sticker_order_id );
				}
			}

			if ( 'confirm' === $action ) {
				EasyPack_Helper::EasyPack_Helper()->print_posting_confirmation();
			}
		}

		/**
		 *
		 */
		public static function admin_menu() {
			global $menu;
			$menu_pos = 56;
			while ( isset( $menu[ $menu_pos ] ) ) {
				++$menu_pos;
			}

			$icon_svg = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIj8+Cjxzdmcgd2lkdGg9IjI0Ni45OTk5OTk5OTk5OTk5NyIgaGVpZ2h0PSIyMjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6c3ZnPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CiA8Zz4KICA8dGl0bGU+TGF5ZXIgMTwvdGl0bGU+CiAgPGcgaWQ9InN2Z18xIiBzdHJva2U9Im51bGwiPgogICA8cGF0aCBpZD0ic3ZnXzciIGQ9Im0xMDEuNTYxMDQsMTEwLjY3NDkyYzAsMCAtMTEuNjQ2MzcsNC41MDMxIC0yNi4wMTU5LDQuNTAzMWMtMTQuMzY4MTQsMCAtMjYuMDE1OSwtNC41MDMxIC0yNi4wMTU5LC00LjUwMzFzMTEuNjQ3NzUsLTQuNTAwMzMgMjYuMDE1OSwtNC41MDAzM2MxNC4zNjk1MywwIDI2LjAxNTksNC41MDAzMyAyNi4wMTU5LDQuNTAwMzMiIGZpbGw9IiNGRkNDMDAiIHN0cm9rZT0ibnVsbCIvPgogICA8cGF0aCBpZD0ic3ZnXzgiIGQ9Im0xMzcuNTM0NjUsNDQuNDYwM2MwLDAgLTEwLjMyMDA2LC02Ljk0OTY1IC0xOC4zNTM5OSwtMTguNjI3ODNjLTguMDMzOTQsLTExLjY3NjggLTEwLjc0MDUsLTIzLjY1OTI0IC0xMC43NDA1LC0yMy42NTkyNHMxMC4zMTg2OCw2Ljk0ODI3IDE4LjM1Mzk5LDE4LjYyNTA3YzguMDMzOTQsMTEuNjc5NTYgMTAuNzQwNSwyMy42NjIwMSAxMC43NDA1LDIzLjY2MjAxIiBmaWxsPSIjRkZDQzAwIiBzdHJva2U9Im51bGwiLz4KICAgPHBhdGggaWQ9InN2Z185IiBkPSJtMTExLjE4NjgzLDczLjAzMjAxYzAsMCAtMTIuNDM4ODQsLTEuMzg1NzggLTI1LjEyNTI0LC03Ljk5OTM2Yy0xMi42ODY0LC02LjYxMjIgLTIwLjgxNDM4LC0xNS45NDc1NSAtMjAuODE0MzgsLTE1Ljk0NzU1czEyLjQzODg0LDEuMzg1NzggMjUuMTI1MjQsNy45OTkzNmMxMi42ODY0LDYuNjEyMiAyMC44MTQzOCwxNS45NDc1NSAyMC44MTQzOCwxNS45NDc1NSIgZmlsbD0iI0ZGQ0MwMCIgc3Ryb2tlPSJudWxsIi8+CiAgIDxwYXRoIGlkPSJzdmdfMTAiIGQ9Im0xMzUuNzU4ODYsMTMwLjc2ODc1YzcuNTI5MTMsLTIuMjg0NzQgMTMuOTA0ODMsLTUuNjE5MTkgMTMuOTA0ODMsLTUuNjE5MTlzLTE3LjczNTc5LC00LjkzMDQ1IC0xNi40MzU3NSwtMjMuNDU0NTVjNC4wNzI5OCwtMzAuMzk3MjkgMjguNjgzNzQsLTU0LjI2MTIyIDU5LjQ5NDU1LC01OC4xMDMyM2MtMy4yNjgwNiwtMC40NTA4NiAtNi42MDUyOCwtMC42ODczNiAtMTAuMDAxOTcsLTAuNjcyMTVjLTM4LjEwODk4LDAuMTY4NzMgLTY4Ljg2MzA5LDMwLjU5NTA2IC02OC42OTE2LDY3Ljk1NzIyYzAuMTcwMTEsMzcuMzYwNzcgMzEuMjA0OTcsNjcuNTEwNSA2OS4zMTI1Nyw2Ny4zNDMxNmMzLjE3ODE3LC0wLjAxMzgzIDYuMzAxMDIsLTAuMjU3MjQgOS4zNjMwMSwtMC42Nzc2OGMtMjcuMDQwNzEsLTMuMzc4NzEgLTQ5LjA1MDAyLC0yMi4wNzE1NCAtNTYuOTQ1NjUsLTQ2Ljc3MzU3bDAuMDAwMDEsLTAuMDAwMDF6IiBmaWxsPSIjRkZDQzAwIi8+CiAgIDxwYXRoIGlkPSJzdmdfMTEiIGQ9Im0xMzcuNTM0NjUsMTc2LjYzMjNjMCwwIC0xMC4zMjAwNiw2Ljk0OTY1IC0xOC4zNTM5OSwxOC42MjkyMWMtOC4wMzM5NCwxMS42NzU0MSAtMTAuNzQwNSwyMy42NjA2MiAtMTAuNzQwNSwyMy42NjA2MnMxMC4zMTg2OCwtNi45NDk2NSAxOC4zNTM5OSwtMTguNjI3ODNjOC4wMzM5NCwtMTEuNjc2OCAxMC43NDA1LC0yMy42NjIwMSAxMC43NDA1LC0yMy42NjIwMSIgZmlsbD0iI0ZGQ0MwMCIgc3Ryb2tlPSJudWxsIi8+CiAgIDxwYXRoIGlkPSJzdmdfMTIiIGQ9Im0xMTEuMTg2ODMsMTQ4LjA2MTk3YzAsMCAtMTIuNDM4ODQsMS4zODU3OCAtMjUuMTI1MjQsNy45OTkzNmMtMTIuNjg2NCw2LjYxMDgxIC0yMC44MTQzOCwxNS45NDYxNyAtMjAuODE0MzgsMTUuOTQ2MTdzMTIuNDM4ODQsLTEuMzg1NzggMjUuMTI1MjQsLTcuOTk3OThzMjAuODE0MzgsLTE1Ljk0NzU1IDIwLjgxNDM4LC0xNS45NDc1NSIgZmlsbD0iI0ZGQ0MwMCIgc3Ryb2tlPSJudWxsIi8+CiAgPC9nPgogPC9nPgo8L3N2Zz4=';
			add_menu_page(
				__( 'InPost', 'inpost-for-woocommerce' ),
				__( 'InPost', 'inpost-for-woocommerce' ),
				'view_woocommerce_reports',
				'inpost',
				null,
				$icon_svg,
				$menu_pos
			);
			add_submenu_page(
				'inpost',
				__( 'Settings', 'inpost-for-woocommerce' ),
				__( 'Settings', 'inpost-for-woocommerce' ),
				'view_woocommerce_reports',
				'admin.php?page=wc-settings&tab=easypack_general'
			);
			add_submenu_page(
				'inpost',
				__( 'Shipments', 'inpost-for-woocommerce' ),
				__( 'Shipments', 'inpost-for-woocommerce' ),
				'view_woocommerce_reports',
				'easypack_shipment',
				array( __CLASS__, 'easypack_shipment' )
			);

			remove_submenu_page( 'inpost', 'inpost' );
		}

		/**
		 * @throws Exception
		 */
		public static function easypack_shipment() {
			$courier_pickup_service = EasyPack()->get_courier_pickup_service();
			$status_service         = EasyPack()->get_shipment_status_service();
			$shipment_service       = EasyPack()->get_shipment_service();
			$view_var_points        = $courier_pickup_service->getDispatchPointsStrArray();
			$dispatch_point         = (int) get_option( EasyPack::ATTRIBUTE_PREFIX . '_dpoint_selected' );

			$view_var_send_methods = self::get_send_methods_for_country( EasyPack_API()->api_country() );
			$view_var_statuses     = $status_service->get_statuses_key_value();
			$view_var_services     = $shipment_service->get_services_key_value();

			if ( true === self::is_pickup() ) {
				self::pickup();
			}

			if ( true === self::is_cancel() ) {
				check_admin_referer( self::NONCE_ACTION, self::NONCE_FIELD );
				return false;
			}

			$send_method = 'all';

			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Admin shipment list UI filters; read-only.
			if ( isset( $_GET['send_method'] ) ) {
				$send_method = sanitize_text_field( wp_unslash( $_GET['send_method'] ) );
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			$view_var_shipment_manager_list_table = new EasyPack_Shipment_Manager_List_Table( $send_method );

			include 'views/html-shipment-manager.php';
		}



		private static function pickup() {
			check_admin_referer( self::NONCE_ACTION, self::NONCE_FIELD );

			$shipment_service       = EasyPack()->get_shipment_service();
			$courier_pickup_service = EasyPack()->get_courier_pickup_service();

			$selected_data = null;

			if ( isset( $_POST['easypack_parcel'] ) ) {
				if ( is_array( $_POST['easypack_parcel'] ) ) {
					$selected_data = array_map(
						'sanitize_text_field',
						wp_unslash( $_POST['easypack_parcel'] )
					);
				}
			}

			$selected_shipments = $selected_data;
			$dispatch_point     = isset( $_POST['easypack_dispatch_point'] ) ? sanitize_text_field( wp_unslash( $_POST['easypack_dispatch_point'] ) ) : null;

			if ( empty( $dispatch_point ) ) {
				return;
			}

			$shipments_to_pick_up = array();
			if ( ! empty( $selected_shipments ) ) {
				foreach ( $selected_shipments as $order_id ) {
					$shipments_to_pick_up[] = $shipment_service->get_shipment_by_order_id( $order_id );
				}
			}

			$dispatch_point_arr = $courier_pickup_service->getDispatchPoint( (int) $dispatch_point );

			try {
				$courier_pickup_service->createDispatchOrder( $dispatch_point_arr, $shipments_to_pick_up );
				$message = esc_html__( 'Shipments dispathed ', 'inpost-for-woocommerce' );
				printf( '<div class="updated"><p>%s</p></div>', esc_html( $message ) );

			} catch ( Exception $e ) {
				$class   = 'error';
				$message = esc_html__( 'Error while creating manifest: ', 'inpost-for-woocommerce' ) . $e->getMessage();
				printf( '<div class="%s"><p>%s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
			}
		}


		/**
		 * @param string $api_country
		 *
		 * @return array
		 */
		private static function get_send_methods_for_country( $api_country ) {
			if ( EasyPack_API::COUNTRY_PL === $api_country ) {
				return array(
					'any'            => __( 'All', 'inpost-for-woocommerce' ),
					'parcel_locker'  => __( 'Parcel Locker', 'inpost-for-woocommerce' ),
					'dispatch_order' => __( 'Courier', 'inpost-for-woocommerce' ),
					'pop'            => __( 'POP', 'inpost-for-woocommerce' ),
				);
			}

			return array();
		}

		/**
		 * Resolve which label/confirmation POST action is requested.
		 *
		 * @return string|null
		 */
		private static function get_label_request_action() {
			if ( true === self::is_stickers_request() ) {
				return 'stickers';
			}
			if ( true === self::is_stickers_return_request() ) {
				return 'stickers_ret';
			}
			if ( true === self::is_sticker_single_request() ) {
				return 'single';
			}
			if ( true === self::is_sticker_single_ret_request() ) {
				return 'single_ret';
			}
			if ( true === self::is_posting_confirmation_request() ) {
				return 'confirm';
			}
			return null;
		}

		/**
		 * @return bool
		 */
		public static function is_courier_context() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Admin shipment list UI filters; read-only.
			return EasyPack_API()->api_country() == EasyPack_API::COUNTRY_PL
					&& isset( $_GET['send_method'] )
					&& 'dispatch_order' === $_GET['send_method'];
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}


		/**
		 * @return bool
		 */
		private static function is_posting_confirmation_request() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in print_stickers() before action runs.
			return isset( $_POST['easypack_posting_confirmation_request'] )
					&& '1' === $_POST['easypack_posting_confirmation_request'];
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		/**
		 * @return bool
		 */
		private static function is_stickers_request() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in print_stickers() before action runs.
			return isset( $_POST['easypack_get_stickers_request'] )
					&& '1' === $_POST['easypack_get_stickers_request'];
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		/**
		 * @return bool
		 */
		private static function is_sticker_single_request() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in print_stickers() before action runs.
			return isset( $_POST['easypack_get_sticker_single_request'] )
					&& '1' === $_POST['easypack_get_sticker_single_request'];
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		private static function is_sticker_single_ret_request() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in print_stickers() before action runs.
			return isset( $_POST['easypack_get_sticker_single_request_ret'] )
					&& '1' === $_POST['easypack_get_sticker_single_request_ret'];
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		/**
		 * @return bool
		 */
		private static function is_stickers_return_request() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in print_stickers() before action runs.
			return isset( $_POST['easypack_get_stickers_ret_request'] )
					&& '1' === $_POST['easypack_get_stickers_ret_request'];
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		/**
		 * @return bool
		 */
		private static function is_pickup() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in pickup() before action runs.
			return isset( $_POST['easypack_create_manifest_input'] )
					&& 1 == $_POST['easypack_create_manifest_input'];
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		/**
		 * @return bool
		 */
		private static function is_cancel() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in easypack_shipment() before action runs.
			return isset( $_POST['easypack_cancel_courier'] )
					&& 1 == $_POST['easypack_cancel_courier'];
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		public static function getSendingMethodFilterFromRequest() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Admin shipment list UI filters; read-only.
			return ! empty( $_GET['send_method'] )
				? sanitize_key( $_GET['send_method'] )
				: null;
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		public static function getStatusFilterFromRequest() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Admin shipment list UI filters; read-only.
			return ! empty( $_GET['status'] )
				? sanitize_key( $_GET['status'] )
				: null;
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		public static function getServiceFilterFromRequest() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Admin shipment list UI filters; read-only.
			return ! empty( $_GET['service'] )
				? sanitize_key( $_GET['service'] )
				: null;
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		public static function getReferenceNumberFilterFromRequest() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Admin shipment list UI filters; read-only.
			return ! empty( $_GET['reference_number'] )
				? (int) sanitize_key( $_GET['reference_number'] )
				: null;
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		public static function getTrackingNumberFilterFromRequest() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Admin shipment list UI filters; read-only.
			return ! empty( $_GET['tracking_number'] )
				? sanitize_key( $_GET['tracking_number'] )
				: null;
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		public static function getOrderIdFilterFromRequest() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Admin shipment list UI filters; read-only.
			return ! empty( $_GET['order_id'] )
				? (int) sanitize_key( $_GET['order_id'] )
				: null;
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		public static function getReceiverEmailFilterFromRequest() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Admin shipment list UI filters; read-only.
			return ! empty( $_GET['receiver_email'] )
				? filter_var( wp_unslash( $_GET['receiver_email'] ), FILTER_SANITIZE_EMAIL )
				: null;
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		public static function getReceiverPhoneFilterFromRequest() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Admin shipment list UI filters; read-only.
			return ! empty( $_GET['receiver_phone'] )
				? esc_sql( strip_shortcodes( wp_strip_all_tags( wp_unslash( $_GET['receiver_phone'] ) ) ) )
				: null;
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}
	}

endif;
