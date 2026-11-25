/**
 * Thank-you page: nearest points list + map fallback for Blocks checkout + JS map button mode.
 *
 * Loaded only when front.js and inpost-pl.js are both skipped (see EasyPack::enqueue_typ_related_points_script).
 * Saving the point and the nearest-points list are handled by typ-locker.js; this file only opens the map.
 */
(function ($) {
	'use strict';

	var inpostjsGeowidgetModalTyp;

	function inpost_pl_typ_get_config() {
		return window.easypack_typ_map || {};
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

		if (inpostjsGeowidgetModalTyp) {
			inpostjsGeowidgetModalTyp.close();
		}

		if (window.InpostPlTypLocker) {
			window.InpostPlTypLocker.selectFromMap(pointName, parcelMachineAddressDesc, addressLine1, addressLine2);
		}
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

	$(function () {
		if (!$('#inpost-pl-typ-map-data').length && !$('.inpost-pl-related-point-btn').length) {
			return;
		}

		inpost_pl_typ_init_geowidget();
	});
}(jQuery));
