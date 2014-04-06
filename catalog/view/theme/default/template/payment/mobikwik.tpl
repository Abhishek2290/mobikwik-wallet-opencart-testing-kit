<form action="https://test.mobikwik.com/mobikwik/wallet"  method="post" id="checkout_mobikwik" onsubmit="return validate_mobikwik();">
	<input type="hidden" name=email value="<?php echo $billing_cust_email; ?>"> 	
	<input type="hidden" name="mid" value="<?php echo $Merchant_Id; ?>">
	<input type="hidden" name="amount" value="<?php echo $Amount; ?>">
	<input type="hidden" name="orderid" value="<?php echo $Order_Id; ?>">	
	<input type="hidden" name="redirecturl" value="<?php echo $Redirect_Url; ?>">
	<input type="hidden" name="merchantname" value="<?php echo $Merchant_Name; ?>">	
    <input type="hidden" name="checksum" value="<?php echo $checksum; ?>">
	<div class="mobikiwik_mobile"><label><?php echo $entry_cell; ?> </label><input type="text" name="cell" id="mobikwik_cell" value="<?php echo $billing_cust_tel; ?>"><span style="font-size:10px;font-weight:bold;">&nbsp;(10 digit number, without leading zero or +91)</span></div>
</form>
<div class="buttons"><span class="payment_inclusive"></span>
  <div class="right"><a id="button-confirm" class="button" onclick="submitForm_mobikwik();"><span><?php echo $button_confirm; ?></span></a></div>
</div>

<script type="text/javascript"><!--

function submitForm_mobikwik(){
	 $('#checkout_mobikwik').submit();	
}
function validate_mobikwik() {
	
	var mobileRegex =/^[1-9]{1}[0-9]{9}$/;
	$('#mobikwik_cell').val($.trim($('#mobikwik_cell').val()));
	var cell_number = $('#mobikwik_cell').val();
	
	$('.warning').remove();
	if(cell_number == "") {
         $('.mobikiwik_mobile').append('<div class="warning" style="display: none;"><?php echo $error_cell_blank?><img src="catalog/view/theme/default/image/close.png" alt="" class="close" /></div>');
         $('#mobikwik_cell').focus();
         $('.warning').fadeIn('slow');
	}	
	else if(!mobileRegex.test(cell_number)){
         $('.mobikiwik_mobile').append('<div class="warning" style="display: none;"><?php echo $error_cell_invalid?><img src="catalog/view/theme/default/image/close.png" alt="" class="close" /></div>');
		 $('#mobikwik_cell').focus();
		 $('.warning').fadeIn('slow');
	}
	else { 
		return true;
	}
	return false;
}
//--></script>