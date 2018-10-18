<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ICountryWarShop.class.php 214223 2015-12-07 05:59:13Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywarshop/ICountryWarShop.class.php $
 * @author $Author: JiexinLin $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-07 05:59:13 +0000 (Mon, 07 Dec 2015) $
 * @version $Revision: 214223 $
 * @brief 
 *  
 **/
interface  ICountryWarShop
{
	/**
	 * 获取商店信息
	 *
	 * @return
	 *
	 * <code>
	 *
	 * array
	 * {
	 * 		copoint:int 国战积分
	 * 		good_list:array
	 *      [
	 *          goodsId => canBuyNum:int    可购买数量
	 *      ]
	 * }
	 *
	 *</code>
	 *
	 */
	function getShopInfo();
	
	/**
	 * 购买商品
	 * @param int $goodsId, $num
	 * @return 'ok'
	*/
	function buy($goodsId, $num);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */