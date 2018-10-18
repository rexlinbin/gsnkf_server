<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RechargeGift.def.php 207375 2015-11-05 02:39:35Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/RechargeGift.def.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2015-11-05 02:39:35 +0000 (Thu, 05 Nov 2015) $
 * @version $Revision: 207375 $
 * @brief 
 *  
 **/
class RechargeGiftDef
{
	// sql
	const UID = 'uid';
	const UPDATE_TIME = 'update_time';
	const VA_REWARD = 'va_reward';
	
	// 配置中的字段
	const TYPE = 'type';
	const REQ_GOLD = 'expenseGold';
	const UNSELECT_REWARD = 'unSelectRewardArr';
	const SELECT_REWARD = 'selectRewardArr';
	
	// 接口的返回字段
	const ACC_GOLD = 'acc_gold';
	const HAD_REWARD = 'hadRewardArr';
	
	// 奖励类型
	const SELECT_TYPE = 'selectType';
	const UNSELECT_TYPE = 'unSelectType';
	
	public static $allColumns = array(
			self::UID,
			self::UPDATE_TIME,
			self::VA_REWARD,
	);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */