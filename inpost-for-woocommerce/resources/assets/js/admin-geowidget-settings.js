var geowidgetModalOrder;
var easypack_current_metabox;

/**
 * jBox Modal adds "unscrollable" to body; if the instance DOM is removed without destroy(), scroll can stay locked.
 */
function easypackReleaseJboxBodyScroll() {
	document.body.classList.remove( 'unscrollable' );
	document.documentElement.classList.remove( 'unscrollable' );
	jQuery( 'body, html' ).removeClass( 'unscrollable' );
}

function easypackDestroyGeowidgetModal() {
	if ( typeof geowidgetModalOrder !== 'undefined' && geowidgetModalOrder !== null ) {
		try {
			if ( geowidgetModalOrder.isOpen ) {
				geowidgetModalOrder.close( { ignoreDelay: true } );
			}
			geowidgetModalOrder.destroy();
		} catch ( err ) {
			console.log( err );
		}
		geowidgetModalOrder = null;
	}
	easypackReleaseJboxBodyScroll();
}

function easypackOpenGeowidgetModal( config ) {
	var mapContent =
		'<inpost-geowidget ' +
		'onpoint="selectPointCallbackAdditional" ' +
		'token="' + easypackAdminGeowidgetSettings.token + '" ' +
		'language="pl" ' +
		'config="' + config + '"></inpost-geowidget>';

	if ( typeof geowidgetModalOrder === 'undefined' || geowidgetModalOrder === null ) {
		geowidgetModalOrder = new jBox(
			'Modal',
			{
				width: easypackAdminGeowidgetSettings.width,
				height: easypackAdminGeowidgetSettings.height,
				title: easypackAdminGeowidgetSettings.title,
				content: mapContent,
				overlay: true,
				closeOnClick: 'overlay',
				closeOnEsc: true,
				isolateScroll: true,
				onClose: easypackReleaseJboxBodyScroll,
				onCloseComplete: easypackReleaseJboxBodyScroll
			}
		);
	} else {
		geowidgetModalOrder.setContent( mapContent );
	}

	if ( ! geowidgetModalOrder.isOpen ) {
		console.log( 'map metabox open' );
		geowidgetModalOrder.open();
	}
}

function selectPointCallbackAdditional(point) {
	if ( typeof easypack_current_metabox != 'undefined' && easypack_current_metabox !== null ) {
		jQuery( easypack_current_metabox ).find( '#parcel_machine_id' ).val( point.name );

		const $metabox = jQuery( easypack_current_metabox );

		if ( $metabox.attr( 'id' ) === 'easypack_shipment_changed' ) {
			console.log( 'easypack_current_metabox CHANGED. send AJAX...' );

			let order_id = jQuery( easypack_current_metabox ).find( '#easypack_inpost_pl_wc_order_id' ).val();
			console.log( 'order_id :', order_id );

			let data = {
				action: 'easypack',
				easypack_action: 'inpost_pl_metabox_paczkomat',
				security: easypack_nonce,
				key: 'inpost_pl_paczkomat',
				point_code: point.name,
				order_id: order_id
			};

			jQuery.ajax(
				{
					url: ajaxurl,
					type: 'POST',
					data: data,
					success: function (response) {
						console.log( 'AJAX :', response );
					},
					error: function (jqXHR, textStatus, errorThrown) {
						console.error( 'error AJAX:', textStatus, errorThrown, jqXHR );
					}
				}
			);
		}

		geowidgetModalOrder.close();
	}
}

/* Show map for additional parcel */
document.addEventListener(
	'click',
	function (e) {
		e          = e || window.event;
		var target = e.target || e.srcElement;

		if ( target.classList.contains( 'settings-geowidget' ) ) {
			e.preventDefault();
			e.stopPropagation();

			easypack_current_metabox = jQuery( target ).closest( '.postbox' );
			let config               = jQuery( easypack_current_metabox ).find( '#parcel_machine_id' ).data( 'geowidget_config' );
			console.log( 'map metabox config' );
			console.log( config );

			easypackOpenGeowidgetModal( config );
		}

	},
	false
);

/* Get label for just created additional parcel */
document.addEventListener(
	'click',
	function (e) {
		e          = e || window.event;
		var target = e.target || e.srcElement;
		if (target.hasAttribute( 'id' ) && target.getAttribute( 'id' ) === 'get_sticker_additional_now') {
			e.preventDefault();
			e.stopPropagation();

			var metabox = jQuery( target ).closest( '.postbox' );
			jQuery( metabox ).find( '#easypack_error' ).html( '' );

			var beforeSend = function () {
				var th_spinner = jQuery( metabox ).find( "#easypack_spinner" );
				jQuery( metabox ).find( "#easypack_spinner" ).addClass( "is-active" );
				jQuery( metabox ).find( '#get_sticker_additional_now' ).attr( 'disabled', true );
			};

			var action          = 'easypack';
			var easypack_action = 'easypack_create_additional_label';
			var inpost_id       = target.getAttribute( 'data-id' );
			var order_id        = target.getAttribute( 'data-order-id' );

			beforeSend();
			var request = new XMLHttpRequest();
			request.open( 'POST', ajaxurl, true );
			request.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8' );
			request.responseType = 'blob';

			request.onload = function () {
				// Only handle status code 200
				if (request.status === 200 && request.response.size > 0) {
					var content_type = request.getResponseHeader( "content-type" );
					if (content_type === 'application/pdf') {
						var filename = 'inpost_zamowenie_dp_do_' + order_id + '_' + inpost_id + '.pdf';
						// download file
						var blob      = new Blob( [request.response], {type: 'application/pdf'} );
						var link      = document.createElement( 'a' );
						link.href     = window.URL.createObjectURL( blob );
						link.download = filename;
						document.body.appendChild( link );
						link.click();
						document.body.removeChild( link );
					} else {
						// some error occured
						let text_from_blob = new Blob( [request.response], {type: 'text/html'} );
						var reader         = new FileReader();
						reader.onload      = function () {
							let textResponse = JSON.parse( reader.result );
							console.log( textResponse );
							if (textResponse.details.key == 'ParcelLabelExpired') {
								jQuery( metabox ).find( '#easypack_error' ).html( 'Etykieta wygasła' );
								jQuery( metabox ).find( '#easypack_error' ).css( 'color', '#f00' );
							} else {
								alert( reader.result );
							}
						};
						reader.readAsText( text_from_blob );
						jQuery( metabox ).find( "#easypack_spinner" ).removeClass( "is-active" );
						jQuery( metabox ).find( '#get_sticker_additional_now' ).attr( 'disabled', false );
						return;
					}

					jQuery( metabox ).find( "#easypack_spinner" ).removeClass( "is-active" );
					jQuery( metabox ).find( '#get_sticker_additional_now' ).attr( 'disabled', false );
				} else {
					jQuery( metabox ).find( '#easypack_error' ).html( 'Wystąpił błąd' );
					jQuery( metabox ).find( '#easypack_error' ).css( 'color', '#f00' );
				}

				jQuery( metabox ).find( "#easypack_spinner" ).removeClass( "is-active" );
				jQuery( metabox ).find( '#get_sticker_additional_now' ).attr( 'disabled', false );
			};

			request.send( 'action=' + action + '&easypack_action=' + easypack_action + '&security=' + easypack_nonce + '&inpost_id=' + inpost_id );
		}
	}
);

jQuery( document ).ready(
	function (e) {
		// Safety net if modal was closed in an unexpected way (e.g. orphaned jBox DOM).
		easypackReleaseJboxBodyScroll();

		/* Get labels for existing additional parcels */
		jQuery( '.get_sticker_additional' ).click(
			function (e) {
				e.preventDefault();
				e.stopPropagation();
				var metabox = jQuery( this ).closest( '.postbox' );
				jQuery( metabox ).find( '#easypack_error' ).html( '' );
				var beforeSend = function () {
					jQuery( metabox ).find( "#easypack_spinner" ).addClass( "is-active" );
					jQuery( metabox ).find( '#get_sticker_additional' ).attr( 'disabled', true );
				};

				var action          = 'easypack';
				var easypack_action = 'easypack_create_additional_label';
				var inpost_id       = jQuery( this ).attr( 'data-id' );
				var order_id        = jQuery( this ).attr( 'data-order-id' );

				beforeSend();
				var request = new XMLHttpRequest();
				request.open( 'POST', ajaxurl, true );
				request.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8' );
				request.responseType = 'blob';

				request.onload = function () {
					// Only handle status code 200
					if (request.status === 200 && request.response.size > 0) {

						var content_type = request.getResponseHeader( "content-type" );
						if (content_type === 'application/pdf') {
							var filename = 'inpost_zamowenie_dp_do_' + order_id + '_' + inpost_id + '.pdf';
							// download file
							var blob      = new Blob( [request.response], {type: 'application/pdf'} );
							var link      = document.createElement( 'a' );
							link.href     = window.URL.createObjectURL( blob );
							link.download = filename;
							document.body.appendChild( link );
							link.click();
							document.body.removeChild( link );
						} else {
							// some error occured
							let text_from_blob = new Blob( [request.response], {type: 'text/html'} );
							var reader         = new FileReader();
							reader.onload      = function () {
								let textResponse = JSON.parse( reader.result );
								console.log( textResponse );
								if (textResponse.details.key == 'ParcelLabelExpired') {
									jQuery( metabox ).find( '#easypack_error' ).html( 'Etykieta wygasła' );
									jQuery( metabox ).find( '#easypack_error' ).css( 'color', '#f00' );
								} else {
									alert( reader.result );
								}
							};
							reader.readAsText( text_from_blob );
							jQuery( metabox ).find( "#easypack_spinner" ).removeClass( "is-active" );
							jQuery( metabox ).find( '#get_sticker_additional' ).attr( 'disabled', false );
							return;
						}

						jQuery( metabox ).find( "#easypack_spinner" ).removeClass( "is-active" );
						jQuery( metabox ).find( '#get_sticker_additional' ).attr( 'disabled', false );
					} else {
						jQuery( metabox ).find( '#easypack_error' ).html( 'Wystąpił błąd' );
						jQuery( metabox ).find( '#easypack_error' ).css( 'color', '#f00' );
					}

					jQuery( metabox ).find( "#easypack_spinner" ).removeClass( "is-active" );
					jQuery( metabox ).find( '#get_sticker_additional' ).attr( 'disabled', false );
				};

				request.send( 'action=' + action + '&easypack_action=' + easypack_action + '&security=' + easypack_nonce + '&inpost_id=' + inpost_id );

			}
		);
	}
);