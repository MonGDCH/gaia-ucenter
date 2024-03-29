<?php

declare(strict_types=1);

namespace plugins\ucenter\dao;

use Throwable;
use mon\log\Logger;
use mon\thinkOrm\Dao;
use mon\util\Instance;
use app\admin\dao\AdminLogDao;
use plugins\ucenter\contract\UserEnum;
use plugins\ucenter\validate\AddressValidate;

/**
 * 用户地址Dao操作
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserAddressDao extends Dao
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'user_address';

    /**
     * 自动写入时间戳
     *
     * @var boolean
     */
    protected $autoWriteTimestamp = true;

    /**
     * 验证器
     *
     * @var string
     */
    protected $validate = AddressValidate::class;

    /**
     * 添加地址
     *
     * @param array $data   操作参数
     * @param integer $adminID  管理员ID，大于0记录管理员日志
     * @param boolean $userLog  是否记录用户日志
     * @return integer
     */
    public function add(array $data, int $adminID, bool $userLog = false): int
    {
        $check = $this->validate()->data($data)->scope('add')->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return 0;
        }

        $userInfo = UserDao::instance()->where('id', $data['uid'])->get();
        if (!$userInfo) {
            $this->error = '用户不存在';
            return 0;
        }

        $this->startTrans();
        try {
            // 保存
            Logger::instance()->channel()->info('add user address');
            $address_id = $this->allowField(['uid', 'name', 'mobile', 'pca', 'address', 'pcode'])->save($data, true, true);
            if (!$address_id) {
                $this->rollback();
                $this->error = '添加地址失败';
                return 0;
            }

            if ($data['default'] == UserEnum::USER_ADDRESS_DEFAULT['enable']) {
                // 设置为默认地址
                $saveDefault = UserDao::instance()->where('id', $data['uid'])->save(['address_id' => $address_id]);
                if (!$saveDefault) {
                    $this->rollback();
                    $this->error = '设置为默认地址失败';
                    return 0;
                }
            }

            // 记录用户操作日志
            if ($userLog) {
                $record = UserLogDao::instance()->record([
                    'uid' => $data['uid'],
                    'action' => '用户添加地址信息',
                    'content' => '用户添加地址信息',
                    'sid' => $address_id
                ]);
                if (!$record) {
                    $this->rollback();
                    $this->error = '记录操作日志失败, ' . UserLogDao::instance()->getError();
                    return false;
                }
            }

            // 保存管理员日志
            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'ucenter',
                    'action' => '添加用户地址',
                    'content' => '添加用户地址',
                    'sid' => $address_id
                ]);
                if (!$record) {
                    $this->rollback();
                    $this->error = '记录系统操作日志失败, ' . AdminLogDao::instance()->getError();
                    return false;
                }
            }

            $this->commit();
            return $address_id;
        } catch (Throwable $e) {
            $this->rollback();
            $this->error = '新增地址信息异常';
            Logger::instance()->channel()->error('Add user address exception. msg: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 修改地址
     *
     * @param array $data   操作参数
     * @param integer $adminID  管理员ID，大于0记录管理员日志
     * @param boolean $userLog  是否记录用户日志
     * @return boolean
     */
    public function edit(array $data, int $adminID, bool $userLog = false): bool
    {
        $check = $this->validate()->data($data)->scope('edit')->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }

        $info = $this->where('id', $data['idx'])->where('uid', $data['uid'])->get();
        if (!$info) {
            $this->error = '地址信息不存在';
            return false;
        }

        $this->startTrans();
        try {
            Logger::instance()->channel()->info('edit user address');
            // 保存
            $save = $this->allowField(['name', 'mobile', 'pca', 'address', 'pcode'])->where('id', $info['id'])->save($data);
            if (!$save) {
                $this->rollback();
                $this->error = '保存地址失败';
                return false;
            }

            if ($data['default'] == '1') {
                // 设置为默认地址
                $saveDefault = UserDao::instance()->where('id', $data['uid'])->save(['address_id' => $info['id']]);
                if (!$saveDefault) {
                    $this->rollback();
                    $this->error = '设置为默认地址失败';
                    return false;
                }
            }

            // 记录用户操作日志
            if ($userLog) {
                $record = UserLogDao::instance()->record([
                    'uid' => $data['uid'],
                    'action' => '用户编辑地址信息',
                    'content' => '用户编辑地址信息',
                    'sid' => $info['id']
                ]);
                if (!$record) {
                    $this->rollback();
                    $this->error = '记录操作日志失败, ' . UserLogDao::instance()->getError();
                    return false;
                }
            }

            if ($adminID > 0) {
                // 保存日志
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'ucenter',
                    'action' => '编辑用户地址',
                    'content' => '编辑用户地址',
                    'sid' => $info['id']
                ]);
                if (!$record) {
                    $this->rollback();
                    $this->error = '记录系统操作日志失败, ' . AdminLogDao::instance()->getError();
                    return false;
                }
            }


            $this->commit();
            return true;
        } catch (Throwable $e) {
            $this->rollback();
            $this->error = '编辑地址信息异常';
            Logger::instance()->channel()->error('edit user address exception. msg: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 修改状态
     *
     * @param array $data 参数
     * @param integer $adminID  管理员ID，大于0记录管理员日志
     * @param integer $uid      用户ID，大于0记录用户日志
     * @return boolean
     */
    public function status(array $data, int $adminID, int $uid = 0): bool
    {
        $check = $this->validate()->data($data)->scope('status')->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }

        // 获取用户信息
        $info = $this->where('id', $data['idx'])->get();
        if (!$info) {
            $this->error = '地址信息不存在';
            return false;
        }

        if ($data['status'] == $info['status']) {
            $this->error = '状态已修改';
            return false;
        }

        // 存在用户ID，为用户修改状态，判断记录是否为当前用户
        if ($uid > 0 && $uid != $info['uid']) {
            $this->error = '用户不允许操作该记录';
            return false;
        }

        $this->startTrans();
        try {
            Logger::instance()->channel()->info('modify address status');
            $save = $this->where('id', $info['id'])->save(['status' => $data['status']]);
            if (!$save) {
                $this->rollback();
                $this->error = '修改用户地址状态失败';
                return false;
            }

            // 记录操作日志
            if ($uid > 0) {
                $record = UserLogDao::instance()->record([
                    'uid' => $uid,
                    'action' => '修改地址状态',
                    'content' => '修改地址状态, status => ' . $data['status'],
                    'sid' => $info['id']
                ]);
                if (!$record) {
                    $this->rollback();
                    $this->error = '记录操作日志失败, ' . UserLogDao::instance()->getError();
                    return false;
                }
            }

            // 记录系统操作日志
            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'ucenter',
                    'action' => '修改用户地址状态',
                    'content' => '修改用户地址状态, status => ' . $data['status'],
                    'sid' => $info['id']
                ]);
                if (!$record) {
                    $this->rollback();
                    $this->error = '记录操作日志失败, ' . AdminLogDao::instance()->getError();
                    return false;
                }
            }

            $this->commit();
            return true;
        } catch (Throwable $e) {
            $this->rollback();
            $this->error = '修改地址状态异常';
            Logger::instance()->channel()->error('User modify address status exception, msg => ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 查询列表
     *
     * @param array $data
     * @return array
     */
    public function getList(array $data): array
    {
        $limit = isset($data['limit']) ? intval($data['limit']) : 10;
        $page = isset($data['page']) && is_numeric($data['page']) ? intval($data['page']) : 1;

        $list = $this->scope('list', $data)->page($page, $limit)->all();
        $count = $this->scope('list', $data, false)->count('id');

        return [
            'list'      => $list,
            'count'     => $count,
            'pageSize'  => $limit,
            'page'      => $page
        ];
    }

    /**
     * 查询场景
     *
     * @param \mon\thinkOrm\extend\Query $query
     * @param array $option
     * @return mixed
     */
    public function scopeList($query, array $option)
    {
        // ID搜索
        if (isset($option['idx']) &&  $this->validate()->id($option['idx'])) {
            $query->where('id', intval($option['idx']));
        }
        if (isset($option['uid']) &&  $this->validate()->id($option['uid'])) {
            $query->where('uid', intval($option['uid']));
        }
        // 按状态
        if (isset($option['status']) && $this->validate()->int($option['status'])) {
            $query->where('status', intval($option['status']));
        }
        // 时间搜索
        if (isset($option['start_time']) && $this->validate()->int($option['start_time'])) {
            $query->where('create_time', '>=', intval($option['start_time']));
        }
        if (isset($option['end_time']) && $this->validate()->int($option['end_time'])) {
            $query->where('create_time', '<=', intval($option['end_time']));
        }

        // 排序字段，默认id
        $order = 'id';
        if (isset($option['order']) && in_array($option['order'], ['update_time', 'create_time'])) {
            $order = $option['order'];
        }
        // 排序类型，默认 ASC
        $sort = 'ASC';
        if (isset($option['sort']) && in_array(strtoupper($option['sort']), ['ASC', 'DESC'])) {
            $sort = $option['sort'];
        }

        return $query->order($order, $sort);
    }
}
