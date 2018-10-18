<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Platform.cfg.php 81287 2013-12-17 06:26:58Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/card/rpcfw/conf/Platform.cfg.php $
 * @author $Author: wuqilin $(wuqilin@babeltime.com)
 * @date $Date: 2013-12-17 14:26:58 +0800 (二, 2013-12-17) $
 * @version $Revision: 81287 $
 * @brief 
 *  
 **/

/**
 * 所有和平台相关的配置都放在这里
 */
class PlatformConfig
{
	
	/** •平台名字
	•yueyu, appstore, android
	*/
	const PLAT_NAME = 'mix';


	/**
	 * 充值时，取哪个配置。（相关表：PAY_BACK，FIRSTPAY_REWARD）
	 * @var int
	 */
	const TOP_UP_CONFIG_INDEX	= 	1;


	const NS_START_TIME = '20140916235959';


	/**
	 * 新月卡更新时间
	 * @var int
	 */
	const NEW_MONTHLYCARD_TIME = '20150318140000';


	//军团第一次改版时间
	const NEW_GUILD_REFRESH_TIME = '2015-03-18 14:00:00';

	public static $CONTRYWAR_CROSS_GROUP = array('game40009001','game40009002');

	/**
	 * 新服活动("开服7天乐")更新时间
	 */
	const NEW_SERVER_ACTIVITY_TIME = '20170309000000';


	/**
	 * 大月卡更新时间
	 * @var int
	 */
	const NEW_MONTHLYCARD_TIME2 = '20161020140000';
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
