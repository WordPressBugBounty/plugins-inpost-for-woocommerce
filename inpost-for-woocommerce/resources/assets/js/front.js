/**
 * Global variables
 */
var inpostjsGeowidgetModal;
let inpost_pl_is_ajax_get_point_running = false;

/**
 * Get the currently selected shipping method from the DOM.
 * @returns {Object} Data containing method and postfix.
 */
function inpost_pl_get_shipping_method_js_mode() {
    let data = {};
    const checkedInput = document.querySelector('input[name^="shipping_method"]:checked');
    const firstInput = document.querySelector('input[name^="shipping_method"]');

    let method = checkedInput ? checkedInput.value : (firstInput ? firstInput.value : undefined);
    let postfix = '';

    if (method && method.indexOf(':') > -1) {
        const arr = method.split(':');
        method = arr[0];
        postfix = arr[1];
    }
    data.method = method;
    data.postfix = postfix;

    return data;
}

/**
 * Callback function triggered when a point is selected on the geowidget.
 * @param {Object} point Selected point data.
 */
function inpost_pl_select_point_callback_js_mode(point) {
    let selected_point_data = '';
    let parcelMachineAddressDesc = '';
    let address_line1 = '';
    let address_line2 = '';
    let point_name = '';

    if (point && 'name' in point) {
        point_name = point.name;
        if (point_name.startsWith("PL_")) {
            point_name = point_name.slice(3);
        }
    }

    if (point.location_description) parcelMachineAddressDesc = point.location_description;
    if (point.address && point.address.line1) address_line1 = point.address.line1;
    if (point.address && point.address.line2) address_line2 = point.address.line2;

    const commonHiddenFields = `
        <input type="hidden" id="parcel_machine_id" name="parcel_machine_id" class="parcel_machine_id" value="${point_name}"/>
        <input type="hidden" id="parcel_machine_desc" name="parcel_machine_desc" class="parcel_machine_desc" value="${parcelMachineAddressDesc}"/>
    `;

    // Construct the HTML for the selected point display
    selected_point_data = `
        <div class="easypack_selected_point_data" id="easypack_selected_point_data" style="margin-bottom:15px">
            <div id="selected-parcel-machine-id">${point_name}</div>
            <span id="selected-parcel-machine-desc">${address_line1}<br>${address_line2}</span><br>
            ${point.location_description ? `<span id="selected-parcel-machine-desc1">(${point.location_description})</span>` : ''}
            ${commonHiddenFields}
        </div>`;

    // Remove existing selected point data and validation fields
    document.querySelectorAll('#easypack_selected_point_data').forEach(el => el.remove());
    document.querySelectorAll('.inpost_pl_additional_validation_field').forEach(el => el.remove());

    // Insert new data after the map button
    const geoBtn = document.getElementById('easypack_js_type_geowidget');
    if (geoBtn) {
        geoBtn.insertAdjacentHTML('afterend', selected_point_data);
        geoBtn.textContent = easypack_front_map.button_text2;
    }

    let is_need_write_missed_locker = false;
    const typ_page = document.getElementById('selected-parcel-locker-pl-id');

    if (typ_page) {
        is_need_write_missed_locker = true;
        typ_page.innerHTML = point_name;
        const typ_page_desc = document.getElementById('selected-parcel-machine-desc');
        if (typ_page_desc) typ_page_desc.innerHTML = selected_point_data;
        document.querySelectorAll('.hidden-inpost-pl-typ-data').forEach(el => el.style.display = 'block');
    }

    // Save selection to LocalStorage
    const EasyPackPointObject = {
        'pointName': point_name,
        'pointDesc': parcelMachineAddressDesc,
        'visiblePointData': address_line1 + '<br>' + address_line2
    };
    localStorage.setItem('EasyPackPointObject', JSON.stringify(EasyPackPointObject));

    // Support for templates like Divi: Add hidden fields to the form
    const form = document.querySelector('.checkout.woocommerce-checkout');
    if (form) {
        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'parcel_machine_id';
        inputId.value = point_name;
        inputId.className = 'inpost_pl_additional_validation_field';

        const inputDesc = document.createElement('input');
        inputDesc.type = 'hidden';
        inputDesc.name = 'parcel_machine_desc';
        inputDesc.value = parcelMachineAddressDesc;
        inputDesc.className = 'inpost_pl_additional_validation_field';

        form.appendChild(inputId);
        form.appendChild(inputDesc);
    }

    if (inpostjsGeowidgetModal) inpostjsGeowidgetModal.close();

    // If on Thank You Page, update locker info via AJAX (Fetch API)
    if (is_need_write_missed_locker) {
        const typMapData = document.getElementById('inpost-pl-typ-map-data');
        const order_id = typMapData ? typMapData.getAttribute('data-id') : '';
        let order_key = '';
        try {
            order_key = new URLSearchParams(window.location.search).get('key') || '';
        } catch (err) {
            order_key = '';
        }

        const formData = new URLSearchParams();
        formData.append('action', 'update_locker_from_typ_page');
        formData.append('order_id', order_id);
        formData.append('order_key', order_key);
        formData.append('inpost_pl_locker', point_name);
        formData.append('inpost_pl_locker_desc', parcelMachineAddressDesc);
        formData.append('security', easypack_front_map.security);

        // UI state: beforeSend
        inpost_pl_is_ajax_get_point_running = true;
        document.querySelectorAll('.inpost_pl_geowidget_related_preloader').forEach(el => el.style.display = 'flex');
        document.querySelectorAll('.inpost_pl_locker_changed').forEach(el => el.remove());
        document.querySelectorAll('.inpost-pl-related-point-btn').forEach(el => el.style.background = '#fff');

        fetch(easypack_front_map.ajaxurl, {
            method: 'POST',
            body: formData,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        })
            .then(response => response.json())
            .then(data => {
                if (!typMapData) return;
                if (data && data.success) {
                    typMapData.insertAdjacentHTML('beforebegin', `<div class="inpost_pl_locker_changed" style="color:#26be22; font-weight: bold">${easypack_front_map.updated_text}: ${point_name}</div>`);
                } else {
                    typMapData.insertAdjacentHTML('beforebegin', `<div class="inpost_pl_locker_changed" style="color:#e03636; font-weight: bold">${easypack_front_map.error_text}</div>`);
                }
            })
            .catch(() => {
                if (!typMapData) return;
                typMapData.insertAdjacentHTML('beforebegin', `<div class="inpost_pl_locker_changed" style="color:#e03636; font-weight: bold">${easypack_front_map.error_text}</div>`);
            })
            .finally(() => {
                inpost_pl_is_ajax_get_point_running = false;
                document.querySelectorAll('.inpost-pl-typ-preloader').forEach(el => el.remove());
                document.querySelectorAll('.inpost_pl_geowidget_related_preloader').forEach(el => el.style.display = 'none');
            });
    }
}

/**
 * Load selected locker info from local storage and display it.
 */
function inpost_pl_js_set_locker_from_local_storage() {
    const storageData = localStorage.getItem('EasyPackPointObject');
    if (!storageData) return;

    const pointData = JSON.parse(storageData);
    if (pointData && pointData.pointName) {
        const point = pointData.pointName;
        const visible_desc = pointData.visiblePointData || '';
        const desc = pointData.pointDesc || '';

        const html = `
            <div class="easypack_selected_point_data" id="easypack_selected_point_data" style="margin-bottom:15px">
                <div id="selected-parcel-machine-id">${point}</div>
                ${visible_desc ? `<span id="selected-parcel-machine-desc1">(${visible_desc})</span>` : ''}
                <input type="hidden" id="parcel_machine_id" name="parcel_machine_id" class="parcel_machine_id" value="${point}"/>
                <input type="hidden" id="parcel_machine_desc" name="parcel_machine_desc" class="parcel_machine_desc" value="${desc}"/>
            </div>`;

        const geoBtn = document.getElementById('easypack_js_type_geowidget');
        if (geoBtn) {
            geoBtn.insertAdjacentHTML('afterend', html);
            geoBtn.textContent = easypack_front_map.button_text2;
        }
    }
}

/**
 * Remove JS-injected map button and selected point markup.
 */
function inpost_pl_js_remove_map_ui() {
    document.querySelectorAll('.easypack_show_geowidget').forEach(el => el.remove());
    document.querySelectorAll('.easypack_selected_point_data').forEach(el => el.remove());
}

/**
 * Determine if current shipping method requires the map.
 */
function inpost_pl_js_method_needs_map_button(method, postfix) {
    if (!method) return false;
    if (method.includes('easypack_parcel_machines')) return true;

    const inpost_methods = inpost_pl_js_get_configured_inpost_methods();
    const linked_method = inpost_methods[postfix];
    if (linked_method && linked_method.inpost_title?.includes('easypack_parcel_machines')) {
        return true;
    }
    return false;
}

/**
 * Render or refresh the map button.
 */
function inpost_pl_js_render_map_button(token) {
    inpost_pl_js_remove_map_ui();

    const shipping_data = inpost_pl_get_shipping_method_js_mode();
    const method = shipping_data.method;
    const postfix = shipping_data.postfix;

    if (!method || !inpost_pl_js_method_needs_map_button(method, postfix)) return;

    if (inpostjsGeowidgetModal) {
        const config = inpost_pl_js_get_map_config_based_on_instance_id(postfix, method);
        const map_content = `<inpost-geowidget id="inpost-geowidget" onpoint="inpost_pl_select_point_callback_js_mode" token="${token}" language="pl" config="${config}"></inpost-geowidget>`;
        inpostjsGeowidgetModal.setContent(map_content);
    }

    const selector = method + ':' + postfix;
    let shipping_option_input = null;

    document.querySelectorAll('#shipping_method input').forEach(input => {
        if (input.value === selector) shipping_option_input = input;
    });

    if (shipping_option_input) {
        const li_parent = shipping_option_input.closest('li');
        if (li_parent) {
            const map_button = `<div class="easypack_show_geowidget" id="easypack_js_type_geowidget">${easypack_front_map.button_text1}</div>`;
            li_parent.insertAdjacentHTML('afterend', map_button);
            inpost_pl_js_set_locker_from_local_storage();
        }
    }
}

/**
 * Main Initialization
 *
 * Note: WooCommerce fires updated_checkout / updated_shipping_method via jQuery(.trigger).
 * Those custom events do NOT reach native addEventListener — keep a thin jQuery bridge
 * (jQuery remains a script dependency for cart/checkout pages).
 */
function inpost_pl_js_init_front() {
    console.log("Inpost PL: front_js initialized");

    const token = easypack_front_map.geowidget_v5_token;
    const shipping_data = inpost_pl_get_shipping_method_js_mode();
    const wH = window.innerHeight - 80;
    const config = inpost_pl_js_get_map_config_based_on_instance_id(shipping_data.postfix, shipping_data.method);

    // Initialize jBox Modal
    if (typeof jBox !== 'undefined' && window.EasypackGeowidgetModalA11y) {
        inpostjsGeowidgetModal = new jBox('Modal', window.EasypackGeowidgetModalA11y.getOptions({
            width: 800,
            height: wH,
            attach: '#easypack_show_geowidget_modal',
            title: 'Wybierz paczkomat',
            content: `<inpost-geowidget id="inpost-geowidget" onpoint="inpost_pl_select_point_callback_js_mode" token="${token}" language="pl" config="${config}"></inpost-geowidget>`
        }));
    }

    // Initial button render
    inpost_pl_js_render_map_button(token);

    const onWcShippingUiUpdate = function () {
        inpost_pl_js_render_map_button(token);
    };

    // jQuery bridge required for WC custom events (native listeners never receive them).
    if (window.jQuery) {
        window.jQuery(document.body).on(
            'updated_checkout updated_shipping_method',
            onWcShippingUiUpdate
        );
    }

    /**
     * Global click delegation
     */
    document.addEventListener('click', function (e) {
        const target = e.target;

        // Map button click
        if (target && target.id === 'easypack_js_type_geowidget') {
            e.preventDefault();
            if (inpostjsGeowidgetModal && !inpostjsGeowidgetModal.isOpen) inpostjsGeowidgetModal.open();
        }

        // Thank you page widget click
        if (target && target.classList.contains('inpost_pl_geowidget_typ')) {
            e.preventDefault();
            if (inpostjsGeowidgetModal && !inpostjsGeowidgetModal.isOpen) inpostjsGeowidgetModal.open();
        }

        // Nearest point button click on TYP
        const relatedBtn = target.closest('.inpost-pl-related-point-btn');
        if (relatedBtn) {
            e.preventDefault();
            if (inpost_pl_is_ajax_get_point_running) return;

            document.querySelectorAll('.inpost-pl-related-point-btn').forEach(el => el.style.background = '#fff');

            const point_id = relatedBtn.getAttribute('data-id');
            const order_id = document.getElementById('inpost-pl-related-data-order')?.value || '';
            let order_key = '';
            try {
                order_key = new URLSearchParams(window.location.search).get('key') || '';
            } catch (err) {
                order_key = '';
            }

            const formData = new URLSearchParams();
            formData.append('action', 'update_locker_from_typ_page');
            formData.append('order_id', order_id);
            formData.append('order_key', order_key);
            formData.append('inpost_pl_locker', point_id);
            formData.append('inpost_pl_locker_desc', relatedBtn.getAttribute('data-address-id'));
            formData.append('security', easypack_front_map.security);

            inpost_pl_is_ajax_get_point_running = true;
            document.querySelectorAll('.hidden-inpost-pl-typ-data').forEach(el => el.style.display = 'none');
            const relatedPreloader = relatedBtn.querySelector('.inpost-pl-select-from-points-preloader');
            if (relatedPreloader) relatedPreloader.style.display = 'block';
            document.querySelectorAll('.inpost_pl_locker_changed').forEach(el => el.remove());

            fetch(easypack_front_map.ajaxurl, {
                method: 'POST',
                body: formData,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            })
                .then(res => res.json())
                .then(data => {
                    const anchor = document.getElementById('inpost-pl-typ-map-data');
                    if (data && data.success) {
                        relatedBtn.style.background = '#afeaad';
                        const info = relatedBtn.querySelector('.inpost-pl-related-locker-info');
                        if (info) {
                            info.insertAdjacentHTML('afterend', `<div class="inpost_pl_locker_changed" style="color:#26be22; font-weight: bold">${easypack_front_map.updated_text}: ${point_id}</div>`);
                        }
                        if (anchor) anchor.insertAdjacentHTML('beforebegin', `<div class="inpost_pl_locker_changed" style="color:#26be22; font-weight: bold">${easypack_front_map.updated_text}: ${point_id}</div>`);
                    } else if (anchor) {
                        anchor.insertAdjacentHTML('beforebegin', `<div class="inpost_pl_locker_changed" style="color:#e03636; font-weight: bold">${easypack_front_map.error_text}</div>`);
                    }
                })
                .catch(() => {
                    const anchor = document.getElementById('inpost-pl-typ-map-data');
                    if (anchor) anchor.insertAdjacentHTML('beforebegin', `<div class="inpost_pl_locker_changed" style="color:#e03636; font-weight: bold">${easypack_front_map.error_text}</div>`);
                })
                .finally(() => {
                    inpost_pl_is_ajax_get_point_running = false;
                    if (relatedPreloader) relatedPreloader.style.display = 'none';
                });
        }
    });

    /**
     * Native change fallback: works even when WooCommerce body events are broken.
     * Also resets locker selection when shipping method changes.
     */
    document.addEventListener('change', function (e) {
        const target = e.target;
        if (target && target.name && (target.name === 'shipping_method[0]' || target.name.startsWith('shipping_method'))) {
            localStorage.setItem('EasyPackPointObject', null);
            document.querySelectorAll('input[name=parcel_machine_desc], input[name=parcel_machine_id]').forEach(input => input.value = '');
            document.querySelectorAll('[id*="selected-parcel-machine-id"]').forEach(el => el.innerHTML = '');
            inpost_pl_js_render_map_button(token);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inpost_pl_js_init_front);
} else {
    inpost_pl_js_init_front();
}

/**
 * Determine map configuration string based on method type.
 */
function inpost_pl_js_get_map_config_based_on_instance_id(instance_id, method) {
    let map_config = 'parcelCollect';
    const inpost_methods = inpost_pl_js_get_configured_inpost_methods();

    const checkMethod = (id) => {
        if (id === 'easypack_parcel_machines_cod') return 'parcelCollectPayment';
        if (id === 'easypack_shipping_courier_c2c') return 'parcelSend';
        if (id === 'easypack_parcel_machines_weekend' || id === 'easypack_parcel_machines_weekend_cod') return 'parcelCollect247';
        return 'parcelCollect';
    };

    if (instance_id && inpost_methods[instance_id]) {
        return checkMethod(inpost_methods[instance_id].inpost_title);
    }
    return checkMethod(method);
}

/**
 * Helper to fetch configured methods from global JS object.
 */
function inpost_pl_js_get_configured_inpost_methods() {
    return window.easypack_front_map?.inpost_methods || [];
}