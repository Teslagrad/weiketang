<?php
/**
 * 【微擎模版备份工具】
 *  @author RubySn0w QQ：171847271
 */
defined('IN_IA') or exit('Access Denied');
class Rusn_toolsModule extends WeModule {
    //配置此函数时，PC应用入口出现问题无法进入，可能有兼容问题
	/*public function welcomeDisplay($menus = array()) {
		//TODO
	    include $this->template('web/home');
	}*/
	public function settingsDisplay($settings) {
	           global $_W, $_GPC;
        include $this->template('web/setting');
    }
    public function fieldsFormDisplay($rid = 0) {
	    //TODO
    }
    public function fieldsFormValidate($rid = 0) {
        //TODO
    }
    public function fieldsFormSubmit($rid) {
        //TODO
    }
    public function ruleDeleted($rid) {
        //TODO
    }
}