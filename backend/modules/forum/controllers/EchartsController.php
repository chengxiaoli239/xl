<?php

namespace backend\modules\forum\controllers;

use backend\models\StaticProfits;
use Yii;
use backend\models\SscDwsHzNums;
use backend\models\searchs\SscDwsHzNums as SscDwsHzNumsSearch;
use backend\models\searchs\StaticProfits as StaticProfitsSearch;
use backend\controllers\BaseController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use backend\service\SscDataService;

/**
 * SscDwsHzNumsController implements the CRUD actions for SscDwsHzNums model.
 */
class EchartsController extends BaseController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all SscDwsHzNums models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new SscDwsHzNumsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @desc 定位和值区间趋势
     * @return string
     */
    public function actionSscDwsHzNums(){
        $searchModel = new SscDwsHzNumsSearch();
        $queryParams = Yii::$app->request->queryParams;
        $dataProvider = $searchModel->search($queryParams);
        $periods = $queryParams['SscDwsHzNums']['periods'];  // 20期，50期，100期，120期...
        $positions = $queryParams['SscDwsHzNums']['positions'];
        $hezhi = $queryParams['SscDwsHzNums']['hezhi'];
        $hezhi = $hezhi ? $hezhi : 9;
        $periodsArr = [
            //20,
            //50,
            //100,
            200,
            '2000',
             '5000'
        ];
        $positions = $positions ? $positions : '2,3';
        $chartsData = SscDataService::getHzNumsChartsData($hezhi, $periodsArr, $positions);
        //p($chartsData);

        $searchModel->positions = $positions;
        $searchModel->hezhi = $hezhi;

        return $this->render('echarts', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'chartsData' => $chartsData,
            'periods' => $periods,
            'positions' => $positions,
        ]);
    }

    /**
     * @desc 定位和值区间趋势
     * @return string
     */
    public function actionTypeStaticProfits(){
        $searchModel = new StaticProfitsSearch();
        $queryParams = Yii::$app->request->queryParams;
        $dataProvider = $searchModel->search($queryParams);
        $periods = $queryParams['StaticProfits']['periods'];  // 20期，50期，100期，120期...
        $plan_id = $queryParams['StaticProfits']['plan_id'];
        //$hezhi = $queryParams['SscDwsHzNums']['hezhi'];
        //$hezhi = $hezhi ? $hezhi : 9;
        $plan_id = $plan_id ? $plan_id : '981';
        $chartsData = SscDataService::getPlanChartsData($plan_id);
        //p($chartsData['series']);

        $searchModel->plan_id = $plan_id;

        return $this->render('type_static_profits', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'chartsData' => $chartsData,
            'periods' => $periods,
            'plan_id' => $plan_id,
        ]);
    }

}
