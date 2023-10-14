<?php

namespace backend\models\wechat;

use Yii;

/**
 * This is the model class for table "lt_wechat_user".
 *
 * @property int $id
 * @property int $user_id user.id,系统用户id
 * @property string $userName 微信id，唯一
 * @property string $nickName 昵称
 * @property string $aliasName 微信号
 * @property int $status 状态-1禁用1启用
 * @property string $balance 余额
 * @property int $is_credit 是否信用用户0否1是
 * @property string $bigHead 大头像
 * @property string $smallHead 小头像
 * @property string $labelList 标签列表
 * @property string $remark 备注信息
 * @property int $expire_time 到期时间
 * @property int $created_at
 * @property int $updated_at
 * @property string $update_at 更新时间
 */
class WechatUser extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lt_wechat_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'status', 'is_credit', 'expire_time', 'created_at', 'updated_at'], 'integer'],
            [['balance'], 'number'],
            [['created_at', 'updated_at'], 'required'],
            [['update_at'], 'safe'],
            [['userName', 'nickName', 'aliasName', 'labelList', 'remark'], 'string', 'max' => 255],
            [['bigHead', 'smallHead'], 'string', 'max' => 640],
            [['userName'], 'unique'],
            [['user_id', 'userName'], 'unique', 'targetAttribute' => ['user_id', 'userName']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'userName' => 'User Name',
            'nickName' => 'Nick Name',
            'aliasName' => 'Alias Name',
            'status' => 'Status',
            'balance' => 'Balance',
            'is_credit' => 'Is Credit',
            'bigHead' => 'Big Head',
            'smallHead' => 'Small Head',
            'labelList' => 'Label List',
            'remark' => 'Remark',
            'expire_time' => 'Expire Time',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'update_at' => 'Update At',
        ];
    }
}
