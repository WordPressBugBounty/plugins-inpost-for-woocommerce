/**
 * Thank-you page: nearest points list + map fallback for Blocks checkout + JS map button mode.
 *
 * Loaded only when front.js and inpost-pl.js are both skipped (see EasyPack::enqueue_typ_related_points_script).
 */
(function ($) {
	'use strict';

	var inpostjsGeowidgetModalTyp;
	var inpost_pl_is_ajax_get_point_running = false;

	function inpost_pl_typ_get_config() {
		return window.easypack_typ_map || {};
	}

	function inpost_pl_typ_update_locker(orderId, orderKey, lockerId, lockerDesc, onComplete) {
		var cfg = inpost_pl_typ_get_config();

		$.ajax({
			type: 'POST',
			url: cfg.ajaxurl,
			data: {
				action: 'update_locker_from_typ_page',
				order_id: orderId,
				order_key: orderKey || '',
				inpost_pl_locker: lockerId,
				inpost_pl_locker_desc: lockerDesc,
				security: cfg.security,
			},
			dataType: 'json',
			beforeSend: function () {
				inpost_pl_is_ajax_get_point_running = true;
				$('.inpost_pl_geowidget_related_preloader').css('display', 'flex');
				$('.inpost_pl_locker_changed').remove();
				$('.inpost-pl-related-point-btn').css('background', '#fff');
			},
			success: function (data) {
				var message;
				var isSuccess = data && data.success;

				if (isSuccess) {
					message = '<div class="inpost_pl_locker_changed" style="color:#26be22; font-weight: bold">' +
						cfg.updated_text + ': ' + lockerId + '</div>';
				} else {
					message = '<div class="inpost_pl_locker_changed" style="color:#e03636; font-weight: bold">' +
						cfg.error_text + '</div>';
				}

				$('#inpost-pl-typ-map-data').before(message);

				if (typeof onComplete === 'function') {
					onComplete(isSuccess);
				}
			},
			error: function () {
				$('#inpost-pl-typ-map-data').before(
					'<div class="inpost_pl_locker_changed" style="color:#e03636; font-weight: bold">' +
						cfg.error_text + '</div>'
				);
				if (typeof onComplete === 'function') {
					onComplete(false);
				}
			},
			complete: function () {
				inpost_pl_is_ajax_get_point_running = false;
				$('.inpost-pl-typ-preloader').remove();
				$('.inpost_pl_geowidget_related_preloader').css('display', 'none');
			},
		});
	}

	window.inpost_pl_typ_select_point_callback = function (point) {
		var cfg = inpost_pl_typ_get_config();
		var pointName = '';
		var parcelMachineAddressDesc = '';
		var addressLine1 = '';
		var addressLine2 = '';

		if (!point) {
			return;
		}

		if ('name' in point) {
			pointName = point.name;
			if (pointName.indexOf('PL_') === 0) {
				pointName = pointName.slice(3);
			}
		}

		if (typeof point.location_description !== 'undefined' && point.location_description !== null) {
			parcelMachineAddressDesc = point.location_description;
		}
		if (typeof point.address.line1 !== 'undefined' && point.address.line1 !== null) {
			addressLine1 = point.address.line1;
		}
		if (typeof point.address.line2 !== 'undefined' && point.address.line2 !== null) {
			addressLine2 = point.address.line2;
		}

		if ($('#selected-parcel-locker-pl-id').length) {
			$('#selected-parcel-locker-pl-id').html(pointName);
			$('#selected-parcel-machine-desc').html(addressLine1 + '<br>' + addressLine2);
			$('.hidden-inpost-pl-typ-data').css('display', 'block');
		}

		if (inpostjsGeowidgetModalTyp) {
			inpostjsGeowidgetModalTyp.close();
		}

		var typMapData = $('#inpost-pl-typ-map-data');
		if (!typMapData.length) {
			return;
		}

		var lockerDesc = parcelMachineAddressDesc || (addressLine1 + ' ' + addressLine2).trim();

		inpost_pl_typ_update_locker(
			typMapData.attr('data-id'),
			'',
			pointName,
			lockerDesc
		);
	};

	function inpost_pl_typ_init_geowidget() {
		var typButton = document.querySelector('.inpost_pl_geowidget_typ');
		var cfg = inpost_pl_typ_get_config();

		if (!typButton || typeof jBox === 'undefined' || !cfg.geowidget_v5_token) {
			return;
		}

		var wH = $(window).height() - 80;
		var mapContent = '<inpost-geowidget id="inpost-geowidget-typ" onpoint="inpost_pl_typ_select_point_callback" token="' +
			cfg.geowidget_v5_token + '" language="pl" config="parcelCollect"></inpost-geowidget>';

		inpostjsGeowidgetModalTyp = new jBox(
			'Modal',
			window.EasypackGeowidgetModalA11y.getOptions(
				{
					width: 800,
					height: wH,
					attach: '#easypack_show_geowidget',
					title: 'Wybierz paczkomat',
					content: mapContent,
				}
			)
		);

		$(document.body).on('click', '.inpost_pl_geowidget_typ', function (e) {
			e.preventDefault();
			if (inpostjsGeowidgetModalTyp && !inpostjsGeowidgetModalTyp.isOpen) {
				inpostjsGeowidgetModalTyp.open();
			}
		});
	}

	function inpost_pl_typ_init_related_points() {
		$(document.body).on('click', '.inpost-pl-related-point-btn', function (e) {
			e.preventDefault();

			var $btn = $(this);
			var cfg = inpost_pl_typ_get_config();
			var orderKey = '';

			if (inpost_pl_is_ajax_get_point_running) {
				return false;
			}

			try {
				orderKey = new URLSearchParams(window.location.search).get('key') || '';
			} catch (err) {
				orderKey = '';
			}

			$('.inpost-pl-related-point-btn').css('background', '#fff');

			$.ajax({
				type: 'POST',
				url: cfg.ajaxurl,
				data: {
					action: 'update_locker_from_typ_page',
					order_id: $('#inpost-pl-related-data-order').val(),
					order_key: orderKey,
					inpost_pl_locker: $btn.attr('data-id'),
					inpost_pl_locker_desc: $btn.attr('data-address-id'),
					security: cfg.security,
				},
				dataType: 'json',
				beforeSend: function () {
					inpost_pl_is_ajax_get_point_running = true;
					$('.hidden-inpost-pl-typ-data').css('display', 'none');
					$btn.find('.inpost-pl-select-from-points-preloader').css('display', 'block');
					$('.inpost_pl_locker_changed').remove();
				},
				success: function (data) {
					if (data && data.success) {
						$btn.css('background', '#afeaad');
						$btn.find('.inpost-pl-related-locker-info').after(
							'<div class="inpost_pl_locker_changed" style="color:#26be22; font-weight: bold">' +
								cfg.updated_text + ': ' + $btn.attr('data-id') + '</div>'
						);
						$('#inpost-pl-typ-map-data').before(
							'<div class="inpost_pl_locker_changed" style="color:#26be22; font-weight: bold">' +
								cfg.updated_text + ': ' + $btn.attr('data-id') + '</div>'
						);
					} else {
						$('#inpost-pl-typ-map-data').before(
							'<div class="inpost_pl_locker_changed" style="color:#e03636; font-weight: bold">' +
								cfg.error_text + '</div>'
						);
					}
				},
				error: function () {
					$('#inpost-pl-typ-map-data').before(
						'<div class="inpost_pl_locker_changed" style="color:#e03636; font-weight: bold">' +
							cfg.error_text + '</div>'
					);
				},
				complete: function () {
					inpost_pl_is_ajax_get_point_running = false;
					$btn.find('.inpost-pl-select-from-points-preloader').css('display', 'none');
				},
			});

			return false;
		});
	}

	$(function () {
		if (!$('#inpost-pl-typ-map-data').length && !$('.inpost-pl-related-point-btn').length) {
			return;
		}

		inpost_pl_typ_init_related_points();
		inpost_pl_typ_init_geowidget();
	});
}(jQuery));
