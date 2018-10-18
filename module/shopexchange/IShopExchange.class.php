<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IShopExchange.class.php 65239 2013-09-18 02:37:03Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/shopexchange/IShopExchange.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-09-18 02:37:03 +0000 (Wed, 18 Sep 2013) $
 * @version $Revision: 65239 $
 * @brief 
 *  
 **/
interface IShopExchange
{
	/**
	 * 商店兑换武将碎片
	 *
	 * @param int $goodsId				商品id
	 * @param int $num					数量
	 * @param string 'ok'
	 */
	public function buy($goodsId, $num);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */