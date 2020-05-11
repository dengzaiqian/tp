<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Message_EweiShopV2Page extends PluginMobilePage
{
	public function main()
	{
		global $_W;
		global $_GPC;
                
                $shequid=$_GPC['shequid'];      //选择的小区ID（用来查询全民社区内分类ID）

                $category = pdo_fetch('select id from '.tablename('ewei_shop_sns_category').' where enabled=1 and shequid=:shequid and uniacid=:uniacid ',array(':shequid'=>$shequid,'uniacid'=>$_W['uniacid']));
		$cid=$category['id'];             //全民社区内分类ID

		//全民社区板块列表
                $board = pdo_fetchall('select id,title from '.tablename('ewei_shop_sns_board').' where enabled=0 and status=1 and cid=:cid and uniacid=:uniacid ',array(':cid'=>$cid,'uniacid'=>$_W['uniacid']));

		include $this->template();
	}

        public function check_is_free(){
                global $_W;
		global $_GPC;
                $bid = intval($_GPC['bid']);
                $board=pdo_fetch('select id,is_free from ' . tablename('ewei_shop_sns_board') . ' where uniacid=:uniacid and id=:id  limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $bid));
                if(empty($board)){
                    show_json(0, '未找到版块!');
                }
                show_json(1, $board);
        }
        public function add(){
                global $_W;
		global $_GPC;
//                if (!$this->islogin) {
//			show_json(0, '未登录');
//		}
//                $_W['openid']="okFDu0XHJC2jCVPtCQb5oy85GIbM";
                $bid = intval($_GPC['type']);
                if (empty($bid)) {
			show_json(0, '参数错误');
		}

                $board=pdo_fetch('select * from ' . tablename('ewei_shop_sns_board') . ' where uniacid=:uniacid and id=:id  limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $bid));

		if (empty($board)) {
			show_json(0, '未找到版块!');
		}
                
                $reward_credit = intval($_GPC['reward_credit']);
                if($reward_credit<=0&&$board["is_free"]==0){
                    show_json(0, '请输入大于0的悬赏积分!');
                }
                //判断悬赏积分是否足够
                $credit1 = m('member')->getCredit($_W['openid'], 'credit1');

                if($reward_credit>$credit1){
                    show_json(0, "积分不足");
                }

		$member = m('member')->getMember($_W['openid']);
		$issupermanager = $this->model->isSuperManager();
		$ismanager = $this->model->isManager($board['id']);
		if (!$issupermanager && !$ismanager) {
			$check = $this->model->checksns($member, $board, true);

			if (is_error($check)) {
				show_json(0, $check['message']);
			}
		}

		$title = trim($_GPC['title']);
		$len = istrlen($title);

		if ($len < 3) {
			show_json(0, '标题最少3个汉字或字符哦~');
		}

		if (25 < $len) {
			show_json(0, '标题最多25个汉字或字符哦~');
		}

		$content = trim($_GPC['info']);
		$len = istrlen($content);

		if ($len < 3) {
			show_json(0, '内容最少3个汉字或字符哦~');
		}

		if (1000 < $len) {
			show_json(0, '内容最多1000个汉字或字符哦~');
		}

		$checked = 0;

		if ($ismanager) {
			$checked = $board['needcheckmanager'] ? 0 : 1;
		}
		else {
			$checked = $board['needcheck'] ? 0 : 1;
		}

		if ($issupermanager) {
			$checked = 1;
		}

		$imagesData = $this->getSet();

		if (is_array($_GPC['images'])) {
			$imgcount = count($_GPC['images']);
			if ($imagesData['imagesnum'] < $imgcount && 0 < $imagesData['imagesnum']) {
				show_json(0, '话题图片最多上传' . $imagesData['imagesnum'] . '张！');
                    }

			if (5 < $imgcount && $imagesData['imagesnum'] == 0) {
				show_json(0, '话题图片最多上传5张！');
                }
		}
                $image=$_GPC['images'];
//                $image=$_GPC['picurl'];
//                if(!empty($image)){
//                    if (is_array($image)) {
//                        $imgcount = count($image);
//                    }else{
//                        $image=array($image);
//                    }
//                }
		$time = time();
                if($reward_credit>0){
                    m('member')->setCredit($_W['openid'], 'credit1', 0 - $reward_credit, '发布话题扣除积分 ' . $reward_credit);
                }
		$data = array('uniacid' => $_W['uniacid'], 'bid' => $bid, 'openid' => $_W['openid'], 'createtime' => $time, 'avatar' => $member['avatar'], 'nickname' => $member['nickname'], 'replytime' => $time, 'title' => trim($_GPC['title']), 'content' => trim($content), 'images' => is_array($image) ? iserializer($image) : serialize(array()), 'checked' => $checked,'is_shequ'=>1,"reward_credit"=>$reward_credit);
		pdo_insert('ewei_shop_sns_post', $data);


		show_json(1, array('checked' => $checked));
        }

	public function category()
	{
		global $_W;
		global $_GPC;
		$data = array();

		if (!empty($_GPC['keyword'])) {
			$data['likecatename'] = $_GPC['keyword'];
		}

		$data = array_merge($data, array(
	'status'  => 1,
	'orderby' => array('displayorder' => 'desc', 'id' => 'asc')
	));
		$category = $this->model->getCategory($data);
		include $this->template();
	}

	public function shequuser()
	{
		global $_W;
		global $_GPC;
		$data = array();
		$data = array_merge($data, array(
	'status'  => 1,
	'orderby' => array('displayorder' => 'desc', 'id' => 'asc')
	));
		$category = $this->model->getCategory($data);

		foreach ($category as &$value) {
			$value['thumb'] = tomedia($value['thumb']);
		}

		unset($value);
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

		if (empty($range)) {
			$range = 10;
		}

		if (!empty($_GPC['keyword'])) {
			$data['like'] = array('shequname' => $_GPC['keyword']);
		}

		if (!empty($_GPC['cateid'])) {
			$data['cateid'] = $_GPC['cateid'];
		}

		$data = array_merge($data, array('status' => 1, 'field' => 'id,uniacid,shequname,desc,logo,groupid,cateid,address,tel,lng,lat'));

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

		show_json(1, array('list' => $shequuser, 'total' => $total, 'pagesize' => $psize));
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
        
        public function feedbackhelp(){
                global $_W;
		global $_GPC;
                
                $shequid=$_GPC['shequid'];
                if(empty($shequid)){
                    
}
                $category = pdo_fetch('select * from '.tablename('ewei_shop_sns_category').' where enabled=1 and shequid=:shequid and uniacid=:uniacid ',array(':shequid'=>$shequid,'uniacid'=>$_W['uniacid']));
                $cid=$category['id'];             //全民社区内分类ID
                //全民社区板块列表
                $board = pdo_fetchall('select id,title from '.tablename('ewei_shop_sns_board').' where enabled=0 and status=1 and cid=:cid and uniacid=:uniacid ',array(':cid'=>$cid,'uniacid'=>$_W['uniacid']));

                foreach ($board as $key => $value) {
                    $list[$key]=$value;
                    $huati=pdo_fetchall('select id,title,avatar,nickname,images,content from '.tablename('ewei_shop_sns_post').' where checked=1 and deleted=0 and bid=:bid and uniacid=:uniacid and deleted=0',array(':bid'=>$value['id'],'uniacid'=>$_W['uniacid']));
                    $list[$key]["post"]=$huati;
                    $list[$key]["len"]=  count($huati);
                }
                $list_len=count($board);
//                print_r($list);
                $data = m('common')->getPluginset('shequ');
                if(empty($data["scrollpic"])){
                    $data["scrollpic"]=array();
                }
                foreach ($data["scrollpic"] as $key => $value) {
                    $image=tomedia($value);
                    $scrollpic[]=array('img'=>$image,'a'=>"#");
                }
                if(count($scrollpic)<=0){
                    $scrollpic[]=array('img'=>"../addons/ewei_shopv2/plugin/shequ/static/images/rectangle6@2x.png",'a'=>"#");
                }
                $scrollpic=  json_encode($scrollpic);
                include $this->template();
        }
        
}

?>
