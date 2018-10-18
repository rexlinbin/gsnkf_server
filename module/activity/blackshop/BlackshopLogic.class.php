<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(pengnana@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/
class BlackshopLogic
{
	public static function getBlackshopInfo()
	{
		self::checkOpen();
		$uid = RPCContext::getInstance()->getUid();
		$blackshopObj = new BlackshopManage($uid);
		$blackshopInfo = $blackshopObj->getInfo();
		return $blackshopInfo;
	}
	public static function exchangeBlackshop($uid,$id,$num)
	{
		self::checkOpen();
		$blackshopObj = new BlackshopManage($uid);
		$valid = $blackshopObj->getValidId();
		if(!in_array($id,$valid))
		{
			throw new FakeException('blackshop id is wrong.valid id is %s,now is %d.',$valid,$id);//!!!
		}
		$blackshopObj->exchange($id,$num);
		// 通知狂欢活动
		EnFestivalAct::notify($uid, FestivalActDef::TASK_BLACK_SHOP_EXCHARGE_NUM, $num);
		return 'ok';
	}
	
	public static function checkOpen()
	{
		if ( FALSE == EnActivity::isOpen(ActivityName::BLACKSHOP) )
		{
			Logger::debug('Act BLACKSHOP is not open.');
			throw new FakeException('Act BLACKSHOP is not open.');
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
