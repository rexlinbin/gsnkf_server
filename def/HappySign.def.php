<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HappySign.def.php 232027 2016-03-10 08:34:53Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/HappySign.def.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2016-03-10 08:34:53 +0000 (Thu, 10 Mar 2016) $
 * @version $Revision: 232027 $
 * @brief 
 *  
 **/
class HappySignDef
{
	// sql
	const UID = 'uid';
	const FIRST_LOGIN_TIME = 'sign_time';	// 当天第一次登陆时间即签到时间
	const LOGIN_NUM = 'login_num';
	const VA_REWARD = 'va_reward';	

	// 配置中的字段
	const TYPE = 'type';
	const REQ_DAY = 'requireDayNum';
	const UNSELECT_REWARD = 'unSelectRewardArr';
	const SELECT_REWARD = 'selectRewardArr';
	const COST = 'cost';	//补签时消耗的金币
	
	// 奖励类型; 1:不可选类型, 2:可选类型
	const SELECT_TYPE = 'selectType';
	const UNSELECT_TYPE = 'unSelectType';
	
	// 接口的返回字段
	const TODAY = 'today';
	const LOGIN_DAYS = 'loginDayNum';
	const HAD_REWARD = 'hadSignIdArr';
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */