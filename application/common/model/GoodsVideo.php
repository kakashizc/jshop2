<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/27
 * Time: 11:55
 */

namespace app\common\model;


class GoodsVideo extends common
{
    //商品的视频轮播图
    public function tableData($post)
    {
        if(isset($post['limit'])){
            $gid = $post['goods_id'];
            $limit = $post['limit'];
        }else{
            $limit = config('paginate.list_rows');
        }
        $tableWhere = $this->tableWhere($post);
        $list = $this->where($tableWhere['where'])->order($tableWhere['order'])->where("goods_id=$gid")->paginate($limit);
        foreach($list as $key => $val)
        {
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