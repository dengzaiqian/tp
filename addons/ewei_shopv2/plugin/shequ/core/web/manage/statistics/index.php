<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'shequ/core/inc/page_shequ.php';
class Index_EweiShopV2Page extends shequWebPage
{
	public function main()
	{
		if (mcv('statistics.sale.main')) {
			header('location: ' . shequUrl('statistics/sale'));
		}
		else if (mcv('statistics.sale_analysis.main')) {
			header('location: ' . shequUrl('statistics/sale_analysis'));
		}
		else if (mcv('statistics.order.main')) {
			header('location: ' . shequUrl('statistics/order'));
		}
		else if (mcv('statistics.sale_analysis.main')) {
			header('location: ' . shequUrl('statistics/sale_analysis'));
		}
		else if (mcv('statistics.goods.main')) {
			header('location: ' . shequUrl('statistics/goods'));
		}
		else if (mcv('statistics.goods_rank.main')) {
			header('location: ' . shequUrl('statistics/goods_rank'));
		}
		else if (mcv('statistics.goods_trans.main')) {
			header('location: ' . shequUrl('statistics/goods_trans'));
		}
		else if (mcv('statistics.member_cost.main')) {
			header('location: ' . shequUrl('statistics/member_cost'));
		}
		else if (mcv('statistics.member_increase.main')) {
			header('location: ' . shequUrl('statistics/member_increase'));
		}
		else {
			header('location: ' . shequUrl());
		}
	}
}

?>
