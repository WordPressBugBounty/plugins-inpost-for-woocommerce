/**
 * InPost PL – thank-you page "missed pick-up point" logic, shared by all checkout modes.
 *
 * Used by:
 * - inpost-pl.js            (map button mode off),
 * - front.js                (classic checkout + JS map button),
 * - typ-related-points.js   (Blocks checkout + JS map button).
 * Those scripts only open the Geowidget in their own way and pass the chosen point here.
 *
 * Config: window.easypack_typ_locker = { ajaxurl, security, updated_text, error_text }.
 */
(function (window, document) {
	'use strict';

	if (window.InpostPlTypLocker) {
		return;
	}

	var busy = false;

	function cfg() {
		return window.easypack_typ_locker || {};
	}

	function escapeHtml(value) {
		return String(value === undefined || value === null ? '' : value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function getOrderKey() {
		try {
			return new URLSearchParams(window.location.search).get('key') || '';
		} catch (err) {
			return '';
		}
	}

	function getOrderId() {
		var mapData = document.getElementById('inpost-pl-typ-map-data');
		if (mapData && mapData.getAttribute('data-id')) {
			return mapData.getAttribute('data-id');
		}
		var hidden = document.getElementById('inpost-pl-related-data-order');
		return hidden ? hidden.value : '';
	}

	function isTypPage() {
		return !!(document.getElementById('inpost-pl-typ-map-data') || document.querySelector('.inpost-pl-related-point-btn'));
	}

	function each(selector, fn) {
		Array.prototype.forEach.call(document.querySelectorAll(selector), fn);
	}

	function notice(isSuccess, lockerId) {
		var text = isSuccess ? cfg().updated_text + ': ' + lockerId : cfg().error_text;
		var color = isSuccess ? '#26be22' : '#e03636';
		return '<div class="inpost_pl_locker_changed" style="color:' + color + '; font-weight: bold">' + escapeHtml(text) + '</div>';
	}

	function insertBeforeMapData(html) {
		var anchor = document.getElementById('inpost-pl-typ-map-data');
		if (anchor) {
			anchor.insertAdjacentHTML('beforebegin', html);
		}
	}

	/**
	 * POST the point to update_locker_from_typ_page.
	 *
	 * @param {string}   lockerId
	 * @param {string}   lockerDesc
	 * @param {Function} onDone     Called with (isSuccess).
	 */
	function save(lockerId, lockerDesc, onDone) {
		var c = cfg();
		var body = new URLSearchParams();
		body.append('action', 'update_locker_from_typ_page');
		body.append('order_id', getOrderId());
		body.append('order_key', getOrderKey());
		body.append('inpost_pl_locker', lockerId);
		body.append('inpost_pl_locker_desc', lockerDesc || '');
		body.append('security', c.security || '');

		busy = true;

		window.fetch(c.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		})
			.then(function (response) { return response.json(); })
			.then(function (data) { onDone(!!(data && data.success)); })
			.catch(function () { onDone(false); })
			.then(function () { busy = false; });
	}

	/**
	 * A point chosen on the Geowidget map (any mode).
	 */
	function selectFromMap(pointName, lockerDesc, addressLine1, addressLine2) {
		var idEl = document.getElementById('selected-parcel-locker-pl-id');
		if (!idEl || !pointName) {
			return;
		}

		idEl.textContent = pointName;
		var descEl = document.getElementById('selected-parcel-machine-desc');
		if (descEl) {
			descEl.innerHTML = escapeHtml(addressLine1) + '<br>' + escapeHtml(addressLine2);
		}
		each('.hidden-inpost-pl-typ-data', function (el) { el.style.display = 'block'; });

		each('.inpost_pl_geowidget_related_preloader', function (el) { el.style.display = 'flex'; });
		each('.inpost_pl_locker_changed', function (el) { el.remove(); });
		each('.inpost-pl-related-point-btn', function (el) { el.style.background = '#fff'; });

		var desc = lockerDesc || ((addressLine1 || '') + ' ' + (addressLine2 || '')).trim();

		save(pointName, desc, function (ok) {
			insertBeforeMapData(notice(ok, pointName));
			each('.inpost-pl-typ-preloader', function (el) { el.remove(); });
			each('.inpost_pl_geowidget_related_preloader', function (el) { el.style.display = 'none'; });
		});
	}

	/**
	 * A point chosen from the "nearest points" list.
	 */
	function selectFromList(btn) {
		if (busy) {
			return;
		}

		var lockerId = btn.getAttribute('data-id');
		var preloader = btn.querySelector('.inpost-pl-select-from-points-preloader');

		each('.inpost-pl-related-point-btn', function (el) { el.style.background = '#fff'; });
		each('.hidden-inpost-pl-typ-data', function (el) { el.style.display = 'none'; });
		each('.inpost_pl_locker_changed', function (el) { el.remove(); });
		if (preloader) {
			preloader.style.display = 'block';
		}

		save(lockerId, btn.getAttribute('data-address-id'), function (ok) {
			if (ok) {
				btn.style.background = '#afeaad';
				var info = btn.querySelector('.inpost-pl-related-locker-info');
				if (info) {
					info.insertAdjacentHTML('afterend', notice(true, lockerId));
				}
			}
			insertBeforeMapData(notice(ok, lockerId));
			if (preloader) {
				preloader.style.display = 'none';
			}
		});
	}

	function bindList() {
		document.addEventListener('click', function (e) {
			var btn = e.target && e.target.closest ? e.target.closest('.inpost-pl-related-point-btn') : null;
			if (!btn) {
				return;
			}
			e.preventDefault();
			selectFromList(btn);
		});
	}

	window.InpostPlTypLocker = {
		isTypPage: isTypPage,
		selectFromMap: selectFromMap,
		selectFromList: selectFromList
	};

	bindList();
}(window, document));
