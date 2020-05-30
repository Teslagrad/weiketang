<?php
/**
 * 【微擎模版备份工具】
 *  @author RubySn0w QQ：171847271
 */
defined('IN_IA') or exit('Access Denied');
include IA_ROOT . "/addons/rusn_tools/model.php";
load()->model('extension');
        load()->model('module');
        load()->model('user');
        load()->model('account');
        load()->classs('account');
        load()->func('db');
        load()->func('file');
		 
class Rusn_toolsModuleSite extends WeModuleSite
{



    public function doWebDown()
    {//自动下载文件
        global $_GPC;
        if (!empty($_GPC['module'])) {
            $path = IA_ROOT."/packback/addons/".$_GPC['module'];
            require_once("inc/web/yasuo.php");
            $yasuo = new PHPZip();
            $yasuo->ZipAndDownload($path);
        }
    }

    private function decrypt($str, $key){
        $str = $this->bind_key(base64_decode($str), $key);
        $strLen = strlen($str);
        $tmp = '';
        for($i=0; $i<$strLen; $i++){
            $tmp .= $str[$i] ^ $str[++$i];
        }
        return $tmp;
    }
    private function bind_key($str, $key){
        $encrypt_key = md5($key);
    
        $tmp = '';
        $strLen = strlen($str);
        for($i=0, $j=0; $i<$strLen; $i++, $j++){
            $j = $j == 32 ? 0 : $j;
            $tmp .= $str[$i] ^ $encrypt_key[$j];
        }
        return $tmp;
    }


}
