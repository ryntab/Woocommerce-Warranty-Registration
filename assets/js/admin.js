/**
 * Plugin Template admin js.
 *
 *  @package Woo Wizard Product Registration/JS
 */
 jQuery(function($) {
	$( document ).ready(function() {
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
	});
});
