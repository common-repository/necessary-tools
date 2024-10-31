/**
 * Holds localized data passed on from wp_localize_script().
 */
var _nt_export;

document.onready = function() {
	nt_init_listeners();

	document.querySelector( '.nt-btn-export' ).addEventListener( 'click', nt_export_btn_click );
};

/**
 * Handles adding event listeners for export options and post table checkboxes.
 */
function nt_init_listeners() {

	// assigns special behavior to checkboxes with the class 'nt-select-all'
	var select_all_cbs = document.querySelectorAll( '.nt-select-all' );
	for ( var i = 0; i < select_all_cbs.length; i++ ) {
		select_all_cbs[ i ].addEventListener( 'click', nt_select_all_click );
	}

	// attaches the listener for the post type select field
	document.querySelector( '.nt-export-post-type' ).
		addEventListener( 'change', nt_export_options_change );

	// attaches the listener for the post status select field
	document.querySelector( '.nt-export-post-status' ).
		addEventListener( 'change', nt_export_options_change );
}

/**
 * Handles the click events for the "Select All" checkboxes in the Necessary Tools export page. This
 * function keeps the select-all and normal checkboxes in sync.
 *
 * @param MouseEvent event The 'click' event.
 */
function nt_select_all_click( event ) {
	var this_cb = event.target;

	// handles all checkboxes
	var all_cbs = document.getElementsByClassName( 'nt-cb' );
	for ( var i = 0; i < all_cbs.length; i++ ) {
		var other_cb = all_cbs[ i ];

		// ignores the target checkbox
		if ( this_cb === other_cb ) {
			continue;
		}

		if ( this_cb.checked !== other_cb.checked ) {
			other_cb.checked = this_cb.checked;
		}
	}
}

/**
 * Handles the change event for the post type and post status select fields. When the fields change,
 * the post list should update.
 *
 * @param Event event The change event.
 */
function nt_export_options_change( event ) {
	var select_type = document.querySelector( '.nt-export-post-type' );
	var select_status = document.querySelector( '.nt-export-post-status' );
	jQuery.ajax({
		url: _nt_export.ajax_url,
		type: 'POST',
		data: {
			action: 'nt_export_page',
			_ajax_nonce: _nt_export.nonce,
			post_type: select_type.value,
			post_status: select_status.value
		}, success: function( res ) {
			var alert = document.querySelector( '.alert-danger' );
			if ( 0 !== parseInt( res ) ) {
				document.querySelector( '.table' ).remove();

				var option_container = document.querySelector( '.nt-export-options' );
				var new_table = document.createElement( 'table' );
				new_table.classList.add( 'table' );

				// matches the extra tabs, and the opening and closing table tags of the response
				var regex = /^(\t+|<table class=".*">|<\/table>)/gm;
				new_table.innerHTML = res.trim().replace( regex, '' );

				option_container.appendChild( new_table );

				if ( alert && ! alert.classList.contains( 'nt-hidden' ) ) {
					alert.classList.add( 'nt-hidden' );
				}

				// re-attaches event listeners, since the table is fresh
				nt_init_listeners();
			} else {
				if ( alert && alert.classList.contains( 'nt-hidden' ) ) {
					alert.innerHTML = 'There was a problem with the provided selection. Refresh ' +
						'and try again!';
					alert.classList.remove( 'nt-hidden' );
				}
			}
		}
	});
}

/**
 * Handles the click event for the export button. Collects the post IDs of the selected posts, and
 * directs the user to the download file using a GET request.
 *
 * @param Event event The click event.
 */
function nt_export_btn_click( event ) {
	var alert = document.querySelector( '.alert-danger' );
	var checked_cbs = document.querySelectorAll( '.nt-cb-normal:checked' );
	var post_ids = [];

	for ( var i = 0; i < checked_cbs.length; i++ ) {
		var id_field = checked_cbs[ i ].parentElement.querySelector( '.nt-post-id' );
		post_ids.push( parseInt( id_field.value ) );
	}

	if ( post_ids.length > 0 ) {
		var action = 'nt_export';
		var url = _nt_export.ajax_url + '?' +
			'action=' + action + '&' +
			'_ajax_nonce=' + _nt_export.nonce + '&' +
			'post_ids=' + post_ids.join( ',' );

		// directs the user to the download file
		window.location = url;

		if ( alert && ! alert.classList.contains( 'nt-hidden' ) ) {
			alert.classList.add( 'nt-hidden' );
		}
	} else {
		if ( alert && alert.classList.contains( 'nt-hidden' ) ) {
			alert.innerHTML = 'Select some posts first, then hit the export button.';
			alert.classList.remove( 'nt-hidden' );
		}
	}
}
