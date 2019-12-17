<?php
namespace app\api\controller;
use app\common\controller\Api;
use Request;
use think\Db;


class Extract extends Api
{
    /**
     * 用户提现到零钱
     */
    public function extract()
    {
        $token = Input('token');//提现人token
        $num = Input('num');//提现金币的数量
        if(empty($token) || empty($num)){
            $result = [
                'status' => true,
                'msg' => '缺少参数'
            ];
            return $result;
        }
        //获取openid
        $openid = DB::name('user_token')
            -> alias('t')
            -> join('user_wx w' , 't.user_id = w.user_id')
            -> where(['token' => $token])
            -> field('w.openid , w.user_id')
            -> find();
        //获取剩余余额
        $moneyEnd = DB::name('backmoney') -> where(['user_id' => $openid['user_id']]) -> value('allmoney');
        if($num > $moneyEnd){
            $result = [
                'status' => true,
                'msg' => '余额不足'
            ];
            return $result;
        }
        $amount = $num * 100;
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
        $data = [];
        $MCHID = '1251766501';
        $KEY = 'mmkshuijichinahehe07181018581018';
        $order_no = date("YmdHis").$openid['user_id'];
        $data['mch_appid'] = "wx760008f39b492217";
        $data['mchid'] = $MCHID;
        $data['nonce_str'] = md5($MCHID.time());;
        $data['partner_trade_no'] = $order_no;
        $data['openid'] = $openid['openid'];
        $data['check_name'] = "NO_CHECK";
        $data['amount'] = $amount;
        $data['desc'] = "水极提现";
        $data['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];
        $data['sign'] = $this->makesign($data,$KEY);
        $xml = $this->arrayToXml($data);
        $res = $this->postXmlCurl($xml,$url);
        $result = json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        // return $result;
        if($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS'){
            //扣除用户金币数量
            $coin_res = Db::name('backmoney')->where(['user_id' => $openid['user_id']])->setDec('allmoney',$num);
            return $result = [
                'status' => true,
                'msg' => '提现成功'
            ];
            return $result;
        }else{
            return $result = [
                'status' => true,
                'msg' => '提现失败'
            ];
            return $result;
        }

    }

    public function  makesign($sdata,$KEY)
    {
        ksort($sdata);
        $sign_str = $this->ToUrlParams($sdata);
        $sign_str = $sign_str."&key=".$KEY;
        $sign = strtoupper(md5($sign_str));
        return $sign;
    }

    public function ToUrlParams($data)
    {
        $buff = "";
        foreach ($data as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    public function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
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


    public function postXmlCurl($xml, $url, $useCert = false, $second = 10)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);//严格校验2
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch,CURLOPT_SSLCERT,ROOT_PATH .'/public/static/cert/apiclient_cert.pem'); //这个是证书的位置绝对路径
        curl_setopt($ch,CURLOPT_SSLKEY,ROOT_PATH .'/public/static/cert/apiclient_key.pem'); //这个也是证书的位置绝对路径
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            return $error;
        }
    }
}