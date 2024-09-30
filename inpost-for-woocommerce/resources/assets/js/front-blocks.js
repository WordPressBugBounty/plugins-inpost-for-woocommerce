let inpostPlGeowidgetModalBlock;

function inpost_pl_get_shipping_method_block() {
	let data                = {};
	let shipping_block_html = jQuery( '.wc-block-components-shipping-rates-control' );
	if (typeof shipping_block_html != 'undefined' && shipping_block_html !== null) {
		let shipping_radio_buttons = jQuery( shipping_block_html ).find( 'input[name^="radio-control-"]' );
		if ( shipping_radio_buttons.length > 0 ) {
			let method  = jQuery( 'input[name^="radio-control-"]:checked' ).val();
			let postfix = '';
			if ('undefined' == typeof method || null === method) {
				method = jQuery( 'input[name^="radio-control-"]' ).val();
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
		nativeInputValueSetter.call( input, value );

		var inputEvent = new Event( "input", {bubbles: true} );
		input.dispatchEvent( inputEvent );
	}
}


function inpost_pl_select_point_callback_blocks(point) {

	let selected_point_data = '';
	let parcelMachineAddressDesc;
	let address_line1 = '';
	let address_line2 = '';

	if ( typeof point.location_description != 'undefined' && point.location_description !== null ) {
		parcelMachineAddressDesc = point.location_description;
	}
	if ( typeof point.address.line2 != 'undefined' && point.address.line2 !== null ) {
		address_line2 = point.address.line2;
	}
	if ( typeof point.address.line1 != 'undefined' && point.address.line1 !== null ) {
		address_line1 = point.address.line1;
	}

	if (point) {
		jQuery( '#easypack_selected_point_data' ).each(
			function (ind, elem) {
				jQuery( elem ).remove();
			}
		);
		inpost_pl_change_react_input_value( document.getElementById( 'inpost-parcel-locker-id' ), point.name );

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

	jQuery( '#inpost_pl_selected_point_data_wrap' ).html( selected_point_data );
	jQuery( '#inpost_pl_selected_point_data_wrap' ).show();
	jQuery( "#easypack_block_type_geowidget" ).text( easypack_block.button_text2 );

	inpostPlGeowidgetModalBlock.close();
}


jQuery( document ).ready(
	function () {

		let modal       = document.createElement( 'div' );
		modal.innerHTML = `
		<div id="inpost_pl_checkout_validation_modal" style="
			display: none;
			position: fixed;
			top: 0;
			left: 0;
			width: 100%; 
			height: 100%; 
			background-color: rgba( 0, 0, 0, 0.5 );
			justify-content: center;
			align-items: center;
			z-index: 1000;">
			<div style="
			background-color: white;
			width: 90%; 
			max-width: 300px;
			padding: 20px;
			position: relative;
			text-align: center;
			border-radius: 10px;
			box-shadow: 0px 4px 10px rgba( 0, 0, 0, 0.1 );">
			<span id="inp_pl_close_modal_cross" style="
				position: absolute;
				top: 10px;
				right: 15px;
				font-size: 20px;
				cursor: pointer;">&times;</span>
			<div style="margin:20px 0; font-size:18px;">
				Musisz wybrać paczkomat.
			</div>
			<button id="inp_pl_close_modal_button" style="
				padding: 10px 20px;
				background-color: #FFA900;
				color: white;
				border: none;
				border-radius: 5px;
				cursor: pointer;
				font-size: 16px;">
				Ok
			</button>
			</div>
		</div>
		`;

		// Append modal to body.
		document.body.appendChild( modal );

		// Event Listeners for closing modal.
		let modal_close_1 = document.getElementById( 'inp_pl_close_modal_cross' );
		if (typeof modal_close_1 != 'undefined' && modal_close_1 !== null) {
			modal_close_1.addEventListener( 'click', inpost_pl_close_validation_modal );
		}
		let modal_close_2 = document.getElementById( 'inp_pl_close_modal_button' );
		if (typeof modal_close_2 != 'undefined' && modal_close_2 !== null) {
			modal_close_2.addEventListener( 'click', inpost_pl_close_validation_modal );
		}

		setTimeout(
			function () {

				let token                        = easypack_block.geowidget_v5_token;
				let shipping_data                = inpost_pl_get_shipping_method_block();
				let config                       = 'parcelCollect';
				let single_inpost_method_req_map = false;
				let method                       = shipping_data.method;
				let postfix                      = shipping_data.postfix;

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

				let wH = jQuery( window ).height() - 80;

				inpostPlGeowidgetModalBlock = new jBox(
					'Modal',
					{
						width: 800,
						height: wH,
						attach: '#eqasypack_show_geowidget',
						title: 'Wybierz paczkomat',
						content: '<inpost-geowidget id="inpost-geowidget" onpoint="inpost_pl_select_point_callback_blocks" token="' + token + '" language="pl" config="' + config + '"></inpost-geowidget>'
					}
				);

				jQuery( 'input[name^="radio-control-"]' ).on(
					'change',
					function () {
						if (this.checked) {
							jQuery( '#inpost_pl_selected_point_data_wrap' ).hide();
							inpost_pl_change_react_input_value( document.getElementById( 'inpost-parcel-locker-id' ), '' );

							let config = 'parcelCollect';
							if (jQuery( this ).attr( 'id' ).indexOf( 'easypack_parcel_machines_cod' ) !== -1) {
								config = 'parcelCollectPayment';
							}
							if (jQuery( this ).attr( 'id' ).indexOf( 'easypack_shipping_courier_c2c' ) !== -1) {
								config = 'parcelSend';
							}
							if (jQuery( this ).attr( 'id' ).indexOf( 'easypack_parcel_machines_weekend' ) !== -1) {
								config = 'parcelCollect247';
							}

							let map_content = '<inpost-geowidget id="inpost-geowidget" onpoint="inpost_pl_select_point_callback_blocks" token="' + token + '" language="pl" config="' + config + '"></inpost-geowidget>';

							inpostPlGeowidgetModalBlock.setContent( map_content );

						}
					}
				);

			},
			1200
		);
	}
);


document.addEventListener(
	'click',
	function (e) {
		e          = e || window.event;
		var target = e.target || e.srcElement;

		if ( target.hasAttribute( 'id' ) ) {
			if (target.getAttribute( 'id' ) === 'easypack_block_type_geowidget' ) {
				e.preventDefault();

				if ( typeof inpostPlGeowidgetModalBlock != 'undefined' && inpostPlGeowidgetModalBlock !== null ) {

					let checked_radio_control = jQuery( 'input[name^="radio-control-"]:checked' );
					if ( typeof checked_radio_control != 'undefined' && checked_radio_control !== null) {
						let id          = jQuery( checked_radio_control ).attr( 'id' );
						let instance_id = null;
						let method_id   = null;
						let method_data = null;
						if (typeof id != 'undefined' && id !== null) {
							method_data = id.split( ":" );
							instance_id = method_data[method_data.length - 1];
							method_id   = method_data[0];							

							if (typeof method_id != 'undefined' && method_id !== null) {
								let token  = easypack_block.geowidget_v5_token;
								let config = 'parcelCollect';
								if (method_id.indexOf( 'easypack_parcel_machines_cod' ) !== -1) {
									config = 'parcelCollectPayment';
								}
								if (method_id.indexOf( 'easypack_shipping_courier_c2c' ) !== -1) {
									config = 'parcelSend';
								}
								if (method_id.indexOf( 'easypack_parcel_machines_weekend' ) !== -1) {
									config = 'parcelCollect247';
								}
								let map_content = '<inpost-geowidget id="inpost-geowidget" onpoint="inpost_pl_select_point_callback_blocks" token="' + token + '" language="pl" config="' + config + '"></inpost-geowidget>';
								inpostPlGeowidgetModalBlock.setContent( map_content );
							}
						}
					}

					if ( ! inpostPlGeowidgetModalBlock.isOpen ) {
						inpostPlGeowidgetModalBlock.open();
					}
				}
			}
		}

		if ( target.classList.contains( 'wc-block-components-checkout-place-order-button' )
			|| target.classList.contains( 'wc-block-checkout__actions_row' ) ) {

			let reactjs_input       = document.getElementById( 'inpost-parcel-locker-id' );
			let reactjs_input_lalue = false;
			if (typeof reactjs_input != 'undefined' && reactjs_input !== null) {
				reactjs_input_lalue = reactjs_input.value;
				if ( ! reactjs_input_lalue ) {
					inpost_pl_open_validation_modal();
				}
			}
		}

		if ( target.classList.contains( 'wc-block-components-button__text' ) ) {
			let parent = target.parentNode;
			if ( parent.classList.contains( 'wc-block-components-checkout-place-order-button' ) ) {
				let reactjs_input       = document.getElementById( 'inpost-parcel-locker-id' );
				let reactjs_input_lalue = false;
				if (typeof reactjs_input != 'undefined' && reactjs_input !== null) {
					reactjs_input_lalue = reactjs_input.value;
					if ( ! reactjs_input_lalue ) {
						inpost_pl_open_validation_modal();
					}
				}
			}
		}
	}
);


function inpost_pl_open_validation_modal() {
	document.getElementById( 'inpost_pl_checkout_validation_modal' ).style.display = 'flex';
}

function inpost_pl_close_validation_modal() {
	document.getElementById( 'inpost_pl_checkout_validation_modal' ).style.display = 'none';

	// Scroll to map button.
	let scrollToElement = document.getElementById( 'easypack_block_type_geowidget' );

	if (scrollToElement) {
		scrollToElement.scrollIntoView( {behavior: 'smooth' } );
	}

}