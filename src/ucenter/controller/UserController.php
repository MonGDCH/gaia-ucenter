<?php

declare(strict_types=1);

namespace plugins\ucenter\controller;

use mon\env\Config;
use mon\http\Request;
use plugins\ucenter\dao\UserDao;
use plugins\admin\comm\Controller;
use plugins\ucenter\contract\UserEnum;
use plugins\ucenter\contract\AssetsEnum;
use plugins\ucenter\service\AssetsService;
use plugins\ucenter\dao\UserCertificationDao;
use plugins\ucenter\contract\CertificationEnum;

/**
 * 用户管理控制器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserController extends Controller
{
    /**
     * 列表
     *
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        if ($request->get('isApi')) {
            $option = $request->get();
            $result = UserDao::instance()->getList($option);
            return $this->success('ok', $result['list'], ['count' => $result['count']]);
        }

        $this->assign('sex', UserEnum::USER_SEX_TITLE);
        $this->assign('level', UserEnum::USER_LEVEL_TITLE);
        $this->assign('status', UserEnum::USER_STATUS_TITLE);
        $this->assign('audit', CertificationEnum::AUDIT_STATUS_TITLE);
        $this->assign('sexJson', json_encode(UserEnum::USER_SEX_TITLE, JSON_UNESCAPED_UNICODE));
        $this->assign('levelJson', json_encode(UserEnum::USER_LEVEL_TITLE, JSON_UNESCAPED_UNICODE));
        $this->assign('auditJson', json_encode(CertificationEnum::AUDIT_STATUS_TITLE, JSON_UNESCAPED_UNICODE));
        return $this->fetch('user/index', ['uid' => $request->uid]);
    }

    /**
     * 获取用户
     *
     * @param Request $request
     * @return mixed
     */
    public function getUser(Request $request)
    {
        $data = $request->get();
        $result = UserDao::instance()->getList($data, 'user');
        return $this->success('ok', $result['list'], ['count' => $result['count']]);
    }

    /**
     * 新增
     *
     * @param Request $request
     * @return mixed
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $option = $request->post();
            $option['pid'] = 0;
            $option['comment'] = '';
            if (isset($option['mobile']) && empty($option['mobile'])) {
                unset($option['mobile']);
            }
            if (isset($option['email']) && empty($option['email'])) {
                unset($option['email']);
            }

            $save = UserDao::instance()->add($option, $request->uid);
            if (!$save) {
                return $this->error(UserDao::instance()->getError());
            }
            return $this->success('操作成功');
        }

        $config = Config::instance()->get('ucenter.app.register');
        return $this->fetch('user/add', [
            'sex' => UserEnum::USER_SEX_TITLE,
            'level' => UserEnum::USER_LEVEL_TITLE,
            'status' => UserEnum::USER_STATUS_TITLE,
            'config' => $config
        ]);
    }

    /**
     * 编辑
     *
     * @param Request $request
     * @return mixed
     */
    public function edit(Request $request)
    {
        $id = $request->get('idx');
        if (!check('id', $id)) {
            return $this->error('params faild');
        }

        $info = UserDao::instance()->where('id', $id)->get();
        if (!$info) {
            return $this->error('用户不存在');
        }

        // 修改信息
        if ($request->isPost()) {
            $option = $request->post();
            $option['pid'] = $info['pid'];
            if (isset($option['mobile']) && empty($option['mobile'])) {
                unset($option['mobile']);
            }
            if (isset($option['email']) && empty($option['email'])) {
                unset($option['email']);
            }

            $save = UserDao::instance()->edit($option, $request->uid);
            if (!$save) {
                return $this->error(UserDao::instance()->getError());
            }
            return $this->success('操作成功');
        }

        $config = Config::instance()->get('ucenter.app.register');
        return $this->fetch('user/edit', [
            'data' => $info,
            'sex' => UserEnum::USER_SEX_TITLE,
            'level' => UserEnum::USER_LEVEL_TITLE,
            'status' => UserEnum::USER_STATUS_TITLE,
            'config' => $config
        ]);
    }

    /**
     * 重置用户密码
     *
     * @param Request $request
     * @return mixed
     */
    public function password(Request $request)
    {
        $id = $request->get('idx');
        if (!check('id', $id)) {
            return $this->error('params faild');
        }
        $info = UserDao::instance()->where('id', $id)->get();
        if (!$info) {
            return $this->error('用户不存在');
        }

        if ($request->isPost()) {
            $option = $request->post();
            $save = UserDao::instance()->password($option, $request->uid);
            if (!$save) {
                return $this->error(UserDao::instance()->getError());
            }
            return $this->success('操作成功');
        }

        return $this->fetch('user/password', ['data' => $info]);
    }

    /**
     * 用户资产
     *
     * @param Request $request
     * @return mixed
     */
    public function assets(Request $request)
    {
        $id = $request->get('uid');
        if (!check('id', $id)) {
            return $this->error('params faild');
        }

        // 支持的操作方式
        $chargeType = AssetsEnum::ASSETS_OPER_TYPE['charge'];
        $deductionType = AssetsEnum::ASSETS_OPER_TYPE['deduction'];
        $opType = [
            // 充值
            $chargeType =>  AssetsEnum::ASSETS_OPER_TYPE_TITLE[$chargeType],
            // 扣减
            $deductionType =>  AssetsEnum::ASSETS_OPER_TYPE_TITLE[$deductionType],
        ];

        // 修改用户资产
        if ($request->isPost()) {
            $data = $request->post();
            if (!isset($data['type']) || !isset($opType[$data['type']])) {
                return $this->error('params invalid');
            }

            $data['from'] = 0;
            if ($data['type'] == $chargeType) {
                $save = AssetsService::instance()->charge($data, $request->uid);
            } else {
                $save = AssetsService::instance()->deduction($data, $request->uid);
            }
            if (!$save) {
                return $this->error(AssetsService::instance()->getError());
            }
            return $this->success('操作成功');
        }

        // 查看
        $info = UserDao::instance()->where('id', $id)->get();
        if (!$info) {
            return $this->error('用户不存在');
        }

        return $this->fetch('user/assets', [
            'data' => $info,
            'cate' => AssetsEnum::ASSETS_CATE_TITLE,
            'opType' => $opType,
            'attrType' => AssetsEnum::ASSETS_ATTR_TITLE,
        ]);
    }

    /**
     * 实名认证
     *
     * @param Request $request
     * @return mixed
     */
    public function certification(Request $request)
    {
        if ($request->isPost()) {
            // 保存认证信息
            $option = $request->post();
            $save = UserCertificationDao::instance()->edit($option, $request->uid);
            if (!$save) {
                return $this->error(UserCertificationDao::instance()->getError());
            }

            return $this->success('操作成功');
        }

        $id = $request->get('uid');
        if (!check('id', $id)) {
            return $this->error('params faild');
        }

        // 默认未上传实名认证信息
        $basicInfo = [
            'uid' => $id,
            'type' => 0,
            'name' => '',
            'identity' => '',
            'person' => '',
            'mobile' => '',
            'email' => '',
            'paper_front' => '',
            'paper_back' => '',
            'paper_hand' => '',
            'license' => '',
            'comment' => '',
            'approved_time' => '',
            'status' => '',
        ];
        // 用户实名认证信息
        $info = UserCertificationDao::instance()->where('uid', $id)->get();
        $info = array_merge($basicInfo, $info);
        // 是否允许编辑，未提交或已驳回
        $isEdit = $info['status'] === '' || $info['status'] == CertificationEnum::AUDIT_STATUS['faild'];
        return $this->fetch('user/certification', [
            'data' => $info,
            'type' => CertificationEnum::AUDIT_TYPE_TITLE,
            'typeTitle' => CertificationEnum::AUDIT_TYPE_TITLE[$info['type']] ?? '',
            'isEdit' => $isEdit
        ]);
    }

    /**
     * 审核实名认证信息
     *
     * @param Request $request
     * @return mixed
     */
    public function checkCertification(Request $request)
    {
        $data = $request->post();
        $check = UserCertificationDao::instance()->status($data, $request->uid);
        if (!$check) {
            return $this->error(UserCertificationDao::instance()->getError());
        }

        return $this->success('操作成功');
    }
}
