<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'shequ/core/inc/page_shequ.php';
class Cover_EweiShopV2Page extends shequWebPage
{
	public function main()
	{
		if (cv('shequ.cover.register')) {
			header('location: ' . webUrl('shequ/cover/register'));
		}
		else {
			if (cv('shequ.cover.shequlist')) {
				header('location: ' . webUrl('shequ/cover/shequlist'));
			}
		}
	}

	protected function _cover($key, $name, $url)
	{
		global $_W;
		global $_GPC;
		$rule = pdo_fetch('select * from ' . tablename('rule') . ' where shequid='.$_W['shequid'].' and uniacid=:uniacid and module=:module and name=:name limit 1', array(':uniacid' => $_W['uniacid'], ':module' => 'cover', ':name' => 'ewei_shopv2' . $name . '入口设置'));
		$keyword = false;
		$cover = false;

		if (!empty($rule)) {
			$keyword = pdo_fetch('select * from ' . tablename('rule_keyword') . ' where  shequid='.$_W['shequid'].' and  uniacid=:uniacid and rid=:rid limit 1', array(':uniacid' => $_W['uniacid'], ':rid' => $rule['id']));
			$cover = pdo_fetch('select * from ' . tablename('cover_reply') . ' where  shequid='.$_W['shequid'].' and  uniacid=:uniacid and rid=:rid limit 1', array(':uniacid' => $_W['uniacid'], ':rid' => $rule['id']));
		}

		if ($_W['ispost']) {
			// ca('shequ.cover.edit');
			$data = is_array($_GPC['cover']) ? $_GPC['cover'] : array();

			if (empty($data['keyword'])) {
				show_json(0, '请输入关键词!');
			}

			$keyword1 = m('common')->keyExist($data['keyword']);

			if (!empty($keyword1)) {
				if ($keyword1['name'] != 'ewei_shopv2' . $name . '入口设置') {
					show_json(0, '关键字已存在!');
				}
			}

			if (!empty($rule)) {
				pdo_delete('rule', array('id' => $rule['id'], 'uniacid' => $_W['uniacid'],'shequid'=>$_W['shequid']));
				pdo_delete('rule_keyword', array('rid' => $rule['id'], 'uniacid' => $_W['uniacid'],'shequid'=>$_W['shequid']));
				pdo_delete('cover_reply', array('rid' => $rule['id'], 'uniacid' => $_W['uniacid'],'shequid'=>$_W['shequid']));
			}

			$rule_data = array('shequid'=>$_W['shequid'],'uniacid' => $_W['uniacid'], 'name' => 'ewei_shopv2' . $name . '入口设置', 'module' => 'cover', 'displayorder' => 0, 'status' => intval($data['status']));
			pdo_insert('rule', $rule_data);
			$rid = pdo_insertid();
			$keyword_data = array('shequid'=>$_W['shequid'],'uniacid' => $_W['uniacid'], 'rid' => $rid, 'module' => 'cover', 'content' => trim($data['keyword']), 'type' => 1, 'displayorder' => 0, 'status' => intval($data['status']));
			pdo_insert('rule_keyword', $keyword_data);
			$cover_data = array('shequid'=>$_W['shequid'],'uniacid' => $_W['uniacid'], 'rid' => $rid, 'module' => 'ewei_shopv2', 'title' => trim($data['title']), 'description' => trim($data['desc']), 'thumb' => save_media($data['thumb']), 'url' => $url);
			pdo_insert('cover_reply', $cover_data);
			plog('shequ.cover.' . $key . '.edit', '修改入口设置');
			show_json(1);
		}

		return array('rule' => $rule, 'cover' => $cover, 'keyword' => $keyword, 'url' => $_W['siteroot'] . 'app/' . substr($url, 2), 'name' => $name, 'key' => $key);
	}

	public function register()
	{
		global $_W;
		global $_GPC;
		$cover = $this->_cover('register', '入驻申请', mobileUrl('shequ/register', array(), false));
		$url = $_W['siteroot'] . 'app/' . substr(mobileUrl('shequ/register', array('shequid'=>$_GPC['shequid']), false), 2);
        // $shequinfo = $this->getListUserOne($_W['shequid']);
        // $rule_data = array('uniacid' => $_W['uniacid'], 'name' => 'ewei_shopv2'.$shequinfo['shequname'].'入口设置', 'module' => 'cover', 'displayorder' => 0, 'status' => 1);
		// pdo_insert('rule', $rule_data);
		$qrcode = m('qrcode')->createQrcode($url);
		include $this->template('shequ/shequcover');
	}

	public function app()
	{
		global $_W;
		global $_GPC;
		$cover = $this->_cover('app', '商家微信管理端', mobileUrl('shequ', array(), false));
		include $this->template('shequ/cover');
	}

	public function shequlist()
	{
		global $_W;
		global $_GPC;
		$cover = $this->_cover('app', '社区导航', mobileUrl('shequ/list', array(), false));
		$url = $cover['url'];
		$qrcode = m('qrcode')->createQrcode($url);
		include $this->template('shequ/cover');
	}

	public function shequuser()
	{
		global $_W;
		global $_GPC;
		$cover = $this->_cover('app', '社区导航(含定位距离)', mobileUrl('shequ/list/shequuser', array(), false));
		$url = $cover['url'];
		$qrcode = m('qrcode')->createQrcode($url);
		include $this->template('shequ/cover');
	}
}
