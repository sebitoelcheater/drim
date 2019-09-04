//Set the shop page banner upload script
$('.me_upload_file_button').live('click', function( event ) { 
	event.preventDefault();
	var file_frame;
	var self = this;

		// If the media frame already exists, reopen it.
		if ( file_frame ) {
			file_frame.open();
			return;
		}
		
		// Create the media frame.
		file_frame = wp.media.frames.file_frame = wp.media({
			title: jQuery( this ).data( 'uploader_title' ),
			button: {
				text: jQuery( this ).data( 'uploader_button_text' ),
			},
			multiple: false
		});
		
		// When an file is selected, run a callback.
		file_frame.on( 'select', function() {

			attachment = file_frame.state().get('selection').first().toJSON();

			// Update front end
			$(self).closest("table").find(".me_banner_img_admin").attr('src', attachment.url );
			$(self).closest("table").find(".me_banner_img_admin").css('display', 'block' );
		});
		
		// Open the Modal
		file_frame.open();
	});

$('.me_remove_file').live('click', function( event ){
	$(this).closest("table").find(".me_banner_img_admin").attr('src', '' );
	$(this).closest("table").find(".me_banner_img_admin").css('display', 'none' );
});

	//script for save the setting data	
	$('#save_me_banners').live('click', function() {	
		var banner_images =  [];
		var categories =  [];
		$('.me_banner_img_admin').each(function( index ) {
			var banner_src = $(this).attr('src');
			var category = $(this).closest("table").find(".category option:selected").val();
			if(banner_src) {
				banner_images.push({'img_src': banner_src, 'category': category});
			}
		});

		$.ajax({
			type: "POST",
			url: ajaxurl,
			data: ({
				action: 'me_save_banner_data',
				banner_images: banner_images,
				security: $( '#category-ajax-nonce_field' ).val()
			}),
			success: function(response) {
				setTimeout(function() { alert("Setting saved."); }, 500);
			}
		});
	});