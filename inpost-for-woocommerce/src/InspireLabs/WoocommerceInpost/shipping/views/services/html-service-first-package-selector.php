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
	$easypack_selected_option           = null;
	$easypack_paczkomat_standard_option = '';
	$easypack_courier_standard_option   = '';

	$easypack_class             = array( 'easypack_first_parcel_selector' );
	$easypack_custom_attributes = array();
	if ( $shipment instanceof ShipX_Shipment_Model ) {
		$easypack_custom_attributes['disabled'] = 'disabled';
		$easypack_class[]                       = 'easypack-disabled';
	}

	$easypack_saved_meta_instance_id = Easypack_Helper()->get_woo_order_meta( $order_id, '_inpost_pl_metabox_shipping_method_instance_id' );
	if ( ! empty( $easypack_saved_meta_instance_id ) ) {
		$easypack_selected_option = $easypack_saved_meta_instance_id;
	}

	$easypack_animation = EasyPack()->getPluginImages() . 'inpost-pl-loader.gif';
	$easypack_logo      = EasyPack()->getPluginImages() . 'logo/small/white.png';

	$inpost_methods = EasyPack_Helper()->get_inpost_methods();

	wp_localize_script( 'easypack-admin-geowidget-settings', 'inpost_methods', $inpost_methods );

	$easypack_active_methods = array();
	foreach ( $inpost_methods as $easypack_instance_id => $inpost_method ) {
		if ( 'yes' === $inpost_method['active'] ) {
			$easypack_active_methods[ $easypack_instance_id ] = $inpost_method['user_title'];

			if ( 'easypack_parcel_machines' === $inpost_method['method_title'] ) {
				$easypack_paczkomat_standard_option = $easypack_instance_id;
			}
			if ( 'easypack_shipping_courier' === $inpost_method['method_title'] ) {
				$easypack_courier_standard_option = $easypack_instance_id;
			}
		}
	}

	$easypack_params = array(
		'type'              => 'select',
		'options'           => $easypack_active_methods,
		'class'             => $easypack_class,
		'input_class'       => array( 'easypack_first_parcel_selector' ),
		'label'             => '',
		'custom_attributes' => $easypack_custom_attributes,
	);
	$order           = wc_get_order( $order_id );


	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in EasyPack_AJAX::ajax_easypack() before metabox HTML re-render.
	if ( isset( $_POST['easypack_change_first_shipment_method_id'] ) ) {
		$easypack_selected_option = sanitize_text_field( wp_unslash( $_POST['easypack_change_first_shipment_method_id'] ) );
	}

	if ( isset( $_POST['easypack_additional_package_method_id'] ) ) {
		$easypack_selected_option = sanitize_text_field( wp_unslash( $_POST['easypack_additional_package_method_id'] ) );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( ! $easypack_selected_option ) {
		$easypack_shipping_methods = $order->get_shipping_methods();
		if ( ! empty( $easypack_shipping_methods ) && is_array( $easypack_shipping_methods ) ) {
			foreach ( $easypack_shipping_methods as $easypack_method ) {
				if ( is_object( $easypack_method ) ) {
					$easypack_selected_option = $easypack_method->get_instance_id();
				}
			}
		}

		if ( ! key_exists( $easypack_selected_option, $inpost_methods ) ) {
			$easypack_selected_option = $easypack_paczkomat_standard_option;
		}
	}

	woocommerce_form_field( 'easypack_change_first_parcel', $easypack_params, $easypack_selected_option );
	?>
	<span id="easypack_change_first_parcel_spinner" style="text-align: center; margin-top: 15px; display: none;">
		<img class="easypack_change_first_parcel_spinner" src="<?php echo esc_url( $easypack_animation ); ?>" alt="inpost change first parcel preloader"/>
	</span>

	<script type="text/javascript">
		jQuery('#easypack_change_first_parcel').on( 'change', function (e) {

			let this_metabox = jQuery(this).closest('.postbox ');
			let selected_inpost_method = jQuery(this).val();
			console.log('selected_inpost_method');
			console.log(selected_inpost_method);

			let metabox_icon = "<?php echo esc_url( $easypack_logo ); ?>";
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
							'<img id="easypack_shipment_changed_logo" style="height:22px; float:right;" src="<?php echo esc_url( $easypack_logo ); ?>"></h2>\n' +
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
