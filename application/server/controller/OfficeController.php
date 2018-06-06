<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/6 16:01
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\server\controller;


use app\common\model\Order;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use think\Db;
use think\facade\Env;

class OfficeController
{
    // 简单示例
    public function writerXlsx()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Hello World !');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Hello World !');

        $writer = new Xlsx($spreadsheet);
        $filePath = Env::get('root_path');
        $fileName = date('YmdHis') . '.xlsx';
        $writer->save($filePath.'/public/static/'.$fileName);
    }

    /**
     * 读取MySQL数据库数据保存在Excel
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function writerXlsx01()
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getActiveSheet()->setTitle('order_title');
        $letter = range('A', 'Z');
        $insertData = Db::name('order')
            ->field('mch_id,order_no,mch_order_no,channel_order_no,goods')
            ->order('create_time desc')
            ->limit(0,20)->select();
        // 需要添加的第一行数据
        $tableHeader = [
            '商户id',
            '订单号',
            '商户订单号',
            '渠道订单号'
        ];
        // 写入表格数据
        for ($i = 0; $i < count($tableHeader); $i++) {
            // 写入表头信息
            $spreadsheet->getActiveSheet()->setCellValue("$letter[$i]1", "$tableHeader[$i]");
            // 具体数据
            $j = 2;
            foreach ($insertData as $k=>$val) {
                $spreadsheet->getActiveSheet()
                    ->setCellValue("A".$j,$val['mch_id'])
                    ->setCellValue('B'.$j,$val['order_no'])
                    ->setCellValue('C'.$j,$val['mch_order_no'])
                    ->setCellValue('D'.$j,$val['channel_order_no']);
                $j++;
            }
        }
        $writer = new Xlsx($spreadsheet);
        $filePath = Env::get('root_path').'/public/static/';
        $fileName = date('YmdHis') . '.xlsx';
        $writer->save($filePath.$fileName);
    }


    /**
     * 通过模板来生成文件
     */
    public function writerXlsx02()
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getActiveSheet()->setTitle('order_title');
        $letter = range('A', 'Z');
        $insertData = Db::name('order')
            ->field('mch_id,order_no,mch_order_no,channel_order_no,goods')
            ->order('create_time desc')
            ->limit(0,20)->select();
        // 需要添加的第一行数据
        $tableHeader = [
            '商户id',
            '订单号',
            '商户订单号',
            '渠道订单号'
        ];
        // 写入表格数据
        for ($i = 0; $i < count($tableHeader); $i++) {
            // 写入表头信息
            $spreadsheet->getActiveSheet()->setCellValue("$letter[$i]1", "$tableHeader[$i]");
            // 具体数据
            $j = 2;
            foreach ($insertData as $k=>$val) {
                $spreadsheet->getActiveSheet()
                    ->setCellValue("A".$j,$val['mch_id'])
                    ->setCellValue('B'.$j,$val['order_no'])
                    ->setCellValue('C'.$j,$val['mch_order_no'])
                    ->setCellValue('D'.$j,$val['channel_order_no']);
                $j++;
            }
        }
        $writer = IOFactory::createWriter($spreadsheet,'Xlsx');
        $filePath = Env::get('root_path');
        $fileName = date('YmdHis') . '.xlsx';
        $writer->save($filePath.'/public/static/'.$fileName);
    }

    /**
     * 直接输出下载
     */
    public function writerXlsx03()
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle('order_title');
        $letter = range('A', 'Z');
        $insertData = Db::name('order')
            ->field('mch_id,order_no,mch_order_no,channel_order_no,goods')
            ->order('create_time desc')
            ->limit(0,20)->select();
        // 需要添加的第一行数据
        $titles = [
            'mch_id'=>'商户id',
            'order_no'=>'订单号',
            'mch_order_no'=>'商户订单号',
            'channel_order_no'=>'渠道订单号'
        ];
        $tableHeader = [
            '商户id',
            '订单号',
            '商户订单号',
            '渠道订单号'
        ];
        // 写入表格数据
        for ($i = 0; $i < count($tableHeader); $i++) {
            $worksheet->setCellValue("$letter[$i]1", "$tableHeader[$i]");
            $j = 2;
            foreach ($insertData as $k=>$val) {
                $worksheet->setCellValue("A".$j,$val['mch_id'])
                    ->setCellValue('B'.$j,$val['order_no'])
                    ->setCellValue('C'.$j,$val['mch_order_no'])
                    ->setCellValue('D'.$j,$val['channel_order_no']);
                $j++;
            }
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = date('YmdHis') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition:attachment;filename=' . $fileName);
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    }

    /**
     * 直接输出下载111111
     */
    public function writerCsv()
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle('order_title');
        $letter = range('A', 'Z');
        $insertData = Db::name('order')
            ->field('mch_id,order_no,mch_order_no,channel_order_no,goods')
            ->order('create_time desc')
            ->limit(0,20)->select();
        // 需要添加的第一行数据
        $tableHeader = [
            '商户id',
            '订单号',
            '商户订单号',
            '渠道订单号'
        ];
        // 写入表格数据
        for ($i = 0; $i < count($tableHeader); $i++) {
            $spreadsheet->getActiveSheet()->setCellValue("$letter[$i]1", "$tableHeader[$i]");
            $j = 2;
            foreach ($insertData as $k=>$val) {
                $spreadsheet->getActiveSheet()
                    ->setCellValue("A".$j,$val['mch_id'])
                    ->setCellValue('B'.$j,$val['goods'])
                    ->setCellValue('C'.$j,$val['mch_order_no'])
                    ->setCellValue('D'.$j,$val['channel_order_no']);
                $j++;
            }
        }

        $writer = IOFactory::createWriter($spreadsheet,'Csv');
        $fileName = date('YmdHis') . '.csv';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition:attachment;filename=' . $fileName);
        header("Content-type:application/vnd.ms-excel;charset=UTF-8");
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    }

    /**
     * 直接输出下载111111
     */
    public function writerHtml()
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle('order_title');
        $letter = range('A', 'Z');
        $insertData = Db::name('order')
            ->field('mch_id,order_no,mch_order_no,channel_order_no,goods')
            ->order('create_time desc')
            ->limit(0,20)->select();
        // 需要添加的第一行数据
        $tableHeader = [
            '商户id',
            '订单号',
            '商户订单号',
            '渠道订单号'
        ];
        // 写入表格数据
        for ($i = 0; $i < count($tableHeader); $i++) {
            $spreadsheet->getActiveSheet()->setCellValue("$letter[$i]1", "$tableHeader[$i]");
            $j = 2;
            foreach ($insertData as $k=>$val) {
                $spreadsheet->getActiveSheet()
                    ->setCellValue("A".$j,$val['mch_id'])
                    ->setCellValue('B'.$j,$val['goods'])
                    ->setCellValue('C'.$j,$val['mch_order_no'])
                    ->setCellValue('D'.$j,$val['channel_order_no']);
                $j++;
            }
        }

        $writer = IOFactory::createWriter($spreadsheet,'Html');
        $fileName = date('YmdHis') . '.html';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition:attachment;filename=' . $fileName);
        header("Content-type:application/vnd.ms-excel;charset=UTF-8");
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    }

    /**
     * 自动计算列宽
     * @param $sheet
     * @param $fromCol
     * @param $toCol
     */
    public function autoFitColumnWidthToContent($sheet, $fromCol, $toCol) {
        if (empty($toCol) ) {   //not defined the last column, set it the max one
            $toCol = $sheet->getColumnDimension($sheet->getHighestColumn())->getColumnIndex();
        }
        for($i = $fromCol; $i <= $toCol; $i++) {
            $sheet->getColumnDimension($i)->setAutoSize(true);
        }
        $sheet->calculateColumnWidths();
    }
}