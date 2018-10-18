<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: VipBonus.class.php 237823 2016-04-12 09:28:41Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/vipbonus/VipBonus.class.php $
 * @author $Author: MingTian $(hoping@babeltime.com)
 * @date $Date: 2016-04-12 09:28:41 +0000 (Tue, 12 Apr 2016) $
 * @version $Revision: 237823 $
 * @brief 
 *  
 **/

class VipBonus implements IVipBonus 
{
	private $uid;
	
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
	}
	
	/* (non-PHPdoc)
	 * @see IVipBonus::getVipBonusInfo()
	 */
	public function getVipBonusInfo() 
	{
		return VipBonusLogic::getVipBonusInfo($this->uid);
	}

	/* (non-PHPdoc)
	 * @see IVipBonus::fetchVipBonus()
	 */
	public function fetchVipBonus() 
	{
		return VipBonusLogic::fetchVipBonus($this->uid);
	}
	
	public function buyWeekGift($vip)
	{
		if($vip < UserConf::INIT_VIP || $vip > EnUser::getUserObj($this->uid)->getVip())
		{
			throw new FakeException("error param:[%d]", $vip);
		}
		return VipBonusLogic::buyWeekGift($this->uid, $vip);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */