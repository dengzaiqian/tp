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
		if (mcv('perm.role') && !empty($_W['accounttotal'])) {
			header('location: ' . shequUrl('perm/role'));
			exit();
		}
		else {
			if (mcv('perm.user') && !empty($_W['accounttotal'])) {
				header('location: ' . shequUrl('perm/user'));
				exit();
			}
			else {
				if (mcv('perm.log')) {
					header('location: ' . shequUrl('perm/log'));
					exit();
				}
			}
		}
	}
}

?>
