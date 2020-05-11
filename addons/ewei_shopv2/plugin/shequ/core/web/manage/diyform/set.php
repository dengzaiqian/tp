<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'shequ/core/inc/page_shequ.php';
class Set_EweiShopV2Page extends shequWebPage
// class Set_EweiShopV2Page extends PluginWebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		// $form_list = $this->model->getDiyformList();
		$form_list = p("diyform")->getDiyformList();

		if ($_W['ispost']) {
			ca('diyform.set.edit');
			$data = (is_array($_GPC['setdata']) ? $_GPC['setdata'] : array());
			$this->updateSet($data);
			plog('diyform.set.edit', '修改基本设置');
			show_json(1);
		}

		$set = $this->set;
		include $this->template();
	}
}

?>
