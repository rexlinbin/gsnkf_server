<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MineralElves.class.php 245724 2016-06-07 02:40:47Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/mineralelves/MineralElves.class.php $
 * @author $Author: QingYao $(yaoqing@babeltime.com)
 * @date $Date: 2016-06-07 02:40:47 +0000 (Tue, 07 Jun 2016) $
 * @version $Revision: 245724 $
 * @brief 
 *  
 **/


class MineralElves implements IMineralElves
{
	public function getSelfMineralElves()
	{
		Logger::trace('get self mineral elves start');
		if(!EnSwitch::isSwitchOpen(SwitchDef::MINERAL)
				||!EnActivity::isOpen(ActivityName::MINERALELVES ))
		{
			throw new FakeException('Mineral switch is not open or activity not open!');
		}
		Logger::trace('get self mineral elves end');
		return MineralElvesLogic::getSelfMineralElves(RPCContext::getInstance()->getUid());
	}
	public function getMineralElves()
	{
		Logger::trace('get mineral elves start');
		
		if(!EnSwitch::isSwitchOpen(SwitchDef::MINERAL)
				||!EnActivity::isOpen(ActivityName::MINERALELVES ))
		{
			throw new FakeException('Mineral switch is not open or activity not open!');
		}
		$returnArr=MineralElvesLogic::getMineralElves(Util::getTime());
		
		Logger::trace('get mineral elves end');
		
		return $returnArr;
	}
	
	public function getMineralElvesByDomainId($domain_id)
	{
		Logger::trace("get mineral elves by domain id:%d start",$domain_id);
		
		
		if(!EnSwitch::isSwitchOpen(SwitchDef::MINERAL)
				||!EnActivity::isOpen(ActivityName::MINERALELVES ))
		{
			throw new FakeException('Mineral switch is not open or activity not open!');
		}
		$returnArr=MineralElvesLogic::getMineralElvesByDomainId($domain_id);
		
		Logger::trace('setsession mineralelves %s.',$domain_id);
		RPCContext::getInstance()->setSession(MineralElvesDef::MINERAL_ELVES_SEESION, $domain_id);
		RPCContext::getInstance()->setSession(SPECIAL_ARENA_ID::SESSION_KEY, SPECIAL_ARENA_ID::MINERALELVES);
		
		Logger::trace('get mineral elves by domain id end');
		
		return $returnArr;
	}
	
	public function leave()
	{
		RPCContext::getInstance()->unsetSession(MineralElvesDef::MINERAL_ELVES_SEESION);
		RPCContext::getInstance()->unsetSession(SPECIAL_ARENA_ID::SESSION_KEY);
	}

	public function occupyMineralElves($domain_id)
	{
		Logger::trace('occupy mineral elves start');
		
		if(!EnSwitch::isSwitchOpen(SwitchDef::MINERAL)
				||!EnActivity::isOpen(ActivityName::MINERALELVES ))
		{
			throw new FakeException('Mineral switch is not open or activity not open!');
		}
		$returnArr=MineralElvesLogic::occupyMineralElves($domain_id, RPCContext::getInstance()->getUid());
		
		Logger::trace('occupy mineral elves stop');
		
		return $returnArr;
	}
	/**
	 * 对内提供，由cron和timer触发调用
	 * @param unknown $args
	 */
	public static function __genMineralElves($args)
	{
		Logger::trace('gen mineral elves start');
		MineralElvesLogic::__genMineralElves($args);
		Logger::trace('gen mineral elves end');
	}
    /**
     * 对内提供，timer触发调用
     */
	public static function __sendMineralElvesPrize($args)
	{
		Logger::trace('send mineral elves prize start');
		MineralElvesLogic::__sendMineralElvesPrize($args);
		Logger::trace('send mineral elves prize end');
	}
	
	public static function getMineralElvesConf()
	{
		Logger::trace('get mineral elves conf end');
		$returnArr= MineralElvesLogic::getMineralElvesConf();
		Logger::trace('get mineral elves conf end');
		return $returnArr;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */