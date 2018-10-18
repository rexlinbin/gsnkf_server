<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FestivalLogic.class.php 175207 2015-05-28 02:39:18Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/festival/FestivalLogic.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-05-28 02:39:18 +0000 (Thu, 28 May 2015) $
 * @version $Revision: 175207 $
 * @brief 
 *  
 **/
class FestivalLogic
{
	public static function getFestivalInfo($uid)
	{
		self::checkComposeOpen();
		
		$festivalObj = FestivalManager::getInstance($uid);
		
		$festivalInfo = $festivalObj->getFestivalInfo();
		
		return $festivalInfo[FestivalDef::VA_DATA]['hasBuy'];
	}

	public static function compose($uid,$fNumber, $num = 1)
	{
		self::checkComposeOpen();
		
		$conf = EnActivity::getConfByName(ActivityName::FESTIVAL);
		$formulaInfo = $conf['data'][FestivalDef::FORMULA];
		
		$oneFormula = $formulaInfo[$fNumber-1];
		
		$req = $oneFormula['req'];
		$acq = $oneFormula['acq'];
		$maxNum = $oneFormula['maxNum'];
		
		$uid = RPCContext::getInstance()->getUid();
		$festivalObj = FestivalManager::getInstance($uid);
		
		$hasBuyNum = $festivalObj->getHasBuyInfo($fNumber);
		
		if ( $hasBuyNum + $num > $maxNum )
		{
			throw new FakeException('No enough num. hasBuyNum: %d , num: %d, maxNum %d.',$hasBuyNum,$num,$maxNum);
		}
		
		$bag = BagManager::getInstance()->getBag();
		
		$needs = array();
		foreach ( $req as $oneReq )
		{
			$needs[$oneReq[0]] = $oneReq[1] * $num;
		}
		//删的装备有强化等(此问题由策划保证没有武将、装备等可以强化和进阶的东西，所以就不传id了)
		if ( FALSE == $bag->deleteItemsByTemplateID($needs) )
		{
			throw new FakeException('No enough items,delete failed. need: %s.',$needs);
		}
		
		$festivalObj->addTimes($fNumber,$num);
		
		$gives = array();
		foreach ( $acq as $oneAcq )
		{
			$gives[$oneAcq[0]] = $oneAcq[1] * $num;
		}
		
		if ( FALSE == $bag->addItemsByTemplateID($gives) )
		{
			Logger::info('Act festival add item, but bag full. Gives: %s.',$gives);
			throw new FakeException('Bag Full.');
		}
		
		$festivalObj->update();
		$bag->update();
		
		return 'ok';
	}

	public static function getFormulaNum()
	{
		$conf = EnActivity::getConfByName(ActivityName::FESTIVAL);
		$arrFormula = $conf['data'][FestivalDef::FORMULA];

		$num = count($arrFormula);

		return $arrFormula;
	}
	
	public static function getActStartTime()
	{
		$conf = EnActivity::getConfByName(ActivityName::FESTIVAL);
		return $conf['start_time'];
	}
	
	public static function getActEndTime()
	{
		$conf = EnActivity::getConfByName(ActivityName::FESTIVAL);
		return $conf['end_time'];
	}
	
	public static function checkComposeOpen()
	{
		if ( FALSE == EnActivity::isOpen(ActivityName::FESTIVAL) )
		{
			throw new FakeException('Act festival is not open.');
		}
		
		$conf = EnActivity::getConfByName(ActivityName::FESTIVAL);
		
		if ( FestivalDef::ACT_TYPE_COMPOSE != $conf['data'][FestivalDef::ACT_TYPE] )
		{
			throw new FakeException('Act drop open, not compose.');
		}
	}
	
	public static function isDropTime()
	{
		$startTime = self::getActStartTime();
		$endTime = self::getActEndTime();
		
		$conf = EnActivity::getConfByName(ActivityName::FESTIVAL);
		
		$dropEndTime = $endTime;
		if ( FestivalDef::ACT_TYPE_COMPOSE == $conf['data'][FestivalDef::ACT_TYPE] )
		{
			$dropEndTime = $endTime - SECONDS_OF_DAY;
		}
				
		$now = Util::getTime();
		
		if ($now >= $startTime && $now <= $dropEndTime)
		{
			return true;
		}
		
		return false;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */