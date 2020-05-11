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
				}
			}
		}
		
}
else 
{
	$get = $_GET;
}

file_put_contents('./notice.txt',json_encode($get,true));
exit('over');
?>