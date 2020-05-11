<?php 
if( !defined("IN_IA") ) 
{
    exit( "Access Denied" );
}

require(EWEI_SHOPV2_PLUGIN . "shequ/core/inc/page_shequ.php");

class Index_EweiShopV2Page extends shequWebPage
{
    public function main()
    {
        global $_W;
        include($this->template());
    }

    public function ajaxgettotalprice()
    {
        global $_W;
        $shequid = $_W["shequid"];
        $totals = $this->model->getshequOrderTotalPrice($shequid);
        show_json(1, $totals);
    }

    public function ajaxgettotalcredit()
    {
        global $_W;
        $shequid = $_W["shequid"];
        $totals = $this->model->getshequCreditTotalPrice($shequid);
        show_json(1, $totals);
    }

}


