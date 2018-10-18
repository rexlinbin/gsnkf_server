<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IBarnShop.class.php 138964 2014-11-07 08:14:10Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/barnshop/IBarnShop.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-11-07 08:14:10 +0000 (Fri, 07 Nov 2014) $
 * @version $Revision: 138964 $
 * @brief 
 *  
 **/
interface IBarnShop
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
	 * 兑换商品
	 *
	 * @param int $goodsId				商品id
	 * @param int $num					数量
	 * @param string 'ok'
	*/
	public function buy($goodsId, $num);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */