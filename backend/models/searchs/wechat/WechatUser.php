<?php

namespace backend\models\searchs\wechat;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\wechat\WechatUser as WechatUserModel;

/**
 * WechatUser represents the model behind the search form of `backend\models\wechat\WechatUser`.
 */
class WechatUser extends WechatUserModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'status', 'is_tuo', 'is_chi', 'is_private', 'is_cha', 'is_bind', 'is_need_confirm', 'reply_type', 'is_credit', 'expire_time', 'created_at', 'updated_at'], 'integer'],
            [['userName', 'nickName', 'aliasName', 'bet_url', 'token', 'bigHead', 'smallHead', 'labelList', 'remark', 'update_at'], 'safe'],
            [['balance', 'all_bet_money', 'today_profits_loss', 'all_profits_loss'], 'number'],
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
        $query = WechatUserModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['id'=>SORT_DESC]],
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
            'user_id' => $this->user_id,
            'status' => $this->status,
            'balance' => $this->balance,
            'is_tuo' => $this->is_tuo,
            'is_chi' => $this->is_chi,
            'is_private' => $this->is_private,
            'is_cha' => $this->is_cha,
            'is_bind' => $this->is_bind,
            'is_need_confirm' => $this->is_need_confirm,
            'reply_type' => $this->reply_type,
            'all_bet_money' => $this->all_bet_money,
            'today_profits_loss' => $this->today_profits_loss,
            'all_profits_loss' => $this->all_profits_loss,
            'is_credit' => $this->is_credit,
            'expire_time' => $this->expire_time,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_at' => $this->update_at,
        ]);

        $query->andFilterWhere(['like', 'userName', $this->userName])
            ->andFilterWhere(['like', 'nickName', $this->nickName])
            ->andFilterWhere(['like', 'aliasName', $this->aliasName])
            ->andFilterWhere(['like', 'bet_url', $this->bet_url])
            ->andFilterWhere(['like', 'token', $this->token])
            ->andFilterWhere(['like', 'bigHead', $this->bigHead])
            ->andFilterWhere(['like', 'smallHead', $this->smallHead])
            ->andFilterWhere(['like', 'labelList', $this->labelList])
            ->andFilterWhere(['like', 'remark', $this->remark]);

        return $dataProvider;
    }
}
