<?php

namespace app\sso\controller;

use think\Controller;
use think\Exception;
use think\Request;
use think\Db;

use \Logger;

/**
 * SSO Token有效时间验证。
 *
 * Class Verify
 * @package app\sso\controller
 */
class Verify extends Controller {

    /**
     * 验证sso的token是否有效接口。
     */
    public function ssoToken() {

        // TODO：包装一个日志全局类
        Logger::configure(APP_PATH . 'config.xml');
        $logger = Logger::getLogger("silk");

        // post请求参数
        $post_params = file_get_contents('php://input', 'r');
        $params = json_decode($post_params, true);

        $app_name = $params['app_name'];
        $mch_id = $params['mch_id'];
        $sso_token = $params['sso_token'];

        // 全局返回
        $response = array(
            'errCode' => 10002,
            'errMsg' => 'token不存在或已过期，请重新申请。',
            'data' => array()
        );

        // 从redis中取出值，token_value是用户id
        $sso_key = $app_name . "." . $sso_token. "." . $mch_id;
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        $token_value = $redis->get($sso_key);

        if ($token_value != null && $token_value != "") {
            // redis中存在值说明token有效
            $response['errCode'] = 0;
            $response['errMsg'] = "ok";
            $response['data']['sso_token'] = $sso_token;
            $response['data']['user_id'] = $token_value;

            // 同时尝试获取用户信息并一并返回
            $sql = "select * from t_customerinfo where customer_id = '" . $token_value . "' and e_id = '". $mch_id . "'";
            $query_result = Db::query($sql);

            if (count($query_result) > 0) {
                // 用户信息
                $customer = $query_result[0];
                $user_info = array(
                    'customer_id' => $customer['customer_id'],
                    'e_id' => $customer['e_id'],
                    'openid' => $customer['openid'],
                    'customer_name' => $customer['customer_name'],
                    'nick_name' => $customer['nick_name'],
                    'account' => $customer['account'],
                    'contact_number' => $customer['contact_number'],
                    'email' => $customer['email'],
                    'user_type' => $customer['user_type'],
                );
                $response['data']['user_info'] = $user_info;

            } else {
                // 用户已被删除
                $response['errCode'] = 10002;
                $response['errMsg'] = "user info not exist!";
            }
        }

        // log4php不能调用两句日志...
//        $logger->info("token_value=" . $token_value . ", response=" . $response);

        return json($response);
    }

}