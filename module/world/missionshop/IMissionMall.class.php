<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IMissionMall.class.php 196814 2015-09-07 03:36:50Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/missionshop/IMissionMall.class.php $
 * @author $Author: ShijieHan $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-09-07 03:36:50 +0000 (Mon, 07 Sep 2015) $
 * @version $Revision: 196814 $
 * @brief 
 *  
 **/
interface IMissionMall
{
	/**
	 * 商店商品信息
	 * array
	 * {
	 * 		good_list:array
	 *      [
	 *          goodId => canBuyNum:int    可购买数量
	 *      ]
	 * }
	 *
	 */
	function getShopInfo();
	
	/**
	 * 购买
	 * @param int $goodId 商品id
	 * @param int $num 购买数量
	 * @return "ok"
	 */
	function buy( $goodId, $num );
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */