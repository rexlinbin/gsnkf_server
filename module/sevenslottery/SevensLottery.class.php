<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SevensLottery.class.php 255093 2016-08-08 09:50:19Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/sevenslottery/SevensLottery.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-08-08 09:50:19 +0000 (Mon, 08 Aug 2016) $
 * @version $Revision: 255093 $
 * @brief 
 *  
 **/
class SevensLottery extends Mall implements ISevensLottery
{
	public function __construct()
	{
		$uid = RPCContext::getInstance()->getUid();
		if (EnSwitch::isSwitchOpen(SwitchDef::SEVENSLOTTERY) == false)
		{
			throw new FakeException('user:%d does not open the sevens lottery', $uid);
		}
		parent::__construct($uid, MallDef::MALL_TYPE_SEVENS_SHOP);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ISevensLottery::getSevensLotteryInfo()
	 */
	public function getSevensLotteryInfo()
	{
		return SevensLotteryLogic::getInfo($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ISevensLottery::lottery()
	 */
	public function lottery($type)
	{
		if (!in_array($type, array(0, 1, 2)))
		{
			throw new FakeException('Err para, type:%d', $type);
		}
		return SevensLotteryLogic::lottery($this->uid, $type);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ISevensLottery::getShopInfo()
	 */
	public function getShopInfo()
	{
		$info = $this->getInfo();
		$point = SevensLotteryObj::getInstance($this->uid)->getPoint();
		return array('goods' => $info, 'point' => $point);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ISevensLottery::buy()
	 */
	public function buy($goodsId, $num)
	{
		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('Err para, goodsId:%d num:%d', $goodsId, $num);
		}
		if (empty(btstore_get()->SEVENS_GOODS[$goodsId]))
		{
			throw new FakeException('The goods is not existed, goodsId:%d', $goodsId);
		}
		
		$this->exchange($goodsId, $num);
		SevensLotteryObj::getInstance($this->uid)->update();
		
		return 'ok';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::getExchangeConf()
	 */
	public function getExchangeConf($goodsId)
	{
		if (empty(btstore_get()->SEVENS_GOODS[$goodsId]))
		{
			Logger::warning('The goods is not existed, goodsId:%d', $goodsId);
			return array();
		}
	
		return btstore_get()->SEVENS_GOODS[$goodsId];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::subExtra()
	 */
	public function subExtra($goodsId, $num)
	{
		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('Err para, goodsId:%d num:%d!', $goodsId, $num);
		}
		if (empty(btstore_get()->SEVENS_GOODS[$goodsId]))
		{
			throw new FakeException('The goods is not existed, goodsId:%d', $goodsId);
		}
	
		$goodsConf = btstore_get()->SEVENS_GOODS[$goodsId];
		$subPoint = $goodsConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA] * $num;
		return SevensLotteryObj::getInstance($this->uid)->subPoint($subPoint);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */