<?php
/**
 * @var ShipX_Shipment_Model $shipment
 * @var int $order_id
 * @var WC_Order $order - The order object
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

use InspireLabs\WoocommerceInpost\EasyPack;
use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Model;
use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Parcel_Model; ?>

<div class="easypack_first_parcel_wrapper" style="display: block;">
	<input type="hidden" id="easypack_inpost_pl_wc_order_id" value="<?php echo esc_attr( $order_id ); ?>">
	<?php
	$selected_option           = null;
	$paczkomat_standard_option = '';
	$courier_standard_option   = '';

	$class             = array( 'easypack_first_parcel_selector' );
	$custom_attributes = array();
	if ( $shipment instanceof ShipX_Shipment_Model ) {
		$custom_attributes['disabled'] = 'disabled';
		$class[]                       = 'easypack-disabled';
	}

	$saved_meta_instance_id = Easypack_Helper()->get_woo_order_meta( $order_id, '_inpost_pl_metabox_shipping_method_instance_id' );
	if ( ! empty( $saved_meta_instance_id ) ) {
		$selected_option = $saved_meta_instance_id;
	}

	$animation = EasyPack()->getPluginImages() . 'inpost-pl-loader.gif';
	$logo      = EasyPack()->getPluginImages() . 'logo/small/white.png';

	$inpost_methods = EasyPack_Helper()->get_inpost_methods();

	wp_localize_script( 'easypack-admin-geowidget-settings', 'inpost_methods', $inpost_methods );

	$active_methods = array();
	foreach ( $inpost_methods as $instance_id => $inpost_method ) {
		if ( 'yes' === $inpost_method['active'] ) {
			$active_methods[ $instance_id ] = $inpost_method['user_title'];

			if ( 'easypack_parcel_machines' === $inpost_method['method_title'] ) {
				$paczkomat_standard_option = $instance_id;
			}
			if ( 'easypack_shipping_courier' === $inpost_method['method_title'] ) {
				$courier_standard_option = $instance_id;
			}
		}
	}

	$params = array(
		'type'              => 'select',
		'options'           => $active_methods,
		'class'             => $class,
		'input_class'       => array( 'easypack_first_parcel_selector' ),
		'label'             => '',
		'custom_attributes' => $custom_attributes,
	);
	$order  = wc_get_order( $order_id );


	if ( isset( $_POST['easypack_change_first_shipment_method_id'] ) ) {
		$selected_option = sanitize_text_field( wp_unslash( $_POST['easypack_change_first_shipment_method_id'] ) );

	} if ( isset( $_POST['easypack_additional_package_method_id'] ) ) {
		$selected_option = sanitize_text_field( wp_unslash( $_POST['easypack_additional_package_method_id'] ) );

	} else {

		if ( ! $selected_option ) {
			$shipping_methods = $order->get_shipping_methods();
			if ( ! empty( $shipping_methods ) && is_array( $shipping_methods ) ) {
				foreach ( $shipping_methods as $method ) {
					if ( is_object( $method ) ) {
						$selected_option = $method->get_instance_id();
					}
				}
			}

			if ( ! key_exists( $selected_option, $inpost_methods ) ) {
				$selected_option = $paczkomat_standard_option;
			}
		}
	}

	woocommerce_form_field( 'easypack_change_first_parcel', $params, $selected_option );
	?>
	<span id="easypack_change_first_parcel_spinner" style="text-align: center; margin-top: 15px; display: none;">
		<img class="easypack_change_first_parcel_spinner" src="<?php echo esc_url( $animation ); ?>" alt="inpost change first parcel preloader"/>
	</span>

	<script type="text/javascript">
		jQuery('#easypack_change_first_parcel').on( 'change', function (e) {

			let this_metabox = jQuery(this).closest('.postbox ');
			let selected_inpost_method = jQuery(this).val();
			console.log('selected_inpost_method');
			console.log(selected_inpost_method);

			let metabox_icon = "<?php echo esc_url( $logo ); ?>";
			if (inpost_methods &&
				selected_inpost_method !== undefined && selected_inpost_method !== null &&
				inpost_methods[selected_inpost_method] &&
				inpost_methods[selected_inpost_method].inpost_icon
			) {
				metabox_icon = inpost_methods[selected_inpost_method].inpost_icon;
			}

			jQuery(this_metabox).find('#easypack_error').html('');
			jQuery(this).attr('disabled', true);
			jQuery(this_metabox).css("opacity", "0.5");
			jQuery(this_metabox).find("#easypack_change_first_parcel_spinner").css("display", "block");
			jQuery(this_metabox).find("#easypack_add_additional_parcel").attr('disabled', true);


			var data = {
				action: 'easypack',
				easypack_action: 'change_first_package',
				security: easypack_nonce,
				easypack_change_first_shipment_method_id: selected_inpost_method,
				order_id: <?php echo esc_attr( $order_id ); ?>
			};

			jQuery.post(ajaxurl, data, function (response) {
				if (response !== 0) {
					response = JSON.parse(response);
					if (response.status === 'ok') {

						let out = '<div id="easypack_shipment_changed" class="postbox">';
						out += '<div class="postbox-header"><h2 class="hndle ui-sortable-handle easypack_shipment_changed">' +
							'InPost' +
							'<img id="easypack_shipment_changed_logo" style="height:22px; float:right;" src="<?php echo esc_url( $logo ); ?>"></h2>\n' +
							'</div>';

						out += '<div class="inside easypack-shipment-changed-metabox">';
						out += response.content;
						out += '<input type="hidden" value="true" id="easypack_shipment_changed">';
						out += '</div></div>';

						jQuery(this_metabox).replaceWith(out);
						// maybe replace icon.
						jQuery("#easypack_shipment_changed_logo").attr("src", metabox_icon );

						return false;
					} else {
						jQuery(this_metabox).find('#easypack_error').html(response.message);
						jQuery(this_metabox).find("#easypack_add_additional_parcel").attr('disabled', false);
					}
				} else {
					jQuery(this_metabox).find('#easypack_error').html('Invalid response.');
					jQuery(this_metabox).find("#easypack_add_additional_parcel").attr('disabled', false);
				}

				jQuery(this_metabox).find("#easypack_change_first_parcel_spinner").css("display", "none");
				jQuery(this_metabox).css("opacity", "1");
				jQuery(this_metabox).find('#easypack_send_parcels').attr('disabled', false);

			});
			return false;

		});

	</script>
</div>
