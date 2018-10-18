<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IDragonShop.class.php 115725 2014-06-19 08:16:12Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/dragon/dragonshop/IDragonShop.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-06-19 08:16:12 +0000 (Thu, 19 Jun 2014) $
 * @version $Revision: 115725 $
 * @brief 
 *  
 **/
interface IDragonShop
{
	/**
	 * 获取商店信息
	 *
	 * @return array
	 * <code>
	 * {
	 * 		$goodsId					商品id
	 * 		{
	 * 			'num'					购买次数
	 * 			'time'					购买时间
	 * 		}
	 * }
	 * </code>
	 */
	public function getShopInfo();
	
	/**
	 * 商店兑换商品
	 *
	 * @param int $goodsId				商品id
	 * @param int $num					数量
	 * @param string 'ok'
	 */
	public function buy($goodsId, $num);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */