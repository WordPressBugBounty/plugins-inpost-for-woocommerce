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
	 * Known Cloudflare IPv4 and IPv6 CIDR ranges.
	 *
	 * @var array
	 */
	private $cloudflare_ip_ranges = array(
		// IPv4
		'103.21.244.0/22',
		'103.22.200.0/22',
		'103.31.4.0/22',
		'141.101.64.0/18',
		'108.162.192.0/18',
		'190.93.240.0/20',
		'188.114.96.0/20',
		'197.234.240.0/22',
		'198.41.128.0/17',
		'162.158.0.0/15',
		'104.16.0.0/13',
		'104.24.0.0/14',
		'172.64.0.0/13',
		'131.0.72.0/22',
		// IPv6
		'2400:cb00::/32',
		'2606:4700::/32',
		'2803:f800::/32',
		'2405:b500::/32',
		'2405:8100::/32',
		'2a06:9880::/29',
		'2c0f:f248::/32',
	);

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
			$target_order_status = 'completed';

			/**
			 * Filter the WooCommerce order status applied when a webhook matches
			 * the "Change order status by webhook" setting (General settings).
			 *
			 * Default is `completed`. Return another registered order status slug
			 * (e.g. `processing`, `on-hold`) or `wc-processing`. Return an empty
			 * string to skip changing the order status.
			 *
			 * @param string   $target_order_status Default WooCommerce status slug.
			 * @param \WC_Order $wc_order           Order being updated.
			 * @param string   $webhook_status      InPost status from the webhook payload.
			 * @param array    $webhook_body        Full decoded webhook request body.
			 */
			$target_order_status = apply_filters(
				'inpost_pl_webhook_order_status',
				$target_order_status,
				$wc_order,
				$status,
				$webhook_body
			);

			if ( is_string( $target_order_status ) && '' !== $target_order_status ) {
				$target_slug          = str_replace( 'wc-', '', $target_order_status );
				$current_order_status = $wc_order->get_status();
				if ( $target_slug !== $current_order_status ) {
					$wc_order->update_status( $target_order_status );
				}
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

			if ( isset( $webhook_body['event_ts'] ) && ! empty( $webhook_body['event_ts'] ) ) {
				$date      = new DateTime( $webhook_body['event_ts'] );
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
		if ( empty( $source_ip ) || ! filter_var( $source_ip, FILTER_VALIDATE_IP ) ) {
			return false;
		}

		return $this->is_allowed_inpost_webhook_ip( $source_ip );
	}

	/**
	 * Resolves the true source IP for webhook verification safely.
	 *
	 * 1. Direct connection from InPost allowed IP -> Returns direct IP immediately.
	 * 2. Direct connection from Cloudflare Edge -> Extracts and returns CF-Connecting-IP.
	 * 3. Direct connection from spoofed/unknown IP -> Returns direct IP (which will fail validation).
	 *
	 * @param array $headers The request headers.
	 * @return string
	 */
	private function get_webhook_source_ip( $headers ) {

		if ( empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return '';
		}

		$direct_ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );

		if ( ! filter_var( $direct_ip, FILTER_VALIDATE_IP ) ) {
			return '';
		}

		// Optimization: If the direct socket IP is ALREADY an allowed InPost IP, trust it immediately.
		// This handles direct non-Cloudflare connections and circumvents CF range checks.
		if ( $this->is_allowed_inpost_webhook_ip( $direct_ip ) ) {
			return $direct_ip;
		}

		// Only trust CF-Connecting-IP if the direct socket connection comes from Cloudflare Edge IPs.
		if ( $this->is_cloudflare_ip( $direct_ip ) ) {
			if ( is_array( $headers ) && ! empty( $headers['cf_connecting_ip'][0] ) ) {
				$cf_ip = trim( (string) $headers['cf_connecting_ip'][0] );
				if ( filter_var( $cf_ip, FILTER_VALIDATE_IP ) ) {
					return $cf_ip;
				}
			}
		}

		return $direct_ip;
	}

	/**
	 * Checks whether an IP address belongs to Cloudflare's network.
	 * Allows overriding/extending ranges via 'easypack_cloudflare_ip_ranges' WP filter.
	 *
	 * @param string $ip IP address to check.
	 * @return bool
	 */
	private function is_cloudflare_ip( $ip ) {
		/**
		 * Filters the array of allowed Cloudflare IP ranges.
		 *
		 * @param array $ranges Array of IPv4/IPv6 CIDR strings.
		 */
		$ranges = apply_filters( 'easypack_cloudflare_ip_ranges', $this->cloudflare_ip_ranges );

		if ( ! is_array( $ranges ) ) {
			$ranges = $this->cloudflare_ip_ranges;
		}

		foreach ( $ranges as $range ) {
			if ( $this->ip_in_cidr( $ip, $range ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks whether an IP address is in the InPost webhook allowlist.
	 *
	 * @param string $ip IP address.
	 * @return bool
	 */
	private function is_allowed_inpost_webhook_ip( $ip ) {
		$allowed_ranges = array(
			'34.38.70.198',
			'34.76.195.238',
			'91.216.25.0/24',
		);

		foreach ( $allowed_ranges as $range ) {
			if ( $this->ip_in_cidr( $ip, $range ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks whether an IP address matches a given CIDR subnet or single IP address.
	 * Supports both IPv4 and IPv6.
	 *
	 * @param string $ip   IP address to check.
	 * @param string $cidr Subnet in CIDR notation or single IP address.
	 * @return bool
	 */
	private function ip_in_cidr( $ip, $cidr ) {
		if ( false === strpos( $cidr, '/' ) ) {
			return $ip === $cidr;
		}

		list( $subnet, $mask_bits ) = explode( '/', $cidr, 2 );
		$mask_bits                  = (int) $mask_bits;

		// Pre-validate IPs.
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) || ! filter_var( $subnet, FILTER_VALIDATE_IP ) ) {
			return false;
		}

		$ip_bin     = inet_pton( $ip );
		$subnet_bin = inet_pton( $subnet );

		if ( false === $ip_bin || false === $subnet_bin ) {
			return false;
		}

		if ( strlen( $ip_bin ) !== strlen( $subnet_bin ) ) {
			return false;
		}

		$bytes = strlen( $ip_bin );
		for ( $i = 0; $i < $bytes; $i++ ) {
			if ( $mask_bits >= 8 ) {
				$mask       = 0xFF;
				$mask_bits -= 8;
			} elseif ( $mask_bits > 0 ) {
				$mask      = 0xFF << ( 8 - $mask_bits );
				$mask_bits = 0;
			} else {
				$mask = 0x00;
			}

			if ( ( ord( $ip_bin[ $i ] ) & $mask ) !== ( ord( $subnet_bin[ $i ] ) & $mask ) ) {
				return false;
			}
		}

		return true;
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
