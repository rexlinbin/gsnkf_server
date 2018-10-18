<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildTask.def.php 116954 2014-06-24 08:16:49Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/GuildTask.def.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-06-24 08:16:49 +0000 (Tue, 24 Jun 2014) $
 * @version $Revision: 116954 $
 * @brief 
 *  
 **/
class GuildTaskDef
{
	const GUILD_TASK_SESSION = 'guildTask.info';
	
	const RESET_TIME = 'reset_time';
	const REF_NUM	= 'ref_num';
	const TASK_NUM = 'task_num';
	const FORGIVE_TIME = 'forgive_time';
	const VA_GUILDTASK = 'va_guildtask';
	
	
	const BTS_TASKID = 'id';
	const BTS_TYPE = 'type';
	const BTS_STAR = 'star';
	const BTS_WEIGHT = 'weight';
	const BTS_NEED_BUILDLV = 'needBuildLv';
	const BTS_FINISH_COND = 'finishCond';
	const BTS_REWARD = 'reward';
	const BTS_RIT_FINISH_GOLD = 'rightFinishGold';
	const BTS_NEED_CITY = 'needCity';
	const BTS_NEED_EXE = 'needExe';
	
	
	const BTS_LIMITID = 'id';
	const BTS_MAXNUM = 'maxNum';
	const BTS_FORGIVE_CD = 'forgiveCd';
	const BTS_REF_GOLD = 'refGold';
	const BTS_INCREF_GOLD = 'incRefGold';
	const BTS_REF_TASKARR = 'refTaskArr';
	const BTS_GUILD_LV = 'guildLv';
	const BTS_USER_LV = 'userLv';
	
	
	const NOT_ACCEPT = 0;
	const ACCEPT = 1;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */