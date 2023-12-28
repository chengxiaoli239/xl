<?php

namespace backend\modules\config\controllers;

use common\service\config\ConfigService;
use backend\controllers\BaseController;
use common\models\config\Config;
use common\models\config\ConfigGroup;
use common\tools\Util;
use common\tools\UtilArray;

class ConfigController extends BaseController
{
    public function actionConfig(): string
    {
        $grouplist = ConfigGroup::find()
            ->orderBy('sort asc')
            ->asArray()
            ->all();
        return $this->render('config.html', ['grouplist' => $grouplist]);
    }

    public function actionGetConfigList()
    {
        $fields = [
            'name' => ['type' => 'stringUtf8', 'empty' => true, 'default' => '', 'message' => ''],
            'group_id' => ['type' => 'int', 'empty' => true, 'default' => 0, 'message' => ''],
            'page' => ['type' => 'int', 'empty' => true, 'default' => 1, 'message' => '分页分数错误'],
            'limit' => ['type' => 'int', 'empty' => true, 'default' => 20, 'message' => '每页数据条数'],
        ];
        $this->va($fields, $_GET);
        list($offset, $limit) = UtilArray::getLimit(self::$params['page'], self::$params['limit']);

        $query = Config::find();

        if (!empty(self::$params['name'])) {
            $query->andWhere(" name like '%" . self::$params['name'] . "%' OR `key` like '%" . self::$params['name'] . "%'");
        }

        if (!empty(self::$params['group_id'])) {
            $query->andWhere("group_id=" . self::$params['group_id']);
        }

        $count = $query->count();
        $query = $query->offset($offset)->limit($limit);

        $data = $query->orderBy('sort asc')->asArray()->all();

        foreach ($data as $key => &$value) {
            $value['create_time'] = Util::formatTime($value['create_time']);
            $value['update_time'] = Util::formatTime($value['update_time']);
            $value['status'] = Config::getS('status', $value['status']);

        }
        $data = \backend\models\Adminuser::setAdminNameList($data);
        $data = ConfigGroup::setGroupNameList($data);

        $this->alert(0, '', $data, $count);
    }

    public function actionConfigCreate()
    {
        $fields = [
            'id' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => '')
        ];
        $this->va($fields);
        if (!empty(self::$params['id'])) {
            $Config = Config::findOne(self::$params['id']);
            if (!empty($Config)) {
                $data = $Config->toArray();
            }
        }
        $grouplist = ConfigGroup::find()
            ->orderBy('sort asc')
            ->asArray()
            ->all();
        $typelist = Config::getS('type');
        return $this->renderBasic('config-create.html', [
            'typelist' => $typelist,
            'grouplist' => $grouplist,
            'data' => $data
        ]);
    }

    public function actionPostConfigDelete()
    {
        $fields = [
            'id' => array('type' => 'array', 'min' => 1, 'message' => '请选择要删除的数据')
        ];
        $this->va($fields, $_POST);
        list($code, $message) = ConfigService::configDelete(self::$params['id']);
        $this->Alert($code, $message);

    }

    public function actionPostConfigCreate()
    {

        $fields = [
            'id' => ['type' => 'int', 'empty' => true, 'default' => 0, 'message' => 'ID参数错误'],
            'status' => ['type' => 'int', 'empty' => true, 'default' => 1, 'message' => '状态参数错误'],
            'name' => ['type' => 'stringUtf8', 'min' => 1, 'default' => '', 'message' => '配置名参数错误'],
            'key' => ['type' => 'stringUtf8', 'min' => 1, 'default' => '', 'message' => '配置键名参数错误'],
            'value' => ['type' => 'stringUtf8', 'min' => 1, 'default' => '', 'message' => '配置值参数错误'],
            'note' => ['type' => 'stringUtf8', 'empty' => true, 'default' => '', 'message' => '说明参数错误'],
            'group_id' => ['type' => 'stringUtf8', 'min' => 1, 'default' => '', 'message' => '分组参数错误'],
            'sort' => ['type' => 'stringUtf8', 'empty' => true, 'default' => 0, 'message' => '排序参数错误'],
            'type' => ['type' => 'stringUtf8', 'empty' => true, 'default' => 0, 'message' => '类型参数错误'],
        ];
        $this->va($fields);
        self::$params['admin_id'] = $this->_user_id;
        list($code, $message) = ConfigService::configSave(self::$params);
        //保存成功后更新首页猜你喜欢缓存-20230206

        $this->alert($code, $message);
    }

    public function actionGroup()
    {
        return $this->renderBasic('group.html', [
        ]);
    }

    public function actionGroupCreate()
    {
        $fields = [
            'id' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => '')
        ];
        $this->va($fields);
        if (!empty(self::$params['id'])) {
            $ConfigGroup = ConfigGroup::findOne(self::$params['id']);
            if (!empty($ConfigGroup)) {
                $data = $ConfigGroup->toArray();
            }
        }
        return $this->renderBasic('group-create.html', [
            'data' => $data
        ]);
    }

    /**
     * 删除
     * @return [type] [description]
     */
    public function actionPostGroupDelete()
    {
        $fields = [
            'id' => array('type' => 'array', 'min' => 1, 'message' => '请选择要删除的数据')
        ];
        $this->va($fields, $_POST);
        list($code, $message) = ConfigService::groupDelete(self::$params['id']);
        $this->alert($code, $message);

    }

    public function actionGetGroupList()
    {
        $fields = [
            'name' => array('type' => 'stringUtf8', 'empty' => true, 'default' => '', 'message' => ''),
            'page' => array('type' => 'int', 'empty' => true, 'default' => 1, 'message' => '分页分数错误'),
            'limit' => array('type' => 'int', 'empty' => true, 'default' => 20, 'message' => '每页数据条数'),
        ];
        $this->va($fields, $_GET);
        list($offset, $limit) = UtilArray::getLimit(self::$params['page'], self::$params['limit']);

        $query = ConfigGroup::find();

        if (!empty(self::$params['name'])) {
            $query->andWhere(" name like '%" . self::$params['name'] . "%' ");
        }

        $count = $query->count();

        $query = $query->offset($offset)->limit($limit);

        $data = $query
            ->orderBy('sort asc')
            ->asArray()
            ->all();


        foreach ($data as $key => &$value) {
            $value['create_time'] = ConfigGroup::formatTime($value['create_time']);
            $value['update_time'] = ConfigGroup::formatTime($value['update_time']);
        }
        $data = \backend\models\Adminuser::setAdminNameList($data);

        $this->alert(0, '', $data, $count);
    }

    public function actionPostGroupCreate()
    {
        $fields = [
            'id' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => 'ID参数错误'),
            'name' => array('type' => 'stringUtf8', 'min' => 1, 'default' => '', 'message' => '分组名称参数错误'),
            'key' => array('type' => 'stringUtf8', 'empty' => true, 'default' => '', 'message' => '键名参数错误'),
            'sort' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => '排序参数错误'),
        ];
        $this->va($fields);
        self::$params['admin_id'] = $this->getUserId();
        list($code, $message) = ConfigService::groupSave(self::$params);
        $this->alert($code, $message);
    }

    /**
     * 上传文件
     * @return [type] [description]
     */
    public function actionUploadFile()
    {
        list($code, $message, $data) = \common\services\files\UploadService::run();
        $this->alert($code, $message, $data);
    }

    /**
     * @description:多文件格式上传
     * @date:2023-09-20 15:00:13
     * @return void
     */
    public function actionUploadMoreFile()
    {
        list($code, $message, $data) = \common\services\files\UploadService::run();
        $this->alert($code, $message, $data);
    }

    public function actionIndex()
    {
        $data = ConfigMobileApp::getConfig();
        return $this->render('index.html', [
            'data' => $data
        ]);
    }

    public function actionPostUpdateConfig()
    {
        $fields = array(
            'user_center_bottom' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => ''),
            'pay_success_group_qr_code_status' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => ''),
            'offline_post_open' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => ''),
            'offline_replace_post_open' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => ''),
            'online_post_open' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => ''),

            'mail_lifeface_h5_open' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => ''),
            'mail_lifeface_mini_open' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => ''),
            'mail_lifeface_android_open' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => ''),
            'mail_lifeface_ios_open' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => ''),
            'mail_lifeface_nopass_canbe_send' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => ''),

            'mail_lifeface_cache_time' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => ''),
            'mail_canbe_post_time' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => ''),
            'submit_use_integral' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => ''),
            'byte_trip_verify' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => ''),
            'kuaishou_trip_verify' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => ''),
            'trip_verify' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => ''),
        );
        $this->va($fields);

        $ConfigMobileApp = ConfigMobileApp::findOne(ConfigMobileApp::KEY_VALUE);
        if (empty($ConfigMobileApp)) {
            $ConfigMobileApp = new ConfigMobileApp();
        }

        foreach ($_FILES as $key => $FILE) {
            $upload_rst = json_decode(Util::uploadImage([$key => $FILE]), true);
            if (!empty($upload_rst) && $upload_rst['status'] == 1) {
                if ($key == 'bottom_image') {
                    self::$params['user_center_bottom_image'] = $upload_rst['url'][0]; # 个人中心-底部设置图片
                    $ConfigMobileApp->user_center_bottom_image = self::$params['user_center_bottom_image'];
                } elseif ($key == 'group_qr_code') {
                    self::$params['pay_success_group_qr_code'] = $upload_rst['url'][0]; # 支付成功页二维码
                    $ConfigMobileApp->pay_success_group_qr_code = self::$params['pay_success_group_qr_code'];
                }
            }
        }
        foreach (self::$params as $key => $value) {

            $ConfigMobileApp->$key = $value;
        }
        $res = $ConfigMobileApp->save();
        if (empty($res)) {
            $this->alert(100002, '保存失败');
        }
        $this->alert(0, '保存成功');
    }


    /** 文档管理*/
    public function actionDoc()
    {
        return $this->render('doc.html');
    }

    /** 文档列表*/
    public function actionGetDocList()
    {
        $fields = [
            'name' => ['type' => 'stringUtf8', 'empty' => true, 'default' => '', 'message' => ''],
            'page' => ['type' => 'int', 'empty' => true, 'default' => 1, 'message' => '分页分数错误'],
            'limit' => ['type' => 'int', 'empty' => true, 'default' => 20, 'message' => '每页数据条数'],
        ];
        $this->va($fields, $_GET);
        list($offset, $limit) = UtilArray::getLimit(self::$params['page'], self::$params['limit']);

        $query = Config::find();
        if (!empty(self::$params['name'])) {
            $query->andWhere(" name like '%" . self::$params['name'] . "%' OR `key` like '%" . self::$params['name'] . "%'");
        }

        $configGroupIns = ConfigGroup::find()->where(['key' => 'doc'])->one();

        /** 文档管理*/
        $query->andWhere(['group_id' => $configGroupIns->id ?? 0]);  // 正式

        $count = $query->count();
        $query = $query->offset($offset)->limit($limit);
        $data = $query ->orderBy('sort asc') ->asArray() ->all();

        foreach ($data as $key => &$value) {
            $value['create_time'] = Config::formatTime($value['create_time']);
            $value['update_time'] = Config::formatTime($value['update_time']);
            $value['status'] = Config::getS('status', $value['status']);
        }
        $data = \backend\models\Adminuser::setAdminNameList($data);
        $data = ConfigGroup::setGroupNameList($data);

        $this->alert(0, '', $data, $count);
    }

    /** 添加文档*/
    public function actionDocCreate()
    {
        $fields = [
            'id' => array('type' => 'int', 'empty' => true, 'default' => 0, 'message' => '')
        ];
        $this->va($fields);
        if (!empty(self::$params['id'])) {
            $Config = Config::findOne(self::$params['id']);
            if (!empty($Config)) {
                $data = $Config->toArray();
            }
        }
        $grouplist = ConfigGroup::find()
            ->where(['key' => 'doc'])
            ->orderBy('sort asc')
            ->asArray()
            ->all();

        if (empty($grouplist)) {
            return '请先配置文档分组,分组关键字请输入doc';
        }

        $typelist = Util::getS('type');
        return $this->renderBasic('doc-create.html', [
            'typelist' => $typelist,
            'grouplist' => $grouplist,
            'data' => $data
        ]);
    }

}
