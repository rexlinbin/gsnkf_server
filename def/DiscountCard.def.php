<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DiscountCard.def.php 240227 2016-04-26 08:55:11Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/DiscountCard.def.php $
 * @author $Author: MingTian $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-04-26 08:55:11 +0000 (Tue, 26 Apr 2016) $
 * @version $Revision: 240227 $
 * @brief 
 *  
 **/
class DiscountCardDef
{
    const DISCOUNTCARD_TBLNAME = 't_discount_card';
    const TBL_SQLFIELD_UID = 'uid';
    const TBL_SQLFIELD_CARDID = 'card_id';
    const TBL_SQLFIELD_BUYTIME = 'buy_time';
    const TBL_SQLFIELD_DUETIME = 'due_time';
    const TBL_SQLFIELD_VAINFO = 'va_card_info';
    static $TBL_ALLSQLFIELD = array(
            self::TBL_SQLFIELD_UID,
            self::TBL_SQLFIELD_CARDID,
            self::TBL_SQLFIELD_BUYTIME,
            self::TBL_SQLFIELD_DUETIME,
            self::TBL_SQLFIELD_VAINFO 
            );
    
    const TBL_SQLFIELD_SUBVA_MONTH = 'monthly_card';
    const TBL_SQLFIELD_MONTH_REWARDTIME = 'reward_time';//领取每天奖励的时间
    const TBL_SQLFIELD_MONTH_GIFTSTATUS = 'gift_status';//大礼包状态
    
    
    const MONTHLYCATD_ID = 1;
    const MONTHLYCATD_ID2 = 2;
    
    static $VAILD_MONTHLYCATD_IDS = array(self::MONTHLYCATD_ID, self::MONTHLYCATD_ID2);
    
    static $MONTHLYCATD_TO_REWARD = array(
    		self::MONTHLYCATD_ID => RewardSource::MONTHLY_CARD,
			self::MONTHLYCATD_ID2 => RewardSource::MONTHLY_CARD2,
    );
    
    static $MONTHLYCATD_TO_COST = array(
    		self::MONTHLYCATD_ID => StatisticsDef::ST_FUNCKEY_BUYCARD_SPEND_GOLD,
    		self::MONTHLYCATD_ID2 => StatisticsDef::ST_FUNCKEY_BUYCARD_SPEND_GOLD2,
    );
    
    static $MONTHLYCATD_TO_STARTTIME = array(
    		self::MONTHLYCATD_ID => PlatformConfig::NEW_MONTHLYCARD_TIME,
    		self::MONTHLYCATD_ID2 => PlatformConfig::NEW_MONTHLYCARD_TIME2,
    );
    
    const NEW_GROUP_GIFT_DAY = 15;
    
}

class MONTHCARD_GIFTSTATUS
{
    const NOGIFT = 1;    //没有大礼包
    const HASGIFT = 2;    //有大礼包 没有领取
    const GOTGIFT = 3;    //已经领取了大礼包
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */