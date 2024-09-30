let inpostplGeowidgetModal;
let inpostplMapConfig;
let inpostplMapToken = '';

function inpost_pl_select_point_callback(point) {

	let parcelMachineAddressDesc = '';
	if ( typeof point.location_description != 'undefined' && point.location_description !== null ) {
		parcelMachineAddressDesc = point.location_description;
	}

	jQuery( 'input[name=parcel_machine_id]' ).each(
		function (ind, elem) {
			jQuery( elem ).val( point.name );
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

	if ( typeof point.name != 'undefined' && point.name !== null ) {
		visible_point_data += point.name + '<br>';
	}

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

	// for some templates like Divi - add hidden fields for Parcel locker validation dynamically.
	var form               = document.getElementsByClassName( 'checkout woocommerce-checkout' )[0];
	var additionalInput1   = document.createElement( 'input' );
	additionalInput1.type  = 'hidden';
	additionalInput1.name  = 'parcel_machine_id';
	additionalInput1.value = point.name;

	var additionalInput2   = document.createElement( 'input' );
	additionalInput2.type  = 'hidden';
	additionalInput2.name  = 'parcel_machine_desc';
	additionalInput2.value = parcelMachineAddressDesc;

	if (form) {
		form.appendChild( additionalInput1 );
		form.appendChild( additionalInput2 );
	}

	inpostplGeowidgetModal.close();
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
		
		console.log("InpostPL: wer.1.5.3");

		// Prepare modal with map.
		inpostplMapToken  = inpost_pl_map.geowidget_v5_token;
		let shipping_data = inpost_pl_get_shipping_method();
		let method        = shipping_data.method;

		let wH = jQuery( window ).height() - 100;

		if (typeof method != 'undefined' && method !== null) {
			if (method === 'easypack_parcel_machines_cod') {
				inpostplMapConfig = 'parcelCollectPayment';
			} else if (method === 'easypack_shipping_courier_c2c') {
				inpostplMapConfig = 'parcelSend';
			} else if (method === 'easypack_parcel_machines_weekend') {
				inpostplMapConfig = 'parcelCollect247';
			} else {
				inpostplMapConfig = 'parcelCollect';
			}

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

				if (typeof method != 'undefined' && method !== null) {
					if (method === 'easypack_parcel_machines_cod') {
						inpostplMapConfig = 'parcelCollectPayment';
					} else if (method === 'easypack_shipping_courier_c2c') {
						inpostplMapConfig = 'parcelSend';
					} else if (method === 'easypack_parcel_machines_weekend') {
						inpostplMapConfig = 'parcelCollect247';
					} else {
						inpostplMapConfig = 'parcelCollect';
					}

					let map_content = '<inpost-geowidget id="inpost-geowidget" onpoint="inpost_pl_select_point_callback" token="' + inpostplMapToken + '" language="pl" config="' + inpostplMapConfig + '"></inpost-geowidget>';
					inpostplGeowidgetModal.setContent( map_content );
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