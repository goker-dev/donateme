<?php

// PAYPAL IPN INTEGRATION
error_reporting(E_ALL ^ E_NOTICE);
// Read the post from PayPal and add 'cmd'
$req = 'cmd=_notify-validate';
if (function_exists('get_magic_quotes_gpc')) {
    $get_magic_quotes_exists = true;
}
foreach ($_POST as $key => $value) {
    // Handle escape characters, which depends on setting of magic quotes
    if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
        $value = urlencode(stripslashes($value));
    } else {
        $value = urlencode($value);
    }
    $req .= "&$key=$value";
}
// Post back to PayPal to validate
$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
$fp = fsockopen('ssl://www.paypal.com', 443, $errno, $errstr, 30);
// Process validation from PayPal
// TODO: This sample does not test the HTTP response code. All
// HTTP response codes must be handles or you should use an HTTP
// library, such as cUrl
if (!$fp) { // HTTP ERROR
} else {
    // NO HTTP ERROR
    fputs($fp, $header . $req);
    while (!feof($fp)) {
        $res = fgets($fp, 1024);
        if (strcmp($res, "VERIFIED") == 0) {
            // TODO:
            // Check the payment_status is Completed
            // Check that txn_id has not been previously processed
            // Check that receiver_email is your Primary PayPal email
            // Check that payment_amount/payment_currency are correct
            // Process payment
            // If 'VERIFIED', send an email of IPN variables and values to the
            // specified email address
            foreach ($_POST as $key => $value) {
                $emailtext .= $key . " = " . $value . "\n\n";
            }
            $status = true; //"Live-VERIFIED IPN";
            //mail($email, "Live-VERIFIED IPN", $emailtext . "\n\n" . $req);
        } elseif (strcmp($res, "INVALID") == 0) {
            // If 'INVALID', send an email. TODO: Log for manual investigation.
            foreach ($_POST as $key => $value) {
                $emailtext .= $key . " = " . $value . "\n\n";
            }
            $status = false; //"Live-INVALID IPN";
            //mail($email, "Live-INVALID IPN", $emailtext . "\n\n" . $req);
        }
    }
}
fclose($fp);
if ($_POST['payment_date']) {
    $info = array(
        'status' => $status,
        'date' => $_POST['payment_date'],
        'message' => $_POST['item_name'] ? $_POST['item_name'] : 'Guest',
        'custom' => $_POST['custom'],
        'gross' => $_POST['mc_gross'],
        'fee' => $_POST['mc_fee']
    );


    $content = @file_get_contents('donations.json');
    if ($content === FALSE)
        $data = array();
    else
        $data = json_decode($content, 1);

    array_push($data, $info);
    file_put_contents('donations.json', json_encode($data));
}
