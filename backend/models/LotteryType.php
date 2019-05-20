<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%lottery_type}}".
 *
 * @property int $id
 * @property int $type 1重庆时时彩2七星彩3排列三4排列五5福彩3D
 * @property int $enable
 * @property int $isDelete
 * @property int $sort
 * @property string $name
 * @property string $codeList 彩票可选号码列表，用半角逗号分隔
 * @property string $title
 * @property string $shortName
 * @property string $info
 * @property string $onGetNoed 请求当前期号时后置事件函数
 * @property int $data_ftime 开奖时间频率(s)
 * @property int $defaultViewGroup 默认显示哪个玩法组
 * @property int $android
 * @property int $num 彩种期数
 * @property string $typeGroupName 彩种分类名称
 */
class LotteryType extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%lottery_type}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'name', 'title', 'info', 'num'], 'required'],
            [['type', 'enable', 'isDelete', 'sort', 'data_ftime', 'defaultViewGroup', 'android', 'num'], 'integer'],
            [['name'], 'string', 'max' => 32],
            [['codeList'], 'string', 'max' => 125],
            [['title', 'onGetNoed'], 'string', 'max' => 64],
            [['shortName'], 'string', 'max' => 8],
            [['info'], 'string', 'max' => 255],
            [['typeGroupName'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => '1重庆时时彩2七星彩3排列三4排列五5福彩3D',
            'enable' => 'Enable',
            'isDelete' => 'Is Delete',
            'sort' => 'Sort',
            'name' => 'Name',
            'codeList' => '彩票可选号码列表，用半角逗号分隔',
            'title' => 'Title',
            'shortName' => 'Short Name',
            'info' => 'Info',
            'onGetNoed' => '请求当前期号时后置事件函数',
            'data_ftime' => '开奖时间频率(s)',
            'defaultViewGroup' => '默认显示哪个玩法组',
            'android' => 'Android',
            'num' => '彩种期数',
            'typeGroupName' => '彩种分类名称',
        ];
    }

    /**
     * @inheritdoc
     * @return LotteryTypeQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new LotteryTypeQuery(get_called_class());
    }
}
