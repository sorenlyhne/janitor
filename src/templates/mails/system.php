<?php

$message .= "\n\n".$_SERVER["HTTP_HOST"]."\n".$_SERVER["REQUEST_URI"]."\n";
$message .= getenv("HTTP_X_FORWARDED_FOR") ? getenv("HTTP_X_FORWARDED_FOR") : getenv("REMOTE_ADDR");

?>