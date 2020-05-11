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
		$this->model->CheckPlugin('diyform');
        
        if (mcv('diyform')) {
			header('location: ' . webUrl('diyform/temp'));
		}

		include $this->template('diyform/temp');
        
		// if (cv('diyform.temp')) {
			// header('location: ' . webUrl('diyform/temp'));
		// }
		// else if (cv('diyform.category')) {
			// header('location: ' . webUrl('diyform/category'));
		// }
		// else if (cv('diyform.set')) {
			// header('location: ' . webUrl('diyform/set'));
		// }
		// else {
			// header('location: ' . webUrl());
		// }
	}
}

?>
