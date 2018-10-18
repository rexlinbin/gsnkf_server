<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChargeRaffle.def.php 114753 2014-06-16 12:28:21Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/ChargeRaffle.def.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-06-16 12:28:21 +0000 (Mon, 16 Jun 2014) $
 * @version $Revision: 114753 $
 * @brief 
 *  
 **/
class ChargeRaffleDef
{
    const TBLNAME = 't_charge_raffle';
    const TBLFIELD_UID = 'uid';
    const TBLFIELD_RAFFLENUM = 'raffle_num';
    const TBLFIELD_ACCUMNUM = 'accum_num';
    const TBLFIELD_ALLRAFFLENUM = 'all_raffle_num';
    const TBLFIELD_LASTRFRTIME = 'last_rfr_time';
    const TBLFIELD_REWARDTIME = 'fetch_reward_time';
    const TBLFIELD_VA_INFO = 'va_raffle_info';
    
    static $ALLFIELD = array(
            self::TBLFIELD_UID,
            self::TBLFIELD_LASTRFRTIME,
            self::TBLFIELD_REWARDTIME,
            self::TBLFIELD_VA_INFO,
            );
    
    const EXTRAFIELD_CANRAFFLENUM1 = 'can_raffle_num_1';
    const EXTRAFIELD_CANRAFFLENUM2 = 'can_raffle_num_2';
    const EXTRAFIELD_CANRAFFLENUM3 = 'can_raffle_num_3';
    const EXTRAFIELD_REWARD_STATUS = 'reward_status';
    
    const REWARDSTATUS_NOREWARD = 0;
    const REWARDSTATUS_HASREWARD = 1;
    const REWARDSTATUS_GETREWARD = 2;
    
    const REWARDUSER_ACTEND_DELAY = 10;//活动结束多长时间给玩家将每日充值奖励发到奖励中心
    
    const MIN_RAFFLE_CLASS = 1;
    const MAX_RAFFLE_CLASS = 3;
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */