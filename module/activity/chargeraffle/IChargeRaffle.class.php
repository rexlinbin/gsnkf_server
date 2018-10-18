<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IChargeRaffle.class.php 116674 2014-06-23 11:00:27Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/chargeraffle/IChargeRaffle.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-06-23 11:00:27 +0000 (Mon, 23 Jun 2014) $
 * @version $Revision: 116674 $
 * @brief 
 *  
 **/
/**
 * 充值抽奖
 * @author dell
 * 1.活动期间内每天首冲有奖励
 * 2.活动期间内每天的抽奖次数没有用完会累积到明天，每天没有领取的首冲奖励发到奖励中心
 */
interface IChargeRaffle
{
    /**
     * 获取充值抽奖的信息
     * @return array
     * [
     *     can_raffle_num_1:int        第一档次可抽奖次数
     *     can_raffle_num_2:int        第二档次可抽奖次数
     *     can_raffle_num_3:int        第三档次可抽奖次数
     *     reward_status:int           领取首冲奖励的状态  0没有奖励  1有奖励没有领取  2已经领取了奖励
     * ]
     */
    public function getInfo();
    
    /**
     * 抽奖
     * @param int $index        档次
     * @return array
     * [
     *     7:array
     *     [
     *         id=>num
     *     ]
     *     14:array
     *     [
     *         id=>num
     *     ]
     * ]
     */
    public function raffle($index);
    
    /**
     * 获取每日首冲奖励
     * @return string 'ok'
     */
    public function getReward();
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */