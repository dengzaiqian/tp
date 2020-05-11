<?php
ini_set("max_execution_time",0);error_reporting(0);
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
if (!(defined('PHP_VERSION_ID'))) 
{
	$version = explode('.', PHP_VERSION);
	define('PHP_VERSION_ID', ($version[0] * 10000) + ($version[1] * 100) + $version[2]);
}
class Upgrade_EweiShopV2Page extends SystemPage 
{
	public function main() 
	{
		load()->func('file');
		global $_W,$_GPC;  
		if(empty($_GPC['op'])) {
			$_GPC['op'] = 'display';
		}
		$ver = include(IA_ROOT . '/addons/ewei_shopv2/version.php');
		$version = defined('EWEI_SHOPV2_VERSION') ? EWEI_SHOPV2_VERSION : '2.0.0';
		$release = defined('EWEI_SHOPV2_RELEASE') ? EWEI_SHOPV2_RELEASE : '201605010000';
		$ver = EWEI_SHOPV2_VERSION;
		$namea = EWEI_SHOPV2_RELEASE;
		$hosturl = urlencode('http://' . $_SERVER['HTTP_HOST']);
		$hostip =  $_SERVER['SERVER_ADDR']?$_SERVER['SERVER_ADDR']:gethostbyname($SERVER_NAME);
		$updatehost = 'http://update.yixiuba.net/update.php';
		$lastver = file_get_contents($updatehost . '?a=check&v='.$ver);
		if($_GPC['op'] == 'display'){
			$op = $_GPC['op'];	
			$gettimeurl = $updatehost . '?a=client_check_time&v=' . $ver . '&u=' . $hosturl;
			$domain_time = file_get_contents($gettimeurl);	
			$chosturl = $updatehost . '?a=change&v=' . $ver. '&u=' . $hosturl;		
			$cinfo = file_get_contents($chosturl);
		}
		elseif($_GPC['op'] == 'update'){
			$op = $_GPC['op'];
			$updatehost = 'http://update.yixiuba.net/update.php';
			$updatehosturl = $updatehost . '?a=update&v=' . $ver . '&u=' . $hosturl. '&i=' .$hostip ;
			$updatenowinfo = file_get_contents($updatehosturl);
			if (strstr($updatenowinfo, 'zip')){		
					$pathurl = $updatehost . '?a=down&f=' . $updatenowinfo . '&u=' . $hosturl;

					$updatedir = IA_ROOT.'/addons/ewei_shopv2/data/Temp/update';

					if(!is_dir($updatedir)) {
						mkdirs($updatedir);
					}	
					$isgot = $this->get_file($pathurl, $updatenowinfo, $updatedir);
					
					if($isgot){				
						$updatezip = $updatedir . '/' . $updatenowinfo;
						require IA_ROOT . "/framework/library/phpexcel/PHPExcel/Shared/PCLZip/pclzip.lib.php";  
						$thisfolder = new PclZip($updatezip);
						$isextract = $thisfolder->extract(PCLZIP_OPT_PATH, $updatedir);
						if ($isextract == 0) {  
							message('解压更新包失败！',referer(),'error'); 
						} 
						
						$archive = new PclZip($updatezip);
						$list = $archive->extract(PCLZIP_OPT_PATH, IA_ROOT, PCLZIP_OPT_REPLACE_NEWER); 
						
						if ($list == 0) {  
							message('远程升级文件不存在或站点没有修改权限。升级失败！',referer(),'error'); 
						} 
						@unlink($updatedir . '/update.sql');
						$this->deldir($updatedir);
						if(file_exists(IA_ROOT . '/update.sql')) {						
							$sqlfile = IA_ROOT . '/update.sql';
							$this->runquery($sqlfile);               
						}
						@unlink(IA_ROOT . '/update.sql');
						message('系统更新完成！',webUrl('system/auth/upgrade'),'success');
					}
					else{
						message('查找不到更新包！',referer(),'error');
					}
			}
			else{
					message('微上宝提醒您:请检查授权状态或联系微上宝客服授权！',referer(),'error');
			}
		}

	include $this->template('system/auth/upgrade');
	}
	
	
	function check() {
		global $_W, $_GPC;

		$plugins = pdo_fetchall('select `identity` from '.tablename('ewei_shop_plugin'),array(),'identity');
		load()->func('db');
        load()->func('communication');
		$version = defined('EWEI_SHOPV2_VERSION') ? EWEI_SHOPV2_VERSION : '2.0.0';
		$release = defined('EWEI_SHOPV2_RELEASE') ? EWEI_SHOPV2_RELEASE : '201605010000';
		$updateinfo = file_get_contents('http://update.yixiuba.net/update.php?a=getinfo');
		$upgrade = json_decode($updateinfo,true);
		if (is_array($upgrade)) {
			if($upgrade['release']>=$release){
				$templatefiles = "";
				//$upgrade = $result['result'];
				if ($upgrade['result'] == 1) {
					
					
					
					$files = array();
					
					if (!empty($upgrade['files'])) {
						
						
						foreach ($upgrade['files'] as $file) {
							$entry =IA_ROOT . '/addons/ewei_shopv2' . $file['path'];
							if (!is_file($entry) || md5_file($entry) != $file['md5']) {

								$files[] = array('path' => $file['path'], 'download' => 0, 'entry'=>$entry,array("md5"=>md5_file($entry),"md5_"=>md5_file($entry),"db"=>md5_file($entry) != $file['md5'],""));
									$templatefiles.= $file['path'] . "<br/>";
							}
						}


					}
					

					//数据表
					$database = array();
					if (!empty($upgrade['structs'])) {
						$upgrade['structs'] = unserialize(base64_decode($upgrade['structs']));
						foreach ($upgrade['structs'] as $remote) {

							$name = substr($remote['tablename'], 4);

							$local = $this->table_schema(pdo(), $name);

							if(empty($local)) {
								$database[] = $remote;
							} else {
								$sqls = db_table_fix_sql_ab($local, $remote);
								if(!empty($sqls)) {
									$database[] = $remote;

								}
							}
						}

					}
					
					$gz = base64_encode(gzdeflate(serialize(array('files' => $files, 'version' => $upgrade['version'], 'release' => $upgrade['release'], 'upgrades' => $upgrade['upgrades'], "database"=>$database))));
					cache_write('cloud:modules:upgradev2', $gz);
					
					//cache_write('cloud:modules:upgradev2', array('files' => $files, 'version' => $upgrade['version'], 'release' => $upgrade['release'], 'upgrades' => $upgrade['upgrades'], "database"=>$database));
					$log = $upgrade['message'];            //日志内容
					show_json(1, array(
						'result' => 1,
						'version' => $upgrade['version'],      //版本号
						'release' => $upgrade['release'],   //版本日期
						'filecount' => count($files),
						'database' => !empty($database),
						'upgrades'=>!empty($database)?1:(count($files)>0?1:0),
						'log' => nl2br($log),
						'templatefiles'=>$templatefiles
					));
				}
				show_json(0, $upgrade['message']);

			}else{
					show_json(1, array(
						'result' => 1,
						'version' => $upgrade['version'],      //版本号
						'release' => $upgrade['release'],   //版本日期
						'filecount' => 0,
						'database' => 0,
						'upgrades'=>!empty($database)?1:(count($files)>0?1:0),
						'log' => nl2br($log),
						'templatefiles'=>$templatefiles
					));
			}
		}
		if (is_file(EWEI_SHOPV2_PATH . "tmp")){
			@unlink(EWEI_SHOPV2_PATH . "tmp");
		}
		show_json(0, $upgrade['message']);
	}
	
	function process() {

		global $_W, $_GPC;
		load()->func('communication');
		load()->func('file');
		load()->func('db');
		$upgrade = cache_load('cloud:modules:upgradev2');
		$upgrade = unserialize(gzinflate(base64_decode($upgrade)));
		
		$files = $upgrade['files'];
		$version = $upgrade['version'];
		$database = $upgrade['database'];
		$upgrades = $upgrade['upgrades'];
		
		$auth = get_auth();
		$action = trim($_GPC['action']);
		empty($action) && $action = 'file';
		if ($action == 'database') {


			if( empty($database)){
				show_json(2, array('total' => 0,'action'=>'database'));
			}
			$remote = false;

			foreach ($database as $d) {
				if (empty($d['updated'])) {
					$remote = $d;
					break;
				}
			}

			if (!empty($remote)) {
                 $name = substr($remote['tablename'], 4);

				$local = $this->table_schema(pdo(), $name);
				$sqls = db_table_fix_sql_ab($local, $remote);

				$error = false;
				foreach ($sqls as $sql) {

					if (pdo_query($sql) === false) {


						$error = true;
						break;
					}
				}

				$success = 0;
				foreach ($database as &$d) {
					if ($d['tablename'] == $remote['tablename'] && !$error) {
						$d['updated'] = 1;
					}
					if($d['updated']){
						$success++;
					}
				}
				unset($d);
				$gz = base64_encode(gzdeflate(serialize(array('files' => $files, 'version' => $upgrade['version'], 'release' => $upgrade['release'], 'upgrades' => $upgrade['upgrades'], "database"=>$database))));
				cache_write('cloud:modules:upgradev2', $gz);
				//cache_write('cloud:modules:upgradev2', array('files' => $files, 'version' => $version, 'release' => $upgrade['release'], 'upgrades' => $upgrade['upgrades'], 'database' => $database));
				if($success >= count($database) ){
					show_json(2, array('total' => count($database),'action'=>'database'));
				}
				show_json(1, array('total' => count($database), 'success' => $success,'action'=>'database'));
			}
			show_json(2, array('total' => count($database),'action'=>'database'));
		} elseif ($action == 'file') {


			$path = "";
			foreach ($files as $f) {
				if (empty($f['download'])) {
					$path = $f['path'];
					break;
				}
			}


			if (!empty($path)) {
				$ret = file_get_contents('http://update.yixiuba.net/update.php?a=downfile&path='.$path);
				$ret = json_decode($ret,true);
				if (is_array($ret)) {
					//$ret = $ret['result'];

					$path = $ret['path'];
					if(strexists($path,'pcsite/')){
						$dirpath = dirname($path);
						if (!is_dir(IA_ROOT ."/". $dirpath)) {
							mkdirs(IA_ROOT."/" . $dirpath);
							@chmod(IA_ROOT ."/". $dirpath,0777);
						}
						$content = base64_decode($ret['content']);
						file_put_contents(IA_ROOT."/". $path, $content);

					}

					$dirpath = dirname($path);
					if (!is_dir(EWEI_SHOPV2_PATH . $dirpath)) {
						mkdirs(EWEI_SHOPV2_PATH . $dirpath);
						@chmod(EWEI_SHOPV2_PATH . $dirpath,0777);
					}
					$content = base64_decode($ret['content']);
					file_put_contents(EWEI_SHOPV2_PATH . $path, $content);



					if (isset($ret['path1'])) {
						$path1 = $ret['path1'];
						$dirpath1 = dirname($path1);
						if (!is_dir(EWEI_SHOPV2_PATH . $dirpath1)) {
							mkdirs(EWEI_SHOPV2_PATH . $dirpath1);
							@chmod(EWEI_SHOPV2_PATH . $dirpath1,0777);
						}
						$content1 = base64_decode($ret['content1']);
						file_put_contents(EWEI_SHOPV2_PATH . $path1, $content1);
					}
					if (isset($ret['path2'])) {
						$path2 = $ret['path2'];
						$dirpath2 = dirname($path2);
						if (!is_dir(EWEI_SHOPV2_PATH . $dirpath2)) {
							mkdirs(EWEI_SHOPV2_PATH . $dirpath2);
							@chmod(EWEI_SHOPV2_PATH . $dirpath2,0777);
						}
						$content2 = base64_decode($ret['content2']);
						file_put_contents(EWEI_SHOPV2_PATH . $path2, $content2);
					}

					$success = 0;

					foreach ($files as &$f) {

						if ($f['path'] == $ret['path']) {
							$f['download'] = 1;
						}
						if ($f['download']) {
							$success++;
						}
					}
					unset($f);
					$gz = base64_encode(gzdeflate(serialize(array('files' => $files, 'version' => $upgrade['version'], 'release' => $upgrade['release'], 'upgrades' => $upgrade['upgrades'], "database"=>$database))));
				cache_write('cloud:modules:upgradev2', $gz);
					
					//cache_write('cloud:modules:upgradev2', array('files' => $files, 'version' => $version, 'release' => $upgrade['release'], 'upgrades' => $upgrade['upgrades']));
					if($success >= count($files) ){
					         show_json(2, array('total' => count($files),'action'=>'file'));
					}
				}
				show_json(1, array('total' => count($files), 'success' => $success,'action'=>'file'));
			}
			show_json(2, array('total' => count($files),'action'=>'file'));
		} else if ($action == 'upgrade') {


			if(empty($upgrades)){
				$this->updateComplete($upgrade['version'],$upgrade['release']);
				show_json(2, array('total' => count($upgrades),'action'=>'upgrade'));
			}

			$update = false;
			foreach ($upgrades as $up) {
				if (empty($up['updated'])) {
				         $update = $up;
				         break;
				}
			}

			if (!empty($update)) {
				$updatepath =EWEI_SHOPV2_PATH . "tmp/";
				if(!is_dir($updatepath)){
					mkdirs($updatepath);
				}
				$updatefile = $updatepath ."upgrade-".$update['release'].".php";
				$content = base64_decode($update['upgrade']);
				if(!empty($content)){
					file_put_contents($updatefile, $content);
					require $updatefile;
					@unlink($updatefile);
				}

				$success = 0;
				foreach ($upgrades as &$up) {
					if ($up['release'] == $update['release']) {
						$up['updated'] = 1;
					}
					if ($up['updated']) {
						$success++;
					}
				}
				unset($up);
				$gz = base64_encode(gzdeflate(serialize(array('files' => $files, 'version' => $upgrade['version'], 'release' => $upgrade['release'], 'upgrades' => $upgrade['upgrades'], "database"=>$database))));
				cache_write('cloud:modules:upgradev2', $gz);

				//cache_write('cloud:modules:upgradev2', array('files' => $files, 'version' => $version, 'release' => $upgrade['release'], 'upgrades' => $upgrades));

				if($success >= count($upgrades) ){
					$this->updateComplete($upgrade['version'],$upgrade['release']);
					show_json(2, array('total' => count($upgrades),'action'=>'upgrade'));
				}

				show_json(1, array('total' => count($upgrades), 'success' => $success,'action'=>'upgrade'));
			}
			else{
				$this->updateComplete($upgrade['version'],$upgrade['release']);
				show_json(2, array('total' => count($upgrades),'action'=>'upgrade'));
			}
		}
	}

	protected function table_schema($db, $tablename = '') {
		$result = $db->fetch("SHOW TABLE STATUS LIKE '" . trim($db->tablename($tablename), '`') . "'");
		if(empty($result)) {
			return array();
		}
		$ret['tablename'] = $result['Name'];
		$ret['charset'] = $result['Collation'];
		$ret['engine'] = $result['Engine'];
		$ret['increment'] = $result['Auto_increment'];
		$result = $db->fetchall("SHOW FULL COLUMNS FROM " . $db->tablename($tablename));
		foreach($result as $value) {
			$temp = array();
			$type = explode(" ", $value['Type'], 2);
			$temp['name'] = $value['Field'];
			$pieces = explode('(', $type[0], 2);
			$temp['type'] = $pieces[0];
			$temp['length'] = rtrim($pieces[1], ')');
			$temp['null'] = $value['Null'] != 'NO';
			$temp['signed'] = empty($type[1]);
			$temp['increment'] = $value['Extra'] == 'auto_increment';
			$temp['default'] = $value['Default'];
			$ret['fields'][$value['Field']] = $temp;
		}
		$result = $db->fetchall("SHOW INDEX FROM " . $db->tablename($tablename));
		foreach($result as $value) {
			$ret['indexes'][$value['Key_name']]['name'] = $value['Key_name'];
			$ret['indexes'][$value['Key_name']]['type'] = ($value['Key_name'] == 'PRIMARY') ? 'primary' : ($value['Non_unique'] == 0 ? 'unique' : 'index');
			if(!empty($value['Sub_part'])){
			$ret['indexes'][$value['Key_name']]['length'] = $value['Sub_part'];
		}
			$ret['indexes'][$value['Key_name']]['fields'][] = $value['Column_name'];
		}
		return $ret;
	}
	protected function updateComplete($version,$release){
		load()->func('file');
		
		cache_delete('cloud:modules:upgradev2');
		$time = time();
		global $my_scenfiles;
		my_scandir(IA_ROOT . "/addons/ewei_shopv2");
		foreach ($my_scenfiles as $file) {
		          if (!strexists($file, '/ewei_shopv2/data/') && !strexists($file, 'version.php')) {
			     @touch($file, $time);
			}
		}
		rmdirs(IA_ROOT . "/addons/ewei_shopv2/tmp");
	}
	function checkversion() {
		file_put_contents(IA_ROOT . "/addons/ewei_shopv2/version.php", "<?php if(!defined('IN_IA')) {exit('Access Denied');}if(!defined('EWEI_SHOPV2_VERSION')) {define('EWEI_SHOPV2_VERSION', '2.0.0');}if(!defined('EWEI_SHOPV2_RELEASE')) {define('EWEI_SHOPV2_RELEASE', '20160101123456');}");
		header('location: ' . webUrl('system/auth/upgrade'));
		exit;
	}

    public function log()
	{
		global $_W;
		global $_GPC;
      header('Location: http://www.gulaitec.com/2/show.php?id=84');
		exit;
		$plugins = pdo_fetchall('select `identity` from ' . tablename('ewei_shop_plugin'), array(), 'identity');
		$auth = get_auth();
		$upgrade = auth_check($auth,$version,$release);
		$version = ((defined('EWEI_SHOPV2_VERSION') ? EWEI_SHOPV2_VERSION : '2.0.0'));
		$release = ((defined('EWEI_SHOPV2_RELEASE') ? EWEI_SHOPV2_RELEASE : '201605010000'));
		$pindex = max(1, intval($_GPC['page']));
		$psize = 3;
		load()->func('communication');
		$resp = ihttp_post(EWEI_SHOPV2_AUTH_URL . 'log', array('ip' => $auth['ip'], 'id' => $auth['id'], 'code' => $auth['code'], 'domain' => trim(preg_replace('/http(s)?:\\/\\//', '', trim($_W['siteroot'], '/'))), 'version' => $version, 'release' => $release, 'manual' => 1, 'plugins' => array_keys($plugins), 'pindex' => $pindex, 'psize' => $psize));
		$res = @json_decode($resp['content'], true);
		$count = 0;
		//$log = '';
        $log = $upgrade['message'];          
		if (is_array($res)) {
			$count = $res['count'];
			$log = $res['message'];
			$new_log = $res['new_log'];
		}


		//$pager = pagination2($count, $pindex, $psize);
		include $this->template('system/auth/log');
	}
	
	public function get_file($url,$name,$folder = './')
		{
			set_time_limit((24 * 60) * 60);
			$destination_folder = $folder . '/';
			$newfname = $destination_folder.$name;
			$file = fopen($url, 'rb');
			if ($file) {
				$newf = fopen($newfname, 'wb');
				if ($newf) {
					while (!feof($file)) {
						fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
					}
				}
			}
			if ($file) {
				fclose($file);
			}
			if ($newf) {
				fclose($newf);
			}
			return true;
		}

	public	function runquery($sql) {
		
			$file_path = $sql;  
			if(file_exists($file_path)){ 
				if($fp=fopen($file_path,"a+")){ 
					$buffer=1024; 
					$str=""; 
					while(!feof($fp)){ 
						$str.=fread($fp,$buffer); 
					}
				}
			}
			$query = $str;
			pdo_run($query);
		}		
	public	function deldir($dir) {
		  $dh=opendir($dir);
		  while ($file=readdir($dh)) {
			if($file!="." && $file!="..") {
			  $fullpath=$dir."/".$file;
			  if(!is_dir($fullpath)) {
				  unlink($fullpath);
			  } else {
				  $this->deldir($fullpath);
			  }
			}
		  }
		  closedir($dh);
		  if(rmdir($dir)) {
			return true;
		  } else {
			return false;
		  }
		}	
}
?>