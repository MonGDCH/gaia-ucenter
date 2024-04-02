<?php

declare(strict_types=1);

namespace plugins\ucenter\service;

use Throwable;
use mon\log\Logger;
use think\facade\Db;
use mon\util\Instance;
use plugins\ucenter\dao\UserDao;
use plugins\admin\dao\AdminLogDao;
use plugins\ucenter\contract\AssetsEnum;
use plugins\ucenter\dao\UserAssetsLogDao;
use plugins\ucenter\validate\AssetsValidate;

/**
 * 用户资产服务
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class AssetsService
{
    use Instance;

    /**
     * 错误信息
     *
     * @var string
     */
    protected $error = '';

    /**
     * 资产充值
     *
     * @param array $data
     * @param integer $adminID 管理员ID，大于0则记录管理员操作日志
     * @return boolean
     */
    public function charge(array $data, int $adminID = 0): bool
    {
        $validate = new AssetsValidate;
        $check = $validate->scope('charge')->data($data)->check();
        if (!$check) {
            $this->error = $validate->getError();
            return false;
        }

        // 资产字段
        $field = AssetsEnum::ASSETS_CATE_FIELD[$data['cate']];
        $freeze_field = 'freeze_' . $field;
        if (!$field) {
            $this->error = '获取资产字段失败';
            return false;
        }
        // 操作资产类型，可用、冻结
        $isUsable = $data['usable'] == AssetsEnum::ASSETS_ATTR['usable'];
        $oper_field = $isUsable ? $field : $freeze_field;

        // 用户信息
        $userInfo = UserDao::instance()->where('id', $data['uid'])->get();
        if (!$userInfo) {
            $this->error = '用户不存在';
            return false;
        }

        Db::startTrans();
        try {
            Logger::instance()->channel()->info('charge user assets');
            $save = UserDao::instance()->inc($oper_field, $data['amount'])->where('id', $data['uid'])->save();
            if (!$save) {
                Db::rollback();
                $this->error = '充值用户资产失败';
                return false;
            }

            // 记录日志
            $record = UserAssetsLogDao::instance()->record([
                'uid' => $data['uid'],
                'from' => $data['from'],
                'sid' => 0,
                'cate' => $data['cate'],
                'type' => $data['type'],
                'remark' => $data['remark'],
                'available_before'  => $userInfo[$field],
                'available_num'     => $isUsable ? $data['amount'] : '0',
                'available_after'   => $isUsable ? ($data['amount'] + $userInfo[$field]) : $userInfo[$field],
                'freeze_before'     => $userInfo[$freeze_field],
                'freeze_num'        => $isUsable ? '0' : $data['amount'],
                'freeze_after'      => $isUsable ? $userInfo[$freeze_field] : ($data['amount'] + $userInfo[$freeze_field]),
            ]);
            if (!$record) {
                Db::rollback();
                $this->error = UserAssetsLogDao::instance()->getError();
                return false;
            }

            // 记录管理员操作日志
            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'ucenter',
                    'action' => '更新用户资产',
                    'content' => '更新用户资产【【' . AssetsEnum::ASSETS_OPER_TYPE_TITLE[$data['type']] . '】',
                    'sid' => $data['uid']
                ]);
                if (!$record) {
                    Db::rollback();
                    $this->error = '记录系统操作日志失败, ' . AdminLogDao::instance()->getError();
                    return false;
                }
            }

            Db::commit();
            return true;
        } catch (Throwable $e) {
            Db::rollback();
            $this->error = '充值用户资产异常';
            Logger::instance()->channel()->error('charge user assets exception. ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 资产扣减
     *
     * @param array $data
     * @param integer $adminID 管理员ID，大于0则记录管理员操作日志
     * @return boolean
     */
    public function deduction(array $data, int $adminID = 0): bool
    {
        $validate = new AssetsValidate;
        $check = $validate->scope('deduction')->data($data)->check();
        if (!$check) {
            $this->error = $validate->getError();
            return false;
        }

        // 资产字段
        $field = AssetsEnum::ASSETS_CATE_FIELD[$data['cate']];
        $freeze_field = 'freeze_' . $field;
        if (!$field) {
            $this->error = '获取资产字段失败';
            return false;
        }

        // 操作资产类型，可用、冻结
        $isUsable = $data['usable'] == AssetsEnum::ASSETS_ATTR['usable'];
        $oper_field = $isUsable ? $field : $freeze_field;

        // 用户信息
        $userInfo = UserDao::instance()->where('id', $data['uid'])->get();
        if (!$userInfo) {
            $this->error = '用户不存在';
            return false;
        }
        if ($userInfo[$oper_field] < $data['amount']) {
            $this->error = '用户资产不足扣减';
            return false;
        }

        Db::startTrans();
        try {
            Logger::instance()->channel()->info('deduction user assets');
            $save = UserDao::instance()->dec($oper_field, $data['amount'])->where('id', $data['uid'])->save();
            if (!$save) {
                Db::rollback();
                $this->error = '扣减用户资产失败';
                return false;
            }

            // 记录日志
            $record = UserAssetsLogDao::instance()->record([
                'uid' => $data['uid'],
                'from' => $data['from'],
                'sid' => 0,
                'cate' => $data['cate'],
                'type' => $data['type'],
                'remark' => $data['remark'],
                'available_before'  => $userInfo[$field],
                'available_num'     => $isUsable ? $data['amount'] : '0',
                'available_after'   => $isUsable ? ($userInfo[$field] - $data['amount']) : $userInfo[$field],
                'freeze_before'     => $userInfo[$freeze_field],
                'freeze_num'        => $isUsable ? '0' : $data['amount'],
                'freeze_after'      => $isUsable ? $userInfo[$freeze_field] : ($userInfo[$freeze_field] - $data['amount']),
            ]);
            if (!$record) {
                Db::rollback();
                $this->error = UserAssetsLogDao::instance()->getError();
                return false;
            }

            // 记录管理员操作日志
            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'ucenter',
                    'action' => '更新用户资产',
                    'content' => '更新用户资产【【' . AssetsEnum::ASSETS_OPER_TYPE_TITLE[$data['type']] . '】',
                    'sid' => $data['uid']
                ]);
                if (!$record) {
                    Db::rollback();
                    $this->error = '记录系统操作日志失败, ' . AdminLogDao::instance()->getError();
                    return false;
                }
            }

            Db::commit();
            return true;
        } catch (Throwable $e) {
            Db::rollback();
            $this->error = '扣减用户资产异常';
            Logger::instance()->channel()->error('deduction user assets exception. ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 资产转换(可用 -> 冻结 || 冻结 -> 可用)
     *
     * @param array $data
     * @param integer $adminID 管理员ID，大于0则记录管理员操作日志
     * @return boolean
     */
    public function shift(array $data, int $adminID = 0): bool
    {
        $validate = new AssetsValidate;
        $check = $validate->scope('shift')->data($data)->check();
        if (!$check) {
            $this->error = $validate->getError();
            return false;
        }

        // 资产字段
        $field = AssetsEnum::ASSETS_CATE_FIELD[$data['cate']];
        $freeze_field = 'freeze_' . $field;
        if (!$field) {
            $this->error = '获取资产字段失败';
            return false;
        }

        // 修改字段, usable 1-冻结转可用 0-可用转冻结
        $isUsable = $data['usable'] == AssetsEnum::ASSETS_ATTR['usable'];
        $charge_field = $isUsable ? $field : $freeze_field;
        $deduction_field = $isUsable ? $freeze_field : $field;

        // 用户信息
        $userInfo = UserDao::instance()->where('id', $data['uid'])->get();
        if (!$userInfo) {
            $this->error = '用户不存在';
            return false;
        }
        if ($userInfo[$deduction_field] < $data['amount']) {
            $this->error = '用户资产不足扣减';
            return false;
        }

        Db::startTrans();
        try {
            Logger::instance()->channel()->info('shift user assets');
            $save = UserDao::instance()->where('id', $data['uid'])->dec($deduction_field, $data['amount'])->inc($charge_field, $data['amount'])->save();
            if (!$save) {
                Db::rollback();
                $this->error = '转换用户资产失败';
                return false;
            }

            // 记录日志
            $record = UserAssetsLogDao::instance()->record([
                'uid' => $data['uid'],
                'from' => $data['from'],
                'sid' => 0,
                'cate' => $data['cate'],
                'type' => $data['type'],
                'remark' => $data['remark'],
                'available_before'  => $userInfo[$field],
                'available_num'     => $data['amount'],
                'available_after'   => $isUsable ? ($userInfo[$field] + $data['amount']) : ($userInfo[$field] - $data['amount']),
                'freeze_before'     => $userInfo[$freeze_field],
                'freeze_num'        => $data['amount'],
                'freeze_after'      => $isUsable ? ($userInfo[$freeze_field] - $data['amount']) : ($userInfo[$freeze_field] + $data['amount']),
            ]);
            if (!$record) {
                Db::rollback();
                $this->error = UserAssetsLogDao::instance()->getError();
                return false;
            }

            // 记录管理员操作日志
            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'ucenter',
                    'action' => '更新用户资产',
                    'content' => '更新用户资产【' . AssetsEnum::ASSETS_OPER_TYPE_TITLE[$data['type']] . '】',
                    'sid' => $data['uid']
                ]);
                if (!$record) {
                    Db::rollback();
                    $this->error = '记录系统操作日志失败, ' . AdminLogDao::instance()->getError();
                    return false;
                }
            }

            Db::commit();
            return true;
        } catch (Throwable $e) {
            Db::rollback();
            $this->error = '转换用户资产异常';
            Logger::instance()->channel()->error('shift user assets exception. ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 转账(uid资产A -> from资产A)
     *
     * @param array $data
     * @param integer $adminID 管理员ID，大于0则记录管理员操作日志
     * @return boolean
     */
    public function transfer(array $data, int $adminID = 0): bool
    {
        $validate = new AssetsValidate;
        $check = $validate->scope('transfer')->data($data)->check();
        if (!$check) {
            $this->error = $validate->getError();
            return false;
        }

        // 资产字段
        $field = AssetsEnum::ASSETS_CATE_FIELD[$data['cate']];
        $freeze_field = 'freeze_' . $field;
        if (!$field) {
            $this->error = '获取资产字段失败';
            return false;
        }

        // 操作资产类型，可用、冻结
        $isUsable = $data['usable'] == AssetsEnum::ASSETS_ATTR['usable'];
        $oper_field = $isUsable ? $field : $freeze_field;

        // 用户信息
        $userInfo = UserDao::instance()->where('id', $data['uid'])->get();
        if (!$userInfo) {
            $this->error = '转出用户不存在';
            return false;
        }
        if ($userInfo[$oper_field] < $data['amount']) {
            $this->error = '用户资产不足扣减';
            return false;
        }

        $fromInfo = UserDao::instance()->where('id', $data['from'])->get();
        if (!$fromInfo) {
            $this->error = '转入用户不存在';
            return false;
        }

        Db::startTrans();
        try {
            Logger::instance()->channel()->info('transfer user assets');
            // 转出
            $saveUser = UserDao::instance()->dec($oper_field, $data['amount'])->where('id', $data['uid'])->save();
            if (!$saveUser) {
                Db::rollback();
                $this->error = '用户转出资产失败';
                return false;
            }
            // 转入
            $saveFrom = UserDao::instance()->inc($oper_field, $data['amount'])->where('id', $data['from'])->save();
            if (!$saveFrom) {
                Db::rollback();
                $this->error = '转入用户资产失败';
                return false;
            }
            // 记录转出日志
            $recordUser = UserAssetsLogDao::instance()->record([
                'uid' => $data['uid'],
                'from' => $data['from'],
                'sid' => 0,
                'cate' => $data['cate'],
                'type' => 4,
                'remark' => $data['remark'],
                'available_before'  => $userInfo[$field],
                'available_num'     => $isUsable ? $data['amount'] : '0',
                'available_after'   => $isUsable ? ($userInfo[$field] - $data['amount']) : $userInfo[$field],
                'freeze_before'     => $userInfo[$freeze_field],
                'freeze_num'        => $isUsable ? '0' : $data['amount'],
                'freeze_after'      => $isUsable ? $userInfo[$freeze_field] : ($userInfo[$freeze_field] - $data['amount']),
            ]);
            if (!$recordUser) {
                Db::rollback();
                $this->error = '记录转出日志失败, ' . UserAssetsLogDao::instance()->getError();
                return false;
            }

            // 记录转入日志
            $recordUser = UserAssetsLogDao::instance()->record([
                'uid' => $data['from'],
                'from' => $data['uid'],
                'sid' => 0,
                'cate' => $data['cate'],
                'type' => 3,
                'remark' => $data['remark'],
                'available_before'  => $fromInfo[$field],
                'available_num'     => $isUsable ? $data['amount'] : '0',
                'available_after'   => $isUsable ? ($data['amount'] + $fromInfo[$field]) : $fromInfo[$field],
                'freeze_before'     => $fromInfo[$freeze_field],
                'freeze_num'        => $isUsable ? '0' : $data['amount'],
                'freeze_after'      => $isUsable ? $fromInfo[$freeze_field] : ($data['amount'] + $fromInfo[$freeze_field]),
            ]);
            if (!$recordUser) {
                Db::rollback();
                $this->error = '记录转入日志失败, ' . UserAssetsLogDao::instance()->getError();
                return false;
            }

            // 记录管理员操作日志
            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'ucenter',
                    'action' => '转帐用户资产',
                    'content' => '转帐用户资产，来源：' . $data['uid'] . '，去向：' . $data['from'],
                    'sid' => $data['uid']
                ]);
                if (!$record) {
                    Db::rollback();
                    $this->error = '记录系统操作日志失败, ' . AdminLogDao::instance()->getError();
                    return false;
                }
            }

            Db::commit();
            return true;
        } catch (Throwable $e) {
            Db::rollback();
            $this->error = '转换用户资产异常';
            Logger::instance()->channel()->error('shift user assets exception. ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取错误信息
     *
     * @return mixed
     */
    public function getError()
    {
        $error = $this->error;
        $this->error = null;
        return $error;
    }
}
