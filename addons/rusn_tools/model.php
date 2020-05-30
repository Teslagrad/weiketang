<?php
/**
 * 【微擎模版备份工具】
 *  @author RubySn0w QQ：171847271
 */
function db_table_insert_sql1($tablename, $start =0, $size= 0) {
	$data = '';
	$tmp = '';
	if(!empty($start)&&!empty($size))
	{
		$sql = "SELECT * FROM {$tablename} LIMIT {$start}, {$size}";		
	}else{
		$sql = "SELECT * FROM {$tablename} ";
	}
	$result = pdo_fetchall($sql);
	if (!empty($result)) {
		foreach($result  as $row) {
			//print_r($key);
			$tmp .= '(';
			foreach($row as $k => $v) {
				$value = str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $v);
				$tmp .= "'" . $value . "',";
				$tname .= $k.',';
			}
			$tmp = rtrim($tmp, ',');
			$tmp .= "),\n";
			$tmp = rtrim($tmp, ",\n");
			$tname = rtrim($tname, ",");
			$data .= "INSERT INTO {$tablename} VALUES \n{$tmp};\n";
			unset($tmp,$tname);
		}
		//echo $data;
		return $data;
	} else {
		return false ;
	}
}

function local_create_sql($schema) {
	$pieces = explode('_', $schema['charset']);
	$charset = $pieces[0];
	$engine = $schema['engine'];
	$sql = "CREATE TABLE IF NOT EXISTS `{$schema['tablename']}` (\n";
	foreach ($schema['fields'] as $value) {
		if(!empty($value['length'])) {
			$length = "({$value['length']})";
		} else {
			$length = '';
		}

		$signed  = empty($value['signed']) ? ' unsigned' : '';
		if(empty($value['null'])) {
			$null = ' NOT NULL';
		} else {
			$null = '';
		}
		if(isset($value['default'])) {
			$default = " DEFAULT '" . $value['default'] . "'";
		} else {
			$default = '';
		}
		if($value['increment']) {
			$increment = ' AUTO_INCREMENT';
		} else {
			$increment = '';
		}

		$sql .= "`{$value['name']}` {$value['type']}{$length}{$signed}{$null}{$default}{$increment},\n";
	}
	foreach ($schema['indexes'] as $value) {
		$fields = implode('`,`', $value['fields']);
		if($value['type'] == 'index') {
			$sql .= "KEY `{$value['name']}` (`{$fields}`),\n";
		}
		if($value['type'] == 'unique') {
			$sql .= "UNIQUE KEY `{$value['name']}` (`{$fields}`),\n";
		}
		if($value['type'] == 'primary') {
			$sql .= "PRIMARY KEY (`{$fields}`),\n";
		}
	}
	$sql = rtrim($sql);
	$sql = rtrim($sql, ',');

	$sql .= "\n) ENGINE=$engine DEFAULT CHARSET=$charset;\n\n";
	return $sql;
}

function db_table_create_upgrade($tablename) {
	$schema = db_table_schema_ru(pdo(), $tablename);
	//file_put_contents(IA_ROOT."/messi.txt","\n 2old:". print_r($schema, true));exit;
	$sql = "";
	foreach ($schema['fields'] as $value) {
		$piece = _db_build_field_sql_ru($value);
		$sql .= 'if(!pdo_fieldexists("'.$tablename.'", "'.$value['name'].'")) {'."\r\n".' pdo_query("ALTER TABLE ".tablename("'.$tablename.'")." ADD `'.$value["name"].'` '.$piece.';");'."\r\n}\r\n";//`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		//$sql .= "`{$value['name']}` {$piece},\n";
	}
	return $sql;
}
function upgrade($table_create,$upgrade_create){
	$str = "<?php\n".'$sql="'.$table_create.'";'."\n".'pdo_run($sql);'."\n".$upgrade_create;
	return $str;
}
function db_table_schema_ru($db, $tablename = '') {
	$result = $db->fetch("SHOW TABLE STATUS LIKE '" . trim($db->tablename($tablename), '`') . "'");
	if(empty($result) || empty($result['Create_time'])) {
		return array();
	}
	$ret['tablename'] = $result['Name'];
	$ret['charset'] = $result['Collation'];
	$ret['engine'] = $result['Engine'];
	$ret['increment'] = $result['Auto_increment'] >= 2 ? 1 : $result['Auto_increment'];
	$result = $db->fetchall("SHOW FULL COLUMNS FROM " . $db->tablename($tablename));
	//file_put_contents(IA_ROOT."/messi1.txt","\n 2old:". print_r($result, true));
	foreach($result as $value) {
		$temp = array();
		$type = explode(" ", $value['Type'], 3);
		$temp['name'] = $value['Field'];
		$pieces = explode('(', $type[0], 2);
		$temp['type'] = $pieces[0];
		$temp['length'] = rtrim($pieces[1], ')');
		$temp['null'] = $value['Null'] != 'NO';
		$temp['signed'] = empty($type[1]);
		$temp['increment'] = $value['Extra'] == 'auto_increment';
		$temp['default'] = $value['Default'];
		$temp['zerofill'] = $type[2];
		$temp['Comment'] = $value['Comment'];
		$ret['fields'][$value['Field']] = $temp;
	}
	$result = $db->fetchall("SHOW INDEX FROM " . $db->tablename($tablename));
	foreach($result as $value) {
		$ret['indexes'][$value['Key_name']]['name'] = $value['Key_name'];
		$ret['indexes'][$value['Key_name']]['type'] = ($value['Key_name'] == 'PRIMARY') ? 'primary' : ($value['Non_unique'] == 0 ? 'unique' : 'index');
		$ret['indexes'][$value['Key_name']]['fields'][] = $value['Column_name'];
	}
	return $ret;
}
function _db_build_field_sql_ru($field) {
	if(!empty($field['length'])) {
		$length = "({$field['length']})";
	} else {
		$length = '';
	}
	if (strpos(strtolower($field['type']), 'int') !== false || in_array(strtolower($field['type']) , array('decimal', 'float', 'dobule'))) {
		$signed = empty($field['signed']) ? ' unsigned' : '';
	} else {
		$signed = '';
	}
	if(empty($field['zerofill'])) {
		$zerofill = '';
	} else {
		$zerofill = ' zerofill';
	}	
	if(empty($field['null'])) {
		$null = ' NOT NULL';
	} else {
		$null = '';
	}
	if(isset($field['default'])) {
		$default = " DEFAULT '" . $field['default'] . "'";
	} else {
		$default = '';
	}
	if($field['increment']) {
		$increment = ' AUTO_INCREMENT';
	} else {
		$increment = '';
	}
	if($field['Comment']) {
		$comment = " COMMENT '" . $field['Comment'] . "'";
	} else {
		$comment = '';
	}	
	return "{$field['type']}{$length}{$signed}{$zerofill}{$null}{$default}{$increment}{$comment}";
}

function addFileToZip($path, $zip, $rpath){
	if(function_exists('scandir')){	
		foreach (scandir($path) as $afile) {
			if ($afile == '.' || $afile == '..') {
				continue;
			}
			if (is_dir($path .'/' . $afile)) {
				addFileToZip($path . '/' . $afile, $zip, $rpath);
			} else {
				$zip->addFile($path . '/' . $afile, str_replace($rpath, '', $path) . '/' . $afile);
			}
		}		
	}else{
	    $current_dir = opendir($path);  
	    while(($file = readdir($current_dir)) !== false) {
	        $sub_dir = $path . '/' . $file;  
	        if($file == '.' || $file == '..') {
	        	continue;
	        }
	        if (is_dir($sub_dir)) {
	            addFileToZip($sub_dir, $zip, $rpath);
	        }else{
				$zip->addFile($path . '/' . $file, str_replace($rpath, '', $path) . '/' . $file);
	        }
	    }		
	}
}
/*  

$filename = "./test/test.zip"; //最终生成的文件名（含路径）   
if(!file_exists($filename)){   
//重新生成文件   
    $zip = new ZipArchive();//使用本类，linux需开启zlib，windows需取消php_zip.dll前的注释   
    if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {   
        exit('无法打开文件，或者文件创建失败');
    }   
    foreach( $datalist as $val){   
        $attachfile = $attachmentDir . $val['filepath']; //获取原始文件路径   
        if(file_exists($attachfile)){   
            $zip->addFile( $attachfile , basename($attachfile));//第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下   
        }   
    }   
    $zip->close();//关闭   
}   
if(!file_exists($filename)){   
    exit("无法找到文件"); //即使创建，仍有可能失败。。。。   
}   
header("Cache-Control: public"); 
header("Content-Description: File Transfer"); 
header('Content-disposition: attachment; filename='.basename($filename)); //文件名   
header("Content-Type: application/zip"); //zip格式的   
header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件    
header('Content-Length: '. filesize($filename)); //告诉浏览器，文件大小   
@readfile($filename);

*/

function checkFounder() {
        global $_W;
			if (empty($_W['isfounder'])) {
		itoast('当前页面只有站长(创始人，founder)才能访问！', '', 'error');
	}
/*         if (!$_W['isfounder']) {
            message('当前页面只有站长(创始人，founder)才能访问！', referer(), 'error');
        } */

    }
/*删除目录函数*/
function removeDir($dirName) 
{ 
    if(! is_dir($dirName)) 
    { 
        return false; 
    } 
    $handle = @opendir($dirName); 
    while(($file = @readdir($handle)) !== false) 
    { 
        if($file != '.' && $file != '..') 
        { 
            $dir = $dirName . '/' . $file; 
            is_dir($dir) ? removeDir($dir) : @unlink($dir); 
        } 
    } 
    closedir($handle); 
      
    return rmdir($dirName) ; 
} 



function current_urli()
{
    $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
    $php_self     = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
    $path_info    = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
    $relate_url   = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : $path_info);
    return $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
}	
function manifest($m) {
    $m['module']['url'] = 'http://www.we7.cc/' ;
 	$versions = '0.8,1.0';

	$setting = $m['module']['settings'] ? 'true' : 'false';

	$subscribes = '';
	$install = '';
	if ($m['install'])
	{
		$install .= $m['install'];
	}
	if ($m['insert'])
	{
		$install .= $m['insert'];
	}
	if ($m['plugin'])
	{
		$plugins = "\r\n\t\t<plugins>";
		foreach ($m['plugin'] as $plugin){
			$plugins .="\r\n\t\t\t<item name=\"{$plugin}\" />";
		}
		$plugins .= "\r\n\t\t</plugins>";
	}else{
		$plugins = "";
	}
	if ($m['plugin_main'])
	{
		$plugin_main = "\r\n\t\t<plugin-main name=\"{$m['plugin_main']}\" />";
		
	}else{
		$plugin_main = "";
	}
	foreach($m['module']['subscribes'] as $s) {

		$subscribes .= "\r\n\t\t\t<message type=\"{$s}\" />";

	}

	$handles = '';

	foreach($m['module']['handles'] as $h) {

		$handles .= "\r\n\t\t\t<message type=\"{$h}\" />";

	}

	$rule = $m['module']['isrulefields'] ? 'true' : 'false';

	$card = $m['module']['iscard'] ? 'true' : 'false'; 
	
	$oauth = $m['module']['oauth_type'];
	
	/*bindings处理完毕*/
	$points = ext_module_bindings();
    $bindings = '';
	foreach($points as $p => $row) {
		$piece = '';
		$t = $p;
        foreach ($m['bindings'] as $k => $entry){
			if($entry['entry']==$p) {
               if ($entry['call']){
				   $call = ' call="'.$entry['call'].'"'; 
				  $piece .= " ";	 
			   }else{
				   $call = '';
				$direct = $entry['direct'] ? 'true' : 'false';
				$piece .= "\r\n\t\t\t<entry title=\"{$entry['title']}\" do=\"{$entry['do']}\" state=\"{$entry['state']}\" direct=\"{$direct}\"";

				$piece .= "/>";			   
			   }
			}

		}
	if($piece){
		$piece ="\r\n\t\t<{$t}{$call}>".$piece."\r\n\t\t</{$t}>";
		$bindings .= $piece;	
	}
	}

 	if(is_array($m['module']['permission']) && !empty($m['module']['permission'])) {

		$permissions = '';

		foreach($m['module']['permission'] as $entry) {

			$piece .= "\r\n\t\t\t<entry title=\"{$entry['title']}\" do=\"{$entry['permission']}\" />";

		}

		$permissions .= $piece;

	}
	$supports = '';
	if($m['module']['wxapp_support']==2){
		$supports .= "\r\n\t\t\t<item type=\"wxapp\" />";
		
	}
		if($m['module']['app_support']==2){
		$supports .= "\r\n\t\t\t<item type=\"app\" />";
		
	}
		if($m['module']['account_support']==2){
		$supports .= "\r\n\t\t\t<item type=\"app\" />";
		
	}	
		if($m['module']['welcome_support']==2){
		$supports .= "\r\n\t\t\t<item type=\"system_welcome\" />";
		
	}
		if($m['module']['webapp_support']==2){
		$supports .= "\r\n\t\t\t<item type=\"webapp\" />";
		
	}
		if($m['module']['phoneapp_support']==2){
		$supports .= "\r\n\t\t\t<item type=\"android\" />";
		$supports .= "\r\n\t\t\t<item type=\"ios\" />";
		
	}
		if($m['module']['xzapp_support']==2){
		$supports .= "\r\n\t\t\t<item type=\"xzapp\" />";

		
	}
		if($m['module']['aliapp_support']==2){
		$supports .= "\r\n\t\t\t<item type=\"aliapp\" />";
		
	}			
	
	$tpl = <<<TPL

<?xml version="1.0" encoding="utf-8"?>

<manifest xmlns="http://www.RubySn0w.com" versionCode="{$versions}">

	<application setting="{$setting}">
		<name><![CDATA[{$m['module']['title']}]]></name>
		<identifie><![CDATA[{$m['module']['name']}]]></identifie>
		<version><![CDATA[{$m['module']['version']}]]></version>
		<type><![CDATA[{$m['module']['type']}]]></type>
		<ability><![CDATA[{$m['module']['ability']}]]></ability>
		<description><![CDATA[{$m['module']['description']}]]></description>
		<author><![CDATA[Q裙:708900924]]></author>
		<url><![CDATA[{$m['module']['url']}]]></url>
	</application>

	<platform>
		<subscribes>{$subscribes}
		</subscribes>

		<handles>{$handles}
		</handles>

		<rule embed="{$rule}" /> 
		<card embed="{$card}" />
		<oauth type="{$oauth}" />
		
		<supports>{$supports}
		</supports>{$plugins}{$plugin_main}
	</platform>

	<bindings>{$bindings}
	</bindings>

	<permissions>{$permissions}
	</permissions>

	<install><![CDATA[{$install}]]></install>
	<uninstall><![CDATA[{$m['uninstall']}]]></uninstall>
	<upgrade><![CDATA[{$m['upgrade']}]]></upgrade>

</manifest>

TPL;

	return ltrim($tpl);

}






?>
