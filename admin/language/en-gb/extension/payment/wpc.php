<?php
/**
 * PayCEC OpenCart Plugin
 *
 * @package PayCEC Payment Gateway
 * @author PayCEC Technical Team
 * @version 1.0
 */

// Versioning
$_['wpc_ptype'] = "OpenCart";
$_['wpc_pversion'] = "1.0";

// Heading
$_['heading_title']					= 'PayCEC Payment Gateway';

// Text
$_['text_extension']                = 'Extensions';
$_['text_payment']					= 'Payment';
$_['text_success']					= 'Success: You have modified PayCEC Payment Gateway account details!';
$_['text_edit']                     = 'Edit PayCEC';
$_['text_wpc']	     			    = '<a onclick="window.open(\'https://www.paycec.com//\');" style="text-decoration:none;"><img src="view/image/payment/paycec.svg" alt="PayCEC Payment Gateway" title="PayCEC Payment Gateway" style="border: 0px solid #EEEEEE;" height=30 width=105/></a>';

// Entry
$_['entry_merchant_name']			= 'PayCEC Merchant Username';
$_['entry_merchant_key']			= 'PayCEC Merchant Key';
$_['entry_status']					= 'Status';
$_['entry_test']					= 'Sandbox Mode';

// Help
$_['help_merchant_key']				= 'Please refer to your PayCEC Merchant Profile for this key.';
$_['help_test']						= 'Use the live or testing (sandbox) gateway server to process transactions?';

// Error
$_['error_permission']				= 'Warning: You do not have permission to modify PayCEC Payment Gateway!';
$_['error_merchant_name']			= '<b>PayCEC Merchant Username</b> Required!';
$_['error_merchant_key']			= '<b>PayCEC Verify Key</b> Required!';
$_['error_settings']       			= 'PayCEC Merchant Id and verify key mismatch, contact support@wpc.com to assist.';
$_['error_status']          		= 'Unable to connect PayCEC API.';
