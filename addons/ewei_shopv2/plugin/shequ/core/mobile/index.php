<?php

if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Index_EweiShopV2Page extends PluginMobilePage
{
	public function main(){
		global $_W;
		global $_GPC;
		$title = '社区管理';
		$uniacid = $_W['uniacid'];
		$mid = intval($_GPC['mid']);
		$shequid = intval($_GPC['shequid']);	
		$default = $shequInfo = array();
		$shequInfo = pdo_fetchall('select u.* from '.tablename('ewei_shop_user_shequ_info').' i left join '.tablename('ewei_shop_shequ_user').' u on i.shequid=u.id where i.openid=:openid and i.uniacid=:uniacid and i.status=0 ORDER BY i.createtime desc limit 6',array(':openid'=>$_W['openid'],':uniacid'=>$_W['uniacid']));
		if($shequInfo){
			$default = $shequInfo[0];
			unset($shequInfo[0]);
		}
		include $this->template();
	}
	
	public function publishmessage()
	{
		global $_W;
		global $_GPC;
		include $this->template();
	}
	//选择小区
	public function select()
	{
		global $_W;
		global $_GPC;
		$title="选择小区";
		$aresList = pdo_fetchall('select `id`,`groupname`,`desc` from ims_ewei_shop_shequ_group where status=1');
		// var_dump($aresList);
		include $this->template();
	}
	public function ajaxshequuser()
	{
		global $_W;
		global $_GPC;
		$data = array();
		$pindex = max(1, intval($_GPC['page']));
		$psize = 30;
		$lat = floatval($_GPC['lat']);
		$lng = floatval($_GPC['lng']);
		$sorttype = $_GPC['sorttype'];
		$range = $_GPC['range'];

		$url = 'https://restapi.amap.com/v3/geocode/regeo?location=' . $_GPC['lng'] . ',' . $_GPC['lat'] . '&key=4bb7f548d37c962c18eda07176e7ebb6';
		$fileContents = file_get_contents($url);
		$fileData = json_decode($fileContents, true);
		// print_r($fileData);
		$address = '';
		if($fileData['status']==1 || $fileData['status']=='OK'){
			$address = $fileData['regeocode']['formatted_address'];
		}
		
		if (empty($range)) {
			$range = 10;
		}

		if (!empty($_GPC['keyword'])) {
			$data['like'] = array('shequname' => $_GPC['keyword']);
		}

		if (!empty($_GPC['cateid'])) {
			$data['cateid'] = $_GPC['cateid'];
		}

		$data = array_merge(
			$data, 
			array(
				'status' => 1, 
				'field' => 'id,uniacid,shequname,desc,logo,groupid,cateid,address,tel,lng,lat'));

		if (!empty($sorttype)) {
			$data['orderby'] = array('id' => 'desc');
		}

		$shequuser = $this->model->getshequ($data);

		if (!empty($shequuser)) {
			$data = array();
			$data = array_merge($data, array(
				'status'  => 1,
				'orderby' => array('displayorder' => 'desc', 'id' => 'asc')
			));
			$category = $this->model->getCategory($data);
			$cate_list = array();

			if (!empty($category)) {
				foreach ($category as $k => $v) {
					$cate_list[$v['id']] = $v;
				}
			}

			foreach ($shequuser as $k => $v) {
				if ($lat != 0 && $lng != 0 && !empty($v['lat']) && !empty($v['lng'])) {
					$lat_num = explode('.', $v['lat']);

					if (1 < sizeof($lat_num)) {
						$decimal = end($lat_num);
						$count = strlen($decimal);

						if ($count <= 6) {
							$gcj02 = $this->Convert_GCJ02_To_BD09($v['lat'], $v['lng']);
							$v['lat'] = $gcj02['lat'];
							$v['lng'] = $gcj02['lng'];
						}
					}

					$distance = m('util')->GetDistance($lat, $lng, $v['lat'], $v['lng'], 2);
					if (0 < $range && $range < $distance) {
						unset($shequuser[$k]);
						continue;
					}

					$shequuser[$k]['distance'] = $distance;
				}
				else {
					$shequuser[$k]['distance'] = 100000;
				}

				$shequuser[$k]['catename'] = $cate_list[$v['cateid']]['catename'];
				$shequuser[$k]['url'] = mobileUrl('shequ/map', array('shequid' => $v['id']));
				$shequuser[$k]['shequ_url'] = mobileUrl('shequ', array('shequid' => $v['id']));
				$shequuser[$k]['logo'] = tomedia($v['logo']);
			}
		}

		$total = count($shequuser);

		if ($sorttype == 0) {
			$shequuser = m('util')->multi_array_sort($shequuser, 'distance');
		}

		$start = ($pindex - 1) * $psize;

		if (!empty($shequuser)) {
			$shequuser = array_slice($shequuser, $start, $psize);
		}

		show_json(1, array('address'=>$address,'list' => $shequuser, 'total' => $total, 'pagesize' => $psize));
	}

	public function Convert_GCJ02_To_BD09($lat, $lng)
	{
		$x_pi = 3.1415926535897931 * 3000 / 180;
		$x = $lng;
		$y = $lat;
		$z = sqrt($x * $x + $y * $y) - 2.0000000000000002E-5 * sin($y * $x_pi);
		$theta = atan2($y, $x) - 3.0000000000000001E-6 * cos($x * $x_pi);
		$data['lng'] = $z * cos($theta) + 0.0064999999999999997;
		$data['lat'] = $z * sin($theta) + 0.0060000000000000001;
		return $data;
	}
	
	//选择完小区展示
	public function merchantDistribution(){
		$title='社区服务';
		global $_W;
		global $_GPC;
		$id = $_GPC['areaid'];
		$shequInfo = pdo_fetch('select * from '.tablename('ewei_shop_shequ_user').' where id=:id and uniacid=:uniacid and status=1',array(':id'=>$id,'uniacid'=>$_W['uniacid']));
		// print_r($shequInfo);
		$services = $merchs = array();
		$countService = 0;
		if($shequInfo){
			$this->bindAreaAndMember();
			// echo 'select `id`,`merchname` as title,`desc`,`logo` from '.tablename('ewei_shop_merch_user').' where FIND_IN_SET('.$id.',shequids) and uniacid=:uniacid and status=1 ORDER BY `displayorder` desc limit 7';
			$merchs = pdo_fetchall('select `id`,`merchname` as title,`desc`,`logo` from '.tablename('ewei_shop_merch_user').' where FIND_IN_SET('.$id.',shequids) and uniacid=:uniacid and status=1 ORDER BY `displayorder` desc limit 7' , array(':uniacid'=>$_W['uniacid']));
			if( count($merchs)<7 && count($merchs)>3 ){
				foreach($merchs as $k => $v){
					if($k>2){unset($merchs[$k]);}
				}
			}
			$title = $shequInfo['shequname'];
			$serviceIds = unserialize($shequInfo['service']);
			// print_r($serviceIds);
			if($serviceIds){
				$condition =' and ( ';
				foreach($serviceIds as $s){
					$condition .= 'id='.$s.' or ';
				}
				$condition = trim($condition,'or ');
				$condition .= ' )';
				$services = pdo_fetchall('select id,groupname from '.tablename('ewei_shop_shequ_group').' where uniacid=:uniacid and status=1 '.$condition , array(':uniacid'=>$_W['uniacid']));
				$countService = count($services);
				if($countService>3){
					foreach($services as $k => $v){
						if($k>2){ 
							unset($services[$k]); 
						}
					}
				}
				foreach($services as &$v){
					$v['massage'] =  pdo_fetchall('select `id`,`title`,`desc`,`logo`,`createtime` from '.tablename('ewei_shop_shequ_massage').' where FIND_IN_SET('.$v['id'].',service) and uniacid=:uniacid and status=1 and shequid=:shequid ORDER BY `displayorder` desc limit 4 ' , array(':uniacid'=>$_W['uniacid'],':shequid'=>$id));
				}unset($v);
			}
			// print_r($services);
		}
		include $this->template();
	}
	
	//绑定会员和小区
	public function bindAreaAndMember(){
		global $_W;
		global $_GPC;
		$id = $_GPC['areaid'];
		$openid = $_W['openid'];
		$issave = pdo_fetch('select * from '.tablename('ewei_shop_user_shequ_info').' where uniacid=:uniacid and openid=:openid and shequid=:shequid',array(':uniacid'=>$_W['uniacid'],':openid'=>$_W['openid'],':shequid'=>$id));
		if($issave){
			pdo_update('ewei_shop_user_shequ_info',array('id'=>$issave['id']),array('createtime'=>time()));
		}else{
			$insert = array(
				'uniacid'=>$_W['uniacid'],
				'openid'=>$openid,
				'createtime'=>time(),
				'shequid'=>$id,
				'status'=>0,
				'isdefault'=>0,
			);
			pdo_insert('ewei_shop_user_shequ_info', $insert);
			// $regid = pdo_insertid();
			// echo $regid;
		}
	}
	
	//更多商户
	public function shanghuMore()
	{
		$title='全部商户';
		global $_W;
		global $_GPC;
		$id = $_GPC['shequid'];
		$shequInfo = pdo_fetch('select * from '.tablename('ewei_shop_shequ_user').' where id=:id and uniacid=:uniacid and status=1',array(':id'=>$id,'uniacid'=>$_W['uniacid']));
		$title = $shequInfo['shequname'].'全部商户';
		$merchs = pdo_fetchall('select `id`,`merchname` as title,`desc`,`logo` from '.tablename('ewei_shop_merch_user').' where FIND_IN_SET('.$id.',shequids) and uniacid=:uniacid and status=1 ORDER BY `displayorder` desc ' , array(':uniacid'=>$_W['uniacid']));
		// print_r($merchs);
		include $this->template();
	}
	
	//获取所有商户
	public function ajaxshequmerch(){
		global $_W;
		global $_GPC;
		$id = $_GPC['shequid'];
		$keyword = $_GPC['keyword'];
		$condition = '';
		if($keyword!=''){
			$condition = " and merchname like '%".trim($keyword)."%' " ;
		}
		$merchs = pdo_fetchall('select `id`,`merchname` as title,`desc`,`logo` from '.tablename('ewei_shop_merch_user').' where FIND_IN_SET('.$id.',shequids) and uniacid=:uniacid and status=1 '. $condition .' ORDER BY `displayorder` desc ' , array(':uniacid'=>$_W['uniacid']));
		show_json(1, array('list' => set_medias($merchs, "logo")));
	}

	//获取更多公告消息
	public function storepageMore(){
		global $_W;
		global $_GPC;
		$id = $_GPC['areaid'];
		$shequInfo = pdo_fetch('select * from '.tablename('ewei_shop_shequ_user').' where id=:id and uniacid=:uniacid and status=1',array(':id'=>$id,'uniacid'=>$_W['uniacid']));
		// print_r($shequInfo);
		$services = $merchs = array();
		if($shequInfo){
			$title = $shequInfo['shequname'];
			$serviceIds = unserialize($shequInfo['service']);
			// print_r($serviceIds);
			if($serviceIds){
				$condition =' and ( ';
				foreach($serviceIds as $s){
					$condition .= 'id='.$s.' or ';
				}
				$condition = trim($condition,'or ');
				$condition .= ' )';
				$services = pdo_fetchall('select id,groupname from '.tablename('ewei_shop_shequ_group').' where uniacid=:uniacid and status=1 '.$condition , array(':uniacid'=>$_W['uniacid']));
				foreach($services as &$v){
					$v['massage'] =  pdo_fetchall('select `id`,`title`,`desc`,`logo`,`createtime` from '.tablename('ewei_shop_shequ_massage').' where FIND_IN_SET('.$v['id'].',service) and uniacid=:uniacid and status=1 and shequid=:shequid ORDER BY `displayorder` desc' , array(':uniacid'=>$_W['uniacid'],':shequid'=>$id));
				}unset($v);
			}			
		}	
		// print_r($services);
		include $this->template();		
	}

	//社区公告详情
	public function moredynamicsdetail(){
		global $_W;
		global $_GPC;
		$id = $_GPC['gid'];
		// $shequInfo = pdo_fetch('select * from '.tablename('ewei_shop_shequ_user').' where id=:id and uniacid=:uniacid and status=1',array(':id'=>$id,'uniacid'=>$_W['uniacid']));
		$gonggao = pdo_fetch('select * from '.tablename('ewei_shop_shequ_massage').' where id=:id and uniacid=:uniacid and status=1 ORDER BY `displayorder` desc',array(':id'=>$id,'uniacid'=>$_W['uniacid']));
		$title = $gonggao['title'];
		$this->addRecordViewMessage();
		include $this->template();
	}
	//添加浏览记录
	public function addRecordViewMessage(){
		global $_W;
		global $_GPC;
		$id = $_GPC['gid'];
		$openid = $_W['openid'];
		$issave = pdo_fetch('select * from '.tablename('ewei_shop_shequ_massage_record').' where messageid=:id and uniacid=:uniacid and openid=:openid',array(':id'=>$id,'uniacid'=>$_W['uniacid'],':openid'=>$openid));
		if(!$issave){
			pdo_insert('ewei_shop_shequ_massage_record',array('uniacid'=>$_W['uniacid'],'openid'=>$openid,'messageid'=>$id));
		}
	}
}

?>
