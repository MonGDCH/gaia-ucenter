<?php

declare(strict_types=1);

namespace plugins\ucenter\contract;

/**
 * 资产相关枚举属性
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface AssetsEnum
{
    /**
     * 资产类型
     * 
     * @var array
     */
    const ASSETS_CATE = [
        // 积分
        'score'     => 0,
        // 余额
        'amount'    => 1,
    ];

    /**
     * 资产类型名称
     * 
     * @var array
     */
    const ASSETS_CATE_TITLE = [
        // 积分
        self::ASSETS_CATE['score']      => '积分',
        // 余额
        self::ASSETS_CATE['amount']     => '余额',
    ];

    /**
     * 资产类型字段名
     * 
     * @var array
     */
    const ASSETS_CATE_FIELD = [
        // 积分
        self::ASSETS_CATE['score']      => 'score',
        // 余额
        self::ASSETS_CATE['amount']     => 'amount',
    ];

    /**
     * 资产操作类型
     * 
     * @var array
     */
    const ASSETS_OPER_TYPE = [
        // 充值
        'charge'    => 1,
        // 扣减
        'deduction' => 2,
        // 转换
        'shift'     => 3,
        // 转账
        'transfer'  => 4,
        // 签到
        'signin'    => 5,
    ];

    /**
     * 资产操作类型名称
     * 
     * @var array
     */
    const ASSETS_OPER_TYPE_TITLE = [
        // 充值
        self::ASSETS_OPER_TYPE['charge']    => '充值',
        // 扣减
        self::ASSETS_OPER_TYPE['deduction'] => '扣减',
        // 转换
        self::ASSETS_OPER_TYPE['shift']     => '转换',
        // 转账
        self::ASSETS_OPER_TYPE['transfer']  => '转账',
        // 签到
        self::ASSETS_OPER_TYPE['signin']    => '签到',
    ];

    /**
     * 资产属性
     * 
     * @var array
     */
    const ASSETS_ATTR = [
        // 不可用
        'unusable'  => 0,
        // 可用
        'usable'    => 1,
    ];

    /**
     * 资产属性名称
     * 
     * @var array
     */
    const ASSETS_ATTR_TITLE = [
        // 不可用
        self::ASSETS_ATTR['unusable']   => '冻结',
        // 可用
        self::ASSETS_ATTR['usable']     => '可用',
    ];

    /**
     * 日志类型
     * 
     * @var array
     */
    const ASSETS_LOG_TYPE = [
        // 用户充值
        'user_recharge'     => 0,
        // 系统充值
        'sys_recharge'      => 1,
        // 系统扣减
        'sys_deduct'        => 2,
        // 用户转入
        'user_transfer_in'  => 3,
        // 用户转出
        'user_transfer_out' => 4,
        // 用户资产兑换
        'user_exchange'     => 5,
        // 用户签到
        'user_signin'       => 6,
    ];

    /**
     * 资产类型名称
     * 
     * @var array
     */
    const ASSETS_LOG_TYPE_TITLE = [
        // 用户充值
        self::ASSETS_LOG_TYPE['user_recharge']      => '用户充值',
        // 系统充值
        self::ASSETS_LOG_TYPE['sys_recharge']       => '系统充值',
        // 系统扣减
        self::ASSETS_LOG_TYPE['sys_deduct']         => '系统扣减',
        // 用户转入
        self::ASSETS_LOG_TYPE['user_transfer_in']   => '用户转入',
        // 用户转出
        self::ASSETS_LOG_TYPE['user_transfer_out']  => '用户转出',
        // 用户资产兑换
        self::ASSETS_LOG_TYPE['user_exchange']      => '用户资产兑换',
        // 用户签到
        self::ASSETS_LOG_TYPE['user_signin']        => '用户签到',
    ];
}
