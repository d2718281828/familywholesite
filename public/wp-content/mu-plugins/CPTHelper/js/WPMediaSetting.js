jQuery(function($){

  // Adapted from the Codex: https://codex.wordpress.org/Javascript_Reference/wp.media
  // Changed to allow multiple image selectors on the same page
  var frame;

  // ADD IMAGE LINK
  $('.wpms-upload-custom-img').on( 'click', function( event ){

    event.preventDefault();

    var question = $(this).closest('.wrap-input'), // find the specific question
    addImgLink = question.find('.wpms-upload-custom-img'),
    delImgLink = question.find( '.wpms-delete-custom-img'),
    imgContainer = question.find( '.wpms-img-container'),
    imgIdInput = question.find( '.wpms-img-id' );

    // console.log("A",question, imgContainer);
    // If the media frame already exists, reopen it.
    if ( frame ) {
      frame.open();
      return;
    }

    // Create a new media frame
    frame = wp.media({
      title: 'Select or Upload Media',
      button: {
        text: 'Use this media'
      },
      multiple: false  // Set to true to allow multiple files to be selected
    });


    // When an image is selected in the media frame...
    frame.on( 'select', function() {

      //console.log("S - selected",question, imgContainer);
      // Get media attachment details from the frame state
      var attachment = frame.state().get('selection').first().toJSON();

      // Send the attachment URL to our custom image input field.
      imgContainer.append( '<img src="'+attachment.url+'" alt="" style="max-width:100%;"/>' );

      // Send the attachment id to our hidden input
      imgIdInput.val( attachment.id );

      // Hide the add image link
      addImgLink.addClass( 'hidden' );

      // Unhide the remove image link
      delImgLink.removeClass( 'hidden' );
    });

    // Finally, open the modal on click
    frame.open();
  });


  // DELETE IMAGE LINK
  $( '.wpms-delete-custom-img').on( 'click', function( event ){

    event.preventDefault();

    var question = $(this).closest('.wrap-input'), // find the specific question
    addImgLink = question.find('.wpms-upload-custom-img'),
    delImgLink = question.find( '.wpms-delete-custom-img'),
    imgContainer = question.find( '.wpms-img-container'),
    imgIdInput = question.find( '.wpms-img-id' );

    // Clear out the preview image
    imgContainer.html( '' );

    // Un-hide the add image link
    addImgLink.removeClass( 'hidden' );

    // Hide the delete image link
    delImgLink.addClass( 'hidden' );

    // Delete the image id from the hidden input
    imgIdInput.val( '' );

  });

});
