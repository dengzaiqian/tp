<?php 
if( !defined("IN_IA") ) {
    exit( "Access Denied" );
}
class Massage_EweiShopV2Page extends PluginWebPage
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
            $condition .= " and ( u.title like :keyword or u.desc like :keyword or u.author like :keyword)";
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
            $sortfield = "u.createtime";
        }
        else
        {
            $sortfield = "u.createtime";
        }

        $sql = "select  u.*  from " . tablename("ewei_shop_shequ_massage") . "  u  where u.uniacid=:uniacid " . $condition . " ORDER BY " . $sortfield . " desc";
        if( empty($_GPC["export"]) ) 
        {
            $sql .= " limit " . ($pindex - 1) * $psize . "," . $psize;
        }
		// echo $sql;exit();
        $list = pdo_fetchall($sql, $params);
		if($list){
			foreach($list as &$l){
				$l['viewcount'] = pdo_fetchcolumn("select count(*) from" . tablename("ewei_shop_shequ_massage_record") .' where messageid='.$l['id']); 
			}unset($l);
		}
        $total = pdo_fetchcolumn("select count(*) from" . tablename("ewei_shop_shequ_massage") . " u  " . " where u.uniacid = :uniacid " . $condition, $params);

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

	public function getGroups(){
		global $_W;
        global $_GPC;
		$shequid = intval($_GPC["shequid"]);
		// $shequid = 101;
		if( empty($_GPC["shequid"]) ) 
		{
			show_json(0, "请选择小区");
		}
		if( $_W["ispost"] ) {
			$item = pdo_fetch("select * from " . tablename("ewei_shop_shequ_user") . " where id=:id and uniacid=:uniacid limit 1", array( ":id" => $shequid, ":uniacid" => $_W["uniacid"] ));
            // print_r($item);
			$services = unserialize($item['service']);
			// print_r($services);
			// echo "select * from ".tablename("ewei_shop_shequ_group") . " where id in ( ".implode(',',$services)." ) and uniacid=:uniacid ";
			$list = pdo_fetchall("select * from ".tablename("ewei_shop_shequ_group") . " where id in ( ".implode(',',$services)." ) and uniacid=:uniacid ",array( ":uniacid" => $_W["uniacid"] ));
		    show_json(1,array('list'=>$list));
        }
	}
    protected function post()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC["id"]);

        $item = pdo_fetch("select * from " . tablename("ewei_shop_shequ_massage") . " where id=:id and uniacid=:uniacid limit 1", array( ":id" => $id, ":uniacid" => $_W["uniacid"] ));

        if( $_W["ispost"] ) 
        {
            $fdata = array(  );

            $status = intval($_GPC["status"]);
            $title = trim($_GPC["title"]);
            $checkmassage = false;
			// print_r($_GPC);
            if( empty($_GPC["shequid"]) ) 
            {
                show_json(0, "请选择小区");
            }

            if( empty($_GPC["service"]) ) 
            {
                // show_json(0, "请选择小区服务!");
            }
			$contents = is_array($_GPC['item']) ? $_GPC['item'] : array();
            $data = array(
                "uniacid" => $_W["uniacid"],
				"title" => trim($_GPC["title"]),
				"desc" => trim($_GPC["desc"]),
				"author" => trim($_GPC["author"]),
                "logo" => save_media($_GPC["logo"]),
				"shequid" => intval($_GPC["shequid"]),
				"service" => implode(',',$_GPC["service"]),
				"status" => intval($_GPC["status"]),
				"displayorder" => intval($_GPC["displayorder"]),
				"content" => m('common')->html_images($contents["content"]),
            );

            if( empty($id) ) {
                $data["createtime"] = time();
            }
			// print_r($_GPC);print_r($data);exit();
            $item = pdo_fetch("select * from " . tablename("ewei_shop_shequ_massage") . " where id=:id and uniacid=:uniacid limit 1", array( ":id" => $id, ":uniacid" => $_W["uniacid"] ));
            if( empty($item) ) 
            {
                pdo_insert("ewei_shop_shequ_massage", $data);
                $id = pdo_insertid();
            }
            else
            {
                pdo_update("ewei_shop_shequ_massage", $data, array( "id" => $id ));
            }

            show_json(1, array( "url" => webUrl("shequ/massage", array( "status" => $item["status"] )) ));
        }
		
		$category = pdo_fetchall('select * from '.tablename('ewei_shop_shequ_user').' where uniacid=:uniacid and status=1', array(':uniacid' => $_W['uniacid']));
		$group = $services = array();
		if($item['service']){
			$services = explode(',',$item['service']);
		}
		$allgroups = pdo_fetchall("select * from ". tablename("ewei_shop_shequ_group") . " where uniacid=:uniacid ",array( ":uniacid" => $_W["uniacid"] ));
        include($this->template());
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

        $items = pdo_fetchall("SELECT id,shequname FROM " . tablename("ewei_shop_shequ_massage") . " WHERE id in( " . $id . " ) AND uniacid=" . $_W["uniacid"]);
        foreach( $items as $item ) 
        {
            pdo_update("ewei_shop_shequ_massage", array( "status" => intval($_GPC["status"]) ), array( "id" => $item["id"] ));
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
        $items = pdo_fetchall("SELECT * FROM " . tablename("ewei_shop_shequ_massage") . " WHERE id in( " . $id . " ) AND uniacid=" . $_W["uniacid"]);
        foreach( $items as $item ) 
        {
            pdo_update("ewei_shop_goods", $change_data, array( "shequid" => $item["id"], "uniacid" => $uniacid ));
            pdo_delete("ewei_shop_shequ_reg", array( "id" => $item["regid"] ));
            pdo_delete("ewei_shop_shequ_account", array( "shequid" => $item["id"], "uniacid" => $uniacid ));
            pdo_delete("ewei_shop_shequ_massage", array( "id" => $item["id"], "uniacid" => $uniacid ));
            plog("shequ.massage.delete", "删除`社区 <br/>社区:  ID: " . $item["id"] . " / 名称:   " . $item["shequname"]);
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

        $ds = pdo_fetchall("SELECT id,shequname FROM " . tablename("ewei_shop_shequ_massage") . " WHERE " . $condition . " order by id asc", $params);
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

        $ds = pdo_fetchall("SELECT id,shequname as title ,logo as thumb FROM " . tablename("ewei_shop_shequ_massage") . " WHERE 1 " . $condition . " order by id desc", $params);
        $ds = set_medias($ds, array( "thumb", "share_icon" ));
        if( $_GPC["suggest"] ) 
        {
            exit( json_encode(array( "value" => $ds )) );
        }

        include($this->template());
    }

}


