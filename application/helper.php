<?php

function p($array) {
    dump($array, 1, '<pre>', 0);
}

//发送邮件
function send_mail($address, $title, $message) {
    vendor('PHPMailer.class#phpmailer');        //导入ThinkPHP的模板

    $mail = new PHPMailer();

    // 设置PHPMailer使用SMTP服务器发送Email
    $mail->IsSMTP();

    // 设置邮件的字符编码，若不指定，则为'UTF-8'
    $mail->CharSet = 'UTF-8';

    // 添加收件人地址，可以多次使用来添加多个收件人
    $mail->AddAddress($address);

    // 设置邮件正文
    $mail->Body = $message;

    // 设置邮件头的From字段。
    $mail->From = "zhaochensheng1218@163.com";

    // 设置发件人名字
    $mail->FromName = 'WeAct';

    // 设置邮件标题
    $mail->Subject = $title;

    // 设置SMTP服务器。
    $mail->Host = "smtp.163.com";

    // 设置为"需要验证"
    $mail->SMTPAuth = true;

    // 设置用户名和密码。
    $mail->Username = "zhaochensheng1218@163.com";
    $mail->Password = "19881218";

    // 发送邮件。
    return ($mail->Send());
}

/**
 * 检查登录后应该跳转的地址。
 * @param string $refererURL 登录前的URL
 * @return string $refererURL    检查后应该确实跳转的URL
 */
function afterloginURL($refererURL = '') {
    $regStart = strpos($refererURL, 'weact');
    $regLogin = strpos($refererURL, 'customerLogin');                // 匹配登录页面
    $regRegister = strpos($refererURL, 'customerRegister');            // 匹配注册页面
    if ($regLogin || $regRegister) {
        if ($regLogin) {
            $regLoginURL = substr($refererURL, $regStart, $regLogin - $regStart + 13);            // 客户登录页面地址
            $loginURL = 'weact/Home/GuestHandle/customerLogin';            // 登录页面
            if ($regLoginURL == $loginURL) {
                $refererURL = '';                                            // 如果是在登录页面或者注册页面，一律跳到会员中心（置空由前台判断）
            }
        } else if ($regRegister) {
            $regRegisterURL = substr($refererURL, $regStart, $regRegister - $regStart + 16);    // 客户注册页面地址
            $registerURL = 'weact/Home/GuestHandle/customerRegister';        // 注册页面
            if ($regRegisterURL == $registerURL) {
                $refererURL = '';                                            // 如果是在登录页面或者注册页面，一律跳到会员中心（置空由前台判断）
            }
        }
    }
    return $refererURL;
}

/**
 * 生成随机字符串
 *
 * @param int $length 要生成的随机字符串长度
 * @param string $type 随机码类型：0，数字+大小写字母；1，数字；2，小写字母；3，大写字母；4，特殊字符；-1，数字+大小写字母+特殊字符
 * @return string
 */
function rand_code($length = 5, $type = 0) {
    $arr = array(1 => "0123456789", 2 => "abcdefghijklmnopqrstuvwxyz", 3 => "ABCDEFGHIJKLMNOPQRSTUVWXYZ", 4 => "~@#$%^&*(){}[]|");
    if ($type == 0) {
        array_pop($arr);
        $string = implode("", $arr);
    } elseif ($type == "-1") {
        $string = implode("", $arr);
    } else {
        $string = $arr[$type];
    }
    $count = strlen($string) - 1;
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $string[rand(0, $count)];
    }
    return $code;
}

/*
 * Author:luozegang
* 生成随机码，以前用来作为主键，但是数据库改版后采用md5随机法，此函数已经很少用。
*/
function generate_uniqueid() {
    $currentdate = date('YmdHms');
    $randdata = rand_code(4, 1);
    return $currentdate . $randdata;
}

function unescape($str) {
    $ret = '';
    $len = strlen($str);
    for ($i = 0; $i < $len; $i++) {
        if ($str[$i] == '%' && $str[$i + 1] == 'u') {
            $val = hexdec(substr($str, $i + 2, 4));
            if ($val < 0x7f) $ret .= chr($val);
            else if ($val < 0x800) $ret .= chr(0xc0 | ($val >> 6)) . chr(0x80 | ($val & 0x3f));
            else $ret .= chr(0xe0 | ($val >> 12)) . chr(0x80 | (($val >> 6) & 0x3f)) . chr(0x80 | ($val & 0x3f));
            $i += 5;
        } else if ($str[$i] == '%') {
            $ret .= urldecode(substr($str, $i, 3));
            $i += 2;
        } else $ret .= $str[$i];
    }
    return $ret;
}


/*--------------------------以下为ThinkPHP提供的微信SDK-------添加时间：20140606，版本号：version 747------------------------*/

/**
 * 验证输入的是否是手机号
 */
function isMobile($mobile) {
    return preg_match("/^(?:13\d|14\d|15\d|18[0123456789])-?\d{5}(\d{3}|\*{3})$/", $mobile);
}

/**
 * 验证输入的是否是电子邮件格式
 */
function isEmail($email) {
    return strlen($email) > 6 && preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email);
}

/**
 * 发送HTTP请求方法，目前只支持CURL发送请求
 * @param  string $url 请求URL
 * @param  array $params 请求参数
 * @param  string $method 请求方法GET/POST
 * @return array  $data   响应数据
 */
function http($url, $params, $method = 'GET', $header = array(), $multi = false) {
    $opts = array(
        CURLOPT_TIMEOUT => 30,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => $header
    );

    /* 根据请求类型设置特定参数 */
    switch (strtoupper($method)) {
        case 'GET':
            $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
            break;
        case 'POST':
            //判断是否传输文件
            $params = $multi ? $params : http_build_query($params);
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $params;
            break;
        default:
            throw new Exception('不支持的请求方式！');
    }

    /* 初始化并执行curl请求 */
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) throw new Exception('请求发生错误：' . $error);
    return $data;
}

/**
 * 从微信服务器下载多媒体文件的curl封装。
 * @param string $url url地址
 * @param unknown $params 参数
 * @return array    返回多媒体信息数组
 */
function downloadWeixinFile($url = '', $params = array()) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_NOBODY, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $package = curl_exec($ch);
    $httpinfo = curl_getinfo($ch);
    curl_close($ch);
    $media = array_merge(array('header' => $httpinfo), array('body' => $package));
    return $media;
}

/**
 * 不转义中文字符和\/的 json 编码方法
 * @param array $arr 待编码数组
 * @return string
 */
function jsencode($arr) {
    $str = str_replace("\\/", "/", json_encode($arr));
    $search = "#\\\u([0-9a-f]+)#ie";

    if (strpos(strtoupper(PHP_OS), 'WIN') === false) {
        $replace = "iconv('UCS-2BE', 'UTF-8', pack('H4', '\\1'))";//LINUX
    } else {
        $replace = "iconv('UCS-2', 'UTF-8', pack('H4', '\\1'))";//WINDOWS
    }

    return preg_replace($search, $replace, $str);
}

// 数据保存到文件
function data2file($filename, $arr = '') {
    if (is_array($arr)) {
        $con = var_export($arr, true);
        $con = "<?php\nreturn $con;\n?>";
    } else {
        $con = $arr;
        $con = "<?php\n $con;\n?>";
    }
    write_file($filename, $con);
}

/**
 * 将standard obj转成array格式的函数
 * @param standard obj $obj
 */
function objtoarr($obj) {
    $ret = array();
    foreach ($obj as $key => $value) {
        if (gettype($value) == 'array' || gettype($value) == 'object') {
            $ret[$key] = objtoarr($value);
        } else {
            $ret[$key] = $value;
        }
    }
    return $ret;
}

/**
 * 系统加密方法
 * @param string $data 要加密的字符串
 * @param string $key 加密密钥
 * @param int $expire 过期时间 单位 秒
 * @return string
 * @author winky
 */
function encrypt($data, $key = '', $expire = 0) {
    $key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = base64_encode($data);
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    $str = sprintf('%010d', $expire ? $expire + time() : 0);

    for ($i = 0; $i < $len; $i++) {
        $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1))) % 256);
    }
    return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($str));
}

/**
 * 系统解密方法
 * @param  string $data 要解密的字符串 （必须是encrypt方法加密的字符串）
 * @param  string $key 加密密钥
 * @return string
 * @author winky
 */
function decrypt($data, $key = '') {
    $key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = str_replace(array('-', '_'), array('+', '/'), $data);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    $data = base64_decode($data);
    $expire = substr($data, 0, 10);
    $data = substr($data, 10);

    if ($expire > 0 && $expire < time()) {
        return '';
    }
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = $str = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    for ($i = 0; $i < $len; $i++) {
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        } else {
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return base64_decode($str);
}

function mkdirs($dir, $mode = 0777) {
    if (is_dir($dir) || mkdir($dir, $mode)) {
        return true;
    }
    if (!mkdirs(dirname($dir), $mode)) {
        return false;
    }
    return mkdir($dir, $mode);
}

/**
 * 组装图片路径函数。
 * @param string $originalpath 形参传入要处理的图片路径
 * @param boolean $realpath 是否需要全路径，默认为false
 * @return string                返回组装完成的路径
 */
function assemblepath($originalpath = '', $realpath = FALSE) {
    $finalpath = $originalpath;
    $http = "ttp://"; // http文件头
    $https = "ttps://"; // https文件头
    if (!empty ($originalpath) && !strpos($originalpath, $http) && !strpos($originalpath, $https)) {
        $project_name = C('PROJECT_NAME');
        if (!strstr($originalpath, $project_name)) {
            if ($realpath) {
                $finalpath = C('SITE_URL') . $project_name . $finalpath;    //没找到项目名称，拼接全路径+项目名称
            } else {
                $finalpath = '/' . $project_name . $finalpath;                //没找到项目名称，拼接项目名称
            }
        } else {
            if ($realpath) $finalpath = C('DOMAIN') . $finalpath;            //已经找到项目名称，拼接全路径（$finalpath里已经包含了/weact，有斜杠）
        }
    }
    return $finalpath;
}

/*--------------------------以上为ThinkPHP提供的微信SDK-------添加时间：20140606，版本号：version 747------------------------*/

/**
 * 整型转完整日期格式
 * @param number $time 格林尼治time()时间戳
 * @param boolean $withouthms 不需要时分秒标志
 * @return string    返回格式化的日期类型
 */
function timetodate($time = 0, $withouthms = FALSE) {
    if ($withouthms) {
        return date("Y-m-d", $time);
    } else {
        return date("Y-m-d H:i:s", $time);
    }
}

/**
 * 格式化成微信支付需要的时间格式yyyyMMddHHmmss。
 * @param number $timenow
 * @return string $wechatpaydate
 */
function formatwechatpaydate($timenow = 0) {
    $wechatpaydate = '';
    if (!empty ($timenow)) {
        $year = date('Y', $timenow);
        $month = date('m', $timenow);
        $day = date('d', $timenow);
        $hour = date('H', $timenow);
        $minute = date('i', $timenow);
        $second = date('s', $timenow);
        $wechatpaydate = '' . $year . $month . $day . $hour . $minute . $second;
    }
    return $wechatpaydate;
}

/**
 * 返回今天开始时间戳
 */
function today_start() {
    return mktime(0, 0, 0, date('m'), date('d'), date('Y'));
}

/**
 * 返回今天结束时间戳
 */
function today_end() {
    return mktime(23, 59, 59, date('m'), date('d'), date('Y'));
}

/**
 * 调试专用函数，记录信息到日志。
 *
 * 这个函数改造成用log4php的方式。
 *
 * @param string|array $loginfo 要记录的日志信息
 */
function debugLog($loginfo = NULL) {
    $filepath = $_SERVER ['DOCUMENT_ROOT'] . __ROOT__ . "/WeChatLog/globaldebug/";    // 全局dubug文件夹
    $filename = "debug" . date("Ymd") . ".log";                                    // 文件名按天存放
    globalLog($filepath, $filename, json_encode($loginfo)); // 记录文件信息
}

/**
 * 全局打印日志文件函数。
 *
 * 这个函数改造成用log4php的方式。
 *
 * CreateTime:2015/08/30 17:33:25.
 * @author shinnlove
 * @param string $filefolder 日志文件存放的文件夹名
 * @param string $filename 日志文件存档的文件名
 * @param string $loginfo 日志文件需要记录的信息
 */
function globalLog($filefolder = "", $filename = "", $loginfo = NULL) {
    $logsuccess = false; // 记录日志文件失败
    if (!empty ($filefolder) && !empty ($filename)) {
        // 如果文件夹路径和文件名都不空，则记录日志文件
        if (!is_dir($filefolder)) mkdirs($filefolder); // 如果没有存在文件夹，直接创建文件夹
        $fp = fopen($filefolder . $filename, "a"); // 所有权限打开这个日志文件，文件夹路径+文件名
        flock($fp, LOCK_EX);    // 锁定文件读写权限
        fwrite($fp, "全局日志记录时间：" . strftime("%Y-%m-%d %H:%M:%S", time()) . "\n" . $loginfo . "\n\n"); // 记录日志信息
        flock($fp, LOCK_UN);    // 解锁文件读写权限
        fclose($fp);            // 关闭文件句柄
        $logsuccess = true;        // 到此日志文件记录成功
    }
    return $logsuccess;
}

/**
 * 获取毫秒时间
 * @return number
 */
function microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    $currentsecond = (float)$usec + (float)$sec;
    return $currentsecond;
}

/**
 * 获取毫秒时间
 * @return number
 */
function microtime_double() {
    list($usec, $sec) = explode(" ", microtime());
    $currentsecond = (double)$usec + (double)$sec;
    return $currentsecond;
}
