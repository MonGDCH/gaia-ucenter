<?php

declare(strict_types=1);

namespace plugins\ucenter\dao;

use mon\thinkOrm\Dao;
use mon\util\Instance;
use plugins\ucenter\validate\AssetsValidate;

/**
 * 用户资产流水日志Dao操作
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserAssetsLogDao extends Dao
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'user_assets_log';

    /**
     * 验证器
     *
     * @var string
     */
    protected $validate = AssetsValidate::class;

    /**
     * 记录日志
     *
     * @param array $data     请求参数
     * @return integer 日志ID
     */
    public function record(array $data): int
    {
        $check = $this->validate()->scope('record')->data($data)->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return 0;
        }

        $data['sid'] = $data['sid'] ?? '0';
        $data['remark'] = $data['remark'] ?? '0';
        $data['create_time'] = time();
        $field = [
            'uid', 'from', 'sid', 'cate', 'type', 'remark', 'available_before', 'available_num',
            'available_after', 'freeze_before', 'freeze_num', 'freeze_after', 'create_time'
        ];
        $log_id = $this->allowField($field)->save($data, true, true);
        if (!$log_id) {
            $this->error = '记录操作日志失败';
            return 0;
        }

        return $log_id;
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
        $total = $this->scope('list', $data)->count();

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
     * @param mixed $query
     * @param mixed $options
     * @return mixed
     */
    protected function scopeList($query, $options)
    {
        $query->alias('log')
            ->join(UserDao::instance()->getTable() . ' user', 'log.uid=user.id', 'LEFT')
            ->join(UserDao::instance()->getTable() . ' from_user', 'log.from=from_user.id', 'LEFT');
        // 查询
        $field = ['log.*', 'user.mobile', 'user.email', 'user.nickname', 'from_user.mobile AS from_mobile', 'from_user.email AS from_email', 'from_user.nickname AS from_nickanme'];
        $query->field($field);
        // 按用户ID
        if (isset($options['uid']) && $this->validate()->id($options['uid'])) {
            $query->where('log.uid', intval($options['uid']));
        }
        // 按来源
        if (isset($options['from']) && $this->validate()->int($options['from'])) {
            $query->where('log.from', intval($options['from']));
        }
        // 按资产类型
        if (isset($options['cate']) && $this->validate()->int($options['cate'])) {
            $query->where('log.cate', trim($options['cate']));
        }
        // 按操作类型
        if (isset($options['type']) && $this->validate()->int($options['type'])) {
            $query->where('log.type', trim($options['type']));
        }
        // 按手机号
        if (isset($options['mobile']) && is_string($options['mobile']) && !empty($options['mobile'])) {
            $query->where('user.mobile', trim($options['mobile']));
        }
        // 按邮箱
        if (isset($options['email']) && is_string($options['email']) && !empty($options['email'])) {
            $query->where('user.email', trim($options['email']));
        }
        // 时间搜索
        if (isset($options['start_time']) && $this->validate()->int($options['start_time'])) {
            $query->where('log.create_time', '>=', intval($options['start_time']));
        }
        if (isset($options['end_time']) && $this->validate()->int($options['end_time'])) {
            $query->where('log.create_time', '<=', intval($options['end_time']));
        }

        // 排序字段，默认id
        $order = 'log.id';
        if (isset($option['order']) && in_array($option['order'], ['id', 'create_time'])) {
            $order = 'log.' . $option['order'];
        }
        // 排序类型，默认 DESC
        $sort = 'DESC';
        if (isset($option['sort']) && in_array(strtoupper($option['sort']), ['ASC', 'DESC'])) {
            $sort = $option['sort'];
        }

        return $query->order($order, $sort);
    }
}
