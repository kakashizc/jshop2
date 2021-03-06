<?php

namespace app\common\model;


class ArticleBannerRed extends common
{
    //资讯列表的tabledata
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
            $list[$key]['img'] = _sImage($val['img']);
            $list[$key]['ctime'] = date('Y-m-d H:i:s',$val['ctime']);
        }
        $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型
        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = $list->total();
        $re['data'] = $data;
        return $re;
    }


}