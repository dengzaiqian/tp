<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'shequ/core/inc/page_shequ.php';
class Index_EweiShopV2Page extends shequWebPage
{
	public function main()
	{
		global $_W;
		include $this->template();
	}

	public function ajaxuser()
	{
		global $_GPC;
		global $_W;
		$totals = $this->model->getUserTotals();
		$order0 = $this->model->getshequOrderTotals(0);
		$order3 = $this->model->getshequOrderTotals(3);
		$totals['totalmoney'] = $order0['totalmoney'];
		$totals['totalcount'] = $order0['totalcount'];
		$totals['tmoney'] = $order3['totalmoney'];
		$totals['tcount'] = $order3['totalcount'];
		show_json(1, $totals);
	}
}


?>
