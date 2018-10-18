<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Boss.cfg.php 88434 2014-01-22 13:35:43Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Boss.cfg.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-01-22 13:35:43 +0000 (Wed, 22 Jan 2014) $
 * @version $Revision: 88434 $
 * @brief 
 *  
 **/
class BossConf
{
	const ATK_LIST_NUM = 10;
	const CD_TIME = 30;
	const INSPIRE_GOLD = 10;
	const MAX_INSPIRE_NUM = 10;
	const NIRVANA_GOLD = 10;
	const NIRVANA_INC_GOLD = 5;
	const BOSS_REWARD_SLEEP_TIME = 3;
	const SUB_CDTIME = 30;
	const BOSS_COMING_TIME = 300;
	const REVIVE_GOLD = 10;
	
	const INSPIRE_EXPERIENCE_RAND =10;
	const INSPIRE_EXPERIENCE_DEC_RAND = 5;
	const INSPIRE_EXPERIENCE_MAX_RAND = 10;
	const REVIVE_REQ_GOLD = 5;
	const INSPIRE_INC_PHYSICAL_ATTACK_PRECENT = 10;
	const BOSS_SEND_MAX_PROBABILITY = 10000;
	const BOSS_SEND_PROBABILITY = 3000;
	const BOSS_END_TIME_SHIFT = 10;
	const FREEZE_TIME = 15;
	
	public static $bossOffset = 0;
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */