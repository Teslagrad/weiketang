<?php
checkFounder();
global $_W, $_GPC;
$sql = 'SELECT * FROM ' . tablename('modules') . ' WHERE `type` <> :type and `name` <> :name';
$modules = pdo_fetchall($sql, array(':type' => 'system', ':name' => 'rusn_tools'), 'name');
$sn = date("YmdHis");
$temppath = IA_ROOT . '/data/tpl/temp/';
if (is_dir($temppath)) {
    removeDir($temppath);
}
$tables = pdo_fetchall('show tables');
foreach ($tables as &$vale) {
    $vale = array_pop($vale);
}
/* 将数组分页显示，

$pageindex = max(intval($_GPC['page']), 1);
$pagesize = 20;
$total = count($tables);

$pager = pagination($total, $pageindex, $pagesize);
$tables = page($tables,$pagesize,$pageindex); 

function page($array,$pagesize,$current){
$_return=array();
$total=ceil(Count($array)/$pagesize);//求总页数
$prev=(($current-1)<=0 ? "1":($current-1));//确定上一页，如果当前页是第一页，点击显示第一页
$next=(($current+1)>=$total ? $total:$current+1);//确定下一页，如果当前页是最后一页，点击下页显示最后一页
$current=($current>($total)?($total):$current);//当前页如果大于总页数，当前页为最后一页
$start=($current-1)*$pagesize;//分页显示时，应该从多少条信息开始读取
for($i=$start;$i<($start+$pagesize);$i++){
array_push($_return,$array[$i]);//将该显示的信息放入数组 $_return 中
}
$pagearray=$_return;
return $pagearray;
}

*/
if (isset($_GPC['checkmodule']) && $_GPC['checkmodule'] == '1') {
    $module_name = $_GPC['name'];
    $module_sql = $_GPC['sql'];
    if (empty($module_name)) {
        exit(json_encode(array('errcode' => '404')));
    }
    $res = array();
    $res['errcode'] = 0;
    $res['name'] = $_GPC['name'];
    $tablenames = pdo_fetchall("SHOW TABLES LIKE :tablename", array(':tablename' => '%' . $module_name . '%'));
    if (empty($tablenames)) {

            $res['errcode'] = 1;
       
    }

    exit(json_encode($res));
}
if (checksubmit()) {
    set_time_limit(60);
    checkFounder();
    $name = trim($_GPC['mname']);
    if (empty($name)) {
        message('请选择模块!', '', 'error');
    } else {
		$manifest = array();
        $type = trim($_GPC['type']);
        $save = trim($_GPC['save']);
        $sql = $_GPC['tables'];
        $add = $_GPC['add'];
        $module = pdo_get('modules', array('name' => $name));
        $module['subscribes'] = iunserializer($module['subscribes']);
        $module['handles'] = iunserializer($module['handles']);
        $module['permissions'] = iunserializer($module['permissions']);
        $bindings = pdo_fetchall('SELECT * FROM ' . tablename('modules_bindings') . ' WHERE module = :module', array(':module' => $name));
		$pluginexists = pdo_getall ('modules_plugin', array ('main_module' => $name ), array (), 'name');
		if($pluginexists){
			$manifest['plugin'] = array_keys ($pluginexists);
		}
		$plugin_main = pdo_getcolumn('modules_plugin', array ('name' => $name ), 'main_module', $limit=1);
		if($plugin_main){
			$manifest['plugin_main'] = $plugin_main;
		}
        
      //  $num = 0;
        if (!empty($sql)) {
            foreach ($sql as $row) {
				if($row !== 'on'){
                $n = substr ($row, strlen ($_W['config']['db']['tablepre']));
				if($type == '2'){
					$module['tables'][] = db_table_schema_ru(pdo(), $n);
				}
				$up_temp = db_table_create_upgrade($n);
                $uninstall .= "DROP TABLE IF EXISTS `" . $row . "`;\r\n";
                //$module['tables'][$n] = base64_encode(iserializer(db_table_schema(pdo(), $n)));
                
                $temp = db_table_schemas($row);
                $temp = str_replace('DROP TABLE IF EXISTS ' . $row . ';', '', $temp);
                $temp = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $temp);
                $table_create .= $temp;
				$upgrade_create .= $up_temp;
                unset($temp,$n,$up_temp);
               // $num++;
				}
            }
        }
		if($module['tables']){
		$schemas = iserializer($module['tables']);
       }
        if (!empty($add)) {
            foreach ($add as $row) {
                $insert .= db_table_insert_sql1($row);
            }
        }
        /* 	function db_table_schemas($table) {
            	$dump = "DROP TABLE IF EXISTS {$table};\n";
            	$sql = "SHOW CREATE TABLE {$table}";
            	$row = pdo_fetch($sql);
            	$dump .= $row['Create Table'];
            	$dump .= ";\n\n";
            	return $dump;
            } */
        //生成建立数据表语句
        /* 	if ($module['tables']){
           		foreach($module['tables'] as $schema) {
           		$sql1 .= db_table_create_sql($schema);
           		}
           	} */
        $manifest['insert'] = $insert;
        $manifest['uninstall'] = $uninstall;
        $manifest['install'] = $table_create;
		$manifest['upgrade'] = 'ru_rpgrade.php';
        $zipname = $sn . '_' . $name . '-' . $module['version'] . '.zip';
        $manifest['user'] = 'QQ:281701592';
        $manifest['iswz'] = $_GPC['iswz'];
        $manifest['name'] = $name;
        $manifest['module'] = $module;
        $manifest['method'] = 'module.create';
        $manifest['bindings'] = iunserializer($bindings);
        $manifest['type'] = $type;
        $manifest['zipname'] = $zipname;
        $manifest['ver'] = $_GPC['ver'];
        $manifest['host'] = $_SERVER['HTTP_HOST'];
        $manifest['url'] = rtrim($_W['siteroot'], '/');
        $manifest['gz'] = function_exists('gzcompress') && function_exists('gzuncompress');
        $xml = manifest($manifest);
		$upgrade = upgrade($table_create,$upgrade_create);//print_r($upgrade); exit;
        $fpath = IA_ROOT . '/data/' . $name . '/';
        $mfname = $name . '-' . $module['version'] . '_manifest.xml';
		$upname = $manifest['upgrade'];
        $mfzip = $name . '-' . $module['version'] . '_manifest.zip';
        $mfdir = $fpath . $mfname;
		$updir = $fpath . $upname;
        $temp_xml = $temppath . $mfname;
		$temp_upgrade = $temppath . $upname;
        $temp_zip = $temppath . $mfzip;
		$schemas_name = IA_ROOT . '/data/schemas'.time().'.txt';
        //if (!file_exists($mfdir)) {
        // }
        if (empty($type)) {
            if (!empty($save)) {
                if (!is_dir($temppath)) {
                    mkdir($temppath, 0777);
                }
                file_put_contents($temp_xml, $xml);
				file_put_contents($temp_upgrade, $upgrade);
                if (file_exists($temp_xml)) {
                    $zip = new ZipArchive();
                    $zip->open($temp_zip, ZipArchive::CREATE);
                    $zip->addFile($temp_xml, $mfname);
					$zip->close();
				}
                if (file_exists($temp_upgrade)) {
                    $zip = new ZipArchive();
                    $zip->open($temp_zip, ZipArchive::CREATE);
                    $zip->addFile($temp_upgrade, $upname);
					$zip->close();
				}				
                   
                if($temp_zip)	{			   
                    header("location:" . $_W['siteroot'] . '/data/tpl/temp/' . $mfzip);
                    exit;
                } else {
                    message('无法找到文件!', '', 'error');
                }
            } else {
				if (!is_dir($fpath)) {
                    @mkdir($fpath, 0777);
                   }
                file_put_contents($mfdir, $xml);
				file_put_contents($updir, $upgrade);
                message('模块xml文件备份成功', $this->createWebUrl('apppack'), 'success');
            }
        } elseif($type== '1'){
            //file_put_contents($mfdir,$xml);
            /* 		cache_write('cloud:transtoken', authcode($zipname, 'ENCODE'));
               		$codetoken = authcode(cache_load('cloud:transtoken'), 'DECODE');
               		$manifest['token'] = $_GPC['iswz'] ? $_W['setting']['site']['token'] : $codetoken; */
            if (!empty($save)) {
                if (!is_dir($temppath)) {
                    @mkdir($temppath, 0777);
                }
				
				file_put_contents($temp_xml, $xml);
				file_put_contents($temp_upgrade, $upgrade);
                $fname = $temppath . $zipname;
                $zip = new ZipArchive();
                $zip->open($fname, ZipArchive::CREATE);
                $zip->addEmptyDir($name);
                addFileToZip(IA_ROOT . '/addons/' . $name, $zip, IA_ROOT . '/addons/');
                $zip->addFile($temp_xml, $name . '/' . $mfname);
				$zip->addFile($temp_upgrade, $name . '/' . $upname);
                $key = $_W['setting']['site']['key'];
                $codefile = IA_ROOT . '/data/module/' . md5($key . $name . 'module.php') . '.php';
                if (file_exists($codefile)) {
                    $zip->addFile($codefile, $name . '/' . md5($key . $name . 'module.php') . '.php');
                    $manifest['mf'] = md5($key . $name . 'module.php') . '.php';
                }
                $codefile = IA_ROOT . '/data/module/' . md5($key . $name . 'site.php') . '.php';
                if (file_exists($codefile)) {
                    $zip->addFile($codefile, $name . '/' . md5($key . $name . 'site.php') . '.php');
                    $manifest['sf'] = md5($key . $name . 'site.php') . '.php';
                }
                $zip->close();
                //选择本地下载后处理方法
                if (!file_exists($fname)) {
                    message('无法找到文件!', '', 'error');
                }
                ob_end_clean();
                header("Cache-Control: public");
                header("Content-Description: File Transfer");
                header('Content-disposition: attachment; filename=' . basename($fname));
                //文件名
                header("Content-Type: application/zip");
                //zip格式的
                header("Content-Transfer-Encoding: binary");
                //告诉浏览器，这是二进制文件
                header('Content-Length: ' . filesize($fname));
                //告诉浏览器，文件大小
                @readfile($fname);
                flush();
                exit;
            } else {
				if (!is_dir($fpath)) {
               @mkdir($fpath, 0777);
              }
                file_put_contents($mfdir, $xml);
				file_put_contents($updir, $upgrade);
                $fname = $fpath . $zipname;
                $zip = new ZipArchive();
                $zip->open($fname, ZipArchive::CREATE);
                $zip->addEmptyDir($name);
                addFileToZip(IA_ROOT . '/addons/' . $name, $zip, IA_ROOT . '/addons/');
                $zip->addFile($mfdir, $name . '/' . $mfname);
				$zip->addFile($updir, $name . '/' . $upname);
                $key = $_W['setting']['site']['key'];
                $codefile = IA_ROOT . '/data/module/' . md5($key . $name . 'module.php') . '.php';
                if (file_exists($codefile)) {
                    $zip->addFile($codefile, $name . '/' . md5($key . $name . 'module.php') . '.php');
                    $manifest['mf'] = md5($key . $name . 'module.php') . '.php';
                }
                $codefile = IA_ROOT . '/data/module/' . md5($key . $name . 'site.php') . '.php';
                if (file_exists($codefile)) {
                    $zip->addFile($codefile, $name . '/' . md5($key . $name . 'site.php') . '.php');
                    $manifest['sf'] = md5($key . $name . 'site.php') . '.php';
                }
                $zip->close();
                unlink($mfdir);
				unlink($updir);
                message('模块备份成功', $this->createWebUrl('apppack'), 'success');
            }
        }else{
            file_put_contents($schemas_name, $schemas);
		}
    }
}
include $this->template('web/apppack');