<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Release_EweiShopV2Page extends PluginWebPage 
{
	private $key = 'asdf734JH3464tr56GJ';
	public function main()
	{
		global $_W;
		$error = NULL;
		$auth = $this->model->getAuth();
		

		if (is_error($auth)) {
			$error = $auth['message'];
		}
		else {
			$is_auth = (is_array($auth) ? $auth['is_auth'] : false);
			$authUrl = EWEI_SHOPV2_AUTH_WXAPP . 'index/index/xcxAuth.html?site_id=' . SITE_ID . '&uniacid=' . $_W['uniacid'];

			if ($is_auth) {
				$release = $this->model->getRelease($auth['id']);
			}
		}
       //判断好物圈
      $data = m('common')->getSysset('app');
      $appid = $data['appid'];
      $secret= $data['secret'];
     
     
    
    $content = ihttp_get('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$secret);
     //$content = ihttp_get('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx60ce699828905f72&secret=7a8e2c640387ead53c74b433acdd375b');//禾匠
	 
  $content =json_decode($content['content'],true);
   
    
    
    $result2 = ihttp_post('https://api.weixin.qq.com/wxa/plugin?access_token=' . $content['access_token'], json_encode(array('action' => 'list')));
    $result2=json_decode($result2['content'],true);
    //var_dump( $result2['plugin_list']);	
    if(!empty($result2['plugin_list'])){
      foreach( $result2['plugin_list'] as $k){
           if($k['status']==2 && $k['nickname']=="好物推荐"){
             $sign = 1;
          }
		}
    }else{
    	 $sign = 0;
    }
    $sign= $this->hwq();
      
      //判断好物圈结束

		include $this->template();
	}
  public function hwq(){
   //判断好物圈
      $data = m('common')->getSysset('app');
      $appid = $data['appid'];
      $secret= $data['secret'];
     
    
  
    $content = ihttp_get('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$secret);
     //$content = ihttp_get('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx60ce699828905f72&secret=7a8e2c640387ead53c74b433acdd375b');//禾匠
	 
  $content =json_decode($content['content'],true);
   
   
    
    
    $result2 = ihttp_post('https://api.weixin.qq.com/wxa/plugin?access_token=' . $content['access_token'], json_encode(array('action' => 'list')));
    $result2=json_decode($result2['content'],true);
    //var_dump( $result2['plugin_list']);	
    if(!empty($result2['plugin_list'])){
      foreach( $result2['plugin_list'] as $k){
           if($k['status']==2 && $k['nickname']=="好物推荐"){
             $sign = 1;
             $hwqappid = $k['appid'];
          }
		}
    }else{
    	 $sign = 0;
    }
    return $hwqappid;
  }
	public function audit() 
	{
		global $_W;
		global $_GPC;
		if (!($_W['ispost'])) 
		{
			show_json(0, '错误的请求');
		}
		$auth = $this->model->getAuth();
		if (is_error($auth)) 
		{
			show_json(0, $auth['message']);
		}
		$action = trim($_GPC['action']);
		if (($action != 'upload') && ($action != 'audit')) 
		{
			show_json(0, '请求参数错误');
		}
		load()->func('communication');
		if ($action == 'upload') 
		{
			$tabBar = '';
			$app_set = m('common')->getSysset('app');
			if (!(empty($app_set))) 
			{
				if (!(empty($app_set['tabbar']))) 
				{
					$app_set['tabbar'] = iunserializer($app_set['tabbar']);
					if (!(empty($app_set['tabbar']))) 
					{
						$tabBar = $app_set['tabbar'];
					}
				}
			}
			if (is_array($tabBar)) 
			{
				if (is_array($tabBar['list'])) 
				{
					foreach ($tabBar['list'] as $index => &$item ) 
					{
						$item['pagePath'] = ltrim($item['pagePath'], '/');
					}
					unset($index, $item);
				}
				$tabBar = json_encode($tabBar);
			}
          $hwqappid=$this->hwq();
			$request = ihttp_post(EWEI_SHOPV2_AUTH_WXAPP . 'index/index/submitCode4.html?ver=31724&site_id=' . SITE_ID . '&uniacid=' . $_W['uniacid'].'&hwqappid=' . $hwqappid, array('tabBar' => $tabBar));
		}
		else 
		{
			$request = ihttp_post(EWEI_SHOPV2_AUTH_WXAPP . 'index/index/auditCode.html?site_id=' . SITE_ID . '&uniacid=' . $_W['uniacid'], array());
		}
		if( $request["code"] != 200 ) 
		{
			show_json(0, "信息查询失败！稍后重试(" . $request["code"] . ")");
		}
		if( empty($request["content"]) ) 
		{
			show_json(0, "信息查询失败！稍后重试(nodata)");
		}
		$content = json_decode($request["content"], true);
		if( !is_array($content) ) 
		{
			show_json(0, "信息查询失败！稍后重试(dataerror)");
		}
		if ($content['errcode'] != 0) 
		{
			show_json(0, $content['errmsg']);
		}
		show_json(1);
	}
	public function auth() 
	{
		global $_W;
		$auth = $this->model->getAuth();
		if( is_error($auth) ) 
		{
			$this->message($auth["message"]);
		}
		$authid = $this->encrypt($auth["id"] . $this->key, $this->key);
		header('Location:' . EWEI_SHOPV2_AUTH_WXAPP . 'index/index/xcxAuth?site_id=' . SITE_ID . '&uniacid=' . $_W['uniacid']);
	}
	protected function encrypt($data, $key) 
	{
		$key = md5($key);
		$char = '';
		$str = '';
		$x = 0;
		$len = strlen($data);
		$l = strlen($key);
		$i = 0;
		while ($i < $len) 
		{
			if ($x == $l) 
			{
				$x = 0;
			}
			$char .= $key[$x];
			++$x;
			++$i;
		}
		$i = 0;
		while ($i < $len) 
		{
			$str .= chr(ord($data[$i]) + (ord($char[$i]) % 256));
			++$i;
		}
		return base64_encode($str);
	}
	public function all() 
	{
		global $_W;
		global $_GPC;
		include $this->template('app/releaseall');
	}
}
?>