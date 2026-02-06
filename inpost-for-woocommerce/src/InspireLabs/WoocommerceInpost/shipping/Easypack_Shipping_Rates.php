<?php

namespace InspireLabs\WoocommerceInpost\shipping;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

use InspireLabs\WoocommerceInpost\EasyPack_Helper;
use WC_Shipping_Method;
use WC_Shipping_Rate;

class Easypack_Shipping_Rates {


	public function init() {

		add_action(
			'woocommerce_after_shipping_rate',
			function ( $method, $index ) {

				/**
				 * @var WC_Shipping_Rate $method
				 */

				$fs_method_name = null;

				if ( EasyPack_Helper()->is_flexible_shipping_activated() ) {

					if ( strpos( $method->get_method_id(), 'flexible_shipping_single' ) !== false ) {
						$fs_method_name = EasyPack_Helper()->get_method_linked_to_fs_by_instance_id( $method->get_instance_id() );
					}
				}

				if ( 0 === strpos( $method->get_method_id(), 'easypack_' )
				|| ( isset( $fs_method_name ) && 0 === strpos( $fs_method_name, 'easypack_' ) ) ) {

					$delivery_terms = '';

					$meta = $method->get_meta_data();
					if ( is_array( $meta ) ) {
						if ( isset( $meta['logo'] ) ) {
							$custom_logo = $meta['logo'];
						}

						if ( ! empty( $meta['delivery_terms'] ) ) {
							$delivery_terms = $meta['delivery_terms'];
						}
					}

					$img = '<span class="inpost_pl-shipping-method-meta-wrap">';

					if ( empty( $custom_logo ) ) {

						$method_name = EasyPack_Helper()->validate_method_name( $method->get_method_id() );

						if ( 'easypack_parcel_machines_weekend' === $method_name
						|| 'easypack_parcel_machines_weekend_cod' === $method_name
						|| ( isset( $fs_method_name ) && 'easypack_parcel_machines_weekend' === $fs_method_name )
						|| ( isset( $fs_method_name ) && 'easypack_parcel_machines_weekend_cod' === $fs_method_name )
						) {
							$img .= '<span class="inpost_pl_shipping_meta easypack-weekend-shipping-method-logo"><img style="" src="'
								. EasyPack()->getPluginImages()
								. 'logo/inpost-paczka-w-weekend.png" /></span>';

						} elseif ( 'easypack_parcel_machines' === $method_name || 'easypack_parcel_machines_cod' === $method_name
						|| ( isset( $fs_method_name ) && 'easypack_parcel_machines' === $fs_method_name )
						|| ( isset( $fs_method_name ) && 'easypack_parcel_machines_cod' === $fs_method_name )
						) {

							$img .= '<span class="inpost_pl_shipping_meta easypack-shipping-method-logo"><img style="" src="'
								. EasyPack()->getPluginImages()
								. 'logo/inpost-paczkomat-logo.png" /></span>';

						} else {

							$img .= '<span class="inpost_pl_shipping_meta easypack-shipping-method-logo"><img style="" src="'
							. EasyPack()->getPluginImages()
							. 'logo/inpost-kurier-logo.png" /></span>';
						}
					} else {
						$img .= '<span class="inpost_pl_shipping_meta easypack-custom-shipping-method-logo"><img style="" src="'
						. esc_url( $custom_logo ) . '" /></span>';
					}

					if ( ! empty( $delivery_terms ) ) {
						$img .= '<span class="inpost_pl_shipping_meta" id="inpost_pl_delivery_terms">' . esc_html( $delivery_terms ) . '</span>';
					}

					$img .= '</span>';

					echo wp_kses_post( $img );

				}
			},
			10,
			2
		);
	}
}
