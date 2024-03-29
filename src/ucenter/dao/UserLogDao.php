<?php

declare(strict_types=1);

namespace plugins\ucenter\dao;

use mon\http\Context;
use mon\http\Request;
use mon\thinkOrm\Dao;
use mon\util\Instance;

/**
 * 用户操作日志Dao操作
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserLogDao extends Dao
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'user_log';

    /**
     * 记录日志
     *
     * @param array $data     请求参数
     * @return integer 日志ID
     */
    public function record(array $data): int
    {
        $check = $this->validate()->rule([
            'uid'       => ['required', 'int', 'min:0'],
            'sid'       => ['int', 'min:0'],
            'method'    => ['str'],
            'path'      => ['str'],
            'ua'        => ['str'],
            'ip'        => ['ip'],
            'action'    => ['required', 'str'],
            'content'   => ['str']
        ])->message([
            'uid'       => '请输入用户ID',
            'sid'       => '请输入关联ID',
            'method'    => '请输入合法的请求方式',
            'path'      => '请输入合法的请求路径',
            'ua'        => '请输入合法的ua标识',
            'ip'        => '请输入合法的IP地址',
            'action'    => '请输入操作类型',
            'content'   => '请输入操作内容'
        ])->data($data)->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return 0;
        }

        /** @var Request $request 上下文请求实例 */
        $request = Context::get(Request::class);

        $saveData = [];
        $saveData['uid'] = $data['uid'];
        $saveData['action'] = $data['action'];
        $saveData['content'] = $data['content'] ?? '';
        $saveData['sid'] = $data['sid'] ?? '0';
        $saveData['method'] = $data['method'] ?? ($request ? $request->method() : '');
        $saveData['path'] = $data['path'] ?? ($request ? $request->path() : '');
        $saveData['ua'] = $data['ua'] ?? ($request ? $request->header('user-agent', '') : '');
        $saveData['ip'] = $data['ip'] ?? ($request ? $request->ip() : '0.0.0.0');
        $saveData['create_time'] = time();

        $saveLogID = $this->save($saveData, true, true);
        if (!$saveLogID) {
            $this->error = '记录用户操作日志失败';
            return 0;
        }

        return $saveLogID;
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
     * @param \mon\thinkOrm\extend\Query $query
     * @param array $option
     * @return mixed
     */
    protected function scopeList($query, $option)
    {
        $query->alias('log')->join(UserDao::instance()->getTable() . ' user', 'log.uid=user.id', 'left');
        $query->field(['log.*', 'user.mobile', 'user.email', 'user.nickname']);

        // 按用户ID
        if (isset($option['uid']) && $this->validate()->id($option['uid'])) {
            $query->where('log.uid', intval($option['uid']));
        }
        // 按手机号
        if (isset($option['mobile']) && is_string($option['mobile']) && !empty($option['mobile'])) {
            $query->where('user.mobile', trim($option['mobile']));
        }
        // 按邮箱
        if (isset($option['email']) && is_string($option['email']) && !empty($option['email'])) {
            $query->where('user.email', trim($option['email']));
        }
        // 时间搜索
        if (isset($option['start_time']) && $this->validate()->int($option['start_time'])) {
            $query->where('log.create_time', '>=', intval($option['start_time']));
        }
        if (isset($option['end_time']) && $this->validate()->int($option['end_time'])) {
            $query->where('log.create_time', '<=', intval($option['end_time']));
        }

        // 排序字段，默认id
        $order = 'log.id';
        if (isset($option['order']) && in_array($option['order'], ['id', 'create_time'])) {
            $order = 'log.' . $option['order'];
        }
        // 排序类型，默认 DESC
        $sort = 'DESC';
        if (isset($option['sort']) && in_array(strtoupper($option['sort']), ['ASC', 'DESC'])) {
            $sort = strtoupper($option['sort']);
        }

        return $query->order($order, $sort);
    }
}
