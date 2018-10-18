<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarShop.class.php 214199 2015-12-07 03:40:32Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywarshop/CountryWarShop.class.php $
 * @author $Author: JiexinLin $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-07 03:40:32 +0000 (Mon, 07 Dec 2015) $
 * @version $Revision: 214199 $
 * @brief 
 *  
 **/
class CountryWarShop implements ICountryWarShop
{
	private $uid = 0;
	
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
	}
	public function getShopInfo() 
	{
		Logger::trace("CountryWarShop::getShopInfo start");
        $ret = CountryWarShopLogic::getShopInfo($this->uid);
        Logger::trace("CountryWarShop::getShopInfo end");
        return $ret;
	}

	public function buy($goodsId, $num) 
	{
		Logger::trace("CountryWarShop::buy start, goodsId:%d.", $goodsId);
        if (empty($goodsId))
        {
            throw new FakeException('goodsId is empty');
        }
        $ret = CountryWarShopLogic::buy($this->uid, $goodsId, $num);
        Logger::trace("CountryWarShop::buy end, goodsId:%d.", $goodsId);
        return $ret;
	}
}	
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */