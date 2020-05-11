<?php
class List_EweiShopV2Page extends PluginMobilePage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$category = $this->model->getCategory(array(
	'isrecommand' => 1,
	'status'      => 1,
	'orderby'     => array('displayorder' => 'desc', 'id' => 'asc')
	));
		$shequuser = $this->model->getshequ(array(
	'isrecommand' => 1,
	'status'      => 1,
	'field'       => 'id,uniacid,shequname,desc,logo,groupid,cateid',
	'orderby'     => array('id' => 'asc')
	));
		$category_swipe = $this->model->getCategorySwipe(array(
	'status'  => 1,
	'orderby' => array('displayorder' => 'desc', 'id' => 'asc')
	));
		include $this->template();
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

}

?>
