<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\WxFriends as WxFriendsModel;

/**
 * WxFriends represents the model behind the search form of `backend\models\WxFriends`.
 */
class WxFriends extends WxFriendsModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'uid', 'Uin', 'status', 'ContactFlag', 'MemberCount', 'HideInputBarFlag', 'Sex', 'VerifyFlag', 'OwnerUin', 'StarFriend', 'AppAccountFlag', 'Statues', 'AttrStatus', 'SnsFlag', 'UniFriend', 'ChatRoomId', 'IsOwner', 'created_at', 'updated_at'], 'integer'],
            [['UserName', 'NickName', 'send_name', 'HeadImgUrl', 'MemberList', 'RemarkName', 'Signature', 'PYInitial', 'PYQuanPin', 'RemarkPYInitial', 'RemarkPYQuanPin', 'Province', 'City', 'Alias', 'DisplayName', 'KeyWord', 'EncryChatRoomId'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = WxFriendsModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'uid' => $this->uid,
            'Uin' => $this->Uin,
            'status' => $this->status,
            'ContactFlag' => $this->ContactFlag,
            'MemberCount' => $this->MemberCount,
            'HideInputBarFlag' => $this->HideInputBarFlag,
            'Sex' => $this->Sex,
            'VerifyFlag' => $this->VerifyFlag,
            'OwnerUin' => $this->OwnerUin,
            'StarFriend' => $this->StarFriend,
            'AppAccountFlag' => $this->AppAccountFlag,
            'Statues' => $this->Statues,
            'AttrStatus' => $this->AttrStatus,
            'SnsFlag' => $this->SnsFlag,
            'UniFriend' => $this->UniFriend,
            'ChatRoomId' => $this->ChatRoomId,
            'IsOwner' => $this->IsOwner,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'UserName', $this->UserName])
            ->andFilterWhere(['like', 'NickName', urlencode($this->NickName)])
            ->andFilterWhere(['like', 'send_name', $this->send_name])
            ->andFilterWhere(['like', 'HeadImgUrl', $this->HeadImgUrl])
            ->andFilterWhere(['like', 'MemberList', $this->MemberList])
            ->andFilterWhere(['like', 'RemarkName', $this->RemarkName])
            ->andFilterWhere(['like', 'Signature', $this->Signature])
            ->andFilterWhere(['like', 'PYInitial', $this->PYInitial])
            ->andFilterWhere(['like', 'PYQuanPin', $this->PYQuanPin])
            ->andFilterWhere(['like', 'RemarkPYInitial', $this->RemarkPYInitial])
            ->andFilterWhere(['like', 'RemarkPYQuanPin', $this->RemarkPYQuanPin])
            ->andFilterWhere(['like', 'Province', $this->Province])
            ->andFilterWhere(['like', 'City', $this->City])
            ->andFilterWhere(['like', 'Alias', $this->Alias])
            ->andFilterWhere(['like', 'DisplayName', $this->DisplayName])
            ->andFilterWhere(['like', 'KeyWord', $this->KeyWord])
            ->andFilterWhere(['like', 'EncryChatRoomId', $this->EncryChatRoomId]);

        return $dataProvider;
    }
}
