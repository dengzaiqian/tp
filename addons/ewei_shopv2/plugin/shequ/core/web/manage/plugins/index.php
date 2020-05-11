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
		global $_GPC;
		$shequid = $_W['shequid'];
		$plugins_data = $this->model->getPluginList($shequid);
		$plugins_list = $plugins_data['plugins_list'];
		$plugins_all = $plugins_data['plugins_all'];
		$cashier = false;

		if (p('cashier')) {
			$sql = 'SELECT * FROM ' . tablename('ewei_shop_cashier_user') . ' WHERE uniacid=:uniacid AND shequid=:shequid AND deleted=0 AND status=1';
			$res = pdo_fetch($sql, array(':uniacid' => $_W['uniacid'], ':shequid' => $_W['shequid']));
			if (!empty($res) && time() < $res['lifetimeend']) {
				$cashier = $res;
				$auth_code = base64_encode(authcode($cashier['username'] . '|' . $cashier['password'] . '|' . $cashier['salt'], 'ENCODE', 'ewei_shopv2_cashier'));
				$url = $_W['siteroot'] . ('web/cashier.php?i=' . $_W['uniacid'] . '&r=login&auth_code=' . $auth_code);
			}
		}

		include $this->template();
	}
}

?>
