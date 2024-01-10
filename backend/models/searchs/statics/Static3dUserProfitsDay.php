<?php

namespace backend\models\searchs\statics;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\statics\Static3dUserProfitsDay as Static3dUserProfitsDayModel;

/**
 * Static3dUserProfitsDay represents the model behind the search form of `backend\models\statics\Static3dUserProfitsDay`.
 */
class Static3dUserProfitsDay extends Static3dUserProfitsDayModel
{
    public $username;  // 代理账号
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'wechat_user_id', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['date', 'wechat_user_name', 'update_time'], 'safe'],
            [['bet_money', 'bonus', 'profits'], 'number'],
            [['username'], 'string'],  // 代理系统账号
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
        $query = Static3dUserProfitsDayModel::find()->alias('a');
        $query->joinWith('proxy');

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder'=>['id'=>SORT_DESC]],
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
            'a.date' => $this->date,
            'a.user_id' => $this->user_id,
            'a.wechat_user_id' => $this->wechat_user_id,
            'a.bet_money' => $this->bet_money,
            'a.bonus' => $this->bonus,
            'a.profits' => $this->profits,
            'a.lottery_type' => $this->lottery_type,
            'a.created_at' => $this->created_at,
            'a.updated_at' => $this->updated_at,
            'a.update_time' => $this->update_time,
            'lt_admin.username' => trim($this->username),
        ]);

        $query->andFilterWhere(['like', 'a.wechat_user_name', $this->wechat_user_name]);
        //$query->andFilterWhere(['like', 'lt_admin.username', $this->username]);
        $query->addSelect(['a.*', 'lt_admin.username']);
        //$sql = $query->createCommand()->getRawSql(); p($sql);

        return $dataProvider;
    }
}
