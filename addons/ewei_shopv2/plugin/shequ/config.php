<?php 
if( !defined("IN_IA") ) 
{
    exit( "Access Denied" );
}

return array( 
	"version" => "1.0", 
	"id" => "shequ", 
	"name" => "社区管理", 
	"v3" => true, 
	"menu" => array( 
		"title" => "社区管理", 
		"plugincom" => 1, 
		"icon" => "page", 
		"items" => array( 
			array( 
				"title" => "社区管理", 
				"route" => "user", 
				"items" => array( 
					array( 
						"title" => "待入驻", 
						"param" => array( "status" => 0 ) 
						), 
					array( 
						"title" => "入驻中", 
						"param" => array( "status" => 1 ) 
						), 
					array( 
						"title" => "暂停中", 
						"param" => array( "status" => 2 ) 
						), 
					array( 
						"title" => "社区分类",
						"route" => "category", 
						"route_ns" => true ) 
				) 
			), 
			array( 
				"title" => "服务管理", 
				"route" => "service", 
				"items" => array(  
					array( 
						"title" => "社区服务",
						"route_ns" => true 
					), 
					array( 
						"title" => "服务项目",
						"route" => "service.param",
						"route_ns" => true 
					), 
				) 
			),
			array( 
				"title" => "信息管理", 
				"route" => "massage", 
				"items" => array(  
					array( 
						"title" => "公告管理",
						"route_ns" => true 
					), 
				) 
			),
			array(
				"title" => "其他设置", 
				"items" => array(
					array( 	
						"title" => "基础设置", "route" => "set" ),  
					array( 	
						"title" => "入口设置", "route" => "cover.register", 
						"extends" => array( 
							"shequ.cover.shequlist",
							"shequ.cover.shequuser" ) 
						), 
					// array( 
						// "title" => "社区分类幻灯", 
						// "route" => "category.swipe", 
						// "extend" => "shequ.category.edit_swipe" 
					// ) 
				) 
			) 
		) 
	),
);

