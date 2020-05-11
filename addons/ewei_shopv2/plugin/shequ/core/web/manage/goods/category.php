<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'shequ/core/inc/page_shequ.php';
class Category_EweiShopV2Page extends shequWebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$children = array();
		$category = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_category') . (' WHERE uniacid = \'' . $_W['uniacid'] . '\' ORDER BY parentid ASC, displayorder DESC'));
		$shequ_category = $this->getSet('shequ_category');

		if (!empty($shequ_category)) {
			foreach ($category as $index => $row) {
				if (array_key_exists($row['id'], $shequ_category)) {
					$category[$index]['enabled'] = $shequ_category[$row['id']];
				}
			}
		}

		foreach ($category as $index => $row) {
			if (!empty($row['parentid'])) {
				$children[$row['parentid']][] = $row;
				unset($category[$index]);
			}
		}

		$set = m('common')->getSysset(array('shop'));
		$shopset = $set['shop'];
		include $this->template();
	}

	public function enabled()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}

		$items = pdo_fetchall('SELECT id,enabled FROM ' . tablename('ewei_shop_category') . ' WHERE uniacid=' . $_W['uniacid']);
		$shequ_category = $this->getSet('shequ_category');

		if (empty($shequ_category)) {
			foreach ($items as $item) {
				if ($id == $item['id']) {
					$shequ_category[$item['id']] = intval($_GPC['enabled']);
				}
				else {
					$shequ_category[$item['id']] = $item['enabled'];
				}
			}
		}
		else {
			foreach ($items as $item) {
				if ($id == $item['id']) {
					$shequ_category[$item['id']] = intval($_GPC['enabled']);
				}
				else {
					if (!array_key_exists($item['id'], $shequ_category)) {
						$shequ_category[$item['id']] = $item['enabled'];
					}
				}
			}
		}

		$this->updateSet(array('shequ_category' => $shequ_category));
		show_json(1, array('url' => referer()));
	}

	public function query()
	{
		global $_W;
		global $_GPC;
		$kwd = trim($_GPC['keyword']);
		$params = array();
		$params[':uniacid'] = $_W['uniacid'];
		$condition = ' and enabled=1 and uniacid=:uniacid';

		if (!empty($kwd)) {
			$condition .= ' AND `name` LIKE :keyword';
			$params[':keyword'] = '%' . $kwd . '%';
		}

		$ds = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_category') . (' WHERE 1 ' . $condition . ' order by displayorder desc,id desc'), $params);
		$ds = set_medias($ds, array('thumb', 'advimg'));
		$shequ_category = $this->getSet('shequ_category');

		if (!empty($shequ_category)) {
			foreach ($ds as $i => $d) {
				$did = $d['id'];

				if (empty($shequ_category[$did])) {
					unset($ds[$i]);
				}
			}
		}

		if ($_GPC['suggest']) {
			exit(json_encode(array('value' => $ds)));
		}

		include $this->template();
	}
}

?>
