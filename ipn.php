<?php

// PAYPAL IPN INTEGRATION
// https://developer.paypal.com/webapps/developer/applications/ipn_simulator

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

            if ($_POST['payment_date']) {
                $info = array(
                    'status' => true,
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

        } elseif (strcmp($res, "INVALID") == 0) {
            $status = false; //"Live-INVALID IPN";
        }
    }
}
fclose($fp);
