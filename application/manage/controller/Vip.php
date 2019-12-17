<?php
namespace app\Manage\controller;

use app\common\controller\Manage;
use think\db;
use app\common\model\Discount as DiscountModel;
use think\facade\Request;
class Vip extends Manage
{

    public function index(){
        if(Request::isAjax())
        {
            $carouselModel = new DiscountModel();
            return  $carouselModel->tableData(input('param.'));
        }
        return $this->fetch('setuser');
    }

    public function edit()
    {
        $id = input('param.id/d');
        $articleModel = new DiscountModel();
        if (Request::isPost()) {
            return $articleModel->saveData(input('param.'));
        }
        $info = $articleModel->where('id',$id)->find();
        if (!$info) {
            return error_code(10002);
        }
        $articleTypeModel = new DiscountModel();
        $list             = $articleTypeModel->select();
        $this->assign('info',$info);
        return $this->fetch('edit');
    }

    //设置分销和购买折扣  以及对应套餐
    public function setUser(Request $request){
//        if(!$request -> isGet()) return json_encode(['code' => 1 , 'msg' => '数据存在部分疑问']);
        $judge = input('judge');
        if($judge != 'vip' && $judge != 'percentage') return json_encode(['code' => 1 , 'msg' => '无效操作']);
        $percentage = input('percentage')/100; //分销百分比
        $discount = input('discount')/100; //价格折扣
        $gift = strtoupper(input('gift')); //套餐标识   统一大写
        if(empty($judge && $percentage && $discount && $gift)) return json_encode(['code' => 1 , 'msg' => '非法操作']);
        //check为区分vip还是合伙人
        $data = [
            'check' => '',
            'gift' => $gift,
            'percentage' => $percentage,
            'discount' => $discount
        ];
        if($judge == 'vip'){
            $data['check'] = 2;
            $sql = DB::name('discount') -> insert($data);
        }else{
            $data['check'] = 3;
            $sql = DB::name('discount') -> insert($data);
        }
    }
	
	/**
     * 后台设置普通用户, 会员  的下级购买会员,合伙人礼包的返佣金,以及购买够多少人以后的返佣金
     */
    public function setBrokerage_member()
    {
        $data = Db::name('brokerage_member')->find(1);
        $this->assign('info',$data);
        return $this->fetch('brokerage_member');
    }
    public function edit_member()
    {
        $result = ['status'=>true,'msg'=>'保存成功','data'=>''];
        if (Request::isPost()) {
            $res = Db::name('brokerage_member')->where('id=1')->update(input('post.'));
            if($res){
                return $result;
            }else{
                return $result = ['status'=>false,'msg'=>'失败','data'=>''];
            }
        }
    }


    //编辑各种角色每天可领取红包次数
    public function coupon_num()
    {
        $data = Db::name('coupon_num')->find(1);
        $this->assign('info',$data);
        return $this->fetch('coupon_num');
    }
    public function edit_coupon_num()
    {
        $result = ['status'=>true,'msg'=>'保存成功','data'=>''];
        if (Request::isPost()) {
            $res = Db::name('coupon_num')->where('id=1')->update(input('post.'));
            if($res){
                return $result;
            }else{
                return $result = ['status'=>false,'msg'=>'失败','data'=>''];
            }
        }
    }
    
    public function setBrokerage_partner()  
    {
        $data = Db::name('brokerage_partner')->find(1);
        $this->assign('info',$data);
        return $this->fetch('brokerage_partner');
    }
    public function edit_partner()
    {
        $result = ['status'=>true,'msg'=>'保存成功','data'=>''];
        if (Request::isPost()) {
            $res = Db::name('brokerage_partner')->where('id=1')->update(input('post.'));
            if($res){
                return $result;
            }else{
                return $result = ['status'=>false,'msg'=>'失败','data'=>''];
            }
        }
    }
}