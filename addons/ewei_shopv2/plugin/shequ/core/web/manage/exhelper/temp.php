<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'shequ/core/inc/page_shequ.php';
class Temp_EweiShopV2Page extends shequWebPage
{
	public function main()
	{
		header('location: ' . shequUrl('exhelper/index'));
	}

	protected function tempData($type)
	{
		global $_W;
		global $_GPC;
		$shequid = $_W['shequid'];
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' uniacid = :uniacid and type=:type and shequid=:shequid';
		$params = array(':uniacid' => $_W['uniacid'], ':type' => $type, ':shequid' => $shequid);

		if (!empty($_GPC['keyword'])) {
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$condition .= ' AND expressname LIKE :expressname';
			$params[':expressname'] = '%' . trim($_GPC['keyword']) . '%';
		}

		$sql = 'SELECT id,expressname,expresscom,isdefault FROM ' . tablename('ewei_shop_exhelper_express') . (' where  1 and ' . $condition . ' ORDER BY isdefault desc, id DESC LIMIT ') . ($pindex - 1) * $psize . ',' . $psize;
		$list = pdo_fetchall($sql, $params);
		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_shop_exhelper_express') . (' where 1 and ' . $condition), $params);
		$pager = pagination($total, $pindex, $psize);
		return array('list' => $list, 'total' => $total, 'pager' => $pager, 'type' => $type);
	}

	public function invoice()
	{
		global $_W;
		global $_GPC;
		$data = $this->tempData(1);
		extract($data);
		include $this->template('exhelper/temp/index');
	}

	public function express()
	{
		global $_W;
		global $_GPC;
		$data = $this->tempData(2);
		extract($data);
		include $this->template('exhelper/temp/index');
	}

	public function add1()
	{
		$this->post();
	}

	public function add2()
	{
		$this->post();
	}

	public function edit1()
	{
		$this->post();
	}

	public function edit2()
	{
		$this->post();
	}

	public function post()
	{
		global $_W;
		global $_GPC;
		$shequid = $_W['shequid'];
		$id = intval($_GPC['id']);
		$type = trim($_GPC['type']);

		if ($type == 'temp.invoice') {
			$type = 1;
		}
		else {
			if ($type == 'temp.express') {
				$type = 2;
			}
		}

		if (!empty($id)) {
			$item = pdo_fetch('select * from ' . tablename('ewei_shop_exhelper_express') . ' where id=:id and type=:type and uniacid=:uniacid and shequid=:shequid limit 1', array(':id' => $id, ':type' => $type, ':uniacid' => $_W['uniacid'], ':shequid' => $shequid));
		}

		include $this->template('exhelper/temp/post');
	}

	public function delete()
	{
	}

	public function setdefault()
	{
		global $_W;
		global $_GPC;
		$shequid = $_W['shequid'];
		$type = intval($_GPC['type']);
		$id = intval($_GPC['id']);
		if (!empty($type) && !empty($id)) {
			$item = pdo_fetch('SELECT id,expressname,type FROM ' . tablename('ewei_shop_exhelper_express') . ' WHERE id=:id and type=:type AND uniacid=:uniacid and shequid=:shequid', array(':id' => $id, ':type' => $type, ':uniacid' => $_W['uniacid'], ':shequid' => $shequid));

			if (!empty($item)) {
				pdo_update('ewei_shop_exhelper_express', array('isdefault' => 0), array('type' => $type, 'uniacid' => $_W['uniacid'], 'shequid' => $shequid));
				pdo_update('ewei_shop_exhelper_express', array('isdefault' => 1), array('id' => $id));
			}
		}

		show_json(1);
	}
}

?>
