<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Debug_EweiShopV2Page extends WebPage 
{
	public function main() 
	{
		global $_W;
		global $_GPC;
	}
	public function createTeam($op) 
	{
		$member = m('member')->getMember($op);
		if (empty($member['isheads']) || empty($member['headsstatus'])) 
		{
			show_json(1, '您还不是队长');
		}
		$data = pdo_fetchall('select id from ' . tablename('ewei_shop_commission_relation') . ' where pid = :pid', array(':pid' => $member['id']));
		if (!(empty($data))) 
		{
			$ids = array();
			foreach ($data as $k => $v ) 
			{
				$ids[] = $v['id'];
			}
			if (!(empty($ids))) 
			{
				pdo_update('ewei_shop_member', array('headsid' => $member['id']), array('id' => $ids));
			}
		}
	}
}
?>