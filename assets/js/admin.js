/**
 * Plugin Template admin js.
 *
 *  @package Woo Wizard Product Registration/JS
 */
 jQuery(function($) {
	let searchRequest = null;
	$( document ).ready(function() {
		var searchRequest = null;
		let theDate = $("#theDate").val()
		$( "#datepicker" ).datepicker({ 
			dateFormat: 'dd-mm-yy',
			defaultDate: theDate,
			setDate: new Date(theDate),
		});
		dateChanged = false;
		serialChanged = false;

		$("#save-serial").prop("disabled", true);
		$('#save-serial').text('Change Serial To a New Value to Save');
		$('#serial-input').on("change paste keyup",function(){
			serialChanged = true;
			$("#save-serial").prop("disabled", false);
			$('#save-serial').text('Update Serial');
		})

		$('#datepicker').on("change",function(){
			dateChanged = true;
			$("#save-serial").prop("disabled", false);
			$('#save-serial').text('Update Registration Date');
		})

		$('.edit-date').on('click', function(){
			$('.edit-date-time').show()
		})

		if (dateChanged && serialChanged){
			$('#save-serial').text('Update Serial and Registration Date');
		}

		$("#save-serial").on('click', function(event){
			var d = new Date();
			var currentTime = d.toLocaleTimeString();
			event.preventDefault();
			$('.edit-date-time').hide()
			$('#save-serial').text('Submitting...');
			  // We'll pass this variable to the PHP function example_ajax_request
			  var serial = $('#serial-input').val()

			  if ($('date-registered').text() == ''){
				  setDate = new Date().toISOString().replace(/T.*/,'').split('-').reverse().join('-');
			  } else {
				if ($( "#datepicker" ).val()) setDate = $("#datepicker").val();
				if (!$( "#datepicker" ).val()) setDate = $("#datepicker").text();
			  }
			  
			  //new Date().toISOString().replace(/T.*/,'').split('-').reverse().join('-')
			  // This does the ajax request
			  $.ajax({
				  url: ajaxurl,
				  data: {
					  'action': 'example_ajax_request',
					  'serial' : serial,
					  'postID' : $("#post_ID").val(),
					  'time' : currentTime,
					  'date' : setDate,
				  },
				  success:function(data) {
					  if (dateChanged){
						$('.date-registered').text($('#datepicker').val());
						$('.time-registered').text(currentTime);
					  }
					  // This outputs the result of the ajax request
					  console.log(data);
					  $('#save-serial').text('Updated!');
					  $("#save-serial").prop("disabled",true);
				  },
				  error: function(errorThrown){
					  console.log(errorThrown);
				  }
			  });  		
		})
		$('#serial-input').on('keyup', function() {
            if ($('#serial-input').val().length > 2) {
                searchRequest = jQuery.ajax({
                    url:  ajaxurl,
                    type: 'post',
                    data: {
                        action: 'verify_serial',
                        keyword: jQuery('#serial-input').val()
                    },
                    beforeSend: function() {
						$('#save-serial').text('Checking Validity');
						$("#save-serial").prop("disabled",true);
                        if (searchRequest != null) {
                            searchRequest.abort();
                        }
                    },
                    success: function(data) {
                       if (data == true){
						   $('#save-serial').html('This Serial Exists Already!<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" display="block" id="CircleAlert"><circle cx="12" cy="12" r="10"/><path d="M12 7v6m0 3.5v.5"/></svg>');
						   $("#save-serial").prop("disabled",true);
					   } else if (data == false){
							$('#save-serial').html('Update Serial<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" display="block" id="CircleCheck"><path d="M8 12.5l3 3 5-6"/><circle cx="12" cy="12" r="10"/></svg>');
							$("#save-serial").prop("disabled",false);
					   }
                    }
                });
            }
        });	
	});
});
