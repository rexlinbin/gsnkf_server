<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Online.cfg.php 56184 2013-07-22 10:37:03Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Online.cfg.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-07-22 10:37:03 +0000 (Mon, 22 Jul 2013) $
 * @version $Revision: 56184 $
 * @brief 
 *  
 **/
class OnlineCfg
{
	//在线奖励领取的次数
	//const ONLINE_MAX_STEP = 6;
	
	//初始化用的数据
	const INI_STEP = 0;
	const INI_ENDTIME = 0;
	const INI_ACCTIME = 0;
	
	//重置用的数据
	const RESET_ENDTIME = 0;
	const RESET_ACCTIME = 0;
	
	//奖励类型
	const REWARD_ITEM = 1;
	const REWARD_SILVER = 2;
	const REWARD_GOLD = 3;
	const REWARD_EXECUTE = 4;
	const REWARD_STAMINA = 5;
	const REWARD_SOUL = 6;	
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */