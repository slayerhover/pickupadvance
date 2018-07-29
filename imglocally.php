<?php
header('content-type:text/html;charset=utf-8');
error_reporting(E_ERROR | E_PARSE);
date_default_timezone_set('PRC');
define('HOME_PATH', dirname(__FILE__));

require(HOME_PATH . '/vendor/autoload.php');
require(HOME_PATH . '/includes/autoload.php');
require(HOME_PATH . '/includes/errorReport.php');
require(HOME_PATH . '/includes/db.php');
use Illuminate\Database\Capsule\Manager as DB;

$start	= 0;
$end	= 5000;
if($argc>1){
switch($argc){
	case 2:
		$start	=	intval($argv[1]);
		break;
	default:
		$start	=	intval($argv[1]);
		$end	=	intval($argv[2]);
		break;
}}

/**
  * 图片本地化
  **/
class	ImgLocally{
	public static function writePhoto($file, $imgurl){
		if(!file_exists($file)){
			$wgetshell='wget -O '.$file.' "'.$imgurl.'" ';
			shell_exec($wgetshell);
			self::cut($file);
			return $file;
		}
		return false;
	}
	public static function writePhotoInWeb($file, $imgurl){
		if(!file_exists($file)){				
			file_put_contents($file, file_get_contents($imgurl));
			self::cut($file);
			return $file;
		}
		return false;
	}
	private static function cut($file){
		$im = imagecreatefromjpeg($file);  
		$x	= imagesx($im);
		$y	= imagesy($im)-70;		//剪切掉底部	
		$dim= imagecreatetruecolor($x, $y); // 创建目标图gd2
		imagecopyresized ($dim,$im,0,0,0,0,$x,$y,$x,$y);
		imagejpeg($dim, $file);
		imagedestroy($im); 
		imagedestroy($dim);
	}
}

try{
	$rows= DB::table('aijiacms_photo_item_12')->where('thumb','<>','')->select('itemid', 'thumb')->get();
	if(!empty($rows)&&is_array($rows)){
	foreach($rows as $k=>$v){
		$cDir 	=	"./images";
		if (! is_dir ( $cDir )) {  mkdir($cDir, 0777);	}
        #http://imga.mizhai.com/newhouse/2015-12/25/567ccf5fd8b75-pc.jpg
		$name	=	pathinfo($v['thumb'])['basename'];
		$path	=	"{$cDir}/{$name}";
		$imgurl	=	'/images/'.$name;
		if($newimg	=	ImgLocally::writePhoto($path, $v['thumb'])){
			DB::table('aijiacms_photo_item_12')->where('itemid','=',$v['itemid'])->update(['thumb'=>$imgurl]);
			echo $newimg, "\n";
		}else{
			echo "jump {$v['thumb']}\n";
		}
	}}
}catch(Exception $e){
    echo "Failed: " . $e->getMessage();
}


$time = round(microtime(true) - (float)$starton, 5);
echo '浪费计算时间共：',$time,'    浪费内存共计：', (memory_get_usage(true) / 1024), "kb\n\nDone.\n";

