<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'shequ/core/inc/page_shequ.php';
class Sale_analysis_EweiShopV2Page extends shequWebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		function sale_analysis_count($sql)
		{
			$c = pdo_fetchcolumn($sql);
			return intval($c);
		}
		$member_count = sale_analysis_count('select count(*) from ' . tablename('ewei_shop_member') . (' where uniacid=' . $_W['uniacid'] . ' and  openid in ( SELECT distinct openid from ') . tablename('ewei_shop_order') . ('   WHERE uniacid = \'' . $_W['uniacid'] . '\' and shequid=\'' . $_W['shequid'] . '\'  )'));
		$orderprice = sale_analysis_count('SELECT sum(price) FROM ' . tablename('ewei_shop_order') . (' WHERE  status>=1 and uniacid = \'' . $_W['uniacid'] . '\' and shequid=\'' . $_W['shequid'] . '\' '));
		$ordercount = sale_analysis_count('SELECT count(*) FROM ' . tablename('ewei_shop_order') . (' WHERE status>=1 and uniacid = \'' . $_W['uniacid'] . '\' and shequid=\'' . $_W['shequid'] . '\''));
		$viewcount = sale_analysis_count('SELECT sum(viewcount) FROM ' . tablename('ewei_shop_goods') . (' WHERE uniacid = \'' . $_W['uniacid'] . '\' and shequid=\'' . $_W['shequid'] . '\''));
		$member_buycount = sale_analysis_count('select count(*) from ' . tablename('ewei_shop_member') . (' where uniacid=' . $_W['uniacid'] . ' and  openid in ( SELECT distinct openid from ') . tablename('ewei_shop_order') . ('   WHERE uniacid = \'' . $_W['uniacid'] . '\' and shequid=\'' . $_W['shequid'] . '\' and status>=1 )'));
		include $this->template('statistics/sale_analysis');
	}
}

?>
