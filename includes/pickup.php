<?php
use QL\QueryList;
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
			'name'	=>	['h1.fl', 'text'],
			'catnum'=>	['.fl.mlmt25', 'text', '-span', function($content){
							$cat = [];
							preg_match('#([A-Z]\d+)#is', $content, $cat);
							return $cat[0];
						}],
			'img'	=>	['div.grid-width-1-4 > img', 'src']
		);
	}
	
	public static function setOption(){
		return array(
			'max'		=>	5,
			'timeout'	=>	10, 
			'connect_timeout'=>	5, 
			'delay'		=>	500,
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
				#'Cookie'   	=> 'abc=111;xxx=222'
			],
		);
	}
	
	public static function pick($url){
		$data = self::$ql::get($url, '', self::$option)->rules(self::$rule)
						   ->query()
						   ->getData()
						   ->all();		
		DB::table('cr_pickdata')->insert($data);
	}
	public static function randProxy(){
		$cache = new Redis();
		$cache->connect('127.0.0.1', 6379);
		$cache->select(3);
		if(!$cache->exists('proxy')){
			self::getProxies($cache);
		}
		$proxy = explode('://', $cache->srandmember('proxy'));
		return [$proxy[0]=>'tcp://'.$proxy[1]];
	}
	public static function checkProxy(){
		$cache = new Redis();
		$cache->connect('127.0.0.1', 6379);
		$cache->select(3);
		if(!$cache->exists('proxy')){
			self::getProxies($cache);
		}else{
			foreach($cache->smembers('proxy') as $v){
				if(stream_socket_client($v, $errno, $errstr, 1)){
					$cache->srem('proxy', $v);
				}
			}
		}		
	}
	public static function getProxies($cache) {
		$baseuri = 'http://www.31f.cn';
		$proxy = QueryList::get($baseuri)->find('.table.table-striped tr')->map(function($tr){
			return $tr->find('td')->texts();
		})->all();
		
		for($i=1;$i<=10;$i++){
			if(stream_socket_client(trim($proxy[$i][1]).":".trim($proxy[$i][2]), $errno, $errstr, 1)){
				$cache->sadd('proxy', 'http://'.trim($proxy[$i][1]).':'.trim($proxy[$i][2]));
			}
		}
	}
	public static function getProxies1($cache) {
		$baseuri = 'http://www.xicidaili.com/nn/';
		$proxy = QueryList::get($baseuri)->find('table#ip_list tr')->map(function($tr){
			return $tr->find('td')->texts();
		})->all();
		print_r($proxy);
		
		for($i=1;$i<=50;$i++){
			if(@stream_socket_client(trim($proxy[$i][1]).":".trim($proxy[$i][2]), $errno, $errstr, 1)){
				$cache->sadd('proxy', strtolower($proxy[$i][5]).'://'.trim($proxy[$i][1]).':'.trim($proxy[$i][2]));
			}
		}
	}
	
	public static function randUserAgent($type = 'pc'){
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
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64; rv:29.0) Gecko/20100101 Firefox/29.0',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.137 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:29.0) Gecko/20100101 Firefox/29.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.137 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/537.75.14',
            'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:29.0) Gecko/20100101 Firefox/29.0',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.137 Safari/537.36',
            'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)',
            'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)',
            'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0)',
            'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)',
            'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; WOW64; Trident/6.0)',
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

#print_r(Pickup::getInstance()->pick('http://www.selleck.cn/products/vitamin-a-palmitate.html'));
