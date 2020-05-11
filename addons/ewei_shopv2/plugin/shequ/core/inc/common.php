<?php
function shequUrl($do = '', $query = NULL, $full = false)
{
	global $_W;
	global $_GPC;
	$dos = explode('/', trim($do));
	$routes = array();
	$routes[] = $dos[0];

	if (isset($dos[1])) {
		$routes[] = $dos[1];
	}

	if (isset($dos[2])) {
		$routes[] = $dos[2];
	}

	if (isset($dos[3])) {
		$routes[] = $dos[3];
	}

	$r = implode('.', $routes);

	if (!is_array($query)) {
		$query = array();
	}

	if (!empty($r)) {
		$query = array_merge(array('r' => $r), $query);
	}

	$query = array_merge(array('do' => 'web'), $query);
	$query = array_merge(array('m' => 'ewei_shopv2'), $query);
	return str_replace('./index.php', './shequant.php', wurl('site/entry', $query));
}

function mce($permtype = '', $item = NULL)
{
	$perm = plugin_run('shequ::check_edit', $permtype, $item);
	return $perm;
}

function mcp($plugin = '')
{
	return true;
}

function mcv($permtypes = '')
{
	$perm = plugin_run('shequ::check_perm', $permtypes);
	return $perm;
}

function mplog($type = '', $op = '')
{
	plugin_run('shequ::log', $type, $op);
}

function mca($permtypes = '')
{
}

function mp($plugin = '')
{
	$plugin = p($plugin);

	if (!$plugin) {
		return false;
	}

	if (mcp($plugin)) {
		return $plugin;
	}

	return false;
}

function mcom($com = '')
{
	return true;
}

global $_W;
$routes = explode('.', $_W['routes']);
$GLOBALS['_W']['tab'] = isset($routes[2]) ? $routes[2] : '';
$uniacid = intval($_GPC['__uniacid']);
$session = $_SESSION['__shequ_uniacid'];

if (!empty($session)) {
	$uniacid = $session;
}

if ($_W['routes'] != 'shequ.manage.login') {
	$session_key = '__shequ_' . $uniacid . '_session';
	$session = json_decode(base64_decode($_GPC[$session_key]), true);

	if (is_array($session)) {
		$account = pdo_fetch('select * from ' . tablename('ewei_shop_shequ_account') . ' where id=:id limit 1', array(':id' => $session['id']));
		if (!is_array($account) || $session['hash'] != md5($account['pwd'] . $account['salt'])) {
			isetcookie($session_key, false, -100);
			header('location: ' . shequurl('login'));
			exit();
		}

		$GLOBALS['_W']['uniaccount'] = $account;
	}
	else {
		isetcookie($session_key, false, -100);
		header('location: ' . shequurl('login'));
		exit();
	}
}

$GLOBALS['_W']['uniacid'] = $uniacid;
$GLOBALS['_W']['shequid'] = $session['shequid'];
$GLOBALS['_W']['shequuid'] = $session['id'];
$GLOBALS['_W']['shequusername'] = $session['username'];
$GLOBALS['_W']['shequisfounder'] = $session['isfounder'];
$shequ_user = pdo_fetch('select u.*,g.groupname,g.goodschecked,g.commissionchecked,g.changepricechecked,g.finishchecked from ' . tablename('ewei_shop_shequ_user') . ' u left join ' . tablename('ewei_shop_shequ_group') . ' g on u.groupid=g.id where u.id=:id limit 1', array(':id' => $session['shequid']));
$GLOBALS['_W']['shequ_user'] = $shequ_user;
$GLOBALS['_W']['shequ_username'] = $shequ_user['shequname'];
$GLOBALS['_W']['accounttotal'] = $shequ_user['accounttotal'];
unset($shequ_user);
$_W['attachurl'] = $_W['attachurl_local'] = $_W['siteroot'] . $_W['config']['upload']['attachdir'] . '/';

if (!empty($_W['setting']['remote'][$_W['uniacid']]['type'])) {
	$_W['setting']['remote'] = $_W['setting']['remote'][$_W['uniacid']];
}

if (!empty($_W['setting']['remote']['type'])) {
	if ($_W['setting']['remote']['type'] == ATTACH_FTP) {
		$_W['attachurl'] = $_W['attachurl_remote'] = $_W['setting']['remote']['ftp']['url'] . '/';
	}
	else if ($_W['setting']['remote']['type'] == ATTACH_OSS) {
		$_W['attachurl'] = $_W['attachurl_remote'] = $_W['setting']['remote']['alioss']['url'] . '/';
	}
	else if ($_W['setting']['remote']['type'] == ATTACH_QINIU) {
		$_W['attachurl'] = $_W['attachurl_remote'] = $_W['setting']['remote']['qiniu']['url'] . '/';
	}
	else {
		if ($_W['setting']['remote']['type'] == ATTACH_COS) {
			$_W['attachurl'] = $_W['attachurl_remote'] = $_W['setting']['remote']['cos']['url'] . '/';
		}
	}
}

load()->func('tpl');

?>
