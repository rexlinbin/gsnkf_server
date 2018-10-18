<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ILimitShop.php 145959 2014-12-13 09:57:08Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/limitshop/ILimitShop.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2014-12-13 09:57:08 +0000 (Sat, 13 Dec 2014) $
 * @version $Revision: 145959 $
 * @brief 
 *  
 **/
interface ILimitShop
{
	/**
	 * 获取限时商店的信息（每天0点调用一次，系统刷新）
	 * @return array
	 * 			[
	 * 				goodsId => num: int    已经购买的次数
	 * 			]
	 */
	public function getLimitShopInfo();
	
	/**
	 * 购买物品
	 * @param int $goodsId
	 * @param int $num
	 * @return string 'ok'
	 */
	public function buyGoods($goodsId, $num);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */