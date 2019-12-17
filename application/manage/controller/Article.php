<?php

namespace app\Manage\controller;

use app\common\controller\Manage;
use app\common\model\Article as articleModel;
use app\common\model\ArticleBanner as BannerModel;
use app\common\model\AshopBanner as AshopModel;
use app\common\model\ShopBannerVideo as sbvModel;
use app\common\model\ArticleType as articleTypeModel;
use think\Db;
use think\facade\Request;

class Article extends Manage
{
    /**
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $article = new articleModel();
        if (Request::isAjax()) {
            return $article->tableData(input('param.'));
        }
        $articleTypeModel = new articleTypeModel();
        $list             = $articleTypeModel->select();
        return $this->fetch('', ['list' => $list]);
    }


    /**
     *  文章添加
     * User:tianyu
     *
     * @return array|mixed
     */
    public function add()
    {
        if (Request::isPost()) {
            $article = new articleModel();
            return $article->addData(input('param.'));
        }
        $articleTypeModel = new articleTypeModel();
        $list             = $articleTypeModel->select();
        return $this->fetch('add', ['list' => $articleTypeModel->getTree($list)]);
    }


    /**
     *
     *  文章编辑
     *
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit()
    {
        $articleModel = new articleModel();
        if (Request::isPost()) {
            return $articleModel->saveData(input('param.'));
        }
        $info = $articleModel->with('articleType')->where('id', input('param.id/d'))->find();
        if (!$info) {
            return error_code(10002);
        }
        $articleTypeModel = new articleTypeModel();
        $list             = $articleTypeModel->select();
        return $this->fetch('edit', ['info' => $info, 'list' => $list]);
    }


    /**
     *
     * User:tianyu
     *
     * @return array
     */
    public function del()
    {
        $article = new articleModel();
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
     * 资讯轮播图列表
     *
     */
    public function banner()
    {
        if(Request::isAjax())
        {
            $carouselModel = new BannerModel();
            return  $carouselModel->tableData(input('param.'));
        }
        return $this->fetch('banner_index');
    }

    /**
     * 前台
     * @return mixed
     */
    public function banner_index()
    {
        if(Request::isAjax())
        {
            $carouselModel = new BannerModel();
            return  $carouselModel->tableData(input('param.'));
        }
        return $this->fetch();
    }
    /**
     * 增加一个轮播图
     */
    public function add_banner()
    {
        return $this->fetch('banner_add');
    }

    /**
     * 执行添加轮播图
     */
    public function do_banner_add()
    {
        if(Request::isPost())
        {
            $data = input();
            unset($data['__Jshop_Token__']);
            $data['ctime'] = time();
            $res = Db::name('article_banner')->insertGetId($data);
            if($res){
                $result  = [
                    'status' => true,
                    'msg'    => '添加成功',
                    'data'   => ''
                ];
            }else{
                $result['status'] = false;
                $result['msg']    = '添加失败';
            }
        }
        return $result;
    }
    //删除banner
    public function del_banner()
    {
        $id = input('id');
        $res = Db::name('article_banner')->delete($id);
        if($res){
            $result  = [
                'status' => true,
                'msg'    => '删除成功',
                'data'   => ''
            ];
        }else{
            $result['status'] = false;
            $result['msg']    = '删除失败';
        }
        return $result;
    }


    /********************************************首页视频轮播*********************************************************/

    /*
     * 首页轮播视频列表
    */
    public function  shop_banner()
    {
        if(Request::isAjax())
        {
            $carouselModel = new sbvModel();
            return  $carouselModel->tableData(input('param.'));
        }
        return $this->fetch('shop_index');
    }

    /**
     * 添加首页轮播视频
     */
    public  function add_shop_banner()
    {

        return $this->fetch('add_shop_banner');
    }

    /**
     * 执行添加首页轮播视频
     * 执行添加商品的轮播视频
     * 两个接口合一起了,懒得写了,真几把麻烦弄这个轮播图,又没什么技术含量,哎
     */
    public function do_shop_banner_add()
    {
        if(Request::isPost())
        {
            //如果有商品id, 那么就保存到goods_video表,
            if(input('goods_id')){
                $video = $this->request->file('file');
                $vname = uploadVideo($video);
                if($vname['status'] == false){
                    $this->error('格式错误');
                }
                $data = input();
                $data['video'] = $vname['data']['url'];
                unset($data['__Jshop_Token__']);
                $data['ctime'] = time();
                $res = Db::name('goods_video')->insertGetId($data);
                if($res){
                    $this->success('添加成功');
                }else{
                    $this->success('添加失败');
                }
            }else{
                $video = $this->request->file('file');
                $vname = uploadVideo($video);
                if($vname['status'] == false){
                    $this->error('格式错误');
                }
                $data = input();
                $data['video'] = $vname['data']['url'];
                unset($data['__Jshop_Token__']);
                $data['ctime'] = time();
                $res = Db::name('shop_banner_video')->insertGetId($data);
                if($res){
                    $this->success('添加成功','shop_banner');
                }else{
                    $this->success('添加失败','shop_banner');
                }
            }
        }
    }
    //删除banner
    public function del_shop_banner()
    {
        $id = input('id');
        $res = Db::name('shop_banner_video')->delete($id);
        if($res){
            $result  = [
                'status' => true,
                'msg'    => '删除成功',
                'data'   => ''
            ];
        }else{
            $result['status'] = false;
            $result['msg']    = '删除失败';
        }
        return $result;
    }


    /*****************首页图片轮播图********************/

    /**
     * 前台
     * @return mixed
     */
    public function ashop_banner_index()
    {
        if(Request::isAjax())
        {
            $carouselModel = new AshopModel();
            return  $carouselModel->tableData(input('param.'));
        }
        return $this->fetch('ashop_banner');
    }

    /**
     * 编辑轮播图跳转到哪个商品
     */
    public function edit_banner()
    {
        $id = input('id');
        $this->assign('banner_id',$id);
        $info = Db::name('ashop_banner')->find($id);
        $where = [];
        $where[] = ['marketable','eq',1];
        $goods = Db::name('goods')->where($where)->select();
        $this->assign('goods',$goods);
        $this->assign('id',$info['gid']);
        return $this->fetch('edit_banner');
    }

    /**
     * 执行编辑
     */
    public function do_edit_banner()
    {
        $banner_id = input('banner_id');
        $gid = input('gid');
        $res  =  Db::name('ashop_banner')->where('id',$banner_id)->setField('gid',$gid);
        if($res){
            $result  = [
                'status' => true,
                'msg'    => '添加成功',
                'data'   => ''
            ];
        }else{
            $result['status'] = false;
            $result['msg']    = '添加失败';
        }
        return $result;
    }
    /**
     * 增加一个轮播图
     */
    public function add_ashop_banner()
    {
        return $this->fetch('ashop_banner_add');
    }

    /**
     * 执行添加轮播图
     */
    public function do_ashop_banner_add()
    {
        if(Request::isPost())
        {
            $data = input();
            unset($data['__Jshop_Token__']);
            $data['ctime'] = time();
            $res = Db::name('ashop_banner')->insertGetId($data);
            if($res){
                $result  = [
                    'status' => true,
                    'msg'    => '添加成功',
                    'data'   => ''
                ];
            }else{
                $result['status'] = false;
                $result['msg']    = '添加失败';
            }
        }
        return $result;
    }
    //删除banner
    public function del_ashop_banner()
    {
        $id = input('id');
        $res = Db::name('ashop_banner')->delete($id);
        if($res){
            $result  = [
                'status' => true,
                'msg'    => '删除成功',
                'data'   => ''
            ];
        }else{
            $result['status'] = false;
            $result['msg']    = '删除失败';
        }
        return $result;
    }
}
