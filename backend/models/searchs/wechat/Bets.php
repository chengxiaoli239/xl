<?php

namespace backend\models\searchs\wechat;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\wechat\Bets as BetsModel;

/**
 * Bets represents the model behind the search form of `backend\models\wechat\Bets`.
 */
class Bets extends BetsModel
{
    public $username;  // 代理账号
    public $wechatUserName;  // 微信id
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'wechat_user_id', 'order_id', 'play_method', 'count', 'status', 'push_status', 'cancel_status', 'is_need_confirm', 'reply_type', 'has_reply', 'is_simulate', 'lottery_type', 'is_profits_record', 'created_at', 'updated_at'], 'integer'],
            [['codes', 'qihao', 'kj_codes', 'push_desc', 'new_msg_id', 'reply_content', 'lottery_name', 'bet_desc', 'api_code_datas', 'update_at'], 'safe'],
            [['bet_money', 'bonus', 'single', 'ratio', 'profits'], 'number'],
            [['wechatUserName', 'username'], 'string'],  // 微信好友微信账号
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
        #$query = BetsModel::find()->alias('b')->joinWith(['wechatUser' => function($query) {
        #    $query->from(['wechat_user' => 'lt_wechat_user']);  // �����һ�У�ʹ�ñ���
        #}]);
        // add conditions that should always apply here

        $query = BetsModel::find()->alias('b');
        $query->joinWith(['wechatUser']); // 'wechatUser'
        $query->joinWith(['proxy']); // 'wechatUser'

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
            'b.id' => $this->id,
            'b.user_id' => $this->user_id,
            'b.wechat_user_id' => $this->wechat_user_id,
            'b.order_id' => $this->order_id,
            'b.play_method' => $this->play_method,
            'b.bet_money' => $this->bet_money,
            'b.bonus' => $this->bonus,
            'b.single' => $this->single,
            'b.count' => $this->count,
            'b.ratio' => $this->ratio,
            'b.profits' => $this->profits,
            'b.status' => $this->status,
            'b.push_status' => $this->push_status,
            'b.cancel_status' => $this->cancel_status,
            'b.is_need_confirm' => $this->is_need_confirm,
            'b.reply_type' => $this->reply_type,
            'b.has_reply' => $this->has_reply,
            'b.is_simulate' => $this->is_simulate,
            'b.lottery_type' => $this->lottery_type,
            'b.is_profits_record' => $this->is_profits_record,
            'b.created_at' => $this->created_at,
            'b.updated_at' => $this->updated_at,
            'b.update_at' => $this->update_at,
            'lt_admin.username' => trim($this->username),
        ]);

        $query->andFilterWhere(['like', 'b.codes', $this->codes])
            ->andFilterWhere(['like', 'b.qihao', $this->qihao])
            ->andFilterWhere(['like', 'b.kj_codes', $this->kj_codes])
            ->andFilterWhere(['like', 'b.push_desc', $this->push_desc])
            ->andFilterWhere(['like', 'b.new_msg_id', $this->new_msg_id])
            ->andFilterWhere(['like', 'b.reply_content', $this->reply_content])
            ->andFilterWhere(['like', 'b.lottery_name', $this->lottery_name])
            ->andFilterWhere(['like', 'b.bet_desc', $this->bet_desc])
            ->andFilterWhere(['like', 'b.api_code_datas', $this->api_code_datas]);
        // �� grid filtering conditions ��ʹ�ù�������ֶ�
        $query->andFilterWhere(['like', 'lt_wechat_user.userName', $this->wechatUserName]);
        $query->addSelect(['b.*', 'lt_wechat_user.userName', 'lt_admin.username']);

        return $dataProvider;
    }
}
