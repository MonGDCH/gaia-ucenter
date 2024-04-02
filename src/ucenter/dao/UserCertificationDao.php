<?php

declare(strict_types=1);

namespace plugins\ucenter\dao;

use Throwable;
use mon\log\Logger;
use mon\thinkOrm\Dao;
use mon\util\Instance;
use plugins\admin\dao\AdminLogDao;
use plugins\ucenter\contract\CertificationEnum;
use plugins\ucenter\validate\CertificationValidate;

/**
 * 用户实名认证Dao操作
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserCertificationDao extends Dao
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'user_certification';

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
    protected $validate = CertificationValidate::class;

    /**
     * 编辑认证信息
     *
     * @param array $data
     * @param integer $adminID
     * @return boolean
     */
    public function edit(array $data, int $adminID): bool
    {
        if (!isset($data['type']) || !isset(CertificationEnum::AUDIT_TYPE_SCOPE[$data['type']])) {
            $this->error = '请选择合法的认证类型';
            return false;
        }
        if (!isset($data['uid']) || !$this->validate()->id($data['uid'])) {
            $this->error = '参数错误';
            return false;
        }

        $scope = CertificationEnum::AUDIT_TYPE_SCOPE[$data['type']];
        $check = $this->validate()->data($data)->scope($scope)->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }

        $info = $this->where('uid', $data['uid'])->find();
        $this->startTrans();
        try {
            Logger::instance()->channel()->info('Admin edit certification');
            $data['status'] = 0;
            $field = ['uid', 'type', 'name', 'identity', 'person', 'mobile', 'email', 'paper_front', 'paper_back', 'paper_hand', 'license', 'status'];
            if (!$info) {
                // 未提交，新增
                $save = $this->allowField($field)->save($data, true);
            } else {
                // 已提交，更新
                $save = $this->allowField($field)->where('uid', $info['uid'])->save($data);
            }
            if (!$save) {
                $this->rollback();
                $this->error = '编辑失败';
                return false;
            }

            // 记录操作日志
            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'ucenter',
                    'action' => '编辑实名认证',
                    'content' => '编辑用户实名认证',
                    'sid' => $data['uid']
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
            $this->error = '编辑用户实名认证异常';
            Logger::instance()->channel()->error('Edit certification exception, msg => ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 审核
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

        $info = $this->where('uid', $data['uid'])->get();
        if (!$info) {
            $this->error = '实名信息不存在';
            return false;
        }
        if ($info['status'] == $data['status']) {
            $this->error = '请勿重复审核';
            return false;
        }

        $saveData = [
            'status' => $data['status'],
            'comment' => $data['comment']
        ];
        // 审核通过，记录通过时间
        if ($data['status'] == CertificationEnum::AUDIT_STATUS['pass']) {
            $saveData['approved_time'] = time();
        }

        $this->startTrans();
        try {
            Logger::instance()->channel()->info('Admin confirm certification');
            $field = ['status', 'comment', 'approved_time'];
            $save = $this->allowField($field)->where('uid', $info['uid'])->save($data);
            if (!$save) {
                $this->rollback();
                $this->error = '审核失败';
                return false;
            }

            // 记录操作日志
            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'ucenter',
                    'action' => '审核实名认证',
                    'content' => '审核用户实名认证ID: ' . $info['uid'],
                    'sid' => $info['uid']
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
            $this->error = '审核用户实名认证异常';
            Logger::instance()->channel()->error('Admin confirm certification exception, msg => ' . $e->getMessage());
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
        $count = $this->scope('list', $data, false)->count('uid');

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
        $order = 'uid';
        if (isset($option['order']) && in_array($option['order'], ['create_time', 'status'])) {
            $order = $option['order'];
        }
        // 排序类型，默认 ASC
        $sort = 'ASC';
        if (isset($option['sort']) && in_array(strtoupper($option['sort']), ['ASC', 'DESC'])) {
            $sort = strtoupper($option['sort']);
        }

        return $query->order($order, $sort);
    }
}
