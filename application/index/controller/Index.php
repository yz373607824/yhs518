<?php

namespace app\index\controller;

use app\admin\model\UserEnterprise;
use app\common\controller\Frontend;
use think\Config;
use think\Db;
use think\paginator\driver\Bootstrap;

class Index extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        //判断是否是移动端
        /*if ($this->request->isMobile()) {
           $this->redirect('/mobile');
        }*/
        //当前零时时间
        $today = strtotime(date("Y-m-d"),time());
        //当前24时时间
        $end = $today+60*60*24;

        $onlineNum = \app\admin\model\expert\Online::where('createtime', '>', $today)->where('createtime', '<', $end)->count();
        $localeNum = \app\admin\model\expert\Locale::where('createtime', '>', $today)->where('createtime', '<', $end)->count();
        //今日登陆和提问数量
        $this->assign('loginNum', config('site.today_login_num') + intval(file_get_contents(ROOT_PATH . "counter.dat")));
        $this->assign('questionNum', config('site.today_question_num') + $onlineNum + $localeNum);

        $enterprise = UserEnterprise::get($this->auth->enterprise_id);
        if ($enterprise) {
            $this->assign('enterprise_level', $enterprise['level']);
        }

        Config::set('seo.title', Config::get("site.name"));
        Config::set('seo.keywords', Config::get("site.keywords"));
        Config::set('seo.description', Config::get("site.description"));
        return $this->view->fetch();
    }

    /**
     * 整站搜索
     */
    public function search()
    {
        $keywords = $this->request->param('keywords');
        $pagenum = $this->request->param('pagenum', 0);
        $pagesize = $this->request->param('pagesize', 10);

        $list = [];

        if ($keywords) {
            $array = Db::query("select id,title,createtime,1 type FROM uweb_cms_archives WHERE model_id NOT IN (19) AND status = 'normal' AND deletetime IS NULL AND title LIKE '%$keywords%' 
                    union select id,company as title,createtime,2 type FROM uweb_user_enterprise WHERE status = '2' AND company LIKE '%$keywords%' 
                    union select id,nickname as title,createtime,3 type FROM uweb_user_expert WHERE status = '1' AND nickname LIKE '%$keywords%' 
                    union select expert_id as id,title,createtime,4 type FROM uweb_expert_online WHERE expert_id IS NOT NULL AND title LIKE '%$keywords%' 
                    LIMIT " . ($pagenum * $pagesize) . ",$pagesize");
            $count = Db::query("select count(*) FROM uweb_cms_archives WHERE model_id NOT IN (19) AND status = 'normal' AND deletetime IS NULL AND title LIKE '%$keywords%' 
                    union select count(*) count FROM uweb_user_enterprise WHERE status = '2' AND company LIKE '%$keywords%' 
                    union select count(*) FROM uweb_user_expert WHERE status = '1' AND nickname LIKE '%$keywords%' 
                    union select count(*) FROM uweb_expert_online WHERE expert_id IS NOT NULL AND title LIKE '%$keywords%' ");

            $total = 0;
            foreach ($count as $item) {
                $total += $item['count(*)'];
            }

            $this->assign('total', $total);

            //遍历整理
            foreach ($array as $item) {
                $url = '';
                switch ($item['type']) {
                    case 1 :
                        $url = url('/archives/' . $item['id']);
                        break;
                    case 2 :
                        $url = url('/index/enterprise/detail', ['id' =>$item['id']]);
                        break;
                    case 3 :
                        $url = url('/expert/detail', ['id' =>$item['id']]);
                        break;
                    case 4 :
                        $url = url('/expert/detail', ['id' =>$item['id']]);
                        break;
                }
                $list[] = [
                    'title' => $item['title'],
                    'url' => $url,
                    'createtime' => $item['createtime'],
                ];
            }
        }

        $this->assign('pagenum', $pagenum);
        $this->assign('pagesize', $pagesize);
        $this->assign('list', $list);
        $this->assign('keywords', $keywords);
        $this->assign('seo.title', '搜索结果');
        return $this->view->fetch();
    }

    public function news()
    {
        $newslist = [];
        return jsonp(['newslist' => $newslist, 'new' => count($newslist), 'url' => 'http://www.uweb.net.cn/']);
    }

}
