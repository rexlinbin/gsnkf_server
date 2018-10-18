<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IMergeServer.class.php 135595 2014-10-10 05:00:49Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mergeserver/IMergeServer.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2014-10-10 05:00:49 +0000 (Fri, 10 Oct 2014) $
 * @version $Revision: 135595 $
 * @brief 
 *  
 **/
 
/**********************************************************************************************************************
 * Class       : IMergeServer
 * Description : 合服活动外部接口类
 * Inherit     : 
 **********************************************************************************************************************/
interface IMergeServer
{
	/**
	 * 获取合服活动奖励信息
	 * 
	 * @return array
	 * <code>
	 * [
	 *		'login' => array
	 *      	'ret' => 'ok'       领取ok
	 *                   'over'     活动结束
	 *          'res' => array      奖励信息
	 * 			<code>
	 * 			[
	 * 				'login' => num	                    已经累积登陆的天数，没有返回0
	 *              'got' => array         已经领取的天数，没有返回空数组
	 * 				'can' => array         可以领取的天数，没有返回空数组
	 * 			]
	 *			</code>
	 *		'recharge' => array
	 *      	'ret' => 'ok'       领取ok
	 *                	 'over'     活动结束
	 *          'res' => array      奖励信息
	 * 			<code>
	 * 			[
	 * 				'recharge' => num	         累计充值的金币，没有返回0
	 *              'got' => array         已经领取的档位 ，没有返回空数组
	 *              'can' => array         可以领取的档位，没有返回空数组
	 * 			]
	 *			</code>
	 * ]
	 * </code>
	 * 
	 */
	public function getRewardInfo();
	
	/**
	 * 获取奖励-累积登陆
	 * 
	 * @param int $day 累积天数 取值  1,2,3,4,5,6,7...
	 * 
	 * @return string 'ok' 领取OK
	 * 
	 */
	public function receiveLoginReward($day);
	
	/**
	 * 获取奖励-累积充值
	 * 
	 * @param int $num 累积充值档位 1,2,3,4...
	 *
	 * @return string 'ok' 领取OK
	 *
	 */
	public function receiveRechargeReward($num);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */