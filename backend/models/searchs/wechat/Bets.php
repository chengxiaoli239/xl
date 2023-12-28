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
    public $wechatUserName;  // 添加这一行
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'wechat_user_id', 'order_id', 'play_method', 'count', 'status', 'push_status', 'cancel_status', 'is_need_confirm', 'reply_type', 'has_reply', 'is_simulate', 'lottery_type', 'is_profits_record', 'created_at', 'updated_at'], 'integer'],
            [['codes', 'qihao', 'kj_codes', 'push_desc', 'new_msg_id', 'reply_content', 'lottery_name', 'bet_desc', 'api_code_datas', 'update_at'], 'safe'],
            [['bet_money', 'bonus', 'single', 'ratio', 'profits'], 'number'],
            [['wechatUserName'], 'string'],  // 适当地添加其他规则
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
        #    $query->from(['wechat_user' => 'lt_wechat_user']);  // 添加这一行，使用别名
        #}]);
        // add conditions that should always apply here

        $query = BetsModel::find()->alias('b');
        $query->joinWith(['wechatUser']); // 这里的 'wechatUser' 是你在 BetsModel 中定义的关联关系

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
        // 在 grid filtering conditions 中使用关联表的字段
        $query->andFilterWhere(['like', 'lt_wechat_user.userName', $this->wechatUserName]);
        $query->addSelect(['b.*', 'lt_wechat_user.userName']);

        return $dataProvider;
    }
}
