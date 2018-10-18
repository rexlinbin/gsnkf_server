<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MergeServer.cfg.php 177999 2015-06-10 14:12:14Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/MergeServer.cfg.php $
 * @author $Author: BaoguoMeng $(jiangzhichao@babeltime.com)
 * @date $Date: 2015-06-10 14:12:14 +0000 (Wed, 10 Jun 2015) $
 * @version $Revision: 177999 $
 * @brief 
 *  
 **/

class MergeServerConf
{
	const MSERVER_PERCENT = 10000;				// 策划专用百分比计算常量
	
	const CONCAT_NAME = '.s';   //合服后名字连接字符，用于连接角色名和公会名等。 
	
	public static $MSERVER_DURING_DAYS = array
	(
			MergeServerDef::MSERVER_TYPE_LOGIN => array(
					'offset' => '000000',
					'days'   => 7 
				),    // 连续登陆活动
			MergeServerDef::MSERVER_TYPE_RECHARGE => array(
					'offset' => '000000',
					'days'   => 3,
				),    // 充值返回活动
			MergeServerDef::MSERVER_TYPE_EXP_GOLD => array(
					'offset' => '000000',
					'days'   => 3,
				),    // 经验宝物摇钱树
			MergeServerDef::MSERVER_TYPE_ARENA => array(
					'offset' => '000000',
					'days'   => 3,
				),   // 竞技场活动
			MergeServerDef::MSERVER_TYPE_MONTH_CARD => array(
					'offset' => '000000',
					'days'   => 30,
				),   // 月卡大礼包活动
			MergeServerDef::MSERVER_TYPE_COMPENSATION => array(
					'offset' => '000000',
					'days'   => 0,
				),	 // 合服补偿
			MergeServerDef::MSERVER_TYPE_MINERAL => array(
					'offset' => '000000',
					'days'   => 3,
				),    // 资源矿产出加成
	);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
