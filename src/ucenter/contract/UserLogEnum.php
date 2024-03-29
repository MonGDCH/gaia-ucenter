<?php

declare(strict_types=1);

namespace plugins\ucenter\contract;

/**
 * 用户日志相关枚举属性
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface UserLogEnum
{
    /**
     * 类型
     * 
     * @var array
     */
    const LOGIN_LOG_TYPE = [
        // 用户登出
        'logout'    => 0,
        // 用户登录
        'login'     => 1,
        // 密码错误
        'pwd_faild' => 2,
    ];

    /**
     * 类型名称
     * 
     * @var array
     */
    const LOGIN_LOG_TYPE_TITLE = [
        // 用户登出
        self::LOGIN_LOG_TYPE['logout']  => '用户登出',
        // 用户登录
        self::LOGIN_LOG_TYPE['login']   => '用户登录',
        // 密码错误
        self::LOGIN_LOG_TYPE['pwd_faild'] => '未通过',
    ];
}
