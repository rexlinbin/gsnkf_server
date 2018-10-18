<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Sign.cfg.php 78322 2013-12-02 12:22:20Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Sign.cfg.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-12-02 12:22:20 +0000 (Mon, 02 Dec 2013) $
 * @version $Revision: 78322 $
 * @brief 
 *  
 **/
class SignCfg
{
	const INI_NORMAL_LEVEL = 1;						//初始连续签到奖励表等级TODO

	
	//奖励类型
	const REWARD_ITEM = 1;
	const REWARD_SILVER = 2;
	const REWARD_GOLD = 3;
	const REWARD_STAMINA = 5;
	const REWARD_EXECUTE = 4;
	const REWARD_SOUL = 6;

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */