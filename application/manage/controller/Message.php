<?php

/**
 * 平台消息管理
 */

namespace app\manage\controller;

use app\common\controller\Manage as ManageController;
use app\common\model\ManageRole;
use app\common\model\MessageCenter;
use app\common\model\Operation;
use app\common\model\User;
use app\common\model\Message as MessageModel;
use app\common\model\ManageRoleRel;
//use Request;
use think\Request;
use think\db;


class Message extends ManageController
{
    public function index()
    {
        if(Request::isAjax()){
            $messageModel = new MessageModel();
            return $messageModel->tableData(input('param.'));
        }else{
            $messageCenterModel = new MessageCenter();
            $this->assign('tpl',$messageCenterModel->tpl);
            return $this->fetch('index');
        }
    }
    public function del()
    {
        if(!input('?param.id')){
            return error_code(10003);
        }
        $id = input('param.id');
        $messageModel = new MessageModel();
        if($messageModel->where(['id'=>$id])->delete()){
            return [
                'status' => true,
                'data' => '',
                'msg' => '删除成功'
            ];
        }else{
            return [
                'status' => false,
                'data' => '',
                'msg' => '删除失败'
            ];
        }
    }
	//用户留言展示
    public function leaveGet(Request $request){
        $must = $request -> post();
        $cid = $must['cid']; //获取产品id或者文章id
        $connect = json_decode(DB::name('message_leave') -> where(['c_id' => $cid]) -> select() , true);
        //获取用户信息
        if($connect){
            foreach($connect as $k => $v){
                $v['createtime'] = date("Y-m-d H:i:s" , $v['createtime']);
                $info =  DB::name('user') -> where(['id' => $v['u_id']]) -> find();
                $connect[$k] = $v + $info;
            }
            return json_encode(['code' => 0 , 'msg' => 'Success' , 'data' => $connect]);
        }else        {
            return json_encode(['code' => 1 , 'msg' => '参数错误']);
        }
    }

   //用户添加留言
    public function addMessage(Request $request){
		//检测提交方式
        if(!$request -> isPost()) return json_encode(['code' => 1 , 'msg' => '数据存在部分疑问']);
        $all = $request -> post();
        $text = empty($all['text']) ? '该用户未添加任何评价' : $all['text'];//内容
        $cid = $all['cid'];//文章id
        $uid = $all['uid'];//用户id
        if($cid && $uid != null){
			//验证用户是否为合法用户
            $info = DB::name('user') -> where(['id' => $uid]) -> find();
            if(!empty($info)){
                $sqlData = [
                    'c_id' => $cid ,
                    'contents' => $text ,
                    'u_id' => $uid ,
                    'createtime' => time()
                ];
            }else{
                return json_encode(['code' => 1 , 'msg' => '该账号存在问题，操作未执行!']);
            }
        }else{
            return json_encode(['code' => 1 , 'msg' => '缺少参数']);
        }
        $res = DB::name('message_leave') -> insert($sqlData);
        if($res){
            return json_encode(['code' => 0 , 'msg' => 'Success']);
        }else{
            return json_encode(['code' => 1 , 'msg' => 'False']);
        }
    }
}