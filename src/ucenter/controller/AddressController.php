<?php

declare(strict_types=1);

namespace plugins\ucenter\controller;

use mon\http\Request;
use app\admin\dao\RegionDao;
use support\http\Controller;
use plugins\ucenter\dao\UserAddressDao;

/**
 * 用户地址管理
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class AddressController extends Controller
{
    /**
     * 列表
     *
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $user_id = $request->get('uid');
        if (!check('id', $user_id)) {
            return $this->error('params faild');
        }

        if ($request->get('isApi')) {
            $option = $request->get();
            $option['uid'] = $user_id;
            $result = UserAddressDao::instance()->getList($option);
            return $this->success('操作成功', $result['list'], ['count' => $result['count']]);
        }

        return $this->fetch('address/index', [
            'uid' => $request->uid,
            'user_id' => $user_id,
        ]);
    }

    /**
     * 新增
     *
     * @param Request $request
     * @return mixed
     */
    public function add(Request $request)
    {
        $user_id = $request->get('uid');
        if (!check('id', $user_id)) {
            return $this->error('params faild');
        }

        if ($request->isPost()) {
            $option = $request->post();
            $option['default'] = 0;
            $save = UserAddressDao::instance()->add($option, $request->uid);
            if (!$save) {
                return $this->error(UserAddressDao::instance()->getError());
            }
            return $this->success('操作成功');
        }

        $region = RegionDao::instance()->getTreeData(0);
        return $this->fetch('address/add', [
            'user_id' => $user_id,
            'region' => json_encode($region, JSON_UNESCAPED_UNICODE)
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
        // 修改信息
        if ($request->isPost()) {
            $option = $request->post();
            // 管理端编辑，固定取消默认地址
            $option['default'] = 0;
            $save = UserAddressDao::instance()->edit($option, $request->uid);
            if (!$save) {
                return $this->error(UserAddressDao::instance()->getError());
            }
            return $this->success('操作成功');
        }

        $id = $request->get('idx');
        if (!check('id', $id)) {
            return $this->error('params faild');
        }

        $info = UserAddressDao::instance()->where('id', $id)->get();
        if (!$info) {
            return $this->error('用户地址不存在');
        }

        $region = RegionDao::instance()->getTreeData(0);
        return $this->fetch('address/edit', [
            'data' => $info,
            'region' => json_encode($region, JSON_UNESCAPED_UNICODE)
        ]);
    }

    /**
     * 修改状态
     *
     * @param Request $request
     * @return mixed
     */
    public function status(Request $request)
    {
        $save = UserAddressDao::instance()->status($request->post(), $request->uid);
        if (!$save) {
            return $this->error(UserAddressDao::instance()->getError());
        }

        return $this->success('操作成功');
    }
}
