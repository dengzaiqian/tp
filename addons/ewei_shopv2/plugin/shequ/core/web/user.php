<?php 
if( !defined("IN_IA") ) {
    exit( "Access Denied" );
}
class User_EweiShopV2Page extends PluginWebPage
{
    public function main()
    {
        global $_W;
        global $_GPC;
        $groups = $this->model->getGroups();
        $pindex = max(1, intval($_GPC["page"]));
        $psize = 20;
        $params = array( ":uniacid" => $_W["uniacid"] );
        $condition = "";
        $keyword = trim($_GPC["keyword"]);
        if( !empty($keyword) ) 
        {
            $condition .= " and ( u.shequname like :keyword or u.realname like :keyword or u.mobile like :keyword)";
            $params[":keyword"] = "%" . $keyword . "%";
        }

        if( $_GPC["groupid"] != "" ) 
        {
            $condition .= " and u.groupid=" . intval($_GPC["groupid"]);
        }

        if( $_GPC["status"] != "" ) 
        {
            $status = intval($_GPC["status"]);
            if( $status == 3 ) 
            {
                $condition .= " and u.status=1 and TIMESTAMPDIFF(DAY,now(),FROM_UNIXTIME(u.accounttime))<=30 ";
            }
            else
            {
                $condition .= " and u.status=" . $status;
            }

        }

        if( $_GPC["status"] == "0" ) 
        {
            $sortfield = "u.applytime";
        }
        else
        {
            $sortfield = "u.jointime";
        }

        $sql = "select  u.*  from " . tablename("ewei_shop_shequ_user") . "  u  where u.uniacid=:uniacid " . $condition . " ORDER BY " . $sortfield . " desc";
        if( empty($_GPC["export"]) ) 
        {
            $sql .= " limit " . ($pindex - 1) * $psize . "," . $psize;
        }

        $list = pdo_fetchall($sql, $params);
        $total = pdo_fetchcolumn("select count(*) from" . tablename("ewei_shop_shequ_user") . " u  " . " left join  " . tablename("ewei_shop_shequ_group") . " g on u.groupid = g.id " . " where u.uniacid = :uniacid " . $condition, $params);
        if( $_GPC["export"] == "1" ) 
        {
            ca("shequ.user.export");
            plog("shequ.user.export", "导出社区数据");
            foreach( $list as &$row ) 
            {
                $row["applytime"] = (empty($row["applytime"]) ? "-" : date("Y-m-d H:i", $row["applytime"]));
                $row["checktime"] = (empty($row["checktime"]) ? "-" : date("Y-m-d H:i", $row["checktime"]));
                $row["groupname"] = (empty($row["groupid"]) ? "无分组" : $row["groupname"]);
                $row["statusstr"] = (empty($row["status"]) ? "待审核" : ($row["status"] == 1 ? "通过" : "未通过"));
                $row["accounttime"] = date("Y-m-d H:i", $row["accounttime"]);
            }
            unset($row);
            m("excel")->export($list, array( "title" => "社区数据-" . date("Y-m-d-H-i", time()), "columns" => array( array( "title" => "ID", "field" => "id", "width" => 12 ), array( "title" => "社区名", "field" => "shequname", "width" => 24 ), array( "title" => "主营项目", "field" => "salecate", "width" => 12 ), array( "title" => "联系人", "field" => "realname", "width" => 12 ), array( "title" => "手机号", "field" => "moible", "width" => 12 ), array( "title" => "子帐号数", "field" => "accounttotal", "width" => 12 ), array( "title" => "可提现金额", "field" => "status0", "width" => 12 ), array( "title" => "已结算金额", "field" => "status3", "width" => 12 ), array( "title" => "到期时间", "field" => "accounttime", "width" => 12 ), array( "title" => "申请时间", "field" => "applytime", "width" => 12 ), array( "title" => "审核时间", "field" => "checktime", "width" => 12 ), array( "title" => "状态", "field" => "createtime", "width" => 12 ) ) ));
        }

        $pager = pagination2($total, $pindex, $psize);
        load()->func("tpl");
        include($this->template());
    }

    public function add()
    {
        $this->post();
    }

    public function edit()
    {
        $this->post();
    }

    protected function post()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC["id"]);
        $area_set = m("util")->get_area_config_set();
        $new_area = intval($area_set["new_area"]);
        if( empty($id) ) 
        {
            $max_flag = $this->model->checkMaxshequUser(1);
            if( $max_flag == 1 ) 
            {
                $this->message("已经达到最大社区数量,不能再添加社区", webUrl("shequ/user"), "error");
            }

        }

        $item = pdo_fetch("select * from " . tablename("ewei_shop_shequ_user") . " where id=:id and uniacid=:uniacid limit 1", array( ":id" => $id, ":uniacid" => $_W["uniacid"] ));
		if( !empty($item) ){ 
			$item['service'] = unserialize($item['service']);
		}     
		
		if( empty($item) ) 
        {
            $item["iscredit"] = 1;
            $item["iscreditmoney"] = 1;
            $item['service']=array();
        } 
		

        if( !empty($item["openid"]) ) 
        {
            $member = m("member")->getMember($item["openid"]);
        }

        if( !empty($item["payopenid"]) ) 
        {
            $user = m("member")->getMember($item["payopenid"]);
        }

        if( empty($item) || empty($item["accounttime"]) ) 
        {
            $accounttime = strtotime("+365 day");
        }
        else
        {
            $accounttime = $item["accounttime"];
        }

        if( !empty($item["accountid"]) ) 
        {
            $account = pdo_fetch("select * from " . tablename("ewei_shop_shequ_account") . " where id=:id and uniacid=:uniacid limit 1", array( ":id" => $item["accountid"], ":uniacid" => $_W["uniacid"] ));
        }

        if( !empty($item["pluginset"]) ) 
        {
            $item["pluginset"] = iunserializer($item["pluginset"]);
        }

        if( empty($account) ) 
        {
            $show_name = $item["uname"];
            $show_pass = m("util")->pwd_encrypt($item["upass"], "D");
        }
        else
        {
            $show_name = $account["username"];
        }

        $diyform_flag = 0;
        $diyform_plugin = p("diyform");
        $f_data = array(  );
        if( $diyform_plugin && !empty($_W["shopset"]["shequ"]["apply_diyform"]) ) 
        {
            if( !empty($item["diyformdata"]) ) 
            {
                $diyform_flag = 1;
                $fields = iunserializer($item["diyformfields"]);
                $f_data = iunserializer($item["diyformdata"]);
            }
            else
            {
                $diyform_id = $_W["shopset"]["shequ"]["apply_diyformid"];
                if( !empty($diyform_id) ) 
                {
                    $formInfo = $diyform_plugin->getDiyformInfo($diyform_id);
                    if( !empty($formInfo) ) 
                    {
                        $diyform_flag = 1;
                        $fields = $formInfo["fields"];
                    }

                }

            }

        }

        if( $_W["ispost"] ) 
        {
            $fdata = array(  );
            if( $diyform_flag ) 
            {
                $fdata = p("diyform")->getPostDatas($fields);
                if( is_error($fdata) ) 
                {
                    show_json(0, $fdata["message"]);
                }

            }

            $status = intval($_GPC["status"]);
            $username = trim($_GPC["username"]);
            $checkUser = false;
            if( 0 < $status ) 
            {
                // $checkUser = true;
            }

            // if( empty($_GPC["groupid"]) ) 
            // {
                // show_json(0, "请选择社区组!");
            // }

            if( empty($_GPC["cateid"]) ) 
            {
                show_json(0, "请选择社区分类!");
            }

            if( $checkUser ) 
            {
                if( empty($username) ) 
                {
                    show_json(0, "请填写账户名!");
                }

                if( empty($account) && empty($_GPC["pwd"]) ) 
                {
                    show_json(0, "请填写账户密码!");
                }

                $where = " username=:username";
                $params = array( ":username" => $username );
                $where .= " and uniacid = :uniacid ";
                $params[":uniacid"] = $_W["uniacid"];
                if( !empty($account) ) 
                {
                    $where .= " and id<>:id";
                    $params[":id"] = $account["id"];
                }

                $usercount = pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_shequ_account") . " where " . $where . " limit 1", $params);
                if( 0 < $usercount ) 
                {
                    show_json(0, "账户名 " . $username . " 已经存在!");
                }

                if( !empty($account) && empty($account["pwd"]) && empty($_GPC["pwd"]) ) 
                {
                    show_json(0, "请填写账户密码!");
                }

            }

            $where = " username=:username";
            $params = array( ":username" => $username );
            $where .= " and uniacid = :uniacid ";
            $params[":uniacid"] = $_W["uniacid"];
            if( !empty($account) ) 
            {
                $where .= " and id<>:id";
                $params[":id"] = $account["id"];
            }

            // $usercount = pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_shequ_account") . " where " . $where . " limit 1", $params);
            // if( 0 < $usercount ) 
            // {
                // show_json(0, "账户名 " . $username . " 已经存在!");
            // }

            $salt = "";
            $pwd = "";
            if( empty($account) || empty($account["salt"]) || !empty($_GPC["pwd"]) ) 
            {
                $salt = random(8);
                while( 1 ) 
                {
                    $saltcount = pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_shequ_account") . " where salt=:salt limit 1", array( ":salt" => $salt ));
                    if( $saltcount <= 0 ) 
                    {
                        break;
                    }

                    $salt = random(8);
                }
                $pwd = md5(trim($_GPC["pwd"]) . $salt);
            }
            else
            {
                $salt = $account["salt"];
                $pwd = $account["pwd"];
            }

            // if( $_GPC["iscreditmoney"] == 0 && $_GPC["creditrate"] == 0 ) 
            // {
                // show_json(0, "开启积分提现，比例不能为0");
            // }

            // if( $_GPC["iscreditmoney"] == 1 ) 
            // {
                // $_GPC["creditrate"] = 0;
            // }

            $data = array(
                "uniacid" => $_W["uniacid"],
                "shequname" => trim($_GPC["shequname"]), 
                "salecate" => trim($_GPC["salecate"]),
                "realname" => trim($_GPC["realname"]),
                "mobile" => trim($_GPC["mobile"]),
                "address" => trim($_GPC["address"]),
                "tel" => trim($_GPC["tel"]),
                "lng" => $_GPC["map"]["lng"],
                "lat" => $_GPC["map"]["lat"],
                "accounttime" => strtotime($_GPC["accounttime"]),
                "accounttotal" => intval($_GPC["accounttotal"]),
                "maxgoods" => intval($_GPC["maxgoods"]),
                "groupid" => intval($_GPC["groupid"]),
                "cateid" => intval($_GPC["cateid"]),
                "isrecommand" => intval($_GPC["isrecommand"]),
                "remark" => trim($_GPC["remark"]),
                "status" => $status,
                "desc" => trim($_GPC["desc1"]), 
                "logo" => save_media($_GPC["logo"]),
                "payopenid" => trim($_GPC["payopenid"]),
                "payrate" => trim($_GPC["payrate"], "%"),
                "pluginset" => iserializer($_GPC["pluginset"]),
                "creditrate" => intval($_GPC["creditrate"]),
                "iscredit" => intval($_GPC["iscredit"]),
                "iscreditmoney" => intval($_GPC["iscreditmoney"]),
                "shequ_hasendtime" => intval($_GPC["shequ_hasendtime"]),
                "shequ_endtime" => strtotime($_GPC["shequ_endtime"]),
                "shequ_readtime" => trim($_GPC["shequ_readtime"]),
                "shequ_rule_credittotal" => intval($_GPC["shequ_rule_credittotal"]),
                "shequ_rule_daynum" => trim($_GPC["shequ_rule_daynum"]),
                "shequ_rule_allnum" => trim($_GPC["shequ_rule_allnum"]),
                "shequ_rule_credit2" => intval($_GPC["shequ_rule_credit2"]),
				"isshowplantgoods" => intval($_GPC['isshowplantgoods']),
				"jiesuan_cardbrandname" => trim($_GPC["jiesuan_cardbrandname"]),
				"jiesuan_cardname" => trim($_GPC["jiesuan_cardname"]),
				"jiesuan_cardnum" => trim($_GPC["jiesuan_cardnum"]),
				"service" => serialize($_GPC["service"]),
                );
               
            if( $diyform_flag ) 
            {
                $data["diyformdata"] = iserializer($fdata);
                $data["diyformfields"] = iserializer($fields);
            }

            if( empty($item["jointime"]) && $status == 1 ) 
            {
                $data["jointime"] = time();
            }
            
            $account = array(
                "uniacid" => $_W["uniacid"],
                "shequid" => $id,
                "username" => $username,
                "pwd" => $pwd,
                "salt" => $salt,
                "status" => 1,
                "perms" => serialize(array(  )),
                "isfounder" => 1
                );
            if(isset($_GPC['parentshequid'])){
                $data["parentshequid"] = intval($_GPC['parentshequid']);
                $account["parentshequid"] = intval($_GPC['parentshequid']);
            }
			if(isset($_GPC['shequantid'])){
				$data["shequantid"] = intval($_GPC['shequantid']);
			}
            $item = pdo_fetch("select * from " . tablename("ewei_shop_shequ_user") . " where id=:id and uniacid=:uniacid limit 1", array( ":id" => $id, ":uniacid" => $_W["uniacid"] ));
            if( empty($item) ) 
            {
				// print_r($data);exit();
                $item["applytime"] = time();
                pdo_insert("ewei_shop_shequ_user", $data);
                $id = pdo_insertid();
				$account["shequid"] = $id;
                pdo_insert("ewei_shop_shequ_account", $account);
                $accountid = pdo_insertid();
                pdo_update("ewei_shop_shequ_user", array( "accountid" => $accountid ), array( "id" => $id ));
                plog("shequ.user.add", "添加社区 ID: " . $data["id"] . " 社区名: " . $data["shequname"] . "<br/>帐号: " . $data["username"] . "<br/>子帐号数: " . $data["accounttotal"] . "<br/>到期时间: " . date("Y-m-d", $data["accounttime"]));
            }
            else
            {
                pdo_update("ewei_shop_shequ_user", $data, array( "id" => $id ));
                if( !empty($item["accountid"]) ) 
                {
                    pdo_update("ewei_shop_shequ_account", $account, array( "id" => $item["accountid"] ));
                }else if( !empty($item["regid"]) )
                {
                    pdo_update("ewei_shop_shequ_reg", array('parentshequid'=>intval($_GPC['parentshequid'])), array( "id" => $item["regid"] ));
                }
                else
                {
                    pdo_insert("ewei_shop_shequ_account", $account);
                    $accountid = pdo_insertid();
                    pdo_update("ewei_shop_shequ_user", array( "accountid" => $accountid ), array( "id" => $id ));
                }
				
                plog("shequ.user.edit", "编辑社区 ID: " . $data["id"] . " 社区名: " . $item["shequname"] . " -> " . $data["shequname"] . "<br/>帐号: " . $item["username"] . " -> " . $data["username"] . "<br/>子帐号数: " . $item["accounttotal"] . " -> " . $data["accounttotal"] . "<br/>到期时间: " . date("Y-m-d", $item["accounttime"]) . " -> " . date("Y-m-d", $data["accounttime"]));
            }
			$this->updateSnsShequInfo($id);//将社区管理与全民社区管理
            show_json(1, array( "url" => webUrl("shequ/user", array( "status" => $item["status"] )) ));
        }

        
        $plugins_data = $this->model->getPluginList();
        $plugins_list = $plugins_data["plugins_list"];     
        
        $groups = $this->model->getGroups();
        $category = $this->model->getCategory();
        include($this->template());
    }
	public function updateSnsShequInfo($id=0){
		global $_W;
        global $_GPC;
		if($id>0){
			$item = pdo_fetch("select * from " . tablename("ewei_shop_shequ_user") . " where id=:id and uniacid=:uniacid limit 1", array( ":id" => $id, ":uniacid" => $_W["uniacid"] ));
			if($item){
				$arr = array(
					'uniacid'=>$_W['uniacid'],
					'name'=>$item['shequname'],
					'thumb'=>$item['logo'],
					'shequid'=>$item['id'],
				);
				$sns_item = pdo_fetch("select * from " . tablename("ewei_shop_sns_category") . " where shequid=:id and uniacid=:uniacid limit 1", array( ":id" => $id, ":uniacid" => $_W["uniacid"] ));
				if($sns_item){
					pdo_update("ewei_shop_sns_category", $arr, array( "shequid" => $id ));
				}else{
					pdo_insert("ewei_shop_sns_category", $arr);
				}
			}
		}
	}
    public function get_show_money()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC["id"]);
        if( !empty($id) ) 
        {
            $tmoney = $this->model->getshequOrderTotalPrice($id);
            show_json(1, array( "status0" => $tmoney["status0"], "status3" => $tmoney["status3"] ));
        }

    }

    public function status()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC["id"]);
        if( empty($id) ) 
        {
            $id = (is_array($_GPC["ids"]) ? implode(",", $_GPC["ids"]) : 0);
        }

        $items = pdo_fetchall("SELECT id,shequname FROM " . tablename("ewei_shop_shequ_user") . " WHERE id in( " . $id . " ) AND uniacid=" . $_W["uniacid"]);
        foreach( $items as $item ) 
        {
            pdo_update("ewei_shop_shequ_user", array( "status" => intval($_GPC["status"]) ), array( "id" => $item["id"] ));
            plog("shequ.group.edit", ("修改社区分组账户状态<br/>ID: " . $item["id"] . "<br/>社区名称: " . $item["shequname"] . "<br/>状态: " . $_GPC["status"] == 1 ? "启用" : "禁用"));
        }
        show_json(1);
    }

    public function delete()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC["id"]);
        if( empty($id) ) 
        {
            $id = (is_array($_GPC["ids"]) ? implode(",", $_GPC["ids"]) : 0);
        }

        $uniacid = $_W["uniacid"];
        $change_data = array(  );
        $change_data["shequid"] = 0;
        $change_data["status"] = 0;
        $items = pdo_fetchall("SELECT * FROM " . tablename("ewei_shop_shequ_user") . " WHERE id in( " . $id . " ) AND uniacid=" . $_W["uniacid"]);
        foreach( $items as $item ) 
        {
            pdo_update("ewei_shop_goods", $change_data, array( "shequid" => $item["id"], "uniacid" => $uniacid ));
            pdo_delete("ewei_shop_shequ_reg", array( "id" => $item["regid"] ));
            pdo_delete("ewei_shop_shequ_account", array( "shequid" => $item["id"], "uniacid" => $uniacid ));
            pdo_delete("ewei_shop_shequ_user", array( "id" => $item["id"], "uniacid" => $uniacid ));
            plog("shequ.user.delete", "删除`社区 <br/>社区:  ID: " . $item["id"] . " / 名称:   " . $item["shequname"]);
        }
        show_json(1);
    }

    public function query()
    {
        global $_W;
        global $_GPC;
        $kwd = trim($_GPC["keyword"]);
        $params = array(  );
        $params[":uniacid"] = $_W["uniacid"];
        $condition = "uniacid=:uniacid AND status=1";
        if( !empty($kwd) ) 
        {
            $condition .= " AND `shequname` LIKE :keyword";
            $params[":keyword"] = "%" . $kwd . "%";
        }

        $ds = pdo_fetchall("SELECT id,shequname FROM " . tablename("ewei_shop_shequ_user") . " WHERE " . $condition . " order by id asc", $params);
        include($this->template());
        exit();
    }

    public function queryshequs()
    {
        global $_W;
        global $_GPC;
        $kwd = trim($_GPC["keyword"]);
        $params = array(  );
        $params[":uniacid"] = $_W["uniacid"];
        $condition = " and uniacid=:uniacid  and status =1";
        if( !empty($kwd) ) 
        {
            $condition .= " AND `shequname` LIKE :keyword";
            $params[":keyword"] = "%" . $kwd . "%";
        }

        $ds = pdo_fetchall("SELECT id,shequname as title ,logo as thumb FROM " . tablename("ewei_shop_shequ_user") . " WHERE 1 " . $condition . " order by id desc", $params);
        $ds = set_medias($ds, array( "thumb", "share_icon" ));
        if( $_GPC["suggest"] ) 
        {
            exit( json_encode(array( "value" => $ds )) );
        }

        include($this->template());
    }

}


