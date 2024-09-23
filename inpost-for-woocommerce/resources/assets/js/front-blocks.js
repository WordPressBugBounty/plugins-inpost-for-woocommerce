var geowidgetModal;
var geowidgetSelectedPoint;

function inpost_pl_get_shipping_method_block() {
    let data = {};
    let shipping_block_html = jQuery('.wc-block-components-shipping-rates-control');
    if(typeof shipping_block_html != 'undefined' && shipping_block_html !== null) {
        let shipping_radio_buttons = jQuery(shipping_block_html).find('input[name^="radio-control-0"]');
        if( shipping_radio_buttons.length > 0 ) {
            let method = jQuery('input[name^="radio-control-0"]:checked').val();
            let postfix = '';
            if ('undefined' == typeof method || null === method) {
                method = jQuery('input[name^="radio-control-0"]').val();
            }

            if (typeof method != 'undefined' && method !== null) {
                if (method.indexOf(':') > -1) {
                    let arr = method.split(':');
                    method = arr[0];
                    postfix = arr[1];
                }
            }
            data.method = method;
            data.postfix = postfix;
        }
    }

    return data;
}

function inpost_pl_change_react_input_value(input,value) {

    if (typeof input != 'undefined' && input !== null) {
        var nativeInputValueSetter = Object.getOwnPropertyDescriptor(
            window.HTMLInputElement.prototype,
            "value"
        ).set;
        nativeInputValueSetter.call(input, value);

        var inputEvent = new Event("input", {bubbles: true});
        input.dispatchEvent(inputEvent);
    }
}


function inpost_pl_show_missed_locker_message() {
    jQuery('.easypack-woocommerce-checkout-block-alert').remove();
    let point_value_input = jQuery('input[id="inpost-parcel-locker-id"]');
    if (typeof point_value_input != 'undefined' && point_value_input !== null) {
        if ( ! jQuery(point_value_input).val() ) {
            let alert = '<div class="easypack-woocommerce-checkout-block-alert">' +
                '<span style="color:red; font-size:24px;">' +
                'Musisz wybrać paczkomat ⇑' +
                '</span>' +
                '</div>';
            jQuery('.wc-block-checkout__actions').prepend(alert);
            jQuery('.wc-block-checkout__actions_row').hide();
        }
    }
}

function inpost_pl_select_point_callback_blocks(point) {
    
    let selected_point_data = '';    
    let parcelMachineAddressDesc;
    let address_line1 = '';
    let address_line2 = '';

    if( typeof point.location_description != 'undefined' && point.location_description !== null ) {
        parcelMachineAddressDesc = point.location_description;
    }
    if( typeof point.address.line2 != 'undefined' && point.address.line2 !== null ) {
        address_line2 = point.address.line2;
    }
    if( typeof point.address.line1 != 'undefined' && point.address.line1 !== null ) {
        address_line1 = point.address.line1;
    }

    if(point) {
        jQuery('#easypack_selected_point_data').each(function(ind, elem) {
            jQuery(elem).remove();
        });
        inpost_pl_change_react_input_value(document.getElementById('inpost-parcel-locker-id'), point.name);
        jQuery('.easypack-woocommerce-checkout-block-alert').remove();
        jQuery('.wc-block-checkout__actions_row').show();
    }


    if (point.location_description) {

        selected_point_data = '<div class="easypack_selected_point_data" id="easypack_selected_point_data">\n'
            + '<div id="selected-parcel-machine-id">' + point.name + '</div>\n'
            + '<span id="selected-parcel-machine-desc">' + address_line1 + '<br>' + address_line2 + '</span><br>'
            + '<span id="selected-parcel-machine-desc1">' + '(' + point.location_description + ')</span></div>';

    } else {
        selected_point_data = '<div class="easypack_selected_point_data" id="easypack_selected_point_data">\n'
            + '<div id="selected-parcel-machine-id">' + point.name + '</div>\n'
            + '<span id="selected-parcel-machine-desc">' + address_line1 + '<br>' + address_line2 + '</span></div>';
    }

    jQuery('#easypack_block_type_geowidget').after(selected_point_data);
    jQuery("#easypack_block_type_geowidget").text(easypack_block.button_text2);

    let point_address = point.address.line1 + '<br>' + address_line2;    

    geowidgetSelectedPoint = selected_point_data;
    geowidgetModal.close();

}

jQuery(document).ready(function() {

    setTimeout(function(){

        let token = easypack_block.geowidget_v5_token;
        let shipping_data = inpost_pl_get_shipping_method_block();
        let config = 'parcelCollect';
        let single_inpost_method_req_map = false;
        let method = null;
        let postfix = null;

        if( jQuery.isEmptyObject(shipping_data) ) {
            if (typeof easypack_single != 'undefined' && easypack_single !== null) {
                if (easypack_single.need_map) {
                    single_inpost_method_req_map = true;
                }
                if (easypack_single.config) {
                    config = easypack_single.config;
                }
            }

        } else {
            method = shipping_data.method;
            postfix = shipping_data.postfix;
        }

        if (typeof method != 'undefined' && method !== null) {
            if (method === 'easypack_parcel_machines_cod') {
                config = 'parcelCollectPayment';
            }
            if (method === 'easypack_shipping_courier_c2c') {
                config = 'parcelSend';
            }
            if (method === 'easypack_parcel_machines_weekend') {
                config = 'parcelCollect247';
            }
        }

        var wH = jQuery(window).height()-80;

        geowidgetModal = new jBox('Modal', {
            width: 800,
            height: wH,
            attach: '#eqasypack_show_geowidget',
            title: 'Wybierz paczkomat',
            content: '<inpost-geowidget id="inpost-geowidget" onpoint="inpost_pl_select_point_callback_blocks" token="' + token + '" language="pl" config="' + config + '"></inpost-geowidget>'
        });


        let map_button = '<button class="button alt easypack_show_geowidget" id="easypack_block_type_geowidget">\n' +
            easypack_block.button_text1 + '</button>';

        if ( typeof method != 'undefined' && method !== null ) {
            if (method.indexOf('easypack_parcel_machines') !== -1) {
                let selector = 'radio-control-0-' + method + ':' + postfix;
                let label = jQuery('label[for="' + selector + '"]');
                jQuery(label).after(map_button);
                jQuery('#shipping-phone').prop('required', true);
                jQuery('label[for="shipping-phone"]').text(easypack_block.phone_text);

                inpost_pl_show_missed_locker_message();
            }

        } else if(single_inpost_method_req_map) {
            let shipping_block_wrap = jQuery('.wc-block-components-shipping-rates-control__package');
            if (typeof shipping_block_wrap != 'undefined' && shipping_block_wrap !== null) {
                jQuery(shipping_block_wrap).after(map_button);
                jQuery('#shipping-phone').prop('required', true);
                jQuery('label[for="shipping-phone"]').text(easypack_block.phone_text);                
            }
            inpost_pl_show_missed_locker_message();

        } else {
            jQuery('#easypack_selected_point_data').remove();
            jQuery('#shipping-phone').prop('required', false);
            inpost_pl_change_react_input_value(document.getElementById('inpost-parcel-locker-id'), '');
            jQuery('.easypack-woocommerce-checkout-block-alert').remove();
            jQuery('.wc-block-checkout__actions_row').show();
        }


        jQuery('input[name^="radio-control-0"]').on('change', function () {
            if (this.checked) {

                jQuery('#easypack_block_type_geowidget').remove();
                jQuery('#easypack_selected_point_data').remove();
                jQuery('.parcel_machine_id').val('');
                jQuery('.parcel_machine_desc').val('');
                inpost_pl_change_react_input_value(document.getElementById('inpost-parcel-locker-id'), '');
                jQuery('#shipping-phone').prop('required', false);

                jQuery('.easypack-woocommerce-checkout-block-alert').remove();
                jQuery('.wc-block-checkout__actions_row').show();
				
				
                let token = easypack_block.geowidget_v5_token;
                let config = 'parcelCollect';

                if (jQuery(this).attr('id').indexOf('easypack_parcel_machines_cod') !== -1) {
                    config = 'parcelCollectPayment';
                }
                if (jQuery(this).attr('id').indexOf('easypack_shipping_courier_c2c') !== -1) {
                    config = 'parcelSend';
                }
                if (jQuery(this).attr('id').indexOf('easypack_parcel_machines_weekend') !== -1) {
                    config = 'parcelCollect247';
                }

                var wH = jQuery(window).height()-80;

                console.log("inpost map config");
                console.log(config);

                geowidgetModal = new jBox('Modal', {
                    width: 800,
                    height: wH,
                    attach: '#eqasypack_show_geowidget',
                    title: 'Wybierz paczkomat',
                    content: '<inpost-geowidget id="inpost-geowidget" onpoint="inpost_pl_select_point_callback_blocks" token="' + token + '" language="pl" config="' + config + '"></inpost-geowidget>'
                });

                if (jQuery(this).attr('id').indexOf('easypack_parcel_machines') !== -1) {
                    let label = jQuery(this).parent('label');
                    jQuery(label).after(map_button);
                    jQuery('#shipping-phone').prop('required', true);
                    jQuery('label[for="shipping-phone"]').text('Telefon (wymagany)');
                    inpost_pl_show_missed_locker_message();

                }
            }
        });


    }, 1200 );
});


document.addEventListener('click', function (e) {
    e = e || window.event;
    var target = e.target || e.srcElement;

    if ( target.hasAttribute('id') )  {
        if (target.getAttribute('id') == 'easypack_block_type_geowidget' || target.getAttribute('id') == 'inpost-parcel-locker-id') {
            e.preventDefault();
            if( typeof geowidgetModal != 'undefined' && geowidgetModal !== null ) {
                if( ! geowidgetModal.isOpen ) {
                    geowidgetModal.open();
                }
            }
        }
    }
	
	if ( target.classList.contains( 'easypack-woocommerce-checkout-block-alert' ) ) {
        let shipping_section = document.getElementById('shipping-option');
        if (typeof shipping_section != 'undefined' && shipping_section !== null) {
            inpost_pl_scroll_to(shipping_section);
        }
    }
});


function inpost_pl_scroll_to(element) {
    window.scroll({
        behavior: 'smooth',
        left: 0,
        top: element.offsetTop
    });
}