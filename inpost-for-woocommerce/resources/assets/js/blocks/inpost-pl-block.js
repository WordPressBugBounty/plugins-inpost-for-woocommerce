(() => {
    "use strict";
    const e = window.wc.blocksCheckout, t = window.React, n = window.wp.element, o = window.wp.i18n,
        l = window.wp.data, {ExperimentalOrderMeta: i} = wc.blocksCheckout;

    function a({handleDeliveryPointChange: e, inpostDeliveryPoint: n}) {
        return (0, t.createElement)("div", {
            className: "inpost-parcel-locker-wrap",
            style: {display: "none"}
        }, (0, t.createElement)("input", {
            value: n,
            type: "text",
            id: "inpost-parcel-locker-id",
            name: "inpost-parcel-locker-id",
            onChange: e
        }))
    }

    const s = JSON.parse('{"apiVersion":2,"name":"inpost-pl/block","version":"2.0.0","title":"Inpost PL Shipping Options Block","category":"woocommerce","description":"Adds map button and add input to save delivery point data.","supports":{"html":false,"align":false,"multiple":false,"reusable":false},"parent":["woocommerce/checkout-shipping-methods-block"],"attributes":{"lock":{"type":"object","default":{"remove":true,"move":true}},"text":{"type":"string","source":"html","selector":".wp-block-inpost-pl","default":""}},"textdomain":"woocommerce-inpost","editorStyle":""}');
    (0, e.registerCheckoutBlock)({
        metadata: s, component: ({checkoutExtensionData: e, extensions: s}) => {
            let c = !1, r = null;
            const [p, d] = (0, n.useState)(""), {setExtensionData: u} = e, m = "inpost-pl-delivery-point-error", {
                setValidationErrors: _,
                clearValidationError: h
            } = (0, l.useDispatch)("wc/store/validation");
            let w = (0, l.useSelect)((e => e("wc/store/cart").getShippingRates()));
            if (null != w) {
                let e = w[Object.keys(w)[0]];
                if (null != e && e.hasOwnProperty("shipping_rates")) {
                    const t = e.shipping_rates, n = [];
                    if (null != t) {
                        for (let e of t) if ("pickup_location" !== e.method_id) {
                            if (!0 === e.selected) {
                                const t = wcSettings.inpost_pl_block_data && wcSettings.inpost_pl_block_data.configured_methods ? wcSettings.inpost_pl_block_data.configured_methods : [];
                                if (r = e.instance_id, -1 !== e.method_id.indexOf("easypack_parcel_machines")) c = !0; else {
                                    let e = t[r];
                                    null != e && e.hasOwnProperty("need_map") && (c = !0)
                                }
                            }
                            n.push(e)
                        }
                        if (!r && n.length > 0) {
                            const e = document.getElementsByClassName("wc-block-components-shipping-rates-control")[0];
                            if (null != e) {
                                const t = e.querySelector('input[name^="radio-control-"]:checked');
                                if (null != t) {
                                    let e = t.getAttribute("id");
                                    if (null != e) {
                                        let t = e.split(":");
                                        if (r = t[t.length - 1], -1 !== t[0].indexOf("easypack_parcel_machines")) c = !0; else {
                                            let e = configured_shipping_methods[r];
                                            null != e && e.hasOwnProperty("need_map") && (c = !0)
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            const g = (0, n.useCallback)((() => {
                c && !p && _({
                    [m]: {
                        message: (0, o.__)("Parcel locker must be choosen.", "woocommerce-inpost"),
                        hidden: !0
                    }
                })
            }), [p, _, h, c]), k = (0, n.useCallback)((() => {
                if (p || !c) return h(m), !0
            }), [p, _, h, c]);
            return (0, n.useEffect)((() => {
                g(), k(), u("inpost", "inpost-parcel-locker-id", p)
            }), [p, u, k]), (0, t.createElement)(t.Fragment, null, c && (0, t.createElement)(t.Fragment, null, (0, t.createElement)("button", {
                className: "button alt easypack_show_geowidget",
                id: "easypack_block_type_geowidget"
            }, (0, o.__)("Wybierz punkt odbioru", "woocommerce-inpost")), (0, t.createElement)("div", {
                id: "inpost_pl_selected_point_data_wrap",
                className: "inpost_pl_selected_point_data_wrap",
                style: {display: "none"}
            }), (0, t.createElement)(i, null, (0, t.createElement)(a, {
                inpostDeliveryPoint: p,
                handleDeliveryPointChange: e => {
                    const t = e.target.value;
                    d(t)
                }
            }))))
        }
    })
})();