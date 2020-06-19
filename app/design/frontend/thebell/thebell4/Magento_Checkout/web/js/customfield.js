require([ 'jquery'],function($){
	jQuery(window).on('load', function() {
		 setTimeout(function () {
			$('div[name="shippingAddress.country_id"]').css('display','none');
			$('div[name="shippingAddress.company"]').addClass('_required');
			$('div[name="shippingAddress.vat_id"]').addClass('_required');
			if(!$('input[name="need_invoice"]').is(':checked')){
				$('div[name="shippingAddress.company"]').css("display","none");
				$('div[name="shippingAddress.vat_id"]').css("display","none");
			}else{
				$('div[name="shippingAddress.company"]').css("display","block");
				$('div[name="shippingAddress.vat_id"]').css("display","block");
			}
			$('input[name="need_invoice"]').on('click', function() {
				if(!$('input[name="need_invoice"]').is(':checked')){
					if($('input[name="ind_company"]').is(':checked')){
						$('input[name="ind_company"]').click();
					}
					$('div[name="shippingAddress.company"]').css("display","none");
					$('div[name="shippingAddress.vat_id"]').css("display","none");
				}else{
					$('div[name="shippingAddress.company"]').css("display","block");
					$('div[name="shippingAddress.vat_id"]').css("display","block");
				}
			});
			$("#shipping-method-buttons-container button").click(function() {
				if($('input[name="need_invoice"]').is(':checked')){
						if($('input[name="company"]').val()==""){
							$('div[name="shippingAddress.company"]').addClass('_error');
							$('.companyrequire').remove();
							$("<div generated='true' class='mage-error companyrequire'>This is a required field.</div>").insertAfter('input[name="company"]');
						}else{
						   $('div[name="shippingAddress.company"]').removeClass('_error');
						   $('.companyrequire').remove();
						} 
						if($('input[name="vat_id"]').val()==""){
						   $('div[name="shippingAddress.vat_id"]').addClass('_error');
						   $('.vatidrequire').remove();
						   $("<div generated='true' class='mage-error vatidrequire'>This is a required field.</div>").insertAfter('input[name="vat_id"]');
						}else{
						   $('div[name="shippingAddress.vat_id"]').removeClass('_error');
						   $('.vatidrequire').remove();
						} 
						if($('input[name="company"]').val()==""){
							return false;
						}else{
						   if($('input[name="vat_id"]').val()==""){
							 return false;
						   }else{
							 return true;
						   }
						}
				}
			});
		 }, 9000);
		
	});
	
});
