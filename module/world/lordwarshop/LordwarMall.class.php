<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: LordwarMall.class.php 175355 2015-05-28 07:16:25Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/lordwarshop/LordwarMall.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-05-28 07:16:25 +0000 (Thu, 28 May 2015) $
 * @version $Revision: 175355 $
 * @brief 
 *  
 **/
class LordwarShop extends Mall implements ILordwarShop 
{
	public function __construct()
	{
		$uid = RPCContext::getInstance()->getUid();
	
		parent::__construct($uid, MallDef::MALL_TYPE_LORDWARSHOP,
				StatisticsDef::ST_FUNCKEY_MALL_LORDWARSHOP_COST,StatisticsDef::ST_FUNCKEY_MALL_LORDWARSHOP_GET);
	
		if (FALSE == EnActivity::isOpen(ActivityName::LORDWAR))
		{
			throw new FakeException('Lordwar is not open.');
		}
	
		$this->loadData();
	
		if (empty($this->dataModify))
		{
			$this->dataModify = array(
					MallDef::ALL => array(),
			);
		}
		$this->refreshData();
	}
	
	public function isInCurRound( $time )
	{
		$ret = EnActivity::getConfByName( ActivityName::LORDWAR );
		if( $time >= $ret['start_time'] && $time <= $ret['end_time'] )
		{
			return true;
		}
		return false;
	}
	
	public function getShopInfo()
	{
		$uid = RPCContext::getInstance()->getUid();
	
		$hasBuyInfo = $this->getInfo();
	
		foreach ($hasBuyInfo as $key => $value)
		{
			if ( isset($value['time']) )
			{
				unset($hasBuyInfo[$key]['time']);
			}
		}
	
		Logger::trace('LordwardShop get info.shopInfo:%s',$hasBuyInfo);
	
		return $hasBuyInfo;
	}
	
	public function buy($goodsId, $num = 1)
	{
		Logger::trace('LordwarShop Buy Start.');
	
		$goodsId = intval($goodsId);
		$num = intval($num);
	
		if ( $goodsId <= 0 || $num <= 0 )
		{
			throw new FakeException('param invaild. goodsId:%d, num:%d.',$goodsId,$num);
		}
	
		$ret = $this->exchange($goodsId, $num);
	
		Logger::trace('User buy %d,num:%d,ret:%s.',$goodsId,$num,$ret);
	
		return 'ok';
	}
	
	public function getExchangeConf($exchangeId)
	{
		$conf = EnActivity::getConfByName(ActivityName::LORDWAR);
	
		$exchangeConf = $conf['data'][1][LwShop::WM];
	
		if ( !isset( $exchangeConf[$exchangeId] ) )
		{
			throw new FakeException('GoodsId %d is not on sale.', $exchangeId);
		}
	
		return  $exchangeConf[$exchangeId];
	}
	
	public function subExtra($exchangeId, $num)
	{
		$uid = RPCContext::getInstance()->getUid();
		$exchangeConf = self::getExchangeConf($exchangeId);
		$wmPer = $exchangeConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA][LwShop::NEEDWM];
		$wmNeed = $wmPer * $num;
	
		$user = EnUser::getUserObj($uid);
		$wmHave = $user->getWmNum();
		if( $wmNeed > $wmHave )
		{
			Logger::warning( 'lack wm need: %s, have: %s',  $wmNeed, $wmHave );
			return FALSE;
		}
		
		$user->subWmNum($wmNeed);
		
		return TRUE;
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */