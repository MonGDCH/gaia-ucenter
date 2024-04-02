<?php

declare(strict_types=1);

namespace plugins\ucenter\dao;

use Throwable;
use mon\log\Logger;
use mon\thinkOrm\Dao;
use mon\util\Instance;
use plugins\admin\dao\AdminLogDao;

/**
 * 用户签到Dao操作
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserSigninDao extends Dao
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'user_signin';

    /**
     * 自动写入时间戳
     *
     * @var boolean
     */
    protected $autoWriteTimestamp = true;

    /**
     * 添加签到记录
     *
     * @param integer $uid  用户ID
     * @param string $day  签到日期
     * @param integer $adminID  管理员ID
     * @return boolean
     */
    public function add(int $uid, string $day, int $adminID): bool
    {
        $date_pattern = "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/";
        if (preg_match($date_pattern, $day) !== 1) {
            $this->error = '签到日期格式错误';
            return false;
        }

        $exists = $this->where('uid', $uid)->where('day', $day)->get();
        if ($exists) {
            $this->error = '用户当日已签到';
            return false;
        }

        $this->startTrans();
        try {
            Logger::instance()->channel()->info('Add user signin');
            $signin_id = $this->save(['uid' => $uid, 'day' => $day], true, true);
            if (!$signin_id) {
                $this->rollback();
                $this->error = '添加签到记录失败';
                return false;
            }
            // 保存日志
            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'ucenter',
                    'action' => '添加用户签到记录',
                    'content' => '添加用户签到记录：' . $day,
                    'sid' => $signin_id
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
            $this->error = '添加用户签到记录异常';
            Logger::instance()->channel()->error('Add user signin exception. file: ' . $e->getFile() . ' line: ' . $e->getLine() . ' msg: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 查询日志列表
     *
     * @param array $data 请求参数
     * @return array
     */
    public function getList(array $data): array
    {
        $limit = isset($data['limit']) ? intval($data['limit']) : 10;
        $page = isset($data['page']) && is_numeric($data['page']) ? intval($data['page']) : 1;
        // 查询
        $list = $this->scope('list', $data)->page($page, $limit)->all();
        $total = $this->scope('list', $data)->count('s.id');

        return [
            'list'      => $list,
            'count'     => $total,
            'pageSize'  => $limit,
            'page'      => $page
        ];
    }

    /**
     * 查询列表场景
     *
     * @param \mon\thinkOrm\extend\Query $query
     * @param array $option
     * @return mixed
     */
    protected function scopeList($query, array $option)
    {
        $query->alias('s')->join(UserDao::instance()->getTable() . ' user', 's.uid=user.id', 'LEFT');
        $query->field(['s.*', 'user.mobile', 'user.email', 'user.nickname']);

        // 按用户ID
        if (isset($option['uid']) && $this->validate()->id($option['uid'])) {
            $query->where('s.uid', intval($option['uid']));
        }
        // 按日期
        if (isset($option['day']) && is_string($option['day']) && !empty($option['day'])) {
            $query->where('s.day', trim($option['day']));
        }
        // 时间搜索
        if (isset($option['start_time']) && $this->validate()->int($option['start_time'])) {
            $query->where('s.create_time', '>=', intval($option['start_time']));
        }
        if (isset($option['end_time']) && $this->validate()->int($option['end_time'])) {
            $query->where('s.create_time', '<=', intval($option['end_time']));
        }

        // 排序字段，默认id
        $order = 's.id';
        if (isset($option['order']) && in_array($option['order'], ['id', 'date', 'create_time'])) {
            $order = 's.' . $option['order'];
        }
        // 排序类型，默认 DESC
        $sort = 'DESC';
        if (isset($option['sort']) && in_array(strtoupper($option['sort']), ['ASC', 'DESC'])) {
            $sort = $option['sort'];
        }

        return $query->order($order, $sort);
    }
}
