<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarShopLogic.class.php 214224 2015-12-07 05:59:37Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywarshop/CountryWarShopLogic.class.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2015-12-07 05:59:37 +0000 (Mon, 07 Dec 2015) $
 * @version $Revision: 214224 $
 * @brief 
 *  
 **/
class CountryWarShopLogic
{
	public static function getShopInfo($uid)
	{
		$countryWarShopObj = CountryWarShopManager::getInstance($uid);
		$goodsInfo = $countryWarShopObj->getInfo();
		$confDataOfGoods = btstore_get()->COUNTRY_WAR_SHOP;
		$goodListForFront = array();
		foreach ($confDataOfGoods as $goodsId => $data)
		{
			$hadBuyNum = 0;
			if (!empty($goodsInfo[$goodsId][CountryWarShopDef::MAX_BUY_NUM]))
			{
				$hadBuyNum = $goodsInfo[$goodsId][CountryWarShopDef::MAX_BUY_NUM];
			}
			$goodListForFront[$goodsId] = $data[CountryWarShopDef::REQ][CountryWarShopDef::MAX_BUY_NUM] - $hadBuyNum;
		}
		$ret = array();
		$serverId = Util::getServerId();
		$pid = EnUser::getUserObj($uid)->getPid();
		$ret[CountryWarShopDef::COPOINT] = CountryWarCrossUser::getInstance($serverId, $pid)->getCopointNum();
		$ret[CountryWarShopDef::GODLIST] = $goodListForFront;
		return $ret;
	}
	
	public static function buy($uid, $goodsId, $num)
	{
		$countryWarShopObj = CountryWarShopManager::getInstance($uid);
		$buyRet = $countryWarShopObj->exchange($goodsId, $num);
		return $buyRet['ret'];
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */