<?php

namespace InspireLabs\WoocommerceInpost;

use DateTime;
use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Model;
use InspireLabs\WoocommerceInpost\shipx\services\shipment\ShipX_Shipment_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EasyPack_Webhook
 */
class EasyPack_Webhook {


	/**
	 * $debud_order_id
	 */
	private $debud_order_id = '';

	/**
	 * Registers hooks for the webhook functionality.
	 *
	 * @return void
	 *
	 * @since 1.7.5
	 * @access public
	 */
	public function hooks() {
		add_action( 'rest_api_init', array( $this, 'register_webhook_status_route' ) );
	}


	/**
	 * Registers the REST API route for InPost webhook callbacks.
	 *
	 * @return void
	 *
	 * @since 1.7.5
	 * @access public
	 */
	public function register_webhook_status_route() {
		register_rest_route(
			'inpost_pl/v1',
			'/order/update',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'inpost_webhook_callback' ),
				'args'                => array(),
				'permission_callback' => function () {
					return true;
				},
			)
		);
	}


	/**
	 * Handles InPost webhook callbacks for order status updates.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response The REST response.
	 *
	 * @since 1.7.5
	 * @access public
	 */
	public function inpost_webhook_callback( \WP_REST_Request $request ) {

		$webhook_body = array();
		$headers      = array();
		$status       = null;

		if ( ! $request->is_json_content_type() ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				\wc_get_logger()->debug( 'Not JSON', array( 'source' => 'inpost-pl-webhook' ) );
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Structured debug output for WooCommerce logs.
				\wc_get_logger()->debug( print_r( $request->get_body(), true ), array( 'source' => 'inpost-pl-webhook' ) );
			}
		}

		try {

			$webhook_body = json_decode( $request->get_body(), true );
			$headers      = $request->get_headers();

		} catch ( \Exception $e ) {

			$exception_data = array(
				'status'   => $e->getCode(),
				'response' => $e->getMessage(),
			);

			if ( function_exists( 'wc_get_logger' ) ) {
				\wc_get_logger()->debug( 'Error:', array( 'source' => 'inpost-pl-webhook' ) );
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Structured debug output for WooCommerce logs.
				\wc_get_logger()->debug( print_r( $exception_data, true ), array( 'source' => 'inpost-pl-webhook' ) );
			}

			return new \WP_REST_Response( $exception_data, 500 );
		}

		if ( ! $this->verify_request( $headers ) ) {

			$resp = array(
				'status'   => 403,
				'response' => 'IP Address does not match to InPost API.',
			);

			if ( function_exists( 'wc_get_logger' ) ) {
				\wc_get_logger()->debug( 'IP Address does not match to InPost API:', array( 'source' => 'inpost-pl-webhook' ) );
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Structured debug output for WooCommerce logs.
				\wc_get_logger()->debug( print_r( $headers, true ), array( 'source' => 'inpost-pl-webhook' ) );
			}

			return new \WP_REST_Response( $resp, 403 );
		}

		if ( ! $this->verify_organisation_id( $webhook_body ) ) {

			$resp = array(
				'status'   => 403,
				'response' => 'Organisation ID does not match.',
			);

			if ( function_exists( 'wc_get_logger' ) ) {
				\wc_get_logger()->debug( 'Organisation ID does not match:', array( 'source' => 'inpost-pl-webhook' ) );
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Structured debug output for WooCommerce logs.
				\wc_get_logger()->debug( print_r( $webhook_body, true ), array( 'source' => 'inpost-pl-webhook' ) );
			}

			return new \WP_REST_Response( $resp, 403 );
		}

		$tracking_number = null;
		$wc_order_id     = null;

		if ( ! empty( $webhook_body['payload']['tracking_number'] ) ) {
			$tracking_number = $webhook_body['payload']['tracking_number'];
		}

		if ( $tracking_number ) {
			$wc_order_id          = $this->get_order_id_by_tracking_number( $tracking_number );
			$this->debud_order_id = $wc_order_id;
		} elseif ( function_exists( 'wc_get_logger' ) ) {

			\wc_get_logger()->debug( 'Tracking number not found:', array( 'source' => 'inpost-pl-webhook' ) );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Structured debug output for WooCommerce logs.
			\wc_get_logger()->debug( print_r( $webhook_body, true ), array( 'source' => 'inpost-pl-webhook' ) );
		}

		if ( empty( $wc_order_id ) ) {

			$resp = array(
				'status'   => 403,
				'response' => 'Woo order ID not found.',
			);

			if ( function_exists( 'wc_get_logger' ) ) {
				\wc_get_logger()->debug( 'Woo order ID not found:', array( 'source' => 'inpost-pl-webhook' ) );
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Structured debug output for WooCommerce logs.
				\wc_get_logger()->debug( print_r( $webhook_body, true ), array( 'source' => 'inpost-pl-webhook' ) );
			}

			return new \WP_REST_Response( $resp, 403 );
		}

		if ( function_exists( 'wc_get_logger' ) ) {
			\wc_get_logger()->debug( 'Webhook received:', array( 'source' => 'inpost-pl-webhook-order-' . $this->debud_order_id ) );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Structured debug output for WooCommerce logs.
			\wc_get_logger()->debug( print_r( $webhook_body, true ), array( 'source' => 'inpost-pl-webhook-order-' . $this->debud_order_id ) );
		}

		if ( ! empty( $webhook_body['payload']['status'] ) ) {
			$status = $webhook_body['payload']['status'];
		} elseif ( ! empty( $webhook_body['event'] ) && 'shipment_confirmed' === $webhook_body['event'] ) {
			$status = 'confirmed';
		}

		if ( empty( $status ) ) {

			$resp = array(
				'status'   => 200,
				'response' => 'Webhook status not found.',
			);

			if ( function_exists( 'wc_get_logger' ) ) {
				\wc_get_logger()->debug( 'Webhook status not found:', array( 'source' => 'inpost-pl-webhook-order-' . $this->debud_order_id ) );
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Structured debug output for WooCommerce logs.
				\wc_get_logger()->debug( print_r( $webhook_body, true ), array( 'source' => 'inpost-pl-webhook-order-' . $this->debud_order_id ) );
			}

			return new \WP_REST_Response( $resp, 200 );
		}

		$wc_order = wc_get_order( $wc_order_id );

		if ( ! $wc_order || is_wp_error( $wc_order ) ) {
			$resp = array(
				'status'   => 200,
				'response' => 'WC Order not found.',
			);

			if ( function_exists( 'wc_get_logger' ) ) {
				\wc_get_logger()->debug( 'WC Order not found for order_id: ' . $this->debud_order_id, array( 'source' => 'inpost-pl-webhook-order-' . $this->debud_order_id ) );
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Structured debug output for WooCommerce logs.
				\wc_get_logger()->debug( print_r( $wc_order, true ), array( 'source' => 'inpost-pl-webhook-order-' . $this->debud_order_id ) );
			}

			return new \WP_REST_Response( $resp, 200 );
		}

		$wc_order->update_meta_data( 'easypack_webhook_status', $status );
		$wc_order->save();

		$change_order_status_webhook = get_option( 'easypack_change_order_status_by_webhook' );
		if ( $change_order_status_webhook === $status ) {
			$current_order_status = $wc_order->get_status();
			if ( 'completed' !== $current_order_status ) {
				$wc_order->update_status( 'wc-completed' );
			}
		}

		$shipment = $wc_order->get_meta( '_shipx_shipment_object' );

		if ( $shipment instanceof ShipX_Shipment_Model ) {
			$shipment->getInternalData()->setStatus( $status );
			$status_service     = EasyPack::EasyPack()->get_shipment_status_service();
			$status_title       = $status_service->getStatusTitle( $status );
			$status_description = $status_service->getStatusDescription( $status );

			if ( empty( $status_title ) ) {
				$status_title = ucwords( str_replace( '_', ' ', $status ) );
			}

			$shipment->getInternalData()->setStatusTitle( $status_title );
			if ( ! empty( $status_description ) ) {
				$shipment->getInternalData()->setStatusDescription( $status_description );
			}

			if ( isset( $body['event_ts'] ) && ! empty( $body['event_ts'] ) ) {
				$date      = new DateTime( $body['event_ts'] );
				$timestamp = $date->getTimestamp();
				$shipment->getInternalData()->setStatusChangedTimestamp( $timestamp );
			}
			$shipment_service = new ShipX_Shipment_Service();
			$shipment_service->update_shipment_to_db( $shipment );
		}

		$resp = array(
			'status'   => 200,
			'response' => 'InPost webhook successfully processed',
		);

		return new \WP_REST_Response( $resp, 200 );
	}


	/**
	 * Verifies that a webhook request comes from an authorized IP address.
	 *
	 * Allowed sources (InPost):
	 * - 91.216.25.0/24
	 * - 34.38.70.198
	 * - 34.76.195.238
	 *
	 * @param array $headers The request headers.
	 * @return bool True if the request is verified, false otherwise.
	 *
	 * @since 1.9.7
	 * @access private
	 */
	private function verify_request( $headers ) {

		if ( 'yes' !== get_option( 'easypack_enable_webhooks' ) ) {
			return false;
		}

		$source_ip = $this->get_webhook_source_ip( $headers );
		if ( empty( $source_ip ) || ! filter_var( $source_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return false;
		}

		return $this->is_allowed_inpost_webhook_ip( $source_ip );
	}

	/**
	 * Resolves the client IP for webhook verification.
	 *
	 * Prefer CF-Connecting-IP (when behind Cloudflare), then REMOTE_ADDR.
	 * X-Real-IP / X-Forwarded-For are fallbacks only.
	 *
	 * @param array $headers The request headers.
	 * @return string
	 */
	private function get_webhook_source_ip( $headers ) {
		$candidates = array();

		if ( is_array( $headers ) && ! empty( $headers['cf_connecting_ip'][0] ) ) {
			$candidates[] = trim( (string) $headers['cf_connecting_ip'][0] );
		}

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$candidates[] = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		if ( is_array( $headers ) ) {
			foreach ( array( 'x_real_ip', 'x_forwarded_for' ) as $header_key ) {
				if ( empty( $headers[ $header_key ][0] ) ) {
					continue;
				}
				$value = trim( (string) $headers[ $header_key ][0] );
				if ( false !== strpos( $value, ',' ) ) {
					$parts = explode( ',', $value );
					$value = trim( $parts[0] );
				}
				if ( '' !== $value ) {
					$candidates[] = $value;
				}
			}
		}

		foreach ( $candidates as $ip ) {
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				return $ip;
			}
		}

		return '';
	}

	/**
	 * Checks whether an IPv4 address is in the InPost webhook allowlist.
	 *
	 * @param string $ip IPv4 address.
	 * @return bool
	 */
	private function is_allowed_inpost_webhook_ip( $ip ) {
		$allowed_exact = array(
			'34.38.70.198',
			'34.76.195.238',
		);

		if ( in_array( $ip, $allowed_exact, true ) ) {
			return true;
		}

		// 91.216.25.0/24
		$ip_parts = explode( '.', $ip );
		if ( 4 === count( $ip_parts )
			&& '91' === $ip_parts[0]
			&& '216' === $ip_parts[1]
			&& '25' === $ip_parts[2]
			&& ctype_digit( $ip_parts[3] )
			&& (int) $ip_parts[3] >= 0
			&& (int) $ip_parts[3] <= 255
		) {
			return true;
		}

		return false;
	}


	/**
	 * Verifies that the organization ID in the webhook request matches the configured one.
	 *
	 * @param array $body The request body.
	 * @return bool True if the organization ID is verified, false otherwise.
	 *
	 * @since 1.7.5
	 * @access private
	 */
	private function verify_organisation_id( $body ) {

		if ( 'yes' !== get_option( 'easypack_enable_webhooks' ) ) {
			return false;
		}

		if ( ! is_array( $body ) || ! isset( $body['organization_id'] ) ) {
			return false;
		}

		if ( empty( $body['organization_id'] ) ) {
			return false;
		}

		$incoming = $body['organization_id'];

		// Reject non-scalar types (e.g. JSON boolean true must not match any org id).
		if ( ! is_string( $incoming ) && ! is_int( $incoming ) ) {
			return false;
		}

		$id_from_settings = trim( (string) get_option( 'easypack_organization_id' ) );
		if ( '' === $id_from_settings ) {
			return false;
		}

		return (string) $incoming === $id_from_settings;
	}

	/**
	 * Retrieves the order ID associated with a tracking number.
	 *
	 * @param string $tracking_number The tracking number to search for.
	 * @return int|null The order ID or null if not found.
	 *
	 * @since 1.7.5
	 * @access private
	 */
	private function get_order_id_by_tracking_number( $tracking_number ) {

		$order_id = null;

		global $wpdb;

		$order_ids = array();

		$meta_key   = '_easypack_parcel_tracking';
		$meta_value = $tracking_number;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off webhook order lookup by tracking meta; caching not applicable.
		if ( 'yes' === get_option( 'woocommerce_custom_orders_table_enabled' ) ) {

			$order_ids = $wpdb->get_col(
				$wpdb->prepare(
					"
                    SELECT DISTINCT om.order_id
                    FROM {$wpdb->prefix}wc_orders_meta AS om
                    WHERE om.meta_key = %s
                    AND om.meta_value = %s
                ",
					$meta_key,
					$meta_value
				)
			);

			if ( empty( $order_ids ) ) {
				// try to get order id from post meta table.
				$order_ids = $wpdb->get_col(
					$wpdb->prepare(
						"
						SELECT DISTINCT pm.post_id
						FROM {$wpdb->prefix}postmeta AS pm
						WHERE pm.meta_key = %s
						AND pm.meta_value = %s
					",
						array(
							$meta_key,
							$meta_value,
						)
					)
				);
			}
		} else {

			$order_ids = $wpdb->get_col(
				$wpdb->prepare(
					"
                    SELECT ID
                    FROM {$wpdb->prefix}posts o
                    INNER JOIN {$wpdb->prefix}postmeta om
                        ON o.ID = om.post_id            
                    WHERE o.post_type = %s    
                    AND om.meta_key = %s
                    AND om.meta_value = %s
                ",
					array(
						'shop_order',
						$meta_key,
						$meta_value,
					)
				)
			);
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! empty( $order_ids ) && isset( $order_ids[0] ) ) {
			$order_id = $order_ids[0];
		}

		return $order_id;
	}
}
