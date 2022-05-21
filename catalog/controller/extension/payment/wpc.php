<?php
/**
 * PayCEC OpenCart Plugin
 *
 * @package PayCEC Payment Gateway
 * @author PayCEC Technical Team
 * @version 1.0
 */

function generateSignatureWpc($endpoint, $params, $secretKey)
{
    ksort($params);
    $sig = $endpoint.'?'.http_build_query($params);
    return hash_hmac('sha512', $sig, $secretKey, false);
}

function callWpc($endpoint, $params, &$errorNo = 0, &$errorMessage = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

    $resultString = curl_exec ($ch);

    if (curl_errno($ch)) {
        $errorNo = curl_errno($ch);
        $errorMessage = curl_error($ch);
        curl_close($ch);

        return null;
    }

    curl_close($ch);

    return json_decode($resultString);
}

/*
 * ORDER STATUS ID
 *          1   =>  Pending
 *          2   =>  Processing
 *          3   =>  Shipped
 *          5   =>  Complete
 *          7   =>  Canceled
 *          8   =>  Denied
 *          9   =>  Canceled Revers
 *          10  =>  Fail
 *          11  =>  Refunded
 *          12  =>  Reversed
 *          13  =>  Chargeback
 *          14  =>  Expired
 *          15  =>  Processed
 *          16  =>  Voided
*/

class ControllerExtensionPaymentWPC extends Controller {
    public function index() {
        $data['button_confirm'] = $this->language->get('button_confirm');
        $this->load->model('checkout/order');
        $this->load->model('extension/total/coupon');
        $this->load->model('extension/total/voucher');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $wpcMerchantUserName = $this->config->get('payment_wpc_merchant_name');
        $wpcMerchantSecretKey = $this->config->get('payment_wpc_merchant_key');

        if ($this->config->get('payment_wpc_test') == 1)
            $mainUrl = 'https://securetest.paycec.com/redirect-service';
        else
            $mainUrl = 'https://secure.paycec.com/redirect-service';

        $tokenUrl = $mainUrl . '/request-token';
        $webscreenUrl = $mainUrl . '/webscreen?token=';

        $comment = 'No Comments.';
        if (isset($this->session->data['comment']) && $this->session->data['comment'] != '')
            $comment = $this->session->data['comment'];

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
            $returnUrl = HTTPS_SERVER . 'image/catalog/wpc_callback.php';
        } else {
            $returnUrl = HTTP_SERVER . 'image/catalog/wpc_callback.php';
        }

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
            $cancelUrl = HTTPS_SERVER . 'image/catalog/wpc_cancel.php';
        } else {
            $cancelUrl = HTTP_SERVER . 'image/catalog/wpc_cancel.php';
        }

        $this->load->model('localisation/currency');
        $currency = $this->model_localisation_currency->getCurrencyByCode($this->session->data['currency']);
        $finalTotal = round($order_info['total'] * $currency['value'], 2);

        $params =  array(
            'merchantName' => $wpcMerchantUserName,
            'merchantSecretKey' => $wpcMerchantSecretKey,
            'merchantToken' => time(),                                  //You can change it by your unique token builder
            'merchantReferenceCode' => $comment,  //Replace it by your description             //Replace it by your buyer email
            'amount' => $finalTotal,                              //Replace it by your total amount
            'currencyCode' => $this->session->data['currency'],                                    //Replace it by your currency code
            'returnUrl' => $returnUrl,   //Replace it by your return call back url
            'cancelUrl' => $cancelUrl,   //Replace it by your cancel call back url
        );

        $params['sig'] = generateSignatureWpc($tokenUrl, $params, $wpcMerchantSecretKey);

        $errorCode = 0;
        $errorMessage = '';
        $reply = callWpc($tokenUrl, $params, $errorCode, $errorMessage);

        if ($reply != null && $reply->isSuccessful == 'true') {
            $data['action'] = $webscreenUrl . $reply->token;
			if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/wpc')) {
				return $this->load->view($this->config->get('config_template') . '/template/extension/payment/wpc', $data);
			} else {
				return $this->load->view('default/template/extension/payment/wpc', $data);
			}
        }
        else {
            if ($reply) {
                $mes = '</br><strong>We\'ve got some errors retrieving from PayCEC service</strong>.
                    </br> - <strong>Error Code:</strong> ' . $reply->errorCode;
                if (isset($reply->errorMessage))
                     $mes .='</br>- <strong>Error Message 1:</strong> ' . $reply->errorMessage;
                if (isset($reply->message)) {
                    $mes .= '</br>- <strong>Error Message 2:</strong> ' . $reply->message;
                }
                if (isset($reply->errorField)) {
                    $mes .= '</br>- <strong>Error Field:</strong> ' . $reply->errorField;
                }
                echo $mes;
            } else if (!empty($errorMessage)) {
                $mes = '</br><strong>We\'ve got some errors when connecting to Secure PayCEC service.</strong>
                    </br> - <strong>Error Code:</strong> ' . $errorCode . '
                    </br> - <strong>Error Message:</strong> ' . $errorMessage;
                echo $mes;
            } else {
                $mes = '</br><strong>Cannot connect to Secure PayCEC service.</strong>';
                echo $mes;
            }
            $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 8, $mes);
        }
    }

    public function callback() {
        $this->load->model('checkout/order');
        $order_id = $this->session->data['order_id'];

        $wpcMerchantUserName = $this->config->get('payment_wpc_merchant_name');
        $wpcMerchantSecretKey = $this->config->get('payment_wpc_merchant_key');
        if ($this->config->get('payment_wpc_test') == 1)
            $mainUrl = 'https://securetest.paycec.com/redirect-service/purchase-details';
        else
            $mainUrl = 'https://secure.paycec.com/redirect-service/purchase-details';

        $token = $this->request->get['token'];

        $comment = '';

        if ($token != null) {
            $params = array(
                'merchantName' => $wpcMerchantUserName,
                'merchantSecretKey' => $wpcMerchantSecretKey,
                'token' => $token,
            );
            $params['sig'] = generateSignatureWpc($mainUrl, $params, $wpcMerchantSecretKey);

            $errorCode = 0;
            $errorMessage = '';
            $reply = callWpc($mainUrl, $params, $errorCode, $errorMessage);

            $comment = $reply->referenceCode;
            $this->model_checkout_order->addOrderHistory($order_id, 2, $comment);

            if ($reply != null && $reply->isSuccessful == 'true') {
                $comment = '<strong>Successfully charged!</strong>
                            </br><strong>Timestamp:</strong> ' . $reply->timestamp .
                            '</br><strong>Transaction ID:</strong> '. $reply->transactionId .
                            '</br><strong>Token:</strong> ' . $reply->token .
                            '</br><strong>Request ID:</strong> ' . $reply->requestId;
                $this->model_checkout_order->addOrderHistory($order_id, 5, $comment);
                echo '<html><head>';
                echo '<meta http-equiv="Refresh" content="0; url=' . $this->url->link('checkout/success') . '">' . "\n";
                echo '</head><body>';
                echo '</body></html>';
            } else {
                if ($reply) {
                    $comment = '<strong>We\'ve got some errors retrieving from PayCEC service.</strong>';
                    $comment .= '</br> - <strong>Error Code:</strong> ' . $reply->errorCode . '</p>';
                    if (isset($reply->errorMessage))
                        $comment .= '</br> - <strong>Error Message 1:</strong> ' . $reply->errorMessage;
                    if (isset($reply->message)) {
                        $comment .= '</br> - <strong>Error Message 2:</strong> ' . $reply->message;
                    }
                    if (isset($reply->errorField)) {
                        $comment .= '</br> - <strong>Error Field:</strong> ' . $reply->errorField;
                    }
                } else if (!empty($errorMessage)) {
                    $comment .= '</br><strong>We\'ve got some errors when connecting to Secure PayCEC service.</strong>';
                    $comment .= '</br> - <strong>Error Code:</strong> ' . $errorCode;
                    $comment .= '</br> - <strong>Error Message:</strong> ' . $errorMessage;
                }

                if ($comment != '')
                    $this->model_checkout_order->addOrderHistory($order_id, 10, $comment);

                echo '<html><head>';
                echo '<meta http-equiv="Refresh" content="0; url=' . $this->url->link('checkout/failure') . '">' . "\n";
                echo '</head><body>';
                echo '</body></html>';
            }
        }
        else {
            echo '<html><head>';
            echo '<meta http-equiv="Refresh" content="0; url=' . $this->url->link('checkout/failure') . '">' . "\n";
            echo '</head><body>';
            echo '</body></html>';
            $comment = '<strong>Missing Token</strong>';
            $this->model_checkout_order->addOrderHistory($order_id, 10, $comment);
        }
    }
}