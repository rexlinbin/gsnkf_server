<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Chat.cfg.php 170445 2015-04-30 03:58:53Z ShiyuZhang $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Chat.cfg.php $
 * @author $Author: ShiyuZhang $(hoping@babeltime.com)
 * @date $Date: 2015-04-30 03:58:53 +0000 (Thu, 30 Apr 2015) $
 * @version $Revision: 170445 $
 * @brief
 *
 **/

class ChatConfig
{

	//最大聊天信息长度(不包含大喇叭，另：配置表中世界聊天的长度，与此应是一致的)
	const MAX_CHAT_LENGTH = 60;

	//聊天文本编码格式.
	const CHAT_ENCODING = FrameworkConfig::ENCODING;

	//世界广播消耗的金币
	const BROATCAST_GOLD = 5;

	//世界聊天需要的最低等级
	const WORLD_MIN_LEVEL = 15;
	
	//私聊需要的最低等级
	const PERSONAL_MIN_LEVEL = 15;
	
	//TODO
	//大喇叭需要的vip等级
	const HORN_VIP_LEVEL = 1;
	//大喇叭需要的用户等级
	const HORN_USER_LEVEL = 10;
	
	//世界频道配置ID
	const WORLD_CONFID = 1;
	//大喇叭配置ID
	const HORN_CONFID = 2;
	//私聊频道配置ID
	const PERSONAL_CONFID = 3;

	//使用银币发送信息
	const SEND_TYPE_SILVER = 1;
	
	const SEND_TYPE_GOLD = 1;
	//使用物品发送信息
	const SEND_TYPE_ITEM = 2;
	
	const SCREEN_TYPE_BOSS = 1;
	
	const SCREEN_TYPE_MINE = 2;
	
	const SCREEN_TYPE_ROBL = 3;
	
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */