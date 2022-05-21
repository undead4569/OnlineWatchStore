<?php
/**
 * WPC OpenCart Plugin
 *
 * @package PayCEC Payment Gateway
 * @author WPC Technical Team
 * @version 1.0
 */

class ControllerExtensionPaymentWPC extends Controller {
    private $error = array();

    public function index() {

        $this->load->language('extension/payment/wpc');


        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') &&
            $this->validate()) {;

            $this->model_setting_setting->editSetting('payment_wpc', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }


        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');

        $data['entry_merchant_name'] = $this->language->get('entry_merchant_name');
        $data['entry_merchant_key'] = $this->language->get('entry_merchant_key');
        $data['entry_status'] = $this->language->get('entry_status');
		
		$data['help_test'] = $this->language->get('help_test');
        $data['entry_test'] = $this->language->get('entry_test');
		
        $data['wpc_url'] = parse_url (HTTP_SERVER);
		
        $data['help_merchant_key'] = $this->language->get('help_merchant_key');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['merchant_name'])) {
            $data['error_merchant_name'] = $this->error['merchant_name'];
        } else {
            $data['error_merchant_name'] = '';
        }

        if (isset($this->error['merchant_key'])) {
            $data['error_merchant_key'] = $this->error['merchant_key'];
        } else {
            $data['error_merchant_key'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/wpc', 'user_token=' . $this->session->data['user_token'], true)
        );

        if (isset($this->request->post['payment_wpc_test'])) {
            $data['payment_wpc_test'] = $this->request->post['payment_wpc_test'];
        } else {
            $data['payment_wpc_test'] = $this->config->get('payment_wpc_test');
        }

        $data['action'] = $this->url->link('extension/payment/wpc', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        if (isset($this->request->post['payment_wpc_merchant_name'])) {
            $data['payment_wpc_merchant_name'] = $this->request->post['payment_wpc_merchant_name'];
        } else {
            $data['payment_wpc_merchant_name'] = $this->config->get('payment_wpc_merchant_name');
        }
        if (isset($this->request->post['payment_wpc_merchant_key'])) {
            $data['payment_wpc_merchant_key'] = $this->request->post['payment_wpc_merchant_key'];
        } else {
            $data['payment_wpc_merchant_key'] = $this->config->get('payment_wpc_merchant_key');
        }
   
        if (isset($this->request->post['payment_wpc_status'])) {
            $data['payment_wpc_status'] = $this->request->post['payment_wpc_status'];
        } else {
            $data['payment_wpc_status'] = $this->config->get('payment_wpc_status');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/wpc',
            $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/payment/wpc')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_wpc_merchant_name']) {
            $this->error['merchant_name'] = $this->language->get('error_merchant_name');
        }

        if (!$this->request->post['payment_wpc_merchant_key']) {
            $this->error['merchant_key'] = $this->language->get('error_merchant_key');
        }

        return !$this->error;
    }


}