<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\BtCrontabs as BtCrontabsModel;

/**
 * BtCrontabs represents the model behind the search form of `backend\models\BtCrontabs`.
 */
class BtCrontabs extends BtCrontabsModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'uid', 'p_id', 'status', 'cron_type', 'created_at', 'updated_at'], 'integer'],
            [['name', 'sName', 'sType', 'domain', 'echo', 'cycle', 'backupTo', 'save', 'where_minute', 'where_hour', 'where1', 'sBody', 'type_desc', 'urladdress', 'addtime', 'update_time'], 'safe'],
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
        $query = BtCrontabsModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['id'=>SORT_DESC]]
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
            'p_id' => $this->p_id,
            'status' => $this->status,
            'cron_type' => $this->cron_type,
            'addtime' => $this->addtime,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'sName', $this->sName])
            ->andFilterWhere(['like', 'sType', $this->sType])
            ->andFilterWhere(['like', 'domain', $this->domain])
            ->andFilterWhere(['like', 'echo', $this->echo])
            ->andFilterWhere(['like', 'cycle', $this->cycle])
            ->andFilterWhere(['like', 'backupTo', $this->backupTo])
            ->andFilterWhere(['like', 'save', $this->save])
            ->andFilterWhere(['like', 'where_minute', $this->where_minute])
            ->andFilterWhere(['like', 'where_hour', $this->where_hour])
            ->andFilterWhere(['like', 'where1', $this->where1])
            ->andFilterWhere(['like', 'sBody', $this->sBody])
            ->andFilterWhere(['like', 'type_desc', $this->type_desc])
            ->andFilterWhere(['like', 'urladdress', $this->urladdress]);

        return $dataProvider;
    }
}
