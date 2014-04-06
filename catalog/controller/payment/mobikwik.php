<?php
class ControllerPaymentMobikwik extends Controller {
    // generate checksum at time of request
    protected function getChecksumMobikwik($MerchantId,$Amount,$OrderId ,$WorkingKey){
    	
    	$algo = 'sha256';    	
    	$checksum_string = "'{$MerchantId}''{$OrderId}'";
    	$checksum = hash_hmac($algo, $checksum_string, $WorkingKey);    	
    	return $checksum;
    }
    
    // calculate checksum from parameter string
    protected function calculateChecksum($all ,$WorkingKey){
    	$algo = 'sha256';
    	$checksum = hash_hmac($algo, $all, $WorkingKey);    	
    	return $checksum;
    }
    
    // verify checksum returned from mobikwik
    protected function verifyChecksum($checksumReceived, $all ,$WorkingKey){
    	$checksum = $this->calculateChecksum($all, $WorkingKey);
        $bool = 0;
        if($checksum==$checksumReceived){
            $bool = 1;
        }	
    	return $bool;
    }
	
	// generate checksum at time of response.
    protected function validateChecksumMobikwik($MerchantId,$statuscode,$orderid,$refid,$amount,$statusmessage,$ordertype,$WorkingKey){
    	 
    	$algo = 'sha256';
    	$checksum_string = "'{$statuscode}''{$orderid}''{$refid}''{$amount}''{$statusmessage}''{$ordertype}'";    	
    	$checksum = hash_hmac($algo, $checksum_string, $WorkingKey);    	    	 
    	return $checksum;
    }
    
    protected function verifyTransaction($MerchantId, $OrderId , $Amount,$WorkingKey){
    	$action = "gettxnstatus"; // fixed value
    	$return = array();
    	
    	$checksum = $this->getChecksumMobikwik($MerchantId,$Amount,$OrderId ,$WorkingKey);
    	
    	$url = "https://test.mobikwik.com/mobikwik/checkstatus";
        
        $version = '2';
    	
		$fields = "mid=$MerchantId&orderid=$OrderId&checksum=$checksum&ver=2";
    	
    	// is cURL installed yet?
    	if (!function_exists('curl_init')){
    		die('Sorry cURL is not installed!');
    	}
    	// then let's create a new cURL resource handle
    	$ch = curl_init();
    	 
    	// Now set some options (most are optional)
    	 
    	// Set URL to hit
    	curl_setopt($ch, CURLOPT_URL, $url);
    	 
    	// Include header in result? (0 = yes, 1 = no)
    	curl_setopt($ch, CURLOPT_HEADER, 0);
    	 
    	curl_setopt($ch, CURLOPT_POST, 1);
    	 
    	curl_setopt($ch, CURLOPT_POSTFIELDS,  $fields);
    	 
    	// Should cURL return or print out the data? (true = return, false = print)
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	 
    	// Timeout in seconds
    	curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	 
    	// Download the given URL, and return output
    	$outputXml = curl_exec($ch);
    	error_log("excecuted");
    	// Close the cURL resource, and free system resources
    	curl_close($ch);
    	error_log("The response received is = " . $outputXml);
    	$outputXmlObject =  simplexml_load_string($outputXml);
		
		
    	
    	$recievedChecksum = $this->validateChecksumMobikwik($MerchantId,
    											$outputXmlObject->statuscode,  
    											$outputXmlObject->orderid,
    											$outputXmlObject->refid,
    											$outputXmlObject->amount,
    											$outputXmlObject->statusmessage,
    											$outputXmlObject->ordertype,
    											$WorkingKey);
    	
    	if($OrderId == 	$outputXmlObject->orderid && $outputXmlObject->amount == $Amount && $outputXmlObject->checksum == $recievedChecksum){

    		$return['statuscode'] = $outputXmlObject->statuscode;
    		$return['orderid'] 	= $outputXmlObject->orderid;
    		$return['refid'] 		= $outputXmlObject->refid;
    		$return['amount']	 	= $outputXmlObject->amount;
    		$return['statusmessage'] 	= $outputXmlObject->statusmessage;
    		$return['ordertype']		= $outputXmlObject->ordertype;
    		$return['checksum']		= $outputXmlObject->checksum;
    		
    	}

    	return $return;
    }
  	protected function index() {
	       
		$this->language->load('payment/mobikwik');
		
		$this->data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
		$this->data['entry_cell'] = $this->language->get('entry_cell');
		$this->data['error_cell_blank'] = $this->language->get('error_cell_blank');
		$this->data['error_cell_invalid'] = $this->language->get('error_cell_invalid');		
		
		
					
            //mobikwik  variables start
            $this->data['Merchant_Id'] = $this->config->get('mobikwik_MID');//This id(also User Id)  available at "Generate Working Key" of "Settings & Options" 
            $this->data['Merchant_Name'] = $this->config->get('mobikwik_MName');
        	$this->data['Redirect_Url'] = $this->url->link('payment/mobikwik/callback'); //your redirect URL where your customer will be redirected after authorisation from Mobikwik        
        	$this->data['WorkingKey'] = $this->config->get('mobikwik_Wkey');//put in the 32 bit alphanumeric key in the quotes provided here.Please note that get this key ,login to your Mobikwik merchant account and visit the "Generate Working Key" section at the "Settings & Options" page.
        
        	$this->currency->set('INR');
            
            if ($this->currency->getCode()=='INR'){
        	    $amount = $order_info['total'] ;   
        	} else {
        	    $amount = $this->currency->convert($order_info['total'], $this->currency->getCode(), 'INR') ;
        	}
        	$this->data['Amount'] = round($amount);  // needed for mobikwik payment gateway
            $this->data['Order_Id'] = "ORDERID". $this->session->data['order_id'] ; //make order Id longer as mobikwik need longer id
        	            
            $this->data['billing_cust_name']=html_entity_decode($order_info['payment_firstname'] . " " . $order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');
        	$this->data['billing_cust_address']=html_entity_decode($order_info['payment_address_1'] . " " . $order_info['payment_address_2'], ENT_QUOTES, 'UTF-8');
        	$this->data['billing_cust_state']=html_entity_decode($order_info['payment_zone'], ENT_QUOTES, 'UTF-8');
        	$this->data['billing_cust_country']=html_entity_decode($order_info['payment_country'], ENT_QUOTES, 'UTF-8');
        	$this->data['billing_cust_tel']=html_entity_decode($order_info['telephone'], ENT_QUOTES, 'UTF-8');
        	$this->data['billing_cust_email']=html_entity_decode($order_info['email'], ENT_QUOTES, 'UTF-8');
        	$this->data['billing_city'] = html_entity_decode($order_info['payment_city'], ENT_QUOTES, 'UTF-8');
        	$this->data['billing_zip'] = html_entity_decode($order_info['payment_postcode'], ENT_QUOTES, 'UTF-8');
        	
            if ($this->cart->hasShipping()) {
                
                //mobikwik shipping
                $this->data['delivery_cust_name']=html_entity_decode($order_info['shipping_firstname'] . " " . $order_info['shipping_lastname'], ENT_QUOTES, 'UTF-8');
            	
                if ($order_info['shipping_address_2']) {
           		   $this->data['delivery_cust_address']=html_entity_decode($order_info['shipping_address_1'] . " " . $order_info['shipping_address_2'], ENT_QUOTES, 'UTF-8');
    			} else {
                   $this->data['delivery_cust_address']=html_entity_decode($order_info['shipping_address_1'], ENT_QUOTES, 'UTF-8'); 
    			}
                
                $this->data['delivery_cust_state'] = html_entity_decode($order_info['shipping_zone'], ENT_QUOTES, 'UTF-8');
            	$this->data['delivery_cust_country'] = html_entity_decode($order_info['shipping_country'], ENT_QUOTES, 'UTF-8');
            	$this->data['delivery_cust_tel']=html_entity_decode($order_info['telephone'], ENT_QUOTES, 'UTF-8');
            	$this->data['delivery_cust_notes']=html_entity_decode($order_info['comment'], ENT_QUOTES, 'UTF-8');
            	$this->data['delivery_city'] = html_entity_decode($order_info['shipping_city'], ENT_QUOTES, 'UTF-8');
            	$this->data['delivery_zip'] = html_entity_decode($order_info['shipping_postcode'], ENT_QUOTES, 'UTF-8');  
                //mobikwik shipping
                
    		} else {
    			$this->data['delivery_cust_name']=html_entity_decode($order_info['payment_firstname'] . " " . $order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');
            	$this->data['delivery_cust_address']=html_entity_decode($order_info['payment_address_1'] . " " . $order_info['payment_address_2'], ENT_QUOTES, 'UTF-8');
            	$this->data['delivery_cust_state'] = html_entity_decode($order_info['payment_zone'], ENT_QUOTES, 'UTF-8');
            	$this->data['delivery_cust_country'] = html_entity_decode($order_info['payment_country'], ENT_QUOTES, 'UTF-8');
            	$this->data['delivery_cust_tel']=html_entity_decode($order_info['telephone'], ENT_QUOTES, 'UTF-8');
            	$this->data['delivery_cust_notes']=html_entity_decode($order_info['comment'], ENT_QUOTES, 'UTF-8');
            	$this->data['delivery_city'] = html_entity_decode($order_info['payment_city'], ENT_QUOTES, 'UTF-8');
            	$this->data['delivery_zip'] = html_entity_decode($order_info['payment_postcode'], ENT_QUOTES, 'UTF-8');		
    		}
            
            $all = "'" . $this->data['billing_cust_tel'] . "''" . $this->data['billing_cust_email'] . "''" . $this->data['Amount'] . "''" . $this->data['Order_Id'] . "''" . $this->data['Redirect_Url'] . "''" . $this->data['Merchant_Id'] . "'";
            
            $this->data['checksum'] = $this->calculateChecksum($all, $this->data['WorkingKey']);
            
			if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/mobikwik.tpl')) {
				$this->template = $this->config->get('config_template') . '/template/payment/mobikwik.tpl';
			} else {
				$this->template = 'default/template/payment/mobikwik.tpl';
			}	
    
			$this->render();
		//}
	}
	
	public function callback() {
	   
        $this->load->language('payment/mobikwik');		
       
		if (isset($_REQUEST['orderid'])) {			
            $Order_Id = str_replace("ORDERID","", $_REQUEST['orderid']);
		} else {
			$Order_Id = 0;
		}
		
		
		$this->load->model('checkout/order');
		
		$order_info = $this->model_checkout_order->getOrder($Order_Id);
		$isDataInvalidate = false;
        if ($order_info) {
        
    		//mobikwik start
            $WorkingKey = $this->config->get('mobikwik_Wkey') ;        	
            $Merchant_Id = $this->config->get('mobikwik_MID');
                        
	        $Amount = $_REQUEST['amount'];
	        $statuscode = $_REQUEST['statuscode'];
	        $mid = $_REQUEST['mid'];
	        $statusmessage = $_REQUEST['statusmessage'];
            $order_id = $_REQUEST['orderid'];
            $checksum = $_REQUEST['checksum'];
            
            $allParamValue = "'" . $statuscode . "''" . $order_id . "''" . $Amount . "''" . $statusmessage . "''" . $mid . "'";
            
            if($checksum != null){
            	$isChecksumValid = $this->verifyChecksum($checksum, $allParamValue, $WorkingKey);
            }
            
        	$this->currency->set('INR');
        	
        	if ($this->currency->getCode()=='INR'){
        		$Oldamount = $order_info['total'] ;
        	} else {
        		$Oldamount = $this->currency->convert($order_info['total'], $this->currency->getCode(), 'INR') ;
        	}
        	$Oldamount = round($Oldamount);  // round off needed for mobikwik payment gateway
         
            if($isChecksumValid){
                if($Amount == $Oldamount) {
            		// Amount could not verify so display error
            		$response = $this->verifyTransaction($Merchant_Id,"ORDERID".$Order_Id , $Amount,$WorkingKey);
            		if($response){
            			
            			if(isset($response['statuscode']) && $response['statuscode'] == 0){
            				//transaction successful
    	        			$order_status_id = $this->config->get('mobikwik_success_status_id');
    	        			$this->model_checkout_order->confirm($Order_Id, $order_status_id);
    	        			
    						$url = 'http'.(isset($_SERVER['HTTPS'])?'s':'').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    						$url_array = explode('index.php',$url);
    						$url = $url_array[0];
    						$this->redirect($url . 'index.php?route=checkout/success');
    						//Here you need to put in the routines for a successful
    	        			//transaction such as sending an email to customer,
    	        			//setting database status, informing logistics etc etc
            			}
            			else {
            				// Transaction Declined //
            				$this->data['breadcrumbs'] = array();        				
            				$order_status_id = $this->config->get('mobikwik_failed_status_id');
            				$this->model_checkout_order->confirm($Order_Id, $order_status_id);
            				
            				$this->data['heading_title'] = $this->language->get('text_failed');
            				$this->data['text_message'] = sprintf($this->language->get('text_failed_message'), HTTPS_SERVER . 'index.php?route=information/contact');
            				$this->data['button_continue'] = $this->language->get('button_continue');
            				$this->data['continue'] = HTTPS_SERVER . 'index.php?route=common/home';
            				
            				if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/common/success.tpl')) {
            					$this->template = $this->config->get('config_template') . '/template/common/success.tpl';
            				} else {
            					$this->template = 'default/template/common/success.tpl';
            				}
            				
            				$this->children = array(
            						'common/column_left',
            						'common/column_right',
            						'common/content_top',
            						'common/content_bottom',
            						'common/footer',
            						'common/header'
            				);
            				
            				//$this->response->setOutput($this->render());
            				$this->response->setOutput($this->render(TRUE), $this->config->get('config_compression'));
            				//Here you need to put in the routines for a failed transaction such as sending an email to customer setting database status etc etc
            				
            			}
            		}
            		else {
    					$order_status_id = $this->config->get('mobikwik_failed_status_id');
            			$this->model_checkout_order->confirm($Order_Id, $order_status_id);
            			$isDataInvalidate = true;
            		}
            	}
            	else {
    					$order_status_id = $this->config->get('mobikwik_failed_status_id');
            			$this->model_checkout_order->confirm($Order_Id, $order_status_id);
            		$isDataInvalidate = true;
            	}
            }else{
                $order_status_id = $this->config->get('mobikwik_failed_status_id');
    			$this->model_checkout_order->confirm($Order_Id, $order_status_id);
    			$isDataInvalidate = true;
            }
           
            //mobikwik stop
            //$this->cart->clear();
        }
        
        
        if($isDataInvalidate){
        	// received data could not validate
            $this->data['breadcrumbs'] = array(); 
            
            $this->data['heading_title'] = $this->language->get('text_mobikwik_error');
			$this->data['text_message'] = sprintf($this->language->get('text_mobikwik_eorror_message'), HTTPS_SERVER . 'index.php?route=information/contact');
			$this->data['button_continue'] = $this->language->get('button_continue');
			$this->data['continue'] = HTTPS_SERVER . 'index.php?route=common/home';
            
            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/common/success.tpl')) {
				$this->template = $this->config->get('config_template') . '/template/common/success.tpl';
			} else {
				$this->template = 'default/template/common/success.tpl';
			}
            
            $this->children = array(
    			    'common/column_left',
					'common/column_right',
					'common/content_top',
					'common/content_bottom',
					'common/footer',
					'common/header'
    		);
            
            //$this->response->setOutput($this->render());
            $this->response->setOutput($this->render(TRUE), $this->config->get('config_compression')); 
        }
	}
}
?>
