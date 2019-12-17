<?php
namespace app\api\controller;
/**
 * 为本地测试代码
 */
use app\common\controller\Api;
use FontLib\Table\Type\post;
use think\Db;
use think\Request;
class Demo extends Api{
    public function index(){
        $payment_id = 25669591734892;
        $orderInfo = DB::name('order')
            -> alias('o')
            -> join('bill_payments_rel b' , 'b.source_id = o.order_id')
            -> where('b.payment_id' , $payment_id)
            -> join('user u' , 'u.id = o.user_id')
            -> field('u.id as id,u.pid as pid,u.grade as grade,b.money as money,o.memo,o.order_id')
            -> find();//获取部分字段  购买人的id  上线id  购买金额以及礼包类型
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
        //根据购买的不同礼包不同处理
        if($orderInfo['memo'] == 2){
            $data = ['grade' => 2];
            DB::name('user') -> where(['id' => $orderInfo['id']]) -> update($data); //修改用户身份
        }else if($orderInfo['memo'] == 3){
            $data = ['grade' => 3];
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

    public function profit(){
        $user_id = Input('userid');
        if(empty($user_id)) return '参数错误';
        $userList = json_decode(DB::name('record') -> where(['pid' => $user_id]) -> select() , true);
        $begintime=date("Y-m-d H:i:s",mktime(0,0,0,date('m'),date('d'),date('Y')));
        $endtime=date("Y-m-d H:i:s",mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1);
        $allmoney = DB::name('backmoney') -> where(['user_id' => $user_id]) -> value('allmoney');
        $today = Db::name('record') -> where(['pid' => $user_id]) ->whereBetweenTime('time', $begintime, $endtime) -> column('money');
        $todaymoney = 0;
        for($i = 0 ; $i < count($today) ; $i++){
            $todaymoney = $todaymoney + $today[$i];
        }
        //修改数据
        foreach($userList as $k => $v){
            //获取关联订单信息
            $userInfo = DB::name('user')
                -> alias('u')
                -> join('order o' , 'u.id = o.user_id')
                -> where(['u.id' => $v['user_id'] , 'o.order_id' => $v['order']])
                -> find();
            $userList[$k]['username'] = $userInfo['nickname'];
            $userList[$k]['headimg'] = $userInfo['avatar'];
            $userList[$k]['expenses'] = $userInfo['order_amount'];
            $userList[$k]['allmoney'] = $allmoney;
            $userList[$k]['todaymoney'] = $todaymoney;
        }
        return $userList;
    }
}

//                            _ooOoo_
//                           o8888888o
//                           88" . "88
//                           (| -_- |)
//                            O\ = /O
//                        ____/`---'\____
//                      .   ' \\| |// `.
//                       / \\||| : |||// \
//                     / _||||| -:- |||||- \
//                       | | \\\ - /// | |
//                     | \_| ''\---/'' | |
//                      \ .-\__ `-` ___/-. /
//                   ___`. .' /--.--\ `. . __
//                ."" '< `.___\_<|>_/___.' >'"".
//               | | : `- `.;`\ _ /`;.`/ - ` : | |
//                 \ \ `-. \_ __\ /__ _/ .-` / /
//         ======`-.____`-.___\_____/___.-`____.-'======
//                            `=---='
//
//         .............................................
//                  佛祖保佑             永无BUG

