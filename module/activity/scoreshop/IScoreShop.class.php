<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IScoreShop.class.php 159842 2015-03-03 07:20:23Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/scoreshop/IScoreShop.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-03-03 07:20:23 +0000 (Tue, 03 Mar 2015) $
 * @version $Revision: 159842 $
 * @brief 
 *  
 **/
interface IScoreShop
{
	/**
	 * 获得商店信息
	 * @return array
	 * 			[
	 * 				point  : int 剩余积分
	 * 				hasBuy : array
	 * 						[
	 * 							goodsId => num  已购买次数
	 * 						]
	 * 			]
	 */
	public function getShopInfo();
	
	/**
	 * 购买物品
	 * @param int $goodsId   商品id（商品id从1开始）
	 * @param int $num       购买数量（默认为1）
	 * @return 'ok'
	 */
	public function buy($goodsId, $num);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */