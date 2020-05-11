<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("IN_IA")) {
    exit("Access Denied");
}
require_once EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php";
class Pay_EweiShopV2Page extends AppMobilePage
{
    const AFTER_PAY = 1;
    private function config() {
        $url = 'https://prd.lakala.com/prd01/thirdpartplatform/scancodpay/8011.dor';
        return 
                array(
                        'appId' 		=>'wx277ed99b32579689',//appid
                        'merchId' 		=>'822121051220062',//商户号
                        'BMCP' 			=>'', 
                        'termId' 		=>'55855545',//终端号
                        'compOrgCode' 	=>'HBJFWYKJ', //机构代码
                        'Sercret_Key' 	=>'mMBvvOW4qzTMJTOnUVrbWgxh1ETdzbJo',
                        'url' 			=> $url,
                        'notify_url' 	=>'http://rongwei.jfwide.com/payment/lklpay/notify.php'
                );
    }
    public function main()
    {
        global $_W;
        global $_GPC;
        $openid = $_W["openid"];
        $uniacid = $_W["uniacid"];
        $member = m("member")->getMember($openid, true);
        $orderid = intval($_GPC["id"]);
        if (empty($orderid)) {
            return app_error(AppError::$ParamsError);
        }
        $order = pdo_fetch("select * from " . tablename("ewei_shop_order") . " where id=:id and uniacid=:uniacid and openid=:openid limit 1", array(":id" => $orderid, ":uniacid" => $uniacid, ":openid" => $openid));
        if (empty($order)) {
            return app_error(AppError::$OrderNotFound);
        }
        if ($order["status"] == -1) {
            return app_error(AppError::$OrderCannotPay);
        }
        if (1 <= $order["status"]) {
            return app_error(AppError::$OrderAlreadyPay);
        }
        $log = pdo_fetch("SELECT * FROM " . tablename("core_paylog") . " WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1", array(":uniacid" => $uniacid, ":module" => "ewei_shopv2", ":tid" => $order["ordersn"]));
        if (!empty($log) && $log["status"] != "0") {
            return app_error(AppError::$OrderAlreadyPay);
        }
        if (!empty($log) && $log["status"] == "0") {
            pdo_delete("core_paylog", array("plid" => $log["plid"]));
            $log = NULL;
        }
        if (empty($log)) {
            $log = array("uniacid" => $uniacid, "openid" => $member["uid"], "module" => "ewei_shopv2", "tid" => $order["ordersn"], "fee" => $order["price"], "status" => 0);
            pdo_insert("core_paylog", $log);
            $plid = pdo_insertid();
        }
        $set = m("common")->getSysset(array("shop", "pay"));
        $credit = array("success" => false);
        if (isset($set["pay"]) && $set["pay"]["credit"] == 1) {
            $credit = array("success" => true, "current" => $member["credit2"]);
        }
        $wechat = array("success" => false);
        if (!empty($set["pay"]["wxapp"]) && 0 < $order["price"] && $this->iswxapp) {
            $tid = $order["ordersn"];
            if (!empty($order["ordersn2"])) {
                $var = sprintf("%02d", $order["ordersn2"]);
                $tid .= "GJ" . $var;
            }
            $payinfo = array("openid" => $_W["openid_wa"], "title" => $set["shop"]["name"] . "订单", "tid" => $tid, "fee" => $order["price"]);
            $res = $this->model->wxpay($payinfo, 14);
            if (!is_error($res)) {
                $wechat = array("success" => true, "payinfo" => $res);
                if (!empty($res["package"]) && strexists($res["package"], "prepay_id=")) {
                    $prepay_id = str_replace("prepay_id=", "", $res["package"]);
                    pdo_update("ewei_shop_order", array("wxapp_prepay_id" => $prepay_id), array("id" => $orderid, "uniacid" => $_W["uniacid"]));
                }
            } else {
                $wechat["payinfo"] = $res;
            }
            if (!$this->checkwxstock($order["id"])) {
                $wechat = array("success" => false);
            }
        }
        if (!empty($order["addressid"])) {
            $cash = array("success" => $order["cash"] == 1 && isset($set["pay"]) && $set["pay"]["cash"] == 1 && $order["isverify"] == 0 && $order["isvirtual"] == 0);
        }
        $alipay = array("success" => false);
        if (!empty($set["pay"]["nativeapp_alipay"]) && 0 < $order["price"] && !$this->iswxapp) {
            $params = array("out_trade_no" => $log["tid"], "total_amount" => $order["price"], "subject" => $set["shop"]["name"] . "订单", "body" => $_W["uniacid"] . ":0:NATIVEAPP");
            $sec = m("common")->getSec();
            $sec = iunserializer($sec["sec"]);
            $alipay_config = $sec["nativeapp"]["alipay"];
            if (!empty($alipay_config)) {
                $res = $this->model->alipay_build($params, $alipay_config);
                $alipay = array("success" => true, "payinfo" => $res);
            }
        }
        //添加拉卡拉支付
        //加拉卡拉支付 start
        $lklpay = array( "success" => false );
        $lklpay_set = array();
        list($lklpay_set,) =  m("common")->public_build();
        if(!empty($lklpay_set) && is_array($lklpay_set['lkl_pay']) && $lklpay_set['lkl_pay']['open']==1){
                $lklpay['success'] = true;
    }
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
        $config=$this->config();
        // $config['appId'] 	= !empty($_W['account']['key'])?$_W['account']['key']: $config['appId'];
        $config['merchId']	= !empty($lklpay_set['lkl_pay']['bank'])?$lklpay_set['lkl_pay']['bank']: $config['merchId'];
        $config['termId'] 	= !empty($lklpay_set['lkl_pay']['banknum'])?$lklpay_set['lkl_pay']['banknum']: $config['termId'];
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
        $newopenid = substr($openid, 7);
        $data['MAC'] 		= $mac;
        $data['FunCod'] 	= '8011'; //固定值
        $data['tradeType'] 	= 'JSAPI';
        $data['sub_appid'] 	= $config['appId']; //推荐关注公众号APPID
        $data['openId'] 	= $newopenid;
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
        if ($payinfo['responseCode'] == '000000') {
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
            $lklpay['msg']=$payinfo['message'];
            $lklpay['pay']=$pre_pay;
            $lklpay['info']=$lkl;
        }else {
            $lkl = array(
                'reqLogNo' => $order['ordersn'],
                'extRpData' => json_encode($payinfo),
            );
            pdo_update("ewei_shop_order",array('lklpayinfo'=>serialize($lkl)), array( "id"=>$orderid, "uniacid" => $uniacid ));
            $lklpay['success'] = false;
            $lklpay['msg']=$payinfo['message'];
        }
        //添加拉卡拉支付 end
        return app_json(array("order" => array("id" => $order["id"], "ordersn" => $order["ordersn"], "price" => $order["price"], "title" => $set["shop"]["name"] . "订单"), "credit" => $credit, "wechat" => $wechat, "alipay" => $alipay, "cash" => $cash, "lklpay" => $lklpay));
    }
    public function complete()
    {
        global $_W;
        global $_GPC;
        $orderid = intval($_GPC["id"]);
        $uniacid = $_W["uniacid"];
        $openid = $_W["openid"];
        if (empty($orderid)) {
            return app_error(AppError::$ParamsError);
        }
        $type = trim($_GPC["type"]);
        if (!in_array($type, array("wechat", "alipay", "credit", "cash", "lklpay"))) {
            return app_error(AppError::$OrderPayNoPayType);
        }
        if ($type == "alipay" && empty($_GPC["alidata"])) {
            return app_error(AppError::$ParamsError, "支付宝返回数据错误");
        }
        $set = m("common")->getSysset(array("shop", "pay"));
        $set["pay"]["weixin"] = !empty($set["pay"]["weixin_sub"]) ? 1 : $set["pay"]["weixin"];
        $set["pay"]["weixin_jie"] = !empty($set["pay"]["weixin_jie_sub"]) ? 1 : $set["pay"]["weixin_jie"];
        $member = m("member")->getMember($openid, true);
        $order = pdo_fetch("select * from " . tablename("ewei_shop_order") . " where id=:id and uniacid=:uniacid and openid=:openid limit 1", array(":id" => $orderid, ":uniacid" => $uniacid, ":openid" => $openid));
        if (empty($order)) {
            return app_error(AppError::$OrderNotFound);
        }
        if (1 <= $order["status"]) {
            return $this->success($orderid);
        }
        $log = pdo_fetch("SELECT * FROM " . tablename("core_paylog") . " WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1", array(":uniacid" => $uniacid, ":module" => "ewei_shopv2", ":tid" => $order["ordersn"]));
        if (empty($log)) {
            return app_error(AppError::$OrderPayFail);
        }
        $order_goods = pdo_fetchall("select og.id,g.title, og.goodsid,og.optionid,g.total as stock,og.total as buycount,g.status,g.deleted,g.maxbuy,g.usermaxbuy,g.istime,g.timestart,g.timeend,g.buylevels,g.buygroups,g.totalcnf from  " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_goods") . " g on og.goodsid = g.id " . " where og.orderid=:orderid and og.uniacid=:uniacid ", array(":uniacid" => $_W["uniacid"], ":orderid" => $orderid));
        foreach ($order_goods as $data) {
            if (empty($data["status"]) || !empty($data["deleted"])) {
                return app_error(AppError::$OrderPayFail, $data["title"] . "<br/> 已下架!");
            }
            $unit = empty($data["unit"]) ? "件" : $data["unit"];
            if (0 < $data["minbuy"] && $data["buycount"] < $data["minbuy"]) {
                return app_error(AppError::$OrderCreateMinBuyLimit, $data["title"] . "<br/> " . $data["min"] . $unit . "起售!");
            }
            if (0 < $data["maxbuy"] && $data["maxbuy"] < $data["buycount"]) {
                return app_error(AppError::$OrderCreateOneBuyLimit, $data["title"] . "<br/> 一次限购 " . $data["maxbuy"] . $unit . "!");
            }
            if (0 < $data["usermaxbuy"]) {
                $order_goodscount = pdo_fetchcolumn("select ifnull(sum(og.total),0)  from " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_order") . " o on og.orderid=o.id " . " where og.goodsid=:goodsid and  o.status>=1 and o.openid=:openid  and og.uniacid=:uniacid ", array(":goodsid" => $data["goodsid"], ":uniacid" => $uniacid, ":openid" => $openid));
                if ($data["usermaxbuy"] <= $order_goodscount) {
                    return app_error(AppError::$OrderCreateMaxBuyLimit, $data["title"] . "<br/> 最多限购 " . $data["usermaxbuy"] . $unit);
                }
            }
            if ($data["istime"] == 1) {
                if (time() < $data["timestart"]) {
                    return app_error(AppError::$OrderCreateTimeNotStart, $data["title"] . "<br/> 限购时间未到!");
                }
                if ($data["timeend"] < time()) {
                    return app_error(AppError::$OrderCreateTimeEnd, $data["title"] . "<br/> 限购时间已过!");
                }
            }
            if ($data["buylevels"] != "") {
                $buylevels = explode(",", $data["buylevels"]);
                if (!in_array($member["level"], $buylevels)) {
                    return app_error(AppError::$OrderCreateMemberLevelLimit, "您的会员等级无法购买<br/>" . $data["title"] . "!");
                }
            }
            if ($data["buygroups"] != "") {
                $buygroups = explode(",", $data["buygroups"]);
                if (!in_array($member["groupid"], $buygroups)) {
                    return app_error(AppError::$OrderCreateMemberGroupLimit, "您所在会员组无法购买<br/>" . $data["title"] . "!");
                }
            }
            if ($data["totalcnf"] == 1) {
                if (!empty($data["optionid"])) {
                    $option = pdo_fetch("select id,title,marketprice,goodssn,productsn,stock,`virtual` from " . tablename("ewei_shop_goods_option") . " where id=:id and goodsid=:goodsid and uniacid=:uniacid  limit 1", array(":uniacid" => $uniacid, ":goodsid" => $data["goodsid"], ":id" => $data["optionid"]));
                    if (!empty($option) && $option["stock"] != -1 && empty($option["stock"])) {
                        return app_error(AppError::$OrderCreateStockError, $data["title"] . "<br/>" . $option["title"] . " 库存不足!");
                    }
                } else {
                    if ($data["stock"] != -1 && empty($data["stock"])) {
                        return app_error(AppError::$OrderCreateStockError, $data["title"] . "<br/>" . $option["title"] . " 库存不足!");
                    }
                }
            }
        }
        if ($type == "cash") {
            if (empty($set["pay"]["cash"])) {
                return app_error(AppError::$OrderPayFail, "未开启货到付款");
            }
            m("order")->setOrderPayType($order["id"], 3);
            $ret = array();
            $ret["result"] = "success";
            $ret["type"] = "cash";
            $ret["from"] = "return";
            $ret["tid"] = $log["tid"];
            $ret["user"] = $order["openid"];
            $ret["fee"] = $order["price"];
            $ret["weid"] = $_W["uniacid"];
            $ret["uniacid"] = $_W["uniacid"];
            $pay_result = m("order")->payResult($ret);
            m("notice")->sendOrderMessage($orderid);
            return $this->success($orderid);
        }
        $ps = array();
        $ps["tid"] = $log["tid"];
        $ps["user"] = $openid;
        $ps["fee"] = $log["fee"];
        $ps["title"] = $log["title"];
        if ($type == "credit") {
            if (empty($set["pay"]["credit"]) && 0 < $ps["fee"]) {
                return app_error(AppError::$OrderPayFail, "未开启余额支付");
            }
            if ($ps["fee"] < 0) {
                return app_error(AppError::$OrderPayFail, "金额错误");
            }
            $credits = $this->member["credit2"];
            if ($credits < $ps["fee"]) {
                return app_error(AppError::$OrderPayFail, "余额不足,请充值");
            }
            $fee = floatval($ps["fee"]);
            $shopset = m("common")->getSysset("shop");
            $result = m("member")->setCredit($openid, "credit2", 0 - $fee, array($_W["member"]["uid"], $shopset["name"] . "APP 消费" . $fee));
            $this->creditpay_log($openid, $fee, $orderid);
            if (is_error($result)) {
                return app_error(AppError::$OrderPayFail, $result["message"]);
            }
            $record = array();
            $record["status"] = "1";
            $record["type"] = "cash";
            pdo_update("core_paylog", $record, array("plid" => $log["plid"]));
            $ret = array();
            $ret["result"] = "success";
            $ret["type"] = $log["type"];
            $ret["from"] = "return";
            $ret["tid"] = $log["tid"];
            $ret["user"] = $log["openid"];
            $ret["fee"] = $log["fee"];
            $ret["weid"] = $log["weid"];
            $ret["uniacid"] = $log["uniacid"];
            @session_start();
            $_SESSION[EWEI_SHOPV2_PREFIX . "_order_pay_complete"] = 1;
            m("order")->setOrderPayType($order["id"], 1);
            $pay_result = m("order")->payResult($ret);
            return $this->success($orderid);
        }
        if ($type == "wechat") {
            if (empty($set["pay"]["wxapp"]) && $this->iswxapp) {
                return app_error(AppError::$OrderPayFail, "未开启微信支付");
            }
            $ordersn = $order["ordersn"];
            if (!empty($order["ordersn2"])) {
                $ordersn .= "GJ" . sprintf("%02d", $order["ordersn2"]);
            }
            $payquery = $this->model->isWeixinPay($ordersn, $order["price"]);
            if (!is_error($payquery)) {
                $record = array();
                $record["status"] = "1";
                $record["type"] = "wechat";
                pdo_update("core_paylog", $record, array("plid" => $log["plid"]));
                m("order")->setOrderPayType($order["id"], 21);
                $ret = array();
                $ret["result"] = "success";
                $ret["type"] = "wechat";
                $ret["from"] = "return";
                $ret["tid"] = $log["tid"];
                $ret["user"] = $log["openid"];
                $ret["fee"] = $log["fee"];
                $ret["weid"] = $log["weid"];
                $ret["uniacid"] = $log["uniacid"];
                $ret["deduct"] = intval($_GPC["deduct"]) == 1;
                $pay_result = m("order")->payResult($ret);
                @session_start();
                $_SESSION[EWEI_SHOPV2_PREFIX . "_order_pay_complete"] = 1;
                pdo_update("ewei_shop_order", array("apppay" => 2), array("id" => $order["id"]));
                return $this->success($orderid);
            }
            return app_error(AppError::$OrderPayFail);
        }
        if ($type == "lklpay") {
            $ordersn = $order["ordersn"];
            if (!empty($order["ordersn2"])) {
                $ordersn .= "GJ" . sprintf("%02d", $order["ordersn2"]);
            }
            $record = array();
            $record["status"] = "1";
            $record["type"] = "lklpay";
            pdo_update("core_paylog", $record, array("plid" => $log["plid"]));
            m("order")->setOrderPayType($order["id"], 1000);
            $ret = array();
            $ret["result"] = "success";
            $ret["type"] = "lklpay";
            $ret["from"] = "return";
            $ret["tid"] = $log["tid"];
            $ret["user"] = $log["openid"];
            $ret["fee"] = $log["fee"];
            $ret["weid"] = $log["weid"];
            $ret["uniacid"] = $log["uniacid"];
            $ret["deduct"] = intval($_GPC["deduct"]) == 1;
            $pay_result = m("order")->payResult($ret);
            @session_start();
            $_SESSION[EWEI_SHOPV2_PREFIX . "_order_pay_complete"] = 1;
            pdo_update("ewei_shop_order", array("apppay" => 2), array("id" => $order["id"]));
            return $this->success($orderid);
            return app_error(AppError::$OrderPayFail);
        }
        if ($type == "alipay") {
            if (empty($set["pay"]["nativeapp_alipay"])) {
                return app_error(AppError::$OrderPayFail, "未开启支付宝支付");
            }
            $sec = m("common")->getSec();
            $sec = iunserializer($sec["sec"]);
            $public_key = $sec["nativeapp"]["alipay"]["public_key"];
            if (empty($public_key)) {
                return app_error(AppError::$OrderPayFail, "支付宝公钥为空");
            }
            $alidata = htmlspecialchars_decode($_GPC["alidata"]);
            $alidata = json_decode($alidata, true);
            $newalidata = $alidata["alipay_trade_app_pay_response"];
            $newalidata["sign_type"] = $alidata["sign_type"];
            $newalidata["sign"] = $alidata["sign"];
            $alisign = m("finance")->RSAVerify($newalidata, $public_key, false, true);
            if ($alisign) {
                $record = array();
                $record["status"] = "1";
                $record["type"] = "wechat";
                pdo_update("core_paylog", $record, array("plid" => $log["plid"]));
                $ret = array();
                $ret["result"] = "success";
                $ret["type"] = "alipay";
                $ret["from"] = "return";
                $ret["tid"] = $log["tid"];
                $ret["user"] = $log["openid"];
                $ret["fee"] = $log["fee"];
                $ret["weid"] = $log["weid"];
                $ret["uniacid"] = $log["uniacid"];
                $ret["deduct"] = intval($_GPC["deduct"]) == 1;
                m("order")->setOrderPayType($order["id"], 22);
                $pay_result = m("order")->payResult($ret);
                pdo_update("ewei_shop_order", array("apppay" => 2), array("id" => $order["id"]));
                return $this->success($order["id"]);
            }
        }
    }
    protected function success($orderid)
    {
        global $_W;
        global $_GPC;
        $openid = $_W["openid"];
        $uniacid = $_W["uniacid"];
        $member = m("member")->getMember($openid, true);
        if (empty($orderid)) {
            return app_error(AppError::$ParamsError);
        }
        $order = pdo_fetch("select * from " . tablename("ewei_shop_order") . " where id=:id and uniacid=:uniacid and openid=:openid limit 1", array(":id" => $orderid, ":uniacid" => $uniacid, ":openid" => $openid));
        $merchid = $order["merchid"];
        $goods = pdo_fetchall("select og.goodsid,og.price,g.title,g.thumb,og.total,g.credit,og.optionid,og.optionname as optiontitle,g.isverify,g.storeids from " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_goods") . " g on g.id=og.goodsid " . " where og.orderid=:orderid and og.uniacid=:uniacid ", array(":uniacid" => $uniacid, ":orderid" => $orderid));
        $address = false;
        if (!empty($order["addressid"])) {
            $address = iunserializer($order["address"]);
            if (!is_array($address)) {
                $address = pdo_fetch("select * from  " . tablename("ewei_shop_member_address") . " where id=:id limit 1", array(":id" => $order["addressid"]));
            }
        }
        $carrier = @iunserializer($order["carrier"]);
        if (!is_array($carrier) || empty($carrier)) {
            $carrier = false;
        }
        $store = false;
        if (!empty($order["storeid"])) {
            if (0 < $merchid) {
                $store = pdo_fetch("select * from  " . tablename("ewei_shop_merch_store") . " where id=:id limit 1", array(":id" => $order["storeid"]));
            } else {
                $store = pdo_fetch("select * from  " . tablename("ewei_shop_store") . " where id=:id limit 1", array(":id" => $order["storeid"]));
            }
        }
        $stores = false;
        if ($order["isverify"]) {
            $storeids = array();
            foreach ($goods as $g) {
                if (!empty($g["storeids"])) {
                    $storeids = array_merge(explode(",", $g["storeids"]), $storeids);
                }
            }
            if (empty($storeids)) {
                if (0 < $merchid) {
                    $stores = pdo_fetchall("select * from " . tablename("ewei_shop_merch_store") . " where  uniacid=:uniacid and merchid=:merchid and status=1 and `type` in (2,3)", array(":uniacid" => $_W["uniacid"], ":merchid" => $merchid));
                } else {
                    $stores = pdo_fetchall("select * from " . tablename("ewei_shop_store") . " where  uniacid=:uniacid and status=1 and `type` in (2,3)", array(":uniacid" => $_W["uniacid"]));
                }
            } else {
                if (0 < $merchid) {
                    $stores = pdo_fetchall("select * from " . tablename("ewei_shop_merch_store") . " where id in (" . implode(",", $storeids) . ") and uniacid=:uniacid and merchid=:merchid and status=1", array(":uniacid" => $_W["uniacid"], ":merchid" => $merchid));
                } else {
                    $stores = pdo_fetchall("select * from " . tablename("ewei_shop_store") . " where id in (" . implode(",", $storeids) . ") and uniacid=:uniacid and status=1", array(":uniacid" => $_W["uniacid"]));
                }
            }
        }
        $goodscircle = p("goodscircle");
        if ($goodscircle) {
            $goodscircle->importOrder($openid, 0, true);
            $goodscircle->importOrder($openid, $orderid);
        }
        $text = "";
        if (!empty($address)) {
            $text = "您的包裹整装待发";
        }
        if (!empty($order["dispatchtype"]) && empty($order["isverify"])) {
            $text = "您可以到您选择的自提点取货了";
        }
        if (!empty($order["isverify"])) {
            $text = "您可以到适用门店去使用了";
        }
        if (!empty($order["virtual"])) {
            $text = "您购买的商品已自动发货";
        }
        if (!empty($order["isvirtual"]) && empty($order["virtual"])) {
            if (!empty($order["isvirtualsend"])) {
                $text = "您购买的商品已自动发货";
            } else {
                $text = "您已经支付成功";
            }
        }
        if ($_GPC["result"] == "seckill_refund") {
            $icon = "e75a";
        } else {
            if (!empty($address)) {
                $icon = "e623";
            }
            if (!empty($order["dispatchtype"]) && empty($order["isverify"])) {
                $icon = "e7b9";
            }
            if (!empty($order["isverify"])) {
                $icon = "e7b9";
            }
            if (!empty($order["virtual"])) {
                $icon = "e7a1";
            }
            if (!empty($order["isvirtual"]) && empty($order["virtual"])) {
                if (!empty($order["isvirtualsend"])) {
                    $icon = "e7a1";
                } else {
                    $icon = "e601";
                }
            }
        }
        $seckill_color = "";
        if (0 < $order["seckilldiscountprice"]) {
            $where = " WHERE uniacid=:uniacid AND type = 5";
            $params = array(":uniacid" => $_W["uniacid"]);
            $page = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_wxapp_page") . $where . " LIMIT 1 ", $params);
            if (!empty($page)) {
                $data = base64_decode($page["data"]);
                $diydata = json_decode($data, true);
                $seckill_color = $diydata["page"]["seckill"]["color"];
            }
        }
        $result = array("order" => array("id" => $orderid, "isverify" => $order["isverify"], "virtual" => $order["virtual"], "isvirtual" => $order["isvirtual"], "isvirtualsend" => $order["isvirtualsend"], "virtualsend_info" => $order["virtualsend_info"], "virtual_str" => $order["virtual_str"], "status" => $order["paytype"] == 3 ? "订单提交支付" : "订单支付成功", "text" => $text, "price" => $order["price"]), "paytype" => $order["paytype"] == 3 ? "需到付" : "实付金额", "carrier" => $carrier, "address" => $address, "stores" => $stores, "store" => $store, "icon" => $icon, "seckill_color" => $seckill_color);
        if (!empty($order["virtual"]) && !empty($order["virtual_str"])) {
            $result["ordervirtual"] = m("order")->getOrderVirtual($order);
            $result["virtualtemp"] = pdo_fetch("SELECT linktext, linkurl FROM " . tablename("ewei_shop_virtual_type") . " WHERE id=:id AND uniacid=:uniacid LIMIT 1", array(":id" => $order["virtual"], ":uniacid" => $_W["uniacid"]));
        }
        $memberSetting = m("common")->getSysset("member");
        if ((int) $memberSetting["upgrade_condition"] === 1 || empty($memberSetting["upgrade_condition"])) {
            m("member")->upgradeLevel($order["openid"], $orderid);
        }
        $memberSetting = m("common")->getSysset("member");
        if ((int) $memberSetting["upgrade_condition"] === 2) {
            m("member")->upgradeLevel($order["openid"], $orderid, static::AFTER_PAY);
        }
        $memberSetting = m("common")->getSysset("member");
        if ((int) $memberSetting["upgrade_condition"] === 2) {
            m("member")->upgradeLevel($order["openid"], $orderid, static::AFTER_PAY);
        }
        return app_json($result);
    }
    protected function str($str)
    {
        $str = str_replace("\"", "", $str);
        $str = str_replace("'", "", $str);
        return $str;
    }
    protected function creditpay_log($openid = "", $fee = 0, $orderid = 0)
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W["uniacid"];
        if (empty($openid)) {
            return false;
        }
        if (empty($fee)) {
            return false;
        }
        if (empty($orderid)) {
            return false;
        }
        $order = pdo_fetch("select id,ordersn from " . tablename("ewei_shop_order") . " where id=:id AND uniacid=:uniacid LIMIT 1", array(":id" => $orderid, ":uniacid" => $uniacid));
        if (empty($order)) {
            return false;
        }
        $log_data = array("uniacid" => $uniacid, "openid" => $openid, "type" => 2, "logno" => $order["ordersn"], "title" => "小程序商城消费", "createtime" => TIMESTAMP, "status" => 1, "money" => 0 - $fee, "rechargetype" => "wxapp", "remark" => "小程序端余额支付");
        pdo_insert("ewei_shop_member_log", $log_data);
    }
    public function checkstock()
    {
        global $_W;
        global $_GPC;
        $orderid = intval($_GPC["id"]);
        $uniacid = $_W["uniacid"];
        $openid = $_W["openid"];
        if (empty($orderid)) {
            return app_error(AppError::$ParamsError);
        }
        $member = m("member")->getMember($openid, true);
        $order = pdo_fetch("select * from " . tablename("ewei_shop_order") . " where id=:id and uniacid=:uniacid and openid=:openid limit 1", array(":id" => $orderid, ":uniacid" => $uniacid, ":openid" => $openid));
        if (empty($order)) {
            return app_error(AppError::$OrderNotFound);
        }
        $order_goods = pdo_fetchall("select og.id,g.title, og.goodsid,og.optionid,g.total as stock,og.total as buycount,g.status,g.deleted,g.maxbuy,g.usermaxbuy,g.istime,g.timestart,g.timeend,g.buylevels,g.buygroups,g.totalcnf from  " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_goods") . " g on og.goodsid = g.id " . " where og.orderid=:orderid and og.uniacid=:uniacid ", array(":uniacid" => $_W["uniacid"], ":orderid" => $orderid));
        foreach ($order_goods as $data) {
            if (empty($data["status"]) || !empty($data["deleted"])) {
                return app_error(AppError::$OrderPayFail, $data["title"] . "<br/> 已下架!");
            }
            $unit = empty($data["unit"]) ? "件" : $data["unit"];
            if (0 < $data["minbuy"] && $data["buycount"] < $data["minbuy"]) {
                return app_error(AppError::$OrderCreateMinBuyLimit, $data["title"] . "<br/> " . $data["min"] . $unit . "起售!");
            }
            if (0 < $data["maxbuy"] && $data["maxbuy"] < $data["buycount"]) {
                return app_error(AppError::$OrderCreateOneBuyLimit, $data["title"] . "<br/> 一次限购 " . $data["maxbuy"] . $unit . "!");
            }
            if (0 < $data["usermaxbuy"]) {
                $order_goodscount = pdo_fetchcolumn("select ifnull(sum(og.total),0)  from " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_order") . " o on og.orderid=o.id " . " where og.goodsid=:goodsid and  o.status>=1 and o.openid=:openid  and og.uniacid=:uniacid ", array(":goodsid" => $data["goodsid"], ":uniacid" => $uniacid, ":openid" => $openid));
                if ($data["usermaxbuy"] <= $order_goodscount) {
                    return app_error(AppError::$OrderCreateMaxBuyLimit, $data["title"] . "<br/> 最多限购 " . $data["usermaxbuy"] . $unit);
                }
            }
            if ($data["istime"] == 1) {
                if (time() < $data["timestart"]) {
                    return app_error(AppError::$OrderCreateTimeNotStart, $data["title"] . "<br/> 限购时间未到!");
                }
                if ($data["timeend"] < time()) {
                    return app_error(AppError::$OrderCreateTimeEnd, $data["title"] . "<br/> 限购时间已过!");
                }
            }
            if ($data["buylevels"] != "") {
                $buylevels = explode(",", $data["buylevels"]);
                if (!in_array($member["level"], $buylevels)) {
                    return app_error(AppError::$OrderCreateMemberLevelLimit, "您的会员等级无法购买<br/>" . $data["title"] . "!");
                }
            }
            if ($data["buygroups"] != "") {
                $buygroups = explode(",", $data["buygroups"]);
                if (!in_array($member["groupid"], $buygroups)) {
                    return app_error(AppError::$OrderCreateMemberGroupLimit, "您所在会员组无法购买<br/>" . $data["title"] . "!");
                }
            }
            if ($data["totalcnf"] == 1) {
                if (!empty($data["optionid"])) {
                    $option = pdo_fetch("select id,title,marketprice,goodssn,productsn,stock,`virtual` from " . tablename("ewei_shop_goods_option") . " where id=:id and goodsid=:goodsid and uniacid=:uniacid  limit 1", array(":uniacid" => $uniacid, ":goodsid" => $data["goodsid"], ":id" => $data["optionid"]));
                    if (!empty($option) && $option["stock"] != -1 && empty($option["stock"])) {
                        return app_error(AppError::$OrderCreateStockError, $data["title"] . "<br/>" . $option["title"] . " 库存不足!");
                    }
                } else {
                    if ($data["stock"] != -1 && empty($data["stock"])) {
                        return app_error(AppError::$OrderCreateStockError, $data["title"] . "<br/>" . $option["title"] . " 库存不足!");
                    }
                }
            }
        }
        return app_json(1);
    }
    public function checkwxstock()
    {
        global $_W;
        global $_GPC;
        $orderid = intval($_GPC["id"]);
        $uniacid = $_W["uniacid"];
        $openid = $_W["openid"];
        if (empty($orderid)) {
            return false;
        }
        $order = pdo_fetch("select * from " . tablename("ewei_shop_order") . " where id=:id and uniacid=:uniacid and openid=:openid limit 1", array(":id" => $orderid, ":uniacid" => $uniacid, ":openid" => $openid));
        if (empty($order)) {
            return false;
        }
        $order_goods = pdo_fetchall("select og.id,g.title, og.goodsid,og.optionid,g.total as stock,og.total as buycount,g.status,g.deleted,g.maxbuy,g.usermaxbuy,g.istime,g.timestart,g.timeend,g.buylevels,g.buygroups,g.totalcnf from  " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_goods") . " g on og.goodsid = g.id " . " where og.orderid=:orderid and og.uniacid=:uniacid ", array(":uniacid" => $_W["uniacid"], ":orderid" => $orderid));
        foreach ($order_goods as $data) {
            if (empty($data["status"]) || !empty($data["deleted"])) {
                return false;
            }
            if ($data["totalcnf"] == 1) {
                if (!empty($data["optionid"])) {
                    $option = pdo_fetch("select id,title,marketprice,goodssn,productsn,stock,`virtual` from " . tablename("ewei_shop_goods_option") . " where id=:id and goodsid=:goodsid and uniacid=:uniacid  limit 1", array(":uniacid" => $uniacid, ":goodsid" => $data["goodsid"], ":id" => $data["optionid"]));
                    if (!empty($option) && $option["stock"] != -1 && empty($option["stock"])) {
                        return false;
                    }
                } else {
                    if ($data["stock"] != -1 && empty($data["stock"])) {
                        return false;
                    }
                }
            }
        }
        return true;
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
}

?>
