# 赤龙支付  

## 支付账号信息  

* 银行卡号：360101198309255010  
* 密码：hswl0571  
* 交易密码：446688  

## 平台信息
* http://pay.yo1c.cc/
* 后台：http://pay.yo1c.cc/admin  admin 123456
* 商家后台：http://pay.yo1c.cc/merchant  12001 123456

## 平台信息  

#### 和壹付平台支付  
* 平台：[saas.yeeyk.com](saas.yeeyk.com)  
* 账号：账号：`3223017786@qq.com`   密码：`Chilong123`  
* 支付接口：http://saas.yeeyk.com/saas-trx-gateway/order/acceptOrder   
* 提现接口：http://saas.yeeyk.com/saas-trx-gateway/order/queryOrder  

#### 汇支付平台   
* 接口文档：[http://dev.heepay.com](http://dev.heepay.com)  

## 如何接入？

* 【渠道管理】-【渠道配置】添加平台信息
* 【支付渠道】配置使用哪一种支付方式

## 如何测试？

#### 测试配置信息   

进入后台管理  

* 【商户模块】-【商户列表】-测试账号  
  * 编辑：【渠道商号】->【添加渠道】如：和壹付（	广州半贸贸易有限公司）  
  * 编辑：【支付开通】->开启要具体测试【支付方式】进行编辑  
  * 如要测试【银联网关】，选择编辑，在下拉列表中选择【支付渠道】就可以了  
* 测试DEMO
  * 本地：http://pay.env/?aa=bb  
  * 线上测试：http://pay.yo1c.cc/?aa=bb  

#### 支付（测试）  

`gateWay($option)`接收到的参数如下：
```json
{
	"total_fee": "1",
	"goods": "银联测试",
	"order_sn": "22161528351633",
	"client": "web",
	"bank_code": "CCB",
	"client_ip": "127.0.0.1",
	"notify_url": "http:\/\/pay.env\/index\/index\/test10",
	"return_url": "http:\/\/pay.env\/index\/index\/test22",
	"mch_id": "12001",
	"version": "1.0",
	"order_no": "S120011806071407171800"
}
```

###### 同步接口返回数据：  

* 同步通知地址：`https://pay.hongnaga.com/return`  
* `returnUrl(PayRepository $payRepository)`网关地址调用支付接口`PayRepository`的`notify()`  
* 支付参数 
  * 渠道：`$channel_str` 
  * 商户订单号：`$mch_order_no`  
  * 商家ID：`$mch_id`  
* 通过`App::invokeClass([$channel_str])`实例化具体的渠道支付类 
* 返回一个跳转地址URL  
```json
{
  "code" : "00000",
  "message" : "成功",
  "payUrl" : "http://saas.yeeyk.com/saas-trx-gateway/order/fetchPay?payNo=201806071115482039216151SP",
  "trxMerchantNo" : "80086000452",
  "hmac" : "7f8dde1f2f6c032facd469bad79ceea5"
}
```

###### 异步回调测试  

* 异步通知地址：`https://pay.hongnaga.com/notify`
* `notify(PayRepository $payRepository)`网关地址调用支付接口`PayRepository`的`notify()`  
* 接受`GET/POST`数据，这里接受到的数据为一个数组`$data`  
* 根据判断该回调属于哪个支付渠道（支付 or 提现）  
* 支付参数 
  * 渠道：`$channel_str` 
  * 商户订单号：`$mch_order_no`  
  * 商家ID：`$mch_id`  
* 通过`App::invokeClass([$channel_str])`实例化具体的渠道支付类  
* 设置商户渠道配置 `$object->setMchChannelConfig($mch_id)`  
* 具体渠道异步处理`$object->notify($data)`，如：`Saas.php`  
  * 参数验证  
  * 签名验证  
  * 第三方接口返回数据验证
  * 根据第三方接口参数返回一个数组，成功或者错误  
* 判断是 ** 支付订单** 还是 **提现订单**  
  * **支付订单**  
    * 调用方法：`payNotify($channel_str, $result, $is_send_notify)`  
    * 查询订单、订单判断  
    * 查询渠道配置  
    * 判断订单状态，`-1 支付失败 0 未支付  1 已支付  2 已退款`  
    * 开启事务修改订单状态（前提是第三方返回结果必须是成功的，success 结果）  
    * 为商户增加余额  
    * 增加余额明细，数据表：`jd_merchant_balance_record`  
    * 如果订单状态`notify_status != 'yes'`并且需要通知给客户  
    * 通过`sendNotify($order_no, $type = 1)`发送给客户异步通知  
    * 根据发送状态修改订单发送状态`notify_status == yes`  
  * **提现订单**  
    * 提现结果异步通知：`cashNotify($channel_str, $result)`  
* 返回数据格式  
```json
{
  "data": {
    "reCode": "1",
    "trxMerchantNo": "800666000037",
    "trxMerchantOrderno": "1504233022730",
    "result": "SUCCESS",
    "productNo": "WX_YF",
    "memberGoods": "1504233022730",
    "amount": "10.00",
    "retMes": "",
    "hmac": "203r5riLq9jJ7rt7e348yzG5fBG96U838a",
  },
  "code": "000000" // 查单返回码
}
```

Api异步通知数据结果 ：  
```json
{
    "result": "SUCCESS",
    "trxMerchantOrderno": "S120011806071409371964",
    "amount": "1.00",
    "trxMerchantNo": "80086000452",
    "memberGoods": "%E9%93%B6%E8%81%94%E6%B5%8B%E8%AF%95",
    "reCode": "1",
    "hmac": "9aac3620e46358f137ee552a81623384",
    "productNo": "EBANK-JS"
}
```

#### 提现/代付（测试）   

商家后台：http://pay.env/merchant/index/index.html  

银行编码：`\repositories\PayRepository.php`添加需要银行编码的额外处理：`['heepay', 'heepay_wechat', 'allscore', 'saas']` 添加别名，查询相关的全部添加上就可以了  

提现：【商家管理】-》【账户管理】=》【提现】  
提现Ajax异步：`merchant/merchant_account/docash`方法  

`$this->mchChannelConfig`成员输出  

```json
{
    "id": 30,
    "channel": "saas",
    "company_name": "广州半贸贸易有限公司",
    "remark": "",
    "channel_mch_id": "80086000452",
    "channel_mch_key": "8u6i14oD68m39147W7Kp3ht7LABuJ3b1J5291541f02hX71U1Cq5MeSXSDvV",
    "channel_mch_rsa_private": "",
    "channel_mch_rsa_public": "",
    "extend_1": null,
    "extend_2": null,
    "extend_3": null,
    "extend_4": "",
    "status": 1,
    "created_at": 1528337071,
    "updated_at": 1528337233
}
```

支付渠道商的`function cash($option)`中`$option`接受参数   

```json
{
    "acc_attr": 2,
    "acc_bankno": "105331021172",
    "acc_bank": "中国建设银行",
    "acc_bank_code": "CCB",
    "acc_card": "6217001540022416380",
    "acc_province": 1837,
    "acc_city": "330100",
    "acc_name": "付贵炉",
    "acc_subbranch": "西溪支行",
    "acc_idcard": "360101198309255010",
    "acc_mobile": "13695884887",
    "amount": "1.00",
    "order_no": "C120011806081320135",
    "type": 1,
    "mch_id": 12001,
    "total_fee": "2.00",
    "channel": "saas",
    "channel_order_no": "",
    "created_at": 1528435213,
    "updated_at": 1528435213
}
```

提现异步返回数据：  

```json
{
  "result": "FAIL",
  "amount": "2.00",
  "reCode": "0", // 1:成功 0:失败
  "hmac": "302893c01b18281495ce9fbc17ae8cfc",
  "merchantOrderNo": "C120011806081320135",
  "merchantNo": "80086000452"
}
```


# -----------------------------------------------------------------
#### 商户同步/异步响应参数

跟多参数了解：[赤龙支付](https://chilong.doc.eooo.biz/_book/)  

###### 同步接口返回数据   

为了模拟客户的请求访问  

设置同步回调地址为`赤龙支付`的：`http://pay.yo1c.cc/index/index/test22`方便测试  

```json
{
	"order_no": "S120011806071532428535",
	"mch_order_no": "89941528356762",
	"goods": "银联测试",
	"total_fee": 1,
	"payment": "gateWay",
	"rate": 0.007,
	"service_charge": 0.002,
	"status": 1,
	"create_time": "2018-06-07 15:32:43",
	"pay_time": "2018-06-07 15:33:16",
	"sign": "19ad4aeb462a13200caf24d0724fb4c3"
}
```




