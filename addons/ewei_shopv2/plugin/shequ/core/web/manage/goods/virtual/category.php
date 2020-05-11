<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'shequ/core/inc/page_shequ.php';
class Category_EweiShopV2Page extends shequWebPage
{
	public function __construct($_com = 'virtual')
	{
		parent::__construct($_com);
	}

	public function main()
	{
		global $_W;
		global $_GPC;

		if (!empty($_W['ispost'])) {
			mca('goods.virtual.category.edit');

			if (!empty($_GPC['catname']['new'])) {
				foreach ($_GPC['catname']['new'] as $k => $v) {
					$_GPC['catname']['new_' . $k] = $v;
				}
			}

			unset($_GPC['catname']['new']);

			foreach ($_GPC['catname'] as $id => $catname) {
				$catname = trim($catname);

				if (empty($catname)) {
					continue;
				}

				if (strpos($id, 'new_') !== false) {
					pdo_insert('ewei_shop_virtual_category', array('name' => $catname, 'uniacid' => $_W['uniacid'], 'shequid' => $_W['shequid']));
					$insert_id = pdo_insertid();
					mplog('goods.virtual.category.add', '添加分类 ID: ' . $insert_id);
				}
				else {
					pdo_update('ewei_shop_virtual_category', array('name' => $catname), array('id' => $id, 'uniacid' => $_W['uniacid'], 'shequid' => $_W['shequid']));
					mplog('goods.virtual.category.edit', '修改分类 ID: ' . $id);
				}
			}

			mplog('goods.virtual.category.edit', '批量修改分类');
			show_json(1, array('url' => shequUrl('goods/virtual/category')));
		}

		$list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_virtual_category') . (' WHERE uniacid = \'' . $_W['uniacid'] . '\' and shequid = \'' . $_W['shequid'] . '\' ORDER BY id DESC'));
		include $this->template();
	}

	public function delete()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$item = pdo_fetch('SELECT id,name FROM ' . tablename('ewei_shop_virtual_category') . (' WHERE id = \'' . $id . '\' AND uniacid=') . $_W['uniacid'] . ' AND shequid=' . $_W['shequid']);

		if (empty($item)) {
			$this->message('抱歉，分类不存在或是已经被删除！', shequUrl('goods/virtual/category', array('op' => 'display')), 'error');
		}

		pdo_delete('ewei_shop_virtual_category', array('id' => $id));
		mplog('goods.virtual.category.delete', '删除分类 ID: ' . $id . ' 标题: ' . $item['name'] . ' ');
		show_json(1, array('url' => shequUrl('goods/virtual/category', array('op' => 'display'))));
	}
}

?>
