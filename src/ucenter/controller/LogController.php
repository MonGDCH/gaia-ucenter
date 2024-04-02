<?php

declare(strict_types=1);

namespace plugins\ucenter\controller;

use mon\http\Request;
use plugins\admin\comm\Controller;
use plugins\ucenter\dao\UserLogDao;
use plugins\ucenter\contract\AssetsEnum;
use plugins\ucenter\dao\UserLoginLogDao;
use plugins\ucenter\dao\UserAssetsLogDao;

/**
 * 用户日志管理
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class LogController extends Controller
{
    /**
     * 登录日志
     *
     * @param Request $request
     * @return mixed
     */
    public function login(Request $request)
    {
        if ($request->get('isApi')) {
            $option = $request->get();
            $result = UserLoginLogDao::instance()->getList($option);
            return $this->success('ok', $result['list'], ['count' => $result['count']]);
        }

        return $this->fetch('log/login', ['uid' => $request->uid]);
    }

    /**
     * 操作日志
     *
     * @param Request $request
     * @return mixed
     */
    public function operate(Request $request)
    {
        if ($request->get('isApi')) {
            $option = $request->get();
            $result = UserLogDao::instance()->getList($option);
            return $this->success('ok', $result['list'], ['count' => $result['count']]);
        }

        return $this->fetch('log/operate', ['uid' => $request->uid]);
    }

    /**
     * 资产日志
     *
     * @param Request $request
     * @return mixed
     */
    public function assets(Request $request)
    {
        if ($request->get('isApi')) {
            $option = $request->get();
            $result = UserAssetsLogDao::instance()->getList($option);
            return $this->success('ok', $result['list'], ['count' => $result['count']]);
        }
        return $this->fetch('log/assets', [
            'uid' => $request->uid,
            'cate' => AssetsEnum::ASSETS_CATE_TITLE,
            'type' => AssetsEnum::ASSETS_LOG_TYPE_TITLE,
            'cateJson' => json_encode(AssetsEnum::ASSETS_CATE_TITLE, JSON_UNESCAPED_UNICODE),
            'typeJson' => json_encode(AssetsEnum::ASSETS_LOG_TYPE_TITLE, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
