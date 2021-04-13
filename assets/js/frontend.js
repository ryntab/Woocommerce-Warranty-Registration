/**
 * Plugin Template frontend js.
 *
 *  @package Woo Wizard Product Registration/JS
 */

 jQuery(function($) {
	$( document ).ready(function() {
		$('.card-form__button').on('click', function(){
			$.ajax({
				url: ajaxurl,
				data: {
					'action': 'get_order_by_serial',
					'serial' : $('#serialNumber').val(),
				},
				success:function(data) {
					console.log(data);
				},
				error: function(errorThrown){
					console.log(errorThrown);
				}
			});  
		})			
	})
});
