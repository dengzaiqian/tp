<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'shequ/core/inc/page_shequ.php';
class Banner_EweiShopV2Page extends shequWebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$shequid = $_W['shequid'];
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' and uniacid=:uniacid and shequid=:shequid';
		$params = array(':uniacid' => $_W['uniacid'], ':shequid' => $shequid);

		if ($_GPC['enabled'] != '') {
			$condition .= ' and enabled=' . intval($_GPC['enabled']);
		}

		if (!empty($_GPC['keyword'])) {
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$condition .= ' and bannername  like :keyword';
			$params[':keyword'] = '%' . $_GPC['keyword'] . '%';
		}

		$list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_shequ_banner') . (' WHERE 1 ' . $condition . '  ORDER BY displayorder DESC limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
		$total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_shequ_banner') . (' WHERE 1 ' . $condition), $params);
		$pager = pagination2($total, $pindex, $psize);
		$shop = $this->model->getSet('shop', $shequid);
		$bannerswipe = $shop['bannerswipe'];
		include $this->template();
	}

	public function add()
	{
		$this->post();
	}

	public function edit()
	{
		$this->post();
	}

	protected function post()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if ($_W['ispost']) {
			$data = array('uniacid' => $_W['uniacid'], 'bannername' => trim($_GPC['bannername']), 'link' => trim($_GPC['link']), 'enabled' => intval($_GPC['enabled']), 'displayorder' => intval($_GPC['displayorder']), 'thumb' => save_media($_GPC['thumb']), 'shequid' => $_W['shequid']);

			if (!empty($id)) {
				pdo_update('ewei_shop_shequ_banner', $data, array('id' => $id));
				mplog('shop.banner.edit', '修改广告 ID: ' . $id);
			}
			else {
				pdo_insert('ewei_shop_shequ_banner', $data);
				$id = pdo_insertid();
				mplog('shop.banner.add', '添加广告 ID: ' . $id);
			}

			show_json(1, array('url' => shequUrl('shop/banner')));
		}

		$item = pdo_fetch('select * from ' . tablename('ewei_shop_shequ_banner') . ' where id=:id and uniacid=:uniacid and shequid=:shequid limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid'], ':shequid' => $_W['shequid']));
		include $this->template();
	}

	public function delete()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}

		$items = pdo_fetchall('SELECT id,bannername FROM ' . tablename('ewei_shop_shequ_banner') . (' WHERE id in( ' . $id . ' ) AND uniacid=' . $_W['uniacid'] . ' and shequid=' . $_W['shequid']));

		foreach ($items as $item) {
			pdo_delete('ewei_shop_shequ_banner', array('id' => $item['id']));
			mplog('shop.banner.delete', '删除广告 ID: ' . $item['id'] . ' 标题: ' . $item['bannername'] . ' ');
		}

		show_json(1, array('url' => referer()));
	}

	public function displayorder()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$displayorder = intval($_GPC['value']);
		$item = pdo_fetchall('SELECT id,bannername FROM ' . tablename('ewei_shop_shequ_banner') . (' WHERE id in( ' . $id . ' ) AND uniacid=' . $_W['uniacid'] . ' and shequid=' . $_W['shequid']));

		if (!empty($item)) {
			pdo_update('ewei_shop_shequ_banner', array('displayorder' => $displayorder), array('id' => $id));
			mplog('shop.banner.edit', '修改广告排序 ID: ' . $item['id'] . ' 标题: ' . $item['bannername'] . ' 排序: ' . $displayorder . ' ');
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

		$items = pdo_fetchall('SELECT id,bannername FROM ' . tablename('ewei_shop_shequ_banner') . (' WHERE id in( ' . $id . ' ) AND uniacid=' . $_W['uniacid'] . ' and shequid=' . $_W['shequid']));

		foreach ($items as $item) {
			pdo_update('ewei_shop_shequ_banner', array('enabled' => intval($_GPC['enabled'])), array('id' => $item['id']));
			mplog('shop.banner.edit', '修改广告状态<br/>ID: ' . $item['id'] . '<br/>标题: ' . $item['bannername'] . '<br/>状态: ' . $_GPC['enabled'] == 1 ? '显示' : '隐藏');
		}

		show_json(1, array('url' => referer()));
	}

	public function setswipe()
	{
		global $_W;
		global $_GPC;
		$shequid = $_W['shequid'];
		$shop = $this->model->getSet('shop', $shequid);
		$shop['bannerswipe'] = intval($_GPC['bannerswipe']);
		$this->model->updateSet(array('shop' => $shop), $shequid);
		mplog('shop.banner.edit', '修改手机端广告轮播');
		show_json(1);
	}
}

?>
