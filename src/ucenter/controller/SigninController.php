<?php

declare(strict_types=1);

namespace plugins\ucenter\controller;

use mon\env\Config;
use mon\http\Request;
use plugins\admin\comm\Controller;
use plugins\admin\service\DictService;
use plugins\ucenter\dao\UserSigninDao;

/**
 * 用户签到管理
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class SigninController extends Controller
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
            $result = UserSigninDao::instance()->getList($option);
            return $this->success('ok', $result['list'], ['count' => $result['count']]);
        }

        return $this->fetch('userSignin/index', ['uid' => $request->uid]);
    }

    /**
     * 添加用户签到记录
     *
     * @param Request $request
     * @return mixed
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $uid = $request->post('uid', 0);
            $date = $request->post('day', '');
            if (!check('id', $uid)) {
                return $this->error('参数错误');
            }

            $save = UserSigninDao::instance()->add(intval($uid), $date, $request->uid);
            if (!$save) {
                return $this->error(UserSigninDao::instance()->getError());
            }
            return $this->success('操作成功');
        }

        return $this->fetch('userSignin/add');
    }

    /**
     * 查看签到配置
     *
     * @param Request $request
     * @return mixed
     */
    public function config(Request $request)
    {
        // if ($request->isPost()) {
        //     $day = $request->post('day', 0);
        //     $week = $request->post('week', 0);
        //     if (!check('int', $day) || $day < 0) {
        //         return $this->error('每日签到参数错误');
        //     }
        //     if (!check('int', $week) || $week < 0) {
        //         return $this->error('每周签到参数错误');
        //     }

        //     // 修改配置字典数据
        //     $dictData = [];
        //     $save = DictService::instance()->edit($dictData, $request->uid, true, 'ucenter');
        //     if (!$save) {
        //         return $this->error(DictService::instance()->getError());
        //     }

        //     return $this->success('操作成功');
        // }

        $config = Config::instance()->get('ucenter.app.sign', []);
        return $this->fetch('userSignin/config', ['config' => $config]);
    }
}
