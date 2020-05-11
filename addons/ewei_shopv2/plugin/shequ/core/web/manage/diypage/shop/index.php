<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'shequ/core/inc/page_shequ.php';
class Index_EweiShopV2Page extends shequWebPage
{
	public function main()
	{
		header('location: ' . webUrl('diypage/shop/page'));
	}

	public function page()
	{
		global $_W;
		global $_GPC;
		$data = $this->model->getSet('diypage');
		$diypage_plugin = p('diypage');
		$home_list = $diypage_plugin->getPageList('allpage', ' and type=2 ');
		$home_list = $home_list['list'];
		$member_list = $diypage_plugin->getPageList('allpage', ' and type=3 ');
		$member_list = $member_list['list'];
		$commission_list = $diypage_plugin->getPageList('allpage', ' and type=4 ');
		$commission_list = $commission_list['list'];
		$detail_list = $diypage_plugin->getPageList('allpage', ' and type=5 ');
		$detail_list = $detail_list['list'];

		if ($_W['ispost']) {
			$diypage = $_GPC['page'];
			$data['page'] = $diypage;
			$this->model->updateSet(array('diypage' => $data));
			$plog = '保存商城 自定义页面: ';

			foreach ($data['page'] as $key => $val) {
				$plog .= $key . '=>' . $val . '; ';
			}

			plog('diypage.shop.page.save', $plog);
			show_json(1);
		}

		include $this->template();
	}

	public function menu()
	{
		global $_W;
		global $_GPC;
		$list = pdo_fetchall('select id, name from ' . tablename('ewei_shop_diypage_menu') . ' where shequ=:shequ and uniacid=:uniacid order by id desc', array(':shequ' => intval($_W['shequid']), ':uniacid' => $_W['uniacid']));
		$pluginList = array('creditshop' => 0, 'commission' => 1, 'groups' => 0, 'mr' => 0, 'sns' => 0, 'sign' => 0, 'seckill' => 0, 'threen' => 0);
                //判断是否拥有积分商城权限
                $shequ_user=pdo_fetch('select pluginset from '. tablename('ewei_shop_shequ_user') .' where id=:id and uniacid=:uniacid',array(':id' => intval($_W['shequid']), ':uniacid' => $_W['uniacid']));
                $pluginset=iunserializer($shequ_user['pluginset']);
                if($pluginset['creditshop']['close']==0){
                    $pluginList['creditshop']=1;
                }
                $data = $this->model->getSet('diypage');

		if ($_W['ispost']) {
			$diymenu = $_GPC['menu'];
			$data['menu'] = $diymenu;
			$this->model->updateSet(array('diypage' => $data));
			$plog = '保存商城 自定义菜单: ';

			foreach ($data['menu'] as $key => $val) {
				$plog .= $key . '=>' . $val . '; ';
			}

			plog('diypage.shop.menu.save', $plog);
			show_json(1);
		}

		include $this->template();
	}

	public function followbar()
	{
		global $_W;
		global $_GPC;
		$diypagedata = $this->model->getSet('diypage');
		$diyfollowbar = $diypagedata['followbar'];
		$logo = $_W['shopset']['shop']['logo'];

		if ($_W['ispost']) {
			$data = $_GPC['data'];

			if (!empty($data)) {
				$diypagedata['followbar'] = $data;
				$this->model->updateSet(array('diypage' => $diypagedata));
				plog('diypage.shop.followbar.main', '编辑自定义关注条');
				show_json(1);
			}

			show_json(0, '数据错误，请刷新页面重试！');
		}

		include $this->template();
	}

	public function layer()
	{
		global $_W;
		global $_GPC;
		$diypagedata = $this->model->getSet('diypage');
		$diylayer = $diypagedata['layer'];

		if ($_W['ispost']) {
			$data = $_GPC['data'];

			if (!empty($data)) {
				$diypagedata['layer'] = $data;
				$this->model->updateSet(array('diypage' => $diypagedata));
				plog('diypage.shop.layer.main', '编辑自定义悬浮按钮');
				show_json(1);
			}

			show_json(0, '数据错误，请刷新页面重试！');
		}

		include $this->template();
	}

	public function gotop()
	{
		global $_W;
		global $_GPC;
		$diypagedata = $this->model->getSet('diypage');
		$diygotop = $diypagedata['gotop'];

		if ($_W['ispost']) {
			$data = $_GPC['data'];

			if (!empty($data)) {
				$diypagedata['gotop'] = $data;
				$this->model->updateSet(array('diypage' => $diypagedata));
				plog('diypage.shop.layer.main', '编辑自定义悬浮按钮');
				show_json(1);
			}

			show_json(0, '数据错误，请刷新页面重试！');
		}

		include $this->template();
	}

	public function danmu()
	{
		global $_W;
		global $_GPC;
		$diypagedata = $this->model->getSet('diypage');
		$danmu = $diypagedata['danmu'];

		if ($_W['ispost']) {
			$data = $_GPC['data'];

			if (!empty($data)) {
				$diypagedata['danmu'] = $data;
				$this->model->updateSet(array('diypage' => $diypagedata));
				plog('diypage.shop.danmu.main', '编辑自定义悬浮按钮');
				show_json(1);
			}

			show_json(0, '数据错误，请刷新页面重试！');
		}

		include $this->template();
	}
}

?>
