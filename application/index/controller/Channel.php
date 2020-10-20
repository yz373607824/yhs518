<?php

namespace app\index\controller;

use addons\cms\model\Archives;
use addons\cms\model\Channel as ChannelModel;
use addons\cms\model\Modelx;
use app\admin\model\cms\Collect;
use app\admin\model\cms\Tags;
use think\Config;
use app\common\controller\Frontend;

/**
 * 栏目控制器
 */
class Channel extends Frontend
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    const FORMULA_ID = 70; //投稿配方
    const KONWLEDGE_ID = 74; //行业知识库
    const ANALYSIS_ID = 73; //配方分析
    const SUPPLY_CID = 69; //供应产品栏目id
    const PURCHASE_CID = 67; //求购栏目id
    const RECRUIT_CID = 71; //人才招聘栏目id
    const VOTE_CID = 128; //我要投票栏目id
    const ENCYCLOPEDIAS_CID = 133; //化工字典栏目id

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        $diyname = $this->request->param('diyname');

        if ($diyname && !is_numeric($diyname)) {
            $channel = ChannelModel::getByDiyname($diyname);
        } else {
            $id = $diyname ? $diyname : $this->request->param('id', '');
            $channel = ChannelModel::get($id);
        }
        if (!$channel) {
            $this->error(__('No specified channel found'));
        }

        //检查是否有默认跳转，有就跳转到默认跳转
        if (!empty($channel['gotoid'])) {
            $channel = ChannelModel::get($channel['gotoid']);
        }

        //判断是否是单页
        if ($channel['type'] === 'page') {
            $archives = Archives::get(['channel_id'=>$channel['id']]);
            if (!$archives || ($archives['user_id'] != $this->auth->id && $archives['status'] != 'normal') || $archives['deletetime']) {
                $this->error(__('No specified article found'));
            }
            $this->redirect(url('/archives/' . $archives['id']));
            exit();
        }

        $filterlist = [];
        $orderlist = [];

        $filter = $this->request->get('filter/a', []);
        $orderby = $this->request->get('orderby', '');
        $orderway = $this->request->get('orderway', '', 'strtolower');
        $tag = $this->request->get('tag', '');
        $keyword = $this->request->get('keyword', '');
        $keywords = $this->request->get('keywords', '');
        $encyclopediasKeyword = $this->request->get('encyclopediasKeyword', '');
        $category_type = $this->request->param('category_type', null);
        $category_value = $this->request->param('category_value', null);
        $is_excellent = $this->request->get('excellent', '');

        $params = ['filter' => '', 'id' => $channel->id, 'diyname' => $channel->diyname];
        if ($filter)
            $params['filter'] = $filter;
        if ($orderby)
            $params['orderby'] = $orderby;
        if ($orderway)
            $params['orderway'] = $orderway;
        if ($tag)
            $params['tag'] = $tag;
        if ($keyword)
            $params['keyword'] = $keyword;
        if ($keywords)
            $params['keywords'] = $keywords;
        if ($category_type)
            $params['category_type'] = $category_type;
        if ($category_value)
            $params['category_value'] = $category_value;
        if ($encyclopediasKeyword)
            $params['encyclopediasKeyword'] = $encyclopediasKeyword;
        if ($channel['type'] === 'link') {
            $this->redirect($channel['outlink']);
        }

        //化工字典搜索条件
        if ($channel['id'] == self::ENCYCLOPEDIAS_CID && isset($encyclopediasKeyword) && trim($encyclopediasKeyword) != '') {
            $encyclopediasWhere = $this->getEncyclopediasWhere($encyclopediasKeyword);
        }

        //优质供求产品推荐
        if ($is_excellent) {
            $params['excellent'] = $is_excellent;
        }

        //省市区搜索
        $province = $this->request->get('province', '');
        $city = $this->request->get('city', '');
        $area = $this->request->get('area', '');
        if ($province)
            $params['province'] = $province;
        if ($city)
            $params['city'] = $city;
        if ($area)
            $params['area'] = $area;


        $model = Modelx::get($channel['model_id']);
        if (!$model) {
            $this->error(__('No specified model found'));
        }
        $fields = [];
        foreach ($model->fields_list as $k => $v) {
            if (!$v['isfilter'] || !in_array($v['type'], ['select', 'selects', 'checkbox', 'radio', 'array']) || !$v['content_list'])
                continue;
            //行业点睛和行业展会类别
            if ($channel['id'] == 47 && $v['name'] == 'category')
                continue;
            if ($channel['id'] == 49 && $v['name'] == 'type')
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

        //栏目对应的标签
        $tagsData = db('cms_tags')->where(['type' => $channel['id'], 'status' => '1'])->column('name', 'id');
        $tagsData = [0 => __('全部')] + $tagsData;
        $tags = [];
        foreach ($tagsData as $k => $v) {
            $active = ($k === 0 && !$tag) || (isset($v) && $tag == $v) ? true : false;
            $prepare = $k === 0 ? array_diff_key($params, ['tag' => $k]) : array_merge($params, ['tag' => $v]);
            $url = '?' . http_build_query($prepare);
            $tags[] = ['title' => $v, 'active' => $active, 'url' => $url];
        }


        $sortrank = [
//            ['name' => 'default', 'field' => 'weigh', 'title' => __('Default')],
            ['name' => 'views', 'field' => 'views', 'title' => __('Views')],
            ['name' => 'paynum', 'field' => 'paynum', 'title' => __('Paynum')],
        ];

        // 扩展免费付费选项
        $free_charge=array_slice($tags,1,2);
        $this->assign('free_charge',$free_charge);

        $orderby = $orderby && in_array($orderby, ['default', 'paynum', 'views']) ? $orderby : 'default';
        $orderway = $orderway ? $orderway : 'desc';
        foreach ($sortrank as $k => $v) {
            $url = '?' . http_build_query(array_merge($params, ['orderby' => $v['name'], 'orderway' => ($orderway == 'desc' ? 'asc' : 'desc')]));
            $v['active'] = $orderby == $v['name'] ? true : false;
            $v['orderby'] = $orderway;
            $v['url'] = $url;
            $orderlist[] = $v;
        }
        $orderby = $orderby == 'default' ? 'weigh' : $orderby;

        $pagelist = Archives::alias('a')
            ->where('channel_id', $channel['id'])
            ->where('a.status', 'normal')
            ->where('deletetime', 'exp', \think\Db::raw('IS NULL'))
            ->where($filter);

        //标签筛选
        if (!empty($tag)) {
            $pagelist->whereLike('tags', '%'.$tag.'%');
        }
        //关键字搜索
        if (!empty($keyword)) {
            $pagelist->whereLike('title', '%'.$keyword.'%');
        }
        //优质供求产品推荐
        if (!empty($is_excellent)) {
            $pagelist->where('n.is_excellent', '1');
        }
        //省市区搜索
        if (!empty($province)) {
            $pagelist->where('n.province', $province);
        }
        if (!empty($city)) {
            $pagelist->where('n.city', $city);
        }
        if (!empty($area)) {
            $pagelist->where('n.area', $area);
        }

        //化工字典搜索
        if (!empty($encyclopediasWhere)) {
            if (isset($encyclopediasWhere['n.id'])) {
                $pagelist->where('n.id', 'in', $encyclopediasWhere['n.id']);
            } else {
                $pagelist->where(function ($query) use ($encyclopediasWhere) {
                    $query->whereOr($encyclopediasWhere);
                });
            }
        }

        //关联企业、会员信息
        if (in_array($channel['id'], [self::SUPPLY_CID, self::PURCHASE_CID, self::RECRUIT_CID])) {
            //企业信息表
            $pagelist->join('user_enterprise ue', 'a.user_id = ue.user_id', 'LEFT')->field('ue.id enterprise_id,ue.level,ue.company,ue.is_excellent');
            //会员表
            $pagelist->join('user u', 'ue.user_id = u.id', 'LEFT')->field('u.expire,u.online');
        }

        //过滤下架的公司的招聘信息
        if ($channel['id'] == self::RECRUIT_CID) {
            $pagelist->where('ue.status', '2');
        }

        //前后处理
        if ($category_type && $category_value) {
            $pagelist->whereLike($category_type, '%'.$category_value.'%');
            $this->view->assign("category_type", $category_type);
            $this->view->assign("category_value", $category_value);
        }

        //判断是否有关键字
        if (!empty($keywords)) {
            $pagelist->whereLike('title', '%'.$keywords.'%');
        }

        //供求产品排序调整
        if ($channel['id'] == self::SUPPLY_CID || $channel['id'] == self::PURCHASE_CID) {
            $orderby = 'updatetime';
            $orderway = 'DESC';
        }

        //我要投票排序调整
        if ($channel['id'] == self::VOTE_CID) {
            $pagelist->field('invent_vote + real_vote as vote');
            $orderby = 'vote';
            $orderway = 'DESC';
        }

        //化工字典排序调整
        if ($channel['id'] == self::ENCYCLOPEDIAS_CID && !isset($encyclopediasWhere['n.id'])) {
            $orderby = 'views';
            $orderway = 'DESC';
        }
        if (isset($encyclopediasWhere['n.id'])) {
            $orderRaw = "find_in_set(`n`.`id`,'".$encyclopediasWhere['n.id']."')";
        }



        $pagelist = $pagelist->join($model['table'] . ' n', 'a.id=n.id', 'LEFT')
            ->field('a.*,content')
            ->field('id', true, config('database.prefix') . $model['table'], 'n');
        if (isset($orderRaw)) {
            $pagelist = $pagelist->orderRaw($orderRaw);
        } else {
            $pagelist = $pagelist->order($orderby, $orderway);
        }
        $pagelist = $pagelist->paginate($channel['pagesize'], false, ['type' => '\\app\\common\\controller\\ExpertPage']);

        $pagelist->appends($params);

        //获取前台要显示标签
        if (in_array($channel['id'], [self::FORMULA_ID, self::KONWLEDGE_ID])) {
            $tagsList = Tags::all(['type'=>$channel['id'], 'status' => '1']);
            $this->assign('tagsList', $tagsList);
            $allFlag = true;
            foreach ($tagsList as $v) {
                if ($keywords == $v['name'])
                    $allFlag = false;
            }
            $this->assign('allFlag', $allFlag);
            $this->disposeCategory($channel['id'] == self::FORMULA_ID ? 'formula' : 'konwledge');

            //列表转为数组
            $idList = collection($pagelist)->toArray();
            //获取文章ID数组
            $ids = array_column($idList[array_keys($idList)[1]], 'id');
            //根据文章ID查询用户已经收藏的文章
            $collectList = array_column(collection(Collect::whereIn('archives_id', implode(',', $ids))->where('user_id', $this->auth->id)->field('archives_id')->select())->toArray(), 'archives_id');

            foreach ($pagelist as &$item) {
                //付费数增加基数
                $item['paynum'] = $item['paynum'] + config('site.article_pay_num');

                //判断是否已经点赞
                if ($item['user_ids']) {
                    $userIds = explode(',', $item['user_ids']);
                    if ($this->auth->id && in_array($this->auth->id, $userIds)) {
                        $item['like'] = true;
                    } else {
                        $item['like'] = false;
                    }
                } else {
                    $item['like'] = false;
                }

                //判断是否已经收藏
                if (in_array($item['id'], $collectList)) {
                    $item['collect'] = true;
                } else {
                    $item['collect'] = false;
                }
            }
        }

        $this->view->assign("tags", $tags);
        $this->view->assign("tag", $tag);
        $this->view->assign("keywords", $keywords);
        $this->view->assign("encyclopediasKeyword", $encyclopediasKeyword);
        $this->view->assign("__FILTERLIST__", $filterlist);
        $this->view->assign("__ORDERLIST__", $orderlist);
        $this->view->assign("__PAGELIST__", $pagelist);
        $this->view->assign("__CHANNEL__", $channel);
        $this->view->assign('seo_title', $channel['name']);
        $this->view->assign('seo_keywords', $channel['keywords']);
        $this->view->assign('seo_description', $channel['description']);
        $template = preg_replace('/\.html$/', '', $channel["{$channel['type']}tpl"]);
        return $this->view->fetch('cms/' . $template);
    }

    /**
     * @comment 化工字典搜索条件
     * @param $keyword string 搜索关键字
     * @return array
     * @throws \Exception
     */
    public function getEncyclopediasWhere($keyword)
    {
        //搜索条件初始化
        $where = [];
        $encyclopediasIds = '';

        //中文名称搜索
        $regularC = '/[\x{4e00}-\x{9fa5}]/u';//匹配中文正则
        preg_match_all($regularC, $keyword, $chinese);
        //完全匹配中文别名
        $encyclopediasIdByAlias = db('cms_encyclopedias')->field('alias_name,name,id')->where('alias_name', 'like','%'.trim($keyword).'%')->limit(5)->select();//模糊查询中文别名
        if ($chinese[0] || $encyclopediasIdByAlias) {
            $chinese = array_unique($chinese[0]);
            $chineseString = implode($chinese,',');

            //完全匹配中文名
            $preciseData = db('cms_archives')->where([
                'title'=> $keyword,
                'channel_id' => 133,
                'model_id' => 24
            ])->column('id');

            //完全匹配中文别名
            //模糊数据处理成完全匹配数据
            /*foreach ($encyclopediasIdByAlias as $key => $value) {
                $aliasNameDatas = explode('&lt;font class=&quot;font-orange&quot;&gt;|&lt;/font&gt;',$value['alias_name']);
                $identify = false;
                foreach ($aliasNameDatas as $item) {
                    $aliasNameData = trim($item);
                    if ($aliasNameData == $keyword) $identify = true;
                }
                if (!$identify) unset($encyclopediasIdByAlias[$key]);
            }*/
            $aliasData = $encyclopediasIdByAlias ? array_column($encyclopediasIdByAlias,'id') : '';
            //合并 完全匹配中文名/完全匹配中文别名
            if ($preciseData && $aliasData) {
                foreach ($aliasData as $aliasDatum) {
                    array_unshift($preciseData, $aliasDatum);
                }
            } elseif ($aliasData) {
                $preciseData = $aliasData;
            }

            //中文关键字搜索
            $encyclopediasId = db('cms_encyclopedias_keyword')->where('name', 'in',$chineseString)->column('encyclopedias_id');
            foreach ($encyclopediasId as $value) {
                $encyclopediasIds = $encyclopediasIds ? $encyclopediasIds.','.$value : $value;
            }
            $encyclopediasIdk = array_count_values(explode(',',$encyclopediasIds));
            //按匹配中文字个数排序
            arsort($encyclopediasIdk);
            $arrayKeys = array_keys($encyclopediasIdk);

            if ($preciseData) {
                foreach ($preciseData as $preciseDatum) {
                    array_unshift($arrayKeys,$preciseDatum);
                }
            }
            $encyclopediasIds = implode(array_unique($arrayKeys), ',');
            $where['n.id'] = $encyclopediasIds;
        }

        if (!$encyclopediasIds) {
            //英文名称搜索
            $regularEN = '/[a-zA-Z]+/';//匹配英文正则
            preg_match_all($regularEN, $keyword, $englishName);
            if ($englishName[0]) {
                $enname = implode(' ', $englishName[0]);
                $where['enname'] = ['like', '%' . $enname . '%', 'OR'];
            }
            //化学式搜索
            $regularCF = '/[a-zA-Z][a-zA-Z]?\d*|\((?:[^()]*(?:\(.*\))?[^()]*)+\)\d+/';//匹配化学式正则
            preg_match_all($regularCF, $keyword, $chemicalFormula);
            if ($chemicalFormula[0]) {
                $chemicalFormula = implode('', $chemicalFormula[0]);
                $where['chemical_formula_num'] = ['like', '%' . $chemicalFormula . '%', 'OR'];
            }
            //cas号搜索
            $regularCAS = '/\d{2,7}-\d{2}-\d{1}/';//匹配化学式正则
            preg_match_all($regularCAS, $keyword, $CAS);
            if ($CAS[0]) {
                $where['cas_code'] = $CAS[0][0];
            }

            if (!$where) {
                $where['cas_code'] = ['like', '%' . $keyword . '%', 'OR'];
            }
        }
        return $where;
    }
}

