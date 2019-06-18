<?php

namespace app\mall\controller;


class Index extends MobileGuest
{
    public function index() {
        $tpl_indexpath = "mall_index_index";
//        $tpl_indexpath = strtolower ( GROUP_NAME . '_' . MODULE_NAME . '_' . ACTION_NAME ); // strtolowerPHP自带函数，转为小写,ThinkPHP自带功能，自动获取“当前分组/控制器名/Action函数名”
        $navinfo = array ( 'e_id' => $this->einfo ['e_id'] );
        $mobilecommon = controller("MobileCommon");
        $pageinfo = $mobilecommon->selectTpl ( $navinfo, $tpl_indexpath ); // 多态查找模板
        $this->assign("pageinfo", $pageinfo);
        unset ( $mobilecommon ); // 注销此对象释放内存
        return $this->fetch ( $this->pageinfo ['template_realpath'] );
    }
}
