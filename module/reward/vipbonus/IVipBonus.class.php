<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IVipBonus.class.php 237823 2016-04-12 09:28:41Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/vipbonus/IVipBonus.class.php $
 * @author $Author: MingTian $(hoping@babeltime.com)
 * @date $Date: 2016-04-12 09:28:41 +0000 (Tue, 12 Apr 2016) $
 * @version $Revision: 237823 $
 * @brief 
 *  
 **/

interface IVipBonus
{
	/**
	 * 获取vip福利信息
	 * 
	 * @return array
	 * <code>
	 * {
	 * 		'bonus':int			是否领取vip每日福利，1是0否
	 * 		'week_gift':array
	 * 		{
	 * 			$vip1, $vip2	已经购买的vip每周礼包
	 * 		}
	 * }
	 * </code>
	 */
	function getVipBonusInfo();
	
	/**
	 * 领取vip每日福利
	 * 
	 * @return string "ok"
	 */
	function fetchVipBonus();
	
	/**
	 * 购买vip每周礼包
	 * 
	 * @param int $vip 要购买的vip礼包
	 * @return string "ok"
	 */
	function buyWeekGift($vip);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */