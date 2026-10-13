var inpost_pl_geowidget_default_config;

function inpost_pl_select_default_point_callback(point) {

	console.log( 'InPost PL plugin configuartion map callback' );

	let point_name = '';
	if ('name' in point) {
		point_name = point.name;
		if (point_name.startsWith( "PL_" )) {
			// Remove first 3 characters "PL_".
			point_name = point_name.slice( 3 );
		}
	}

	console.log( 'InPost PL default point: ' + point_name );

	let point_input = document.getElementById( 'easypack_default_machine_id' );
	if (typeof point_input != 'undefined' && point_input !== null) {
		point_input.value = point_name;
	}

	if (typeof inpost_pl_geowidget_default_config != 'undefined' && inpost_pl_geowidget_default_config !== null) {
		inpost_pl_geowidget_default_config.close();
	}
}

(function ($) {

	$( document ).ready(
		function () {

			let insurance_mode         = $( 'input[name="easypack_insurance_amount_mode"]:checked' ).val();
			let insurance_input        = $( 'input[name="easypack_insurance_amount_default"]' );
			let insurance_input_parent = $( insurance_input ).closest( 'tr' );
			let webhook_url            = easypack_settings.webhook_url;
			$( 'input[name="easypack_enable_webhooks_url"]' ).val( webhook_url );

			if ('2' === insurance_mode) {
				$( insurance_input_parent ).show();
				$( insurance_input ).attr( "required", true );
			} else {
				$( insurance_input_parent ).hide();
				$( insurance_input ).attr( "required", false );
			}

			$( 'input[name="easypack_insurance_amount_mode"]' ).on(
				"change",
				function () {
					if ('2' === $( this ).val()) {
						$( insurance_input_parent ).show();
						$( insurance_input ).attr( "required", true );
					} else {
						$( insurance_input_parent ).hide();
						$( insurance_input ).attr( "required", false );
					}
				}
			)

			let debug_text = '';
			if (typeof easypack_settings.debug_notice != 'undefined' && easypack_settings.debug_notice !== null) {
				debug_text = '<p class="easypack_debug_notice">' + easypack_settings.debug_notice + '</p>';
			}

			if ($( '#easypack_js_map_button' ).is( ':checked' )) {
				$( '#easypack_button_output' ).prop( 'disabled', true );
				$( '#easypack_button_output' ).closest( '.forminp-select' ).append( debug_text );
			} else {
				$( '#easypack_button_output' ).prop( 'disabled', false );
				$( '.easypack_debug_notice' ).each(
					function (ind, elem) {
						$( elem ).remove();
					}
				);
			}

			$( '#easypack_js_map_button' ).on(
				'change',
				function () {
					if ($( this ).is( ':checked' )) {
						$( '#easypack_button_output' ).prop( 'disabled', true );
						$( '#easypack_button_output' ).closest( '.forminp-select' ).append( debug_text );

					} else {
						$( '#easypack_button_output' ).prop( 'disabled', false );
						$( '.easypack_debug_notice' ).each(
							function (ind, elem) {
								$( elem ).remove();
							}
						);
					}
				}
			);

			if ( ! $( '#easypack_set_default_courier_dimensions' ).is( ':checked' )) {
				$( '.easypack_hidden_setting' ).each(
					function (i, elem) {
						let parent = $( elem ).closest( 'tr' );
						$( parent ).css( 'display', 'none' );
					}
				);
			}

			$( '#easypack_set_default_courier_dimensions' ).on(
				'change',
				function () {
					if ($( this ).is( ':checked' )) {
						$( '.easypack_hidden_setting' ).each(
							function (i, elem) {
								let parent = $( elem ).closest( 'tr' );
								$( parent ).fadeIn( 300 );
							}
						);
					} else {
						$( '.easypack_hidden_setting' ).each(
							function (i, elem) {
								let parent = $( elem ).closest( 'tr' );
								$( parent ).fadeOut( 100 );
							}
						);
					}
				}
			);

			$( '#easypack_api_url' ).closest( 'tr' ).css( 'display', 'none' );
			$( '#easypack_geowidget_url' ).closest( 'tr' ).css( 'display', 'none' );

			let webhook_setting = $( 'input[name="easypack_enable_webhooks"]' );
			if (typeof webhook_setting != 'undefined' && webhook_setting !== null) {
				if ($( webhook_setting ).is( ':checked' )) {
					$( '.easypack_hidden_setting_webhook' ).each(
						function (i, elem) {
							let parent = $( elem ).closest( 'tr' );
							$( parent ).show();
						}
					);
				} else {
					$( '.easypack_hidden_setting_webhook' ).each(
						function (i, elem) {
							let parent = $( elem ).closest( 'tr' );
							$( parent ).hide();
						}
					);
				}

				$( 'input[name="easypack_enable_webhooks"]' ).on(
					"change",
					function () {
						if ($( this ).is( ':checked' )) {
							$( '.easypack_hidden_setting_webhook' ).each(
								function (i, elem) {
									let parent = $( elem ).closest( 'tr' );
									$( parent ).fadeIn( 300 );
								}
							);
						} else {
							$( '.easypack_hidden_setting_webhook' ).each(
								function (i, elem) {
									let parent = $( elem ).closest( 'tr' );
									$( parent ).fadeOut( 100 );
								}
							);
						}
					}
				)
			}

			function easypack_show_tooltip(input, maxValue) {
				const $input   = $( input );
				const $tooltip = $( '<div class="inpost-pl-max-value-tooltip">Maximum value is ' + maxValue + '</div>' );
				$tooltip.css(
					{
						position: 'absolute',
						background: '#333',
						color: '#fff',
						padding: '5px 10px',
						borderRadius: '3px',
						fontSize: '12px',
						zIndex: 1000
					}
				);

				const inputPos = $input.offset();
				$tooltip.css(
					{
						top: inputPos.top - 30,
						left: inputPos.left
					}
				);

				$( 'body' ).append( $tooltip );

				setTimeout(
					function () {
						$tooltip.fadeOut(
							300,
							function () {
								$( this ).remove();
							}
						);
					},
					3000
				);
			}

			$( '#easypack_organization_id' ).change(
				function () {
					$( '#easypack_api_change' ).val( '1' );
				}
			);
			$( '#easypack_api_environment' ).change(
				function () {
					$( '#easypack_api_change' ).val( '1' );
				}
			);
			$( '#easypack_token' ).change(
				function () {
					$( '#easypack_api_change' ).val( '1' );
				}
			);

			$( '#easypack_token' ).keyup(
				function () {
					if (easypack_token !== $( '#easypack_token' ).val()) {
						$( '#easypack_api_change' ).val( '1' );
					}
				}
			);
			var easypack_token = $( '#easypack_token' ).val();

			config = $( '#easypack_default_machine_id' ).data( 'geowidget_config' );

			inpost_pl_geowidget_default_config = new jBox(
				'Modal',
				{
					width: easypackAdminGeowidgetSettings.width,
					height: easypackAdminGeowidgetSettings.height,
					attach: '#easypack_default_machine_id',
					title: easypackAdminGeowidgetSettings.title,
					content: '<inpost-geowidget onpoint="inpost_pl_select_default_point_callback" token="' + easypackAdminGeowidgetSettings.token + '" language="pl" config="' + config + '"></inpost-geowidget>'
				}
			);

			$( '#easypack_default_machine_id' ).click(
				function (e) {
					e.preventDefault();
					if (typeof inpost_pl_geowidget_default_config != 'undefined' && inpost_pl_geowidget_default_config !== null) {
						if ( ! inpost_pl_geowidget_default_config.isOpen) {
							inpost_pl_geowidget_default_config.open();
						}
					}
				}
			);

			$( '.easypack_courier_tmplts_dmtemplate' ).on(
				'input change',
				function () {
					const $input   = $( this );
					const maxValue = parseFloat( $input.attr( 'max' ) );
					const value    = parseFloat( $input.val() );

					if (value > maxValue) {
						$input.val( maxValue );
						easypack_show_tooltip( this, maxValue );
					}
				}
			);

			function easypack_show_courier_dimensions_cm_notice() {
				if ( $( '#easypack-courier-dimensions-cm-notice' ).length ) {
					return;
				}

				const message = ( typeof easypack_settings !== 'undefined' && easypack_settings.courier_dimensions_cm_notice )
					? easypack_settings.courier_dimensions_cm_notice
					: 'If you change this setting, please note: existing template and default dimension values are not converted automatically. Please update them to the correct values.';
				const buttonLabel = ( typeof easypack_settings !== 'undefined' && easypack_settings.courier_dimensions_cm_notice_btn )
					? easypack_settings.courier_dimensions_cm_notice_btn
					: 'I understand';

				const $overlay = $(
					'<div id="easypack-courier-dimensions-cm-notice" style="position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:100000;display:flex;align-items:center;justify-content:center;">' +
						'<div style="background:#fff;max-width:480px;width:90%;padding:24px;border-radius:4px;box-shadow:0 4px 20px rgba(0,0,0,0.2);">' +
							'<p style="margin:0 0 20px;font-size:14px;line-height:1.5;color:#1d2327;">' + message + '</p>' +
							'<div style="text-align:right;">' +
								'<button type="button" class="button button-primary easypack-courier-dimensions-cm-notice-close">' + buttonLabel + '</button>' +
							'</div>' +
						'</div>' +
					'</div>'
				);

				$( 'body' ).append( $overlay );

				$overlay.on(
					'click',
					'.easypack-courier-dimensions-cm-notice-close',
					function ( e ) {
						e.preventDefault();
						$overlay.remove();
					}
				);
			}

			function easypack_update_courier_dimension_labels( isCm ) {
				if ( typeof easypack_settings === 'undefined' || ! easypack_settings.dim_labels ) {
					return;
				}

				const unit   = isCm ? 'cm' : 'mm';
				const labels = easypack_settings.dim_labels[ unit ];
				const maxes  = ( easypack_settings.dim_max && easypack_settings.dim_max[ unit ] )
					? easypack_settings.dim_max[ unit ]
					: null;

				if ( ! labels ) {
					return;
				}

				$( '#easypack_courier_tmplts_ctemplate-cell th[data-easypack-dim-header="length"]' ).text( labels.length );
				$( '#easypack_courier_tmplts_ctemplate-cell th[data-easypack-dim-header="width"]' ).text( labels.width );
				$( '#easypack_courier_tmplts_ctemplate-cell th[data-easypack-dim-header="height"]' ).text( labels.height );

				$( 'label[for="easypack_default_courier_dimensions[length]"]' ).text( labels.box_length );
				$( 'label[for="easypack_default_courier_dimensions[width]"]' ).text( labels.box_width );
				$( 'label[for="easypack_default_courier_dimensions[height]"]' ).text( labels.box_height );

				if ( maxes ) {
					$( '#easypack_courier_tmplts_ctemplate-cell input[data-id="length"]' ).attr( 'max', maxes.length );
					$( '#easypack_courier_tmplts_ctemplate-cell input[data-id="width"]' ).attr( 'max', maxes.width );
					$( '#easypack_courier_tmplts_ctemplate-cell input[data-id="height"]' ).attr( 'max', maxes.height );
				}
			}

			$( '#easypack_courier_dimensions_in_cm' ).on(
				'change',
				function () {
					easypack_update_courier_dimension_labels( $( this ).is( ':checked' ) );
					easypack_show_courier_dimensions_cm_notice();
				}
			);

			const copyBtn = document.getElementById( 'inpost-copy-webhook-url-btn' );
			const input   = document.getElementById( 'easypack_enable_webhooks_url' );
			const tooltip = document.getElementById( 'copy-tooltip' );

			if (copyBtn && input && tooltip) {
				copyBtn.addEventListener(
					'click',
					function () {
						// Create a temporary input to copy the value.
						const tempInput = document.createElement( 'input' );
						tempInput.value = input.value;
						document.body.appendChild( tempInput );
						tempInput.select();
						tempInput.setSelectionRange( 0, 99999 );

						try {
							document.execCommand( 'copy' );
							copyBtn.classList.add( 'copied' );
							tooltip.textContent = easypack_settings.webhook_notice;

							// Reset after 2 seconds.
							setTimeout(
								function () {
									copyBtn.classList.remove( 'copied' );
									tooltip.textContent = '';
								},
								2000
							);

						} catch (err) {
							console.error( 'Failed to copy: ', err );
							tooltip.textContent = 'Failed to copy';

							setTimeout(
								function () {
									tooltip.textContent = 'Copy';
								},
								2000
							);
						}

						// Remove temporary input.
						document.body.removeChild( tempInput );
					}
				);
			}

		}
	);

})( jQuery );


function inpost_pl_config_wait_for_element(selector) {
	return new Promise(
		function (resolve) {
			if (document.querySelector( selector )) {
				return resolve( document.querySelector( selector ) );
			}

			const observer = new MutationObserver(
				function (mutations) {
					if (document.querySelector( selector )) {
						resolve( document.querySelector( selector ) );
						observer.disconnect();
					}
				}
			);

			observer.observe(
				document.body,
				{
					childList: true,
					subtree: true
				}
			);
		}
	);
}
