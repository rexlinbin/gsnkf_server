<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Welcomeback.def.php 259700 2016-08-31 08:15:16Z YangJin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Welcomeback.def.php $
 * @author $Author: YangJin $(jinyang@babeltime.com)
 * @date $Date: 2016-08-31 08:15:16 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259700 $
 * @brief 老玩家回归
 *  
 **/
class WelcomebackDef
{
	//session
	const SESSION_OPEN_STATUS = 'welcomeback.openStatus';
	const SESSION_OPEN = 'yes';
	const SESSION_CLOSE = 'no';
	
	//sql
	const UID ='uid';
	const OFFLINE_TIME = 'offline_time';
	const BACK_TIME = 'back_time';
	const END_TIME = 'end_time';
	const NEED_BUFA = 'need_bufa';
	const VA_INFO = 'va_info';
	
	const VA_INFO_GIFT = 'gift';
	const VA_INFO_TASK = 'task';
	const VA_INFO_RECHARGE = 'recharge';
	const VA_INFO_RECHARGEUPDATETIME ='rechargeUpdateTime';
	const VA_INFO_SHOP = 'shop';
	
	//return_reward.csv
	const ID ='id';
	const TYPE = 'type';
	const LEVEL_LIMITS = 'levelLimits';//每个任务有不同的开启等级，比如打竞技场需要玩家等级达到80
	const TYPE_ID = 'typeId';//每种任务的类型，比如打竞技场的typeId=106
	const FINISH = 'finish';
	const REWARD ='reward';
	const DISCOUNT_ITEM = 'discountItem';
	const COST = 'cost';
	const BUY_TIMES = 'buyTimes';
	const GOLD = 'gold';
	const GOLD_REWARD = 'goldReward';
	const RECHARGE_TIMES = 'rechargeTimes';
	const ONE_FROM_N = 'choiceAward';//N选1奖励
	
	const TYPE_GIFT = '1';
	const TYPE_TASK = '2';
	const TYPE_RECHARGE = '3';
	const TYPE_SHOP = '4';
	
	const TASK_TYPE_NCOPY = '102';//成功攻打普通副本
	const TASK_TYPE_ECOPY = '103';//成功攻打精英副本
	const TASK_TYPE_DIVINE = '104';//进行占星
	const TASK_TYPE_FRAGSEIZE = '105';//进行夺宝
	const TASK_TYPE_ARENA = '106';//进行竞技场挑战
	const TASK_TYPE_MINERAL = '107';//占领或协助资源矿
	
	//return.csv
	const DURING = 'time';
	const LEVEL_LIMIT = 'levelLimit';
	const SERVER_LIMIT = 'serverLimit';
	const OFFLINE_LIMIT = 'offlineLimit';//礼包的倍率
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */