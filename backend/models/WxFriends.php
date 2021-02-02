<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%wx_friends}}".
 *
 * @property int $id
 * @property int $uid 用户id
 * @property int $Uin
 * @property string $UserName
 * @property string $NickName 昵称
 * @property int $status 是否发送：0否1是
 * @property string $send_name 发送微信名称
 * @property string $HeadImgUrl 头像
 * @property int $ContactFlag
 * @property int $MemberCount
 * @property string $MemberList
 * @property string $RemarkName
 * @property int $HideInputBarFlag
 * @property int $Sex 性别
 * @property string $Signature 签名
 * @property int $VerifyFlag
 * @property int $OwnerUin
 * @property string $PYInitial
 * @property string $PYQuanPin 全拼
 * @property string $RemarkPYInitial
 * @property string $RemarkPYQuanPin
 * @property int $StarFriend
 * @property int $AppAccountFlag
 * @property int $Statues
 * @property string $AttrStatus
 * @property string $Province 省
 * @property string $City 市县
 * @property string $Alias 别名
 * @property int $SnsFlag
 * @property int $UniFriend
 * @property string $DisplayName
 * @property int $ChatRoomId
 * @property string $KeyWord
 * @property string $EncryChatRoomId
 * @property int $IsOwner
 * @property int $created_at 创建时间
 * @property int $updated_at
 */
class WxFriends extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wx_friends}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'Uin', 'status', 'ContactFlag', 'MemberCount', 'HideInputBarFlag', 'Sex', 'VerifyFlag', 'OwnerUin', 'StarFriend', 'AppAccountFlag', 'Statues', 'SnsFlag', 'UniFriend', 'ChatRoomId', 'IsOwner', 'created_at', 'updated_at'], 'integer'],
            [['MemberList'], 'string'],
            [['UserName', 'NickName', 'HeadImgUrl', 'PYInitial', 'PYQuanPin', 'KeyWord', 'EncryChatRoomId'], 'string', 'max' => 255],
            [['send_name', 'RemarkName', 'AttrStatus', 'Province', 'City', 'Alias', 'DisplayName'], 'string', 'max' => 32],
            [['Signature'], 'string', 'max' => 120],
            [['RemarkPYInitial', 'RemarkPYQuanPin'], 'string', 'max' => 64],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'uid' => Yii::t('app', '用户id'),
            'Uin' => Yii::t('app', 'Uin'),
            'UserName' => Yii::t('app', 'User Name'),
            'NickName' => Yii::t('app', '昵称'),
            'status' => Yii::t('app', '是否发送：0否1是'),
            'send_name' => Yii::t('app', '发送微信名称'),
            'HeadImgUrl' => Yii::t('app', '头像'),
            'ContactFlag' => Yii::t('app', 'Contact Flag'),
            'MemberCount' => Yii::t('app', 'Member Count'),
            'MemberList' => Yii::t('app', 'Member List'),
            'RemarkName' => Yii::t('app', 'Remark Name'),
            'HideInputBarFlag' => Yii::t('app', 'Hide Input Bar Flag'),
            'Sex' => Yii::t('app', '性别'),
            'Signature' => Yii::t('app', '签名'),
            'VerifyFlag' => Yii::t('app', 'Verify Flag'),
            'OwnerUin' => Yii::t('app', 'Owner Uin'),
            'PYInitial' => Yii::t('app', 'Pyinitial'),
            'PYQuanPin' => Yii::t('app', '全拼'),
            'RemarkPYInitial' => Yii::t('app', 'Remark Pyinitial'),
            'RemarkPYQuanPin' => Yii::t('app', 'Remark Pyquan Pin'),
            'StarFriend' => Yii::t('app', 'Star Friend'),
            'AppAccountFlag' => Yii::t('app', 'App Account Flag'),
            'Statues' => Yii::t('app', 'Statues'),
            'AttrStatus' => Yii::t('app', 'Attr Status'),
            'Province' => Yii::t('app', '省'),
            'City' => Yii::t('app', '市县'),
            'Alias' => Yii::t('app', '别名'),
            'SnsFlag' => Yii::t('app', 'Sns Flag'),
            'UniFriend' => Yii::t('app', 'Uni Friend'),
            'DisplayName' => Yii::t('app', 'Display Name'),
            'ChatRoomId' => Yii::t('app', 'Chat Room ID'),
            'KeyWord' => Yii::t('app', 'Key Word'),
            'EncryChatRoomId' => Yii::t('app', 'Encry Chat Room ID'),
            'IsOwner' => Yii::t('app', 'Is Owner'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @inheritdoc
     * @return WxFriendsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new WxFriendsQuery(get_called_class());
    }
}
