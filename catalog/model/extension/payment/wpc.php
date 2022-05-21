<?php
class ModelExtensionPaymentWPC extends Model {	
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/wpc');
	  
		$method_data = array(
		  'code'     => 'wpc',
		  'title'    => $this->language->get('text_title'),
		  'terms'	=> '',
		  'sort_order' => $this->config->get('wpc_sort_order')
		);
	  
		return $method_data;
	}
}