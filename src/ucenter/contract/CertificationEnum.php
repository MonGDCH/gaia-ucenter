<?php

declare(strict_types=1);

namespace plugins\ucenter\contract;

/**
 * 实名认证相关枚举属性
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface CertificationEnum
{
    /**
     * 状态
     * 
     * @var array
     */
    const AUDIT_STATUS = [
        // 待审核
        'pending'   => 0,
        // 已通过
        'pass'      => 1,
        // 未通过
        'faild'     => 2,
    ];

    /**
     * 状态名称
     * 
     * @var array
     */
    const AUDIT_STATUS_TITLE = [
        // 待审核
        self::AUDIT_STATUS['pending']    => '待审核',
        // 已通过
        self::AUDIT_STATUS['pass']       => '已通过',
        // 未通过
        self::AUDIT_STATUS['faild']      => '未通过',
    ];

    /**
     * 认证类型
     * 
     * @var array
     */
    const AUDIT_TYPE = [
        // 个人认证
        'person'    => 0,
        // 企业认证
        'company'   => 1,
    ];

    /**
     * 认证类型名称
     * 
     * @var array
     */
    const AUDIT_TYPE_TITLE = [
        // 个人认证
        self::AUDIT_TYPE['person']  => '个人认证',
        // 企业认证
        self::AUDIT_TYPE['company'] => '企业认证',
    ];

    /**
     * 认证类型验证场景规则
     * 
     * @var array
     */
    const AUDIT_TYPE_SCOPE = [
        // 个人认证
        self::AUDIT_TYPE['person']  => 'person',
        // 企业认证
        self::AUDIT_TYPE['company'] => 'company',
    ];
}
