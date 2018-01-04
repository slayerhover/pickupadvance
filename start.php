<?php
header('content-type:text/html;charset=utf-8');
date_default_timezone_set('PRC');
require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/includes/db.php');
require_once(__DIR__ . '/includes/pickup.php');
require_once(__DIR__ . '/includes/server.php');

$run	=new Server;
