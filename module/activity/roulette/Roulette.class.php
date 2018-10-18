<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Roulette.class.php 175626 2015-05-29 08:08:35Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/roulette/Roulette.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-05-29 08:08:35 +0000 (Fri, 29 May 2015) $
 * @version $Revision: 175626 $
 * @brief 
 *  
 **/
class Roulette implements IRoulette
{
	private $uid;
	
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
	}
	
	public function getMyRouletteInfo()
	{
		Logger::trace('Roulette::getMyRoulette Start.');
		
		$ret = RouletteLogic::getRouletteInfo($this->uid);
		
		Logger::trace('Roulette::getMyRoulette End.');
		
		return $ret;
	}
	
	public function rollRoulette($num)
	{
		Logger::trace('Roulette::rollRoulette Start.');
		
		$num = intval($num);
		
		$ret = RouletteLogic::rollRoulette($num, $this->uid);
		
		Logger::trace('Roulette::rollRoulette End.');
		
		return $ret;
	}
	
	public function receiveBoxReward($num)
	{
		Logger::trace('Roulette::receiveBoxReward Start.');
		
		$num = intval($num);
		
		$ret = RouletteLogic::receiveBoxReward($num, $this->uid);
		
		Logger::trace('Roulette::receiveBoxReward End.');
		
		return $ret;
	}
	
	public function getRankList()
	{
		Logger::trace('Roulette::getRankList Start.');
		
		$ret = RouletteLogic::getRankList($this->uid);
		
		Logger::trace('Roulette::getRankList End.');
		
		return $ret;
	}
	
// 	public function receiveRankReward()
// 	{
// 		Logger::trace('Roulette::receiveRankReward Start.');
		
// 		$ret = RouletteLogic::receiveRankReward($this->uid);
		
// 		Logger::trace('Roulette::receiveRankReward End.');
		
// 		return $ret;
// 	}
	
	public function checkRewardTimer()
	{
		RouletteLogic::checkRewardTimer();
	}
	
	public function rewardUserBfClose()
	{
		RPCContext::getInstance()->asyncExecuteTask('roulette.rewardUser', array());
	}
	
	public function rewardUser()
	{
		Logger::trace('roulette rewardUser start.');
		
		RouletteLogic::rewardUser();
		
		Logger::trace('roulette rewardUser end.');
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */