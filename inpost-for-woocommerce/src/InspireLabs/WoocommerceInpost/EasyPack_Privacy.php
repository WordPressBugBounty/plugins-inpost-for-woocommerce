<?php
/**
 * GDPR / privacy: personal data eraser and exporter for InPost PL order data.
 *
 * @package InspireLabs\WoocommerceInpost
 */

namespace InspireLabs\WoocommerceInpost;

use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Model;
use WC_Order;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Erases / exports InPost PL order data together with WooCommerce personal data tools.
 *
 * - Eraser: same principle as WooCommerce core (respects "Remove personal data from orders on request"):
 *   removes shipment data holding receiver PII (shipment object, API request, additional packages),
 *   tracking numbers and the chosen pick-up point. Also hooked into
 *   `woocommerce_privacy_remove_order_personal_data`, so WooCommerce's own order anonymization
 *   (erasure requests and order data retention) clears them too.
 * - Exporter: only InPost-specific data (pick-up point, tracking, return tracking, shipment ID/status);
 *   name, address, phone etc. are already exported by WooCommerce from the order itself.
 */
class EasyPack_Privacy {

	/**
	 * Orders processed per eraser/exporter page.
	 */
	const PER_PAGE = 10;

	/**
	 * Order meta removed on erase.
	 *
	 * Shipment object / create args / additional packages contain the receiver's name, e-mail, phone and address.
	 * Status keys are removed as well, so the order does not stay in the Shipment Manager without its shipment data.
	 *
	 * @var string[]
	 */
	const ERASE_META_KEYS = array(
		'_shipx_shipment_object',
		'_easypack_parcel_create_args',
		'_easypack_additional_packages',
		'_easypack_parcel_tracking',
		'_easypack_return_tracking',
		'_parcel_machine_id',
		'_parcel_machine_desc',
		'_easypack_status',
		'easypack_webhook_status',
		'_easypack_dispatched',
	);

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'woocommerce_privacy_remove_order_personal_data', array( $this, 'remove_order_personal_data' ), 10, 1 );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
	}

	/**
	 * @param array $erasers Registered erasers.
	 * @return array
	 */
	public function register_eraser( $erasers ) {
		$erasers['inpost-pl-order-data'] = array(
			'eraser_friendly_name' => __( 'InPost PL order data', 'inpost-for-woocommerce' ),
			'callback'             => array( $this, 'eraser' ),
		);

		return $erasers;
	}

	/**
	 * @param array $exporters Registered exporters.
	 * @return array
	 */
	public function register_exporter( $exporters ) {
		$exporters['inpost-pl-order-data'] = array(
			'exporter_friendly_name' => __( 'InPost PL order data', 'inpost-for-woocommerce' ),
			'callback'               => array( $this, 'exporter' ),
		);

		return $exporters;
	}

	/**
	 * Orders of a customer (by billing e-mail and, if registered, user ID), one page.
	 *
	 * @param string $email_address E-mail.
	 * @param int    $page          1-based page.
	 * @return WC_Order[]
	 */
	private function get_orders( $email_address, $page ) {
		if ( empty( $email_address ) || ! is_email( $email_address ) ) {
			return array();
		}

		$query = array(
			'limit'    => self::PER_PAGE,
			'page'     => max( 1, (int) $page ),
			'customer' => array( $email_address ),
		);

		$user = get_user_by( 'email', $email_address );
		if ( $user instanceof \WP_User ) {
			$query['customer'][] = (int) $user->ID;
		}

		$orders = wc_get_orders( $query );

		return array_values(
			array_filter(
				is_array( $orders ) ? $orders : array(),
				function ( $order ) {
					return $order instanceof WC_Order;
				}
			)
		);
	}

	/**
	 * WP personal data eraser callback.
	 *
	 * @param string $email_address E-mail.
	 * @param int    $page          1-based page.
	 * @return array
	 */
	public function eraser( $email_address, $page = 1 ) {
		$response = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);

		$orders = $this->get_orders( $email_address, $page );
		if ( empty( $orders ) ) {
			return $response;
		}

		// Same switch WooCommerce core uses for order data.
		$erasure_enabled = wc_string_to_bool( get_option( 'woocommerce_erasure_request_removes_order_data', 'no' ) );

		foreach ( $orders as $order ) {
			if ( ! apply_filters( 'woocommerce_privacy_erase_order_personal_data', $erasure_enabled, $order ) ) {
				if ( $this->order_has_inpost_data( $order ) ) {
					$response['items_retained'] = true;
					$response['messages'][]     = sprintf(
						/* translators: %s: order number */
						__( 'InPost PL data within order %s has been retained.', 'inpost-for-woocommerce' ),
						$order->get_order_number()
					);
				}
				continue;
			}

			if ( $this->remove_order_personal_data( $order ) ) {
				$response['items_removed'] = true;
				$response['messages'][]    = sprintf(
					/* translators: %s: order number */
					__( 'Removed InPost PL data from order %s.', 'inpost-for-woocommerce' ),
					$order->get_order_number()
				);
			}
		}

		// The e-mail is not changed by this eraser, so plain paging stays stable.
		$response['done'] = count( $orders ) < self::PER_PAGE;

		return $response;
	}

	/**
	 * Remove InPost PL data from an order (also used by WooCommerce's own anonymization).
	 *
	 * @param WC_Order $order Order.
	 * @return bool True if anything was removed.
	 */
	public function remove_order_personal_data( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		$removed = false;
		foreach ( self::ERASE_META_KEYS as $key ) {
			if ( $order->meta_exists( $key ) ) {
				$order->delete_meta_data( $key );
				$removed = true;
			}
		}

		if ( $removed ) {
			$order->save();
		}

		return $removed;
	}

	/**
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	private function order_has_inpost_data( $order ) {
		foreach ( self::ERASE_META_KEYS as $key ) {
			if ( $order->meta_exists( $key ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * WP personal data exporter callback: InPost-specific data only.
	 *
	 * @param string $email_address E-mail.
	 * @param int    $page          1-based page.
	 * @return array
	 */
	public function exporter( $email_address, $page = 1 ) {
		$orders = $this->get_orders( $email_address, $page );
		$items  = array();

		foreach ( $orders as $order ) {
			$data = $this->get_export_data( $order );
			if ( empty( $data ) ) {
				continue;
			}

			$items[] = array(
				'group_id'          => 'inpost-pl-orders',
				'group_label'       => __( 'InPost PL shipments', 'inpost-for-woocommerce' ),
				'group_description' => __( 'Pick-up point and shipment data of your orders delivered by InPost.', 'inpost-for-woocommerce' ),
				'item_id'           => 'inpost-pl-order-' . $order->get_id(),
				'data'              => $data,
			);
		}

		return array(
			'data' => $items,
			'done' => count( $orders ) < self::PER_PAGE,
		);
	}

	/**
	 * @param WC_Order $order Order.
	 * @return array<int, array{name: string, value: string}>
	 */
	private function get_export_data( $order ) {
		$data = array();

		$add = function ( $name, $value ) use ( &$data ) {
			$value = is_scalar( $value ) ? trim( (string) $value ) : '';
			if ( '' !== $value ) {
				$data[] = array(
					'name'  => $name,
					'value' => $value,
				);
			}
		};

		$shipment  = $order->get_meta( '_shipx_shipment_object' );
		$inpost_id = '';
		if ( $shipment instanceof ShipX_Shipment_Model && $shipment->getInternalData() ) {
			$inpost_id = (string) $shipment->getInternalData()->getInpostId();
		}

		$fields = array(
			array( __( 'Pick-up point', 'inpost-for-woocommerce' ), $order->get_meta( '_parcel_machine_id' ) ),
			array( __( 'Pick-up point description', 'inpost-for-woocommerce' ), $order->get_meta( '_parcel_machine_desc' ) ),
			array( __( 'Tracking number', 'inpost-for-woocommerce' ), $order->get_meta( '_easypack_parcel_tracking' ) ),
			array( __( 'Return tracking number', 'inpost-for-woocommerce' ), $order->get_meta( '_easypack_return_tracking' ) ),
			array( __( 'InPost shipment ID', 'inpost-for-woocommerce' ), $inpost_id ),
			array( __( 'Shipment status', 'inpost-for-woocommerce' ), $order->get_meta( '_easypack_status' ) ),
		);

		foreach ( $fields as $field ) {
			$add( $field[0], $field[1] );
		}

		if ( ! empty( $data ) ) {
			array_unshift(
				$data,
				array(
					'name'  => __( 'Order number', 'inpost-for-woocommerce' ),
					'value' => (string) $order->get_order_number(),
				)
			);
		}

		return $data;
	}
}
