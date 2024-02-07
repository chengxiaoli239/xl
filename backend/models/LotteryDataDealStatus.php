<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%lottery_data_deal_status}}".
 *
 * @property int $id
 * @property int $lottery_type 彩票类型
 * @property int $status 状态:0已关闭1已开启
 * @property string $status_desc 状态结果描述
 * @property int $static4dPerDateProfits_status A每天四定利润统计:0已关闭1已开启
 * @property string $static4dPerDateProfits_status_desc A每天四定利润统计结果描述
 * @property int $updateDs_status B单双处理状态:0已关闭1已开启
 * @property string $updateDs_status_desc B单双处理结果描述
 * @property int $updateDsYL_status C单双遗漏处理状态:0已关闭1已开启
 * @property string $updateDsYL_status_desc C单双遗漏处理结果描述
 * @property int $update3NumYL_status D开奖三字现处理状态:0已关闭1已开启
 * @property string $update3NumYL_status_desc D开奖三字现处理结果描述
 * @property int $updateSdHzYL_status E和值遗漏状态:0已关闭1已开启
 * @property string $updateSdHzYL_status_desc E和值遗漏处理结果描述
 * @property int $opProfitsPlans_status F投注计划处理状态:0已关闭1已开启
 * @property string $opProfitsPlans_status_desc 投注计划处理结果描述
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class LotteryDataDealStatus extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%lottery_data_deal_status}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lottery_type', 'status', 'static4dPerDateProfits_status', 'updateDs_status', 'updateDsYL_status', 'update3NumYL_status', 'updateSdHzYL_status', 'opProfitsPlans_status', 'created_at', 'updated_at'], 'integer'],
            [['created_at', 'updated_at'], 'required'],
            [['update_time'], 'safe'],
            [['status_desc', 'static4dPerDateProfits_status_desc', 'updateDs_status_desc', 'updateDsYL_status_desc', 'update3NumYL_status_desc', 'updateSdHzYL_status_desc', 'opProfitsPlans_status_desc'], 'string', 'max' => 240],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'lottery_type' => Yii::t('app', '彩票类型'),
            'status' => Yii::t('app', '状态:0待处理1已处理'),
            'status_desc' => Yii::t('app', '状态结果描述'),
            'static4dPerDateProfits_status' => Yii::t('app', 'A每天四定利润统计:0已关闭1已开启'),
            'static4dPerDateProfits_status_desc' => Yii::t('app', 'A每天四定利润统计结果描述'),
            'updateDs_status' => Yii::t('app', 'B单双处理状态:0已关闭1已开启'),
            'updateDs_status_desc' => Yii::t('app', 'B单双处理结果描述'),
            'updateDsYL_status' => Yii::t('app', 'C单双遗漏处理状态:0已关闭1已开启'),
            'updateDsYL_status_desc' => Yii::t('app', 'C单双遗漏处理结果描述'),
            'update3NumYL_status' => Yii::t('app', 'D开奖三字现处理状态:0已关闭1已开启'),
            'update3NumYL_status_desc' => Yii::t('app', 'D开奖三字现处理结果描述'),
            'updateSdHzYL_status' => Yii::t('app', 'E和值遗漏状态:0已关闭1已开启'),
            'updateSdHzYL_status_desc' => Yii::t('app', 'E和值遗漏处理结果描述'),
            'opProfitsPlans_status' => Yii::t('app', 'F投注计划处理状态:0已关闭1已开启'),
            'opProfitsPlans_status_desc' => Yii::t('app', '投注计划处理结果描述'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return LotteryDataDealStatusQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new LotteryDataDealStatusQuery(get_called_class());
    }
}
