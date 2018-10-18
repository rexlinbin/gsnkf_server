<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Divine.class.php 257979 2016-08-23 10:36:58Z MingmingZhu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/divine/Divine.class.php $
 * @author $Author: MingmingZhu $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-08-23 10:36:58 +0000 (Tue, 23 Aug 2016) $
 * @version $Revision: 257979 $
 * @brief 
 *  
 **/
class Divine implements IDivine
{
	private $uid;
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
	}
	
	public function getDiviInfo()
	{
		Logger::trace( 'begin getDiviInfo' );
		$diviInfo = DivineLogic::getDiviInfo( $this->uid );
		if ( isset( $diviInfo[ 'refresh_time' ] ) )
		{
			unset( $diviInfo[ 'refresh_time' ] );
		}
		Logger::trace( 'end ' );
		return $diviInfo;
	}
	
	public function divi( $pos )
	{
		Logger::trace( 'begin divi' );
		DivineLogic::divine( $this->uid, $pos );
		$afterDiviInfo = DivineLogic::getDiviInfo($this->uid);
		EnAchieve::updateDivine($this->uid, $afterDiviInfo['integral']);

		EnActive::addTask( ActiveDef::DIVINE );
		EnWeal::addKaPoints( KaDef::DIVINE );
		EnMission::doMission($this->uid, MissionType::DIVINE);
		Logger::trace( 'end divi' );
	}
	
	public function refreshCurstar()
	{
		Logger::trace( 'begin refreshCurstar' );
		DivineLogic::refreshCurr( $this->uid );
		Logger::trace( 'end refreshCurr' );
	}
	
	public function drawPrize( $step )
	{
		Logger::trace( 'begin drawPrize' );
		$prizeArrConf = DivineLogic::drawPrize( $this->uid, $step );
		Logger::trace( 'end drawPrize' );
		return $prizeArrConf;
	}
	
	public function upgrade()
	{
		Logger::trace( 'begin upgrade' );
		DivineLogic::upgradePrize( $this->uid );
		Logger::trace( 'end upgrade' );
	}
	
	public function refPrize()
	{
		Logger::trace( 'begin refPrize' );
		$ret = DivineLogic::refPrize( $this->uid );
		Logger::trace( 'end refPrize' );
		return $ret;
	}
	
	public function drawPrizeAll()
	{
		Logger::trace( 'begin drawPrizeAll' );
		DivineLogic::drawPrizeStep($this->uid, true);
		Logger::trace( 'end drawPrizeAll' );
	}
	
	public function oneClickDivine()
	{
		Logger::trace( 'begin oneClickDivine' );
		DivineLogic::oneClickDivine($this->uid);
		Logger::trace( 'end oneClickDivine' );
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */