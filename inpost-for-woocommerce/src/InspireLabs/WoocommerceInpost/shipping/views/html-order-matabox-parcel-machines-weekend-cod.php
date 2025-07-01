<?php /** @var ShipX_Shipment_Model $shipment */

use InspireLabs\WoocommerceInpost\EasyPack;
use InspireLabs\WoocommerceInpost\EasyPack_Helper;
use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Model;
use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Parcel_Model;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status_service = EasyPack()->get_shipment_status_service();
if ( true === $wrong_api_env ) {

	$internal_data = $shipment->getInternalData();
	$origin_api    = $shipment->getInternalData()->getApiVersion();

	if ( $internal_data->getApiVersion() === $internal_data::API_VERSION_PRODUCTION ) {
		?>
		<span style="font-weight: bold; color: #a00">
			<?php
			esc_html_e(
				'This shipment was created in production API. Change API environment to production to process this shipment',
				'woocommerce-inpost'
			)
			?>
		</span>
		<?php
	}

	if ( $internal_data->getApiVersion() === $internal_data::API_VERSION_SANDBOX ) {
		?>
		<span style="font-weight: bold; color: #a00">
			<?php
			esc_html_e(
				'This shipment was created in sandbox API. Change API environment to sandbox to process this shipment',
				'woocommerce-inpost'
			)
			?>
		</span>
		<?php
	}
	return;
}


$first_parcel      = true;
$shipment_service  = EasyPack()->get_shipment_service();
$parcel_machine_id = Easypack_Helper()->get_locker_id_from_meta( $order_id );

$class             = array( 'wc-enhanced-select' );
$custom_attributes = array( 'style' => 'width:100%;' );
if ( $disabled ) {
	$custom_attributes['disabled'] = 'disabled';
	$class[]                       = 'easypack-disabled';
}
?>

<p>
	<label for="parcel_machine_id">
		<?php esc_html_e( 'Selected parcel locker', 'woocommerce-inpost' ); ?>
	</label>
	<input value="<?php echo esc_attr( $parcel_machine_id ); ?>" type="text"
			class="settings-geowidget" id="parcel_machine_id"
			name="parcel_machine_id"
			data-geowidget_config="<?php echo sanitize_text_field( $geowidget_config ); ?>"
		<?php echo $disabled ? ' disabled ' : ''; ?>
	>
</p>
<p>
	<span style="font-weight: bold">
        <?php esc_html_e( 'Service:', 'woocommerce-inpost' ); ?>
	</span>
	<span>
		<?php
		echo esc_html( $selected_service );
		?>
	</span>
</p>

<p>
	<span style="font-weight: bold">
		<?php esc_html_e( 'Status:', 'woocommerce-inpost' ); ?>
	</span>
	<?php
	if ( $shipment instanceof ShipX_Shipment_Model && ! $additional_package ) {
		$status       = $shipment->getInternalData()->getStatus();
		$status_title = $shipment->getInternalData()->getStatusTitle();
		$status_desc  = $shipment->getInternalData()->getStatusDescription();
		if ( $shipment->isCourier() ) {
			$send_method = 'courier';
		}

		if ( $shipment->isParcelMachine() ) {
			$send_method = 'parcel_machine';
		}
		?>
		<span title="<?php echo esc_attr( $status_desc ); ?>">
			<?php echo esc_html( $status_title ); ?> (<?php echo esc_html( $status ); ?>)
		</span>
</p>

    <?php
	} else {
		esc_html_e( 'Not created yet (new)', 'woocommerce-inpost' );
	}


	if ( ! empty( $shipment instanceof ShipX_Shipment_Model && $shipment->getInternalData()->getTrackingNumber() ) && ! $additional_package ) {
    ?>
	<span style="font-weight: bold">
			<?php esc_html_e( 'Tracking number:', 'woocommerce-inpost' ); ?>
	</span>

	<a target="_blank"
		href="<?php echo esc_url( $shipment_service->getTrackingUrl( $shipment ) ); ?>">
		<?php echo esc_html( $shipment->getInternalData()->getTrackingNumber() ); ?>
	</a>
	<div class="padding-bottom15"></div>
	<?php } ?>

<?php require 'costs/html-order-metabox-costs.php'; ?>

<p>
	<?php esc_html_e( 'Attributes:', 'woocommerce-inpost' ); ?>
	<ul id="easypack_parcels" style="list-style: none">
		<?php /** @var ShipX_Shipment_Parcel_Model $parcel */ ?>
		<?php /** @var ShipX_Shipment_Parcel_Model[] $parcels */ ?>

		<?php foreach ( $parcels as $parcel ) { ?>
			<li>
				<?php if ( $status == 'new' || $additional_package ) { ?>
					<?php
					$params = array(
						'type'        => 'select',
						'options'     => $package_sizes,
						'class'       => array( 'easypack_parcel' ),
						'input_class' => array( 'easypack_parcel' ),
						'label'       => '',
					);

					$saved_meta_data = get_post_meta( $order_id, '_easypack_parcels', true );
					$saved_package_size = $saved_meta_data[0]['package_size'] ?? Easypack_Helper()->get_parcel_size_from_settings($order_id);
					woocommerce_form_field( 'parcel[]', $params, $saved_package_size );
					$cod_amount = $saved_meta_data[0]['cod_amount'] ?? $order->get_total();
					?>

					<?php esc_html_e( 'COD amount: ', 'woocommerce-inpost' ); ?>
					<input class="easypack_cod_amount" type="number" style=""
							value="<?php echo esc_attr( $cod_amount ); ?>"
							placeholder="0.00" step="any" min="0"
							name="cod_amount[]"
                    >

					<?php if ( $status == 'new' && ! $first_parcel ) { ?>
						<button class="button easypack_remove_parcel">
                            <?php esc_html_e( 'Remove', 'woocommerce-inpost' ); ?>
                        </button>
					<?php } ?>
				<?php } else { ?>
					<?php esc_html_e( 'Size', 'woocommerce-inpost' ); ?>:
					<?php echo '<span style="font-size: 16px">'; ?>
					<?php echo esc_html( EasyPack_Helper()->convert_size_to_symbol( $parcel->getTemplate() ) ); ?>
					<?php echo '</span>'; ?>
					<br>
					<?php esc_html_e( 'COD amount', 'woocommerce-inpost' ); ?>: <?php echo esc_html( $shipment->getCod()->getAmount() ); ?>
				<?php } ?>
			</li>
			<?php $first_parcel = false; ?>
		<?php } ?>
	</ul>
</p>


<?php require 'services/html-service-insurance.php'; ?>
<?php require 'html-field-reference.php'; ?>


<?php
$custom_attributes = array( 'style' => 'width:100%;' );
if ( $disabled || $send_method_disabled ) {
	$custom_attributes['disabled'] = 'disabled';
}
$params = array(
	'type'              => 'select',
	'options'           => $send_methods,
	'class'             => array( 'wc-enhanced-select' ),
	'custom_attributes' => $custom_attributes,
	'label'             => __( 'Send method', 'woocommerce-inpost' ),
);

$send_method = get_post_meta( $order_id, '_easypack_send_method', true )
	? get_post_meta( $order_id, '_easypack_send_method', true )
	: $send_method;

woocommerce_form_field( 'easypack_send_method', $params, $send_method );
?>

<p>
	<?php if ( 'new' === $status ) : ?>
		<button id="easypack_send_parcels" class="button button-primary">
			<?php esc_html_e( 'Send parcel', 'woocommerce-inpost' ); ?>
		</button>
	<?php endif; ?>

	<?php require 'html-no-funds-alert.php'; ?>

	<?php
	if ( $shipment instanceof ShipX_Shipment_Model
				&& ! empty(
					$shipment->getInternalData()
									->getTrackingNumber()
				) && ! $additional_package ) :
		?>
		<input id="get_stickers" type="submit" class="button button-primary"
				value="<?php esc_html_e( 'Get sticker', 'woocommerce-inpost' ); ?>">
		<input type="hidden" name="easypack_get_stickers_request"
				id="easypack_get_stickers_request">
		<input type="hidden" name="easypack_parcel"
				value="<?php echo esc_attr( $shipment->getInternalData()->getOrderId() ); ?>">
	<?php endif; ?>

	<span id="easypack_spinner" class="spinner"></span>
</p>

<p id="easypack_error"></p>

<a href="#" download id="easypack_download" target="_blank" hidden></a>

<script type="text/javascript">
    console.log('PWW COD metabox');

	jQuery('#easypack_send_parcels').click(function (e) {

		var metabox = jQuery(this).closest('.postbox');
		var is_additional_package = '<?php echo esc_html( $additional_package ); ?>';

		jQuery(metabox).find('#easypack_error').html('');
		jQuery(this).attr('disabled', true);
		jQuery(metabox).find("#easypack_spinner").addClass("is-active");
		var parcels = [];
		jQuery(metabox).find('select.easypack_parcel').each(function (i) {
			parcels[i] = jQuery(this).val();
		});

		if (!parcels.length ) {
			let alternate_parcels_find = jQuery(metabox).find('#easypack_parcels').find('select').val();
			parcels.push(alternate_parcels_find);
		}

		var insurance_amounts = [];
		jQuery(metabox).find('input.insurance_amount').each(function (i) {
			insurance_amounts[i] = jQuery(this).val();
		});

		var cod_amounts = [];
		jQuery(metabox).find('input.easypack_cod_amount').each(function (i) {
			cod_amounts[i] = jQuery(this).val();
		});
		
		var order_id = '<?php echo esc_attr( $order_id ); ?>';
		
		var data = {
			action: 'easypack',
			easypack_action: 'parcel_machines_weekend_create_package_cod',
			security: easypack_nonce,
			order_id: order_id,
			parcel_machine_id: jQuery(metabox).find('#parcel_machine_id').val(),
			parcels: parcels,
			cod_amounts: cod_amounts,
			send_method: jQuery(metabox).find('#easypack_send_method').val(),
			insurance_amounts: insurance_amounts,
			reference_number: jQuery(metabox).find('#reference_number').val(),
			easypack_additional_package: jQuery(metabox).find('#easypack_additional_package').val()
		};

        console.log('PWW COD data');
        console.log(data);
        //return;

		jQuery.post(ajaxurl, data, function (response) {
			//console.log(response);
			if (response !== 0) {
				response = JSON.parse(response);
				//console.log(response);
				//console.log(response.status);
				if (response.status === 'ok') {
					if( is_additional_package ) {
						let additional_package_data = '';
						if(typeof response.tracking_number != 'undefined' && response.tracking_number !== null) {

							additional_package_data += '<p><span style="font-weight: bold">Usługa: </span>\n' +
								'<span title="">'+ response.service +'</span></p>';


							additional_package_data += '<p><span style="font-weight: bold">Status: </span>\n' +
								'<span title="">'+ response.api_status +'</span></p>';

							additional_package_data += '<p><span style="font-weight: bold">Ref. number: </span>\n' +
								'<span title="">'+ response.ref_number +'</span></p>';

							let tracking_url = 'https://inpost.pl/sledzenie-przesylek?number=' + response.tracking_number;
							let button_text = '<?php echo esc_html__( 'Get sticker for additional package', 'woocommerce-inpost' ); ?>';

							additional_package_data += '<span style="font-weight:bold">Tracking number:</span>' +
								'<br><a target="_blank" ' +
								'href="' + tracking_url + '">'+response.tracking_number+'</a>' +
								'<br>';

							additional_package_data += '<span id="easypack_spinner" class="spinner"></span>';

							additional_package_data += '<input ' +
								'id="get_sticker_additional_now" ' +
								'type="button" ' +
								'data-id="'+ response.inpost_id +'" ' +
								'data-order-id="'+ order_id +'" ' +
								'class="button secondary" value="'+button_text+'">';


						} else {
							additional_package_data += '<p><span style="font-weight: bold">Status: </span>\n' +
								'<span title="">'+ response.api_status +'</span></p>';
						}

						jQuery(metabox).find(".inside").html(additional_package_data);

					} else {
						jQuery(metabox).find(".inside").html(response.content);
					}
					return false;

				} else {
					//alert(response.message);
					jQuery(metabox).find('#easypack_error').html(response.message);
					jQuery(metabox).find('#easypack_error').css('color', '#f00');
				}
			} else {
				//alert('Bad response.');
				jQuery(metabox).find('#easypack_error').html('Invalid response.');
				jQuery(metabox).find('#easypack_error').css('color', '#f00');
			}
			jQuery(metabox).find("#easypack_spinner").removeClass("is-active");
			jQuery(metabox).find('#easypack_send_parcels').attr('disabled', false);
		});
		return false;

	});

</script>

<?php require 'services/html-service-get-label.php'; ?>
<?php require 'services/html-service-view-additional-package.php'; ?>
<?php require 'services/html-service-additional-package.php'; ?>
