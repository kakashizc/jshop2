<?php
// +----------------------------------------------------------------------
// | JSHOP [ 小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 http://jihainet.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <jima@jihainet.com>
// +----------------------------------------------------------------------
namespace app\common\model;

use think\Db;

class Form extends common
{

    protected $autoWriteTimestamp = true;
    protected $updateTime = 'utime';
    protected $createTime = 'ctime';

    const NEED_LOGIN = 1;//需要登录
    const NOT_NEED_LOGIN = 2;//不需要登录

    const HEAD_TYPE_IMAGE = 1;//图片
    const HEAD_TYPE_SLIDE = 2;//轮播
    const HEAD_TYPE_VIDEO = 3;//视频
    //表单类型
    const FORM_TYPE_ORDER = 1;//订单
    const FORM_TYPE_PAY = 2;//付款码
    const FORM_TYPE_MESSAGE = 3;//留言
    const FORM_TYPE_FEEDBACK = 4;//反馈
    const FORM_TYPE_REGISTER = 5;//登记
    const FORM_TYPE_SURVEY = 6;//调研


    public function add($data)
    {
        Db::startTrans();
        $res = $this->save($data);
        if (!$res) {
            Db::rollback();
            return false;
        }
        $goodsModel = new Goods();
        $formid     = $this->getLastInsID();
        if ($data['field']) {
            $field = [];
            foreach ($data['field']['name'] as $key => $value) {
                if ($data['field']['type'][$key] == 'goods') {
                    $goodsinfo = $goodsModel->field('name')->where(['id' => intval($data['field']['value'][$key])])->find();
                    $value     = $goodsinfo['name'];
                }
                $field[$key] = [
                    'form_id'         => $formid,
                    'name'            => $value,
                    'type'            => $data['field']['type'][$key],
                    'value'           => $data['field']['value'][$key],
                    'default_value'   => $data['field']['default'][$key],
                    'validation_type' => $data['field']['validation_type'][$key],
                    'required'        => isset($data['field']['required'][$key]) ? $data['field']['required'][$key] : '2',
                    'sort'            => $data['field']['sort'][$key],
                ];
            }

            $formItem = new FormItem();

            $res      = $formItem->saveAll($field);
            if (!$res) {
                Db::rollback();
                return false;
            }
        }
        Db::commit();
        return true;
    }


    //where搜索条件
    protected function tableWhere($post)
    {
        $where           = [];
        if(isset($post['name']) && $post['name']){
            $where[] = ['name','=',$post['name']];
        }
        $result['where'] = $where;
        $result['field'] = "*";
        $result['order'] = ['sort'=>'ASC','id'=>'desc'];
        return $result;
    }

    public function tableFormat($list)
    {
        $list = $list->toArray();
        foreach ((array)$list as $key => $value) {
            $list[$key]['type']      = config('params.form')['type'][$value['type']];
            $list[$key]['head_type'] = config('params.form')['head_type'][$value['head_type']];
            $list[$key]['is_login']  = ($value['is_login'] == self::NEED_LOGIN) ? '是' : '否';
            $list[$key]['ctime']     = ($value['ctime'] > 0) ? date('Y-m-d H:i:s', $value['ctime']) : '';
            $list[$key]['utime']     = ($value['utime'] > 0) ? date('Y-m-d H:i:s', $value['utime']) : '';
        }
        return parent::tableFormat($list); // TODO: Change the autogenerated stub
    }

    /**
     * 获取form表单详情
     * @param int $id
     * @return array
     */
    public function getFormInfo($id = 0)
    {
        $result = [
            'status' => false,
            'msg'    => '获取失败',
            'data'   => []
        ];
        if (!$id) {
            return $result;
        }
        $form = $this->where(['id' => $id])->find();
        if (!$form) {
            $result['msg'] = '暂无表单';
            return $result;
        }
        $form = $form->toArray();
        if (isset($form['end_date'])) {
            if ($form['end_date'] == 0) {
                $form['end_date'] = '';
            } else {
                $form['end_date'] = date('Y-m-d', $form['end_date']);
            }
        }
        switch ($form['head_type']) {
            case self::HEAD_TYPE_IMAGE:
                $form['head_type_value_url'][]   = _sImage($form['head_type_value']);
                $form['head_type_value_image'][] = $form['head_type_value'];
                break;
            case self::HEAD_TYPE_SLIDE:
                $images = explode(',', $form['head_type_value']);
                foreach ((array)$images as $key => $val) {
                    $form['head_type_value_url'][]   = _sImage($val);
                    $form['head_type_value_image'][] = $val;
                }
                break;
            case self::HEAD_TYPE_VIDEO:
                $video                         = explode(',', $form['head_type_value']);
                $form['head_type_video_url'][] = _sFile($form['head_type_video']);
                $form['head_type_value_url'][] = _sImage($video[0]);
                break;
            default:
                $form['head_type_value_url'][]   = _sImage($form['head_type_value']);
                $form['head_type_value_image'][] = $form['head_type_value'];
                break;
        }
        $formItem = new FormItem();
        $items    = $formItem->where(['form_id' => $id])->order('sort asc')->select();
        if (!$items->isEmpty()) {
            $items      = $items->toArray();
            $goodsModel = new Goods();
            foreach ($items as $key => $val) {
                if ($val['type'] == 'goods') {
                    $detial               = $goodsModel->getGoodsDetial($val['value'],'id,bn,name,price,costprice,mktprice,image_id,goods_cat_id,goods_type_id,brand_id,is_nomal_virtual,marketable,stock,freeze_stock,weight,spes_desc');
                    $items[$key]['goods'] = $detial['data'];
                } elseif ($val['type'] == 'radio') {
                    $items[$key]['radio_value'] = explode(' ', $val['value']);
                } elseif ($val['type'] == 'range') {
                    $items[$key]['range_value'] = explode(',', $val['value']);
                } elseif ($val['type'] == 'checbox') {
                    $checbox_value = explode(' ', $val['value']);
                    $default       = explode(' ', $val['default']);
                    $temp_checbox  = [];
                    foreach ((array)$checbox_value as $k => $v) {
                        if (in_array($v, $default)) {
                            $temp_checbox[$k]['checked'] = true;
                        } else {
                            $temp_checbox[$k]['checked'] = false;
                        }
                        $temp_checbox[$k]['value'] = $v;
                    }
                    $items[$key]['checbox_value'] = $temp_checbox;
                } elseif ($val['type'] == 'date') {
                    if (!$val['default_value']) {//没有值的时候设置下默认值
                        $items[$key]['default_value'] = date('Y-m-d');
                    }
                } elseif ($val['type'] == 'time') {
                    if (!$val['default_value']) {//没有值的时候设置下默认值
                        $items[$key]['default_value'] = date('H:i');
                    }
                }
            }
        }
        $form['items']    = $items;
        $result['data']   = $form;
        $result['status'] = true;
        $result['msg']    = '查询成功';
        return $result;
    }

    /**
     * 删除form表单，有用户提交记录时，需要先删除提交记录，再删除表单
     * @param int $id
     * @return array|bool
     */
    public function deleteForm($id = 0)
    {
        $result = [
            'status' => false,
            'msg'    => '删除失败',
            'data'   => ''
        ];
        if (!$id) {
            $result['msg'] = '关键参数丢失';
            return $result;
        }
        //查询是否有表单结果
        $formSubmit = new FormSubmit();
        $userForm   = $formSubmit->where(['form_id' => $id])->count();
        if ($userForm > 0) {
            $result['msg'] = '请先删除该表单下用户的提交记录';
            return $result;
        }
        Db::startTrans();
        $res = $this->where(['id' => $id])->delete();
        if (!$res) {
            Db::rollback();
            return false;
        }
        $formItem = new FormItem();
        $res      = $formItem->where(['form_id' => $id])->delete();
        if (!$res) {
            Db::rollback();
            return false;
        }
        Db::commit();
        $result['msg']    = '删除成功';
        $result['status'] = true;
        return $result;
    }

    /*
     * 编辑数据
     */
    public function edit($where = [], $data)
    {
        Db::startTrans();
        $res = $this->save($data, $where);

        if (!$res) {
            Db::rollback();
            return false;
        }
        $goodsModel = new Goods();
        $formid     = $data['id'];
        if ($data['field']) {
            $field = [];
            foreach ($data['field']['name'] as $key => $value) {
                if ($data['field']['type'][$key] == 'goods') {
                    $goodsinfo = $goodsModel->field('name')->where(['id' => intval($data['field']['value'][$key])])->find();
                    $value     = $goodsinfo['name'];
                }
                $field[$key] = [
                    'id'              => isset($data['field']['id'][$key]) ? $data['field']['id'][$key] : 0,
                    'form_id'         => $formid,
                    'name'            => $value,
                    'type'            => $data['field']['type'][$key],
                    'value'           => $data['field']['value'][$key],
                    'default_value'   => $data['field']['default'][$key],
                    'validation_type' => $data['field']['validation_type'][$key],
                    'required'        => isset($data['field']['required'][$key]) ? $data['field']['required'][$key] : '2',
                    'sort'            => $data['field']['sort'][$key],
                ];
            }

            $ids = [];
            foreach ((array)$field as $key => $val) {
                $formItem = new FormItem();
                if ($val['id']) {
                    $ids[] = $val['id'];
                    $res = $formItem->update($val, ['id' => $val['id']]);
                } else {
                    $res = $formItem->add($val);
                    $ids[] = $formItem->getLastInsID();
                }
                if ($res === false) {
                    Db::rollback();
                    return false;
                }
            }
            //删除不匹配的
            if ($ids) {
                $formItem = new FormItem();
                $where   = [];
                $where[] = ['id', 'not in', $ids];
                $where[] = ['form_id', '=', $formid];
                $formItem->where($where)->delete();
            }
        }
        Db::commit();
        return true;
    }

    /**
     * 获取form列表
     * @param string $field
     * @return array
     */
    public function getAll($field = '*')
    {
        $list = $this->field($field)->select();
        if (!$list->isEmpty()) {
            return $list->toArray();
        } else {
            return [];
        }
    }

}