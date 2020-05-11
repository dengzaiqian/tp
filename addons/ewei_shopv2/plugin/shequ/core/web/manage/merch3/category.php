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
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' and shequid='.$_W['shequid'].' and uniacid=:uniacid';
		$params = array(':uniacid' => $_W['uniacid']);

		if ($_GPC['status'] != '') {
			$condition .= ' and status=' . intval($_GPC['status']);
		}

		if (!empty($_GPC['keyword'])) {
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$condition .= ' and catename  like :keyword';
			$params[':keyword'] = '%' . $_GPC['keyword'] . '%';
		}

		$list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_shequ_category') . (' WHERE 1 ' . $condition . '  ORDER BY displayorder desc, id DESC limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
		$total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_shequ_category') . (' WHERE 1 ' . $condition), $params);
		$pager = pagination2($total, $pindex, $psize);
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
			$data = array(
                'uniacid' => $_W['uniacid'], 
                'catename' => trim($_GPC['catename']),
                'status' => intval($_GPC['status']),
                'displayorder' => intval($_GPC['displayorder']),
                'thumb' => save_media($_GPC['thumb']),
                'isrecommand' => intval($_GPC['isrecommand']),
                'shequid'=>$_W['shequid']);

			if (!empty($id)) {
				pdo_update('ewei_shop_shequ_category', $data, array('id' => $id));
				plog('shequ.category.edit', '修改社区分类 ID: ' . $id);
			}
			else {
				$data['createtime'] = time();
				pdo_insert('ewei_shop_shequ_category', $data);
				$id = pdo_insertid();
				plog('shequ.category.add', '添加社区分类 ID: ' . $id);
			}

			show_json(1, array('url' => webUrl('shequ/category')));
		}

		$item = pdo_fetch('select * from ' . tablename('ewei_shop_shequ_category') . ' where shequid='.$_W['shequid'].' and id=:id and uniacid=:uniacid limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
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

		$items = pdo_fetchall('SELECT id,catename FROM ' . tablename('ewei_shop_shequ_category') . (' WHERE shequid='.$_W['shequid'].' and  id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_delete('ewei_shop_shequ_category', array('id' => $item['id']));
			plog('shequ.category.delete', '删除社区分类 ID: ' . $item['id'] . ' 标题: ' . $item['catename'] . ' ');
		}

		show_json(1, array('url' => referer()));
	}

	public function status()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}

		$items = pdo_fetchall('SELECT id,catename FROM ' . tablename('ewei_shop_shequ_category') . (' WHERE  shequid='.$_W['shequid'].' and  id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_update('ewei_shop_shequ_category', array('status' => intval($_GPC['status'])), array('id' => $item['id']));
			plog('shequ.category.edit', '修改社区分类状态<br/>ID: ' . $item['id'] . '<br/>分类名称: ' . $item['catename'] . '<br/>状态: ' . $_GPC['status'] == 1 ? '显示' : '隐藏');
		}

		show_json(1, array('url' => referer()));
	}

	public function swipe()
	{
		global $_W;
		global $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' shequid='.$_W['shequid'].' and uniacid=:uniacid';
		$params = array(':uniacid' => $_W['uniacid']);

		if ($_GPC['status'] != '') {
			$condition .= ' and status=' . intval($_GPC['status']);
		}

		if (!empty($_GPC['keyword'])) {
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$condition .= ' and title  like :keyword';
			$params[':keyword'] = '%' . $_GPC['keyword'] . '%';
		}

		$list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_shequ_category_swipe') . (' WHERE 1 ' . $condition . '  ORDER BY displayorder desc, id DESC limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
		$total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_shequ_category_swipe') . (' WHERE 1 ' . $condition), $params);
		$pager = pagination2($total, $pindex, $psize);
		include $this->template();
	}

	public function add_swipe()
	{
		$this->post_swipe();
	}

	public function edit_swipe()
	{
		$this->post_swipe();
	}

	protected function post_swipe()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if ($_W['ispost']) {
			$data = array(
                'uniacid' => $_W['uniacid'],
                'title' => trim($_GPC['title']),
                'status' => intval($_GPC['status']),
                'displayorder' => intval($_GPC['displayorder']),
                'thumb' => save_media($_GPC['thumb']),
                'shequid'=>$_W['shequid']
                );

			if (!empty($id)) {
				pdo_update('ewei_shop_shequ_category_swipe', $data, array('id' => $id));
				plog('shequ.category.swipe.edit', '添加社区分类幻灯 ID: ' . $id);
			}
			else {
				$data['createtime'] = time();
				pdo_insert('ewei_shop_shequ_category_swipe', $data);
				$id = pdo_insertid();
				plog('shequ.category.swipe.add', '添加社区分类幻灯 ID: ' . $id);
			}

			show_json(1, array('url' => webUrl('shequ/category/swipe')));
		}

		$item = pdo_fetch('select * from ' . tablename('ewei_shop_shequ_category_swipe') . ' where  shequid='.$_W['shequid'].' and  id=:id and uniacid=:uniacid limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
		include $this->template();
	}

	public function delete_swipe()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}

		$items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_shequ_category_swipe') . (' WHERE  shequid='.$_W['shequid'].' and  id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_delete('ewei_shop_shequ_category_swipe', array('id' => $item['id']));
			plog('shequ.category.delete', '删除社区幻灯 ID: ' . $item['id'] . ' 标题: ' . $item['catename'] . ' ');
		}

		show_json(1, array('url' => referer()));
	}

	public function status_swipe()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}

		$items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_shequ_category_swipe') . (' WHERE shequid='.$_W['shequid'].' and  id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_update('ewei_shop_shequ_category_swipe', array('status' => intval($_GPC['status'])), array('id' => $item['id']));
			plog('shequ.category.edit', '修改社区幻灯状态<br/>ID: ' . $item['id'] . '<br/>分类名称: ' . $item['catename'] . '<br/>状态: ' . $_GPC['status'] == 1 ? '显示' : '隐藏');
		}

		show_json(1, array('url' => referer()));
	}

	public function query()
	{
		global $_W;
		global $_GPC;
		$kwd = trim($_GPC['keyword']);
		$params = array();
		$params[':uniacid'] = $_W['uniacid'];
		$condition = ' and shequid='.$_W['shequid'].' and uniacid=:uniacid AND status=1';

		if (!empty($kwd)) {
			$condition .= ' AND `catename` LIKE :keyword';
			$params[':keyword'] = '%' . $kwd . '%';
		}

		$list = pdo_fetchall('SELECT id, catename, thumb FROM ' . tablename('ewei_shop_shequ_category') . (' WHERE ' . $condition . ' order by id asc'), $params);
		include $this->template();
		exit();
	}
}

?>