<?php
/**
 * Description of vnpay_payment
 *
 * @author TamDT
 */
error_reporting(0); 
class ControllerExtensionPaymentVnpayPayment extends Controller {
    private $error = array();
    public function index() {
        $this->load->language('extension/payment/vnpay_payment');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_payment'] = $this->language->get('text_payment');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['heading_title'] = $this->language->get('heading_title');
        $data['entry_order_status'] = $this->language->get('entry_order_status');
        $data['entry_access_code'] = $this->language->get('entry_access_code');
        $data['entry_url'] = $this->language->get('entry_url');
        $data['entry_types'] = $this->language->get('entry_types');
        $data['entry_secretkey'] = $this->language->get('entry_secretkey');
        $data['error_secretkey'] = $this->language->get('error_secretkey');
        $data['error_access_code'] = $this->language->get('error_access_code');
        $data['error_url'] = $this->language->get('error_url');
        $data['entry_pending_status'] = $this->language->get('entry_pending_status');
        $data['entry_failed_status'] = $this->language->get('entry_failed_status');
        $data['entry_completed_status'] = $this->language->get('entry_completed_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        /*DEFINE OPENCART VERSION AND BUILD A FLOAT TYPE VARIABLE FOR MULTIVERSION OPENCART MODULE*/
        global $classPrefix;
        $OpenCartVersion = floatval(VERSION);
        switch ($OpenCartVersion) {
            case ($OpenCartVersion  >=  2.3 && $OpenCartVersion < 3.0):
                $OpenCartVersion    =   2.3;
                $token              =   'token=' . $this->session->data['token'];
                $urlBase            =   'extension';
                $classPrefix        =   '';
                break;
            case ($OpenCartVersion  >=  3.0 && $OpenCartVersion < 4.0):
                $OpenCartVersion    =   3.0;
                $token              =   'user_token=' . $this->session->data['user_token'];
                $urlBase            =   'marketplace';
                $classPrefix        =   'payment_';
                break;
        }
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

            $this->model_setting_setting->editSetting($classPrefix.'vnpay_payment', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link($urlBase.'/extension', $token . '&type=payment', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

	if (isset($this->error['error_access_code'])) {
		$data['error_access_code'] = $this->error['error_access_code'];
	} else {
		$data['error_access_code'] = '';
		}
        if (isset($this->error['error_secretkey'])) {
		$data['error_secretkey'] = $this->error['error_secretkey'];
	} else {
		$data['error_secretkey'] = '';
	}

	if (isset($this->error['error_url'])) {
		$data['error_url'] = $this->error['error_url'];
	} else {
		$data['error_url'] = '';
		}
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', $token , true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link($urlBase.'/extension', $token . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/vnpay_payment', $token , true)
        );

        $data['action'] = $this->url->link('extension/payment/vnpay_payment', $token , true);

        $data['cancel'] = $this->url->link($urlBase.'/extension', $token . '&type=payment', true);
        
        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
      
        //Terminal code
        if (isset($this->request->post[$classPrefix.'vnpay_payment_access_code'])) 
        {
            $data[$classPrefix.'vnpay_payment_access_code'] = $this->request->post[$classPrefix.'vnpay_payment_access_code'];
        } 
        else 
        {
            $data[$classPrefix.'vnpay_payment_access_code'] = $this->config->get($classPrefix.'vnpay_payment_access_code');
        }
        //Secretkey VNPAY
        if (isset($this->request->post[$classPrefix.'vnpay_payment_secretkey'])) 
        {
            $data[$classPrefix.'vnpay_payment_secretkey'] = $this->request->post[$classPrefix.'vnpay_payment_secretkey'];
        }
        else 
        {
            $data[$classPrefix.'vnpay_payment_secretkey'] = $this->config->get($classPrefix.'vnpay_payment_secretkey');
        }
        //Vnpay Url
        if (isset($this->request->post[$classPrefix.'vnpay_payment_url']))
        {
            $data[$classPrefix.'vnpay_payment_url'] = $this->request->post[$classPrefix.'vnpay_payment_url'];
        }
        else
        {
            $data[$classPrefix.'vnpay_payment_url'] = $this->config->get($classPrefix.'vnpay_payment_url');
        }
        
        // Order Status pending
        if (isset($this->request->post[$classPrefix.'vnpay_payment_order_pending_status_id'])) 
        {
            $data[$classPrefix.'vnpay_payment_order_pending_status_id'] = $this->request->post[$classPrefix.'vnpay_payment_order_pending_status_id'];
        }
        else 
        {
            $data[$classPrefix.'vnpay_payment_order_pending_status_id'] = $this->config->get($classPrefix.'vnpay_payment_order_pending_status_id');
        }
        
        // Order Status failed
        if (isset($this->request->post[$classPrefix.'vnpay_payment_order_failed_status_id'])) 
        {
            $data[$classPrefix.'vnpay_payment_order_failed_status_id'] = $this->request->post[$classPrefix.'vnpay_payment_order_failed_status_id'];
        }
        else 
        {
            $data[$classPrefix.'vnpay_payment_order_failed_status_id'] = $this->config->get($classPrefix.'vnpay_payment_order_failed_status_id');
        }
        
        // Order Status complete
        if (isset($this->request->post[$classPrefix.'vnpay_payment_order_status_id'])) 
        {
            $data[$classPrefix.'vnpay_payment_order_status_id'] = $this->request->post[$classPrefix.'vnpay_payment_order_status_id'];
        }
        else 
        {
            $data[$classPrefix.'vnpay_payment_order_status_id'] = $this->config->get($classPrefix.'vnpay_payment_order_status_id');
        }
        
        // Vnpay_payment status
        if (isset($this->request->post[$classPrefix.'vnpay_payment_status']))
        {
            $data[$classPrefix.'vnpay_payment_status'] = $this->request->post[$classPrefix.'vnpay_payment_status'];
        }
        else
        {
            $data[$classPrefix.'vnpay_payment_status'] = $this->config->get($classPrefix.'vnpay_payment_status');
        }
        //Sort Order
        if (isset($this->request->post[$classPrefix.'vnpay_payment_sort_order']))
        {
            $data[$classPrefix.'vnpay_payment_sort_order'] = $this->request->post[$classPrefix.'vnpay_payment_sort_order'];
	}
        else 
        {
            $data[$classPrefix.'vnpay_payment_sort_order'] = $this->config->get($classPrefix.'vnpay_payment_sort_order');
        }
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/vnpay_payment', $data));
    }
    
        protected function validate() {
        global $classPrefix;
        
        if (!$this->user->hasPermission('modify', 'extension/payment/vnpay_payment'))  $this->error['warning'] = $this->language->get('error_permission');
        if (!$this->request->post[$classPrefix.'vnpay_payment_access_code'])           $this->error['access_code'] = $this->language->get('error_access_code');
        if (!$this->request->post[$classPrefix.'vnpay_payment_secretkey'])          $this->error['secretkey'] = $this->language->get('error_secretkey');
      

        return !$this->error;
    }
    
}
