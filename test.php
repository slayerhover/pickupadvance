<?php
header('content-type:text/html;charset=utf-8');
date_default_timezone_set('PRC');
require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/includes/db.php');
use QL\QueryList;
use QL\Ext\PhantomJs;
$ql = QueryList::getInstance();
$ql->use(PhantomJs::class,'/usr/bin/phantomjs','browser');


$data = $ql->browser(function (\JonnyW\PhantomJs\Http\RequestInterface $r){
	$r->setMethod('GET');
	$r->setUrl('https://m.toutiao.com');
	$r->setTimeout(20000); // 10 seconds
	$r->setDelay(5); // 3 seconds
	return $r;
})->find('.single-mode-rbox-inner')->texts();
print_r($data->all());
exit;

$html = $ql->browser('https://m.toutiao.com')->getHtml();
print_r($html);
exit;

$data = $ql->browser('https://m.toutiao.com')->find('div.feed-infinite-wrapper')->html();
print_r($data);


$data = $ql->browser('https://m.toutiao.com',true,[
	// 使用http代理
	'--proxy' => '114.115.140.25:3128',
	'--proxy-type' => 'http'
]);
print_r($data);
