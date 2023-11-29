<?php

namespace backend\models;


class EyunAuthBackend extends \common\models\eyun\EyunAuth
{
    const PLATFORM_ID_EYUN = 1;
    const PLATFORM_ID_WBOT = 2;

    const PLATFORM_ID_OPTIONS = [
        self::PLATFORM_ID_EYUN => 'E云',
        self::PLATFORM_ID_WBOT => 'WeBot',
    ];

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['type', 'status', 'created_at', 'updated_at'], 'integer'],
            [['authorization', 'desc'], 'string'],
            [['created_at', 'updated_at'], 'required'],
            [['update_at'], 'safe'],
            [['account'], 'string', 'max' => 32],
            [['password', 'callback_url', 'base_url'], 'string', 'max' => 255],
        ];
    }
}
