<?php /** @var ShipX_Shipment_Model $shipment */

use InspireLabs\WoocommerceInpost\EasyPack;
use InspireLabs\WoocommerceInpost\EasyPack_Helper;
use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Model;
use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Parcel_Model; ?>
<?php /** @var string $wp_ajax_action_create */ ?>

<?php
if (!defined('ABSPATH')) {
    exit;
} ?>

<?php
$status_service = EasyPack()->get_shipment_status_service();
?>

<?php if (true === $wrong_api_env): ?>
    <?php
    $internal_data = $shipment->getInternalData();
    $origin_api = $shipment->getInternalData()->getApiVersion();
    if ($internal_data->getApiVersion()
        === $internal_data::API_VERSION_PRODUCTION
    ):?>

    <?php endif; ?>

    <?php if ($internal_data->getApiVersion()
        === $internal_data::API_VERSION_PRODUCTION
    ): ?>
        <span style="font-weight: bold; color: #a00">
            <?php _e('This shipment was created in production API. Change API environment to production to process this shipment',
                'woocommerce-inpost' ) ?>
        </span>

    <?php endif; ?>

    <?php if ($internal_data->getApiVersion()
        === $internal_data::API_VERSION_SANDBOX
    ): ?>
        <span style="font-weight: bold; color: #a00">
            <?php _e('This shipment was created in sandbox API. Change API environment to sandbox to process this shipment',
                'woocommerce-inpost' ) ?>
        </span>
    <?php endif; ?>
    <?php return; ?>
<?php endif; ?>

<?php $first_parcel = true;
$shipment_service = EasyPack()->get_shipment_service();
?>

<?php
$class = ['wc-enhanced-select'];
$custom_attributes = ['style' => 'width:100%;'];
if ($disabled) {
    $custom_attributes['disabled'] = 'disabled';
    $class[] = 'easypack-disabled';
}

?>

<?php /** @var ShipX_Shipment_Model $shipment */ ?>
<?php /** @var WC_Order $order */ ?>

<p>
    <span style="font-weight: bold"><?php _e('Service:', 'woocommerce-inpost' ) ?>
    </span>
    <span>
        <?php echo esc_html( $selected_service );
        ?>
    </span>
</p>

<p><span style="font-weight: bold"><?php _e('Status:', 'woocommerce-inpost' ) ?> </span>
    <?php if ($shipment instanceof ShipX_Shipment_Model && ! $additional_package ): ?>
    <?php $status = $shipment->getInternalData()->getStatus() ?>
    <?php $status_title = $shipment->getInternalData()->getStatusTitle() ?>
    <?php $status_desc = $shipment->getInternalData()->getStatusDescription() ?>
    <span title="<?php echo esc_attr( $status_desc ); ?>"><?php echo esc_html( $status_title ); ?>
        (<?php echo esc_html( $status ); ?>)</span></p>
<?php if ($shipment->isCourier()) {
    $send_method = 'courier';
}

if ($shipment->isParcelMachine()) {
    $send_method = 'parcel_machine';
}
?>
<?php else: ?>
    <?php _e('Not created yet (new)', 'woocommerce-inpost') ?>
<?php endif ?>

<?php if (!empty($shipment instanceof ShipX_Shipment_Model
    && $shipment->getInternalData()->getTrackingNumber()) && ! $additional_package
): ?>
    <span style="font-weight: bold">
            <?php _e('Tracking number:', 'woocommerce-inpost') ?>
    </span>

    <a target="_blank"
       href="<?php echo esc_url( $shipment_service->getTrackingUrl($shipment) ); ?>">
        <?php echo esc_html( $shipment->getInternalData()->getTrackingNumber() ); ?>
    </a>
    <div class="padding-bottom15"></div>
<?php endif ?>

<?php include('costs/html-order-metabox-costs.php'); ?>

<div>
	<?php _e('Attributes:', 'woocommerce-inpost'); ?>

	
	<?php /** @var ShipX_Shipment_Parcel_Model $parcel */ ?>
	<?php /** @var ShipX_Shipment_Parcel_Model[] $parcels */ ?>
	<?php 
	$wielopak = false;
	if( is_array($parcels) && ( count($parcels) > 1) ) {
		$wielopak = true;
	}
	if ( $wielopak && $status !== 'new' && ! $additional_package ) : ?>
		<span id="easypack_wielopak_label">
			<?php echo esc_html__('Wielopak', 'woocommerce-inpost'); ?>
		</span>
	<?php endif;
	foreach ($parcels as $parcel) : ?>
		<?php if ( $status == 'new' || $additional_package ) : ?>
			<p>
                <select id="easypack_send_method_many_qty">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                    <option value="8">8</option>
                    <option value="9">9</option>
                    <option value="10">10</option>
                    <option value="11">11</option>
                </select>
				<label for="easypack_send_method_many">					
					<input name="easypack_send_method_many" id="easypack_send_method_many" type="checkbox" class="" value="1">
					<?php echo esc_html__('Send as several packages', 'woocommerce-inpost'); ?>
				</label>
			</p>
			<ul id="easypack_parcels" class="easypack_courier_std" style="list-style:none">
				<li>
					<?php
					$length = [
						'type' => 'number',
						'class' => ['easypack_parcel'],
						'input_class' => ['easypack_parcel'],
						'label' => __('Length:', 'woocommerce-inpost')
						. ' (' . $parcel->getDimensions()->getUnit() . ')',
						'required' => true,
					];
					$width = [
						'type' => 'number',
						'class' => ['easypack_parcel'],
						'input_class' => ['easypack_parcel'],
						'label' => __('Width:', 'woocommerce-inpost')
							. ' (' . $parcel->getDimensions()->getUnit() . ')',
						'required' => true,
					];
					$height = [
						'type' => 'number',
						'class' => ['easypack_parcel'],
						'input_class' => ['easypack_parcel'],
						'label' => __('Height:', 'woocommerce-inpost')
							. ' (' . $parcel->getDimensions()->getUnit() . ')',
						'required' => true,
					]; ?>

					<?php // Parcel dimensions block for courier shipments ?>
					<?php include('html-courier-parcel-dimensions.php'); ?>

					<?php if ($status == 'new' && !$first_parcel) : ?>
						<button class="button easypack_remove_parcel"><?php _e('Remove', 'woocommerce-inpost' ); ?></button>
					<?php endif; ?>
				</li></ul>
		<?php else : ?>
			<ul id="easypack_parcels" class="easypack_courier_std" style="list-style:none">
				
				<li>
					<?php _e('Length', 'woocommerce-inpost' ); ?>: <?php echo esc_html( $parcel->getDimensions()->getLength() ); ?>

					<?php echo esc_html( $parcel->getDimensions()->getUnit() ); ?>
					<br>
					<?php _e('Width', 'woocommerce-inpost' ); ?>: <?php echo esc_html( $parcel->getDimensions()->getWidth() ); ?>

					<?php echo esc_html( $parcel->getDimensions()->getUnit() ); ?>
					<br>
					<?php _e('Height', 'woocommerce-inpost' ); ?>: <?php echo esc_html( $parcel->getDimensions()->getHeight() ); ?>

					<?php echo esc_html( $parcel->getDimensions()->getUnit() ); ?>
					<br>
					<?php _e('Non standard', 'woocommerce-inpost' ); ?>: <?php echo $parcel->is_non_standard() === true
						? __('yes', 'woocommerce-inpost' )
						: __('no', 'woocommerce-inpost' ); ?>
					<br>
					<?php _e('Weight', 'woocommerce-inpost' ); ?>: <?php echo esc_html( $parcel->getWeight()->getAmount() ); ?>

					<?php echo esc_html( $parcel->getWeight()->getUnit() ); ?>
					</li></ul>
		<?php endif; ?>
		<?php $first_parcel = false; ?>
	<?php endforeach; ?>
</div>


<?php include('services/html-service-insurance.php'); ?>
<?php include('html-field-reference.php'); ?>
<?php include('services/html-service-sms.php'); ?>


<?php
$custom_attributes = ['style' => 'width:100%;'];
if ($disabled || $send_method_disabled) {
    $custom_attributes['disabled'] = 'disabled';
}
$params = [
    'type' => 'select',
    'options' => $send_methods,
    'class' => ['wc-enhanced-select'],
    'custom_attributes' => $custom_attributes,
    'label' => __('Send method', 'woocommerce-inpost'),
];

$send_method = get_post_meta( $order_id, '_easypack_send_method', true )
    ? get_post_meta( $order_id, '_easypack_send_method', true )
    : $send_method;

woocommerce_form_field('easypack_send_method', $params, $send_method);
?>

<p>
    <?php if ($status == 'new') : ?>
        <button id="easypack_send_parcels"
                class="button button-primary"><?php _e('Send parcel', 'woocommerce-inpost' ); ?></button>
    <?php endif; ?>

    <?php include( 'html-no-funds-alert.php' ); ?>


    <?php if ( $shipment instanceof ShipX_Shipment_Model
        && !empty($shipment->getInternalData()->getTrackingNumber()) && ! $additional_package ) : ?>
        <input id="get_stickers" type="submit" class="button button-primary"
               value="<?php _e('Get sticker', 'woocommerce-inpost'); ?>">
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
    document.addEventListener('click', function (e) {
        e = e || window.event;
        let target = e.target || e.srcElement;
        if ( target.classList.contains( 'easypack-courier-repeat-block-btn-close' ) ) {
			
			let repeat_block = target.closest('.easypack-courier-repeat-block');			
			repeat_block.remove();
			let repeat_blocks_left = document.querySelectorAll('.easypack-courier-repeat-block').length;
            if( repeat_blocks_left === 1 ) {
                jQuery('#easypack_send_method_many').prop('checked', false);
                jQuery('.easypack-courier-repeat-block').each(function(ind, elem) {
                    jQuery(elem).removeClass('divided');
                    if(ind > 0) {
                        jQuery(elem).remove();
                    }
					jQuery('.easypack-courier-repeat-block-title').each(function(ind, elem) {
						jQuery(elem).removeClass('divided');
					});					
                });
            }

            let order_id = '<?php echo esc_attr( $order_id ); ?>';
            let set_limit = jQuery('#easypack_send_method_many_qty').val();
            jQuery('.easypack-courier-repeat-block-title').each(function(ind, elem) {
                jQuery(elem).addClass('divided');
                let num = ind + 1;
                let divided_parcel_id = '#' + order_id + ' ' + num + '/' + set_limit;
                jQuery(elem).find('.easypack-repeat-block-title').val(divided_parcel_id);
            });
        }

        if (target.hasAttribute('id') && target.getAttribute('id') == 'easypack_send_method_many') {
            let repeat = jQuery('.easypack-courier-repeat-block');
            if( jQuery(target).is(':checked') ) {
				//var limit = 4;
                let set_limit = +jQuery('#easypack_send_method_many_qty').val();
                let limit = set_limit-1;
                let repeat_blocks_qty = document.querySelectorAll('.easypack-courier-repeat-block').length;

                while(repeat_blocks_qty <= limit) {
					jQuery(repeat).clone().insertAfter(repeat);
                    repeat_blocks_qty++;
				}
                
                jQuery('.easypack-courier-repeat-block-btn-close').each(function(ind, elem) {
                    if(ind > 0) {
                        jQuery(elem).addClass('divided');
                    }
                });
                let order_id = '<?php echo esc_attr( $order_id ); ?>';
                jQuery('.easypack-courier-repeat-block-title').each(function(ind, elem) {
                    jQuery(elem).addClass('divided');
                    let num = ind + 1;
                    let divided_parcel_id = '#' + order_id + ' ' + num + '/' + set_limit;
                    jQuery(elem).find('.easypack-repeat-block-title').val(divided_parcel_id);
                });
                jQuery('.easypack-courier-repeat-block').each(function(ind, elem) {
                    jQuery(elem).addClass('divided');
                });

            } else {
                jQuery('.easypack-courier-repeat-block-title').each(function(ind, elem) {
                    jQuery(elem).removeClass('divided');
                });
                jQuery('.easypack-courier-repeat-block').each(function(ind, elem) {
                    jQuery(elem).removeClass('divided');
                    if(ind > 0) {
                        jQuery(elem).remove();
                    }
                });
            }
        }
    });

    jQuery('#easypack_send_parcels').click(function (e) {
        let metabox = jQuery(this).closest('.postbox');
        let is_additional_package = '<?php echo esc_html( $additional_package ); ?>';
        jQuery(metabox).find('#easypack_error').html('');
        jQuery(this).attr('disabled', true);
        jQuery(metabox).find("#easypack_spinner").addClass("is-active");
        let parcels = [];
        jQuery(metabox).find('select.easypack_parcel').each(function (i) {
            parcels[i] = jQuery(this).val();
        });

        if (!parcels.length ) {
            let alternate_parcels_find = jQuery(metabox).find('#easypack_parcels').find('select').val();
            parcels.push(alternate_parcels_find);
        }

        let insurance_amounts = [];
        jQuery(metabox).find('input.insurance_amount').each(function (i) {
            insurance_amounts[i] = jQuery(this).val();
        });
        let order_id = '<?php echo esc_attr( $order_id ); ?>';
        let data = {};
        if( jQuery('#easypack_send_method_many').is(':checked') ) {
            let wielopak = [];
            jQuery('.easypack-courier-repeat-block').each(function(ind, elem) {
                wielopak.push( {
                    weight: {
                        unit: 'kg',
                        amount: jQuery(elem).find('#parcel_weight').val()
                    },
                    is_non_standard: jQuery(elem).find('#parcel_non_standard').val(),
                    id: jQuery(elem).find('.easypack-repeat-block-title').val(),
                    dimensions: {
                        unit: 'mm',
                        length: jQuery(elem).find('#parcel_length').val(),
                        width: jQuery(elem).find('#parcel_width').val(),
                        height: jQuery(elem).find('#parcel_height').val(),
                    }
                });
            });

            data = {
                action: 'easypack',
                easypack_action: '<?php echo sanitize_text_field($wp_ajax_action_create); ?>',
                security: easypack_nonce,
                order_id: order_id,
                parcel_mode: 'wielopaki',
                parcels : wielopak,
                send_method: jQuery(metabox).find('#easypack_send_method').val(),
                insurance_amounts: insurance_amounts,
                easypack_sms: jQuery(metabox).find('#easypack_sms_enabled').is(':checked'),
                easypack_email: jQuery(metabox).find('#easypack_email_enabled').is(':checked'),
                reference_number: jQuery(metabox).find('#reference_number').val(),
                easypack_additional_package: jQuery(metabox).find('#easypack_additional_package').val()
            };

        } else {

            data = {
                action: 'easypack',
                easypack_action: '<?php echo sanitize_text_field($wp_ajax_action_create); ?>',
                security: easypack_nonce,
                order_id: order_id,
                parcel_machine_id: jQuery(metabox).find('#parcel_machine_id').val(),
                parcel_length: jQuery(metabox).find('#parcel_length').val(),
                parcel_width: jQuery(metabox).find('#parcel_width').val(),
                parcel_height: jQuery(metabox).find('#parcel_height').val(),
                parcel_weight: jQuery(metabox).find('#parcel_weight').val(),
                parcel_non_standard: jQuery(metabox).find('#parcel_non_standard').val(),
                send_method: jQuery(metabox).find('#easypack_send_method').val(),
                insurance_amounts: insurance_amounts,
                easypack_sms: jQuery(metabox).find('#easypack_sms_enabled').is(':checked'),
                easypack_email: jQuery(metabox).find('#easypack_email_enabled').is(':checked'),
                reference_number: jQuery(metabox).find('#reference_number').val(),
                easypack_additional_package: jQuery(metabox).find('#easypack_additional_package').val()
            };
        }

        jQuery.post(ajaxurl, data, function (response) {
            //console.log(response);
            if (response !== 0 && response !== '') {
                response = JSON.parse(response);
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
                            let button_text = '<?php echo esc_html__("Get sticker for additional package", "woocommerce-inpost");?>';

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
                }
                else {
                    //alert(response.message);
                    jQuery(metabox).find('#easypack_error').html(response.message);
                    jQuery(metabox).find('#easypack_error').css('color', '#f00');

                }
            }
            else {
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

<?php include('services/html-service-get-label.php'); ?>
<?php include('services/html-service-view-additional-package.php'); ?>
<?php include('services/html-service-additional-package.php'); ?>
