<?php

namespace app\Manage\controller;

use app\common\model\Partner;
use think\facade\Request;
use app\common\controller\Manage;
use think\Db;
class My extends Manage
{
    /**
     * 后台 合伙人申请列表
     */
    public function partner_index()
    {
        if(Request::isAjax())
        {
            $carouselModel = new Partner();
            return  $carouselModel->tableData(input('param.'));
        }
        return $this->fetch('partner_index');
    }
    /**
     * 删除 合伙人申请
     *
     */
    public function del_partner()
    {
        $article = new Partner();
        $result  = [
            'status' => true,
            'msg'    => '删除成功',
            'data'   => ''
        ];
        if (!$article->destroy(input('param.id/d'))) {
            $result['status'] = false;
            $result['msg']    = '删除失败';
        }
        return $result;

    }

    /**
     * 合伙人申请 审核通过
     */
    public function verify()
    {
        $result  = [
            'status' => true,
            'msg'    => '通过审核成功',
            'data'   => ''
        ];
        $fail  = [
            'status' => true,
            'msg'    => '异常错误',
            'data'   => ''
        ];
        $id = input('id');
        $res = Db::name('partner')->find($id);

        if($res['status'] == 0){
            $ores = Db::name('partner')->where("id=$id")->setField('status',1);
            if($ores){
                return $result;
            }else{
                return $fail;
            }

        }else{
            $ores = Db::name('partner')->where("id=$id")->setField('status',0);
            $result  = [
                'status' => true,
                'msg'    => '禁止通过',
                'data'   => ''
            ];
            if($ores){
                return $result;
            }else{
                return $fail;
            }

        }

    }
}