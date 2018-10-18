<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CityWar.cfg.php 113771 2014-06-12 07:57:14Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/CityWar.cfg.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-06-12 07:57:14 +0000 (Thu, 12 Jun 2014) $
 * @version $Revision: 113771 $
 * @brief 
 *  
 **/
class CityWarConf
{
	//一轮持续时间
	const ROUND_DURATION = 345600;
	//从一轮开始到报名的间隔时间
	const GAP_START_SIGNUP = 36000;
	//报名持续时间
	const SIGNUP_DURATION = 122400;
	//报名到战斗的间隔时间
	const GAP_SIGNUP_BATTLE = 87000;
	//准备持续时间
	const PREPARE_DURATION = 600;
	//每场小战斗的间隔时间
	const GAP_ATTACK_ATTACK = 900;
	//每场小战斗的持续时间
	const ATTACK_DURATION = 300;
	//整轮战斗的持续时间
	const BATTLE_DURATION = 1200;
	//小战斗的场数
	const ATTACK_OF_BATTLE = 2;
	//战斗到发奖的间隔时间
	const GAP_BATTLE_REWARD = 5400;
	//发奖持续时间
	const REWARD_DURATION = 129600;
	//check attack距离报名结束的偏移时间
	const CHECK_OFFSET = 30;
	
	//离线时间
	const OFFLINE_DURATION = 600;
	
	//破坏者人数上限
	const RUIN_LIMIT = 20;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */