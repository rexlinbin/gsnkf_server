<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: OneRecharge.def.php 251176 2016-07-12 07:06:22Z YangJin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/OneRecharge.def.php $
 * @author $Author: YangJin $(linjiexin@babeltime.com)
 * @date $Date: 2016-07-12 07:06:22 +0000 (Tue, 12 Jul 2016) $
 * @version $Revision: 251176 $
 * @brief
 *
 **/
class OneRechargeDef
{
	const VA_REWARD = 'va_reward';
	const REMAIN = 1;
	const NOT_REMAIN = 0;
	const MAX_FETCH = 100;

	// 接口
	const TO_REWARD = 'toReward';
	const HAD_REWARD = 'hadReward';

	// csv
	const REQ = 'req_recharge';
	const REWARD = 'reward';
	const DAY_NUM = 'day_num';

	//new added csv
    const ONE_FROM_N = 'type';
	const ONE_FROM_N_NO = '1';
    const ONE_FROM_N_YES ='2';

	// sql
	const UID = 'uid';
	const VA_INFO = 'va_info';
	const IF_REMAIN = 'if_remain';
	const REFRESH_TIME = 'refresh_time';

	//timer
	const ONE_RECHARGE_REWARD_CENTER_TASK_NAME = 'onerecharge.rewardToCenter';
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */