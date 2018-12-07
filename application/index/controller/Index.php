<?php

namespace app\index\controller;

use think\Controller;
use think\Db;
use think\Exception;
use think\Log;
use \Logger;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


class Index extends Controller
{
    public function index()
    {
        return '<style type="text/css">*{ padding: 0; margin: 0; } .think_default_text{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:)</h1><p> ThinkPHP V5<br/><span style="font-size:30px">十年磨一剑 - 为API开发设计的高性能框架</span></p><span style="font-size:22px;">[ V5.0 版本由 <a href="http://www.qiniu.com" target="qiniu">七牛云</a> 独家赞助发布 ]</span></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="ad_bd568ce7058a1091"></think>';
    }

    public function hello($name = 'thinkphp')
    {
        $this->test2Logs();

        $this->sendMsgToRabbit();

        $this->assign('name', $name);
        return $this->fetch();
    }

    public function receive(){
        // 两个句柄
        $connection = null;
        $channel = null;

        try {

            $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
            $channel = $connection->channel();

            $channel->queue_declare('hello', false, false, false, false);

            $logger = Logger::getLogger("silk");
            $logger->info("[*] Waiting for messages. To exit press CTRL+C");

            // 定义消息处理回调钩子
            $callback = function ($msg) {
                $logger = Logger::getLogger("silk");
                // 将消息打到日志里
                $logger->info('[x] Received ' . $msg->body);
            };

            $channel->basic_consume('hello', '', false, true, false, false, $callback);

            // Our code will block while our $channel has callbacks.
            // Whenever we receive a message our $callback function will be passed the received message.
            // PHP消费MQ消息还是有点弱，就怕进程跑飞。
            $i = 0;
            while (count($channel->callbacks)) {
                $channel->wait();
                // 让消息出现
                $i ++;
                if($i > 0) break;
            }

        } catch (Exception $e) {
            $logger = Logger::getLogger("silk");
            $logger->error("接受消息发生错误" . $e);
        }

        if ($connection != null && $channel != null) {
            $channel->close();
            $connection->close();
        }

        return $this->fetch();
    }

    /**
     * 发送消息到RabbitMQ
     */
    function sendMsgToRabbit(){
        // 两个句柄
        $connection = null;
        $channel = null;

        try {
            $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
            $channel = $connection->channel();

            $channel->queue_declare('hello', false, false, false, false);

            $msg = new AMQPMessage('Hello World!');
            $channel->basic_publish($msg, '', 'hello');
        } catch (Exception $e) {
            dump("投递消息发生错误". $e);
        }

        if ($connection != null && $channel != null){
            $channel->close();
            $connection->close();
        }

        dump("[x] Sent 'Hello World!'");
    }

    public function read()
    {
        # 被误用的优惠券
        $coupon_missed = "select 
                                supplier_id, new_value 
                                    from opt_log ol 
                                    where ol.new_value like '%to_order%' 
                                        and ol.new_value not like '%\"from_order\": null,%' 
                                        and ol.new_value not like '%\"to_order\": 0%' 
                                        and ol.old_value not like '%\"to_order\": 0%' 
                                        and ol.create_time > '2018-11-07 15:55:54'";

        $order_sql = "select o.id as `order_id`,
                            d.release_user_id as `supplier_id`,
                            d.customer_id,
                            d.customer_name
                            from orders o
                                inner join demand d
                                    on o.demand_id = d.id
                            where o.id = ";

        $stat_sql = "select u.id as `user_id`, 
		ur.ref_id as `advertiser_id`,
		ur.core_user_id,
		ur.advertiser_name,
		count(vip.supplier_id) as flush_count
	from user u
	inner join user_ref ur
		on u.id = ur.user_id
			inner join (
				select supplier_id
					from opt_log ol
				where ol.new_value like '%to_order%' 
					# 过滤掉手动发放的
					and ol.new_value not like '%\"from_order\": null,%'
					# 更新优惠券使用字段
					and ol.new_value not like '%\"to_order\": 0%'
					and ol.old_value not like '%\"to_order\": 0%' 
					# 版本上线后
					and ol.create_time > '2018-11-07 15:55:54' 
			) vip
			on u.id = vip.supplier_id
	where user_id = ";

        $export_info = array();

        // Step1：查询损失的优惠券
        $data = Db::query($coupon_missed);

        $len = count($data);
        dump($len);
        for ($i = 0; $i < $len; $i++) {
            // 重要，供应商id
            $supplier_id = $data[$i]['supplier_id'];

            $update_info = json_decode($data[$i]['new_value']);
            $order_id = $update_info->to_order; // 注意json解析后object用->访问属性

            // Step2：where限定条件查询订单和需求
            $order_sql_exec = $order_sql . "'" . $order_id . "'";

            $order_info = Db::query($order_sql_exec);

            // 重要，客户id和name、和关联的supplier_id
            $customer_id = $order_info[0]['customer_id'];
            $customer_name = $order_info[0]['customer_name'];

            // Step3：查询这个代理商统计信息
            $stat_sql_exec = $stat_sql . "'" . $supplier_id . "' group by u.id order by flush_count desc";

            $stat_info = Db::query($stat_sql_exec);

            // 关键字段
            $user_id = $stat_info[0]['user_id'];
            $advertiser_id = $stat_info[0]['advertiser_id'];
            $core_user_id = $stat_info[0]['core_user_id'];
            $advertiser_name = $stat_info[0]['advertiser_name'];
            $flush_count = $stat_info[0]['flush_count'];

            $record_info = array(
                'order_id' => $order_id,
                'user_id' => $user_id,
                'advertiser_id' => $advertiser_id,
                'core_user_id' => $core_user_id,
                'advertiser_name' => $advertiser_name,
                'flush_count' => $flush_count,
                'customer_id' => $customer_id,
                'customer_name' => $customer_name
            );

            array_push($export_info, $record_info);

        }

        $this->downLoadExcel($export_info);

        dump('导出完成');
        die;

        // 以下内容不相关
        $data = Db::name('t_customerinfo')->find();
        $this->assign('result', $data);
        return $this->fetch();
    }

    public function analysis()
    {
        vendor("PHPExcel.Classes.PHPExcel");

        $root_path = $_SERVER['DOCUMENT_ROOT'];

        $file_name = "abc.xlsx";
        $file_type = "xlsx";

        if ($file_type == 'xls') {
            $reader = \PHPExcel_IOFactory::createReader('Excel5'); //设置以Excel5格式(Excel97-2003工作簿)
        }
        if ($file_type == 'xlsx') {
            $reader = new \PHPExcel_Reader_Excel2007();
        }

        // 读excel文件
        $PHPExcel = $reader->load($root_path . "/" . $file_name, 'utf-8'); // 载入excel文件
        $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $highestColumn = $sheet->getHighestColumn(); // 取得总列数

        // 把Excel数据保存数组中
        $data = array();
        //循环读取每个单元格的内容。注意行从1开始，列从A开始
        for ($rowIndex = 1, $i = 0; $rowIndex <= $highestRow; $rowIndex++, $i++) {
            for ($colIndex = 'A', $j = 0; $colIndex <= $highestColumn; $colIndex++, $j++) {
                $addr = $colIndex . $rowIndex;
                $cell = $sheet->getCell($addr)->getValue();
                if ($cell instanceof \PHPExcel_RichText) {
                    //富文本转换字符串
                    $cell = $cell->__toString();
                }

                // 二维数组存储
                $data[$i][$j] = $cell;
            }
        }

        // 准备查询的sql
        $view_sql = "select o.id as `order_id`,
                        o.create_time,
                        o.status as `order_status`,
                        d.`release_user_id` as `agent_id`,
                        d.telephone,
                        ur.ref_id as `author_adv_id`,
                        ur.advertiser_name as `author_adv_name`,
                        o.producer_id,
                        ur.core_user_id
                     from orders o
                        inner join user u 
                            on o.producer_id = u.id
                                inner join user_ref ur
                                    on u.id = ur.user_id
                                        inner join demand d
                                            on o.demand_id = d.id
                     where o.id = ";

        // 准备读取数据
        $total = 0;
        $row_len = count($data);
        $notify_info = array();
        for ($i = 1; $i < $row_len; $i++) {
            $order_id = $data[$i][4];
            $view_sql_exec = $view_sql . "'" . $order_id . "'";
            $view_info_list = Db::query($view_sql_exec);
            if (count($view_info_list) > 0) {
                $view_info = $view_info_list[0];
                $order_status = $view_info['order_status'];
                if ($order_status != 2) {
                    // 订单没有已关闭代表非撤单
                    continue;
                }

                $one_notify = array(
                    'order_id' => $order_id,
                    'agent_id' => $view_info['agent_id'], // 代理商下发布者账户
                    'agent_tel' => $view_info['telephone'], // 代理商电话，要通知
                    'core_user_id' => $view_info['core_user_id'], // 客户core_user_id，要拿电话去通知
                );

                array_push($notify_info, $one_notify);

                $total++;
            }
        }

        // total=110，正确
        // dump($total);

        // dump($notify_info);

        return json_encode($notify_info);

//        $this->downloadExcelExtend($notify_info);

//        dump('导出完成');
//        die();

    }

    function downloadExcelExtend($data = array())
    {
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel(); // 创建一个excel
        $excelFileName = "短信通知";

        $header = array(
            0 => 'order_id',
            1 => 'agent_tel',
            2 => 'core_user_id',
        );

        // 设置当前的sheet
        $objPHPExcel->setActiveSheetIndex(0);
        // 设置单元格的值
        $objPHPExcel->getActiveSheet()->setCellValue('A1', $header[0]);
        $objPHPExcel->getActiveSheet()->setCellValue('B1', $header[1]);
        $objPHPExcel->getActiveSheet()->setCellValue('C1', $header[2]);

        // 循环设置数据值
        $data_len = count($data);
        for ($i = 0; $i < $data_len; $i++) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . ($i + 2), " " . $data[$i]['order_id']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . ($i + 2), " " . $data[$i]['agent_tel']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . ($i + 2), " " . $data[$i]['core_user_id']);
        }

        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        // 直接输出到浏览器
        ob_end_clean(); // 清空缓存
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header("Content-Disposition:attachment;filename={$excelFileName}.xls");
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');

        exit;
    }

    /**
     * 将数据库数据导出为excel文件
     */
    function downLoadExcel($data)
    {
//        $user = Db::query("select * from user");
//        Loader::import('PHPExcel.PHPExcel');
//        Loader::import('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
//        Loader::import('PHPExcel.PHPExcel.Reader.Excel2007');
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        //设置每列的标题
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '代理即合user_id')
            ->setCellValue('B1', '代理ad账户advertiser_id')
            ->setCellValue('C1', '代理头条账户core_user_id')
            ->setCellValue('D1', '代理即合账户名称')
            ->setCellValue('E1', '旗下刷免单次数')
            ->setCellValue('F1', '刷单订单id')
            ->setCellValue('G1', '被代理顾客id')
            ->setCellValue('H1', '刷免单客户名称');
        //存取数据  这边是关键
        foreach ($data as $k => $v) {
            $num = $k + 2;
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . $num, ' ' . $v['user_id'])
                ->setCellValue('B' . $num, ' ' . $v['advertiser_id'])
                ->setCellValue('C' . $num, ' ' . $v['core_user_id'])
                ->setCellValue('D' . $num, $v['advertiser_name'])
                ->setCellValue('E' . $num, $v['flush_count'])
                ->setCellValue('F' . $num, ' ' . $v['order_id'])
                ->setCellValue('G' . $num, ' ' . $v['customer_id'])
                ->setCellValue('H' . $num, $v['customer_name']);
        }
        //设置工作表标题
        $objPHPExcel->getActiveSheet()->setTitle('优惠券刷单客户');
        //设置列的宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);

        // 输出到文件
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save(str_replace('.php', '.xlsx', __FILE__));

        // Redirect output to a client’s web browser (Excel2007)
//        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
//        header('Content-Disposition: attachment;filename="01simple.xlsx"');
//        header('Cache-Control: max-age=0');
//
//        // If you're serving to IE over SSL, then the following may be needed
//        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
//        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
//        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
//        header ('Pragma: public'); // HTTP/1.0
//
//        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
//        $objWriter->save('php://output');
        exit;

    }

    /**
     *
     * execl数据导出
     * 应用场景：订单导出
     * @param string $title 模型名（如Member），用于导出生成文件名的前缀
     * @param array $cellName 表头及字段名
     * @param array $data 导出的表数据
     *
     * 特殊处理：合并单元格需要先对数据进行处理
     */
    function exportOrderExcel($title, $cellName, $data)
    {
        //引入核心文件
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        //定义配置
        $topNumber = 2;//表头有几行占用
        $xlsTitle = iconv('utf-8', 'gb2312', $title);//文件名称
        $fileName = $title . date('_YmdHis');//文件名称
        $cellKey = array(
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
            'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM',
            'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'
        );

        //写在处理的前面（了解表格基本知识，已测试）
//     $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(20);//所有单元格（行）默认高度
//     $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(20);//所有单元格（列）默认宽度
//     $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(30);//设置行高度
//     $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(30);//设置列宽度
//     $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(18);//设置文字大小
//     $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);//设置是否加粗
//     $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE);// 设置文字颜色
//     $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//设置文字居左（HORIZONTAL_LEFT，默认值）中（HORIZONTAL_CENTER）右（HORIZONTAL_RIGHT）
//     $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
//     $objPHPExcel->getActiveSheet()->getStyle('A1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);//设置填充颜色
//     $objPHPExcel->getActiveSheet()->getStyle('A1')->getFill()->getStartColor()->setARGB('FF7F24');//设置填充颜色

        //处理表头标题
        $objPHPExcel->getActiveSheet()->mergeCells('A1:' . $cellKey[count($cellName) - 1] . '1');//合并单元格（如果要拆分单元格是需要先合并再拆分的，否则程序会报错）
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '订单信息');
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(18);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

        //处理表头
        foreach ($cellName as $k => $v) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellKey[$k] . $topNumber, $v[1]);//设置表头数据
            $objPHPExcel->getActiveSheet()->freezePane($cellKey[$k] . ($topNumber + 1));//冻结窗口
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k] . $topNumber)->getFont()->setBold(true);//设置是否加粗
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k] . $topNumber)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
            if ($v[3] > 0)//大于0表示需要设置宽度
            {
                $objPHPExcel->getActiveSheet()->getColumnDimension($cellKey[$k])->setWidth($v[3]);//设置列宽度
            }
        }
        //处理数据
        foreach ($data as $k => $v) {
            foreach ($cellName as $k1 => $v1) {
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$k1] . ($k + 1 + $topNumber), $v[$v1[0]]);
                if ($v['end'] > 0) {
                    if ($v1[2] == 1)//这里表示合并单元格
                    {
                        $objPHPExcel->getActiveSheet()->mergeCells($cellKey[$k1] . $v['start'] . ':' . $cellKey[$k1] . $v['end']);
                        $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k1] . $v['start'])->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    }
                }
                if ($v1[4] != "" && in_array($v1[4], array("LEFT", "CENTER", "RIGHT"))) {
                    $v1[4] = eval('return PHPExcel_Style_Alignment::HORIZONTAL_' . $v1[4] . ';');
                    //这里也可以直接传常量定义的值，即left,center,right；小写的strtolower
                    $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k1] . ($k + 1 + $topNumber))->getAlignment()->setHorizontal($v1[4]);
                }
            }
        }
        //导出excel
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    /**
     * 测试thinkphp中两种不同的日志。
     */
    function test2Logs(){
        // thinkphp 原生的日志系统
        Log::record('测试日志信息');
        Log::error('错误信息');
        Log::info('日志信息');

        // thinkphp使用log4php
        Logger::configure(APP_PATH.'config.xml');
        $logger = Logger::getLogger("silk");
        $logger->info("This is an informational message.");
        $logger->warn("I'm not feeling so good...");
        $logger->error("Some exception occurs...");
    }

}
