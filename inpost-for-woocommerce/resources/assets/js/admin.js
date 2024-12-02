(function ($) {
	$( document ).ready(
		function () {

			var mediaUploader;

			$( '.woo-inpost-logo-upload-btn' ).on(
				'click',
				function (e) {
					e.preventDefault();

					if ( mediaUploader ) {
						mediaUploader.open();
						return;
					}

					mediaUploader = wp.media.frames.file_frame = wp.media(
						{
							title: 'Choose a shipping method logo',
							button: {
								text: 'Choose Image'
							},
							multiple:false
						}
					);

					mediaUploader.on(
						'select',
						function () {
							var attachment = mediaUploader.state().get( 'selection' ).first().toJSON();
							$( '#woocommerce_easypack_logo_upload' ).val( attachment.url );
							$( '#woo-inpost-logo-preview' ).attr( 'src',attachment.url );
							$( '#woo-inpost-logo-preview' ).css( 'display', 'block' );
							$( '#woo-inpost-logo-action' ).css( 'display', 'block' );
						}
					);
					mediaUploader.open();
				}
			);

			$( '#woo-inpost-logo-delete' ).on(
				'click',
				function (e) {
					e.preventDefault();
					$( '#woo-inpost-logo-preview' ).css( 'display', 'none' );
					$( '#woocommerce_easypack_logo_upload' ).val( '' );
					$( '#woo-inpost-logo-action' ).css( 'display', 'none' );
				}
			);

			function easypack_dispatch_point() {
				if ( $( '#easypack_api_country' ).val() === 'pl' || $( '#easypack_api_country' ).val() === 'test-pl' ) {
					$( '#easypack_dispatch_point_name' ).closest( 'table' ).prev().css( 'display','block' );
					$( '#easypack_dispatch_point_name' ).closest( 'table' ).css( 'display','table' );
					$( '#easypack_crossborder_password' ).closest( 'tr' ).css( 'display','table-row' );
				} else {
					$( '#easypack_dispatch_point_name' ).closest( 'table' ).prev().css( 'display','none' );
					$( '#easypack_dispatch_point_name' ).closest( 'table' ).css( 'display','none' );
					$( '#easypack_crossborder_api_url' ).closest( 'tr' ).css( 'display','none' );
					$( '#easypack_crossborder_password' ).closest( 'tr' ).css( 'display','none' );
					$( '#easypack_default_package_size' ).val( 'A' );
					$( '#easypack_default_package_size' ).closest( 'tr' ).css( 'display','none' );
				}
			}
			easypack_dispatch_point();

			$( '#easypack_api_country' ).change(
				function () {
					var url = 'https://api-' + $( '#easypack_api_country' ).val() + '.easypack24.net';
					url     = url.replace( 'api-test', 'test-api' );
					$( '#easypack_api_url' ).val( url );
					easypack_dispatch_point();

					if ($( '#easypack_api_country' ).val() === 'gb') {
						$( '#easypack_api_url' ).val( 'https://sandbox-api-shipx-pl.easypack24.net' );
					}
				}
			);

			$( '.easypack_parcel' ).click(
				function () {

					var allowReturnStickers = $( this ).data( 'allow_return_stickers' ) === 1;
					if ($( this ).is( ':checked' )) {
						if (false === allowReturnStickers) {
							$( '#get_return_stickers' ).prop( 'disabled', true );
						}
					} else {
						if (false === allowReturnStickers) {
							$( '#get_return_stickers' ).removeAttr( 'disabled' );
						}
					}
				}
			);

			$( '#woo_inpost_dpoint_add' ).click(
				function (e) {
					e.preventDefault();
					var cloned = $( '#woo_inpost_dpoint-cell .woo_inpost_dpoint-cell-wraper:last' ).clone();
					$( '#woo_inpost_dpoint-cell' ).append( cloned );

					$( '.woo_inpost_dpoint_selected' ).each(
						function (index, obj) {
							$( this ).val( index );
						}
					);

				}
			);

			document.addEventListener(
				'click',
				function (e) {
					e          = e || window.event;
					var target = e.target || e.srcElement;

					if ( target.classList.contains( 'woo_inpost_dpoint-remove' ) ) {
						target.parentNode.parentNode.parentNode.removeChild( target.parentNode.parentNode );
					}

				},
				false
			);

			// integration with Flexible Shipping.
			let fs_integration_select            = $( '#woocommerce_flexible_shipping_fs_inpost_pl_method' );
			let fs_integration_insurance_field_1 = $( 'label[for="woocommerce_flexible_shipping_fs_insurance_inpost_pl"]' );
			let fs_integration_insurance_field_2 = $( 'label[for="woocommerce_flexible_shipping_fs_insurance_value_inpost_pl"]' );

			if ($( fs_integration_select ).val() !== '0') {
				$( fs_integration_insurance_field_1 ).closest( 'tr' ).show();
				$( fs_integration_insurance_field_2 ).closest( 'tr' ).show();
			} else {
				$( fs_integration_insurance_field_1 ).closest( 'tr' ).hide();
				$( fs_integration_insurance_field_2 ).closest( 'tr' ).hide();
			}

			if ($( fs_integration_select ).val() !== 'easypack_parcel_machines_weekend' || $( fs_integration_select ).val() !== 'easypack_parcel_machines_weekend_cod') {
				$( '.fs-inpost-pl-weekend' ).each(
					function (i,elem) {
						$( elem ).closest( 'tr' ).hide();
					}
				);
			}

			$( fs_integration_select ).on(
				'change',
				function () {
					if ($( this ).val() === 'easypack_parcel_machines_weekend' || $( this ).val() === 'easypack_parcel_machines_weekend_cod') {

						$( '.fs-inpost-pl-weekend' ).each(
							function (i,elem) {
								$( elem ).closest( 'tr' ).show();
							}
						);

					} else {

						$( '.fs-inpost-pl-weekend' ).each(
							function (i,elem) {
								$( elem ).closest( 'tr' ).hide();
							}
						);
					}

					if ($( this ).val() !== '0') {
						$( '#woocommerce_flexible_shipping_method_integration' ).val( '' ).trigger( 'change' );
						$( fs_integration_insurance_field_1 ).closest( 'tr' ).show();
						$( fs_integration_insurance_field_2 ).closest( 'tr' ).show();
					} else {
						$( fs_integration_insurance_field_1 ).closest( 'tr' ).hide();
						$( fs_integration_insurance_field_2 ).closest( 'tr' ).hide();
					}

					$( '#woocommerce_flexible_shipping_method_integration' ).on(
						'change',
						function () {
							if ($( this ).val()) {
								$( fs_integration_select ).val( '0' ).trigger( 'change' );
							}
						}
					)
				}
			);

		}
	);
})( jQuery );