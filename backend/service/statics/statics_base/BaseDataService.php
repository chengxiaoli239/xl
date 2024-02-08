<?php
namespace backend\service\statics\statics_base;

use backend\models\Num4Type;
use backend\models\searchs\SscDwsHzNums;
use backend\models\SscDsTypeDatas;
use backend\models\SscDsYl;
use backend\models\SscKjData;
use backend\models\SscKjData3num;
use backend\models\SscStaticVal;
use backend\models\ThreeNum;
use backend\service\BaseNumService;
use backend\service\BaseService;
use backend\service\NumService;
use backend\service\SscDataService;
use backend\service\StaticService;
use common\service\CommonService;
use common\tools\Tool_Common;

class BaseDataService extends BaseService
{
    /**
     * 添加和值区间统计记录
     * @param int $interval
     */
    public static function insertSscDwsHzNums($lottery_type = DEFAULT_LOTTERY_TYPE, int $interval = 20, $qihao = '', $id = ''): bool
    {
        //p([$interval, $qihao, $id],0);
        $zuHes = [ [1,2], [1,3], [1,4], [2,3], [2,4], [3,4] ];
        # 每期记录前多少期的和值统计
        $numsArr = [6,7,8,9,10,11,12];  // [8,9,10,11,12,13];  // 和值
        //$newRecord = SscKjData::find()->select(['qihao','code_str'])->orderBy('id DESC')->asArray()->limit(1)->one();
        $last_id = SscDataService::getKjDataStartId($lottery_type, $interval, $qihao);
        //p($last_id,0);
        foreach ($zuHes as $key => $zuHe) {
            $position = implode(',', $zuHe);
            $field = 'code_'.implode('_',$zuHe);
            foreach ($numsArr as $zhi) {
                $start_id = $last_id;
                $end_id = $last_id + $interval;
                $nums = SscKjData::find()->select('COUNT(id) as nums')->where([$field=>$zhi])->andWhere(['between', 'id', $start_id, $end_id ])->asArray()->one()['nums'];
                //p(['start_id'=>$start_id, 'end_id'=>$end_id, 'nums'=>$nums,$field=>$zhi]);
                $opData = [
                    'hezhi' => $zhi,
                    'nums' => $nums,
                    'positions'=>$position,
                    'periods'=>$interval,
                    'qihao' => $qihao,
                    'updated_at' => time(),
                ];
                $where = ['qihao'=>(string)$qihao,'positions'=>$position,'periods'=>$interval, 'hezhi'=>$zhi ];
                $SscDwsHzNums = SscDwsHzNums::findOne($where);
                if(!$SscDwsHzNums){
                    $SscDwsHzNums = new SscDwsHzNums();
                    $opData['created_at'] = time();
                }
                $SscDwsHzNums->setAttributes($opData);
                $rst = $SscDwsHzNums->save();
                $logArr = ['opData'=>$opData, 'rst'=>$rst,'nums'=>$nums, 'where'=>$where, 'id'=>$id];
                if(!$rst){
                    $logArr['msg'] = $SscDwsHzNums->getErrors();
                    Tool_Common::log('static_SscDwsHzNums','INFO','统计区间某个号码出现的次数', $logArr);
                }
            }
        }
        return $rst;
    }

    /**
     * @desc 每期开奖三字现记录-已完成  写入
     * @param int $lottery_type -彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @param string $qihao
     */
    public static function insertSscKjData3Num(string $qihao = '', int $lottery_type = DEFAULT_LOTTERY_TYPE): bool
    {
        $SscKjData = SscKjData::findOne(['qihao'=>$qihao, 'lottery_type'=>$lottery_type]);
        $data = explode(',',$SscKjData['code_str']);
        array_pop($data);
        $nums = CommonService::get3x($data);

        $opData = [];
        $opData['code_3n'] = implode(',', $nums);
        $opData['updated_at'] = time();
        $opData['updated_time'] = date('Y-m-d H:i:s');
        $SscKjData3Num = SscKjData3num::findOne(['qihao'=>$qihao, 'lottery_type'=>$lottery_type]);
        if(!$SscKjData3Num){
            $SscKjData3Num = new SscKjData3num();
            $opData['created_at'] = time();
        }

        $opData['qihao'] = $qihao;
        $opData['index_id'] = $SscKjData->index_id;
        $opData['code_str'] = $SscKjData['code_str'];
        $opData['date'] = $SscKjData['date'];
        $opData['lottery_type'] = $lottery_type;
        $SscKjData3Num->setAttributes($opData);
        $rst = $SscKjData3Num->save();
        //p([$opData,$SscKjData3Num->attributes,$SscKjData3Num->getErrors()],0);
        if(!$rst){
            $logArr = ['attributes'=>$SscKjData3Num->attributes, 'msg'=>$SscKjData3Num->getErrors()];
            Tool_Common::log('insertSscKjData3Num','INFO','统计号码出现次数', $logArr);
        }

        return $rst;
    }

    /**
     * @desc 插入四定单双组合单双遗漏表
     */
    public static function insert4dDsZHData(){
        $typeArr = StaticService::$typeArr;
        foreach ($typeArr as $key=>$Arr){
            if(in_array($key, [0, 10, 11])) continue;
            $SscDsYl = SscDsYl::findOne(['positions'=>'1,2,3,4', 'zhi'=>implode(',', $Arr)]);
            if(!$SscDsYl){
                $SscDsYl = new SscDsYl();
            }
            $SscDsYl->type = 4;
            $SscDsYl->positions = '1,2,3,4';
            $SscDsYl->zhi = implode(',', $Arr);
            $SscDsYl->update_time = time();
            //p($setData, 0);
            $rst = $SscDsYl->save();
        }

        return ['status'=>$rst, 'msg'=>'处理成功~', 'rst'=>$rst];
    }

    /**
     * @desc 插入号码类型表 - 四字定
     * @return bool
     */
    public static function insertCodeType(){
        set_time_limit(0);

        //for($i = 10000; $i<=19999; $i++){
        for($i = 10000; $i<=13501; $i++){
            //for($i = 13500; $i<=16501; $i++){
            //for($i = 16500; $i<=19999; $i++){
            $code = substr($i, 1,4);
            $codes = $code[0].','.$code[1].','.$code[2].','.$code[3];
            if(!$Num4Type = Num4Type::findOne(['code'=>$codes])){
                $Num4Type = new Num4Type();
            }else{
                //continue;
            }
            $code_strArr = [$code[0], $code[1], $code[2], $code[3]];
            asort($code_strArr);
            $code_str = implode('', $code_strArr);

            $code_type = CommonService::isCodeType4numDs($codes); # 号码类型,四定单双:0保留1四单2四双3两单两双4一单三双5一双三单
            $setData = [
                'code' => $codes, # 号码
                'code_str' => $code_str, # 号码
                'code_1' => $code[0], # 第一个号码
                'code_2' => $code[1], # 第二个号码
                'code_3' => $code[2], # 第三个号码
                'code_4' => $code[3], # 第四个号码
                'type_2' => CommonService::isCodeType2($codes), # 是否双重
                'type_22' => CommonService::isCodeType22($codes), # 是否双双重
                'type_3' => CommonService::isCodeType3($codes), # 是否三重
                'type_4' => CommonService::isCodeType4($codes), # 是否四重
                'type_2b' => CommonService::isCodeType2b($codes), # 是否两兄弟
                'type_22b' => CommonService::isCodeType22b($codes), # 是否两兄弟
                'type_3b' => CommonService::isCodeType3b($codes), # 是否三兄弟
                'type_4b' => CommonService::isCodeType4b($codes), # 是否四兄弟
                'type_4ds' => CommonService::isCodeType4ds($codes), # 是否四单双：0非四单四双1四单2四双
                'type_log' => CommonService::isCodeTypeLog($codes), # 是否对数
                'type_3d' => $code_type == 5 ? 1 : 0, # 是否四单
                'type_3s' => $code_type == 4 ? 1 : 0, # 是否四单
                'type_4d' => $code_type == 1 ? 1 : 0, # 是否四单
                'type_4s' => $code_type == 2 ? 1 : 0, # 是否四双
                'type_3n_2b' => CommonService::isCodeType3n2b($codes), # 三现:双重+兄弟
                'code_type' => 4, # 号码类型:1一字定2二字定3三字定4四字定
                'codes_hz' => array_sum([$code[0],$code[1],$code[2],$code[3]]),
                'updated_at' => time(),
                'created_at' => time(),
            ];
            $Num4Type->setAttributes($setData);

            if(!$rst = $Num4Type->save()){
                p($Num4Type->getFirstErrors());
            }
        }
        return $rst;
    }

    /**
     * @desc 插入二字定
     * @return bool
     */
    public static function insertCodeType2(){
        $rst = true;
        set_time_limit(0);

        //for($i = 10000; $i<=19999; $i++){
        for($i = 100; $i<=199; $i++){
            $code = substr($i, 1,2);
            $codeNums = [$code[0],$code[1]];
            $codesArr = NumService::getCodesTwo($codeNums); # 格式：[['1','2', 'X', 'X'], ['1', 'X', '2', 'X']] ..
            foreach ($codesArr as $codes){
                $code = implode(',', $codes);
                if(!$Num4Type = Num4Type::findOne(['code'=>$code])){
                    $Num4Type = new Num4Type();
                }else{
                    //continue;
                }

                $codesA = NumService::delByValue($codes, 'X');
                $setData = [
                    'code' => $code, # 号码
                    'code_1' => $codes[0], # 第一个号码
                    'code_2' => $codes[1], # 第二个号码
                    'code_3' => $codes[2], # 第三个号码
                    'code_4' => $codes[3], # 第四个号码
                    'type_2' => CommonService::isCodeType_2($code), # 是否双重
                    'type_2b' => CommonService::isCodeType2b($code), # 是否两兄弟
                    'type_log' => CommonService::isCodeTypeLog($code), # 是否对数
                    'code_type' => 2, # 号码类型:1一字定2二字定3三字定4四字定
                    'codes_hz' => array_sum($codesA),
                    'updated_at' => time(),
                    'created_at' => time(),
                ];
                $Num4Type->setAttributes($setData);

                if(!$rst = $Num4Type->save()){
                    p($Num4Type->getFirstErrors());
                }
            }
        }

        return $rst;
    }

    /**
     * @desc 插入二字定 - 五位二定
     * @return bool
     */
    public static function insertCodeType5(){
        $rst = true;
        set_time_limit(0);

        //for($i = 10000; $i<=19999; $i++){
        for($i = 100; $i<=199; $i++){
            $code = substr($i, 1,2);
            $codeNums = [$code[0],$code[1]];
            $codesArr = NumService::getCodesTwo5($codeNums); # 格式：[['1','X', 'X', 'X', '2'], ['X', '1', 'X', 'X', '2'], ['X', 'X', '1', 'X', '2'], ['X', 'X', 'X', '1', '2']] ..
            foreach ($codesArr as $codes){
                $code = implode(',', $codes);
                if(!$Num4Type = Num4Type::findOne(['code'=>$code])){
                    $Num4Type = new Num4Type();
                }else{
                    //continue;
                }

                $codesA = NumService::delByValue($codes, 'X');
                $setData = [
                    'code' => $code, # 号码
                    'code_1' => $codes[0], # 第一个号码
                    'code_2' => $codes[1], # 第二个号码
                    'code_3' => $codes[2], # 第三个号码
                    'code_4' => $codes[3], # 第四个号码
                    'code_5' => $codes[4], # 第四个号码
                    'type_2' => CommonService::isCodeType_2($code), # 是否双重
                    'type_2b' => CommonService::isCodeType2b($code), # 是否两兄弟
                    'type_log' => CommonService::isCodeTypeLog($code), # 是否对数
                    'code_type' => 5, # 号码类型:1一字定2二字定3三字定4四字定5五位二定
                    'codes_hz' => array_sum($codesA),
                    'updated_at' => time(),
                    'created_at' => time(),
                ];
                $Num4Type->setAttributes($setData);

                if(!$rst = $Num4Type->save()){
                    p($Num4Type->getFirstErrors());
                }
            }
        }

        return $rst;
    }

    /**
     * @desc 插入三字定
     * @return bool
     */
    public static function insertCodeType3(){
        $rst = true;
        set_time_limit(0);

        //for($i = 1000; $i<=1350; $i++){
        //for($i = 1350; $i<=1650; $i++){
        for($i = 1651; $i<=1999; $i++){
            $code = substr($i, 1,3);
            $codeNums = [$code[0], $code[1], $code[2]];
            $codesArr = NumService::getCodesTwo($codeNums); # 格式：[['1','2', '1', 'X'], ['1', '1', '2', 'X']] ..
            foreach ($codesArr as $codes){
                $code = implode(',', $codes);
                if(!$Num4Type = Num4Type::findOne(['code'=>$code])){
                    $Num4Type = new Num4Type();
                }else{
                    //continue;
                }

                $codesA = NumService::delByValue($codes, 'X');
                $setData = [
                    'code' => $code, # 号码
                    'code_1' => $codes[0], # 第一个号码
                    'code_2' => $codes[1], # 第二个号码
                    'code_3' => $codes[2], # 第三个号码
                    'code_4' => $codes[3], # 第四个号码
                    'type_2' => CommonService::isCodeType_2($code), # 是否双重
                    'type_3' => CommonService::isCodeType3($code), # 是否三重
                    'type_2b' => CommonService::isCodeType2b($code), # 是否两兄弟
                    'type_3b' => CommonService::isCodeType3b($code), # 是否三兄弟
                    'type_log' => CommonService::isCodeTypeLog($code), # 是否对数
                    'type_3n_2b' => CommonService::isCodeType3n2b($code), # 三现:双重+兄弟
                    'code_type' => 3, # 号码类型:1一字定2二字定3三字定4四字定
                    'codes_hz' => array_sum($codesA),
                    'updated_at' => time(),
                    'created_at' => time(),
                ];
                $Num4Type->setAttributes($setData);

                if(!$rst = $Num4Type->save()){
                    p($Num4Type->getFirstErrors());
                }
            }
        }

        return $rst;
    }

    /**
     * @desc 更新四定号码类型表号码类型
     * @return mixed
     */
    public static function insertStaticVal(){

        $SscStaticVals = SscStaticVal::findAll(['type'=>[4,5]]);
        foreach ($SscStaticVals as $SscStaticVal){
            $code = $SscStaticVal->val;
            $codes = $code[0].','.$code[1].','.$code[2].','.$code[3];
            $ds = CommonService::isCodeType4ds($codes); # 是否四单双：0非四单四双1四单2四双
            $setData = [
                'type_2' => CommonService::isCodeType2($codes), # 是否双重
                'type_22' => CommonService::isCodeType22($codes), # 是否双双重
                'type_3' => CommonService::isCodeType3($codes), # 是否三重
                'type_4' => CommonService::isCodeType4($codes), # 是否四重
                'type_2b' => CommonService::isCodeType2b($codes), # 是否两兄弟
                'type_3b' => CommonService::isCodeType3b($codes), # 是否三兄弟
                'type_4b' => CommonService::isCodeType4b($codes), # 是否四兄弟
                'type_4d' => $ds == 1 ? 1 : 0,
                'type_4s' => $ds == 2 ? 1 : 0,
                'type_log' => CommonService::isCodeTypeLog($codes), # 是否对数
            ];
            $SscStaticVal->setAttributes($setData);
            /*
            if($codes == '1,2,3,4'){
                p($setData,0);
                p(CommonService::isCodeType4b($codes), 0);
                p($SscStaticVal->attributes);
            }
            */
            $rst = $SscStaticVal->save();
        }

        return $rst;
    }

    /**
     * @desc 统计表插入 三字现
     * @param int $type - 类型：1和值2号码类型[例如:双双重、三重]3三字现4四字现
     * @return bool
     */
    public static function insertCode(int $type = 3): bool
    {
        if($type<3) return false;

        $setData = [];
        switch ($type){
            case 3:
                $codes = BaseNumService::getRepeat3Codes($isRepeat = 1);  # 三字现，双重加一码、不含三重
                $codes_3 = BaseNumService::getRepeat3Codes3(); # 三重三字现
                $codes = array_merge($codes, $codes_3);
                break;
            case 4:
                $codes = BaseNumService::getRepeat4Codes($isRepeat = 1); # 四字现，双重加两码、不含三重
                $codes = array_merge($codes, BaseNumService::getRepeat4Codes($isRepeat = 0)); # 四字现不含双重
                $codes = array_merge($codes, BaseNumService::getRepeat4Codes3()); # 四字现三重
                $codes = array_merge($codes, BaseNumService::getRepeat4Codes22()); # 四字现双双重
                break;
            case 5:
                $codes = ThreeNum::find()->asArray()->all();
                $codes = \yii\helpers\ArrayHelper::getColumn($codes, 'code');
                break;
        }
        foreach ($codes as $code){
            $where = ['val'=>$code, 'type'=>$type];
            if(!$SscStaticVal = SscStaticVal::findOne($where)){
                $SscStaticVal = new SscStaticVal();
                $setData = [
                    'created_at' => time(),
                    'updated_at' => time(),
                ];
            }
            $setData = array_merge($setData,[
                'type' => $type,
                'val' => $code,
                'name' => $code,
                'status' => 1,
            ]);
            if($type == 3){
                # 1、三字
                $code_str = $code[0].','.$code[1].','.$code[2];
                $type_2 = CommonService::isCodeType2_3z($code_str);
                $type_3 = CommonService::isCodeType3_3z($code_str);
                $setData = array_merge($setData, [
                    'type_2' => $type_2,
                    'type_3' => $type_3,
                    'codes_hz' => $code[0] + $code[1] + $code[2],
                ]);

            }elseif ( $type == 4 ){
                # 四字
                $code_str = $code[0].','.$code[1].','.$code[2].','.$code[3];
                $ds = CommonService::isCodeType4ds($code_str); # 是否四单双：0非四单四双1四单2四双
                $setData = array_merge($setData, [
                    'codes_hz' => $code[0] + $code[1] + $code[2] + $code[3],
                    'type_2' => CommonService::isCodeType2($code_str),
                    'type_3' => CommonService::isCodeType3($code_str),
                    'type_4' => CommonService::isCodeType4($code_str),
                    'type_2b' => CommonService::isCodeType2b($code_str),
                    'type_3b' => CommonService::isCodeType3b($code_str),
                    'type_4b' => CommonService::isCodeType4b($code_str),
                    'type_22' => CommonService::isCodeType22($code_str),
                    'type_4d' => $ds == 1 ? 1 : 0,
                    'type_4s' => $ds == 2 ? 1 : 0,
                    'type_4ds' => in_array($ds, [1,2]) ? 1 : 0,
                    'type_log' => CommonService::isCodeTypeLog($code_str),
                ]);
            }elseif ( $type == 5 ){
                # 带双四字现三码，如：123，包含1123、1223、1233
                $code_str = $code[0].','.$code[1].','.$code[2].','.$code[0];
                $ds = CommonService::isCodeType4ds($code_str); # 是否四单双：0非四单四双1四单2四双
                $setData = array_merge($setData, [
                    'codes_hz' => $code[0] + $code[1] + $code[2] + $code[3],
                    'static_nums' => 6000,
                    'type_2' => 1,
                    'type_2b' => CommonService::isCodeType2b($code_str),
                    'type_3b' => CommonService::isCodeType3b($code_str),
                    'type_4d' => $ds == 1 ? 1 : 0,
                    'type_4s' => $ds == 2 ? 1 : 0,
                    'type_4ds' => in_array($ds, [1,2]) ? 1 : 0,
                    'type_log' => CommonService::isCodeTypeLog($code_str),
                ]);

            }

            $SscStaticVal->setAttributes($setData);
            $SscStaticVal->save();

        }

        return true;
    }

    /**
     * @desc 插入单双号码类型数据 - 初始化
     * @param int $lottery_type
     * @return array
     */
    public static function insertDsTypeDatas($lottery_type = DEFAULT_LOTTERY_TYPE){
        $rst = ['status'=>200, 'msg'=>'操作成功'];

        $where = ['status'=>1];
        $datas = SscDsTypeDatas::find()->where($where)->asArray()->orderBy(['sort'=>SORT_DESC])->all();
        foreach ($datas as $data){
            $where = ['positions'=>$data['positions'], 'zhi'=>$data['vals'], 'type'=>$data['code_type'], 'lottery_type'=>$lottery_type];
            $setDatas = [];
            if(!$SscDsYl = SscDsYl::findOne($where)){
                $SscDsYl = new SscDsYl();
                $setDatas = array_merge($setDatas, [
                    'positions'=>$data['positions'],
                    'zhi'=>$data['vals'],
                    'type'=>$data['code_type'],
                    'lottery_type'=>$lottery_type,
                    'created_at'=>time(),
                ]);
            }
            $setDatas = array_merge($setDatas, [
                'updated_at'=>time(),
                'static_nums' => $data['static_nums'],
            ]);

            $SscDsYl->setAttributes($setDatas);
            if(!$SscDsYl->save()){
                $msg = $SscDsYl->getErrors();
                return ['status'=>300, 'msg'=>$msg];
            }
        }

        return $rst;
    }
}
