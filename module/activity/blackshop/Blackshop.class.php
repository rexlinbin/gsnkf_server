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
class Blackshop implements IBlackshop
{
	private $uid = 0;
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
	}
	/*获取玩家已兑换次数
	 * @return array   [id] => [num]
	* */
	public function getBlackshopInfo()
	{
		Logger::trace('Blackshop::getBlackshopInfo Start.');
		
		$ret = BlackshopLogic::getBlackshopInfo();
			
		Logger::trace('Blackshop::getBlackshopInfo End.');
		
		return $ret;
	}
	
	/*兑换物品
	 * @param  id  int 兑换的配置id
	*          num int  兑换个数
	* @return   'ok'
	* */
	public function exchangeBlackshop($id,$num = 1)
	{
		Logger::trace('Blackshop::exchangeBlackshop Start.');
		
		$ret = BlackshopLogic::exchangeBlackshop($this->uid,$id,$num);
		
		Logger::trace('Blackshop::exchangeBlackshop End.');
		
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */