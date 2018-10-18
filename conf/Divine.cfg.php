<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Divine.cfg.php 257981 2016-08-23 10:39:57Z MingmingZhu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Divine.cfg.php $
 * @author $Author: MingmingZhu $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-08-23 10:39:57 +0000 (Tue, 23 Aug 2016) $
 * @version $Revision: 257981 $
 * @brief 
 *  
 **/
class DivineCfg
{
	const MAX_DIVI_TIMES = 16;			//最大每日占星次数
	
	const INI_DIVI_TIMES = 0;			//初始已经占星的次数
	const INI_FREE_REFRESH_NUM = 1;		//初始免费次数
	const INI_PRIZE_STEP =0;			//奖励领取次数
	const INI_TARGET_FINISH_NUM = 0;	//初始怒表星座完成次数
	const INI_INTEGRAL = 0;
	const INI_PRIZE_LEVEL = 1;
	
	const DIVI_TIME_OFFSET = 0; 	//十小时的秒数,后改为0点刷新
	
	const RESET_DIVI_TIMES = 0;     	//重置占星次数
	const RESET_INTEGRAL = 0;			//重置我的星数
	const RESET_PRIZE_STEP = 0;			//重置奖励领取进度
	const RESET_FREE_REFNUM	= 1;		//重置免费次数
	const RESET_FINISH_NUM = 0;			//重置目标星座完成次数
	
	const TARGET_STARS_NUM = 4; 		//目标星座的星数
	const CURRENT_STARS_NUM = 5;		//占星星座的星数
	
	const REFRESH_COST = 10;				//刷新当前占星星座的金币花费
	
	const ONECLICK_ITEM_COST = 20;			//一键占星所需占星令消耗
	const ONECLICK_GOLD_UNIT_COST = 10;		//一键占星占星令不足时，补足占星令的金币单价（1占星令 = 10金币）
	
	//奖励类型
	const REWARD_SILVER = 0;
	const REWARD_GOLD = 1;
	const REWARD_SOUL = 2;
	const REWARD_ITEM = 3;

	//奖励物品的数量
	const REWARD_NUM = 1;
	
	const DIVINE_GUIDE = true;
	
	public static $fakePosArr = array(
			1,3,2,0
	);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */