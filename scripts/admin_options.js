/**
 * Holds localized data passed on from wp_localize_script().
 */
var _nt_options;

document.onready = function() {

	/**
	 * Checks to see if a "success" parameter was passed in the URL for the options page. If the
	 * parameter is found, the user has most-likely been redirected to the page after a successful
	 * update to the plugin's options; of course, this isn't guaranteed.
	 *
	 * A success alert will be presented at the top of the page.
	 */
	var query_match = window.location.search.match( /^(.*&|\?)success=([^=&]*)$/ );
	if ( query_match && 1 === parseInt( query_match[2] ) ) {
		var alert_success = document.querySelector( '.alert-success' );
		alert_success.innerHTML = 'Options updated!';
		alert_success.classList.remove( 'nt-hidden' );

		var alert_danger = document.querySelector( '.alert-danger' );
		alert_danger.classList.add( 'nt-hidden' );
	}

	document.querySelector( '.nt-btn-save' ).addEventListener( 'click', function() {
		var values_array = [];
		var option_wraps = document.querySelectorAll( '.nt-option-wrap' );

		for ( var i = 0; i < option_wraps.length; i++ ) {
			var checkbox = option_wraps[ i ].querySelector( '.nt-option-input[type="checkbox"]' );
			var feature_name = option_wraps[ i ].querySelector( '.nt-option-input[type="hidden"]' );
			values_array.push( [ feature_name.value, checkbox.checked ? 1 : 0 ] );
		}

		if ( values_array.length > 0 ) {
			jQuery.ajax({
				url: _nt_options.ajax_url,
				type: 'POST',
				data: {
					action: 'nt_save_options',
					_ajax_nonce: _nt_options.nonce,
					features: values_array
				}, success: function( res ) {
					if ( 1 === parseInt( res ) ) {
						var admin_url = window.location.origin + window.location.pathname;
						var query = '?page=' + _nt_options.page_slug + '&success=1';

						/**
						 * Re-constructs the URL without any potential hash values appended, then
						 * refreshes the page to trigger the 'admin_menu' hook. This will make the
						 * changes appear immediately, rather than after the user has navigated
						 * to another page themselves.
						 *
						 * Format: http[s]://[admin-url]?[query]
						 */
						window.location.href = admin_url + query;
					} else {
						var alert_danger = document.querySelector( '.alert-danger' );
						if ( alert_danger && alert_danger.classList.contains( 'nt-hidden' ) ) {
							alert_danger.innerHTML = 'There was a problem! Refresh ' +
								'and try again! If that doesn\'t work, log out and log back in.';
							alert_danger.classList.remove( 'nt-hidden' );
						}
					}
				}
			});
		}
	});
};
