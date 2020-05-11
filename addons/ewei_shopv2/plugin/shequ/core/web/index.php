<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Index_EweiShopV2Page extends PluginWebPage
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
		// $order0 = $this->model->getshequOrderTotals(0);
		// $order3 = $this->model->getshequOrderTotals(3);
		$totals['totalmoney'] = 0;
		$totals['totalcount'] = 0;
		$totals['tmoney'] = 0;
		$totals['tcount'] = 0;
		show_json(1, $totals);
	}
}

?>
