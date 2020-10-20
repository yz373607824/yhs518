<?php

namespace app\index\controller;

use addons\cms\model\Modelx;
use app\common\controller\Frontend;
use app\common\library\Token;
use think\Config;

/**
 * 企业栏目
 */
class Enterprise extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    protected $noCheck = ['index', 'excellent'];

    //企业详情
    protected $info = [];
    protected $enterprise_id = 0;
    protected $level = 0;

    const SUPPLY_MID = 10;//供应产品模块id
    const PURCHASE_MID = 8;//求购产品模块id
    const HONOR_MID = 16;//荣誉资质模块id
    const RECRUIT_MID = 12;//人才招聘模块id
    const SUPPLY_CID = 69; //供应产品栏目ID

    public function _initialize()
    {

        //企业权限验证
        if (!in_array($this->request->action(), $this->noCheck)) {
            //企业详情
            $id = input('id');
            $info = db('user_enterprise')->where(['id' => $id, 'status' => ['gt', 1]])->find();

            if (!$info) {
                $this->error(__('You have no permission'));
            }

            $this->info = $info;
            $this->level = $info['level'];
            $this->enterprise_id = $info['id'];
            $this->assign('info', $info);
            $this->assign('level', $info['level']);
            $this->layout = $this->level == 1 ? 'enterprise_silver' : 'enterprise';
        }


        parent::_initialize();
    }

    /**
     * 联盟企业列表
     */
    public function index()
    {
        //banner
        $banner = get_banner(3, 1);
        //搜索广告
        $ad = get_banner(37, 1);

        $this->assign('banner', $banner);
        $this->assign('ad', $ad);
        $this->assign('seo_title', '联盟企业');

        $this->lists(false);
        return $this->view->fetch('settled_enterprise');
    }

    /**
     * 王者供应商列表
     */
    public function excellent()
    {
        //banner
        $banner = get_banner(49, 1);
        //搜索广告
        $ad = get_banner(57, 1);

        $this->assign('banner', $banner);
        $this->assign('ad', $ad);
        $this->assign('seo_title', '王者供应商');

        $this->lists(true);
        return $this->view->fetch('excellent_enterprise');
    }

    /**
     * 企业列表
     */
    private function lists($isExcellent = false)
    {
        //搜索关键字
        $keywords = $this->request->get('keywords', null,'addslashes,htmlspecialchars');
        //供应商类别筛选
        $supplierType = input('supplierType');

        //初始条件
        $where['a.status'] = ['gt', '1'];
        $search = '';

        //搜索
        if (!empty($keywords)) {
            $search = "a.company LIKE '%$keywords%' OR a.product LIKE '%$keywords%'";
        }
        //优质供应商
        if ($isExcellent === true) {
            $where['a.is_excellent'] = '1';
        }
        //筛选
        if (!empty($supplierType)) {
            $where['a.supplier_type'] = ['like', '%' . $supplierType . '%'];
        }

        $list = db('user_enterprise')->alias('a')
            ->field('a.id,a.user_id,a.logo,a.company,a.level,a.is_excellent,b.online,a.product_logo,a.product_url')
            ->join('user b', 'a.user_id = b.id', 'LEFT')
            ->where($where) //已审核过
            ->where($search)
            ->order('b.online DESC,b.loginnum DESC,a.weigh DESC')
            ->paginate(config('page_size.enterprise'), false, [
                'query' => ['keywords' => $keywords],
                'type'  => '\\app\\common\\controller\\ExpertPage'
            ]);

        $paged = $list->render();
        $list = $list->items();

        //查询产品
        foreach ($list as $k => $v) {
            $list[$k]['pro'] = db('cms_archives')->alias('a')
                ->field('a.title,a.image,a.id,b.price,unit')
                ->join('cms_supply b', 'a.id = b.id', 'LEFT')
                ->where(['a.user_id' => $v['user_id'], 'a.model_id' => self::SUPPLY_MID, 'a.status' => 'normal', 'a.deletetime' => null])
                ->order('b.is_excellent DESC,a.updatetime DESC')
                ->limit(4)
                ->select();
            //推荐产品
            $list[$k]['recommend'] = db('cms_archives')->alias('a')
                ->field('a.image,a.id')
                ->join('cms_supply b', 'a.id = b.id', 'LEFT')
                ->where(['a.user_id' => $v['user_id'], 'a.model_id' => self::SUPPLY_MID, 'a.status' => 'normal', 'a.deletetime' => null])
                ->where(['b.is_company' => '1'])
                ->order('a.updatetime DESC')
                ->limit(1)
                ->select();
        }

        $this->assign('list', $list);
        $this->assign('paged', $paged);
        $this->assign('isExcellent', $isExcellent);
        $this->assign('supplierType', config('enterprise.supplier_type'));
    }

    /**
     * 关于企业
     * @return string
     */
    public function detail()
    {
        //荣誉资质
        if ($this->info['level'] > 1) {
            $honor = db('cms_archives')
                ->where(['deletetime' => null, 'status' => 'normal', 'model_id' => self::HONOR_MID, 'user_id' => $this->info['user_id']])
                ->order('weigh', 'DESC')
                ->select();
            $this->assign('honor', $honor);
        }

        //栏目名称
        $column = '关于我们';
        $this->assign('column', $column);
        $this->assign('seo_title', $this->info['company'] . '-' . $column);
        $this->assign('enterprise_type', config('enterprise.enterprise_type'));
        $this->assign('business_type', config('enterprise.business_type'));
        return $this->view->fetch();
    }

    /**
     * 供应产品列表
     */
    public function supply()
    {
        //标签参数
        $tag = $this->request->get('tag', '');
        //模型字段的筛选参数
        $filter = $this->request->get('filter/a', []);
        //关键词参数
        $keyword = $this->request->get('keyword', '');
        //筛选参数
        $params['tag'] = $tag;
        $params['filter'] = $filter;

        //整理模型的筛选字段
        $model = Modelx::get(self::SUPPLY_MID);
        $fields = [];
        foreach ($model->fields_list as $k => $v) {
            if (!$v['isfilter'] || !in_array($v['type'], ['select', 'selects', 'checkbox', 'radio', 'array']) || !$v['content_list'])
                continue;
            $fields[] = [
                'name' => $v['name'], 'title' => $v['title'], 'content' => $v['content_list'], 'type' => $v['type']
            ];
        }
        $filter = array_intersect_key($filter, array_flip(array_column($fields, 'name')));
        foreach ($fields as $k => $v) {
            $content = [];
            $all = ['' => __('全部')] + $v['content'];
            foreach ($all as $m => $n) {
                $active = ($m === '' && !isset($filter[$v['name']])) || (isset($filter[$v['name']]) && $filter[$v['name']] == $m) ? TRUE : FALSE;
                $prepare = $m === '' ? array_diff_key($filter, [$v['name'] => $m]) : array_merge($filter, [$v['name'] => $m]);
                $url = '?' . http_build_query(array_merge(['filter' => $prepare], array_diff_key($params, ['filter' => ''])));
                $content[] = ['value' => $m, 'title' => $n, 'active' => $active, 'url' => $url];
            }
            //去除‘无’这个选项
            if( $content[1]['value'] == '无' && $content[1]['title'] == '无' ){
                unset($content[1]);
            }
            $filterlist[] = [
                'name'    => $v['name'],
                'title'   => $v['title'],
                'content' => $content,
            ];

            //多选字段调整查询条件
            if (in_array($v['type'], ['selects', 'checkbox']) && isset($filter[$v['name']])) {
                $filter[$v['name']] = ['LIKE', '%'. $filter[$v['name']] .'%'];
            }
        }

        //获取供应产品的标签列表
        $tagsData = db('cms_tags')->where(['type' => self::SUPPLY_CID, 'status' => '1'])->column('name', 'id');
        $tagsData = [0 => __('全部')] + $tagsData;
        $tags = [];
        foreach ($tagsData as $k => $v) {
            $active = ($k === 0 && !$tag) || (isset($v) && $tag == $v) ? true : false;
            $prepare = $k === 0 ? array_diff_key($params, ['tag' => $k]) : array_merge($params, ['tag' => $v]);
            $url = '?' . http_build_query($prepare);
            $tags[] = ['title' => $v, 'active' => $active, 'url' => $url];
        }

        $list = db('cms_archives a')
            ->field('a.id,image,title,tags,price,unit,description')
            ->join($model['table'] . ' b', 'a.id = b.id', 'LEFT')
            ->where(['deletetime' => null, 'status' => 'normal', 'model_id' => self::SUPPLY_MID, 'user_id' => $this->info['user_id']])
            ->where($filter)
            ->where(empty($tag) ? '1=1' : ['tags' => ['like', '%'.$tag.'%']])
            ->where(empty($keyword) ? '1=1' : ['title' => ['like', '%'.$keyword.'%']])
            ->order('updatetime', 'DESC')
            ->paginate(config('page_size.supply'), false,[
                'type' => '\\app\\common\\controller\\ExpertPage'
            ]);

        $paged = $list->render();
        $list = $list->items();

        foreach ($list as $k => $v) {
            //整理标签
            if ($v['tags']) {
                $list[$k]['tags'] = explode(',', $v['tags']);
            } else {
                $list[$k]['tags'] = [];
            }
        }

        //栏目名称
        $column = '供应产品';
        $this->view->assign("tags", $tags);
        $this->view->assign("__FILTERLIST__", $filterlist);
        $this->assign('column', $column);
        $this->assign('seo_title', $this->info['company'] . '-' . $column);
        $this->assign('list', $list);
        $this->assign('paged', $paged);
        return $this->view->fetch();
    }

    /**
     * 供应产品详情
     */
    public function supply_detail()
    {
        $itemid = input('itemid/d');

        $model = Modelx::get(self::SUPPLY_MID)->toArray();
        $read = db('cms_archives a')
            ->join($model['table'] . ' b', 'a.id = b.id', 'LEFT')
            ->where(['deletetime' => null, 'status' => 'normal', 'model_id' => self::SUPPLY_MID, 'user_id' => $this->info['user_id'], 'a.id' => $itemid])
            ->find();

        if (!$read) {
            $this->error(__('You have no permission'));
        }

        //整理标签
        if ($read['tags']) {
            $read['tags'] = explode(',', $read['tags']);
        } else {
            $read['tags'] = [];
        }

        $read['content'] = htmlspecialchars_decode($read['content']);

        //栏目名称
        $column = '供应产品';
        $this->assign('column', $column);
        $this->assign('read', $read);
        $this->assign('seo_title', $read['title']);
        $this->assign('seo_keywords', $read['keywords']);
        $this->assign('seo_description', $read['description']);
        return $this->view->fetch();
    }



    /**
     * 求购产品列表
     */
    public function purchase()
    {
        $model = Modelx::get(self::PURCHASE_MID)->toArray();
        $list = db('cms_archives a')
            ->field('a.id,title,number,unit,standard,description')
            ->join($model['table'] . ' b', 'a.id = b.id', 'LEFT')
            ->where(['deletetime' => null, 'status' => 'normal', 'model_id' => self::PURCHASE_MID, 'user_id' => $this->info['user_id']])
            ->order('updatetime', 'DESC')
            ->paginate(config('page_size.purchase'), false,[
                'type' => '\\app\\common\\controller\\ExpertPage'
            ]);

        //栏目名称
        $column = '求购产品';
        $this->assign('column', $column);
        $this->assign('seo_title', $this->info['company'] . '-' . $column);
        $this->assign('list', $list->items());
        $this->assign('paged', $list->render());
        return $this->view->fetch();
    }

    /**
     * 求购产品详情
     */
    public function purchase_detail()
    {
        $itemid = input('itemid/d');

        $model = Modelx::get(self::PURCHASE_MID)->toArray();
        $read = db('cms_archives a')
            ->join($model['table'] . ' b', 'a.id = b.id', 'LEFT')
            ->where(['deletetime' => null, 'status' => 'normal', 'model_id' => self::PURCHASE_MID, 'user_id' => $this->info['user_id'], 'a.id' => $itemid])
            ->find();

        if (!$read) {
            $this->error(__('You have no permission'));
        }

        $read['content'] = htmlspecialchars_decode($read['content']);

        //栏目名称
        $column = '求购产品';
        $this->assign('column', $column);
        $this->assign('read', $read);
        $this->assign('seo_title', $read['title']);
        $this->assign('seo_keywords', $read['keywords']);
        $this->assign('seo_description', $read['description']);
        return $this->view->fetch();
    }

    /**
     * 人才招聘列表
     */
    public function recruit()
    {
        $model = Modelx::get(self::RECRUIT_MID)->toArray();
        $list = db('cms_archives a')
            ->field('a.id,title,b.*')
            ->join($model['table'] . ' b', 'a.id = b.id', 'LEFT')
            ->where(['deletetime' => null, 'status' => 'normal', 'model_id' => self::RECRUIT_MID, 'user_id' => $this->info['user_id']])
            ->order('weigh', 'DESC')
            ->paginate(config('page_size.recruit'), false,[
                'type' => '\\app\\common\\controller\\ExpertPage'
            ]);

        //栏目名称
        $column = '人才招聘';
        $this->assign('column', $column);
        $this->assign('seo_title', $this->info['company'] . '-' . $column);
        $this->assign('list', $list->items());
        $this->assign('paged', $list->render());
        return $this->view->fetch();
    }

    /**
     * 联系方式
     */
    public function contact()
    {
        //栏目名称
        $column = '联系方式';
        $this->assign('column', $column);
        $this->assign('seo_title', $this->info['company'] . '-' . $column);
        return $this->view->fetch();
    }
}