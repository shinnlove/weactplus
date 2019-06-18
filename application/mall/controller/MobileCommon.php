<?php

namespace app\mall\controller;

use think\controller;

import ( 'Class.Model.Home.TplManage.commontpl', APP_PATH, '.php' ); // 载入公共类模板
import ( 'Class.Model.Home.TplManage.indextpl', APP_PATH, '.php' ); // 载入主页模板类
import ( 'Class.Model.Home.TplManage.microshoptpl', APP_PATH, '.php' ); // 载入微商城模板类
import ( 'Class.Model.Home.TplManage.productlisttpl', APP_PATH, '.php' ); // 载入产品陈列模板类
import ( 'Class.Model.Home.TplManage.navlisttpl', APP_PATH, '.php' ); // 载入信息导航模板类
import ( 'Class.Model.Home.TplManage.simpleinfotpl', APP_PATH, '.php' ); // 载入信息详情模板类
import ( 'Class.Model.Home.TplManage.productshowtpl', APP_PATH, '.php' ); // 载入商品详情类模板
import ( 'Class.Model.Home.TplManage.orderinfotpl', APP_PATH, '.php' ); // 载入订单详情类模板

//import ( 'Class.Model.Home.TplManage.membertpl', APP_PATH, '.php' ); // 载入会员中心类模板
class MobileCommon extends Controller {
    /**
     * 定义模板托管映射组。
     * @var array $reflect_tpltype 模板托管映射路径
     */
    var $reflect_tpltype = array (
        'home_index_index' => array ( 'tpl_type' => 1 ), //手机端主页模板类型与处理函数映射
        'home_customerview_shownavlist1' => array ( 'tpl_type' => 4 ), //手机端信息二级导航模板类型与处理函数映射
        'home_customerview_shownavlist2' => array ( 'tpl_type' => 2 ), //手机端微商城主页（商品二级导航）模板类型与处理函数映射
        'home_customerview_shownavlist5' => array ( 'tpl_type' => 2 ), //非服饰类的商品也映射到微商城（商品二级导航）模板类型与处理函数映射
        'home_customerview_showsimpleinfo' => array ( 'tpl_type' => 5 ), //手机端信息详情模板类型与处理函数映射
        'home_productview_productlist' => array ( 'tpl_type' => 3 ), //手机端产品陈列模板类型与处理函数映射
        'home_productview_productshow' => array ( 'tpl_type' => 6 ), // 产品展示页面的模板映射
        // 此处购物车页面（如果添加产品详情参数的话，还要一个7的页面，建议购物车页面是8）
        'home_orderview_orderprehandle' => array ( 'tpl_type' => 9 ), // 订单预处理
        'home_orderview_orderinfo' => array ( 'tpl_type' => 10 ) // 订单详情
    );

    /**
     * 映射模板类具体名称的时候，把模板类型和模板名称以字符串方式拼起来。
     * @var array $reflect_tplname
     */
    var $reflect_tplname = array (
        '1index' => 'NineIndexTpl', // 九宫格幻灯片模板
        '1indexFourLuxury' => 'FourLuxuryIndexTpl', // 四格奢华版模板
        '1indexNineLuxury' => 'NineLuxuryIndexTpl', // 九宫格国际版模板
        '1indexFour' => 'FourIndexTpl', // 四格国际版
        '2showNavList' => 'ListMicroShopTpl', // 列表式微商城模板
        '2microShop' => 'SquareMicroShopTpl', // 区块式微商城模板
        '2microShop2' => 'BannerMicroShopTpl', // 横条式微商城模板
        '2microShop3' => 'TimeMicroShopTpl', // 时间轴微商城模板
        '3productList' => 'WindowExpoProTpl',
        '3productList2' => 'DoubleWaterfallProTpl',
        '3productList3' => 'AlbumWaterfallProTpl',
        '3productList4' => 'TimeAxisProTpl',
        '4showNavList' => 'InfoNavListTpl',
        '4showNavList2' => 'BannerNavListTpl',
        '5showSimpleInfo' => 'GraphicInfoTpl',
        '5showSimpleInfo2' => 'BannerInfoTpl',
        '5showSimpleInfo3' => 'PictureInfoTpl',
        '6productShow' => 'RecProductShowTpl',
        '6productTMallShow' => 'TMallProductShowTpl', // 天猫式模板详情映射
        // 此处购物车页面
        '9orderPreHandle' => 'OriginalPreOrderTpl', // 原始的订单预处理页面
        '9orderPreHandle2' => 'LuxuryPreOrderTpl', // 奢华版订单预处理页面
        '10orderInfo' => 'OriginalOrderInfoTpl', // 原始的订单信息页面
        '10orderInfo2' => 'LuxuryOrderInfoTpl', // 奢华版订单信息页面
    );

    /**
     * 传入导航信息选择公共模板信息。
     * @param array $navinfo 商家导航信息
     * @property string $e_id 商家编号
     * @return array $pageinfo 页面信息
     */
    public function selectCommonTpl($navinfo = NULL) {
        $commontpl = new CommonTpl ( $navinfo ); // 创建公共类模板对象
        $pageinfo = $commontpl->getInitData(); // 初始化模板的值
        return $pageinfo;
    }

    /**
     * Author：赵臣升。
     * CreateTime：2014/07/15 07:35:25.
     *
     * @param null $navinfo             导航信息
     * @param string $tpl_indexpath     导航编号（主页模板选择没有该信息）
     * @param int $secondnav            区分二级导航是微商城还是信息二级导航，1信息导航，2服饰商品导航，3超链接，4餐饮，5其他品类
     * @return array
     */
    public function selectTpl($navinfo = NULL, $tpl_indexpath = '', $secondnav = 0) {
        $tpl_type = 0; // 设置全局选择模板变量
        $tplinfo = array (); // 当前的选择的模板信息
        $tplobjname = ''; // 模板对象名称
        $tplrenderdata = array(); // 模板最终渲染完成的数据（因为getInitData+tplinfo才是最终数据）

        // Step1：判断选择模板类型
        if($secondnav) {
            $tpl_type = $this->reflect_tpltype [$tpl_indexpath . $secondnav] ['tpl_type']; // 映射二级模板
        } else {
            $tpl_type = $this->reflect_tpltype [$tpl_indexpath] ['tpl_type']; // 映射主页或三级模板
        }

        // Step2：查询personalTailor处理个性化定制模板信息（并兼容之前的程序）
        $tplmap = array (
            'e_id' => $navinfo ['e_id'], // 当前商家编号
            'template_type' => $tpl_type, // 当前模板类型（主要区分二级信息导航和微商城）
            'template_indexpath' => $tpl_indexpath, // 当前模板索引
            'is_del' => 0  // 没有被删除的
        );
        $tplresult = M ( 'personaltailor' )->where ( $tplmap )->order( 'customized_time' )->select (); // 寻找私人定制模板表里的模板信息（查的是personaltailor），选用select找到可能存在的多个定制（主页除外）

        // Step3：根据$tplresult的值情况，选择一个正确的模板（有定制与默认，多模版与个性模板）
        if ($tplresult) {
            $tplinfo = $tplresult [0]; // 商家定制过模板，就直接赋值；同时也适用于$num == 1的情形赋值，即只有一个模板

            $num = count ( $tplresult ); // 统计该类模板定制数量（进到这层循环$num必然大于0，至少有一个模板存在）
            if ($num > 1 && $tpl_type != 1) {
                // 如果有两个以上的模板定制（主页除外），找寻该导航是否有特殊定制
                $navcheck = array (
                    'e_id' => $navinfo ['e_id'],
                    'nav_id' => $navinfo ['nav_id'],
                    'is_del' => 0
                );
                $checkresult = M ( 'navigation' )->where ( $navcheck )->find (); // 找出导航全部信息
                if (! empty ( $checkresult ['display_tailor'] )) {
                    for ($i = 0; $i < $num; $i ++) {
                        if ($tplresult [$i] ['tailor_id'] == $checkresult ['display_tailor']) {
                            $tplinfo = $tplresult [$i]; // 如果找到定制模板，将定制模板信息给$tplinfo
                            break;
                        }
                    }
                }
            }
        } else {
            $originmap = array (
                'template_type' => $tpl_type, // 当前模板类型（主要区分二级信息导航和微商城）
                'template_indexpath' => $tpl_indexpath, // 当前模板索引
                'default_selected' => 1, // 没有定制过模板，使用默认的（必须加）
                'is_del' => 0  // 没有被删除的
            );
            $tplinfo = M ( 'templatetailor' )->where ( $originmap )->find (); // 找出默认模板并赋值
        }

        $tplobjname = $this->reflect_tplname [ $tplinfo ['template_type'] . $tplinfo ['template_realpath'] ]; // 映射模板类名称

        $tpl = new $tplobjname ( $navinfo ); // 得到模板类名称后建立模板类对象，传入导航信息
        $tplrenderdata = $tpl->getInitData(); // 模板数据放入渲染数据中
        $tplrenderdata ['template_title'] = $tplinfo ['template_title']; // 增加渲染数据template_title
        $tplrenderdata ['template_banner'] = assemblepath ( $tplinfo ['template_banner'] ); // 增加渲染数据template_banner
        $tplrenderdata ['template_realpath'] = $tplinfo ['template_realpath']; // 增加渲染数据template_realpath
        $tplrenderdata ['template_indexpath'] = $tplinfo ['template_indexpath']; // 增加渲染数据template_indexpath
        if (! empty ( $tplresult ) && ! empty ( $tplresult [0] ['customized_css'] )) {
            $tplrenderdata ['csscontent'] = $this->cssCustomize ( $tplresult [0] ['customized_css'], $navinfo ['e_id'] ); // 特别注意$tplresult这是个二维数组
        }
        return $tplrenderdata;
    }

    /**
     * 定制css样式函数。
     * @param string $tailor_id 自定义css模板编号
     * @param string $e_id 商家编号
     * @param number $type css模板种类
     * @return string $css_content css类型
     */
    public function cssCustomize($tailor_id = '', $e_id = '', $type = 1) {
        $cssmap = array (
            'tailor_id' => $tailor_id,
            'e_id' => $e_id,
            'template_type' => $type, // 1是二级导航类型css，2是按钮css；这里默认是1类css。
            'is_del' => 0
        );
        $cssresult = M ( 'csstailor' )->where ( $cssmap )->find (); // 若开放按钮css，这里还要做细节处理（查询csstemplate表）
        return $cssresult ['css_content'];
    }

    /**
     * 手机端分享页面的记录函数。
     */
    public function shareRecord(){
        $sharedata = array(
            'share_id' => md5 (uniqid (rand(), true)),
            'e_id' => I('e_id'),
            'link_url' => I('link'),
            'openid' => I('openid'),
            'share_time' => date('YmdHms')
        );
        $sharetable = M('share');
        $shareresult = $sharetable->data($sharedata)->add();
        if($shareresult){
            $this->ajaxReturn( array('status' => 1, 'msg' => '分享成功!') );
        }else{
            $this->ajaxReturn( array('status' => 0, 'msg' => '分享失败!') );
        }
    }

}
?>