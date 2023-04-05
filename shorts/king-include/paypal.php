<?php

    require_once 'king-base.php';
    require_once QA_INCLUDE_DIR . 'king-db/selects.php';
    require_once QA_INCLUDE_DIR . 'king-app/options.php';
    require_once QA_INCLUDE_DIR . 'king-app/users.php';
// For test payments we want to enable the sandbox mode. If you want to put live
// payments through then this setting needs changing to `false`.
$enableSandbox = qa_opt('paypal_sandbox');
// PayPal settings. Change these to your account details and the relevant URLs
// for your site.
$pageurl = qa_opt('site_url');
$paypalConfig = [
    'email' => qa_opt('paypal_email'),
    'return_url' => qa_path_absolute('membership', array('pay' => 'succes')),
    'cancel_url' => qa_path_absolute('membership', array('pay' => 'error')),
    'notify_url' => $pageurl.'king-include/paypal.php'
];

$paypalUrl = $enableSandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';

// Include Functions
/**
 * Verify transaction is authentic
 *
 * @param array $data Post data from Paypal
 * @return bool True if the transaction is verified by PayPal
 * @throws Exception
 */
function verifyTransaction($data) {
    global $paypalUrl;

    $req = 'cmd=_notify-validate';
    foreach ($data as $key => $value) {
        $value = urlencode(stripslashes($value));
        $value = preg_replace('/(.*[^%^0^D])(%0A)(.*)/i', '${1}%0D%0A${3}', $value); // IPN fix
        $req .= "&$key=$value";
    }

    $ch = curl_init($paypalUrl);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
    curl_setopt($ch, CURLOPT_SSLVERSION, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
    $res = curl_exec($ch);

    if (!$res) {
        $errno = curl_errno($ch);
        $errstr = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL error: [$errno] $errstr");
    }

    $info = curl_getinfo($ch);

    // Check the http response
    $httpCode = $info['http_code'];
    if ($httpCode != 200) {
        throw new Exception("PayPal responded with http code $httpCode");
    }

    curl_close($ch);

    return $res === 'VERIFIED';
}

if (isset( $_POST['mplan'] ) && qa_check_form_security_code('paypal', qa_post_text('code'))) {

    $data = [];
    foreach ($_POST as $key => $value) {
        $data[$key] = stripslashes($value);
    }

    // Set the PayPal account.
    $data['business'] = $paypalConfig['email'];

    // Set the PayPal return addresses.
    $data['return'] = stripslashes($paypalConfig['return_url']);
    $data['cancel_return'] = stripslashes($paypalConfig['cancel_url']);
    $data['notify_url'] = stripslashes($paypalConfig['notify_url']);

    // Set the details about the product being purchased, including the amount
    // and currency so that these aren't overridden by the form data.
    $type = isset( $_POST['mplan'] ) ? $_POST['mplan'] : '';
    $uid = isset( $_POST['userid'] ) ? $_POST['userid'] : '';
    $usd = qa_opt('plan_usd_'.$type);
    $amount = $usd . '.00';

    $data['item_name'] = qa_opt('plan_'.$type.'_title');
    $data['amount'] = $amount;
    $data['currency_code'] = qa_opt('currency');
    $data['item_number'] = $type;
    $data['custom'] = $uid;
    // Add any custom fields for the query string.
    //$data['custom'] = USERID;

    // Build the query string from the data.
    $queryString = http_build_query($data);

    header('location:' . $paypalUrl . '?' . $queryString);
    exit();


} else {
    $data = [
        'item_name' => $_POST['item_name'],
        'item_number' => $_POST['item_number'],
        'payment_status' => $_POST['payment_status'],
        'payment_amount' => $_POST['mc_gross'],
        'payment_currency' => $_POST['mc_currency'],
        'txn_id' => $_POST['txn_id'],
        'receiver_email' => $_POST['receiver_email'],
        'payer_email' => $_POST['payer_email'],
        'custom' => $_POST['custom'],
    ];
    if (verifyTransaction($_POST)) {
        king_insert_membership($data['item_number'], $data['payment_amount'], $data['custom'], $data['txn_id'] );
    }
}
?>