<?php

namespace org\payments;

use app\common\model\UserWx;
use think\db;
class wechatpay implements Payment
{
    private $config = [];

    function __construct($config){
        $this->config = $config;
    }

    public function pay($paymentInfo){
        $result = [
            'status' => false,
            'data' => [],
            'msg' => ''
        ];
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

        $data['mch_id'] = $this->config['mch_id'];//商户号
        $data['nonce_str'] = self::getNonceStr();//$this->config['nonce_str'];//32位随机数
        $data['body'] = "商品详情";
        $data['out_trade_no'] = $paymentInfo['payment_id'];//商户订单号
        $res = Db::name('user') -> where(['id' => $paymentInfo['user_id']]) -> value('grade'); //获取用户身份
        $discount = DB::name('discount') -> where(['check' => $res]) -> value('discount');
        $discount = 1;
        $data['total_fee'] = $paymentInfo['money']*100*$discount;                        //总金额
        $data['spbill_create_ip'] = $paymentInfo['ip'];                 //终端ip
        $data['notify_url'] = url('b2c/Callback/pay',['code'=>'wechatpay','payment_id'=>$paymentInfo['payment_id']],'html',true);                       //异步通知地址

        if(isset($paymentInfo['params']) && $paymentInfo['params'] != ""){
            $params = json_decode($paymentInfo['params'],true);
            if(!isset($params['trade_type'])){
                $params['trade_type'] = "JSAPI";
            }
            $trade_type_re = $this->getTradeType($params);
            if(!$trade_type_re['status']){
                return $trade_type_re;
            }
            $trade_type = $trade_type_re['data'];

        }else{
            $result['msg'] = '支付参数不能为空';
            return $result;
        }

        //取appid
        $appid_re = $this->getAppid($params['trade_type']);

        if(!$appid_re['status']){
            return $appid_re;
        }
        $data['appid'] = $appid_re['data'];



        $data['trade_type'] = $trade_type;                       //交易类型JSAPI微信小程序或微信公众号，MWEB是H5支付,APP是app支付

        //当时JSAPI的时候，也就是小程序的时候，openid必传
        if($trade_type == 'JSAPI'){
            //取open_id
            if($params['trade_type'] == 'JSAPI_OFFICIAL' && isset($params['openid'])){      //如果是公众号，并且前台传过来了openid就用前台的。
                $data['openid'] = $params['openid'];
            }else{
                $openid_re = $this->getOpenId($paymentInfo['user_id'],$params['trade_type']);
                $data['openid'] = $openid_re['data'];
            }
        }
        if($trade_type == 'MWEB'){
            $data['scene_info'] = [
                'h5_info' => [
                    'type' => 'Wap',
                    'wap_url' => $params['return_url']."?id=".$paymentInfo['payment_id'],
                    'wap_name' => 'jshop',//$params['wap_name'],
                ]
            ];
            $data['scene_info'] = json_encode($data['scene_info']);
        }
        if($trade_type == "NATIVE"){
            $data['product_id'] = $params['product_id']?$params['product_id']:"";
        }

        $data['sign'] = $this->makeSign($data);

        $xml = $this->toXml($data);

        $response = $this->postXmlCurl($xml, $url, false, 6);
        $re = $this->fromXml($response);

        if(!isset($re['return_code'])){
            $result['msg'] = $re;           //把错误信息都返回到前台吧，方便调试。
            return $result;
        }
        if($re['return_code'] == 'SUCCESS'){
            if($re['result_code'] == 'SUCCESS'){
                $result['status'] = true;
                $data = $this->wxapppay($re);
                //支付单传到前台
                $data['payment_id'] = $paymentInfo['payment_id'];
                $result['data'] = $data;
            }else{
                $result['data'] = $re['err_code'];
                $result['msg'] = $re['err_code_des'];
            }
        }else{
            $result['data'] = '';
            $result['msg'] = $re['return_msg'];
        }
        return $result;
    }

    //根据统一下单接口的数据拼装调起支付所需要的参数,为什么要传第二个参数$source_data,这是原始参数，为了h5支付拼接回调地址
    private function wxapppay($data){
        $app_data = [];

        switch ($data['trade_type'])
        {
            case 'JSAPI':                   //微信小程序组建数据
                $time = time();
                $app_data['timeStamp'] = "$time";
                $app_data['nonceStr'] = $data['nonce_str'];
                $app_data['package'] = 'prepay_id='.$data['prepay_id'];
                $app_data['signType'] = 'MD5';
                $app_data['appid'] = $data['appid'];
                $app_data['paySign'] = md5(
                    'appId='.$data['appid'].
                    '&nonceStr='.$app_data['nonceStr'].
                    '&package='.$app_data['package'].
                    '&signType='.$app_data['signType'].
                    '&timeStamp='.$app_data['timeStamp'].
                    '&key='.$this->config['key']
                );
                break;
            case 'MWEB':                //微信H5支付
                $app_data['mweb_url'] = $data['mweb_url'];
                break;
            case 'NATIVE':
                $app_data['code_url'] = $data['code_url'];
                break;
            case 'APP':                //微信APP支付
                $time = time();
                $app_data['appid'] = $data['appid'];
                $app_data['partnerid'] =  $this->config['mch_id'];
                $app_data['prepayid'] =  $data['prepay_id'];
                $app_data['noncestr'] = $data['nonce_str'];
                $app_data['package'] = 'Sign=WXPay';
                $app_data['timestamp'] = "$time";
                $app_data['sign'] = $this->makeSign($app_data);
                break;
        }

        return $app_data;
    }

    public function callback(){
        $result = [
            'status' => false,
            'data' => [],
            'msg' => ''
        ];
        //获取通知的数据
        $xml = file_get_contents('php://input');
        $data = $this->xmlToArray($xml);
        file_put_contents('test.txt',$xml);
        trace($data,'wechatpay');

        if($data && $data['return_code'] == 'SUCCESS' && $data['sign'] == $this->makeSign($data)){
            //说明值没问题，并且验证签名通过
            if($data['result_code'] == "SUCCESS"){
                $result['status'] = true;
                $result['data']['payment_id'] = $data['out_trade_no'];
                $result['data']['status'] = 2;          //1未支付，2支付成功，3其他
                $result['data']['payed_msg'] = $data['result_code'];
                $result['data']['trade_no'] = $data['transaction_id'];
                $result['data']['money'] = $data['total_fee']/100;
                $payment_id = $data['out_trade_no'];
                $orderInfo = DB::name('order')
            -> alias('o')
            -> join('bill_payments_rel b' , 'b.source_id = o.order_id')
            -> where('b.payment_id' , $payment_id)
            -> join('user u' , 'u.id = o.user_id')
            -> field('u.id as id,u.pid as pid,u.grade as grade,b.money as money,o.memo,o.order_id')
            -> find();//获取部分字段  购买人的id  上线id  购买金额以及礼包类型
            file_put_contents('wetw.txt', json_encode($orderInfo));
        //检测是否有邀请人
        $userUp = DB::name('user') -> where(['id' => $orderInfo['pid']]) -> find();//获取邀请人的身份
        $upMoney = DB::name('backmoney') -> where(['user_id' => $userUp['id']]) -> find();//判断是否有佣金数据
        //做用户赏金不同操作问题
        $addMoney = $this -> allMoney($orderInfo);
        //判断数据是不是唯一的
        if($addMoney != 10014){
           
            //判断总人数 +1 < 最大上限人数
            if($addMoney['allCount']+1 <= $addMoney['mixpeople']){
                
                $recordData = [
                    'user_id' => $orderInfo['id'] ,
                    'pid' => $orderInfo['pid'],
                    'type' => '该用户已经返利'.$addMoney['money'].'元',
                    'money' => $addMoney['money'],
                    'time' => time(),
                    'order' => $orderInfo['order_id']
                ];
                $save = [
                    'allmoney' => $addMoney['money'] + $upMoney['allmoney']  //当前对应金额加已获得总收益
                ];
                DB::name('backmoney') -> where(['user_id' => $orderInfo['pid']]) -> update($save); //修改总收益
                
                DB::name('record') -> insert($recordData); //添加记录
                
            }else{
            
                $recordData = [
                    'user_id' => $orderInfo['id'] ,
                    'pid' => $orderInfo['pid'],
                    'type' => '该用户已经返利'.$addMoney['money'].'元，自动补齐差价',
                    'money' => $addMoney['money'],
                    'time' => time(),
                    'order' => $orderInfo['order_id']
                ];
                $save = [
                    'allmoney' => ($addMoney['allCount'] + 1) * $addMoney['money'] //超过时、总人数x上限后金额
                ];
                DB::name('backmoney') -> where(['user_id' => $orderInfo['pid']]) -> update($save); //修改总收益金额
                DB::name('record') -> insert($recordData); //添加记录
            }
        }


        //如果购买礼包成功，修改 user表中对应uid 的 temp = 2 ，既为 pid 的永久下级
        //根据购买的不同礼包不同处理
        if($orderInfo['memo'] == 2){

            $data = ['grade' => 2,'temp' => 2];
            //先查询用户身份,如果以前就是合伙人 或者 会员,重复购买礼包的话, 会员可以升级为合伙人, 而合伙人购买会员礼包 不降级
            $who = Db::name('user')->where(['id' => $orderInfo['id']]) ->value('grade');
            if($who == 3){ //如果是合伙人,则仍然是合伙人
                $data = ['grade' => 3,'temp' => 2];
            }
            DB::name('user') -> where(['id' => $orderInfo['id']]) -> update($data); //修改用户身份
            
        }else if($orderInfo['memo'] == 3){
            $data = ['grade' => 3,'temp' => 2];
            DB::name('user') -> where(['id' => $orderInfo['id']]) -> update($data); //修改用户身份
        }
        $percentage = DB::name('discount')-> where(['check' => $userUp['grade']])  -> value('percentage'); //获取返利百分比
//        print_r($percentage);die;
        //检测邀请人是否有效   并且是合伙人或者vip
        if(!empty($userUp) && $percentage != ''){
            $money = $orderInfo['money'] * $percentage;//返利总金额
            //修改或者添加数据
            if(empty($upMoney)){
                $data = ['user_id' => $userUp['id'] , 'money' => $money , 'allmoney' => $addMoney['newmoney']];
                DB::name('backmoney') -> insert($data);
            }else{
                $data = ['money' => $upMoney['money']+$money , 'user_id' => $userUp['id']]; //原金额加上本次收益
                DB::name('backmoney') -> where(['user_id' => $userUp['id']]) -> update($data);
            }
        }
            }else{
                //如果未支付成功，也更新支付单
                $result['status'] = true;
                $result['data']['payment_id'] = $data['out_trade_no'];
                $result['data']['status'] = 3;          //1未支付，2支付成功，3其他
                $result['data']['payed_msg'] = $data['err_code'].':'.$data['err_code_des'];
                $result['data']['trade_no'] = '';
                $result['data']['money'] = $data['total_fee']/100;
            }
            $result['msg'] = 'success';
        }else{
            $result['msg'] = 'fail';
            return $result;
        }

        return $result;
    }
    //数据逻辑处理
    public function allMoney($info){
        $userUp = DB::name('user') -> where(['id' => $info['pid']]) -> find();//获取邀请人的身份
        //查看用户一共有多少个下级已经购买礼包
        $pidCount = DB::name('record') -> where(['pid' => $info['pid']]) -> count();
        $pid_user = DB::name('record') -> where(['user_id' => $info['id']]) -> find(); //判断是否第一次购买
        if(!empty($pid_user)){
            return '10014'; //返回错误码
        }
        //获取不同身份对应的上限人数
        if($userUp['grade'] == 2){
            $peopleMix = DB::name('brokerage_member') -> value('member_num');
        }else if($userUp['grade'] == 3){
            $peopleMix = DB::name('brokerage_member') -> value('partner_num');
        }else{
            $peopleMix = DB::name('brokerage_member') -> value('normal_num');
        }
        //获取用户总收益
        $all = DB::name('backmoney') -> where(['user_id' => $info['pid']]) -> find();
        if(empty($all['allmoney'])){
            $all = 0;
        }
        //如果总人数小于规定上限人数 加一
        if($info['memo'] != 2 and $info['memo'] != 3) return 10014;
        if($pidCount+1 <= $peopleMix){
            //判断购买礼包返利金额   低于上限人数
            if($info['memo'] == 2){
                //获取vip礼包获得赏金数额
                $getMoney = DB::name('brokerage_member') -> value('member_before');
                $newMoney = $all['allmoney'] + $getMoney;
            }else if($info['memo'] == 3){
                //获取合伙人礼包获得赏金数额
                $getMoney = DB::name('brokerage_partner') -> value('partner_before');
                $newMoney = $all['allmoney'] + $getMoney;
            }else{
                $newMoney = 0;
            }
        }else{
            //判断购买礼包返利金额   超过上限以后
            if($info['memo'] == 2){
                //获取vip礼包获得赏金数额
                $getMoney = DB::name('brokerage_member') -> value('member_after');
                $newMoney = $all['allmoney'] + $getMoney;
            }else if($info['memo'] == 3){
                //获取合伙人礼包获得赏金数额
                $getMoney = DB::name('brokerage_partner') -> value('partner_after');
                $newMoney = $all['allmoney'] + $getMoney;
            }
        }
        //返回
        $data = [
            'allCount' => $pidCount, //该用户已购买礼包人数
            'money' => $getMoney , //当前购买钱数
            'mixpeople' => $peopleMix, //总上限
            'newmoney' => $newMoney //当前金额
        ];
        return $data;
    }
    
    public function refund($refundInfo,$paymentInfo){
        $result = [
            'status' => false,
            'data' => [],
            'msg' => ''
        ];


        $cert_dir = ROOT_PATH.DS."config".DS."payment_cert".DS."wechatpay".DS;
        if(
            !file_exists($cert_dir."apiclient_cert.pem") ||
            !file_exists($cert_dir."apiclient_key.pem")
        ){
            $result['status'] = true;                       //没有证书的时候，相当于客户没有配置证书，那么原路返回线上就直接做已退款操作就可以了，不实际去退了，具体的金额在支付后台做退款
            $result['msg'] = "微信支付证书没有上传，不能在线退款";
            return $result;
        }


        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';

        $params = json_decode($paymentInfo['params'],true);
        if(!isset($params['trade_type'])){
            $params['trade_type'] = "JSAPI";
        }
        //取appid
        $appid_re = $this->getAppid($params['trade_type']);
        if(!$appid_re['status']){
            return $appid_re;
        }



        $data['appid'] = $appid_re['data'];
        $data['mch_id'] = $this->config['mch_id'];//商户号
        $data['nonce_str'] = self::getNonceStr();
        $data['out_trade_no'] = $paymentInfo['payment_id'];      //平台支付单号
        $data['out_refund_no'] = $refundInfo['refund_id'];            //退款单号

        $data['total_fee'] = $paymentInfo['money']*100;                        //订单总金额
        $data['refund_fee'] = $refundInfo['money']*100;         //退款金额


        //$data['notify_url'] = url('b2c/Callback/pay',['code'=>'wechatpay','payment_id'=>$paymentInfo['payment_id']],'html',true);                       //异步通知地址

        $data['sign'] = $this->makeSign($data);
        $xml = $this->toXml($data);
        $response = $this->postXmlCurl($xml, $url, true, 6);
        if($response == ""){
            //出错了
            $result['msg'] = '未知错误';
            return $result;
        }

        $re = $this->fromXml($response);
        if(!isset($re['return_code'])){
            $result['msg'] = '未知错误2';
            return $result;
        }
        if($re['return_code'] == 'SUCCESS'){
            if($re['result_code'] == 'SUCCESS'){
                $result['status'] = true;
            }else{
                $result['msg'] = $re['err_code'].'-'.$re['err_code_des'];
            }
        }else{
            $result['msg'] = $re['return_msg'];
        }
        return $result;
    }


    private function getTradeType($params){
        $result = [
            'status' => false,
            'data' => '',
            'msg' => ''
        ];
        //判断是否是指定的三种类型
        if(
            $params['trade_type'] != 'JSAPI' &&
            $params['trade_type'] != 'MWEB' &&
            $params['trade_type'] != 'JSAPI_OFFICIAL' &&
            $params['trade_type'] != 'NATIVE' &&
            $params['trade_type'] != 'APP'
        ){
            $result['msg'] = '参数错误，trade_type为非法值';
            return $result;
        }
        $trade_type = $params['trade_type'];
        if($trade_type == 'JSAPI_OFFICIAL'){          //微信公众号和小程序交易类型都是JSAPI，但是在前端的时候，我们区分开，为了取不同的appid
            $trade_type = "JSAPI";
        }
        $result['data'] = $trade_type;
        $result['status'] = true;
        return $result;
    }

    /**
     * 根据用户id和场景值来取对应的appid和openid
     * @param $user_id  用户id
     * @param $type     场景值，JSAPI代表小程序支付，JSAPI_OFFICIAL代表公众号支付，MWEB为h5支付
     * @return array
     */
    private function getAppid($type){
        $result = [
            'status' => false,
            'data' => '',
            'msg' => ''
        ];
        $appid = "";
        if($type == 'JSAPI'){
            $appid = getSetting('wx_appid');
        }elseif($type == 'JSAPI_OFFICIAL'){
            $appid = getSetting('wx_official_appid');
        }elseif($type == 'MWEB' || $type == 'NATIVE') {
            $appid = getSetting('wx_appid');
            if ($appid == "") {
                $appid = getSetting('wx_official_appid');
            }
        }elseif($type == 'APP'){
            $appid =  getAddonsConfigVal("App","open_weixin_appid");
        }else{
            $result['msg'] = '未知的微信支付类型';
            return $result;
        }
        //判断是否取得了appid
        if($appid == ''){
            $result['msg'] = '没有取得appid';
            return $result;
        }
        $result['data'] = $appid;
        $result['status'] = true;
        return $result;
    }

    /**
     * 获取openid
     * @param $user_id      用户id
     * @param $type         场景值，JSAPI代表小程序支付，JSAPI_OFFICIAL代表公众号支付
     * @return array
     */
    private function getOpenId($user_id,$type){
        $result = [
            'status' => false,
            'data' => '',
            'msg' => ''
        ];
        $userWxModel = new UserWx();
        if($type == 'JSAPI'){
            $type = $userWxModel::TYPE_MINIPROGRAM;
        }elseif($type == 'JSAPI_OFFICIAL'){
            $type = $userWxModel::TYPE_OFFICIAL;
        }else{
            return error_code(10061);
        }


        $userWxInfo = $userWxModel->where(['type'=>$type,'user_id'=>$user_id])->find();
        if(!$userWxInfo){
            return error_code(10062);
        }
        $result['data'] = $userWxInfo['openid'];
        $result['status'] = true;
        return $result;
    }

    /**
     * 将xml转为array
     * @param string $xml
     * @throws WxPayException
     */
    private function xmlToArray($xml)
    {
        if(!$xml){
            return [];
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    private function makeSign($values)
    {
        //签名步骤一：按字典序排序参数
        ksort($values);
        $string = $this->ToUrlParams($values);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".$this->config['key'];
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
    /**
     * 格式化参数格式化成url参数
     */
    private function toUrlParams($value)
    {
        $buff = "";
        foreach ($value as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }
    /**
     * 输出xml字符
     **/
    public function toXml($data)
    {
        if(!is_array($data)
            || count($data) <= 0)
        {
            return "";
        }

        $xml = "<xml>";
        foreach ($data as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
     * 将xml转为array
     * @param string $xml
     */
    private function fromXml($xml)
    {
        if(!$xml){
            return "";
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $this->values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $this->values;
    }
    /**
     * 以post方式提交xml到对应的接口url
     *
     * @param string $xml  需要post的xml数据
     * @param string $url  url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second   url执行超时时间，默认30s
     */
    public function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        //如果有配置代理这里就设置代理
//        if(WxPayConfig::CURL_PROXY_HOST != "0.0.0.0"
//            && WxPayConfig::CURL_PROXY_PORT != 0){
//            curl_setopt($ch,CURLOPT_PROXY, WxPayConfig::CURL_PROXY_HOST);
//            curl_setopt($ch,CURLOPT_PROXYPORT, WxPayConfig::CURL_PROXY_PORT);
//        }
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        if($useCert == true){
            $cert_dir = ROOT_PATH.DS."config".DS."payment_cert".DS."wechatpay".DS;
            if(
                !file_exists($cert_dir."apiclient_cert.pem") ||
                !file_exists($cert_dir."apiclient_key.pem")
            ){
                return "";
            }
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, $cert_dir."apiclient_cert.pem");
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, $cert_dir."apiclient_key.pem");
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            return "";
        }
    }
    /**
     *
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return 产生的随机字符串
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }
}
