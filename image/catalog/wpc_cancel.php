<?php
/* Redirect to the payment module page in the current directory that was requested */
$host  = $_SERVER['HTTP_HOST'];
$uri   = str_replace("/image/catalog","",rtrim(dirname($_SERVER['PHP_SELF']), '/\\'));
$path  = 'index.php?route=checkout/checkout';
header("Location: http://$host$uri/$path");
exit;
?>