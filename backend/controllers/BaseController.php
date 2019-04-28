<?php
namespace backend\controllers;

use backend\models\User;
use backend\service\HN0898Service;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;

/**
 * Site controller
 */
class BaseController extends Controller
{
    public $_post;
    public $_get;
    public $_account;
    public $_user_id;

    public function init()
    {
        parent::init();
        $this->_user_id = Yii::$app->user->id;
        self::_initData();
        $this->_post = \Yii::$app->request->post();
        $this->_get = \Yii::$app->request->get();
    }

    public function _initData(){
        $User = User::findOne(['admin_id'=>$this->_user_id]);
        $this->_account = $User->account;
        Yii::$app->params['baseUrl']  = $User->ssc_domain;
        Yii::$app->params['domain']  = str_replace('https://','',$User->ssc_domain);
        Yii::$app->params['ajaxUrlRouteUser']  = $User->ssc_domain.Yii::$app->params['ajaxUrlRouteUser_key'];
        Yii::$app->params['sscUrlRoute']  = $User->ssc_domain.Yii::$app->params['sscUrlRoute_key'];
        Yii::$app->params['ajaxUrlRouteLot']  = $User->ssc_domain.Yii::$app->params['ajaxUrlRouteLot_key'];
        Yii::$app->params['ajaxUrlRouteLotDw']  = $User->ssc_domain.Yii::$app->params['ajaxUrlRouteLotDw_key'];
        //HN0898Service::synBalanceByUserId($this->_user_id);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

}
