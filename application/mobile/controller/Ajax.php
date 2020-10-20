<?php

namespace app\mobile\controller;

use app\common\controller\Frontend;
use think\Lang;

/**
 * Ajax异步请求接口
 * @internal
 */
class Ajax extends Frontend
{
    protected $noNeedLogin = ['lang', 'vote', 'upload'];
    protected $noNeedRight = ['*'];
    protected $layout = '';

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
        return action('api/common/upload');
    }
}
