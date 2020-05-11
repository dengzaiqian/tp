<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Merch_EweiShopV2Page extends PluginMobilePage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$uniacid = $_W['uniacid'];
		$mid = intval($_GPC['mid']);
		$merchid = intval($_GPC['merchid']);
		if (!$merchid || empty($this->merch_user)) {
			$this->message('没有找到此商户', '', 'error');
		}
                
                $myid = m("member")->getMid();
                $this->doshare($merchid, $mid, $myid) ;

		$this->diypage('home');
		$index_cache = $this->getpage($merchid);

		if (!empty($mid)) {
			$index_cache = preg_replace_callback('/href=[\\\'"]?([^\\\'" ]+).*?[\\\'"]/', function($matches) use($mid) {
				$preg = $matches[1];

				if (strexists($preg, 'mid=')) {
					return 'href=\'' . $preg . '\'';
				}

				if (!strexists($preg, 'javascript')) {
					$preg = preg_replace('/(&|\\?)mid=[\\d+]/', '', $preg);

					if (strexists($preg, '?')) {
						$newpreg = $preg . ('&mid=' . $mid);
					}
					else {
						$newpreg = $preg . ('?mid=' . $mid);
					}

					return 'href=\'' . $newpreg . '\'';
				}
			}, $index_cache);
		}

		$set = $this->model->getListUserOne($merchid);

		if (!empty($set)) {
			$_W['shopshare'] = array('title' => $set['merchname'], 'imgUrl' => tomedia($set['logo']), 'desc' => $set['desc'], 'link' => mobileUrl('merch', array('merchid' => $merchid), true));

			if (p('commission')) {
				$set = p('commission')->getSet();

				if (!empty($set['level'])) {
					$member = m('member')->getMember($_W['openid']);
					if (!empty($member) && $member['status'] == 1 && $member['isagent'] == 1) {
						$_W['shopshare']['link'] = mobileUrl('merch', array('merchid' => $merchid, 'mid' => $member['id']), true);
					}
					else {
						if (!empty($mid)) {
							$_W['shopshare']['link'] = mobileUrl('merch', array('merchid' => $merchid, 'mid' => $mid), true);
						}
					}
				}
			}
		}
		include $this->template('index');
	}

	public function shanghuMore()
	{
		global $_W;
		global $_GPC;
		$set = $this->model->getListUserOne(intval($_GPC['merchid']));

		if ($set['status'] == 1) {
			$args = array('page' => intval($_GPC['page']), 'pagesize' => 6, 'isrecommand' => 1, 'order' => 'displayorder desc,createtime desc', 'by' => '', 'merchid' => intval($_GPC['merchid']));
			$recommand = m('goods')->getList($args);
		}

		include($this->template('shequ/merch/storepageMore'));
	}

	public function storepage()
	{
		global $_W;
		global $_GPC;
		include($this->template());
	}

	public function getpage($merchid)
	{
		global $_W;
		global $_GPC;
		$uniacid = $_W['uniacid'];
		$merchid = intval($merchid);
		$defaults = array(
			'adv'    => array('text' => '幻灯片', 'visible' => 1),
			'search' => array('text' => '搜索栏', 'visible' => 1),
			'nav'    => array('text' => '导航栏', 'visible' => 1),
			'notice' => array('text' => '公告栏', 'visible' => 1),
			'cube'   => array('text' => '魔方栏', 'visible' => 1),
			'banner' => array('text' => '广告栏', 'visible' => 1),
			'goods'  => array('text' => '推荐栏', 'visible' => 1)
			);
		$shop = p('merch')->getSet('shop', $merchid);
		$sorts = isset($shop['indexsort']) ? $shop['indexsort'] : $defaults;
		$sorts['recommand'] = array('text' => '系统推荐', 'visible' => 1);
		$advs = pdo_fetchall('select id,advname,link,thumb from ' . tablename('ewei_shop_merch_adv') . ' where uniacid=:uniacid and merchid=:merchid and enabled=1 order by displayorder desc', array(':uniacid' => $uniacid, ':merchid' => $merchid));
		$navs = pdo_fetchall('select id,navname,url,icon from ' . tablename('ewei_shop_merch_nav') . ' where uniacid=:uniacid and merchid=:merchid and status=1 order by displayorder desc', array(':uniacid' => $uniacid, ':merchid' => $merchid));
		$cubes = is_array($shop['cubes']) ? $shop['cubes'] : array();
		$banners = pdo_fetchall('select id,bannername,link,thumb from ' . tablename('ewei_shop_merch_banner') . ' where uniacid=:uniacid and merchid=:merchid and enabled=1 order by displayorder desc', array(':uniacid' => $uniacid, ':merchid' => $merchid));
		$bannerswipe = $shop['bannerswipe'];

		if (!empty($shop['indexrecommands'])) {
			$goodids = implode(',', $shop['indexrecommands']);

			if (!empty($goodids)) {
				$indexrecommands = pdo_fetchall('select id, title, thumb, marketprice, productprice, minprice, total from ' . tablename('ewei_shop_goods') . (' where id in( ' . $goodids . ' ) and uniacid=:uniacid and merchid=:merchid and status=1 order by instr(\'' . $goodids . '\',id),merchdisplayorder desc'), array(':uniacid' => $uniacid, ':merchid' => $merchid));
			}
		}

		$goodsstyle = $shop['goodsstyle'];
		$notices = pdo_fetchall('select id, title, link, thumb from ' . tablename('ewei_shop_merch_notice') . ' where uniacid=:uniacid and merchid=:merchid and status=1 order by displayorder desc limit 5', array(':uniacid' => $uniacid, ':merchid' => $merchid));
		ob_start();
		ob_implicit_flush(false);
		require $this->template('index_tpl');
		return ob_get_clean();
	}
        
        public function doshare($merchid, $shareid, $myid) 
	{
		global $_W;
		global $_GPC;
//		$myid = m("member")->getMid();
//		$shareid = intval($_GPC["mid"]);
//		$merchid = intval($_GPC["merchid"]);
                
		if (empty($merchid) || empty($shareid) || empty($myid) || $shareid == $myid) {
                        return NULL;
                }

                $merch = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_merch_user') . ' WHERE id=:merchid and status=1 and uniacid=:uniacid limit 1 ', array(':merchid' => $merchid, ':uniacid' => $_W['uniacid']));

                if (empty($merch)) {
                        return NULL;
                }

                $profile = m('member')->getMember($shareid);
                $myinfo = m('member')->getMember($myid);
                if (empty($myinfo) || empty($profile)) {
                        return NULL;
                }

                $shopset = $_W['shopset'];
//                $givecredit = intval($article['article_rule_credit']);
//                $givemoney = floatval($article['article_rule_money']);
                $my_click = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_shop_merch_user') . ' WHERE merchid=:merchid and click_user=:click_user and uniacid=:uniacid ', array(':merchid' => $merchid['id'], ':click_user' => $myid, ':uniacid' => $_W['uniacid']));
//
                if (!empty($my_click)) {
                        $givecredit = intval($merch['merch_rule_credit2']);
//                        $givemoney = floatval($article['article_rule_money2']);
                }else{
                    $givecredit = intval($merch['merch_rule_credit2']);
                }

                if (!empty($merch['merch_hasendtime']) && $merch['merch_endtime'] < time()) {
                        return NULL;
                }

                $readtime = $merch['merch_readtime'];

                if ($readtime <= 0) {
                        $readtime = 4;
                }

                $clicktime = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_shop_merch_share') . ' WHERE merchid=:merchid and share_user=:share_user and click_user=:click_user and uniacid=:uniacid ', array(':merchid' => $merch['id'], ':share_user' => $shareid, ':click_user' => $myid, ':uniacid' => $_W['uniacid']));

                if ($readtime <= $clicktime) {
                        return NULL;
                }

                $all_click = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_shop_merch_share') . ' WHERE merchid=:merchid and share_user=:share_user and uniacid=:uniacid ', array(':merchid' => $merch['id'], ':share_user' => $shareid, ':uniacid' => $_W['uniacid']));

                if ($merch['merch_rule_allnum'] <= $all_click) {
                        $givecredit = 0;
//                        $givemoney = 0;
                }
                else {
                        $day_start = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
                        $day_end = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
                        $day_click = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_shop_merch_share') . ' WHERE merchid=:merchid and share_user=:share_user and click_date>:day_start and click_date<:day_end and uniacid=:uniacid ', array(':merchid' => $merch['id'], ':share_user' => $shareid, ':day_start' => $day_start, ':day_end' => $day_end, ':uniacid' => $_W['uniacid']));

                        if ($merch['merch_rule_daynum'] <= $day_click) {
                                $givecredit = 0;
//                                $givemoney = 0;
                        }
                }

                $toto = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_shop_merch_share') . ' WHERE merchid=:merchid and share_user=:click_user and click_user=:share_user and uniacid=:uniacid ', array(':merchid' => $merch['id'], ':share_user' => $shareid, ':click_user' => $myid, ':uniacid' => $_W['uniacid']));

                if (!empty($toto)) {
                        return NULL;
                }

                if (0 < $merch['merch_rule_credittotal'] ) {
                        $creditlast = 0;
//                        $moneylast = 0;
                        $firstreads = pdo_fetchcolumn('select count(distinct click_user) from ' . tablename('ewei_shop_merch_share') . ' where merchid=:merchid and uniacid=:uniacid limit 1', array(':merchid' => $merch['id'], ':uniacid' => $_W['uniacid']));
                        $allreads = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_merch_share') . ' where merchid=:merchid and uniacid=:uniacid limit 1', array(':merchid' => $merch['id'], ':uniacid' => $_W['uniacid']));
                        $secreads = $allreads - $firstreads;

                        if (0 < $merch['merch_rule_credittotal']) {
                                if (!empty($merch['merch_advance'])) {
//                                        $creditlast = $merch['merch_rule_credittotal'] - ($firstreads + ($merch['article_virtualadd'] ? $merch['article_readnum_v'] : 0)) * $merch['article_rule_creditm'] - $secreads * $article['article_rule_creditm2'];
                                }
                                else {
                                        $creditout = pdo_fetchcolumn('select sum(add_credit) from ' . tablename('ewei_shop_merch_share') . ' where merchid=:merchid and uniacid=:uniacid limit 1', array(':merchid' => $merch['id'], ':uniacid' => $_W['uniacid']));
                                        $creditlast = $merch['merch_rule_credittotal'] - $creditout;
                                }
                        }

//                        if (0 < $article['article_rule_moneytotal']) {
//                                if (!empty($article['article_advance'])) {
//                                        $moneylast = $article['article_rule_moneytotal'] - ($firstreads + ($article['article_virtualadd'] ? $article['article_readnum_v'] : 0)) * $article['article_rule_moneym'] - $secreads * $article['article_rule_moneym2'];
//                                }
//                                else {
//                                        $moneyout = pdo_fetchcolumn('select sum(add_money) from ' . tablename('ewei_shop_article_share') . ' where aid=:aid and uniacid=:uniacid limit 1', array(':aid' => $article['id'], ':uniacid' => $_W['uniacid']));
//                                        $moneylast = $article['article_rule_moneytotal'] - $moneyout;
//                                }
//                        }

                        $creditlast <= 0 && ($creditlast = 0);
//                        $moneylast <= 0 && ($moneylast = 0);

                        if ($creditlast <= 0) {
                                $givecredit = 0;
                        }

//                        if ($moneylast <= 0) {
//                                $givemoney = 0;
//                        }
                }
                
                
                //查询多商户管理者积分
                $merch_user=m('member')->getMember($merch['openid']);
                if(empty($merch_user['openid'])||$merch_user['credit1']<=0){
                    return NULL;
                }
                
                $insert = array('merchid' => $merch['id'], 'share_user' => $shareid, 'click_user' => $myid, 'click_date' => time(), 'add_credit' => $givecredit, 'uniacid' => $_W['uniacid']);
                pdo_insert('ewei_shop_merch_share', $insert);

                if (0 < $givecredit) {
                    pdo_update("mc_members", array( "credit3" => 3 ), array( "uid" => 2));
                        m('member')->setCredit($profile['openid'], 'credit1', $givecredit, array(0, $shopset['name'] . ' 分享多商户奖励积分'));
                        m('member')->setCredit($merch_user['openid'], 'credit1', 0-$givecredit, array(0,  $profile['nickname'].' 分享多商户扣除积分'));
                }

//                if (0 < $givemoney) {
//                        m('member')->setCredit($profile['openid'], 'credit2', $givemoney, array(0, $shopset['name'] . ' 文章营销奖励余额'));
//                }

                if (0 < $givecredit ) {
//                        $article_sys = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_article_sys') . ' WHERE uniacid=:uniacid limit 1 ', array(':uniacid' => $_W['uniacid']));
                        $detailurl = mobileUrl('member', NULL, true);
                        $p = '';

                        if (0 < $givecredit) {
                                $p .= $givecredit . '个积分、';
                        }

//                        if (0 < $givemoney) {
//                                $p .= $givemoney . '元余额';
//                        }

                        $datas = array(
                                array('name' => '昵称', 'value' => $profile['nickname']),
                                array('name' => '任务名称', 'value' => '分享得奖励'),
                                array('name' => '通知类型', 'value' => '用户通过您的分享进入商户（' . $merch['merchname'] . '），系统奖励您' . $p . '。'),
                                array('name' => '完成时间', 'value' => date('Y-m-d H:i', time())),
                                array('name' => '备注', 'value' => '奖励已发放成功，请到会员中心查看。')
                                );
                        $remark = "\n<a href='" . $detailurl . '\'>点击进入查看奖励详情</a>';
                        $text = '亲爱的[昵称]，用户通过您的分享进入商户（' . $merch['merchname'] . '），系统奖励您' . $p . '。' . $remark;
                        $message = array(
                                'first'    => array('value' => '您的奖励已到帐！', 'color' => '#4a5077'),
                                'keyword2' => array('title' => '业务状态', 'value' => '分享得奖励', 'color' => '#4a5077'),
                                'keyword3' => array('title' => '业务内容', 'value' => '用户通过您的分享进入商户（' . $merch['merchname'] . '》，系统奖励您' . $p . '。', 'color' => '#4a5077'),
                                'keyword1' => array('title' => '业务类型', 'value' => '会员通知', 'color' => '#000000'),
                                'remark'   => array('value' => '奖励已发放成功，请到会员中心查看。', 'color' => '#4a5077')
                                );

//                        if (empty($article_sys['article_close_advanced'])) {
                                m('notice')->sendNotice(array('openid' => $profile['openid'], 'tag' => 'article', 'default' => $message, 'cusdefault' => $text, 'url' => $detailurl, 'datas' => $datas, 'plugin' => 'merch'));
//                        }
                }
	}
}

?>
