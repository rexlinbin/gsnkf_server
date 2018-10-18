<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Star.cfg.php 137958 2014-10-30 02:22:16Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Star.cfg.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-10-30 02:22:16 +0000 (Thu, 30 Oct 2014) $
 * @version $Revision: 137958 $
 * @brief 
 *  
 **/
class StarConf
{
	// 奖励类型
	const STAR_TYPE_SILVER = 1;						// 银两
	const STAR_TYPE_GOLD = 2;    					// 金币
	const STAR_TYPE_SOUL = 3;						// 将魂
	const STAR_TYPE_STAMINA = 4;					// 耐力
	const STAR_TYPE_EXECUTION = 5;					// 体力
	const STAR_TYPE_GOODWILL = 6;					// 好感度
	const STAR_TYPE_EXP = 7;						// 经验值
	const STAR_TYPE_STAMINA_LIMIT = 8;				// 耐力上限值
	
	//valid reward types
	public static $REWARD_VALID_TYPES	= 	array (
			self::STAR_TYPE_SILVER,
			self::STAR_TYPE_GOLD,
			self::STAR_TYPE_SOUL,
			self::STAR_TYPE_STAMINA,
			self::STAR_TYPE_EXECUTION,
			self::STAR_TYPE_GOODWILL,
			self::STAR_TYPE_EXP,
			self::STAR_TYPE_STAMINA_LIMIT,
	);
	
	// 触发答题事件的概率
	const STAR_TRIGER_PROBABILITY = 20;
	
	//一键赠送礼物上限
	const STAR_GIFT_LIMIT = 50;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */