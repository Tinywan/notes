<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/6 15:51
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 数据处理控制器
 * |  【1】PHPExcel 数据导出功能
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\server\controller;


use think\Db;
use think\facade\Env;
use think\facade\Log;

class DataProcessingController
{
    /**
     * Excel 数据导出功能
     */
    public function excelExportData($method = 'load')
    {
        $postData = request()->param();
        $model = Db::name('order');
        $orderData = $model->field(
          'mch_id,
            order_no,
            mch_order_no,
            channel_order_no,
            goods,
            price,
            total_fee,
            channel,
            payment,
            jiesuan_status,
            cost_rate,
            rate,
            status,
            pay_time,
            create_time'
        )->limit(0, 20)->order('create_time desc')->select();

        //是否为空数据
        if (empty($orderData)) {
            $res = ['errorCode' => 201, 'data' => $orderData];
            return json($res);
        }

        $objPHPExcel = new \PHPExcel();
        // 表头
        $tableHeader = [
          '商户id',
          '订单号',
          '商户订单号',
          '渠道订单号',
          '商品名称',
          '原价',
          '实付金额',
          '渠道',
          '支付方式',
          '结算状态',
          '成本费率',
          '费率',
          '支付状态',
          '支付时间',
        ];
        static $letter = [];
        for ($i = 65; $i <= (64 + count($tableHeader)); $i++) {
            $letter[] = strtoupper(chr($i));
        }

        $objSheet = $objPHPExcel->getActiveSheet();
        $objSheet->setTitle("支付订单");   //给当前活动sheet起个名称
        $objSheet->getDefaultRowDimension()->setVisible(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objSheet->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        // 设置默认字体
        $objSheet->getDefaultStyle()->getFont()->setSize(12)->setName("宋体");
        $objSheet->getStyle("A1:N1")->getFont()->setBold(true);
        //设置默认行高
        $objSheet->getDefaultRowDimension()->setRowHeight(20);
        //设置列的宽度
        $objSheet->getDefaultColumnDimension()->setWidth(20);
        $objSheet->getColumnDimension("A")->setWidth(10);
        $objSheet->getColumnDimension("B")->setWidth(30);
        $objSheet->getColumnDimension("C")->setWidth(30);
        $objSheet->getColumnDimension("D")->setWidth(30);
        $objSheet->getColumnDimension("E")->setWidth(20);
        $objSheet->getColumnDimension("F")->setWidth(10);
        $objSheet->getColumnDimension("G")->setWidth(10);
        $objSheet->getColumnDimension("H")->setWidth(15);
        $objSheet->getColumnDimension("I")->setWidth(15);
        $objSheet->getColumnDimension("J")->setWidth(10);
        $objSheet->getColumnDimension("K")->setWidth(10);
        $objSheet->getColumnDimension("L")->setWidth(10);
        $objSheet->getColumnDimension("M")->setWidth(10);
        $objSheet->getColumnDimension("N")->setWidth(25);
        // 表格添加数据
        for ($i = 0; $i < count($tableHeader); $i++) {
            $objSheet->setCellValue("$letter[$i]1", "$tableHeader[$i]");
            $j = 2;
            foreach ($orderData as $key => $val) {
                $objSheet->setCellValue("A" . $j, $val['mch_id'])
                  ->setCellValue('B' . $j, $val['order_no'])
                  ->setCellValue('C' . $j, $val['mch_order_no'])
                  ->setCellValue('D' . $j, $val['channel_order_no'])
                  ->setCellValue('E' . $j, $val['goods'])
                  ->setCellValue('F' . $j, $val['price'])
                  ->setCellValue('G' . $j, $val['total_fee'])
                  ->setCellValue('H' . $j, $val['channel'])
                  ->setCellValue('I' . $j, $val['payment'])
                  ->setCellValue('J' . $j, $val['jiesuan_status'])
                  ->setCellValue('K' . $j, $val['cost_rate'])
                  ->setCellValue('L' . $j, $val['rate'])
                  ->setCellValue('M' . $j, $val['status'])
                  ->setCellValue('N' . $j, date("Y-m-d H:i:s", $val['create_time']));
                $j++;
            }
        }
        // 直接下载
        if ($method == 'load') {
            return $this->export_excel($objPHPExcel, time());
            // Ajax 异步下载 $method == 'ajax'
        } else {
            //返回已经存好的文件目录地址提供下载
            $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
            $url = $this->saveExcelToLocalFile($objWriter);
            if ($url) {
                $res = [
                  'errorCode' => 200,
                  'url' => $url,
                  'data' => $orderData
                ];
            } else {
                $res = [
                  'errorCode' => 500,
                  'url' => ''
                ];
            }
            return json($res);
        }

    }

    /**
     * 适用于Ajax 异步请求，请保证改目录具有写入权限，否则失败
     * @param $objWriter
     * @return string
     */
    protected function saveExcelToLocalFile($objWriter)
    {
        $fileName = date('YmdHis') . '.xlsx';
        $fileSavePath = ROOT_PATH . '/public/static/' . $fileName;
        Log::error('-------saveExcelToLocalFile--------' . $fileSavePath);
        $filePath = '/static/' . $fileName;
        $objWriter->save($fileSavePath);
        return $filePath;
    }

    /**
     * 数据直接导出
     * 【测试】OK
     * @param $objPHPExcel
     * @param $liveId
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    protected function export_excel($objPHPExcel, $fileName)
    {
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel2007");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition:attachment;filename=' . $fileName . '订单列表.xlsx');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
    }

    /**
     * 读取 Excel 上传文件后批量导入MySQL数据库
     * 行政区划代码
     */
    public function excelImport()
    {
        $inputFileName = Env::get('ROOT_PATH') . '/public/static/city-code11.xlsx';
        // 读取excel文件
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName); // Excel2007
        // 设置以Excel格式
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType); // return obj
        // 载入excel文件
        $objPHPExce = $objReader->load($inputFileName);

        // 读取第一個工作表
        $sheet = $objPHPExce->getSheet(0);
        // 取得总行数
        $highestRow = $sheet->getHighestRow();
        // 取得总列数
        $highestColumm = $sheet->getHighestColumn();

        $data = [];
        for ($column = 'A'; $column <= $highestColumm; $column++) {
            for ($row = 2; $row <= $highestRow; $row++) {
                $data[$row][] = $sheet->getCell($column . $row)->getValue();
            }
        }

        $newData = [];
        static $tmpData = [];
        $time = time();
        foreach ($data as $k => $v) {
            if ($v[2] == null) {
                $tmpData[$v[1]] = $v[0]; // 省级
                $newData[] = [
                  'city_code' => $v[0],
                  'channel' => 'saas',
                  'city_name' => $v[1],
                  'tid' => 0,
                  'created_at' => $time,
                  'updated_at' => $time
                ];
            } elseif (($v[2] != null) && ($v[3] == null) && ($v[1] != $v[2])) { // 记得过滤掉重复值
                $newData[] = [
                  'city_code' => $v[0],
                  'channel' => 'saas',
                  'city_name' => $v[2],
                  'tid' => $tmpData[$v[1]],
                  'created_at' => $time,
                  'updated_at' => $time
                ];
            }
        }

        $db = Db::name('bank_city_test')->insertAll($newData);
        halt($db);
    }
}