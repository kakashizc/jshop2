<?php

namespace app\common\model;
use think\Validate;

class Discount extends Common
{


    //验证规则
    protected $rule     =   [
        'discount'         =>  'require',

    ];

    protected $msg          =   [
        'discount.require'     =>  '必须填写折扣',

    ];
    //合伙人列表列表的tabledata
    public function tableData($post)
    {
        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = config('paginate.list_rows');
        }
        $tableWhere = $this->tableWhere($post);
        $list = $this->where($tableWhere['where'])->order($tableWhere['order'])->paginate($limit);
        foreach ($list as $k => $v){
            if($v['check'] == 3){
                $list[$k]['check'] = '合伙人';
            }else if ($v['check'] == 2){
                $list[$k]['check'] = '会员';
            }
        }
        $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型
        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = $list->total();
        $re['data'] = $data;
        return $re;
    }

    /**
     *  文章编辑更新
     * User:tianyu
     * @param $data
     * @return array
     */
    public function saveData($data)
    {
        $validate = new Validate($this->rule,$this->msg);
        $result = ['status'=>true,'msg'=>'保存成功','data'=>''];
        if(!$validate->check($data))
        {
            $result['status'] = false;
            $result['msg'] = $validate->getError();
        } else {
            if(!$this->allowField(true)->save($data,['id'=>$data['id']]))
            {
                $result['status'] = false;
                $result['msg'] = '保存失败';
            }
        }
        return $result;
    }
}