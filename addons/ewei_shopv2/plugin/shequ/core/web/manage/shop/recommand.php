<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'shequ/core/inc/page_shequ.php';
class Recommand_EweiShopV2Page extends shequWebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$shop = $this->getSet('shop');

		if ($_W['ispost']) {
			$shop['indexrecommands'] = $_GPC['goodsid'];
			$this->updateSet(array('shop' => $shop));
			mplog('shop.recommand', '修改首页推荐商品设置');
			show_json(1);
		}

		$goodsids = isset($shop['indexrecommands']) ? implode(',', $shop['indexrecommands']) : '';
		$goods = false;

		if (!empty($goodsids)) {
			$goods = pdo_fetchall('select id,title,thumb from ' . tablename('ewei_shop_goods') . (' where id in (' . $goodsids . ') and status=1 and deleted=0 and uniacid=' . $_W['uniacid'] . ' and shequid=' . $_W['shequid'] . ' order by instr(\'' . $goodsids . '\',id)'));
		}

		$goodsstyle = $shop['goodsstyle'];
		include $this->template();
	}

	public function setstyle()
	{
		global $_W;
		global $_GPC;
		$shop = $this->getSet('shop');
		$shop['goodsstyle'] = intval($_GPC['goodsstyle']);
		$this->updateSet(array('shop' => $shop));
		mplog('shop.recommand', '修改手机端商品组样式');
		show_json(1, $shop);
	}
}

?>
