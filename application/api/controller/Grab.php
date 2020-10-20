<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use think\Exception;

/**
 * 抓取接口
 */
class Grab extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 抓取数据处理
     *
     */
    public function getGrab()
    {
        if ($this->request->isPost()) {
            //获取数据
            $param = $this->request->param(false);
            $params = $param['row'];

            //开启事务
            Db::startTrans();
            try {
                /* 添加到内容表 */
                $archicesData = $this->getArchicesData($params);//获取内容表组装数据
                $archicesAddID = db('cms_archives')->insertGetId($archicesData);//获取添加成功的id
                if ($archicesAddID == 0 || !$archicesAddID) {
                    Db::rollback();//回滚事务
                    $this->error('添加内容表失败:' . $params['title']);
                }

                /* 添加标签到标签表 */
                $tagsExistedData = $this->getTagsExistedData($params['tags']);//获取已存在的标签数据
                $tagsArray = explode(',', $params['tags']);//标签字符串转为数组
                //(更新/添加)标签信息
                foreach ($tagsArray as $key => $value) {
                    if ($tagsExistedData && isset($tagsExistedData[$value])) {
                        //标签已存在,更新标签信息
                        $tagUpdate = [
                            'archives' => $tagsExistedData[$value]['archives'] . ',' . $archicesAddID,
                            'nums' => (int)$tagsExistedData[$value]['nums'] + 1
                        ];
                        db('cms_tags')->where('id', $tagsExistedData[$value]['id'])->update($tagUpdate);
                    } else {
                        //标签不存在,新增标签信息
                        $tagAdd = [
                            'name' => $value,
                            'archives' => $archicesAddID,
                            'nums' => 1,
                            'type' => 133,
                            'status' => 1
                        ];
                        db('cms_tags')->insert($tagAdd);
                    }
                }

                /* 添加额外内容到副表cms_encyclopedias */
                $encyclopediasData = $this->getEncyclopediasData($archicesAddID, $params);//获取副表组装数据
                $encyclopediasAdd = db('cms_encyclopedias')->insert($encyclopediasData);//更新副表操作
                if ($encyclopediasAdd == 0) {
                    Db::rollback();//回滚事务
                    $this->error('添加内容副表失败:' . $params['title']);
                }

                /* 添加化工字典关键字 */
                $result = $this->getKeywordExistedData($params['title']);//获取已存在的关键字
                if ($result['chineseArray']) {
                    $chineseArray = $result['chineseArray'];
                    if ($result['keywordExistedData']) {
                        //存在关键字,更新关键字数据
                        $keywordExistedData = $result['keywordExistedData'];
                        foreach ($result['chineseArray'] as $key => $value) {
                            if (isset($keywordExistedData[$value])) {
                                $updateData = [
                                    'id' => $keywordExistedData[$value]['id'],
                                    'encyclopedias_id' => $keywordExistedData[$value]['encyclopedias_id'] == '' ? $archicesAddID : $keywordExistedData[$value]['encyclopedias_id'] . ',' . $archicesAddID,
                                ];
                                db('cms_encyclopedias_keyword')->update($updateData);
                                unset($chineseArray[$key]);
                            }
                        }
                    }
                    $addData = [];
                    foreach ($chineseArray as $item) {
                        $addData[] = [
                            'name' => $item,
                            'encyclopedias_id' => $archicesAddID,
                            'createtime' => time(),
                            'updatetime' => time(),
                        ];
                    }
                    db('cms_encyclopedias_keyword')->insertAll($addData);
                }
                Db::commit();
                $this->success('请求成功');
            } catch (Exception $e) {
                Db::rollback();//回滚事务
                $this->error('添加失败:' . $e->getMessage());
            }
        }
    }

    /**
     * @comment 组装新增内容表数据
     * @param $params array
     * @return array
     */
    private function getArchicesData($params)
    {
        $archicesData = [
            'channel_id' => '133',
            'model_id' => '24',
            'title' => $params['title'],
            'image' => $params['image'],
            'keywords' => $params['title'],
            'tags' => $params['tags'],
            'createtime' => time(),
            'updatetime' => time(),
            'publishtime' => time(),
        ];
        return $archicesData;
    }

    /**
     * @comment 获取已存在的标签
     * @param $tags
     * @return array
     */
    private function getTagsExistedData($tags)
    {
        $tagsExistedData = db('cms_tags')->where([
            'name' => ['in', $tags],
            'type' => '133'
        ])->column('name,id,archives,nums');
        return $tagsExistedData;
    }

    /**
     * @comment 组装新增副表数据
     * @param $archicesID int 新增的内容表ID
     * @param $params array
     * @return array
     */
    private function getEncyclopediasData($archicesID, $params)
    {
        $encyclopediasData = [
            'id' => $archicesID,
            'name' => $params['name'],//中文名
            'alias_name' => htmlspecialchars($params['alias_name']),//中文别名
            'enname' => $params['enname'],//英文名
            'alias_enname' => htmlspecialchars($params['alias_enname']),//英文别名
            'common_name' => $params['common_name'],//常用名
            'cas_code' => $params['cas_code'],//CAS号
            'density' => $params['density'],//密度
            'boiling_point' => $params['boiling_point'],//沸点
            'melting_point' => $params['melting_point'],//熔点
            'chemical_formula' => htmlspecialchars($params['chemical_formula']),//化学式
            'chemical_formula_num' => str_replace("</SUB>","",str_replace("<SUB>", "", $params['chemical_formula'])),//化学式
            'structural_formula' => htmlspecialchars($params['structural_formula']),//结构式
            'flash_point' => $params['flash_point'],//闪点
            'molecular_weight' => $params['molecular_weight'],//分子量
            'precise_quality' => $params['precise_quality'],//精确质量
            'psa' => $params['psa'],//PSA
            'exterior' => $params['exterior'],//外观性状
            'stockpile' => htmlspecialchars($params['stockpile']),//储存条件
            'stability' => $params['stability'],//稳定性
            'water_solubility' => $params['water_solubility'],//水溶解性
            'shape' => $params['shape'],//形态
            'hlb' => $params['hlb'],//HLB值
            'viscosity' => $params['viscosity'],//粘度
            'ph' => $params['ph'],//PH值
            'role' => htmlspecialchars($params['role']),//作用/用途
            'preparation' => htmlspecialchars($params['preparation']),//制备方法
            'safety_reminder' => htmlspecialchars($params['safety_reminder']),//安全提醒
            'msds' => htmlspecialchars($params['msds']),//硫酸钾MSDS
            'material' => htmlspecialchars($params['material']),//原料简介
            'application' => htmlspecialchars($params['application']),//应用范围
            'toxicity_ecology' => htmlspecialchars($params['toxicity_ecology']),//毒性和生态
            'security_Information' => htmlspecialchars($params['security_Information']),//安全信息
            'is_dangerous' => htmlspecialchars($params['is_dangerous']),//是否危险品
            'dangerous' => htmlspecialchars($params['dangerous']),//危险性
            'hazard_code' => htmlspecialchars($params['hazard_code']),//危险类别码
            'danger_levels' => htmlspecialchars($params['danger_levels']),//危险等级
            'trigger_way' => htmlspecialchars($params['trigger_way']),//触发危险方式
            'manual' => htmlspecialchars($params['manual']),//安全技术说明书
            'impact' => htmlspecialchars($params['impact']),//环境影响程度
            'packaging' => htmlspecialchars($params['packaging']),//包装与贮运
        ];
        return $encyclopediasData;
    }

    /**
     * @comment 获取已存在的关键字
     * @param $title string 标题名称
     * @return array
     */
    private function getKeywordExistedData($title)
    {
        //取出标题中的中文作为化工字典关键字
        $regularC = '/[\x{4e00}-\x{9fa5}]/u';//匹配中文正则
        preg_match_all($regularC, $title, $chinese);
        $chineseArray = $chinese[0];
        $chineseString = implode($chineseArray, ',');
        $keywordExistedData = db('cms_encyclopedias_keyword')->where('name', 'in', $chineseString)->column('name,id,encyclopedias_id');
        return ['keywordExistedData' => $keywordExistedData, 'chineseArray' => $chineseArray];
    }

}
