<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Lang;
use think\Log;

/**
 * Ajax异步请求接口
 * @internal
 */
class Ajax extends Frontend
{
    protected $noNeedLogin = ['lang', 'vote', 'upload', 'pcaJson', 'updateArchivesRecommends'];
    protected $noNeedRight = ['*'];
    protected $layout = '';

    /**
     * 更新文章推荐位信息（注意：更新前先备份数据库）
     */
    public function updateArchivesRecommends()
    {
        set_time_limit(0);
        $archives = db('cms_archives')->field('id,model_id')->select();
        $fields = [];
        $tables = [];
        foreach ($archives as $v) {
            $recommends = [];

            //推荐字段
            if (!isset($fields[$v['model_id']])) {
                $fields[$v['model_id']] = db('cms_fields')->where(['model_id' => $v['model_id'], 'isrecommend' => 1])->column('title', 'name');
            }

            //副表
            if (!isset($tables[$v['model_id']])) {
                $tables[$v['model_id']] = db('cms_model')->where('id', $v['model_id'])->value('table');
            }

            //副表值
            $value = db($tables[$v['model_id']])->where('id', $v['id'])->find();

            foreach ($fields[$v['model_id']] as $field => $fieldTitle) {
                if (isset($value[$field]) && $value[$field] == 1) {
                    $recommends[] = $fieldTitle;
                }
            }

            db('cms_archives')->where('id', $v['id'])->update(['recommends' => implode(',', $recommends)]);
        }
    }

    /**
     * PC端pca.js插件的json数据转化为手机端城市插件json数据
     */
    public function pcaJson()
    {
        //需要转化时将pac.js文件里的省市区json数据复制到该变量
        $pcJson = '';

        $pca = json_decode($pcJson, true);

        $i = 0;
        $data = [];
        foreach ($pca as $pro => $cityArr) {
            //省ID
            $i++;
            $proId = $i;

            //城市数组
            $cityData = [];
            foreach ($cityArr as $city => $areaArr) {
                //市ID
                $i++;
                $cityId = $i;

                //地区数组
                $areaData = [];
                foreach ($areaArr as $area) {
                    //区ID
                    $i++;
                    $areaId = $i;
                    $areaData[] = [
                        'id' => $areaId,
                        'name' => $area,
                    ];
                }

                $cityData[] = [
                    'id' => $cityId,
                    'name' => $city,
                    'child' => $areaData,
                ];
            }

            $data[] = [
                'id' => $proId,
                'name' => $pro,
                'child' => $cityData,
            ];
        }

        echo json_encode($data);exit;
    }

    /**
     * 投票
     */
    public function vote()
    {
        if (!$this->auth->id) {
            $this->error('请先登录', null, ['url' => url('user/login')]);
        }

        $id = input('id/d');

        //判断是否截止
        $channel = db('cms_archives')->alias('a')->field('b.vote_end_time,b.vote_repeat,b.id')->join('cms_channel b', 'a.channel_id = b.id', 'left')->where('a.id', $id)->find();
        if ($channel['vote_end_time'] <= time()) {
            $this->error('投票已结束');
        }

        $voteName = 'vote' . $channel['id'];
        $vote = cookie($voteName);

        //判断是否能重复投票
        if ($vote && $channel['vote_repeat'] == '0') {
            $this->error('您已投票');
        }

        //判断投票
        if ($vote && $vote > time()) {
            $this->error('您已投票,请30分钟后再来哦');
        }

        if (db('cms_vote')->where('id', $id)->find()) {
            db('cms_vote')->where('id', $id)->setInc('real_vote');
            cookie($voteName, time() + config('vote_interval'));
            $this->success('投票成功');
        }

        $this->error('投票失败');


    }

    /**
     * 加载语言包
     */
    public function lang()
    {
        header('Content-Type: application/javascript');
        $callback = $this->request->get('callback');
        $controllername = input("controllername");
        $this->loadlang($controllername);
        //强制输出JSON Object
        $result = 'define(' . json_encode(Lang::get(), JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE) . ');';
        return $result;
    }
    
    /**
     * 上传文件
     */
    public function upload()
    {
        $result = check_upload($_FILES['file']['tmp_name']);
        if ($result) {
            // 这里加个拦截提示
            Log::error('前台上传错误:'.var_export($_FILES['file']['tmp_name'],TRUE));
            $this->error(__($result));
        }
        return action('api/common/upload');
    }
}
