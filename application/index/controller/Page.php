<?php

namespace addons\cms\controller;

use addons\cms\model\Page as PageModel;
use app\common\controller\Frontend;
use think\Config;

/**
 * 单页控制器
 */
class Page extends Frontend
{

    public function index()
    {
        $diyname = $this->request->param('diyname');
        if ($diyname && !is_numeric($diyname)) {
            $page = PageModel::getByDiyname($diyname);
        } else {
            $id = $diyname ? $diyname : $this->request->param('id', '');
            $page = PageModel::get($id);
        }
        if (!$page || $page['status'] != 'normal') {
            $this->error(__('No specified page found'));
        }
        $this->view->assign("__PAGE__", $page);
        Config::set('seo.title', $page['title']);
        Config::set('seo.keywords', $page['keywords']);
        Config::set('seo.description', $page['description']);
        $template = preg_replace("/\.html$/i", "", $page['showtpl'] ? $page['showtpl'] : 'page');
        return $this->view->fetch('/' . $template);
    }

}
