<?php

namespace app\sso\controller;

use think\Controller;
use think\Exception;
use think\Request;

use \Logger;

/**
 * SSO Token有效时间验证。
 *
 * Class Varify
 * @package app\sso\controller
 */
class Varify extends Controller {

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
            'errMsg' => 'token无效',
            'data' => array()
        );

        // 从redis中取出值
        $sso_key = $app_name . "." . $sso_token. "." . $mch_id;
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        $token_value = $redis->get($sso_key);

        if ($token_value != null && $token_value != "") {
            $response['errCode'] = 0;
            $response['errMsg'] = "ok";
            $response['data']['sso_token'] = $sso_token;
            $response['data']['user_id'] = $token_value;
        }

        // log4php不能调用两句日志...
//        $logger->info("token_value=" . $token_value . ", response=" . $response);

        return json($response);
    }

}