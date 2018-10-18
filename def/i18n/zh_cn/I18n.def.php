<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: I18n.def.php 144018 2014-12-03 10:29:23Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-32-69/def/i18n/zh_cn/I18n.def.php $
 * @author $Author: BaoguoMeng $(wuqilin@babeltime.com)
 * @date $Date: 2014-12-03 18:29:23 +0800 (三, 2014-12-03) $
 * @version $Revision: 144018 $
 * @brief 
 *  
 **/
class I18nDef
{
	//军团默认宣言
	const GUILD_DEFAULT_SLOGAN = "欢迎大家来到放开那三国！";
	
	//军团默认公告
	const GUILD_DEFAULT_POST = "大家快来一起建设军团吧！";
	
	//比武机器人名字
	static $ARR_ROBOT_NAME = array(
			'李浩宇',
			'赵金隅',
			'钱皓轩',
			'孙擎宇',
			'周致远',
			'吴天佑',
			'郑英杰',
			'王骏驰',
			'萧亭轩',
			'慕容涛'
	);
	
	//军团抢粮战之抢夺方消息
	const GUILD_ROB_ATTACKER_MSG = "您的军团向[%s]军团发起了粮草抢夺战，请及时进入到抢夺战场做好抢粮准备！";
	
	//军团抢粮战之被抢方消息
	const GUILD_ROB_DEFENDER_MSG = "[%s]军团向您的军团发起了粮草抢夺战，请及时进入到抢夺战场做好战斗准备！";
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */