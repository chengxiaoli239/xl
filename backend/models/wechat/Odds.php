<?php

namespace backend\models\wechat;

use Yii;

/**
 * This is the model class for table "lt_odds".
 *
 * @property int $id
 * @property int $user_id 用户id
 * @property int $play_method_id 玩法id
 * @property string $name 玩法名称
 * @property string $money 本金
 * @property string $bouns 奖金
 * @property string $odds 赔率:奖金除于本金%
 * @property int $status 状态-1禁用1启用
 * @property int $created_at
 * @property int $updated_at
 * @property string $update_at 更新时间
 */
class Odds extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lt_odds';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'play_method_id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['odds'], 'number'],
            [['created_at', 'updated_at'], 'required'],
            [['update_at'], 'safe'],
            [['name'], 'string', 'max' => 255],
            [['money', 'bouns'], 'string', 'max' => 11],
            [['user_id', 'play_method_id'], 'unique', 'targetAttribute' => ['user_id', 'play_method_id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => '用户ID',
            'play_method_id' => '玩法ID',
            'name' => '名称',
            'money' => '金额[元]',
            'bouns' => '奖金[元]',
            'odds' => '赔率',
            'status' => '状态',
            'created_at' => '创建时间',
            'updated_at' => '创建时间',
            'update_at' => '更新时间',
        ];
    }
}
