<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id$$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL$$
 * @author $$Author$$(ShijieHan@babeltime.com)
 * @date $$Date$$
 * @version $$Revision$$
 * @brief 
 *  
 **/
class TopupRewardDef
{
    const UID = 'uid';
    const VA_DATA = 'va_data';
    const CANREC = 0;   //能否领奖
    const ISREC = 1;    //是否已领

    const TOPUP = 'topup';
    const LASTTIME = 'lasttime'; //最后一次参加活动时间

    const CANRECYES = 1; //能领
    const CANRECNO = 0; //不能领
    const ISRECYES = 1;  //已领
    const ISRECNO = 0;  //没领

    static $fields = array(
        self::UID,
        self::VA_DATA,
    );
    const TBL_TOPUP_REWARD = 't_topup_reward';
}

class ContinuePayCsv
{
    const ID = 'id';
    const OPENID = 'openId';
    const PAYNUM = 'payNum';
    const PAYREWARD = 'payReward';
    const ACTIVITYEXPLAIN = 'activityExplain';
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */