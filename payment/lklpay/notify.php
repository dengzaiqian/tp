<?php
error_reporting(0);
define('IN_MOBILE', true);
require '../../framework/bootstrap.inc.php';
load()->web('common');
load()->classs('coupon');
$input = file_get_contents('php://input');
libxml_disable_entity_loader(true);

if ( !(empty($input)) ) 
{
	$obj = simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);
	$data = json_decode(json_encode($obj), true);
	// $data = json_decode(file_get_contents('./notice.txt'),true);
	if( empty($data) || $data['responseCode']!='000000' ) 
	{
		exit(json_encode($data));
	}
		$get = $data;
		$order = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_order') . ' WHERE ordersn = :ordersn AND uniacid = :uniacid', array(':ordersn' => $get['ornReqLogNo'], ':uniacid' =>1));
		$sql = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE `module`=:module AND `tid`=:tid  limit 1';
		$params = array();
		$params[':tid'] = $get['ornReqLogNo'];
		$params[':module'] = 'ewei_shopv2';
		$log = pdo_fetch($sql, $params);
		file_put_contents('./order.txt',json_encode($order,true));
		file_put_contents('./log.txt',json_encode($log,true));
		// print_r($order);exit();
		if ( !(empty($log)) && !empty($order) && $order['status']==0 ) 
		{
			pdo_update('ewei_shop_order',
				array(
					'status' => 1,  
					'paytype' => 21,  
					'transid' => $get['payOrderId']
				), 
				array('ordersn' => $log['tid'], 'uniacid' => $log['uniacid']));
			
			$site = WeUtility::createModuleSite($log['module']);
			
			$method = 'payResult';
			if (method_exists($site, $method)) 
			{
				$ret = array();
				$ret['acid'] = $log['acid'];
				$ret['uniacid'] = $log['uniacid'];
				$ret['result'] = 'success';
				$ret['type'] = $log['type'];
				$ret['from'] = 'return';
				$ret['tid'] = $log['tid'];
				$ret['user'] = $log['openid'];
				$ret['fee'] = $log['fee'];
				$ret['tag'] = $log['tag'];
				$result = $site->$method($ret);
				if ($result) 
				{
					$log['tag'] = iunserializer($log['tag']);
					$log['tag']['transaction_id'] = $get['payOrderId'];
					$record = array();
					$record['status'] = '1';
					$record['tag'] = iserializer($log['tag']);
					pdo_update('core_paylog', $record, array('plid' => $log['plid']));
				
					//短信通知供货商发货
					$sms_order =  pdo_fetch('select mu.mobile,o.address from ims_ewei_shop_order as o left join ims_ewei_shop_order_goods as og on o.id=og.orderid left join ims_ewei_shop_goods as g on og.goodsid=g.id left join ims_ewei_shop_merch_user as mu on g.merchid=mu.id where o.ordersn="'.$get['ornReqLogNo'].'"');
					// print_r($order);
					if($sms_order){
						$addressInfo = unserialize($sms_order['address']);
						$phonenumbers = empty($sms_order['mobile'])?'15732526315':$sms_order['mobile'];
						$name = empty($addressInfo['realname'])?'樊素飞':$addressInfo['realname'];
						$mobile = empty($addressInfo['mobile'])?'15732526315':$addressInfo['mobile'];
					}
					$param = array();
					$param['keyid']			='LTAIddEO4hOjm2Lj';
					$param['keysecret']		='8BNqLdj9TEoJEtOAVnlCdPNvVwzCGv';
					$param['signname']		='九方无隅';
					$param['templatecode']	='SMS_186515513';
					// $param['templatecode']	='SMS_168586764';
					$param['phonenumbers'] 	= $phonenumbers;
					$param['templateparam']['consignee'] = $name;
					$param['templateparam']['number'] = $mobile;
					// $param['templateparam']['code'] = $mobile;
					// print_r($param);
					$result = sendsms($param);
					// var_dump( $result  );
				}
			}
		}
		
}
else 
{
	$get = $_GET;
	if(empty($get['ornReqLogNo'])){
		$get['ornReqLogNo'] = 'SH15845037056266';
	}

}
//发送验证码
function sendsms($options = array()){
	print_r($options);
	$params = array();
	// *** 需用户填写部分 **
	// fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
	$accessKeyId = $options['keyid'];
	$accessKeySecret = $options['keysecret'];
	// fixme 必填: 短信接收号码
	$params["PhoneNumbers"] = $options['phonenumbers'];
	// fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
	$params["SignName"] = $options['signname'];
	// fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
	$params["TemplateCode"] = $options['templatecode'];
	// fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
	$params['TemplateParam'] = $options['templateparam'];

	if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
		$params["TemplateParam"] = json_encode($params["TemplateParam"]);
	}
	// 初始化SignatureHelper实例用于设置参数，签名以及发送请求
	// $helper = new SignatureHelper();
	$helper = new sendSms();
	// 此处可能会抛出异常，注意catch
	$content = $helper->request(
		$accessKeyId,
		$accessKeySecret,
		"dysmsapi.aliyuncs.com",
		array_merge($params, array(
			"RegionId" => "cn-hangzhou",
			"Action" => "SendSms",
			"Version" => "2017-05-25",
		)),
		false
	);
	return $content;
}

class sendSms {

    public function request($accessKeyId, $accessKeySecret, $domain, $params, $security=false, $method='POST') {
        $apiParams = array_merge(array (
            "SignatureMethod" => "HMAC-SHA1",
            "SignatureNonce" => uniqid(mt_rand(0,0xffff), true),
            "SignatureVersion" => "1.0",
            "AccessKeyId" => $accessKeyId,
            "Timestamp" => gmdate("Y-m-d\TH:i:s\Z"),
            "Format" => "JSON",
        ), $params);
        ksort($apiParams);

        $sortedQueryStringTmp = "";
        foreach ($apiParams as $key => $value) {
            $sortedQueryStringTmp .= "&" . $this->encode($key) . "=" . $this->encode($value);
        }

        $stringToSign = "${method}&%2F&" . $this->encode(substr($sortedQueryStringTmp, 1));

        $sign = base64_encode(hash_hmac("sha1", $stringToSign, $accessKeySecret . "&",true));

        $signature = $this->encode($sign);

        $url = ($security ? 'https' : 'http')."://{$domain}/";

        try {
            $content = $this->fetchContent($url, $method, "Signature={$signature}{$sortedQueryStringTmp}");
            return json_decode($content,true);
        } catch( \Exception $e) {
            return false;
        }
    }

    private function encode($str)
    {
        $res = urlencode($str);
        $res = preg_replace("/\+/", "%20", $res);
        $res = preg_replace("/\*/", "%2A", $res);
        $res = preg_replace("/%7E/", "~", $res);
        return $res;
    }

    private function fetchContent($url, $method, $body) {
        $ch = curl_init();

        if($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        } else {
            $url .= '?'.$body;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "x-sdk-client" => "php/2.0.0"
        ));

        if(substr($url, 0,5) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $rtn = curl_exec($ch);

        if($rtn === false) {
            // 大多由设置等原因引起，一般无法保障后续逻辑正常执行，
            // 所以这里触发的是E_USER_ERROR，会终止脚本执行，无法被try...catch捕获，需要用户排查环境、网络等故障
            trigger_error("[CURL_" . curl_errno($ch) . "]: " . curl_error($ch), E_USER_ERROR);
        }
        curl_close($ch);

        return $rtn;
    }
}

?>