<?php
namespace app\api\controller;
use app\common\controller\Api;
use app\common\model\Coupon;
use think\Db;

/**
 * 各种前台控制的接口
 * Class Set
 * @package app\api\controller
 */
class Set extends Api
{
    private  $result = [
                'code' => 1,
                'msg'    => '成功'
            ];
    private $couponModel = null;


    protected function initialize()
    {
        parent::initialize();
        $this->couponModel = new Coupon();
    }
    /**
     * 控制用户点击申请合伙人  填写资料还是直接跳到购买合伙人礼包页面
     * 0-跳转购买   1-填写资料
     */
    public function setPartner()
    {
        $result = $this->result;
        $result['code'] = 0;
        return $result;
    }

    /**
     * 控制是否显示红包
     * 0-显示   1-不显示
     */
    public function setRed()
    {
        $result = $this->result;
        $result['code'] = 0;
        return $result;
    }

    /**
     * 控制用户点击申请会员  填写资料还是直接跳到购买会员礼包页面
     * 0-跳转购买   1-填写资料
     */
    public function setMember()
    {
        $result = $this->result;
        $result['code'] = 0;
        return $result;
    }

    /**
     * 获取当天所有优惠券,提供给用户选择
     */
    public function getTodayCounpon()
    {   
        $return_data = [
            'status' => true,
            'msg'    => '查询成功',
            'data'   => []
        ];
        
        if (!input('token')) {
            $return_data = [
                'code' => 3,
                'msg'    => '缺少token'
            ];
            return  $return_data;
        }
        $uid = DB::name('user_token') -> where(['token' => input('token')]) -> value('user_id');
        $is_max = $this->check_num($uid);
        if( $is_max == 0 ){
            //$return_data = [
            //    'code' => 4,
            //    'msg'  => '您今天领取红包次数已够'
           // ];
            //return $return_data;
        }

        //获取今天0点到24点 所有的优惠券
        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d 23:59:59');
        $stime = strtotime($start);
        $etime = strtotime($end);
        //查询所有大于开始时间 小于结束时间的优惠券
        $data = $this->couponModel->todayCoupon($stime,$etime);
        $return_data['data'] = $data;
        return $return_data;
    }

    /**
     * 购买礼包后赠送的优惠券红包
     */
    public function getPackage()
    {
        $return_data = [
            'status' => true,
            'msg'    => '查询成功',
            'data'   => []
        ];
        //获取今天0点到24点 所有的优惠券
        $start = date('Y-m-d 00:00:00');
        $stime = strtotime($start);
        //查询所有大于开始时间 小于结束时间的优惠券
        $data = $this->couponModel->todayPackage($stime);
        $return_data['data'] = $data;
        return $return_data;
    }

    /**
     * 获取我的所有优惠券
     */
    public function getMyPacket()
    {
        $return_data = [
            'status' => false,
            'msg'    => '查询失败',
            'data'   => []
        ];
        $token = input('token');
        if(!$token) return $return_data;
        $user_id = Db::name('user_token')->where("token='$token'")->value('user_id');
        $data = $this->couponModel->getMyCoupon($user_id);
        return $data;

    }

    /*
     * 用户领取优惠券
     */
    public function userGetCoupon()
    {
        $return_data = [
            'code'   => 3,
            'msg'    => '缺少参数token或者code',
            'data'   => []
        ];
        $code = input('code');
        $token = input('token');
        if( !$token || !$code ) return $return_data;
        //换取用户ID
        $user_id = DB::name('user_token') -> where(['token' => $token]) -> value('user_id');
        $date = date('Ymd');
        //查询用户今天领过就不能重复领取了
//        $is_get = Db::name('coupon')->where("user_id='$user_id' && get_date='$date'")->find();
//        if ($is_get){
//            $return_data['msg'] = '您今天已经领取过了';
//            return $return_data;
//        }
        $is_max = $this->check_num($user_id);
        if( $is_max == 0 ){
            $return_data = [
                'code' => 2,
                'msg'  => '您今天领取红包次数已够'
            ];
            //return $return_data;
        }
        $sqlData = [
            'user_id' => $user_id,
            'get_date'=>date('Ymd')
        ];

        $resmoney = DB::name('coupon')
            -> alias('c')
            -> join('promotion_result p' , 'c.promotion_id = p.promotion_id')
            -> where(['c.coupon_code' => $code])
            -> value('params');
        $money = json_decode($resmoney,true)['money'];
        //修改优惠券使用情况
        $res = DB::name('coupon') -> where(['coupon_code' => $code]) -> update($sqlData);
        if($res){
            $return_data = [
                'code' => 0,
                'msg'    => '恭喜您获得'.$money.'元优惠券',
                'money' => $money
            ];
        }else{
            $return_data = [
                'code' => 1,
                'msg'    => '使用失败',
            ];
        }
        return $return_data;
    }
     /**
     * 获取优惠券总的分类,和分类对应的图片
     */
    public function allPro()
    {
        $return_data = [
            'code' => 1,
            'msg'    => '缺少参数token或者code',
            'data'   => []
        ];

        $data = Db::name('promotion')
            ->where('is_package = 2')
            ->where('is_nine = 1')
            ->where('etime','>', time())
            ->where('status=1')
            ->where('isnull(isdel)')
            ->field('id,name,cover')
            ->limit(8)
            ->select()
            ->toArray();
            //var_dump($data);exit;
        for ($x=0; $x<8; $x++) {
            if( !isset($data[$x]['id']) ){
                $data[$x]['id'] = '';
                $data[$x]['name'] = '无';
                $data[$x]['cover'] = 'http://www.shuijichina.cn/static/uploads/images/2019/09/11/15681796345d7885b2cd3cd.jpg';
            }else{
                if($data[$x]['cover'] != null){
                    $data[$x]['cover'] = _sImage($data[$x]['cover']);
                }else{
                    $data[$x]['cover'] = 'http://www.shuijichina.cn/static/uploads/images/2019/09/11/15681796345d7885b2cd3cd.jpg';
                }
            }
        }
        $return_data['code'] = 0;
        $return_data['msg'] = '成功';
        $return_data['data'] = $data;
        return $return_data;
    }
    /**
     * 根据总id, 获取优惠券码,并发给某个用户
     */
     public function couponByPro()
    {
        $return_data = [
            'code' => 1,
            'msg'    => '此红包已经用完',
            'data'   => []
        ];
        $id = input('id');//优惠券总id
        $uid = DB::name('user_token') -> where(['token' => input('token')]) -> value('user_id');
        if( !$id || !$uid ){
            $return_data['code'] = 3;
            $return_data['msg'] = '缺少参数';
            return $return_data;
        }
        $coupons = Db::name('coupon')->where('promotion_id',$id)->where('user_id','=',null)->column('coupon_code');
        $count = sizeof($coupons);
        if ( $count < 1 ){
            return $return_data;
        }
        $is_max = $this->check_num($uid);
        if( $is_max == 0 ){
            $return_data = [
                'code' => 4,
                'msg'  => '您今天领取红包次数已够'
            ];
            return $return_data;
        }

        $index = mt_rand(0,$count);
        $code = $coupons[$index];
        $sqlData = [
            'user_id' => $uid,
            'get_date'=>date('Ymd')
        ];
        $resmoney = DB::name('coupon')
            -> alias('c')
            -> join('promotion_result p' , 'c.promotion_id = p.promotion_id')
            -> where(['coupon_code' => $code])
            -> value('params');
        $money = json_decode($resmoney,true)['money'];
        //修改优惠券使用情况
        $res = DB::name('coupon') -> where(['coupon_code' => $code]) -> update($sqlData);
        if($res){
            $return_data = [
                'code' => 0,
                'msg'    => '恭喜您获得'.$money.'元优惠券',
                'money' => $money
            ];
        }else{
            $return_data = [
                'code' => 2,
                'msg'    => '获取失败',
            ];
        }

        return $return_data;
    }


    public function check_num($uid)
    {
        //判断一个用户每天领取次数是否达到上限
        $type = Db::name('user')->find($uid);
        $type = $type['grade'];
        $mtimes = Db::name('coupon_num')->find(1);
        switch ($type){
            case 1:
                $cont = $mtimes['normal'];
                break;
            case 2:
                $cont = $mtimes['member'];
                break;
            case 3:
                $cont = $mtimes['partner'];
                break;
        }
        $date = date('Ymd');
        //查询出购买礼包送的红包
        //$coups = Db::name('coupon')->group('promotion_id')->column('promotion_id');

        $today_times = Db::name('coupon')->where("user_id='$uid' and get_date='$date'")->count();
        if ( $today_times < $today_times){
            return 1;
        }else{
            return 0;
        }
    }

    //获取某个优惠券的 条件
    public function couCondition()
    {
        $id = input('id');
        $return_data = [
            'code'   => 1,
            'msg'    => '失败',
            'data'   => []
        ];
        $condition = Db::name('promotion_condition')->where('promotion_id',$id)->find();
        if (!$condition){
            return $return_data;
        }

        switch ($condition['code']){
            case('GOODS_ALL')://所有商品满足条件
                $data['type'] = 'GOODS_ALL';
                $data['intro'] = '所有商品满足条件';
                $data['good_ids'] = 0;
                break;
            case('GOODS_IDS')://指定商品满足条件
                $data['type'] = 'GOODS_IDS';
                $data['intro'] = '指定商品满足条件';
                $params = json_decode($condition['params'],1);
                $data['good_ids'] = $params['goods_id'];
                break;
            case('ORDER_FULL')://订单满减
                $data['type'] = 'ORDER_FULL';
                $data['intro'] = '订单满减';
                $data['good_ids'] = 0;
                break;
        }
        $return_data['msg'] = '成功';
        $return_data['data'] = $data;
        return $return_data;
    }

    //获取优惠券对应的商品列表
    public function goodsList()
    {
        $gids = input('goods_id');
        $return_data = [
            'code'   => 1,
            'msg'    => '成功',
            'data'   => []
        ];
        $id_arr = explode(',',$gids);
        //查询商品信息
        $data = [];
        foreach ($id_arr as $k=>$v){
            $good = Db::name('goods')->find($v);
            $data[$k]['img'] = _sImage($good['image_id']);
            $data[$k]['id'] = $v;
            $data[$k]['name'] = $good['name'];
            $data[$k]['price'] = $good['price'];
            $data[$k]['package'] = $good['package'];
        }
        $return_data['data'] = $data;
        return $return_data;
    }
}