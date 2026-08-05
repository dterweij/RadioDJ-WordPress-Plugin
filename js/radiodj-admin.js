jQuery(document).on( 'click', '.rdj-requests-disabled .notice-dismiss', function() {
    jQuery.ajax({
        url: ajaxurl,
		method: 'POST',
        data: {
            action: 'rdj_dismiss_notice',
			notice: 'requests',
			_wpnonce: (typeof RadioDJAdmin !== 'undefined') ? RadioDJAdmin.nonce : ''
        }
    });
});
