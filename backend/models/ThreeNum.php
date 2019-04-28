<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%three_num}}".
 *
 * @property string $id
 * @property string $code 号码
 */
class ThreeNum extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%three_num}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code'], 'string', 'max' => 8],
            [['code'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => '号码',
        ];
    }
}
