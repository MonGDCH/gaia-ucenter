<?php

declare(strict_types=1);

namespace plugins\ucenter\contract;

/**
 * 用户相关枚举属性
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface UserEnum
{
    /**
     * 用户会员等级
     * 
     * @var array
     */
    const USER_LEVEL = [
        // 普通
        'norm'      => 0,
        // 白银
        'silver'    => 1,
        // 黄金
        'gold'      => 2,
        // 铂金
        'platinum'  => 3,
        // 钻石
        'diamond'   => 4,
    ];

    /**
     * 用户会员等级名称
     * 
     * @var array
     */
    const USER_LEVEL_TITLE = [
        // 普通
        self::USER_LEVEL['norm']        => '普通',
        // 白银
        self::USER_LEVEL['silver']      => '白银',
        // 黄金
        self::USER_LEVEL['gold']        => '黄金',
        // 铂金
        self::USER_LEVEL['platinum']    => '铂金',
        // 钻石
        self::USER_LEVEL['diamond']     => '钻石',
    ];

    /**
     * 用户状态
     * 
     * @var array
     */
    const USER_STATUS = [
        // 禁用
        'disable'       => 0,
        // 正常
        'enable'        => 1,
        // 待审核
        'audit'         => 2,
        // 审核失败
        'audit_fail'    => 3,
    ];

    /**
     * 用户状态名称
     * 
     * @var array
     */
    const USER_STATUS_TITLE = [
        // 禁用
        self::USER_STATUS['disable']    => '禁用',
        // 正常
        self::USER_STATUS['enable']     => '正常',
        // 待审核
        self::USER_STATUS['audit']      => '待审核',
        // 审核失败
        self::USER_STATUS['audit_fail'] => '审核失败',
    ];

    /**
     * 用户性别
     * 
     * @var array
     */
    const USER_SEX = [
        // 保密
        'secrecy'   => 0,
        // 男
        'male'      => 1,
        // 女
        'female'    => 2
    ];

    /**
     * 用户性别名称
     * 
     * @var array
     */
    const USER_SEX_TITLE = [
        // 保密
        self::USER_SEX['secrecy']   => '保密',
        // 男
        self::USER_SEX['male']      => '男',
        // 女
        self::USER_SEX['female']    => '女',
    ];

    /**
     * 用户地址状态
     * 
     * @var array
     */
    const USER_ADDRESS_STATUS = [
        // 禁用
        'disable'   => 0,
        // 正常
        'enable'    => 1
    ];

    /**
     * 用户地址状态名称
     * 
     * @var array
     */
    const USER_ADDRESS_STATUS_TITLE = [
        // 禁用
        self::USER_ADDRESS_STATUS['disable']    => '禁用',
        // 正常
        self::USER_ADDRESS_STATUS['enable']     => '正常'
    ];

    /**
     * 用户地址默认状态
     * 
     * @var array
     */
    const USER_ADDRESS_DEFAULT = [
        // 非默认地址
        'disable'   => 0,
        // 默认地址
        'enable'    => 1
    ];

    /**
     * 用户地址默认状态名称
     * 
     * @var array
     */
    const USER_ADDRESS_DEFAULT_TITLE = [
        // 禁用
        self::USER_ADDRESS_DEFAULT['disable']    => '非默认',
        // 正常
        self::USER_ADDRESS_DEFAULT['enable']     => '默认'
    ];

    /**
     * 用户登录方式
     * 
     * @var array
     */
    const LOGIN_TYPE = [
        // 手机号登录方式
        'mobile'    => 1,
        // 邮箱登录方式
        'email'     => 2,
    ];

    /**
     * 用户登录方式描述
     * 
     * @var array
     */
    const LOGIN_TYPE_TITLE = [
        // 手机号登录方式
        self::LOGIN_TYPE['mobile']    => '手机号登录',
        // 邮箱登录方式
        self::LOGIN_TYPE['email']     => '邮箱登录',
    ];
}
