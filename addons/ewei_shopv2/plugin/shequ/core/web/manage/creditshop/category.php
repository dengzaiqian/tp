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
		$list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_creditshop_category') . ("\r\n\t\tWHERE uniacid = '" . $_W['uniacid'] . '\' ORDER BY displayorder,id DESC'));
		$shequ_category = $this->getSet('shequ_creditshop_category');

		if (!empty($shequ_category)) {
			foreach ($list as $index => $row) {
				if (array_key_exists($row['id'], $shequ_category)) {
					$list[$index]['enabled'] = $shequ_category[$row['id']];
				}
			}
		}

		$set = m('common')->getSysset(array('creditshop'));
		$shopset = $set['creditshop'];
		include $this->template();
	}

	public function displayorder()
	{
		global $_GPC;
		global $_W;
		$id = intval($_GPC['id']);
		$displayorder = intval($_GPC['value']);
		$item = pdo_fetchall('SELECT id,name FROM ' . tablename('ewei_shop_creditshop_category') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		if (!empty($item)) {
			pdo_update('ewei_shop_creditshop_category', array('displayorder' => $displayorder), array('id' => $id));
			plog('creditshop.category.edit', '修改分类排序 ID: ' . $item['id'] . ' 标题: ' . $item['name'] . ' 排序: ' . $displayorder . ' ');
		}

		show_json(1);
	}

	public function enabled()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}

		$items = pdo_fetchall('SELECT id,name FROM ' . tablename('ewei_shop_creditshop_category') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);
		$shequ_category = $this->getSet('shequ_creditshop_category');

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

		$this->updateSet(array('shequ_creditshop_category' => $shequ_category));
		show_json(1, array('url' => referer()));
	}

	public function query()
	{
		global $_W;
		global $_GPC;
		$keyword = trim($_GPC['keyword']);
		$condition = ' and enabled=1 and uniacid=:uniacid';
		$params = array();
		$params[':uniacid'] = $_W['uniacid'];

		if (!empty($keyword)) {
			$condition .= ' AND `name` LIKE :keyword';
			$params[':keyword'] = '%' . $keyword . '%';
		}

		$list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_creditshop_category') . (' WHERE 1 ' . $condition . ' order by displayorder desc,id desc'), $params);

		if (!empty($list)) {
			$list = set_medias($list, array('thumb', 'advimg'));
		}

		$shequ_category = $this->getSet('shequ_creditshop_category');

		if (!empty($shequ_category)) {
			foreach ($list as $i => $d) {
				$did = $d['id'];

				if (empty($shequ_category[$did])) {
					unset($list[$i]);
				}
			}
		}

		include $this->template();
	}
}

?>
