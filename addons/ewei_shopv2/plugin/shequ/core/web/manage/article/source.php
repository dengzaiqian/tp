<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'shequ/core/inc/page_shequ.php';
class Source_EweiShopV2Page extends shequWebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$article_sys = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_article_sys') . ' WHERE uniacid=:uniacid limit 1 ', array(':uniacid' => $_W['uniacid']));

		if (empty($article_sys['article_source'])) {
			$sourceUrl = '../addons/ewei_shopv2/plugin/article/static/images';
		}
		else {
			$sourceUrl = $article_sys['article_source'];
			$endstr = substr($sourceUrl, -1);

			if ($endstr == '/') {
				$sourceUrl = rtrim($sourceUrl, '/');
			}
		}

		include $this->template();
	}
}

?>
