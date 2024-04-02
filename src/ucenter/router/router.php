<?php
/*
|--------------------------------------------------------------------------
| 定义应用请求路由
|--------------------------------------------------------------------------
| 通过Route类进行注册
|
*/

use mon\env\Config;
use mon\http\Route;
use plugins\admin\middleware\AuthMiddleware;
use plugins\admin\middleware\LoginMiddleware;
use plugins\ucenter\controller\LogController;
use plugins\ucenter\controller\UserController;
use plugins\ucenter\controller\SigninController;
use plugins\ucenter\controller\AddressController;

/** @var Route $route */

Route::instance()->group(Config::instance()->get('admin.app.root_path', ''), function (Route $route) {
    // 需要登录
    $route->group(['path' => '/ucenter', 'middleware' => LoginMiddleware::class], function (Route $route) {
        // 获取用户
        $route->get('/getUser', [UserController::class, 'getUser']);

        // 权限接口
        $route->group(['middleware' => AuthMiddleware::class], function (Route $route) {
            // 用户管理
            $route->group('/user', function (Route $route) {
                // 查看
                $route->get('', [UserController::class, 'index']);
                // 新增
                $route->map(['GET', 'POST'], '/add', [UserController::class, 'add']);
                // 编辑
                $route->map(['GET', 'POST'], '/edit', [UserController::class, 'edit']);
                // 重置密码
                $route->map(['get', 'post'], '/password', [UserController::class, 'password']);
                // 资产管理
                $route->map(['GET', 'POST'], '/assets', [UserController::class, 'assets']);
                // 实名认证
                $route->map(['GET', 'POST'], '/certification', [UserController::class, 'certification']);
                // 审核实名认证
                $route->post('/certification/check', [UserController::class, 'checkCertification']);

                // 用户地址
                $route->group('/address', function (Route $route) {
                    // 查看
                    $route->get('', [AddressController::class, 'index']);
                    // 新增
                    $route->map(['get', 'post'], '/add', [AddressController::class, 'add']);
                    // 编辑
                    $route->map(['get', 'post'], '/edit', [AddressController::class, 'edit']);
                    // 修改状态
                    $route->post('/toggle', [AddressController::class, 'status']);
                });
            });

            // 日志
            $route->group('/log', function (Route $route) {
                // 登录日志
                $route->get('/login', [LogController::class, 'login']);
                // 操作日志
                $route->get('/operate', [LogController::class, 'operate']);
                // 资产流失
                $route->get('/assets', [LogController::class, 'assets']);
            });

            // 积分签到
            $route->group('/signin', function (Route $route) {
                // 查看
                $route->get('', [SigninController::class, 'index']);
                // 新增
                $route->map(['GET', 'POST'], '/add', [SigninController::class, 'add']);
                // 签到配置
                $route->map(['GET', 'POST'], '/config', [SigninController::class, 'config']);
            });
        });
    });
});
