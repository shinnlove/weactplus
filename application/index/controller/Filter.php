<?php

namespace app\index\controller;

use think\Controller;
use think\Request;

/**
 * 请求头过滤解析器。
 *
 * Class Filter
 * @package app\index\controller
 */
class Filter extends Controller {

    /**
     * sso解析请求头页面。
     *
     * 尝试访问下面网址试试看：
     * http://localhost/weactplus/public/index/filter/sso?user_id=123456&name=shinnlove&nickname=%E9%87%91%E5%8D%87&age=24&redirect_uri=http%3A%2F%2Fwww.we-act.cn%2Fweactplus%2Fproduct%2Fdetail%2F100
     *
     * @return mixed
     */
    public function sso() {

        $request = Request::instance();

        $this->handle_request_param($request);

        $this->basic_request_info($request);

        $header = $request->header();
        $this->handle_header($header);

        $cookie = $request->cookie();
        $this->handle_cookie($cookie);

        $session = $request->session();
        $this->handle_session($session);

        $server = $request->server();
        $this->handler_server($server);

        return $this->fetch();
    }

    /**
     * 处理request请求的入参。
     *
     * @param $request
     */
    public function handle_request_param($request) {
        dump("===========handle_request_param=========");
        $request_params = $request->param();
        $id = $request->param('user_id');
        $name = $request->param('name');

        dump($request_params);
        dump($id);
        dump($name);
    }

    /**
     * 请求头是比较重要的信息，需要对协议等进行解析，甚至包含cookie值。
     *
     * @param $header
     */
    public function handle_header($header) {
        dump("===========handle_header=========");
        dump($header);

        $host = $header['host'];
        $connection = $header['connection'];
        $upgrade_insecure_requests = $header['upgrade-insecure-requests'];
        $user_agent = $header['user-agent'];
        $accept = $header['accept'];
        $accept_encoding = $header['accept-encoding'];
        $host = $header['accept-language'];
        $cookie = $header['cookie'];

        $cookie_info_list = preg_split('/[; ]+/s', $cookie);
        dump($cookie_info_list);

        // 对其中某一项进行切割
        $cookie_info_pair = preg_split('/[=]+/s', $cookie_info_list[0]);
        $cookie_info_key = $cookie_info_pair[0];
        $cookie_info_value = $cookie_info_pair[1];

        dump($cookie_info_key);
        dump($cookie_info_value);
    }

    /**
     * cookie是专门针对header请求头中的cookie字段进行的解析。
     *
     * 将一整条cookie按; 进行切割，而后把key=>value放进数组中返回。
     *
     * @param $cookie
     */
    public function handle_cookie($cookie) {
        dump("===========handle_cookie=========");
        dump($cookie);
    }

    /**
     * 只有当写入服务器才会有session信息打印出来。
     *
     * @param $session
     */
    public function handle_session($session) {
        dump("===========handle_session=========");
        dump($session);
    }

    /**
     * server全局信息，有些字段比较敏感。
     *
     * @param $server
     */
    public function handler_server($server) {
        dump("===========handler_server=========");
        dump($server);

        // 访问框架地址
        $request_uri = $server['REQUEST_URI'];

        // 通信的协议1.0还是1.1
        $server_protocol = $server['SERVER_PROTOCOL'];

        // HTTP请求类型GET/POST
        $request_method = $server['REQUEST_METHOD'];

        // Response Status 200 ok
        $redirect_status = $server['REDIRECT_STATUS'];

        // header host
        $http_host = $server['HTTP_HOST'];

        // header connection
        $http_connection = $server['HTTP_CONNECTION'];

        // header cache-control
        $http_cache_control = isset($server['HTTP_CACHE_CONTROL']) ? $server['HTTP_CACHE_CONTROL'] : "";

        // header user-agent
        $http_user_agent = $server['HTTP_USER_AGENT'];

        // response header accept
        $http_accept = $server['HTTP_ACCEPT'];

        // response header accept-encoding
        $http_accept_encoding = $server['HTTP_ACCEPT_ENCODING'];

        // response header accept-language
        $http_accept_language = $server['HTTP_ACCEPT_LANGUAGE'];

        // response header cookie
        $http_cookie = $server['HTTP_COOKIE'];

        // server os、web-container、php-version
        $server_software = $server['SERVER_SOFTWARE'];

        // help self to do some report for this machine
        $server_name = $server['SERVER_NAME'];
        $server_addr = $server['SERVER_ADDR'];
        $server_port = $server['SERVER_PORT'];
        $remote_addr = $server['REMOTE_ADDR'];

    }

    /**
     * tp框架的一些默认的模板。
     *
     * @param $request
     */
    public function basic_request_info($request) {
        dump("===========basic_request_info=========");
        // 获取当前域名
        echo 'domain: ' . $request->domain() . '<br/>';

        // 获取当前入口文件
        echo 'file: ' . $request->baseFile() . '<br/>';

        // 获取当前URL地址 不含域名
        echo 'url: ' . $request->url() . '<br/>';

        // 获取包含域名的完整URL地址
        echo 'url with domain: ' . $request->url(true) . '<br/>';

        // 获取当前URL地址 不含QUERY_STRING
        echo 'url without query: ' . $request->baseUrl() . '<br/>';

        // 获取URL访问的ROOT地址
        echo 'root:' . $request->root() . '<br/>';

        // 获取URL访问的ROOT地址
        echo 'root with domain: ' . $request->root(true) . '<br/>';

        // 获取URL地址中的PATH_INFO信息
        echo 'pathinfo: ' . $request->pathinfo() . '<br/>';

        // 获取URL地址中的PATH_INFO信息 不含后缀
        echo 'pathinfo: ' . $request->path() . '<br/>';

        // 获取URL地址中的后缀信息
        echo 'ext: ' . $request->ext() . '<br/>';
    }

}

?>

