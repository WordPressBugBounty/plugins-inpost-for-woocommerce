let inpostplGeowidgetModal;
let inpostplMapConfig;
let inpostplMapToken = '';

function inpost_pl_validate_parcel_machine_for_gpay() {

	let checked_shipping_input = document.querySelector( '#shipping_method input[name^="shipping_method["]:checked' );
	if (checked_shipping_input !== undefined && checked_shipping_input !== null) {
		let id = checked_shipping_input.value;
		console.log( 'Shipping method ID:', id );

		if (id.indexOf( 'easypack_parcel_machines' ) !== -1) {
			let hidden_input = document.querySelector( '#parcel_machine_id' );
			if (hidden_input !== undefined && hidden_input !== null) {
				let paczkomat_id = hidden_input.value;
				console.log( 'Paczkomat ID:', paczkomat_id );

				if (paczkomat_id.trim() === '') {
					return false;
				}
			}
		}
	}

	return true;
}



function inpost_pl_get_map_config_by_shipping_instance_id(instance_id, method) {
	let map_config     = 'parcelCollect';
	let inpost_methods = inpost_pl_get_configured_inpost_methods_data();

	if (instance_id !== undefined && instance_id !== null && instance_id !== '') {
		let selected_method = inpost_methods[instance_id];
		if (typeof selected_method != 'undefined' && selected_method !== null) {
			let method_id = selected_method.inpost_title;
			if (method_id === 'easypack_parcel_machines_cod') {
				map_config = 'parcelCollectPayment';
			}
			if (method_id === 'easypack_shipping_courier_c2c') {
				map_config = 'parcelSend';
			}
			if (method_id === 'easypack_parcel_machines_weekend' || method_id === 'easypack_parcel_machines_weekend_cod') {
				map_config = 'parcelCollect247';
			}
		}

	} else {
		if (method === 'easypack_parcel_machines_cod') {
			map_config = 'parcelCollectPayment';
		}
		if (method === 'easypack_shipping_courier_c2c') {
			map_config = 'parcelSend';
		}
		if (method === 'easypack_parcel_machines_weekend' || method === 'easypack_parcel_machines_weekend_cod') {
			map_config = 'parcelCollect247';
		}
	}

	return map_config;
}

function inpost_pl_get_configured_inpost_methods_data() {
	if (typeof inpost_pl_map != 'undefined' && inpost_pl_map !== null) {
		if ( inpost_pl_map.inpost_methods ) {
			return inpost_pl_map.inpost_methods;
		}
	}
	return [];
}


function inpost_pl_select_point_callback(point) {

	let parcelMachineAddressDesc = '';
	if ( typeof point.location_description != 'undefined' && point.location_description !== null ) {
		parcelMachineAddressDesc = point.location_description;
	}

	let point_name = '';
	if ( 'name' in point ) {
		point_name = point.name;
		if (point_name.startsWith( "PL_" )) {
			// Remove first 3 characters "PL_".
			point_name = point_name.slice( 3 );
		}
	}

	jQuery( 'input[name=parcel_machine_id]' ).each(
		function (ind, elem) {
			jQuery( elem ).val( point_name );
		}
	);
	jQuery( 'input[name=parcel_machine_desc]' ).each(
		function (ind, elem) {
			jQuery( elem ).val( parcelMachineAddressDesc );
		}
	);

	// some woo stores have re-built Checkout pages and multiple '#' id is possible.
	jQuery( '*[id*=selected-parcel-machine]' ).each(
		function (ind, elem) {
			jQuery( elem ).removeClass( 'hidden-paczkomat-data' );
		}
	);

	let visible_point_data = '';

	visible_point_data += point_name + '<br>';

	if ( typeof point.address.line1 != 'undefined' && point.address.line1 !== null ) {
		visible_point_data += point.address.line1 + '<br>';
	}

	if ( typeof point.address.line2 != 'undefined' && point.address.line2 !== null ) {
		visible_point_data += point.address.line2 + '<br>';
	}

	visible_point_data += parcelMachineAddressDesc;

	jQuery( '*[id*=selected-parcel-machine-id]' ).each(
		function (ind, elem) {
			jQuery( elem ).html( visible_point_data );
		}
	);

	let EasyPackPointObject = { 'pointName': point_name, 'pointDesc': parcelMachineAddressDesc, 'visiblePointData': visible_point_data };
	localStorage.setItem( 'EasyPackPointObject', JSON.stringify( EasyPackPointObject ) );

	// for some templates like Divi - add hidden fields for Parcel locker validation dynamically.
	var form               = document.getElementsByClassName( 'checkout woocommerce-checkout' )[0];
	var additionalInput1   = document.createElement( 'input' );
	additionalInput1.type  = 'hidden';
	additionalInput1.name  = 'parcel_machine_id';
	additionalInput1.value = point_name;

	var additionalInput2   = document.createElement( 'input' );
	additionalInput2.type  = 'hidden';
	additionalInput2.name  = 'parcel_machine_desc';
	additionalInput2.value = parcelMachineAddressDesc;

	if (form) {
		form.appendChild( additionalInput1 );
		form.appendChild( additionalInput2 );
	}

	inpostplGeowidgetModal.close();

	let data = {
		action: 'inpost_save_to_wc_session',
		security: inpost_pl_map.security,
		key: 'inpost_pl_wc_paczkomat',
		value: point_name
	};

	jQuery.ajax(
		{
			type: 'POST',
			url: inpost_pl_map.ajaxurl,
			data: data,
			dataType: 'json',
			success: function (response) {
				console.log( 'Paczkomat saved in session data:', response );
			},
			error: function (jqXHR, textStatus, errorThrown) {
				console.log( "Error response saving paczkomato into session" );
				console.log( textStatus );
				console.log( 'Error: ' + errorThrown + ' ' + jqXHR.responseText );

			}
		}
	);
}


function inpost_pl_get_shipping_method() {
	let data    = {};
	let method  = jQuery( 'input[name^="shipping_method[0]"]:checked' ).val();
	let postfix = '';
	if ('undefined' == typeof method || null === method ) {
		method = jQuery( 'input[name^="shipping_method[0]"]' ).val();
	}
	if (typeof method != 'undefined' && method !== null) {
		if (method.indexOf( ':' ) > -1) {
			let arr = method.split( ':' );
			method  = arr[0];
			postfix = arr[1];
		}
	}
	data.method  = method;
	data.postfix = postfix;

	return data;
}



jQuery( document ).ready(
	function () {

		// Prepare modal with map.
		inpostplMapToken  = inpost_pl_map.geowidget_v5_token;
		let shipping_data = inpost_pl_get_shipping_method();
		let method        = shipping_data.method;
		let instance_id   = shipping_data.postfix;
		inpostplMapConfig = 'parcelCollect';

		let wH = jQuery( window ).height() - 100;

		if (typeof method != 'undefined' && method !== null) {
			inpostplMapConfig = inpost_pl_get_map_config_by_shipping_instance_id( instance_id, method );
		}

		inpostplGeowidgetModal = new jBox(
			'Modal',
			{
				width: 800,
				height: wH,
				attach: '#easypack_show_geowidget',
				title: 'Wybierz paczkomat',
				content: '<inpost-geowidget id="inpost-geowidget" onpoint="inpost_pl_select_point_callback" token="' + inpostplMapToken + '" language="pl" config="' + inpostplMapConfig + '"></inpost-geowidget>'
			}
		);

		jQuery( '#easypack_show_geowidget' ).on(
			'click',
			function () {
				console.log( 'inpost geowidget open' );
				if ( ! inpostplGeowidgetModal.isOpen ) {
					inpostplGeowidgetModal.open();
				}
			}
		);

		jQuery( document.body ).on(
			'updated_checkout',
			function () {

				// Change modal map params.
				let shipping_data = inpost_pl_get_shipping_method();
				let method        = shipping_data.method;
				let instance_id   = shipping_data.postfix;
				inpostplMapConfig = 'parcelCollect';

				if ( typeof method != 'undefined' && method !== null ) {
					inpostplMapConfig = inpost_pl_get_map_config_by_shipping_instance_id( instance_id, method );

					let map_content = '<inpost-geowidget id="inpost-geowidget" onpoint="inpost_pl_select_point_callback" token="' + inpostplMapToken + '" language="pl" config="' + inpostplMapConfig + '"></inpost-geowidget>';
					inpostplGeowidgetModal.setContent( map_content );

					let EasyPackPointObject = localStorage.getItem( 'EasyPackPointObject' );

					if (EasyPackPointObject !== null) {
						let point,
							visible_desc,
							desc;

						let pointData = JSON.parse( EasyPackPointObject );
						if (typeof pointData != 'undefined' && pointData !== null) {
							if (typeof pointData.pointName != 'undefined' && pointData.pointName !== null) {
								point = pointData.pointName;
							}
							if (typeof pointData.visiblePointData != 'undefined' && pointData.visiblePointData !== null) {
								visible_desc = pointData.visiblePointData;
							}
							if (typeof pointData.pointDesc != 'undefined' && pointData.pointDesc !== null) {
								desc = pointData.pointDesc;
							}

							if (typeof point != 'undefined' && point !== null) {
								jQuery('#easypack_show_geowidget').text(inpost_pl_map.button_text2);
								jQuery('input[name=parcel_machine_id]').each(
									function (ind, elem) {
										jQuery(elem).val(point);
									}
								);
								jQuery('#divi_parcel_machine_id').val(point);

								if (typeof visible_desc != 'undefined' && visible_desc !== null) {
									jQuery('*[id*=selected-parcel-machine-id]').each(
										function (ind, elem) {
											jQuery(elem).html(visible_desc);
										}
									);
									jQuery('*[id*=selected-parcel-machine]').each(
										function (ind, elem) {
											jQuery(elem).removeClass('hidden-paczkomat-data');
										}
									);
								}
							}

							if (typeof desc != 'undefined' && desc !== null) {
								jQuery('input[name=parcel_machine_desc]').each(
									function (ind, elem) {
										jQuery(elem).val(desc);
									}
								);
								jQuery('#divi_parcel_machine_desc').val(desc);
							}
						}
					}

				}
			}
		);


		document.addEventListener(
			'change',
			function (e) {
				e          = e || window.event;
				var target = e.target;
				if (target.hasAttribute( 'name' )) {
					if (target.getAttribute('name') === 'shipping_method[0]') {
						localStorage.setItem( 'EasyPackPointObject', null );
						console.log('reset local storage value');
					}
				}
			}
		);
	}
);


document.addEventListener(
	'click',
	function (e) {
		e          = e || window.event;
		var target = e.target || e.srcElement;

		if (target.hasAttribute( 'id' ) && target.getAttribute( 'id' ) === 'easypack_show_geowidget') {
			e.preventDefault();

			if (typeof inpostplGeowidgetModal != 'undefined' && inpostplGeowidgetModal !== null) {
				if ( ! inpostplGeowidgetModal.isOpen ) {
					console.log( 'open inpost geowidget' );
					inpostplGeowidgetModal.open();
				}
			}
		}
	}
);


window.addEventListener(
	'message',
	function (event) {

		let parsedData;
		try {
			if (typeof event.data === 'string') {
				parsedData = JSON.parse( event.data );
			} else {
				parsedData = event.data;
			}

			if (
			parsedData.message.payload &&
			parsedData.message.payload.event === "shippingratechange" &&
			parsedData.message.payload.data &&
			parsedData.message.payload.data.shippingRate &&
			parsedData.message.payload.data.shippingRate.id
			) {
				let chosen_shipping_method = parsedData.message.payload.data.shippingRate.id;

				console.log( 'Chosen_shipping_method:' );
				console.log( chosen_shipping_method );

				if ( chosen_shipping_method.indexOf( 'easypack_parcel_machines' ) !== -1 ) {
					let checked_shipping_input = document.querySelector( '#shipping_method input[name^="shipping_method["]:checked' );
					if (checked_shipping_input !== undefined && checked_shipping_input !== null) {
						let id = checked_shipping_input.value;
						console.log( 'Shipping method ID:', id );

						if (id.indexOf( 'easypack_parcel_machines' ) !== -1) {
							let hidden_input = document.querySelector( '#parcel_machine_id' );
							if (hidden_input !== undefined && hidden_input !== null) {
								let paczkomat_id = hidden_input.value;
								console.log( 'Paczkomat ID:', paczkomat_id );

								if (paczkomat_id.trim() === '') {
									alert( 'Wygląda na to, że zapomniałeś wybrać paczkomat.' + "\n\n" + ' Jeśli tak, zamknij okno modalne, wybierz punkt za pomocą przycisku "Wybierz punkt odbioru", a następnie wróć do płatności.' );
									return false;
								}
							}
						}
					}
				}
			}

			// console.log('Check JS parsedData');
			// console.log(parsedData);

			// Now check for Google Pay click using the parsed data
			if (
			parsedData.type === "parent" &&
			parsedData.message &&
			parsedData.message.action === "stripe-frame-event" &&
			parsedData.message.payload &&
			parsedData.message.payload.event === "click" &&
			parsedData.message.payload.data
			) {

				if (
					"google_pay" === parsedData.message.payload.data.paymentMethodType
					|| "apple_pay" === parsedData.message.payload.data.paymentMethodType
					|| "apple_pay_inner" === parsedData.message.payload.data.paymentMethodType
				) {
					// console.log('Google Pay or Apple Pay button click detected');
					if ( ! inpost_pl_validate_parcel_machine_for_gpay() ) {
							// console.log('Parcel machine validation failed');

							alert( 'Wygląda na to, że zapomniałeś wybrać paczkomat.' + "\n\n" + ' Jeśli tak, zamknij okno modalne, wybierz punkt za pomocą przycisku "Wybierz punkt odbioru", a następnie wróć do płatności.' );

							return false;
					}
				}

			}
		} catch (err) {
			// console.log('Error processing message:', err);
		}
	}
);