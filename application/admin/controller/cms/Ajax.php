<?php

namespace app\admin\controller\cms;

use app\common\controller\Backend;

/**
 * Ajax
 *
 * @icon fa fa-circle-o
 * @internal
 */
class Ajax extends Backend
{

    /**
     * 模型对象
     */
    protected $model = null;
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 获取模板列表
     * @internal
     */
    public function get_template_list()
    {
        $files = [];
        $keyValue = $this->request->request("keyValue");
        if (!$keyValue) {
            $name = $this->request->request("name");
            if ($name) {
                $files[] = ['name' => $name . '.html'];
            }
            //设置过滤方法
            $this->request->filter(['strip_tags']);

//            $config = get_addon_config('cms');
//            $themeDir = ADDON_PATH . 'cms' . DS . 'view' . DS . $config['theme'] . DS;
            $themeDir = CMS_TEMP_PATH;
            $dh = opendir($themeDir);
            while (false !== ($filename = readdir($dh))) {
                if ($filename == '.' || $filename == '..' || strpos($filename,'.html') == false)
                    continue;
                $files[] = ['name' => $filename];
            }
        } else {
            $files[] = ['name' => $keyValue];
        }
        return $result = ['total' => count($files), 'list' => $files];
    }

    /**
     * 获取响应式模板列表
     * @internal
     */
    public function get_mobile_template_list()
    {
        $files = [];
        $keyValue = $this->request->request("keyValue");
        if (!$keyValue) {
            $name = $this->request->request("name");
            if ($name) {
                $files[] = ['name' => $name . '.html'];
            }
            //设置过滤方法
            $this->request->filter(['strip_tags']);

//            $config = get_addon_config('cms');
//            $themeDir = ADDON_PATH . 'cms' . DS . 'view' . DS . $config['theme'] . DS;
            $themeDir = ROOT_PATH . 'application' . DS . 'mobile' . DS . 'view' . DS . 'cms' . DS;
            $dh = opendir($themeDir);
            while (false !== ($filename = readdir($dh))) {
                if ($filename == '.' || $filename == '..' || strpos($filename,'.html') == false)
                    continue;
                $files[] = ['name' => $filename];
            }
        } else {
            $files[] = ['name' => $keyValue];
        }
        return $result = ['total' => count($files), 'list' => $files];
    }
}
