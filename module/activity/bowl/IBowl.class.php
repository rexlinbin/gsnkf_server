<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IBowl.class.php 152191 2015-01-13 10:02:48Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/bowl/IBowl.class.php $
 * @author $Author: GuohaoZheng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-01-13 10:02:48 +0000 (Tue, 13 Jan 2015) $
 * @version $Revision: 152191 $
 * @brief 
 *  
 **/
 
interface IBowl
{
	/**
	 * 获取用户的聚宝盆信息（是否需要充值信息）
	 *
	 *@throws
	 *
	 *@return array
	 *[
	 *		charge => int       活动期间已充值多少金币
	 *		type =>array		聚宝盆类型=>聚宝盆信息
	 *		[
	 *			state			聚宝盆当前状态	1  不可购买 2 可购买  3 已购买 （如果返回充值数据的话 这个也不需要）
	 *			reward			只有在state为3的情况下有效，否则为空array
	 *			[
	 *				day => int	0 ：不可领取 1：可领 2： 已经领取 
	 *			]
	 *		]
	 *]
	 */
	public function getBowlInfo();
	
	/**
	 * 购买聚宝盆
	 * 
	 *@param int $type 			宝箱类型 
	 *
	 *@throws
	 *
	 *@return string
	 *		  ok				购买ok
	 */
	public function buy($type);
	
	/**
	 * 领取聚宝盆奖励
	 *
	 *@param int $type 			宝箱类型
	 *@param int $day  	 		领取的天数
	 *
	 *@throws
	 *
	 *@return string
	 *		  ok				领取ok
	 */
	public function receive($type, $day);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */