<?php

namespace app\api\controller;


class wechat
{
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