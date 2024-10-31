var nt_clone_post_ajax_php_vars;
function nt_clone_post_ajax() {
	var admin_url, ajax_url, post_id, post_type, nonce;
	jQuery( 'input#nt-button-clone' ).click(function() {
		admin_url = nt_clone_post_ajax_php_vars.admin_url;
		ajax_url = nt_clone_post_ajax_php_vars.ajax_url;
		post_id = nt_clone_post_ajax_php_vars.post_id;
		post_type = nt_clone_post_ajax_php_vars.post_type;
		nonce = nt_clone_post_ajax_php_vars.nonce;

		jQuery( 'input#nt-button-clone' ).prop( 'disabled', true );
		jQuery.ajax({
			url: ajax_url,
			type: 'POST',
			data: {
				action: 'nt_clone_post',
				_ajax_nonce: nonce,
				post_id: post_id,
				post_type: post_type
			}, success: function( url ) {
				var reg = new RegExp( '^(' + admin_url + ')' );
				var match = reg.test( url );
				if ( '' === url || ! match ) {
					jQuery( 'input#nt-button-clone' ).prop( 'disabled', false );
				} else if ( match ) {
					window.location = url;
				}
			}
		});
	});
}
jQuery( document ).ready( function() {
	nt_clone_post_ajax();
});