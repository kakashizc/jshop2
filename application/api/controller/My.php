<?php
namespace app\api\controller;

use think\Db;
use app\common\controller\Api;
use app\common\model\Partner as PartnerModel;
use org\decrypt\WXBizDataCrypt;
/**
 * 小程序->我的
 * Class My
 * @package app\api\controller
 */
class My extends api
{   


    /**
     * 来个车支付宝回调测试
     */
    public function aliback()
    {
        $str = file_get_contents('php://input');
        file_put_contents('44444411111.txt', $str);
    }

    public function add_partner()
    {
        $result = [
            'status' => true,
            'msg' => '获取成功',
        ];
        $data  = $this->request->param();
        $res = Db::name('partner')->insertGetId($data);
        if($res){
            echo json_encode($result);
        }else{
            $result['msg'] = 'fail';
            $result['status'] = false;
            echo json_encode($result);
        }
    }

    public function upload()
    {
        $file = $this->request->file('file');
        if(!empty($file)){
            $file = $file->move(ROOT_PATH . 'public/upload/img');
            $filename = $file->getSaveName();
            $new = str_replace('\\','/',$filename);
            echo $new;
        }else{
            return 'fail';
        }
    }

    /**
     * 获取微信session_key等信息
     */
    public function login()
    {
        $appid = 'wx760008f39b492217';
        $appsecret = '44f2772928dda7ff174c83a516219a0b';
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$appsecret.'&js_code='.$_GET['code'].'&grant_type=authorization_code';
        $content = file_get_contents($url);
        $content = json_decode($content);
        echo $content->session_key;
    }

    /**
     * 解密我的步数的加密数据
     *
     */
    public function undata()
    {
        $result = [
            'status' => true,
            'msg' => '获取成功',
            'data'=> []
        ];
        $pc = new WXBizDataCrypt('wx760008f39b492217', $_GET['session']);
        $errCode = $pc->decryptData($_GET['encryptedData'], $_GET['iv'], $data );

        if ($errCode == 0) {
            $result['data'] = $data;
            return $result;
        } else {
            $resutl['msg'] = '失败';
            $resutl['data'] = $errCode;
            return $result;
        }
    }

    /**
     * 获取我的收益
     */
    public function myProfit()
    {
        $usertoken = Input('userid');
        $user_id = DB::name('user_token') -> where(['token' => $usertoken]) -> value('user_id');
        $result = [
            'code' => 1,
            'msg' => 'token错误'
        ];
        if(empty($user_id)) return $result;
        //总收益
        $all = Db::name('backmoney')->where('user_id',$user_id)->value('allmoney');
        if($all){
            $result['data']['allmoney'] = $all;
        }else{
            $result['data']['allmoney'] = 0;
        }
        $date = date('Ymd',time());
        //今日收益
        $today = Db::name('backmoney_rec')->where('user_id',$user_id)->where('date',$date)->sum('money');
        if($today){
            $result['data']['todaymoney'] = $today;
        }else{
            $result['data']['todaymoney'] = 0;
        }
        $result['code'] = 0;
        $result['msg'] = '成功';
        return $result;
    }

    /**
     * 用户提现到零钱
     */
    public function extract()
    {
        $usertoken = input('userid');//userToken
        $user_id = DB::name('user_token') -> where(['token' => $usertoken]) -> value('user_id');
        $result = [
            'status' => false,
            'msg' => '参数错误'
        ];
        if(empty($user_id)) return $result;

        $num = $this->request->param('num');//提现金额
        $max = Db::name('backmoney')->where('user_id',$user_id)->value('allmoney');

        if( empty($num) || $num > $max ){
            return cmf_api('2','缺少参数或金额不足');
        }
        //单笔付款到个人 最低为0.3元,所以限制不能少于10个才能提现
        if($num < 0.3){
            return cmf_api('1','最少0.3元才能提现!');
        }

        $wechat = new wechat();
        //获取openid
        $personnel_find = Db::name('user_wx')->where('user_id',$user_id)->find();
        $openid = $personnel_find['openid'];

        //根据提现金币的数量,转换为提现的金额 服务费为10%,1个金币1毛.  如果提现100个金币,扣除10%手续费,就是90个金币,提现为9元
        $amount = $num;
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
        $data = [];
        $MCHID = '1251766501';
        $KEY = 'mmkshuijichinahehe07181018581018';
        $order_no = date("YmdHis").$user_id;
        $data['mch_appid'] = "wx760008f39b492217";
        $data['mchid'] = $MCHID;
        $data['nonce_str'] = md5($MCHID.time());;
        $data['partner_trade_no'] = $order_no;
        $data['openid'] = $openid;
        $data['check_name'] = "NO_CHECK";
        $data['amount'] = $amount*100;
        $data['desc'] = "佣金提现";
        $data['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];
        $data['sign'] = $wechat->makesign($data,$KEY);
        $xml = $wechat->arrayToXml($data);
        $res = $wechat->postXmlCurl($xml,$url);

        $result = json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        if($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS'){
            //扣除用户金币数量
            Db::name('backmoney')->where('user_id',$user_id)->setDec('allmoney',$num);
            return cmf_api('0','提现成功');
        }else{
            return cmf_api('3',$result['err_code_des']);
        }
    }
}