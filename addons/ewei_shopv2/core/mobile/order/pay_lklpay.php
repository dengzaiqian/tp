<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
class Pay_Lklpay_EweiShopV2Page extends MobilePage 
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$url = urldecode($_GPC["url"]);
		if( !is_weixin() ) 
		{
			header("location: " . $url);
			exit();
		}
		include($this->template("order/alipay"));
	}
	private function config() {
        $url = 'https://prd.lakala.com/prd01/thirdpartplatform/scancodpay/8011.dor';
        return 
			array(
				'appId' 		=>'wxe45677466dc48aa4',//appid
				'merchId' 		=>'822121048161239',//商户号
				'BMCP' 			=>'', 
				'termId' 		=>'54158462',//终端号
				'compOrgCode' 	=>'HBJFWYKJ', //机构代码
				'Sercret_Key' 	=>'mMBvvOW4qzTMJTOnUVrbWgxh1ETdzbJo',
				'url' 			=> $url,
				'notify_url' 	=>'http://rongwei.jfwide.com/payment/lklpay/notify.php'
			);
    }
	public function prepay(){
		global $_GPC;
		global $_W;
		$uniacid = $_W["uniacid"];
		$orderid = intval($_GPC["id"]);	
		$openid = $_W["openid"];		
		$set = m("common")->getSysset(array( "pay" ));
		if( !empty($set) && $set['pay']['lkl_pay']['open']==1 ){
			$order = pdo_fetch("select * from " . tablename("ewei_shop_order") . " where id=:id and uniacid=:uniacid limit 1", array( ":id" => $orderid, ":uniacid" => $uniacid ));
			// print_r($order);exit();
			if( empty($order) || $order['status']!=0 ){
				show_json(0,'订单异常');
			}
			$member = m("member")->getMember($openid, true);
			if( !empty($order['lklpayinfo']) ){ //已经申请过支付的更新订单编号
				$ordersn = m("common")->createNO("order", "ordersn", "SH");
				$result = pdo_update("ewei_shop_order",array('ordersn'=>$ordersn), array( "id"=>$orderid, "uniacid" => $uniacid ));
                if($result){
					$pay_log = pdo_fetch("select * from ".tablename("core_paylog")." where tid='".$order['ordersn']."' and uniacid=".$uniacid." limit 1");
					if(!empty($pay_log)){
						pdo_update("core_paylog",array('tid'=>$ordersn),array('tid'=>$order['ordersn']));
					}else{
						$log = array( 
							"uniacid" => $uniacid, 
							"openid" => trim($member["uid"]), 
							"module" => "ewei_shopv2", 
							"tid" => trim($ordersn), 
							"fee" => $order["price"], 
							"status" => 0
						);
						pdo_insert("core_paylog", $log);
					}
					$order['ordersn'] = $ordersn;
				}
			}
			
			$config = $this->config();
			$config['appId'] 	= !empty($_W['account']['key'])?$_W['account']['key']: $config['appId'];
			$config['merchId']	= !empty($set['pay']['lkl_pay']['bank'])?$set['pay']['lkl_pay']['bank']: $config['merchId'];
			$config['termId'] 	= !empty($set['pay']['lkl_pay']['banknum'])?$set['pay']['lkl_pay']['banknum']: $config['termId'];
			$url = $config['url'];
			$data = array(
				'compOrgCode' 	=> $config['compOrgCode'],
				// 'reqLogNo'	 	=> str_pad($orderid,16,'0',STR_PAD_LEFT ),
				'reqLogNo'	 	=> $order['ordersn'],
				'reqTm' 		=> date("YmdHis"),
				'payChlTyp' 	=> 'WECHAT',
				'mercId' 		=> $config['merchId'],
				'termId' 		=> $config['termId'],
				'txnAmt' 		=> $order['price'] * 100,
			);

			foreach ($data as $k => $v) {
				$str .= $v;
			}
			$str .= $config['Sercret_Key'];
			$mac = sha1($str);
			$data['MAC'] 		= $mac;
			$data['FunCod'] 	= '8011'; //固定值
			$data['tradeType'] 	= 'JSAPI';
			$data['sub_appid'] 	= $config['appId']; //推荐关注公众号APPID
			$data['openId'] 	= $openid;
			$data['NTF_URL'] 	= $config['notify_url'];

			$xml_data = '<?xml version="1.0" encoding="GBK" standalone="yes" ?> <xml>';
			foreach ($data as $key => $value) {
				$xml_data .= "<" . $key . ">" . $value . "</" . $key . ">";
			}
			$xml_data .= "</xml>";
			// 转化成GBK编码（拉卡拉需要GBK）
			// 1.识别编码
			$encode = mb_detect_encoding($xml_data, array("ASCII", "UTF-8", "GB2312", "GBK", "BIG5"));
			// 2.将字符串转换成GBK编码
			$xml_data = mb_convert_encoding($xml_data, 'GBK', $encode);
			$response = $this->postXmlCurl($xml_data, $url);
			$payinfo = $this->xmlstr_to_array($response);
			// $payinfo['responseCode'] = '000000';
			if ($payinfo['responseCode'] == '000000') {
				// $payinfo['extRpData'] = "appId=wxefadce7b35bd05ed|timeStamp=1576661439|nonceStr=1cca18a7bb0b4c8987e3a58494239e65|package=prepay_id=wx1817303943393482dd48e2b61918365300|signType=RSA|paySign=HBNFcxgIO5pcHCiGPZkk9f6160AmMYoaSgZxSvEhChQMPegxqEqF+NV9Al5eEy4jTzrPJJZYL/S8fuNnfjLf15zd1m6mwbhoy3xcvaXbaI1Htq3FfcP1pTtaKJAmGl1ZDkY0HjpPZQqMXKl2bZ3WmeZMNRuj+PcQ6Dr5sIiJK1BTCSSwy0AN2Xj/AcuSOliNuqPUFfguwjtV49FcWt2m/N6xcAljYi7SfNl1MXhMJbuN8cFW9i7Eh+GLs2q/k1bON7sJj5i2EVVsItNZsSSSApHpPU2NwaqXEc2vq2n2ntFjGjm1OeCwupVTjjfDxF3O7LccYUQOhzsiItX3FoTl0Q==";
                $lkl = array(
                    'mac' 			=> $payinfo['MAC'],
                    'extRpData' 	=> $payinfo['extRpData'],
                    'merOrderNo' 	=> $payinfo['merOrderNo'],
                    'mercId' 		=> $payinfo['mercId'],
                    'payChlDesc' 	=> $payinfo['payChlDesc'],
                    'prePayId' 		=> $payinfo['prePayId'],
                    'txnTm' 		=> $payinfo['txnTm'],
                    'userId' 		=> $payinfo['userId'],
                    'reqLogNo' 		=> $payinfo['reqLogNo'],
                    'txnAmt' 		=> $payinfo['txnAmt'],
                );
                pdo_update("ewei_shop_order",array('lklpayinfo'=>serialize(array('data'=>$data,'xml_data'=>$xml_data,'ans'=>$lkl,'pre'=>$payinfo))), array( "id"=>$orderid, "uniacid" => $uniacid ));
                $pre_payinfo = explode('|',$payinfo['extRpData']);
				$pre_pay = array();
				foreach($pre_payinfo as $info){
					$arrs = explode('=',$info);
					switch($arrs[0]){
						case 'appId':
							$pre_pay['appId'] = $arrs[1] ; break;						
						case 'timeStamp':
							$pre_pay['timeStamp'] = $arrs[1] ; break;						
						case 'nonceStr':
							$pre_pay['nonceStr'] = $arrs[1] ; break;						
						case 'package':
							$pre_pay['package'] = $arrs[1].'='.$arrs[2] ; break;						
						case 'signType':
							$pre_pay['signType'] = $arrs[1] ; break;
						case 'paySign':
							$pre_pay['paySign'] = str_replace('paySign=','',$info); break;
					}
				}
				show_json(1, array('msg'=>$payinfo['message'],'pay'=>$pre_pay,'info'=>$lkl));
			} else {
                $lkl = array(
                    'reqLogNo' => $order['ordersn'],
                    'extRpData' => json_encode($payinfo),
                );
				pdo_update("ewei_shop_order",array('lklpayinfo'=>serialize($lkl)), array( "id"=>$orderid, "uniacid" => $uniacid ));
                show_json(0, array('msg'=>$payinfo['message'],'set'=>$set,'data' =>$data,'xml_data' =>$xml_data,'info'=>$payinfo));
            }
		}else{
			show_json(0,array('msg'=>'拉卡拉支付未开启','set'=>$set));
		}
	}
	
	//xml转换数组
    private function xmlstr_to_array($xmlstr) {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xmlstr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $val = json_decode(json_encode($xmlstring), true);
        return $val;
    }

	/* post https请求，CURLOPT_POSTFIELDS xml格式 */
    private function postXmlCurl($xml, $url, $second = 30) {
        // 初始化curl
        $ch = curl_init();
        // 超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        // 这里设置代理，如果有的话
        // curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        // curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		// 设置header
        curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type:text/xml; charset=utf-8"));
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
		// 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        // 运行curl
        $data = curl_exec($ch);

        // 返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            echo "curl出错，错误码:$error";
			//throw new Exception("curl出错，错误码:$error");
        }
    }

	public function complete() 
	{
		global $_GPC;
		global $_W;
		if($_GPC['type']=='lklpay'){
			show_json(1, array( "message" =>'支付成功' ));
		}
		$set = m("common")->getSysset(array( "shop", "pay" ));
		$fromwechat = intval($_GPC["fromwechat"]);
		$tid = $_GPC["out_trade_no"];
		if( is_h5app() ) 
		{
			$sec = m("common")->getSec();
			$sec = iunserializer($sec["sec"]);
			$alidata = base64_decode($_GET["alidata"]);
			$alidata = json_decode($alidata, true);
			$sign_type = trim($alidata["sign_type"], "\"");
			if( $sign_type == "RSA" ) 
			{
				$public_key = $sec["app_alipay"]["public_key"];
			}
			else 
			{
				if( $sign_type == "RSA2" ) 
				{
					$public_key = $sec["app_alipay"]["public_key_rsa2"];
				}
			}
			if( empty($set["pay"]["app_alipay"]) || empty($public_key) ) 
			{
				$this->message("支付出现错误，请重试(1)!", mobileUrl("order"));
			}
			$alisign = m("finance")->RSAVerify($alidata, $public_key, false);
			$tid = $this->str($alidata["out_trade_no"]);
			if( $alisign == 0 ) 
			{
				$this->message("支付出现错误，请重试(2)!", mobileUrl("order"));
			}
			if( strexists($tid, "GJ") ) 
			{
				$tids = explode("GJ", $tid);
				$tid = $tids[0];
			}
		}
		else 
		{
			if( empty($set["pay"]["alipay"]) ) 
			{
				$this->message("未开启支付宝支付!", mobileUrl("order"));
			}
			if( !m("finance")->isAlipayNotify($_GET) ) 
			{
				$log = pdo_fetch("SELECT * FROM " . tablename("core_paylog") . " WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1", array( ":uniacid" => $_W["uniacid"], ":module" => "ewei_shopv2", ":tid" => $tid ));
				if( $log["status"] == 1 && $log["fee"] == $_GPC["total_fee"] ) 
				{
					if( $fromwechat ) 
					{
						$this->message(array( "message" => "请返回微信查看支付状态", "title" => "支付成功!", "buttondisplay" => false ), NULL, "success");
					}
					else 
					{
						$this->message(array( "message" => "请返回商城查看支付状态", "title" => "支付成功!" ), mobileUrl("order"), "success");
					}
				}
				$this->message(array( "message" => "支付出现错误，请重试(支付验证失败)!", "buttondisplay" => ($fromwechat ? false : true) ), ($fromwechat ? NULL : mobileUrl("order")));
			}
		}
		$log = pdo_fetch("SELECT * FROM " . tablename("core_paylog") . " WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1", array( ":uniacid" => $_W["uniacid"], ":module" => "ewei_shopv2", ":tid" => $tid ));
		if( empty($log) ) 
		{
			$this->message(array( "message" => "支付出现错误，请重试(支付验证失败2)!", "buttondisplay" => ($fromwechat ? false : true) ), ($fromwechat ? NULL : mobileUrl("order")));
		}
		if( is_h5app() ) 
		{
			$alidatafee = $this->str($alidata["total_fee"]);
			$alidatastatus = $this->str($alidata["success"]);
			if( $log["fee"] != $alidatafee || !$alidatastatus ) 
			{
				$this->message("支付出现错误，请重试(4)!", mobileUrl("order"));
			}
		}
		if( $log["status"] != 1 ) 
		{
			$record = array( );
			$record["status"] = "1";
			$record["type"] = "alipay";
			pdo_update("core_paylog", $record, array( "plid" => $log["plid"] ));
			$orderid = pdo_fetchcolumn("select id from " . tablename("ewei_shop_order") . " where ordersn=:ordersn and uniacid=:uniacid", array( ":ordersn" => $log["tid"], ":uniacid" => $_W["uniacid"] ));
			if( !empty($orderid) ) 
			{
				m("order")->setOrderPayType($orderid, 22);
				$data_alipay = array( "transid" => $_GET["trade_no"] );
				if( is_h5app() ) 
				{
					$data_alipay["transid"] = $alidata["trade_no"];
					$data_alipay["apppay"] = 1;
				}
				pdo_update("ewei_shop_order", $data_alipay, array( "id" => $orderid ));
			}
			$ret = array( );
			$ret["result"] = "success";
			$ret["type"] = "alipay";
			$ret["from"] = "return";
			$ret["tid"] = $log["tid"];
			$ret["user"] = $log["openid"];
			$ret["fee"] = $log["fee"];
			$ret["weid"] = $log["weid"];
			$ret["uniacid"] = $log["uniacid"];
			m("order")->payResult($ret);
		}
		if( is_h5app() ) 
		{
			$url = mobileUrl("order/detail", array( "id" => $orderid ), true);
			exit( "<script>top.window.location.href='" . $url . "'</script>" );
		}
		if( $fromwechat ) 
		{
			$this->message(array( "message" => "请返回微信查看支付状态", "title" => "支付成功!", "buttondisplay" => false ), NULL, "success");
		}
		else 
		{
			$this->message(array( "message" => "请返回商城查看支付状态", "title" => "支付成功!" ), mobileUrl("order"), "success");
		}
	}
	public function recharge_complete() 
	{
		global $_W;
		global $_GPC;
		$fromwechat = intval($_GPC["fromwechat"]);
		$logno = trim($_GPC["out_trade_no"]);
		$notify_id = trim($_GPC["notify_id"]);
		$sign = trim($_GPC["sign"]);
		$set = m("common")->getSysset(array( "shop", "pay" ));
		if( is_h5app() ) 
		{
			$sec = m("common")->getSec();
			$sec = iunserializer($sec["sec"]);
			if( empty($_GET["alidata"]) ) 
			{
				$this->message("支付出现错误，请重试(1)!", mobileUrl("member"));
			}
			$alidata = base64_decode($_GET["alidata"]);
			$alidata = json_decode($alidata, true);
			$sign_type = $alidata["sign_type"];
			if( $sign_type == "RSA" ) 
			{
				$public_key = $sec["app_alipay"]["public_key"];
			}
			else 
			{
				if( $sign_type == "RSA2" ) 
				{
					$public_key = $sec["app_alipay"]["public_key_rsa2"];
				}
			}
			if( empty($set["pay"]["app_alipay"]) || empty($public_key) ) 
			{
				$this->message("支付出现错误，请重试(2)!", mobileUrl("order"));
			}
			$alisign = m("finance")->RSAVerify($alidata, $public_key, false);
			$logno = $this->str($alidata["out_trade_no"]);
			if( $alisign == 0 ) 
			{
				$this->message("支付出现错误，请重试(3)!", mobileUrl("member"));
			}
			$transid = $alidata["trade_no"];
		}
		else 
		{
			if( empty($logno) ) 
			{
				$this->message(array( "message" => "支付出现错误，请重试(支付验证失败1)!", "buttondisplay" => ($fromwechat ? false : true) ), ($fromwechat ? NULL : mobileUrl("member")));
			}
			if( empty($set["pay"]["alipay"]) ) 
			{
				$this->message(array( "message" => "支付出现错误，请重试(未开启支付宝支付)!", "buttondisplay" => ($fromwechat ? false : true) ), ($fromwechat ? NULL : mobileUrl("member")));
			}
			if( !m("finance")->isAlipayNotify($_GET) ) 
			{
				$log = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_member_log") . " WHERE `logno`=:logno and `uniacid`=:uniacid limit 1", array( ":uniacid" => $_W["uniacid"], ":logno" => $logno ));
				if( !empty($log) && !empty($log["status"]) ) 
				{
					if( $fromwechat ) 
					{
						$this->message(array( "message" => "请返回微信查看支付状态", "title" => "支付成功!", "buttondisplay" => false ), NULL, "success");
					}
					else 
					{
						$this->message(array( "message" => "请返回商城查看支付状态", "title" => "支付成功!" ), mobileUrl("member"), "success");
					}
				}
				$this->message(array( "message" => "支付出现错误，请重试(支付验证失败2)!", "buttondisplay" => ($fromwechat ? false : true) ), ($fromwechat ? NULL : mobileUrl("member")));
			}
			$transid = $_GET["trade_no"];
		}
		$log = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_member_log") . " WHERE `logno`=:logno and `uniacid`=:uniacid limit 1", array( ":uniacid" => $_W["uniacid"], ":logno" => $logno ));
		if( !empty($log) && empty($log["status"]) ) 
		{
			pdo_update("ewei_shop_member_log", array( "status" => 1, "rechargetype" => "alipay", "apppay" => (is_h5app() ? 1 : 0), "transid" => $transid ), array( "id" => $log["id"] ));
			m("member")->setCredit($log["openid"], "credit2", $log["money"], array( 0, $_W["shopset"]["shop"]["name"] . "会员充值:alipayreturn:credit2:" . $log["money"] ));
			m("member")->setRechargeCredit($log["openid"], $log["money"]);
			com_run("sale::setRechargeActivity", $log);
			com_run("coupon::useRechargeCoupon", $log);
			m("notice")->sendMemberLogMessage($log["id"]);
			$member = m("member")->getMember($log["openid"]);
			$params = array( "nickname" => (empty($member["nickname"]) ? "未更新" : $member["nickname"]), "price" => $log["money"], "paytype" => "支付宝支付", "paytime" => date("Y-m-d H:i:s", time()) );
			com_run("printer::sendRechargeMessage", $params);
		}
		if( is_h5app() ) 
		{
			$url = mobileUrl("member", NULL, true);
			exit( "<script>top.window.location.href='" . $url . "'</script>" );
		}
		if( $fromwechat ) 
		{
			$this->message(array( "message" => "请返回微信查看支付状态", "title" => "支付成功!", "buttondisplay" => false ), NULL, "success");
		}
		else 
		{
			$this->message(array( "message" => "请返回商城查看支付状态", "title" => "支付成功!" ), mobileUrl("member"), "success");
		}
	}
	protected function str($str) 
	{
		$str = str_replace("\"", "", $str);
		$str = str_replace("'", "", $str);
		return $str;
	}
}
?>