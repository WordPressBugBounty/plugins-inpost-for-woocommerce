let successful_ids = {};

/**
 * Order table row (classic posts table or HPOS).
 *
 * @param {string|number} order_id
 * @return {jQuery}
 */
function inpost_get_order_row( order_id ) {
	let $row = jQuery( '#post-' + order_id );
	if ( ! $row.length ) {
		$row = jQuery( '#order-' + order_id );
	}
	return $row;
}

/**
 * InPost shipping status cell for an order row.
 *
 * @param {string|number} order_id
 * @return {jQuery}
 */
function inpost_pl_get_order_status_cell( order_id ) {
	const $row = inpost_get_order_row( order_id );
	let $cell  = $row.children( '.easypack_shipping_statuses, td.easypack_shipping_statuses, td.column-easypack_shipping_statuses' );

	if ( ! $cell.length ) {
		$cell = $row.find( '> .easypack_shipping_statuses, > td.easypack_shipping_statuses, > td.column-easypack_shipping_statuses' );
	}

	if ( ! $cell.length ) {
		$cell = $row.find( '.easypack_shipping_statuses' ).first();
	}

	return $cell;
}

/**
 * Remove error row highlight from previous bulk run.
 *
 * @return {void}
 */
function inpost_pl_clear_bulk_row_error_highlights() {
	jQuery( 'tr.inpost-pl-bulk-row-error' ).removeClass( 'inpost-pl-bulk-row-error' );
}

/**
 * @param {string|number} order_id
 * @return {void}
 */
function inpost_pl_mark_bulk_row_error( order_id ) {
	inpost_get_order_row( order_id ).addClass( 'inpost-pl-bulk-row-error' );
}

/**
 * @param {string[]|number[]} order_ids
 * @return {void}
 */
function inpost_pl_mark_bulk_row_errors( order_ids ) {
	inpost_pl_unique_order_ids( order_ids ).forEach( function ( order_id ) {
		inpost_pl_mark_bulk_row_error( order_id );
	} );
}

/**
 * @param {string|number} order_id
 * @return {void}
 */
function inpost_pl_unmark_bulk_row_error( order_id ) {
	inpost_get_order_row( order_id ).removeClass( 'inpost-pl-bulk-row-error' );
}

/**
 * Toggle bulk-processing wait state on admin body.
 *
 * @param {boolean} is_busy
 * @return {void}
 */
function inpost_pl_set_bulk_ui_busy( is_busy ) {
	const body = document.querySelector( 'body' );

	if ( ! body ) {
		return;
	}

	if ( is_busy ) {
		body.style.opacity = '0.6';
		body.style.cursor  = 'wait';
	} else {
		body.style.opacity = '1';
		body.style.cursor  = 'unset';
	}
}

/**
 * Show shipment error in the InPost status column.
 *
 * @param {jQuery} $status_cell InPost status table cell.
 * @param {object} data         AJAX response.
 * @return {void}
 */
function inpost_show_shipment_error( $status_cell, data ) {
	if ( typeof data.message === 'undefined' || data.message === null ) {
		return;
	}

	let cleared_message_text = data.message.replace( /(<([^>]+)>)/ig, ' ' );
	console.log( cleared_message_text );

	if ( cleared_message_text.indexOf( 'height required' ) !== -1
		|| cleared_message_text.indexOf( 'length required' ) !== -1
		|| cleared_message_text.indexOf( 'width required' ) !== -1
		|| cleared_message_text.indexOf( 'weight amount required ' ) !== -1
	) {
		cleared_message_text = 'Sprawdź czy wymiary paczki są prawidłowe';
		$status_cell.addClass( 'easypack-alert-status' );
	}

	$status_cell.html( '<span style="color:#f00;"><b>' + cleared_message_text + '</b></span>' );
}

/** @type {number[]} */
let inpost_pl_bulk_shipment_failed_ids = [];

/** @type {number} */
let inpost_pl_bulk_total_selected = 0;

/** @type {string[]} All order IDs from the latest bulk selection. */
let inpost_pl_bulk_all_selected_ids = [];

/**
 * @return {object}
 */
function inpost_pl_get_popup_texts() {
	if ( typeof easypack_bulk !== 'undefined' && easypack_bulk.popup_texts ) {
		return easypack_bulk.popup_texts;
	}
	return {};
}

/**
 * @param {string} key
 * @return {string}
 */
function inpost_pl_popup_text( key ) {
	const texts = inpost_pl_get_popup_texts();
	return texts[ key ] || '';
}

/**
 * @param {string} template
 * @param {...(string|number)} args
 * @return {string}
 */
function inpost_pl_popup_sprintf( template, ...args ) {
	if ( ! template ) {
		return '';
	}

	let result = template.replace( /%(\d+)\$[ds]/g, function ( match, position ) {
		const index = parseInt( position, 10 ) - 1;
		return typeof args[ index ] !== 'undefined' ? String( args[ index ] ) : match;
	} );

	let i = 0;
	result = result.replace( /%(?!\d+\$)[ds]/g, function () {
		return typeof args[ i ] !== 'undefined' ? String( args[ i++ ] ) : '';
	} );

	return result;
}

/**
 * @param {object|array} orders
 * @return {string[]}
 */
function inpost_pl_normalize_order_ids( orders ) {
	const ids = [];
	if ( Array.isArray( orders ) ) {
		orders.forEach( function ( id ) {
			if ( id ) {
				ids.push( String( id ) );
			}
		} );
	} else if ( orders && typeof orders === 'object' ) {
		Object.keys( orders ).forEach( function ( key ) {
			if ( orders[ key ] ) {
				ids.push( String( orders[ key ] ) );
			}
		} );
	}
	return ids.filter( function ( value, index, self ) {
		return self.indexOf( value ) === index;
	} );
}

/**
 * @param {string[]|number[]} order_ids
 * @return {string[]}
 */
function inpost_pl_unique_order_ids( order_ids ) {
	const unique = [];
	const seen   = {};

	inpost_pl_normalize_order_ids( order_ids ).forEach( function ( id ) {
		if ( ! seen[ id ] ) {
			seen[ id ] = true;
			unique.push( id );
		}
	} );

	return unique;
}

/**
 * Whether order row looks ready for label download (based on current table UI).
 *
 * @param {string|number} order_id
 * @return {boolean}
 */
function inpost_pl_order_has_label_eligibility( order_id ) {
	const $row = inpost_get_order_row( order_id );

	if ( $row.hasClass( 'inpost-pl-bulk-row-error' ) ) {
		return false;
	}

	const $cell = inpost_pl_get_order_status_cell( order_id );
	if ( ! $cell.length ) {
		return false;
	}

	if ( $cell.find( '.dashicons-media-spreadsheet' ).length ) {
		return true;
	}

	const text = $cell.text().replace( /\s+/g, ' ' ).trim();
	if ( '' === text ) {
		return false;
	}

	if ( text.indexOf( 'Przesyłka już utworzona' ) !== -1 ) {
		return true;
	}

	if ( /\d{12,}/.test( text ) ) {
		return true;
	}

	if ( text.indexOf( 'Występują błędy' ) !== -1
		|| text.indexOf( 'Nie utworzona' ) !== -1
		|| text.indexOf( 'Not created' ) !== -1
	) {
		return false;
	}

	return false;
}

/**
 * Why order was skipped for labels_only bulk action.
 *
 * @param {string|number} order_id
 * @return {'not_inpost'|'not_ready'|'other'|null}
 */
function inpost_pl_get_order_label_skip_reason( order_id ) {
	if ( inpost_pl_order_has_label_eligibility( order_id ) ) {
		return null;
	}

	const $cell = inpost_pl_get_order_status_cell( order_id );
	if ( ! $cell.length ) {
		return 'not_inpost';
	}

	const text = $cell.text().replace( /\s+/g, ' ' ).trim();
	if ( '' === text ) {
		return 'not_inpost';
	}

	if ( text.indexOf( 'Nie utworzona' ) !== -1 || text.indexOf( 'Not created' ) !== -1 ) {
		return 'not_ready';
	}

	return 'other';
}

/**
 * @param {string[]} order_ids
 * @return {{not_inpost: string[], not_ready: string[], other: string[]}}
 */
function inpost_pl_group_failed_orders_by_reason( order_ids ) {
	const groups = {
		not_inpost: [],
		not_ready: [],
		other: [],
	};

	inpost_pl_unique_order_ids( order_ids ).forEach( function ( order_id ) {
		const reason = inpost_pl_get_order_label_skip_reason( order_id );
		if ( 'not_inpost' === reason ) {
			groups.not_inpost.push( order_id );
		} else if ( 'not_ready' === reason ) {
			groups.not_ready.push( order_id );
		} else {
			groups.other.push( order_id );
		}
	} );

	return groups;
}

/**
 * @param {{not_inpost: string[], not_ready: string[], other: string[]}} groups
 * @param {string[]} excludeIds
 * @return {{not_inpost: string[], not_ready: string[], other: string[]}}
 */
function inpost_pl_filter_failed_order_groups_by_ids( groups, excludeIds ) {
	const excluded = {};
	inpost_pl_unique_order_ids( excludeIds ).forEach( function ( order_id ) {
		excluded[ order_id ] = true;
	} );

	return {
		not_inpost: groups.not_inpost.filter( function ( order_id ) {
			return ! excluded[ order_id ];
		} ),
		not_ready: groups.not_ready.filter( function ( order_id ) {
			return ! excluded[ order_id ];
		} ),
		other: groups.other.filter( function ( order_id ) {
			return ! excluded[ order_id ];
		} ),
	};
}

/**
 * @param {Array<{order_id: (string|null)}>} detailLines
 * @return {string[]}
 */
function inpost_pl_get_detail_line_order_ids( detailLines ) {
	const ids = [];

	( detailLines || [] ).forEach( function ( line ) {
		if ( line && line.order_id ) {
			ids.push( String( line.order_id ) );
		}
	} );

	return inpost_pl_unique_order_ids( ids );
}

/**
 * @param {string} template
 * @param {number} count
 * @param {string} countClass
 * @return {string}
 */
function inpost_pl_format_popup_count_line( template, count, countClass ) {
	const placeholder = '%d';
	const index       = template.indexOf( placeholder );

	if ( -1 === index ) {
		return template;
	}

	return (
		template.slice( 0, index ) +
		'<span class="inpost-pl-bulk-labels-popup__count ' + countClass + '">' + count + '</span>' +
		template.slice( index + placeholder.length )
	);
}

/**
 * @param {number}  total
 * @param {number}  labelsCount
 * @param {object}  texts
 * @param {boolean} showLabelsCount
 * @return {string}
 */
function inpost_pl_format_popup_summary_html( total, labelsCount, texts, showLabelsCount ) {
	const mismatch     = showLabelsCount && total !== labelsCount;
	const summaryClass = 'inpost-pl-bulk-labels-popup__summary' + ( mismatch ? ' inpost-pl-bulk-labels-popup__summary--mismatch' : '' );
	const selectedClass = mismatch
		? 'inpost-pl-bulk-labels-popup__count--selected'
		: 'inpost-pl-bulk-labels-popup__count--neutral';
	const downloadedClass = mismatch
		? 'inpost-pl-bulk-labels-popup__count--downloaded'
		: 'inpost-pl-bulk-labels-popup__count--neutral';
	let html = '<div class="' + summaryClass + '">';

	if ( total > 0 ) {
		html += '<p class="inpost-pl-bulk-labels-popup__summary-line">' +
			inpost_pl_format_popup_count_line( texts.selected_count, total, selectedClass ) +
			'</p>';
	}

	if ( showLabelsCount ) {
		html += '<p class="inpost-pl-bulk-labels-popup__summary-line">' +
			inpost_pl_format_popup_count_line( texts.labels_ok_count, labelsCount, downloadedClass ) +
			'</p>';
	}

	html += '</div>';
	return html;
}

/**
 * @param {string[]} bodyParts
 * @param {object}   texts
 * @param {{not_inpost: string[], not_ready: string[], other: string[]}} groups
 * @return {void}
 */
function inpost_pl_append_failed_order_groups( bodyParts, texts, groups ) {
	if ( groups.not_inpost.length > 0 ) {
		bodyParts.push(
			'<p class="inpost-pl-bulk-labels-popup__order-links">' +
			inpost_pl_popup_sprintf( texts.labels_not_inpost, inpost_pl_format_order_links_html( groups.not_inpost ) ) +
			'</p>'
		);
	}

	if ( groups.not_ready.length > 0 ) {
		bodyParts.push(
			'<p class="inpost-pl-bulk-labels-popup__order-links">' +
			inpost_pl_popup_sprintf( texts.labels_not_ready, inpost_pl_format_order_links_html( groups.not_ready ) ) +
			'</p>'
		);
	}

	if ( groups.other.length > 0 ) {
		bodyParts.push(
			'<p class="inpost-pl-bulk-labels-popup__order-links">' +
			inpost_pl_popup_sprintf( texts.labels_partial_failed, inpost_pl_format_order_links_html( groups.other ) ) +
			'</p>'
		);
	}
}

/**
 * @param {string} tracking_code
 * @param {object} context
 * @return {string|null}
 */
function inpost_pl_resolve_order_id_by_tracking_code( tracking_code, context ) {
	if ( ! tracking_code ) {
		return null;
	}

	const allSelected = inpost_pl_unique_order_ids(
		context.allSelectedOrderIds || context.labelOrderIds || []
	);

	for ( let i = 0; i < allSelected.length; i++ ) {
		const order_id = allSelected[ i ];
		const $cell    = inpost_pl_get_order_status_cell( order_id );
		if ( $cell.length && $cell.text().indexOf( tracking_code ) !== -1 ) {
			return String( order_id );
		}
	}

	return null;
}

/**
 * @param {string} key
 * @return {string}
 */
function inpost_pl_popup_label_error_key( key ) {
	const texts = inpost_pl_get_popup_texts();

	if ( 'ParcelLabelExpired' === key ) {
		return texts.label_error_parcel_expired || key;
	}

	if ( ! key ) {
		return texts.unknown_api_error || '';
	}

	return inpost_pl_popup_sprintf( texts.label_error_key_fallback || '%s', key );
}

/**
 * @param {Array}  details
 * @param {object} context
 * @return {Array<{order_id: (string|null), tracking_code: string, key: string, message: string}>}
 */
function inpost_pl_parse_label_api_details_array( details, context ) {
	const lines = [];

	if ( ! Array.isArray( details ) ) {
		return lines;
	}

	details.forEach( function ( item ) {
		try {
			if ( ! item || 'object' !== typeof item ) {
				return;
			}

			if ( true === item.success ) {
				return;
			}

			const key           = item.key ? String( item.key ) : '';
			const tracking_code = item.code ? String( item.code ) : '';
			const order_id      = inpost_pl_resolve_order_id_by_tracking_code( tracking_code, context );
			const message       = inpost_pl_popup_label_error_key( key );

			if ( ! key ) {
				return;
			}

			lines.push( {
				order_id: order_id,
				tracking_code: tracking_code,
				key: key,
				message: message,
			} );
		} catch ( err ) {
			console.log( 'InPost label API detail item parse error:', err );
		}
	} );

	return lines;
}

/**
 * @param {Array<{order_id: (string|null), tracking_code: string, key: string, message: string}>} detailLines
 * @param {object} texts
 * @return {string[]}
 */
function inpost_pl_format_label_api_detail_lines_html( detailLines, texts ) {
	const htmlParts = [];

	detailLines.forEach( function ( line ) {
		const safeMessage = jQuery( '<span/>' ).text( line.message || '' ).html();

		if ( line.order_id ) {
			htmlParts.push(
				'<p class="inpost-pl-bulk-labels-popup__error">' +
				inpost_pl_popup_sprintf(
					texts.label_detail_order_key,
					inpost_pl_format_order_links_html( [ line.order_id ] ),
					safeMessage
				) +
				'</p>'
			);
		} else if ( line.tracking_code ) {
			htmlParts.push(
				'<p class="inpost-pl-bulk-labels-popup__error">' +
				inpost_pl_popup_sprintf(
					texts.label_detail_tracking_key,
					jQuery( '<span/>' ).text( line.tracking_code ).html(),
					safeMessage
				) +
				'</p>'
			);
		} else if ( line.message ) {
			htmlParts.push( '<p class="inpost-pl-bulk-labels-popup__error">' + safeMessage + '</p>' );
		}
	} );

	return htmlParts;
}

/**
 * @param {{order_id: (string|null), key: string, message: string}} line
 * @return {boolean}
 */
function inpost_pl_is_known_label_api_error_line( line ) {
	const unknown = inpost_pl_popup_text( 'unknown_api_error' );

	return !! (
		line
		&& line.key
		&& line.message
		&& line.message !== unknown
	);
}

/**
 * Return only the primary API order that blocked label download.
 *
 * @param {Array} detailLines
 * @return {Array}
 */
function inpost_pl_get_label_api_blocker_lines( detailLines ) {
	if ( ! detailLines || ! detailLines.length ) {
		return [];
	}

	const realErrors = detailLines.filter( inpost_pl_is_known_label_api_error_line );

	if ( realErrors.length > 0 ) {
		return [ realErrors[ 0 ] ];
	}

	return [];
}

/**
 * @param {string|number} order_id
 * @return {string}
 */
function inpost_pl_get_order_edit_url( order_id ) {
	if ( document.getElementById( 'wc-orders-filter' ) ) {
		return 'admin.php?page=wc-orders&action=edit&id=' + order_id;
	}
	return 'post.php?post=' + order_id + '&action=edit';
}

/**
 * @param {string[]} order_ids
 * @return {string}
 */
function inpost_pl_format_order_links_html( order_ids ) {
	return order_ids.map( function ( order_id ) {
		const url = inpost_pl_get_order_edit_url( order_id );
		return '<a href="' + url + '" target="_blank" rel="noopener noreferrer">#' + order_id + '</a>';
	} ).join( ', ' );
}

/**
 * @param {object|string} raw
 * @param {object}        context
 * @return {{text: string, order_id: (string|null), detail_lines: Array, raw: object}}
 */
function inpost_pl_parse_label_api_error( raw, context ) {
	context = context || {};

	let parsed = raw;
	if ( typeof raw === 'string' ) {
		try {
			parsed = JSON.parse( raw );
		} catch ( err ) {
			return {
				text: raw,
				order_id: null,
				detail_lines: [],
				raw: { message: raw },
			};
		}
	}

	console.log( 'InPost label API error (parsed):', parsed );

	let detail_lines = [];

	try {
		if ( Array.isArray( parsed.details ) ) {
			detail_lines = inpost_pl_parse_label_api_details_array( parsed.details, context );
		}
	} catch ( err ) {
		console.log( 'InPost label API details array parse error:', err );
	}

	if ( detail_lines.length > 0 ) {
		const firstWithOrder = detail_lines.find( function ( line ) {
			return !! line.order_id;
		} );

		return {
			text: detail_lines.map( function ( line ) {
				return line.message;
			} ).filter( Boolean ).join( '; ' ) || inpost_pl_popup_text( 'unknown_api_error' ),
			order_id: parsed.order_id
				? String( parsed.order_id )
				: ( firstWithOrder ? firstWithOrder.order_id : null ),
			detail_lines: detail_lines,
			raw: parsed,
		};
	}

	const parts = [];

	try {
		if ( parsed.details && 'object' === typeof parsed.details && ! Array.isArray( parsed.details ) ) {
			if ( parsed.details.shipment_status ) {
				parts.push( 'status przesyłki: ' + parsed.details.shipment_status );
			}
			if ( parsed.details.action ) {
				parts.push( 'akcja: ' + parsed.details.action );
			}
			if ( parsed.details.shipment_id ) {
				parts.push( 'shipment_id: ' + parsed.details.shipment_id );
			}
			if ( parsed.details.key ) {
				parts.push( inpost_pl_popup_label_error_key( String( parsed.details.key ) ) );
			}
		} else if ( parsed.details && ! Array.isArray( parsed.details ) ) {
			parts.push( String( parsed.details ) );
		}
	} catch ( err ) {
		console.log( 'InPost label API details object parse error:', err );
	}

	if ( ! parts.length ) {
		if ( parsed.message ) {
			parts.push( parsed.message );
		}
		if ( parsed.error && parsed.error !== parsed.message ) {
			parts.push( parsed.error );
		}
		if ( parsed.status ) {
			parts.push( 'HTTP ' + parsed.status );
		}
	}

	return {
		text: parts.join( ', ' ) || inpost_pl_popup_text( 'unknown_api_error' ),
		order_id: parsed.order_id ? String( parsed.order_id ) : null,
		detail_lines: [],
		raw: parsed,
	};
}

/**
 * Order IDs selected in bulk but not ready for label download.
 *
 * @param {object} context
 * @return {string[]}
 */
function inpost_pl_get_ineligible_label_order_ids( context ) {
	const allSelected = inpost_pl_unique_order_ids(
		context.allSelectedOrderIds || context.labelOrderIds || []
	);

	return allSelected.filter( function ( order_id ) {
		return ! inpost_pl_order_has_label_eligibility( order_id );
	} );
}

/**
 * Resolve which order blocked label download in API error popup.
 *
 * @param {object} context
 * @param {{order_id: (string|null), raw: object}} parsed
 * @return {string|null}
 */
function inpost_pl_resolve_blocked_order_id( context, parsed ) {
	if ( parsed.order_id ) {
		return String( parsed.order_id );
	}

	if ( parsed.detail_lines && parsed.detail_lines.length ) {
		const lineWithOrder = parsed.detail_lines.find( function ( line ) {
			return !! line.order_id;
		} );
		if ( lineWithOrder ) {
			return lineWithOrder.order_id;
		}
	}

	const ineligible = inpost_pl_get_ineligible_label_order_ids( context );
	if ( ineligible.length === 1 ) {
		return ineligible[0];
	}

	const labelOrderIds = inpost_pl_normalize_order_ids( context.labelOrderIds || [] );
	if ( labelOrderIds.length === 1 ) {
		return labelOrderIds[0];
	}

	return null;
}

/**
 * @return {void}
 */
function inpost_pl_close_labels_popup() {
	jQuery( '#inpost-pl-bulk-labels-popup' ).remove();
}

/**
 * @param {object} options
 * @return {void}
 */
function inpost_pl_show_labels_popup( options ) {
	inpost_pl_close_labels_popup();

	const texts        = inpost_pl_get_popup_texts();
	const bodyParts    = [];
	const total        = options.totalSelected || 0;
	const labelsCount  = options.labelsCount || 0;
	const failedIds    = inpost_pl_unique_order_ids( options.failedOrderIds || [] );
	const blockedId        = options.blockedOrderId || null;
	const blockedError     = options.blockedErrorText || '';
	const detailLines      = options.detailLines || [];
	const failedOrderGroups = options.failedOrderGroups || null;
	const showDownload     = !! options.downloadBlob && !! options.downloadFilename;
	const blockedFailedIds = blockedId
		? failedIds.filter( function ( order_id ) {
			return String( order_id ) !== String( blockedId );
		} )
		: failedIds;
	const blockedFailedGroups = failedOrderGroups
		? {
			not_inpost: blockedFailedIds.filter( function ( id ) {
				return failedOrderGroups.not_inpost.indexOf( id ) !== -1;
			} ),
			not_ready: blockedFailedIds.filter( function ( id ) {
				return failedOrderGroups.not_ready.indexOf( id ) !== -1;
			} ),
			other: blockedFailedIds.filter( function ( id ) {
				return failedOrderGroups.other.indexOf( id ) !== -1;
			} ),
		}
		: inpost_pl_group_failed_orders_by_reason( blockedFailedIds );
	const detailLineOrderIds  = inpost_pl_get_detail_line_order_ids( detailLines );
	const filteredBlockedGroups = inpost_pl_filter_failed_order_groups_by_ids(
		blockedFailedGroups,
		detailLineOrderIds
	);

	if ( total > 0 && 'info' !== options.mode ) {
		bodyParts.push(
			inpost_pl_format_popup_summary_html(
				total,
				labelsCount,
				texts,
				'success' === options.mode || ( 'blocked' === options.mode && labelsCount > 0 )
			)
		);
	}

	if ( options.mode === 'blocked' ) {
		bodyParts.push( '<p><strong>' + texts.labels_blocked_intro + '</strong></p>' );

		if ( detailLines.length > 0 ) {
			bodyParts.push.apply( bodyParts, inpost_pl_format_label_api_detail_lines_html( detailLines, texts ) );
		} else if ( blockedId ) {
			bodyParts.push(
				'<p class="inpost-pl-bulk-labels-popup__error">' +
				inpost_pl_popup_sprintf(
					texts.labels_blocked_order,
					inpost_pl_format_order_links_html( [ blockedId ] ),
					jQuery( '<span/>' ).text( blockedError ).html()
				) +
				'</p>'
			);
		} else if ( blockedError ) {
			bodyParts.push( '<p class="inpost-pl-bulk-labels-popup__error">' + jQuery( '<span/>' ).text( blockedError ).html() + '</p>' );
		}

		inpost_pl_append_failed_order_groups( bodyParts, texts, filteredBlockedGroups );

		bodyParts.push( '<p class="inpost-pl-bulk-labels-popup__hint">' + texts.labels_blocked_hint + '</p>' );
	} else if ( options.mode === 'info' ) {
		bodyParts.push( '<p>' + jQuery( '<span/>' ).text( options.message || '' ).html() + '</p>' );
	} else {
		if ( failedOrderGroups ) {
			inpost_pl_append_failed_order_groups( bodyParts, texts, failedOrderGroups );
		} else if ( failedIds.length > 0 ) {
			inpost_pl_append_failed_order_groups( bodyParts, texts, inpost_pl_group_failed_orders_by_reason( failedIds ) );
		}
	}

	const downloadLabel = options.downloadType === 'pdf' ? texts.download_pdf : texts.download_zip;
	let actionsHtml     = '<button type="button" class="button inpost-pl-bulk-labels-popup__close">' + texts.close + '</button>';

	if ( showDownload ) {
		actionsHtml =
			'<button type="button" class="button button-primary inpost-pl-bulk-labels-popup__download">' + downloadLabel + '</button>' +
			actionsHtml;
	}

	const popupHtml =
		'<div id="inpost-pl-bulk-labels-popup" class="inpost-pl-bulk-labels-popup">' +
		'<div class="inpost-pl-bulk-labels-popup__backdrop"></div>' +
		'<div class="inpost-pl-bulk-labels-popup__dialog" role="dialog" aria-modal="true">' +
		'<h2 class="inpost-pl-bulk-labels-popup__title">' + texts.title + '</h2>' +
		'<div class="inpost-pl-bulk-labels-popup__body">' + bodyParts.join( '' ) + '</div>' +
		'<div class="inpost-pl-bulk-labels-popup__actions">' + actionsHtml + '</div>' +
		'</div>' +
		'</div>';

	jQuery( 'body' ).append( popupHtml );

	const $popup = jQuery( '#inpost-pl-bulk-labels-popup' );

	$popup.on( 'click', '.inpost-pl-bulk-labels-popup__backdrop, .inpost-pl-bulk-labels-popup__close', function () {
		inpost_pl_close_labels_popup();
	} );

	if ( showDownload ) {
		$popup.on( 'click', '.inpost-pl-bulk-labels-popup__download', function () {
			const blob     = options.downloadBlob;
			const filename = options.downloadFilename;
			inpost_pl_close_labels_popup();
			setTimeout( function () {
				inpost_pl_trigger_blob_download( blob, filename );
			}, 600 );
		} );
	}
}

/**
 * @param {Blob} blob
 * @param {string} filename
 * @return {void}
 */
function inpost_pl_trigger_blob_download( blob, filename ) {
	const link  = document.createElement( 'a' );
	const url   = window.URL || window.webkitURL;
	link.href   = url.createObjectURL( blob );
	link.download = filename;
	document.body.appendChild( link );
	link.click();
	document.body.removeChild( link );
}

/**
 * @param {object|array} orders
 * @return {void}
 */
function inpost_pl_restore_label_cells_ui( orders ) {
	jQuery.each(
		inpost_pl_normalize_order_ids( orders ),
		function ( ind, order_id ) {
			const $cell = inpost_pl_get_order_status_cell( order_id );
			$cell.removeClass( 'order-preview' );
			$cell.removeClass( 'disabled' );
			$cell.find( '.inpost-status-inside-td' ).show();
		}
	);
}

/**
 * Order IDs to include in downloaded label filename.
 *
 * @param {object}       context
 * @param {object|array} orders
 * @return {string[]}
 */
function inpost_pl_get_orders_for_label_filename( context, orders ) {
	const requestedIds = inpost_pl_unique_order_ids( orders );

	if ( 'labels_only' === context.source ) {
		const allSelected = inpost_pl_unique_order_ids( context.allSelectedOrderIds || orders );
		return allSelected.filter( inpost_pl_order_has_label_eligibility );
	}

	return requestedIds;
}

/**
 * @param {object} context
 * @param {string} content_type
 * @param {Blob} response_blob
 * @param {object|array} orders
 * @return {{blob: Blob, filename: string, downloadType: string}|null}
 */
function inpost_pl_prepare_label_download( context, content_type, response_blob, orders ) {
	const order_ids      = inpost_pl_get_orders_for_label_filename( context, orders );
	let file_name_part   = '';
	let filename         = '';
	let blob             = null;
	let downloadType     = 'zip';

	order_ids.forEach( function ( order_id ) {
		file_name_part += '_' + order_id;
	} );

	if ( content_type.indexOf( 'application/zip' ) !== -1 ) {
		if ( order_ids.length > 4 ) {
			const today          = new Date();
			const yyyy           = today.getFullYear();
			let mm               = today.getMonth() + 1;
			let dd               = today.getDate();
			const formattedToday = dd + '_' + mm + '_' + yyyy;
			file_name_part       = '_' + formattedToday;
		}
		filename = 'inpost_zamowenia' + file_name_part + '.zip';
		blob     = new Blob( [ response_blob ], { type: 'application/zip' } );
		downloadType = 'zip';
	} else if ( content_type.indexOf( 'application/pdf' ) !== -1 ) {
		filename = 'inpost_zamowenie' + file_name_part + '.pdf';
		blob     = new Blob( [ response_blob ], { type: 'application/pdf' } );
		downloadType = 'pdf';
	} else {
		return null;
	}

	return {
		blob: blob,
		filename: filename,
		downloadType: downloadType,
	};
}

/**
 * @param {object} context
 * @param {object|array} orders
 * @param {object} downloadData
 * @return {void}
 */
function inpost_pl_handle_label_download_success( context, orders, downloadData ) {
	const totalSelected  = context.totalSelected || 0;
	const allSelected    = inpost_pl_unique_order_ids( context.allSelectedOrderIds || orders );
	const requestedIds   = inpost_pl_unique_order_ids( orders );
	let labelsCount      = requestedIds.length;
	let failedOrderIds   = inpost_pl_unique_order_ids( context.shipmentFailedOrderIds || [] );

	if ( 'labels_only' === context.source ) {
		const eligibleIds  = allSelected.filter( inpost_pl_order_has_label_eligibility );
		const skippedIds   = allSelected.filter( function ( id ) {
			return eligibleIds.indexOf( id ) === -1;
		} );

		labelsCount    = eligibleIds.length;
		failedOrderIds = inpost_pl_unique_order_ids( failedOrderIds.concat( skippedIds ) );
	}

	inpost_pl_mark_bulk_row_errors( failedOrderIds );

	if ( totalSelected > 1 ) {
		inpost_pl_show_labels_popup( {
			mode: 'success',
			totalSelected: totalSelected,
			labelsCount: labelsCount,
			failedOrderIds: failedOrderIds,
			failedOrderGroups: inpost_pl_group_failed_orders_by_reason( failedOrderIds ),
			downloadBlob: downloadData.blob,
			downloadFilename: downloadData.filename,
			downloadType: downloadData.downloadType,
		} );
		return;
	}

	inpost_pl_trigger_blob_download( downloadData.blob, downloadData.filename );
}

/**
 * @param {object} context
 * @param {string} rawErrorText
 * @return {void}
 */
function inpost_pl_handle_label_download_error( context, rawErrorText ) {
	const parsed             = inpost_pl_parse_label_api_error( rawErrorText, context );
	const totalSelected      = context.totalSelected || 0;
	const blockedOrderId     = inpost_pl_resolve_blocked_order_id( context, parsed );
	const ineligibleIds      = inpost_pl_get_ineligible_label_order_ids( context );
	const blockerLines       = inpost_pl_get_label_api_blocker_lines( parsed.detail_lines || [] );
	const hasApiBlocker      = blockerLines.length > 0;
	const detailOrderIds     = blockerLines.map( function ( line ) {
		return line.order_id;
	} ).filter( Boolean );
	const errorOrderIds      = hasApiBlocker
		? inpost_pl_unique_order_ids( detailOrderIds )
		: inpost_pl_unique_order_ids(
			[].concat(
				blockedOrderId ? [ blockedOrderId ] : [],
				ineligibleIds,
				detailOrderIds
			)
		);

	inpost_pl_mark_bulk_row_errors( errorOrderIds );

	const popupOptions = {
		mode: 'blocked',
		totalSelected: totalSelected || 1,
		blockedOrderId: hasApiBlocker ? null : blockedOrderId,
		blockedErrorText: parsed.text,
		detailLines: hasApiBlocker ? blockerLines : ( parsed.detail_lines || [] ),
		failedOrderIds: hasApiBlocker ? [] : ineligibleIds,
		failedOrderGroups: hasApiBlocker
			? { not_inpost: [], not_ready: [], other: [] }
			: inpost_pl_group_failed_orders_by_reason( ineligibleIds ),
	};

	if ( totalSelected > 1 ) {
		popupOptions.totalSelected = totalSelected;
	}

	inpost_pl_show_labels_popup( popupOptions );
}

/**
 * @param {Event} e
 * @return {void}
 */
function inpost_pl_handle_bulk_apply_click( e ) {
	const $btn       = jQuery( e.currentTarget );
	const selectorId = ( 'doaction2' === $btn.attr( 'id' ) ) ? '#bulk-action-selector-bottom' : '#bulk-action-selector-top';
	const action     = jQuery( selectorId ).val();
	const form       = jQuery( '#posts-filter' ).length ? jQuery( '#posts-filter' ) : jQuery( '#wc-orders-filter' );
	const inpostBulkActions = [
		'easypack_bulk_create_shipments_then_labels',
		'easypack_bulk_create_shipments',
		'easypack_bulk_create_shipments_A',
		'easypack_bulk_create_shipments_B',
		'easypack_bulk_create_shipments_C',
		'easypack_bulk_create_labels',
	];

	if ( inpostBulkActions.indexOf( action ) === -1 ) {
		return;
	}

	e.preventDefault();
	e.stopImmediatePropagation();

	if ( action === 'easypack_bulk_create_shipments_then_labels' ) {
		const selected_data = inpost_table_processing();
		if ( typeof selected_data != 'undefined' && selected_data !== null ) {
			if ( Object.keys( selected_data.orders ).length ) {
				inpost_process_selected_item( selected_data.orders, 1, selected_data.selected_row_count, form, 0, 1 );
			}
		}
		return;
	}

	if ( action === 'easypack_bulk_create_shipments'
		|| action === 'easypack_bulk_create_shipments_A'
		|| action === 'easypack_bulk_create_shipments_B'
		|| action === 'easypack_bulk_create_shipments_C' ) {
		const selected_data = inpost_table_processing();

		if ( typeof selected_data != 'undefined' && selected_data !== null ) {
			if ( Object.keys( selected_data.orders ).length ) {
				let locker_size = false;
				if ( action === 'easypack_bulk_create_shipments_A' ) {
					locker_size = 'easypack_bulk_create_shipments_A';
				} else if ( action === 'easypack_bulk_create_shipments_B' ) {
					locker_size = 'easypack_bulk_create_shipments_B';
				} else if ( action === 'easypack_bulk_create_shipments_C' ) {
					locker_size = 'easypack_bulk_create_shipments_C';
				}
				inpost_process_selected_item( selected_data.orders, 1, selected_data.selected_row_count, form, 0, 0, locker_size );
			}
		}
		return;
	}

	if ( action === 'easypack_bulk_create_labels' ) {
		const selected_data = inpost_table_processing();

		if ( typeof selected_data != 'undefined' && selected_data !== null ) {
			if ( Object.keys( selected_data.orders ).length ) {
				const all_selected = inpost_pl_normalize_order_ids( selected_data.orders );
				print_labels_bulk(
					selected_data.orders,
					{
						source: 'labels_only',
						totalSelected: selected_data.selected_row_count,
						allSelectedOrderIds: all_selected,
						labelOrderIds: all_selected,
						shipmentFailedOrderIds: [],
					}
				);
			}
		}
	}
}

jQuery( document ).ready(
	function () {

		console.log( 'bulk actions inpost_pl' );

		jQuery( '#doaction, #doaction2' ).on( 'click', inpost_pl_handle_bulk_apply_click );


		//let activeTooltip = null;

		jQuery(".inpost-pl-status-container").hover(
			function() {
				// Only create tooltip if none exists

				const $container = jQuery(this);
				const $statusDiv = $container.find(".inpost-pl-status-tooltip");
				const statusText = $statusDiv.text();

				// Create tooltip
				let activeTooltip = jQuery('<div class="inpost-pl-status-value-tooltip">' + statusText + '</div>');

				// Position tooltip relative to the container
				const containerPos = $container.offset();
				activeTooltip.css({
					position: 'absolute',
					background: '#333',
					color: '#fff',
					padding: '5px 10px',
					borderRadius: '3px',
					fontSize: '12px',
					zIndex: 1000,
					top: containerPos.top - 40, // Position above the status bar
					left: containerPos.left,
					boxShadow: '0 2px 5px rgba(0,0,0,0.2)',
					maxWidth: '300px'
				});

				// Add arrow
				activeTooltip.append('<div class="tooltip-arrow"></div>');

				// Add to body
				jQuery('body').append(activeTooltip);

				// Set timeout to remove tooltip
				setTimeout(function() {
					activeTooltip.fadeOut(300, function() {
						jQuery(this).remove();
						activeTooltip = null;
					});
				}, 3000);

			}
		);
	}
);

document.addEventListener(
	'click',
	function (e) {
		e          = e || window.event;
		var target = e.target || e.srcElement;

		if ( target.classList.contains( 'dashicons-media-spreadsheet' ) ) {
			e.preventDefault();

			let order_id = target.getAttribute( 'data-id' );

			let row_id                    = '#post-' + order_id;
			let inpost_custom_column_cell = jQuery( row_id + ' > .easypack_shipping_statuses' );
			if ( ! inpost_custom_column_cell.length > 0 ) {
				row_id                    = '#order-' + order_id;
				inpost_custom_column_cell = jQuery( row_id + ' > .easypack_shipping_statuses' );
			}

			jQuery( inpost_custom_column_cell ).addClass( 'order-preview' );
			jQuery( inpost_custom_column_cell ).addClass( 'disabled' );
			jQuery( inpost_custom_column_cell ).find( '.inpost-status-inside-td' ).hide();
			print_labels_bulk(
				[ order_id ],
				{
					totalSelected: 1,
					labelOrderIds: [ String( order_id ) ],
					shipmentFailedOrderIds: [],
				}
			);

		}
	}
);

/**
 * @param {object|array} orders
 * @param {object}       context
 * @return {void}
 */
function print_labels_bulk( orders, context ) {
	context = context || {};
	context.labelOrderIds = inpost_pl_normalize_order_ids( orders );
	context.totalSelected = context.totalSelected || context.labelOrderIds.length;

	const beforeSend = function () {
		inpost_pl_set_bulk_ui_busy( true );
	};

	const general_action = 'easypack';
	const easy_action    = 'easypack_create_bulk_labels';
	const order_ids      = JSON.stringify( orders );

	beforeSend();

	const request = new XMLHttpRequest();
	request.open( 'POST', easypack_bulk.ajaxurl, true );
	request.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8' );
	request.responseType = 'blob';

	request.onload = function () {
		inpost_pl_restore_label_cells_ui( orders );

		const finishUi = function () {
			inpost_pl_set_bulk_ui_busy( false );
		};

		if ( request.status === 200 && request.response && request.response.size > 0 ) {
			const content_type  = request.getResponseHeader( 'content-type' ) || '';
			const downloadData  = inpost_pl_prepare_label_download( context, content_type, request.response, orders );

			if ( downloadData ) {
				inpost_pl_handle_label_download_success( context, orders, downloadData );
				finishUi();
				return;
			}
		}

		const reader = new FileReader();
		reader.onload = function () {
			inpost_pl_handle_label_download_error( context, reader.result );
			finishUi();
		};
		reader.onerror = function () {
			inpost_pl_handle_label_download_error( context, inpost_pl_popup_text( 'unknown_api_error' ) );
			finishUi();
		};
		reader.readAsText( request.response || new Blob( [] ) );
	};

	request.onerror = function () {
		inpost_pl_restore_label_cells_ui( orders );
		inpost_pl_handle_label_download_error( context, inpost_pl_popup_text( 'unknown_api_error' ) );
		inpost_pl_set_bulk_ui_busy( false );
	};

	request.send( 'action=' + general_action + '&easypack_action=' + easy_action + '&security=' + easypack_nonce + '&order_ids=' + order_ids );
}


function inpost_process_selected_item(orders, index, total, form, failed, need_labels = false, locker_size = false) {

	// if total reached.
	if (index > total) {
		return false;
	}

	if ( 1 === index ) {
		successful_ids = {};
		inpost_pl_bulk_shipment_failed_ids = [];
	}

	var ajaxdata_process_item = {},
		order_id              = orders[index];

	ajaxdata_process_item['order_id']    = order_id;
	ajaxdata_process_item['action']      = 'easypack_bulk_create_shipments';
	ajaxdata_process_item['locker_size'] = '';
	ajaxdata_process_item['nonce']       = easypack_bulk.nonce;
	if ( locker_size ) {
		ajaxdata_process_item['locker_size'] = locker_size;
	}

	jQuery.ajax(
		{
			beforeSend: function () {
				inpost_pl_set_bulk_ui_busy( true );
			},
			type: 'POST',
			url: easypack_bulk.ajaxurl,
			data: ajaxdata_process_item,
			dataType: 'json',
			success: function (data) {

				let row_id                    = '#post-' + order_id;
				let inpost_custom_column_cell = jQuery( row_id + ' > .easypack_shipping_statuses' );

				if ( ! inpost_custom_column_cell.length > 0 ) {
					row_id                    = '#order-' + order_id;
					inpost_custom_column_cell = jQuery( row_id + ' > .easypack_shipping_statuses' );
				}
				jQuery( inpost_custom_column_cell ).find( '.inpost-status-inside-td' ).removeClass( 'easypack-alert-status' );
				jQuery( inpost_custom_column_cell ).removeClass( 'order-preview' );
				jQuery( inpost_custom_column_cell ).removeClass( 'disabled' );
				inpost_pl_unmark_bulk_row_error( order_id );

				if (data.status === 'ok') {
					let status_message = '';
					if (typeof data.tracking_number != 'undefined' && data.tracking_number !== null) {
						status_message = '<a href="#" ' +
							'target="_blank" ' +
							'data-id="' + order_id + '" ' +
							'class="get_sticker_action_orders">' +
							'<span title="Print stickers"  ' +
							'class="dashicons dashicons-media-spreadsheet" ' +
							'data-id="' + order_id + '">' +
							'</span></a> ' + data.tracking_number;
					} else if (typeof data.api_status != 'undefined' && data.api_status !== null) {
						status_message = data.api_status;
					} else {
						status_message = 'OK';
					}
					jQuery( inpost_custom_column_cell ).html( status_message );

					// collect succesful created orders for further print labels.
					successful_ids[index] = order_id;

					if (index <= total) {
						inpost_process_selected_item( orders, (index + 1), total, form, failed, need_labels, locker_size );
					}

				} else {
					failed++;

					inpost_show_shipment_error( jQuery( inpost_custom_column_cell ), data );

					if ( 'already_created' === data.status ) {
						successful_ids[index] = order_id;
					} else {
						inpost_pl_mark_bulk_row_error( order_id );
						const failed_id = String( order_id );
						if ( inpost_pl_bulk_shipment_failed_ids.indexOf( failed_id ) === -1 ) {
							inpost_pl_bulk_shipment_failed_ids.push( failed_id );
						}
					}

					// continue with next.
					if (index < total) {
						inpost_process_selected_item( orders, (index + 1), total, form, failed, need_labels, locker_size );
					}
				}

				// last item.
				if (index == total) {
					if ( need_labels ) {
						const label_order_ids = inpost_pl_normalize_order_ids( successful_ids );
						if ( ! label_order_ids.length ) {
							inpost_pl_show_labels_popup( {
								mode: 'info',
								totalSelected: inpost_pl_bulk_total_selected,
								message: inpost_pl_popup_text( 'no_orders_ready_for_labels' ),
							} );
						} else {
							print_labels_bulk(
								successful_ids,
								{
									source: 'shipments_then_labels',
									totalSelected: inpost_pl_bulk_total_selected,
									allSelectedOrderIds: inpost_pl_bulk_all_selected_ids.slice(),
									labelOrderIds: label_order_ids,
									shipmentFailedOrderIds: inpost_pl_unique_order_ids( inpost_pl_bulk_shipment_failed_ids ),
								}
							);
						}
					}
				}

			}, complete: function () {
				if ( index !== total ) {
					return;
				}

				if ( ! need_labels ) {
					inpost_pl_set_bulk_ui_busy( false );
					return;
				}

				const label_order_ids = inpost_pl_normalize_order_ids( successful_ids );
				if ( ! label_order_ids.length ) {
					inpost_pl_set_bulk_ui_busy( false );
				}
			}
		}
	);

}

function inpost_table_processing() {
	inpost_pl_clear_bulk_row_error_highlights();

	var form  = jQuery( '#posts-filter' ),
		table = form.find( 'table' );

	if ( form.length === 0 ) {
		form  = jQuery( '#wc-orders-filter' );
		table = form.find( 'table' );
	}

	let all_rows = table.find( "th[class='check-column']" ).children( "input[type='checkbox']" );

	let result             = {};
	let selected_row_count = 0;
	let orders             = {};
	let index              = 1;
	all_rows.each(
		function (i, elem) {
			if (jQuery( elem ).is( ':checked' )) {

				let order_row = null;
				let order_id  = jQuery( elem ).val();

				//console.log( "order_id" );
				//console.log( order_id );

				let old_table = document.getElementById( 'post-' + order_id );
				let new_table = document.getElementById( 'order-' + order_id );

				if ( typeof old_table != 'undefined' && old_table !== null ) {
					order_row = old_table;
				} else if ( typeof new_table != 'undefined' && new_table !== null ) {
					order_row = new_table;
				}

				if ( order_row ) {
					let inpost_custom_column_cell = jQuery( order_row ).find( '.easypack_shipping_statuses' );
					if ( typeof inpost_custom_column_cell != 'undefined' && inpost_custom_column_cell !== null ) {
						//console.log( "inpost_custom_column_cell" );
						//console.log( inpost_custom_column_cell );
						jQuery( inpost_custom_column_cell ).find( '.inpost-status-inside-td' ).hide();
						jQuery( inpost_custom_column_cell ).addClass( 'order-preview' );
						jQuery( inpost_custom_column_cell ).addClass( 'disabled' );
						selected_row_count++;
						orders[index] = order_id;
						index++;
					}
				}
			}
		}
	);

	if (selected_row_count === 0) {
		inpost_pl_show_labels_popup( {
			mode: 'info',
			totalSelected: 0,
			message: inpost_pl_popup_text( 'no_orders_selected' ),
		} );
		return;
	}

	inpost_pl_bulk_total_selected    = selected_row_count;
	inpost_pl_bulk_all_selected_ids  = inpost_pl_normalize_order_ids( orders );
	result['selected_row_count']     = selected_row_count;
	result['orders']                 = orders;
	return result;
}