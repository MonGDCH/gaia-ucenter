<?php

declare(strict_types=1);

namespace gaia\admin\command;

use Throwable;
use mon\util\Sql;
use ErrorException;
use mon\env\Config;
use think\facade\Db;
use mon\thinkORM\Dao;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;
use plugins\admin\dao\MenuDao;
use plugins\admin\dao\RuleDao;

/**
 * 数据库初始化
 *
 * @author Mon <98555883@qq.com>
 * @version 1.0.0
 */
class InitCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'ucenter:init';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Initialization ucenter database';

    /**
     * 指令分组
     *
     * @var string
     */
    protected static $defaultGroup = 'Admin';

    /**
     * 菜单
     *
     * @var array
     */
    protected $menu = [
        ['name'  => 'ucenter', 'title' => '会员中心', 'icon'  => 'layui-icon layui-icon-group', 'chilid' => [
            ['name'  => 'userLog', 'title' => '用户日志', 'icon'  => 'layui-icon layui-icon-list', 'chilid' => [
                ['name'  => '/ucenter/log/login', 'title' => '登录日志', 'icon'  => 'layui-icon layui-icon-template-1'],
                ['name'  => '/ucenter/log/operate', 'title' => '操作日志', 'icon'  => 'layui-icon layui-icon-template-1'],
                ['name'  => '/ucenter/log/assets', 'title' => '资产流水', 'icon'  => 'layui-icon layui-icon-template-1']
            ]],
            ['name'  => '/ucenter/user', 'title' => '用户管理', 'icon'  => 'layui-icon layui-icon-user'],
            ['name'  => '/ucenter/signin', 'title' => '积分签到', 'icon'  => 'layui-icon layui-icon-face-smile-fine']
        ]],
    ];

    /**
     * 权限
     *
     * @var array
     */
    protected $rule = [
        ['name'  => 'ucenter', 'title' => '会员中心', 'chilid' => [
            ['name'  => 'user', 'title' => '用户管理', 'chilid' => [
                ['name' => '/ucenter/user', 'title' => '查看'],
                ['name' => '/ucenter/user/add', 'title' => '新增'],
                ['name' => '/ucenter/user/edit', 'title' => '编辑'],
                ['name' => '/ucenter/user/password', 'title' => '重置密码'],
                ['name' => '/ucenter/user/assets', 'title' => '资产管理'],
            ]],
            ['name'  => 'userLog', 'title' => '用户日志', 'chilid' => [
                ['name' => '/ucenter/log/login', 'title' => '登录日志'],
                ['name' => '/ucenter/log/operate', 'title' => '操作日志'],
                ['name' => '/ucenter/log/assets', 'title' => '资产流水'],
            ]],
            ['name'  => 'signin', 'title' => '积分签到', 'chilid' => [
                ['name' => '/ucenter/signin', 'title' => '查看'],
                ['name' => '/ucenter/signin/add', 'title' => '新增'],
                ['name' => '/ucenter/signin/config', 'title' => '配置'],
            ]]
        ]]
    ];

    /**
     * 执行指令
     *
     * @param  Input  $in  输入实例
     * @param  Output $out 输出实例
     * @return integer  exit状态码
     */
    public function execute(Input $in, Output $out)
    {
        // 读取sql文件
        $file = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'database.sql';
        $sqls = Sql::instance()->parseFile($file);
        // 执行sql
        Db::setConfig(Config::instance()->get('database', []));
        $out->block('Installation bootstrap');
        $out->spinBegiin();
        foreach ($sqls as $i => $sql) {
            Db::execute($sql);
            if ($i % 5 == 0) {
                $out->spin();
            }
        }

        $out->spin();

        $this->createMenu($this->menu, MenuDao::instance());
        $out->spin();
        $this->createRule($this->rule, RuleDao::instance());


        $out->spinEnd();
        $out->block('Installation done!', 'SUCCESS');
    }

    /**
     * 创建菜单
     *
     * @param array $list   菜单列表
     * @param Dao $dao      菜单Dao操作实例
     * @param integer $pid  父级ID
     * @return void
     */
    public function createMenu(array $list, Dao $dao, int $pid = 0)
    {
        $dao->startTrans();
        try {
            foreach ($list as $item) {
                // 判断是否存在后代
                $hasChild = isset($item['chilid']) && $item['chilid'] ? true : false;
                // 写入记录
                $data = [
                    'pid'   => $pid,
                    'name'  => $item['name'],
                    'title' => $item['title'],
                    'icon'  => $item['icon'],
                    'type'  => $hasChild ? '0' : '1',
                ];
                $menu_id = $dao->save($data, true, true);
                if (!$menu_id) {
                    $dao->rollback();
                    throw new ErrorException('新增菜单失败：' . $item['name']);
                }
                // 判断是否存在后代，存在则递归执行
                if ($hasChild) {
                    $this->createMenu($item['chilid'], $dao, $menu_id);
                }
            }

            $dao->commit();
            return;
        } catch (Throwable $e) {
            $dao->rollback();
            throw $e;
        }
    }

    /**
     * 创建权限
     *
     * @param array $list   权限列表
     * @param Dao $dao      权限Dao操作实例
     * @param integer $pid  父级ID
     * @return void
     */
    public function createRule(array $list, Dao $dao, int $pid = 0)
    {
        $dao->startTrans();
        try {
            foreach ($list as $item) {
                // 判断是否存在后代
                $hasChild = isset($item['chilid']) && $item['chilid'] ? true : false;
                // 写入记录
                $data = [
                    'pid'   => $pid,
                    'name'  => $item['name'],
                    'title' => $item['title'],
                ];
                $rule_id = $dao->save($data, true, true);
                if (!$rule_id) {
                    $dao->rollback();
                    throw new ErrorException('新增权限失败：' . $item['name']);
                }
                // 判断是否存在后代，存在则递归执行
                if ($hasChild) {
                    $this->createRule($item['chilid'], $dao, $rule_id);
                }
            }

            $dao->commit();
            return;
        } catch (Throwable $e) {
            $dao->rollback();
            throw $e;
        }
    }
}
