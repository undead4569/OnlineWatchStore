<?php

/**
 * Description of vnpay_payment
 *
 * @author TamDT
 */
class ControllerExtensionPaymentVnpayPayment extends Controller {
    
        public function index() {
        $this->load->language('extension/payment/vnpay_payment');
        $data['text_response'] = $this->language->get('text_response');
        $data['text_success'] = $this->language->get('text_success');
        $data['text_failure'] = $this->language->get('text_failure');
        $data['text_failure_wait'] = $this->language->get('text_failure_wait');
        $data['continue'] = $this->url->link('extension/payment/vnpay_payment/checkout', '', true);
        unset($this->session->data['vnpay_payment']);
        return $this->load->view('extension/payment/vnpayredirect', $data);
    }
    public function checkout()
        {
        /*DEFINE OPENCART VERSION AND BUILD A FLOAT TYPE VARIABLE FOR MULTIVERSION OPENCART MODULE*/
            $OpenCartVersion = floatval(VERSION);
            switch ($OpenCartVersion) {
                case ($OpenCartVersion  >=  2.3 && $OpenCartVersion < 3.0):
                    $OpenCartVersion    =   2.3;
                    $classPrefix        =   '';
                    break;
                case ($OpenCartVersion  >=  3.0 && $OpenCartVersion < 4.0):
                    $OpenCartVersion    =   3.0;
                    $classPrefix        =   'payment_';
                    break;
            }
            
        $this->load->model('extension/payment/vnpay_payment');
        $this->load->model('checkout/order');
        
        $order_status_id = $this->config->get($classPrefix.'vnpay_payment_order_pending_status_id');
        $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $order_status_id);
        
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $order_id = $this->session->data['order_id'];
        $total_amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
        $date = new DateTime(); //this returns the current date time
        $result = $date->format('Y-m-d-H-i-s');
        $krr = explode('-', $result);
        $result1 = implode("", $krr);
        $this->session->data['time'] = $result1;
        $vnp_Url = $this->config->get($classPrefix.'vnpay_payment_url');
        $vnp_Returnurl = $this->config->get('config_ssl') . "index.php?route=extension/payment/vnpay_payment/checkoutReturn";
        $hashSecret = $this->config->get($classPrefix.'vnpay_payment_secretkey');
        $vnp_Locale = $this->language->get('code');
        $vnp_OrderInfo = 'Thanh toan don hang :' . $order_id;
        $vnp_TerminalCode = $this->config->get($classPrefix.'vnpay_payment_access_code');
        $vnp_Amount = $total_amount * 100;
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
        $Odarray = array(
            "vnp_TmnCode" => $vnp_TerminalCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => $result1,
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => "other",
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $order_id,
            "vnp_Version" => "2.1.0",
        );
        ksort($Odarray);
        $query = "";
        $i = 0;
        $data = "";
        foreach ($Odarray as $key => $value) {
            if ($i == 1) {
                $data .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $data .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }

            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }
        $vnp_Url .='?';
        $vnp_Url .=$query;
        if (isset($hashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $data, $hashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }
        //$OrdersArray = array('code' => '00'
           // , 'message' => 'success'
           // , 'data' => $vnp_Url);
        //echo json_encode($OrdersArray);
       // die;
		$this->response->redirect($vnp_Url);
		
        }
        // Return url dia chi tra ra ket qua cho client
    public function checkoutReturn() 
        {
        /*DEFINE OPENCART VERSION AND BUILD A FLOAT TYPE VARIABLE FOR MULTIVERSION OPENCART MODULE*/
            $OpenCartVersion = floatval(VERSION);
            switch ($OpenCartVersion) {
                case ($OpenCartVersion  >=  2.3 && $OpenCartVersion < 3.0):
                    $OpenCartVersion    =   2.3;
                    $classPrefix        =   '';
                    break;
                case ($OpenCartVersion  >=  3.0 && $OpenCartVersion < 4.0):
                    $OpenCartVersion    =   3.0;
                    $classPrefix        =   'payment_';
                    break;
            }
            
        $vnp_HashSecret = $this->config->get($classPrefix.'vnpay_payment_secretkey');
        $inputData = array();
        $data1 = $_REQUEST;
        foreach ($data1 as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }

        $vnp_SecureHash = $inputData['vnp_SecureHash'];
        unset($inputData['vnp_SecureHashType']);
        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }
        $vnpTranId = $inputData['vnp_TransactionNo']; //Mã giao dịch tại VNPAY
        $vnp_BankCode = $inputData['vnp_BankCode']; //Ngân hàng thanh toán
        $vnp_TxnRef = $inputData['vnp_TxnRef']; // Mã tham chiếu giữa hai hệ thống
        $vnp_ResponseCode = $inputData['vnp_ResponseCode']; //Mã phản hồi trạng thái thanh toán tại VNPAY
        $secureHash = hash_hmac('sha512' , $hashData, $vnp_HashSecret);
        $this->language->load('extension/payment/vnpay_payment');
        $data['text_response'] = $this->language->get('text_response');
        $data['text_success'] = $this->language->get('text_success');
        $data['text_failure'] = $this->language->get('text_failure');
        $data['text_failure_wait'] = $this->language->get('text_failure_wait');
        $data['text_failure_wait'] = $this->language->get('text_success_wait');
        $data['title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));

	if (!$this->request->server['HTTPS']) {
            $data['base'] = HTTP_SERVER;
	} else {
            $data['base'] = HTTPS_SERVER;
	}
        
        $data['language'] = $this->language->get('code');
	$data['direction'] = $this->language->get('direction');

	$data['heading_title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));

	$data['text_success_wait'] = sprintf($this->language->get('text_success_wait'), $this->url->link('checkout/success'));
	$data['text_failure_wait'] = sprintf($this->language->get('text_failure_wait'), $this->url->link('checkout/cart'));
        
        $this->load->model('checkout/order');
        $data['continue'] = $this->url->link('checkout/success');

	$data['column_left'] = $this->load->controller('common/column_left');
	$data['column_right'] = $this->load->controller('common/column_right');
	$data['content_top'] = $this->load->controller('common/content_top');
	$data['content_bottom'] = $this->load->controller('common/content_bottom');
	$data['footer'] = $this->load->controller('common/footer');
	$data['header'] = $this->load->controller('common/header');
        
        $order_info = $this->model_checkout_order->getOrder($vnp_TxnRef);
        if (isset($order_info['order_id']) && $order_info['order_id'] != NULL) {

            if ($secureHash == $vnp_SecureHash) {
                if ($vnp_ResponseCode == '00') {
                    $data['continue'] = $this->url->link('checkout/success');
                    $this->response->setOutput($this->load->view('extension/payment/vnpay_payment_success', $data));
                } else {
                    $this->response->setOutput($this->load->view('extension/payment/vnpay_payment_failure', $data));
                }
            }
                else {
                        $this->response->setOutput($this->load->view('extension/payment/vnpay_payment_failure', $data));
                     }
        }
         else {
                    $this->response->setOutput($this->load->view('extension/payment/vnpay_payment_failure', $data));
                }
        }
        //IPN dia chi cap nhat trang thai thanh toan
    public function vnpay_ipnurl() {
//                require_once("./log4php/Logger.php");
//                Logger::configure('log4php.xml');
//                $log = Logger::getLogger('VnpayGatewaylogger');
        try {
        /*DEFINE OPENCART VERSION AND BUILD A FLOAT TYPE VARIABLE FOR MULTIVERSION OPENCART MODULE*/
            $OpenCartVersion = floatval(VERSION);
            switch ($OpenCartVersion) {
                case ($OpenCartVersion  >=  2.3 && $OpenCartVersion < 3.0):
                    $OpenCartVersion    =   2.3;
                    $classPrefix        =   '';
                    break;
                case ($OpenCartVersion  >=  3.0 && $OpenCartVersion < 4.0):
                    $OpenCartVersion    =   3.0;
                    $classPrefix        =   'payment_';
                    break;
            }   
        $vnp_HashSecret = $this->config->get($classPrefix.'vnpay_payment_secretkey');
        $inputData = array();
        $data1 = $_REQUEST;
        foreach ($data1 as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }

        $vnp_SecureHash = $inputData['vnp_SecureHash'];
        unset($inputData['vnp_SecureHashType']);
        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }
        $vnpTranId = $inputData['vnp_TransactionNo']; //Mã giao dịch tại VNPAY
        $vnp_BankCode = $inputData['vnp_BankCode']; //Ngân hàng thanh toán
        $order_id = $inputData['vnp_TxnRef']; // Mã tham chiếu giữa hai hệ thống
        $vnp_ResponseCode = $inputData['vnp_ResponseCode']; //Mã phản hồi trạng thái thanh toán tại VNPAY
        $secureHash = hash_hmac('sha512' , $hashData, $vnp_HashSecret);
            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($order_id);
            if (isset($order_info['order_id']) && $order_info['order_id'] != NULL) {
                if ($secureHash == $vnp_SecureHash) {
                if ($order_info['order_status_id'] == '1') {
                        if ($vnp_ResponseCode == '00') {
                            $returnData['RspCode'] = '00';
                            $returnData['Message'] = 'Success confirmed ok';
                            $this->model_checkout_order->addOrderHistory($order_id, $this->config->get($classPrefix.'vnpay_payment_order_status_id'));
//                            $log->info($order_id);
                        } else {
                            $returnData['RspCode'] = '00';
                            $returnData['Message'] = 'Success confirmed fail';
                            $this->model_checkout_order->addOrderHistory($order_id, $this->config->get($classPrefix.'vnpay_payment_order_failed_status_id'));
                        }
                    } else {
                        $returnData['RspCode'] = '02';
                        $returnData['Message'] = 'Order already confirmed';
                    }
                } else {
                    $returnData['RspCode'] = '97';
                    $returnData['Message'] = 'Chu ky khong hop le';
                    $returnData['Signature'] = $secureHash;
                }
            } else {
                $returnData['RspCode'] = '01';
                $returnData['Message'] = 'Order not found';
            }
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($returnData));
        } catch (Exception $e) {
            $returnData = array();
            $returnData['RspCode'] = '99';
            $returnData['Message'] = 'Co loi say ra';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($returnData));
        }
    }
}
