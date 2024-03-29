<?php

declare(strict_types=1);

namespace plugins\ucenter\contract;

/**
 * 用户签到相关枚举属性
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface UserSigninEnum
{
    /**
     * 配件键名
     * 
     * @var string
     */
    const CONFIG_KEY = 'ucenter_signin';

    /**
     * 每日奖励配置键名
     * 
     * @var string
     */
    const CONFIG_DAY_GIFT_KEY = 'day';

    /**
     * 每周奖励配置键名
     * 
     * @var string
     */
    const CONFIG_WEEK_GIFT_KEY = 'week';
}
