<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarCross.class.php 216772 2015-12-22 01:59:23Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/CountryWarCross.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-22 01:59:23 +0000 (Tue, 22 Dec 2015) $
 * @version $Revision: 216772 $
 * @brief 
 *  
 *  跨服接口入口，构造函数里做场景判定
 *  
 **/
class CountryWarCross implements ICountryWarCross
{
	
	function __construct()
	{
		if( !CountryWarUtil::isCrossScene() )
		{
			throw new FakeException( 'call cross method, not in cross scene' );
		}
	}
	/* (non-PHPdoc)
	 * @see ICountryWarCross::loginCross()
	 */
	public function loginCross($serverId, $pid, $token) 
	{
		$serverId = intval( $serverId );
		$pid = intval( $pid );
		return CountryWarLogic::loginCross($serverId, $pid, $token);
	}

	/* (non-PHPdoc)
	 * @see ICountryWarCross::enter()
	 */
	public function enter($countryId = NULL) 
	{
		
		return CountryWarLogic::enter($countryId);
	}

	/* (non-PHPdoc)
	 * @see ICountryWarCross::leave()
	 */
	public function leave() 
	{
		return CountryWarLogic::leave();
	}

	/* (non-PHPdoc)
	 * @see ICountryWarCross::getEnterInfo()
	 */
	public function getEnterInfo() 
	{
		$ret = CountryWarLogic::getEnterInfo();
		return $ret;
	}

	/* (non-PHPdoc)
	 * @see ICountryWarCross::joinTransfer()
	 */
	public function joinTransfer($transferId) 
	{
		
		if( $transferId < 0 )
		{
			throw new FakeException( 'invalid transferId:%s', $transferId );
		}
		$ret = CountryWarLogic::joinTransfer($transferId);
		return $ret;
	}

	/* (non-PHPdoc)
	 * @see ICountryWarCross::inspire()
	 */
	public function inspire() 
	{
		CountryWarLogic::inspire();
	}

	/* (non-PHPdoc)
	 * @see ICountryWarCross::clearJoinCd()
	 */
	public function clearJoinCd() 
	{
		return CountryWarLogic::clearJoinCd();
	}

	/* (non-PHPdoc)
	 * @see ICountryWarCross::recoverByUser()
	 */
	public function recoverByUser() 
	{
		return CountryWarLogic::recoverByUser();
	}

	/* (non-PHPdoc)
	 * @see ICountryWarCross::setRecoverPara()
	 */
	public function setRecoverPara($percent) 
	{
		return CountryWarLogic::setRecoverPara( $percent );
	}

	/* (non-PHPdoc)
	 * @see ICountryWarCross::turnAutoRecover()
	*/
	public function turnAutoRecover( $onOrOff ) 
	{
		if( $onOrOff != CountryWarConf::AUTO_RECOVER_ON && $onOrOff != CountryWarConf::AUTO_RECOVER_OFF )
		{
			throw new FakeException( 'invalid para:%s', $onOrOff );
		}
		return CountryWarLogic::turnAutoRecover($onOrOff);
	}
	
	
	/* (non-PHPdoc)
	 * @see ICountryWarCross::getRankList()
	*/
	public function getRankList() 
	{
		$ret = CountryWarLogic::getRankList();
		return $ret;
	}
	
	
	//======================================================================
	//========================转的请求及lcserver回调============================
	
	function doNotifySign( $teamId, $serverId, $pid,$countryId )
	{
		CountryWarLogic::doNotifySign($teamId, $serverId, $pid, $countryId);
	}
	/**
	 * 创建房间,创建时给场景初始化参数
	 */
	function doCheckAndCreateRoom( $teamId, $countryId )
	{
		CountryWarLogic::doCheckAndCreateRoom( $teamId, $countryId );
	}
	
	/**
	 * 链接断开后调用
	*/
	function onLogoff()
	{
		CountryWarLogic::onLogoff();
	}
	
	/**
	 * 一场战斗胜利
	*/
	function onFightWin($battleId, $attackerId, $winnerId, $loserId, $winStreak, $terminalStreak, $brid, $replayData, $fightEndTime, $isWinnerOut, $winnerHpArr = array(), $winnerUname, $loserUname)
	{
		return CountryWarLogic::onFightWin($battleId, $attackerId, $winnerId, $loserId, $winStreak, $terminalStreak, $brid, $replayData, $fightEndTime, $isWinnerOut, $winnerHpArr, $winnerUname, $loserUname);		
	}
	
	/**
	 * 一场战斗失败
	*/
	function onFightLose($uid, $fightEndTime)
	{
		CountryWarLogic::onFightLose($uid, $fightEndTime);
	}
	
	/**
	 * 达阵
	*/
	function onTouchDown( $battleId, $groupId, $attackerId,$time )
	{
		return CountryWarLogic::onTouchDown( $battleId,$groupId, $attackerId,$time );
	}
	
	/**
	 * 战场结束
	 * @param unknown $battleId
	*/
	function onBattleEnd($battleId, $robDuration, $attackerResource, $defenderResource)
	{
		CountryWarLogic::onBattleEnd($battleId, $robDuration, $attackerResource, $defenderResource);
	}



}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */