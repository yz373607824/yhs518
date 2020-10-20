<?php

namespace app\mobile\controller;

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
        Config::set('seo.title', Config::get("site.name"));
        Config::set('seo.keywords', Config::get("site.keywords"));
        Config::set('seo.description', Config::get("site.description"));
        return $this->view->fetch();
    }

    public function test() {
        $user_url = url('/mobile/user/myAskquestion', '', true, true);
        $data = ['type' => '在线提问', 'time' => date('Y-m-d H:i', time()), 'title' => '测试信息'];
        //发送用户的微信信息
        Frontend::sendTplMsg('obLIE1dMl-DYTF7mKgZmK5CsEfnA', '您好，您的订单已支付，请留意专家的回复！', $user_url, $data);
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
            $array = Db::query("select id,title,createtime,model_id,1 type FROM uweb_cms_archives WHERE model_id IN (10, 11, 15) AND status = 'normal' AND deletetime IS NULL AND title LIKE '%$keywords%' 
                    union select id,company as title,createtime,0 model_id,2 type FROM uweb_user_enterprise WHERE status = '2' AND company LIKE '%$keywords%'
                    union select id,nickname as title,createtime,0 model_id,3 type FROM uweb_user_expert WHERE status = '1' AND nickname LIKE '%$keywords%' 
                    LIMIT " . ($pagenum * $pagesize) . ",$pagesize");
            $count = Db::query("select count(*) FROM uweb_cms_archives WHERE model_id IN (11, 15) AND status = 'normal' AND deletetime IS NULL AND title LIKE '%$keywords%' 
                    union select count(*) count FROM uweb_user_enterprise WHERE status = '2' AND company LIKE '%$keywords%'
                    union select count(*) FROM uweb_user_expert WHERE status = '1' AND nickname LIKE '%$keywords%' ");

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
                        $url = $item['model_id'] == 10 ? url('/archives/' . $item['id']) : url('/mobile/archives/' . $item['id']);
                        break;
                    case 2 :
                        $url = url('/index/enterprise/detail', ['id' =>$item['id']]);
                        break;
                    case 3 :
                        $url = url('/mobile/expert/detail', ['id' =>$item['id']]);
                        break;
                }
                $list[] = [
                    'title' => $item['title'],
                    'url' => $url,
                    'createtime' => $item['createtime'],
                ];
            }
        }

        //异步加载
        if ($this->request->isAjax()) {
            $html = '';
            foreach ($list as $item) {
                $html .= <<<HTML
                <li>
                    <div class="p">{$item['title']}</div>
                    <div class="more-btn">
                        <a href="{$item['url']}">查看详情</a>
                    </div>
                </li>
HTML;
            }

            $this->result($html);
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
