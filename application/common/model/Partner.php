<?php

namespace app\common\model;
use think\Validate;

class Partner extends Common
{
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
        foreach($list as $key => $val)
        {
            $list[$key]['status'] =  $val['status'] == 0 ? $val['status'] = '未通过' : $val['status'] = '已通过';
            $list[$key]['identity_card_face'] = 'http://'.$_SERVER['SERVER_NAME'].'/upload/img/'.$val['identity_card_face'];
            $list[$key]['identity_card_back'] = 'http://'.$_SERVER['SERVER_NAME'].'/upload/img/'.$val['identity_card_back'];
            $list[$key]['business_license'] = 'http://'.$_SERVER['SERVER_NAME'].'/upload/img/'.$val['business_license'];
        }
        $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型
        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = $list->total();
        $re['data'] = $data;
        return $re;
    }
}