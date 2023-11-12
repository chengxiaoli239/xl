<?php

namespace common\models\open;

use Yii;

/**
 * This is the model class for table "{{%outsite_request_log}}".
 *
 * @property int $id ID
 * @property int $send_time 请求时间
 * @property string $api_method 接口方法名称
 * @property int $response_micro_time 响应时长，毫秒
 * @property string $param 业务参数
 * @property string $sign_data 签名数据
 * @property string $response_data 响应数据
 * @property string $request_method 请求方式
 * @property string $full_url 完整URL,包含查询参数
 * @property int $status 状态 1-正常 2-异常
 * @property string $remark 备注
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class OutSiteRequestLog extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%out_site_request_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['send_time', 'response_micro_time', 'status'], 'integer'],
            [['param', 'sign_data', 'response_data'], 'required'],
            [['param', 'sign_data', 'response_data', 'remark'], 'string'],
            [['create_time', 'update_time'], 'safe'],
            [['api_method'], 'string', 'max' => 128],
            [['request_method'], 'string', 'max' => 8],
            [['full_url'], 'string', 'max' => 256],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'send_time' => Yii::t('app', '请求时间'),
            'api_method' => Yii::t('app', '接口方法名称'),
            'response_micro_time' => Yii::t('app', '响应时长，毫秒'),
            'param' => Yii::t('app', '业务参数'),
            'sign_data' => Yii::t('app', '签名数据'),
            'response_data' => Yii::t('app', '响应数据'),
            'request_method' => Yii::t('app', '请求方式'),
            'full_url' => Yii::t('app', '完整URL,包含查询参数'),
            'status' => Yii::t('app', '状态 1-正常 2-异常'),
            'remark' => Yii::t('app', '备注'),
            'create_time' => Yii::t('app', '创建时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }
}
