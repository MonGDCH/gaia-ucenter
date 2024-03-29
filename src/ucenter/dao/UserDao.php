<?php

declare(strict_types=1);

namespace plugins\ucenter\dao;

use Throwable;
use mon\log\Logger;
use mon\util\Common;
use mon\thinkOrm\Dao;
use mon\util\Instance;
use app\admin\dao\AdminLogDao;
use plugins\ucenter\contract\UserEnum;
use plugins\ucenter\validate\UserValidate;

/**
 * 用户Dao操作
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserDao extends Dao
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'user';

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
    protected $validate = UserValidate::class;

    /**
     * 新增
     *
     * @param array $data   新增参数
     * @param integer $adminID 管理员ID, 管理员ID大于0，则记录管理员日志
     * @return integer      用户ID
     */
    public function add(array $data, int $adminID): int
    {
        $check = $this->validate()->data($data)->scope('add')->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return 0;
        }
        if (!isset($data['mobile']) && !isset($data['email'])) {
            $this->error = '邮箱或者手机号必须设置一种';
            return 0;
        }
        // 判断用重复数据
        if (!$this->checkUnique($data)) {
            return 0;
        }

        // 邀请人
        $data['pids'] = '';
        if ($data['pid'] > 0) {
            $pidInfo = $this->where('id', $data['pid'])->find();
            if (!$pidInfo) {
                $this->error = '邀请人不存在';
                return 0;
            }
            // 邀请人链表
            $pidsList = explode(',', $pidInfo['pids']);
            array_unshift($pidsList, $data['pid']);
            $data['pids'] = implode(',', $pidsList);
        }

        // 生成密码
        $data['salt'] = Common::instance()->randString();
        $data['password'] = $this->encodePassword($data['password'], $data['salt']);

        $this->startTrans();
        try {
            Logger::instance()->channel()->info('Admin add user');
            // 保存
            $field = ['pid', 'pids', 'salt', 'email', 'mobile', 'password', 'nickname', 'level', 'avatar', 'sex', 'comment', 'status'];
            $uid = $this->allowField($field)->save($data, true, true);
            if (!$uid) {
                $this->rollback();
                $this->error = '用户新增失败';
                return 0;
            }

            // 保存日志
            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'ucenter',
                    'action' => '用户新增',
                    'content' => '用户新增：' . $data['nickname'] . ', ID：' . $uid,
                    'sid' => $uid
                ]);
                if (!$record) {
                    $this->rollback();
                    $this->error = '记录操作日志失败, ' . AdminLogDao::instance()->getError();
                    return 0;
                }
            }

            $this->commit();
            return $uid;
        } catch (Throwable $e) {
            $this->rollback();
            $this->error = '添加用户异常';
            Logger::instance()->channel()->error('Admin add user exception. file: ' . $e->getFile() . ' line: ' . $e->getLine() . ' msg: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 编辑
     *
     * @param array $data
     * @param integer $adminID
     * @return boolean
     */
    public function edit(array $data, int $adminID): bool
    {
        $check = $this->validate()->data($data)->scope('edit')->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }
        if (!isset($data['mobile']) && !isset($data['email'])) {
            $this->error = '邮箱或者手机号必须设置一种';
            return false;
        }

        $info = $this->where('id', $data['idx'])->get();
        if (!$info) {
            $this->error = '用户不存在';
            return false;
        }

        // 判断用重复数据
        if (!$this->checkUnique($data, [['id', '<>', $info['id']]])) {
            return false;
        }

        // 邀请人
        $data['pids'] = $info['pids'];
        if ($data['pid'] > 0) {
            $pidInfo = $this->where('id', $data['pid'])->get();
            if (!$pidInfo) {
                $this->error = '邀请人不存在';
                return false;
            }
            // 邀请人链表
            $pidsList = explode(',', $pidInfo['pids']);
            array_unshift($pidsList, $data['pid']);
            $data['pids'] = implode(',', $pidsList);
        }

        $this->startTrans();
        try {
            Logger::instance()->channel()->info('Edit user info');
            // 保存
            $field = ['pid', 'pids', 'email', 'mobile', 'nickname', 'level', 'avatar', 'sex', 'comment', 'status'];
            $save = $this->allowField($field)->where('id', $info['id'])->save($data);
            if (!$save) {
                $this->error = '用户编辑失败';
                return false;
            }

            // 保存日志
            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'ucenter',
                    'action' => '修改用户信息',
                    'content' => '修改用户信息: ID => ' . $info['id'],
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
            $this->error = '编辑用户信息异常';
            Logger::instance()->channel()->error('Edit user info exception. msg: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 修改密码
     *
     * @param array $data       参数
     * @param integer $adminID  管理员ID, 管理员ID大于0，则记录管理员日志
     * @param boolean $ispay    是否为交易密码
     * @param boolean $check    是否校验原密码
     * @return boolean
     */
    public function password(array $data, int $adminID, bool $ispay = false, bool $check = false): bool
    {
        $validated = $this->validate()->data($data)->scope('password')->check();
        if (!$validated) {
            $this->error = $this->validate()->getError();
            return false;
        }

        $info = $this->where('id', $data['idx'])->get();
        if (!$info) {
            $this->error = '用户不存在';
            return false;
        }

        // 修改密码类型
        $prefix = $ispay ? 'pay_' : '';
        $salt_key = $prefix . 'salt';
        $pwd_key = $prefix . 'password';

        // 验证原密码
        if ($check) {
            if (!isset($data['old_password']) || !is_string($data['old_password'])) {
                $this->error = '请输入原密码';
                return false;
            }

            $old_password = $this->encodePassword($data['old_password'], $info[$salt_key]);
            if ($old_password != $info[$pwd_key]) {
                $this->error = '原密码错误';
                return false;
            }
        }

        $salt = Common::instance()->randString();
        $password = $this->encodePassword($data['password'], $salt);
        $saveData = [];
        $saveData[$salt_key] = $salt;
        $saveData[$pwd_key] = $password;
        $pwd_text = $ispay ? '支付密码' : '密码';

        $this->startTrans();
        try {
            Logger::instance()->channel()->info('Reset user password');
            $save = $this->where('id', $info['id'])->save($saveData);
            if (!$save) {
                $this->rollback();
                $this->error = '修改密码失败';
                return false;
            }

            // 保存日志
            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'ucenter',
                    'action' => '重置用户' . $pwd_text,
                    'content' => '重置用户' . $pwd_text,
                    'sid' => $data['idx']
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
            $this->error = '重置' . $pwd_text . '异常';
            Logger::instance()->channel()->error('Reset user password exception. msg: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 修改用户状态
     *
     * @param array $data
     * @param integer $adminID
     * @return boolean
     */
    public function status(array $data, int $adminID): bool
    {
        $check = $this->validate()->data($data)->scope('status')->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }

        $info = $this->where('id', $data['idx'])->get();
        if (!$info) {
            $this->error = '用户不存在';
            return false;
        }

        if ($info['status'] == $data['status']) {
            $this->error = '状态已修改';
            return false;
        }
        // 修改审核状态，原状态必须为待审核
        if ($data['status'] == UserEnum::USER_STATUS['audit'] && $info['status'] != UserEnum::USER_STATUS['audit_fail']) {
            $this->error = '只能审核状态为审核中的用户';
            return false;
        }

        $statusMsg = UserEnum::USER_STATUS_TITLE[$data['status']];
        $this->startTrans();
        try {
            Logger::instance()->channel()->info('Edit user status');
            $saveMessage = $this->where('id', $info['id'])->save(['status' => $data['status']]);
            if (!$saveMessage) {
                $this->rollback();
                $this->error = '修改用户状态失败';
                return false;
            }

            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'ucenter',
                    'action' => '修改用户状态【' . $statusMsg . '】',
                    'content' => '修改用户状态【' . $statusMsg . '】' . ', ID: ' . $info['id'],
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
            $this->error = '修改用户状态【' . $statusMsg . '】异常';
            Logger::instance()->channel()->error('Edit user status exception. msg: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 查询列表
     *
     * @param array $data   查询参数
     * @param string $scope 查询场景
     * @return array
     */
    public function getList(array $data, string $scope = 'list'): array
    {
        $limit = isset($data['limit']) ? intval($data['limit']) : 10;
        $page = isset($data['page']) && is_numeric($data['page']) ? intval($data['page']) : 1;

        $list = $this->scope($scope, $data)->page($page, $limit)->all();
        $count = $this->scope($scope, $data, false)->count('u.id');

        return [
            'list'      => $list,
            'count'     => $count,
            'pageSize'  => $limit,
            'page'      => $page
        ];
    }

    /**
     * 列表场景
     *
     * @param \mon\thinkOrm\extend\Query $query
     * @param array $option
     * @return mixed
     */
    public function scopeList($query, array $option)
    {
        $field = ['u.*', 'c.status AS check_status'];
        $query->alias('u')->leftJoin(UserCertificationDao::instance()->getTable() . ' c', 'u.id = c.uid')->field($field);
        // ID搜索
        if (isset($option['idx']) &&  $this->validate()->id($option['idx'])) {
            $query->where('u.id', intval($option['idx']));
        }
        // 按邮箱
        if (isset($option['email']) && is_string($option['email']) && !empty($option['email'])) {
            $query->where('u.email', trim($option['email']));
        }
        // 按手机号
        if (isset($option['mobile']) && is_string($option['mobile']) && !empty($option['mobile'])) {
            $query->where('u.mobile', trim($option['mobile']));
        }
        // 按会员等级
        if (isset($option['level']) && $this->validate()->int($option['level'])) {
            $query->where('u.level', intval($option['level']));
        }
        // 按状态
        if (isset($option['status']) && $this->validate()->int($option['status'])) {
            $query->where('u.status', intval($option['status']));
        }
        // 按性别
        if (isset($option['sex']) && $this->validate()->int($option['sex'])) {
            $query->where('u.sex', intval($option['sex']));
        }
        // 按审核状态
        if (isset($option['check']) && $this->validate()->int($option['check'])) {
            $query->where('c.status', intval($option['check']));
        }
        // 注册时间搜索
        if (isset($option['start_time']) && $this->validate()->int($option['start_time'])) {
            $query->where('u.create_time', '>=', intval($option['start_time']));
        }
        if (isset($option['end_time']) && $this->validate()->int($option['end_time'])) {
            $query->where('u.create_time', '<=', intval($option['end_time']));
        }

        // 排序字段，默认id
        $order = 'u.id';
        if (isset($option['order']) && in_array($option['order'], ['create_time'])) {
            $order = 'u.' . $option['order'];
        }
        // 排序类型，默认 ASC
        $sort = 'ASC';
        if (isset($option['sort']) && in_array(strtoupper($option['sort']), ['ASC', 'DESC'])) {
            $sort = strtoupper($option['sort']);
        }

        return $query->order($order, $sort);
    }

    /**
     * 用户场景
     *
     * @param \mon\thinkOrm\extend\Query $query
     * @param array $option
     * @return mixed
     */
    public function scopeUser($query, array $option)
    {
        $query->alias('u')->field(['id', 'nickname', 'avatar', 'email', 'mobile', 'status']);
        // ID搜索
        if (isset($option['id']) &&  $this->validate()->id($option['id'])) {
            $query->where('id', intval($option['id']));
        }
        // 按昵称
        if (isset($option['nickname']) && is_string($option['nickname']) && !empty($option['nickname'])) {
            $query->where('nickname', trim($option['nickname']));
        }
        // 按邮箱
        if (isset($option['email']) && is_string($option['email']) && !empty($option['email'])) {
            $query->where('email', trim($option['email']));
        }
        // 按手机号
        if (isset($option['mobile']) && is_string($option['mobile']) && !empty($option['mobile'])) {
            $query->where('mobile', trim($option['mobile']));
        }
        // 按状态
        if (isset($option['status']) && $this->validate()->int($option['status'])) {
            $query->where('status', intval($option['status']));
        }
        // 混合查询
        if (isset($option['key']) && is_string($option['key']) && !empty($option['key'])) {
            $query->whereRaw("CONCAT(nickname, email, mobile) LIKE '%{$option['key']}%'");
        }

        // 排序字段，默认id
        $order = 'id';
        if (isset($option['order']) && in_array($option['order'], ['id'])) {
            $order = $option['order'];
        }
        // 排序类型，默认 ASC
        $sort = 'DESC';
        if (isset($option['sort']) && in_array(strtoupper($option['sort']), ['ASC', 'DESC'])) {
            $sort = $option['sort'];
        }

        return $query->order($order, $sort);
    }

    /**
     * 混淆加密密码
     *
     * @param string $password  密码
     * @param string $salt      加密盐
     * @return string
     */
    public function encodePassword(string $password, string $salt): string
    {
        return md5($salt . $password);
    }

    /**
     * 验证参数字段值是否唯一
     *
     * @param array $data   参数
     * @param array $where  额外的where条件
     * @param array $unique 验证唯一的字段
     * @return boolean
     */
    protected function checkUnique(array $data, array $where = [], array $unique = ['mobile' => '手机号', 'email' => '邮箱']): bool
    {
        foreach ($unique as $field => $text) {
            if (isset($data[$field]) && !empty($data[$field])) {
                // 存在需要唯一的字段，且字段值不为空
                $info = $this->where($field, $data[$field])->field($field)->where($where)->find();
                if ($info) {
                    $this->error = $text . '已存在';
                    return false;
                }
            }
        }

        return true;
    }
}
