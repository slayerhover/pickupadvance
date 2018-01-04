<?php
header('content-type:text/html;charset=utf-8');
date_default_timezone_set('PRC');
require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/includes/db.php');
use QL\QueryList;
use Illuminate\Database\Capsule\Manager as DB;

$baseuri = 'http://www.selleck.cn';
$url = QueryList::get($baseuri . '/alltargets.jsp')->find('.alphabetic_index_item')->map(function($item){
    return $item->find('a')->map(function($links){ return $links->href; })->all();
})->all();
#print_r($url);

$cache = new Redis();
$cache->connect('127.0.0.1', 6379);
$cache->select(3);
$allgoodsurl = [];
foreach($url as $k1=>$v1){
	foreach($v1 as $k2=>$v2){
		$targeturl = $baseuri . $v2;
		$goodsurl  = QueryList::get($targeturl)->find('h4>a')->map(function($goods){
			return ['url'=>$goods->href, 'name'=>$goods->text()];
		})->all();		
		foreach($goodsurl as $k3=>$v3){
			$allgoodsurl[] = $baseuri . $v3['url'];
			$cache->lpush('pickup_queue', $baseuri . $v3['url']);
			DB::table('pp_goodslist')->insert(['name'=>$v3['name'], 'url'=>$baseuri . $v3['url']]);
		}
		QueryList::destruct();
		#usleep(rand(500,3000));
}}
#print_r($allgoodsurl);

echo "Queue List is OK.\n";
