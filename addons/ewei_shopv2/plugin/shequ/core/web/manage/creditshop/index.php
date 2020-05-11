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
		$this->model->CheckPlugin('creditshop');

		if (mcv('creditshop')) {
			header('location: ' . webUrl('creditshop/goods'));
		}

		include $this->template('creditshop/goods');
	}
}

?>
