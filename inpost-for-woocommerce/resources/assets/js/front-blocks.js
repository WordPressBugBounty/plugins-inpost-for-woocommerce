let inpostPlGeowidgetModalBlock;

/**
 * Validation for GPay/ApplePay
 */
function inpost_pl_validate_parcel_machine_for_gpay_block() {
	const selected_shipping_radio = document.querySelector('#shipping-option .wc-block-components-radio-control__input:checked');

	if (selected_shipping_radio) {
		const id = selected_shipping_radio.value;
		console.log('Shipping method ID:', id);

		if (id.includes('easypack_parcel_machines')) {
			const hidden_input = document.getElementById('inpost-parcel-locker-id');
			if (hidden_input) {
				const paczkomat_id = hidden_input.value;
				console.log('Paczkomat ID:', paczkomat_id);

				if (paczkomat_id.trim() === '') {
					return false;
				}
			}
		}
	}
	return true;
}

/**
 * Get shipping method
 */
function inpost_pl_get_shipping_method_block() {
	let data = {};
	const shipping_block = document.querySelector('.wc-block-components-shipping-rates-control');

	if (shipping_block) {
		const shipping_radio_buttons = shipping_block.querySelectorAll('input[name^="radio-control-"]');
		if (shipping_radio_buttons.length > 0) {
			const checked_radio = shipping_block.querySelector('input[name^="radio-control-"]:checked');
			let method = checked_radio ? checked_radio.value : shipping_radio_buttons[0].value;
			let ship_method_instance_id = '';

			if (method && method.includes(':')) {
				const arr = method.split(':');
				method = arr[0];
				ship_method_instance_id = arr[1];
			}
			data.method = method;
			data.instance_id = ship_method_instance_id;
		}
	}
	return data;
}

/**
 * Change React input value
 */
function inpost_pl_change_react_input_value(input, value) {
	if (input) {
		const nativeInputValueSetter = Object.getOwnPropertyDescriptor(
			window.HTMLInputElement.prototype,
			"value"
		).set;
		nativeInputValueSetter.call(input, value);

		const inputEvent = new Event("input", { bubbles: true });
		input.dispatchEvent(inputEvent);
	}
}

/**
 * Select point callback
 */
function inpost_pl_select_point_callback_blocks(point) {
	let selected_point_data = '';
	let parcel_machine_address_desc = '';
	let address_line1 = '';
	let address_line2 = '';
	let point_name = '';

	if (point) {

		document.querySelectorAll('#easypack_selected_point_data').forEach(el => el.remove());

		if (point.name) {
			point_name = point.name;
			if (point_name.startsWith("PL_")) {
				point_name = point_name.slice(3);
			}
		}

		inpost_pl_change_react_input_value(document.getElementById('inpost-parcel-locker-id'), point_name);

		if (point.address.line2) address_line2 = point.address.line2;
		if (point.address.line1) address_line1 = point.address.line1;

		if (point.location_description) {
			parcel_machine_address_desc = point.location_description;
			selected_point_data = `
                <div class="easypack_selected_point_data" id="easypack_selected_point_data">
                    <div id="selected-parcel-machine-id">${point_name}</div>
                    <span id="selected-parcel-machine-desc">${address_line1}<br>${address_line2}</span><br>
                    <span id="selected-parcel-machine-desc1">(${point.location_description})</span>
                </div>`;
		} else {
			selected_point_data = `
                <div class="easypack_selected_point_data" id="easypack_selected_point_data">
                    <div id="selected-parcel-machine-id">${point_name}</div>
                    <span id="selected-parcel-machine-desc">${address_line1}<br>${address_line2}</span>
                </div>`;
			parcel_machine_address_desc = address_line1 + ' ' + address_line2;
		}

		inpost_pl_change_react_input_value(document.getElementById('inpost-parcel-locker-description'), parcel_machine_address_desc);

		const wrap = document.getElementById('inpost_pl_selected_point_data_wrap');
		if (wrap) {
			wrap.innerHTML = selected_point_data;
			wrap.style.display = 'block';
		}

		const btnLabel = document.getElementById("easypack_block_type_geowidget");
		if (btnLabel) btnLabel.textContent = easypack_block.button_text2;

		// saving locker into session
		const formData = new URLSearchParams();
		formData.append('action', 'inpost_save_to_wc_session');
		formData.append('security', easypack_block.security);
		formData.append('key', 'inpost_pl_wc_paczkomat');
		formData.append('value', point_name);

		fetch(easypack_block.ajaxurl, {
			method: 'POST',
			body: formData,
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		})
			.then(response => response.json())
			.catch(err => console.error("Error response saving paczkomat into session", err));
	}

	if (inpostPlGeowidgetModalBlock) {
		inpostPlGeowidgetModalBlock.close();
	}
}

/**
 * Modal for validation
 */
function inpost_pl_create_validation_modal() {
	const modal = document.createElement('div');
	modal.id = 'inpost_pl_checkout_validation_modal';
	Object.assign(modal.style, {
		display: 'none',
		position: 'fixed',
		top: '0',
		left: '0',
		width: '100%',
		height: '100%',
		backgroundColor: 'rgba(0, 0, 0, 0.5)',
		justifyContent: 'center',
		alignItems: 'center',
		zIndex: '1000'
	});

	const modalContent = document.createElement('div');
	Object.assign(modalContent.style, {
		backgroundColor: 'white',
		width: '90%',
		maxWidth: '300px',
		padding: '20px',
		position: 'relative',
		textAlign: 'center',
		borderRadius: '10px',
		boxShadow: '0px 4px 10px rgba(0, 0, 0, 0.1)'
	});

	const closeSpan = document.createElement('span');
	closeSpan.id = 'inp_pl_close_modal_cross';
	closeSpan.textContent = '×';
	Object.assign(closeSpan.style, {
		position: 'absolute',
		top: '10px',
		right: '15px',
		fontSize: '20px',
		cursor: 'pointer'
	});

	const messageDiv = document.createElement('div');
	messageDiv.textContent = 'Musisz wybrać paczkomat.';
	Object.assign(messageDiv.style, { margin: '20px 0', fontSize: '18px' });

	const okButton = document.createElement('button');
	okButton.id = 'inp_pl_close_modal_button';
	okButton.textContent = 'Ok';
	Object.assign(okButton.style, {
		padding: '10px 20px',
		backgroundColor: '#FFA900',
		color: 'white',
		border: 'none',
		borderRadius: '5px',
		cursor: 'pointer',
		fontSize: '16px'
	});

	modalContent.appendChild(closeSpan);
	modalContent.appendChild(messageDiv);
	modalContent.appendChild(okButton);
	modal.appendChild(modalContent);

	return modal;
}

// On page load
document.addEventListener('DOMContentLoaded', function () {
	const validation_modal = inpost_pl_create_validation_modal();
	document.body.appendChild(validation_modal);

	document.getElementById('inp_pl_close_modal_cross')?.addEventListener('click', inpost_pl_close_validation_modal);
	document.getElementById('inp_pl_close_modal_button')?.addEventListener('click', inpost_pl_close_validation_modal);

	setTimeout(function () {
		const token = easypack_block.geowidget_v5_token;
		const shipping_data = inpost_pl_get_shipping_method_block();
		const wH = window.innerHeight - 80;

		let config = inpost_pl_get_map_config_based_on_instance_id(shipping_data.instance_id, shipping_data.method);

		// jBox init
		inpostPlGeowidgetModalBlock = new jBox(
			'Modal',
			window.EasypackGeowidgetModalA11y.getOptions({
				width: 800,
				height: wH,
				attach: '#eqasypack_show_geowidget',
				title: 'Wybierz paczkomat',
				content: `<inpost-geowidget id="inpost-geowidget" onpoint="inpost_pl_select_point_callback_blocks" token="${token}" language="pl" config="${config}"></inpost-geowidget>`
			})
		);

		// On shipping method change
		document.addEventListener('change', function (e) {
			if (e.target && e.target.name && e.target.name.startsWith('radio-control-')) {
				const parent = document.getElementById("shipping-option");
				if (parent && parent.contains(e.target) && e.target.checked) {

					const wrap = document.getElementById('inpost_pl_selected_point_data_wrap');
					if (wrap) wrap.style.display = 'none';

					inpost_pl_change_react_input_value(document.getElementById('inpost-parcel-locker-id'), '');
					inpost_pl_change_react_input_value(document.getElementById('inpost-parcel-locker-description'), '');

					let newConfig = 'parcelCollect';
					const shipping_method_data = e.target.id;
					if (shipping_method_data) {
						const method_data = shipping_method_data.split(":");
						const instance_id = method_data[method_data.length - 1];
						const method_id = method_data[0];
						newConfig = inpost_pl_get_map_config_based_on_instance_id(instance_id, method_id);
					}

					const map_content = `<inpost-geowidget id="inpost-geowidget" onpoint="inpost_pl_select_point_callback_blocks" token="${token}" language="pl" config="${newConfig}"></inpost-geowidget>`;
					if (inpostPlGeowidgetModalBlock) {
						inpostPlGeowidgetModalBlock.setContent(map_content);
					}
				}
			}
		});

	}, 1200);
});

// On click
document.addEventListener('click', function (e) {
	const target = e.target;

	// map button click
	if (target.id === 'easypack_block_type_geowidget') {
		e.preventDefault();
		if (inpostPlGeowidgetModalBlock) {
			const checked_radio = document.querySelector('input[name^="radio-control-"]:checked');
			if (checked_radio) {
				const method_data = checked_radio.id.split(":");
				const instance_id = method_data[method_data.length - 1];
				const method_id = method_data[0];
				const token = easypack_block.geowidget_v5_token;
				const newConfig = inpost_pl_get_map_config_based_on_instance_id(instance_id, method_id);

				const map_content = `<inpost-geowidget id="inpost-geowidget" onpoint="inpost_pl_select_point_callback_blocks" token="${token}" language="pl" config="${newConfig}"></inpost-geowidget>`;
				inpostPlGeowidgetModalBlock.setContent(map_content);
			}

			if (!inpostPlGeowidgetModalBlock.isOpen) {
				inpostPlGeowidgetModalBlock.open();
			}
		}
	}

	// validation
	if (target.closest('.wc-block-components-checkout-place-order-button') ||
		target.classList.contains('wc-block-components-checkout-place-order-button') ||
		target.classList.contains('wc-block-checkout__actions_row')) {

		const reactjs_input = document.getElementById('inpost-parcel-locker-id');
		if (reactjs_input && !reactjs_input.value) {
			inpost_pl_open_validation_modal();
		}
	}
});

function inpost_pl_open_validation_modal() {
	const modal = document.getElementById('inpost_pl_checkout_validation_modal');
	if (modal) modal.style.display = 'flex';
}

function inpost_pl_close_validation_modal() {
	const modal = document.getElementById('inpost_pl_checkout_validation_modal');
	if (modal) modal.style.display = 'none';

	const scrollToElement = document.getElementById('easypack_block_type_geowidget');
	if (scrollToElement) {
		scrollToElement.scrollIntoView({ behavior: 'smooth' });
	}
}

function inpost_pl_get_map_config_based_on_instance_id(instance_id, method) {
	let map_config = 'parcelCollect';
	const inpost_methods = inpost_pl_get_configured_inpost_methods();

	if (instance_id) {
		const selected_method = inpost_methods[instance_id];
		if (selected_method) {
			const method_id = selected_method.inpost_title;
			if (method_id === 'easypack_parcel_machines_cod') map_config = 'parcelCollectPayment';
			else if (method_id === 'easypack_shipping_courier_c2c') map_config = 'parcelSend';
			else if (method_id === 'easypack_parcel_machines_weekend' || method_id === 'easypack_parcel_machines_weekend_cod') map_config = 'parcelCollect247';
		}
	} else {
		if (method === 'easypack_parcel_machines_cod') map_config = 'parcelCollectPayment';
		else if (method === 'easypack_shipping_courier_c2c') map_config = 'parcelSend';
		else if (method === 'easypack_parcel_machines_weekend' || method === 'easypack_parcel_machines_weekend_cod') map_config = 'parcelCollect247';
	}
	return map_config;
}

function inpost_pl_get_configured_inpost_methods() {
	return (typeof wcSettings !== 'undefined' && wcSettings?.inpost_pl_block_data?.configured_methods)
		? wcSettings.inpost_pl_block_data.configured_methods
		: [];
}

/**
 * Validation for GPay
 */
window.addEventListener('message', function (event) {
	try {
		const parsedData = (typeof event.data === 'string') ? JSON.parse(event.data) : event.data;

		if (parsedData.type === "parent" &&
			parsedData.message?.action === "stripe-frame-event" &&
			parsedData.message.payload?.event === "click") {

			const paymentType = parsedData.message.payload.data?.paymentMethodType;
			if (["google_pay", "apple_pay", "apple_pay_inner"].includes(paymentType)) {
				if (!inpost_pl_validate_parcel_machine_for_gpay_block()) {
					alert('Wygląda na to, że zapomniałeś wybrać paczkomat.\n\nJeśli tak, zamknij okno modalne, wybierz punkt za pomocą приcisku "Wybierz punkt odbioru", a następnie wróć do płatności.');
					return false;
				}
			}
		}
	} catch (err) {}
});