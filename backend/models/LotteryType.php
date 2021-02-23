<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%lottery_type}}".
 *
 * @property int $id
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
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
 * @property int $updated_at 更新时间
 * @property int $created_at 创建时间
 * @property string $update_time 更新时间
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
            [['lottery_type', 'enable', 'isDelete', 'sort', 'data_ftime', 'defaultViewGroup', 'android', 'num', 'updated_at', 'created_at'], 'integer'],
            [['name', 'title', 'info', 'num'], 'required'],
            [['update_time'], 'safe'],
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
            'id' => Yii::t('app', 'ID'),
            'lottery_type' => Yii::t('app', '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc'),
            'enable' => Yii::t('app', 'Enable'),
            'isDelete' => Yii::t('app', 'Is Delete'),
            'sort' => Yii::t('app', 'Sort'),
            'name' => Yii::t('app', 'Name'),
            'codeList' => Yii::t('app', '彩票可选号码列表，用半角逗号分隔'),
            'title' => Yii::t('app', 'Title'),
            'shortName' => Yii::t('app', 'Short Name'),
            'info' => Yii::t('app', 'Info'),
            'onGetNoed' => Yii::t('app', '请求当前期号时后置事件函数'),
            'data_ftime' => Yii::t('app', '开奖时间频率(s)'),
            'defaultViewGroup' => Yii::t('app', '默认显示哪个玩法组'),
            'android' => Yii::t('app', 'Android'),
            'num' => Yii::t('app', '彩种期数'),
            'typeGroupName' => Yii::t('app', '彩种分类名称'),
            'updated_at' => Yii::t('app', '更新时间'),
            'created_at' => Yii::t('app', '创建时间'),
            'update_time' => Yii::t('app', '更新时间'),
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
