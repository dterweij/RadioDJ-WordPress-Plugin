jQuery(document).on( 'click', '.requests-disabled .notice-dismiss', function() {
    jQuery.ajax({
        url: ajaxurl,
		method: 'POST',
        data: {
            action: 'rdj_dismiss_notice',
			notice: 'requests',
        }
    });
});
