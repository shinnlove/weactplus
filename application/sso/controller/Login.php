<?php

namespace app\sso\controller;

use think\Controller;
use think\Request;
use think\View;
use think\Db;
use think\Exception;


class Login extends Controller {

    /**
     * sso授权登录页面。
     */
    public function auth() {
        // 请求实例
        $request = Request::instance();
        $params = $request->param();

        // sso请求参数
        $app_name = $params['app_name'];
        $mch_id = $params['mch_id'];
        $redirect_uri = $params['redirect_uri'];

        // 放到模板中
        $view = new View();
        $view->app_name = $app_name;
        $view->mch_id = $mch_id;
        $view->redirect_uri = $redirect_uri;

        return $view->fetch();
    }

    /**
     * sso授权校验请求，如果登录成功则生成session，并且存入缓存中，30分钟有效。
     *
     * @return string
     */
    public function checkin() {
        // 请求实例
        $request = Request::instance();
        $params = $request->param();

        // sso登录参数
        $app_name = $params['app_name'];
        $mch_id = $params['mch_id'];
        $redirect_uri = $params['redirect_uri'];
        $username = $params['username'];
        $password = $params['password'];

        // 响应信息
        $response = array(
            'errCode' => 10001,
            'errMsg' => "账号或密码错误"
        );

        // 请求用户登录
        $sql = 'select * from t_customerinfo where e_id = "' . $mch_id . '" and account="' . $username . '" and password="' . md5($password) . '" and is_del = 0';
        $login_result = Db::query($sql);

        if (count($login_result) > 0) {
            // 用户信息
            $user_info = $login_result[0];
            $user_id = $user_info['customer_id'];

            // TODO：sso-token生成算法有待优化
            $sso_token = md5 ( uniqid ( rand (), true ) );
            $sso_key = $app_name . "." . $sso_token . "." . $mch_id;
            $sso_value = $user_id;

            // TODO：将app_name、mch_id、sso_token -> user_id 存入redis缓存中，30分钟有效，标记登录过
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
            $redis->set($sso_key, $sso_value);

            $src_url = "";
            // TODO：正则校验$redirect_uri中有没有参数，有参数的话则用&拼接，否则就直接?拼接。这里先简单处理下。
            if (strpos($redirect_uri, "?")) {
                $src_url = $redirect_uri . "&sso_token=" . $sso_token;
            } else {
                $src_url = $redirect_uri . "?sso_token=" . $sso_token;
            }

            // 登录成功
            $response['errCode'] = 0;
            $response['errMsg'] = "ok";
            // 跳转原来系统
            $response['redirect_uri'] = $src_url;
        }

        // 响应给前端
        return json($response);
    }

}

