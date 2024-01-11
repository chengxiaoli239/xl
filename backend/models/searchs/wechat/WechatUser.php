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
    public $username;  // 代理账号
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'status', 'is_tuo', 'is_chi', 'is_private', 'is_cha', 'is_bind', 'is_need_confirm', 'reply_type', 'is_credit', 'expire_time', 'created_at', 'updated_at'], 'integer'],
            [['robot_wechat', 'userName', 'nickName', 'aliasName', 'bet_url', 'token', 'bigHead', 'smallHead', 'labelList', 'remark', 'update_at'], 'safe'],
            [['balance', 'all_bet_money', 'today_profits_loss', 'all_profits_loss'], 'number'],
            [['username'], 'string'],  // 微信好友微信账号
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
        $query = WechatUserModel::find()->alias('a');
        $query->joinWith(['proxy']); // 'wechatUser'

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
            'a.id' => $this->id,
            'a.user_id' => $this->user_id,
            'a.status' => $this->status,
            'a.balance' => $this->balance,
            'a.is_tuo' => $this->is_tuo,
            'a.is_chi' => $this->is_chi,
            'a.is_private' => $this->is_private,
            'a.is_cha' => $this->is_cha,
            'a.is_bind' => $this->is_bind,
            'a.is_need_confirm' => $this->is_need_confirm,
            'a.reply_type' => $this->reply_type,
            'a.all_bet_money' => $this->all_bet_money,
            'a.today_profits_loss' => $this->today_profits_loss,
            'a.all_profits_loss' => $this->all_profits_loss,
            'a.is_credit' => $this->is_credit,
            'a.expire_time' => $this->expire_time,
            'a.created_at' => $this->created_at,
            'a.updated_at' => $this->updated_at,
            'a.update_at' => $this->update_at,
            'lt_admin.username' => trim($this->username),
        ]);

        $query->andFilterWhere(['like', 'a.robot_wechat', $this->robot_wechat])
            ->andFilterWhere(['like', 'a.userName', $this->userName])
            ->andFilterWhere(['like', 'a.nickName', $this->nickName])
            ->andFilterWhere(['like', 'a.aliasName', $this->aliasName])
            ->andFilterWhere(['like', 'a.bet_url', $this->bet_url])
            ->andFilterWhere(['like', 'a.token', $this->token])
            ->andFilterWhere(['like', 'a.bigHead', $this->bigHead])
            ->andFilterWhere(['like', 'a.smallHead', $this->smallHead])
            ->andFilterWhere(['like', 'a.labelList', $this->labelList])
            ->andFilterWhere(['like', 'a.remark', $this->remark]);
        $query->addSelect(['a.*', 'lt_admin.username']);

        return $dataProvider;
    }
}
