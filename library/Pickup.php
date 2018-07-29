<?php
namespace library;

use QL\QueryList;
use Overtrue\Pinyin\Pinyin as PY;
use Illuminate\Database\Capsule\Manager as DB;

final class Pickup{
    private static $ql;     
	private static $rule;
	private static $option;
	    
    public static function getInstance(){
        if(self::$ql==null) self::$ql = QueryList::getInstance();
		if(self::$rule==null) self::$rule = self::setRule();		
		if(self::$option==null) self::$option = self::setOption();
		
        return new self();
    }
	
	public static function setRule(){
		return array(
			'title'	=>	['h1', 'text'],
			'avatar'=>	['div#content .topic-content .user-face img.pil', 'src'],
			'home'	=>	['div#content .topic-content .user-face a', 'href'],
			'author'=>	['div#content .topic-doc h3 a', 'text'],
			'publish_date'=>['div#content .topic-doc h3 .color-green', 'text'],
			'content'=>	['div#content #link-report>.topic-content', 'html'],
		);
	}

	public static function setOption(){
		return array(
			'max'		=>	5,
			'timeout'	=>	20, 
			'connect_timeout'=>	10, 
			'delay'		=>	1000,
			'strict'	=>	false, 
			'referer'	=>	true, 
			'protocols'	=>	['http'], 
			'track_redirects' => false,
			'proxy'		=>	self::randProxy(),
			'headers'	=>	[				
				'Connection'	=> 'keep-alive',
				'Cache-Control' => 'max-age=0',
				'Upgrade-Insecure-Requests' => '1',
				'Referer'		=> 'https://www.baidu.com/',
				'User-Agent'	=> self::randUserAgent('pc'),
				'Accept'		=> 'application/json, text/plain, */*',
				'Accept-Encoding'=> 'gzip, deflate, sdch, br',
				'Cookie'	=> 'bid=ht8lQejUFHM; ps=y; _pk_ses.100001.8cb4=*; as="https://www.douban.com/group/topic/18164337/"; dbcl2="153060491:KZjompj0Zmw"; ck=UXE_; push_noty_num=0; push_doumail_num=0; _pk_id.100001.8cb4=4187d8ecac730746.1521077909.2.1521082919.1521078313.',
			],
		);
	}
	
	public static function pick($url){		
		try{
				self::$option['proxy'] =self::randProxy();
				#$data = self::$ql->get($url, '', self::$option)->getHtml();
				preg_match('/\d+/', $url, $loupanId);
				$loupanId = $loupanId[0];
				Server::Log("[loupan]:".$loupanId);				
				
                $itemid= self::pickHome("http://www.mizhai.com/info/".$loupanId.".html");
				usleep(rand(1000,2000));
                self::pickThumbAndMaps($url, $itemid);                
				usleep(rand(1000,2000));
                self::pickImages("http://www.mizhai.com/photos/".$loupanId.".html", $itemid);
				usleep(rand(1000,2000));
                self::pickHuxing("http://www.mizhai.com/huxing/".$loupanId.".html", $itemid);
				usleep(rand(2000,4000));
		} catch (\Exception $e ) {
				Server::Log("[code]:" . $e->getCode()." [message]:" . $e->getMessage()."\n");
				Server::$cache->lPush("errorlist", $url);
				if(preg_match('#cURL#', $e->getMessage())){
					Server::Log("[proxy]:" . key(self::$option['proxy']).'://'. explode('://', current(self::$option['proxy']))[1]);
					self::checkProxy(self::$option['proxy']);
				}
		}
	}

    /**
     * @return mixed
     */
    public static function pickHome($url)
    {
        $rule = array(            
			'catid'		=>	['ul.base-info li:eq(4)', 'text', '-span'],
			'areaid'	=>	['ul.base-info li:eq(1)', 'text', '-span'],
			'title'	    =>	['h3', 'text', '-span'],
			'introduce'	=>	['.mt10.jumbotron:eq(3)', 'text'],
			'price'     =>  ['ul.base-info li:eq(0) em', 'text'],			
            'telephone' =>  ['li.phone', 'text'],
            'address'   =>  ['ul.base-info li:eq(1)', 'title'],
            'tedian'	=>  ['ul.base-info li:eq(7)', 'text', '.label'],
			'selltime'	=>	['ul.base-info li:eq(2)', 'text', '-span -a'],
			'completion'=>	['ul.base-info li:eq(3)', 'text', '-span'],
			'lp_type'	=>	['ul.base-info li:eq(4)', 'text', '-span'],
			'lp_company'=>	['ul.base-info li:eq(15)', 'text', '-span'],
			'lp_costs'	=>	['ul.base-info li:eq(16)', 'text', '-span'],
			'lp_totalarea'=>['ul.base-info li:eq(10)', 'text', '-span'],
			'lp_area'	=>	['ul.base-info li:eq(8)', 'text', '-span'],
			'lp_number'	=>	['ul.base-info li:eq(13)', 'text', '-span'],
			'lp_car'	=>	['ul.base-info li:eq(12)', 'text', '-span'],
			'lp_volume'	=>	['ul.base-info li:eq(9)', 'text', '-span'],
			'lp_green'	=>	['ul.base-info li:eq(11)', 'text', '-span'],
			'lp_bus'	=>	['.mt10.jumbotron:eq(1)', 'text'],
			'lp_edu'	=>	['.mt10.jumbotron:eq(2)', 'text'],
			'buildtype'	=>	['ul.base-info li:eq(6)', 'text', '.label'],
			'kfs'		=>	['ul.base-info li:eq(14)', 'text', '-span'],			
        );
        $data = self::$ql->get($url, '', self::$option)->rules($rule)->query()->getData()->first();
		$pinyin = new PY;
		$rows = array(
			'catid'		=>	self::get_catid($data['catid']),
			'areaid'	=>	self::get_areaid(preg_split("/[\s]/", $data['areaid'])[0]),
			'typeid'	=>	2,
			'level'		=>	1,
			'title'	    =>	preg_split("/[\s]/", $data['title'])[0],
			'introduce'	=>	$data['introduce']??'',
			'price'     =>  $data['price']??0.00,
			'keyword'	=>	preg_split("/[\s]/", $data['title'])[0],
			'hits'		=>	rand(200, 10000),
			'username'	=>	'admin',
            'telephone' =>  $data['telephone']??'',
            'address'   =>  $data['address']??'',
			'editor'	=>	'admin',
			'editdate'	=>	date('Y-m-d'),
			'addtime'	=>	time(),
			'edittime'	=>	time(),
			'adddate'	=>	date('Y-m-d'),
			'status'	=>	3,
			'letter'	=>	$pinyin->abbr(preg_split("/[\s]/", $data['title'])[0]),
            'tedian'	=>  $data['tedian'],
			'selltime'	=>	$data['selltime']??'',
			'completion'=>	$data['completion']??'',
			'lp_type'	=>	self::get_catid($data['catid']),
			'lp_year'	=>	0,
			'lp_company'=>	$data['lp_company'],
			'lp_costs'	=>	$data['lp_costs']??0.00,
			'lp_totalarea'=>$data['lp_totalarea']??0,
			'lp_area'	=>	$data['lp_area'],
			'lp_number'	=>	$data['lp_number']??0,
			'lp_car'	=>	$data['lp_car'],
			'lp_volume'	=>	$data['lp_volume'],
			'lp_green'	=>	$data['lp_green'],
			'lp_bus'	=>	$data['lp_bus'],
			'lp_edu'	=>	$data['lp_edu'],
			'buildtype'	=>	self::get_buildtype($data['lp_buildtype']),
			'kfs'		=>	$data['kfs']??'',
			'pinyin'	=>	$pinyin->permalink(preg_split("/[\s]/", $data['title'])[0], ''),
		);		
        $itemid = DB::table('aijiacms_newhouse_6')->insertGetId($rows);
		if(!empty($data['price'])){
			$priceRows = array(
				'pid'   =>  $itemid,
				'price' =>  $data['price'],
				'username'=>'admin',
				'addtime'=> time(),
				'status'=>  3,
				'editor'=>  'admin',
				'edittime'=>time(),
			);
			DB::table('aijiacms_newhouse_price')->insert($priceRows);
		}
		Server::Log("[home]:" . $itemid . " ok.");
        return $itemid;
    }

    /**
     * @return mixed
     */
    public static function pickThumbAndMaps($url, $itemid)
    {
        $rule = array(
            'thumb'		=>	['li.slide img', 'src'],
            'maps'	    =>	['.section5.mt10', 'html', '-span -a -div -ul -s -em'],
            'dianping'  =>  ['li.zhiding .info p', 'html']
        );
        $data = self::$ql->get($url, '', self::$option)->rules($rule)->query()->getData()->first();
        preg_match('/\d{2}.\d{6}/', $data['maps'], $lat);
        preg_match('/\d{3}.\d{6}/', $data['maps'], $lng);

        $rows = array(
            'thumb'	=>	$data['thumb']??'',
            'map'	=>	$lng[0].','.$lat[0],
        );        
        DB::table('aijiacms_newhouse_6')->where('itemid', '=', $itemid)->update($rows);
        $dataRows   =   array(
            'itemid'    =>  $itemid,
            'content'   =>  $data['dianping']??'',
        );
        DB::table('aijiacms_newhouse_data_6')->insert($dataRows);
		Server::Log("[maps]:" . $itemid . " ok.");
		return TRUE;
    }
	
	public static function pickImages($url, $itemid)
    {   
		$housename= DB::table('aijiacms_newhouse_6')->where('itemid','=',$itemid)->pluck('title');
		self::pickOneImg($url, $itemid, $housename);
		self::pickTwoImg($url, $itemid, $housename);
    }
	
	private static function pickOneImg($url, $itemid, $housename)
    {
		$rule = array(
            'thumb'		=>	['div.section1 .mt15:eq(0) .imglist li', 'html']
        );			
		$xiaoguotu	=self::$ql->get($url, '', self::$option)->rules($rule)->query()->getData()->all();	
		if(!empty($xiaoguotu)){
			$imgRules = array(
				'img'	=>	['img', 'zoomfile'],
				'title'	=>	['b', 'text'],
			);		
			$imgResult= self::$ql->html($xiaoguotu[0]['thumb'])->rules($imgRules)->query()->getData()->first();				
			$rows = array(
				'catid'		=>	23,
				'title'		=>	$housename.'效果图',
				'introduce'	=>	$housename.'效果图',
				'items'		=>	sizeof($xiaoguotu),
				'hits'		=>	rand(80, 500),
				'thumb'		=>	$imgResult['img'],
				'username'	=>	'admin',
				'addtime'	=>	time(),
				'editor'	=>	'admin',
				'edittime'	=>	time(),
				'status'	=>	3,
				'open'		=>	3,
				'houseid'	=>	$itemid,
				'housename'	=>	$housename,
			);
			$pid = DB::table('aijiacms_photo_12')->insertGetId($rows);				
			DB::table('aijiacms_photo_12')->where('itemid', '=', $pid)->update(['linkurl'=>"p{$pid}-h{$itemid}.html"]);
			$imgRows = array(
				'itemid'	=>	$pid,
				'content'	=>	$housename.'效果图',
			);
			DB::table('aijiacms_photo_data_12')->insert($imgRows);
			foreach($xiaoguotu as $k=>$v){
				$imgResult= self::$ql->html($v['thumb'])->rules($imgRules)->query()->getData()->first();
				$imgRows = array(
					'item'		=>	$pid,
					'introduce'	=>	$imgResult['title'],
					'thumb'		=>	$imgResult['img'],
				);
				DB::table('aijiacms_photo_item_12')->insert($imgRows);
			}
		}
		Server::Log("[xiaoguotu]:" . $itemid . " ok.");
		return TRUE;
    }
	private static function pickTwoImg($url, $itemid, $housename)
    {
		$rule = array(
            'thumb'		=>	['div.section1 .mt15:eq(2) .imglist li', 'html']
        );			
		$xiaoguotu	=self::$ql->get($url, '', self::$option)->rules($rule)->query()->getData()->all();		
		if(!empty($xiaoguotu)){
			$imgRules = array(
				'img'	=>	['img', 'zoomfile'],
				'title'	=>	['b', 'text'],
			);
			$housename= DB::table('aijiacms_newhouse_6')->where('itemid','=',$itemid)->pluck('title');
			$imgResult= self::$ql->html($xiaoguotu[0]['thumb'])->rules($imgRules)->query()->getData()->first();		
			$rows = array(
				'catid'		=>	26,
				'title'		=>	$housename.'实景图',
				'introduce'	=>	$housename.'实景图',
				'items'		=>	sizeof($xiaoguotu),
				'hits'		=>	rand(80, 500),
				'thumb'		=>	$imgResult['img'],
				'username'	=>	'admin',
				'addtime'	=>	time(),
				'editor'	=>	'admin',
				'edittime'	=>	time(),
				'status'	=>	3,
				'open'		=>	3,
				'houseid'	=>	$itemid,
				'housename'	=>	$housename,
			);
			$pid = DB::table('aijiacms_photo_12')->insertGetId($rows);				
			DB::table('aijiacms_photo_12')->where('itemid', '=', $pid)->update(['linkurl'=>"p{$pid}-h{$itemid}.html"]);
			$imgRows = array(
				'itemid'	=>	$pid,
				'content'	=>	$housename.'实景图',
			);
			DB::table('aijiacms_photo_data_12')->insert($imgRows);
			foreach($xiaoguotu as $k=>$v){
				$imgResult= self::$ql->html($v['thumb'])->rules($imgRules)->query()->getData()->first();
				$imgRows = array(
					'item'		=>	$pid,
					'introduce'	=>	$imgResult['title'],
					'thumb'		=>	$imgResult['img'],
				);
				DB::table('aijiacms_photo_item_12')->insert($imgRows);
			}
		}
		Server::Log("[shijing]:" . $itemid . " ok.");
		return TRUE;
    }
	public static function pickHuxing($url, $itemid)
    {        		
		$rule = array(
            'thumb'		=>	['div#huxing .imglist li img', 'zoomfile'],
			'title'		=>	['div#huxing .imglist li img', 'title'],
			'mianji'	=>	['div#huxing .imglist li b', 'text'],
        );			
		$xiaoguotu	=self::$ql->get($url, '', self::$option)->rules($rule)->query()->getData()->all();				
		if(!empty($xiaoguotu)){
			$housename= DB::table('aijiacms_newhouse_6')->where('itemid','=',$itemid)->pluck('title');		
			$imgResult= $xiaoguotu[0];		
			$rows = array(
				'catid'		=>	24,
				'title'		=>	$housename.'户型图',
				'introduce'	=>	$housename.'户型图',
				'items'		=>	sizeof($xiaoguotu),
				'hits'		=>	rand(80, 500),
				'thumb'		=>	$imgResult['thumb'],
				'username'	=>	'admin',
				'addtime'	=>	time(),
				'editor'	=>	'admin',
				'edittime'	=>	time(),
				'status'	=>	3,
				'open'		=>	3,
				'houseid'	=>	$itemid,
				'housename'	=>	$housename,
			);
			$pid = DB::table('aijiacms_photo_12')->insertGetId($rows);				
			DB::table('aijiacms_photo_12')->where('itemid', '=', $pid)->update(['linkurl'=>"p{$pid}-h{$itemid}.html"]);
			$imgRows = array(
				'itemid'	=>	$pid,
				'content'	=>	$housename.'户型图',
			);
			DB::table('aijiacms_photo_data_12')->insert($imgRows);
			foreach($xiaoguotu as $k=>$v){			
				$imgRows = array(
					'item'		=>	$pid,
					'introduce'	=>	$v['title'],
					'thumb'		=>	$v['thumb'],
					'mianji'	=>	$v['mianji'],
				);
				DB::table('aijiacms_photo_item_12')->insert($imgRows);
			}
		}
		Server::Log("[huxing]:" . $itemid . " ok.");
		return TRUE;
    }
	
	private static function get_catid($cat){
		switch($cat){
			case '住宅':return 1;
			case '公寓':return 2;
			case '商铺':return 3;
			case '写字楼':return 4;
			case '别墅':return 5;
			case '商住楼':return 6;
			default:return 7;
		}
	}
	private static function get_areaid($area){
		switch(TRUE){
			case strstr('金水区',$area):return 2;
			case strstr('二七区',$area):return 3;
			case strstr('中原区',$area):return 4;
			case strstr('管城区',$area):return 5;
			case strstr('惠济区',$area):return 6;
			case strstr('郑东新区',$area):return 7;
			case strstr('上街区',$area):return 8;
			case strstr('新郑',$area):return 9;
			case strstr('新密',$area):return 10;
			case strstr('巩义',$area):return 11;
			case strstr('中牟',$area):return 12;
			case strstr('荥阳',$area):return 13;
			case strstr('登封',$area):return 14;
			case strstr('高新区',$area):return 15;
			case strstr('经开区',$area):return 16;
			case strstr('航空港区',$area):return 17;
			case strstr('平原新区',$area):return 18;
			case strstr('开封',$area):return 19;
			case strstr('焦作',$area):return 20;
			default:return 0;
		}
	}
    private static function get_buildtype($buildtype){
        switch($buildtype){
            case '多层':return 1;
            case '小高':return 2;
            case '高层':return 3;
            case '别墅':return 4;
            default:return 5;
        }
    }

	public static function randProxy(){		
		if(!Server::$cache->exists('proxy')){
			self::getProxies();
		}		
		#return Server::$cache->srandmember('proxy');
		$proxy = explode('://', Server::$cache->srandmember('proxy'));
		return [$proxy[0]=>'tcp://'.$proxy[1]];		
	}
	public static function checkProxy($ip){
		if(!stream_socket_client(current($ip), $errno, $errstr, 2)){
			#Server::Log("[remove proxy]:" . key($ip).'://'.explode('://', current($ip))[1]);
			Server::$cache->srem('proxy', key($ip).'://'.explode('://', current($ip))[1]);
		}
	}
	public static function checkAllProxy(){
		if(!Server::$cache->exists('proxy')){
			self::getProxies();
		}else{
			foreach(Server::$cache->smembers('proxy') as $v){
				if(!stream_socket_client($v, $errno, $errstr, 2)){
					Server::$cache->srem('proxy', $v);
				}
			}
		}		
	}
	public static function getProxies() {
		$baseuri = 'https://31f.cn/http-proxy/';
		$proxy = self::$ql->get($baseuri)->find('.table.table-striped tr')->map(function($tr){
			return $tr->find('td')->texts();
		})->all();

		for($i=1;$i<=20;$i++){
			if(stream_socket_client(trim($proxy[$i][1]).":".trim($proxy[$i][2]), $errno, $errstr, 1)){
				Server::$cache->sadd('proxy', 'http://'.trim($proxy[$i][1]).':'.trim($proxy[$i][2]));
			}
		}
	}
	public static function getProxies2() {
		$baseuri = 'http://www.xicidaili.com/nn/';
		$proxy = self::$ql->get($baseuri)->find('table#ip_list tr')->map(function($tr){
			return $tr->find('td')->texts();
		})->all();
		
		for($i=1;$i<=30;$i++){
			if(stream_socket_client(trim($proxy[$i][1]).":".trim($proxy[$i][2]), $errno, $errstr, 1)){
				Server::$cache->sadd('proxy', strtolower($proxy[$i][5]).'://'.trim($proxy[$i][1]).':'.trim($proxy[$i][2]));
			}
		}		
	}
	
	public static function randUserAgent($type = 'pc')
    {
        switch ($type) {
            case 'pc':
                return self::$userAgentArray['pc'][array_rand(self::$userAgentArray['pc'])] . rand(0, 10000);
                break;
            case 'android':
                return self::$userAgentArray['android'][array_rand(self::$userAgentArray['android'])] . rand(0, 10000);
                break;
            case 'ios':
                return self::$userAgentArray['ios'][array_rand(self::$userAgentArray['ios'])] . rand(0, 10000);
                break;
            case 'mobile':
                $userAgentArray = array_merge(self::$userAgentArray['android'], self::$userAgentArray['ios']);
                return $userAgentArray[array_rand($userAgentArray)] . rand(0, 10000);
            default:
                return $type;
                break;
        }
    }
	public static $userAgentArray = [
        'pc' => [
			'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.186 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64; rv:29.0) Gecko/20100101 Firefox/29.0',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.137 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:29.0) Gecko/20100101 Firefox/29.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.137 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/537.75.14',
            'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:29.0) Gecko/20100101 Firefox/29.0',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.137 Safari/537.36',            
            'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko',
        ],
        'android' => [
            'Mozilla/5.0 (Android; Mobile; rv:29.0) Gecko/29.0 Firefox/29.0',
            'Mozilla/5.0 (Linux; Android 4.4.2; Nexus 4 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.114 Mobile Safari/537.36',
        ],
        'ios' => [
            'Mozilla/5.0 (iPad; CPU OS 7_0_4 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) CriOS/34.0.1847.18 Mobile/11B554a Safari/9537.53',
            'Mozilla/5.0 (iPad; CPU OS 7_0_4 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11B554a Safari/9537.53',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 8_0_2 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12A366 Safari/600.1.4',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 8_0 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12A366 Safari/600.1.4',
        ],
    ];
}

#print_r(Pickup::getInstance()->pick('pickup_queue'));
#$time = round(microtime(true) - (float)$starton, 5);
#echo '浪费计算时间共：',$time,'    浪费内存共计：', (memory_get_usage(true) / 1024), "kb\n\nDone.\n";