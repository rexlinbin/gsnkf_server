<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: LordwarUtil.class.php 128822 2014-08-23 11:12:45Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/lordwar/LordwarUtil.class.php $
 * @author $Author: wuqilin $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-08-23 11:12:45 +0000 (Sat, 23 Aug 2014) $
 * @version $Revision: 128822 $
 * @brief 
 *  
 **/

class LordwarUtil
{		

	public static function getKey( $serverId,$pid )
	{
		return $serverId.'_'.$pid;
	}
	
	public static function getCrossDbName()
	{
		return LordwarDef::LDW_DB_PREFIX.PlatformConfig::PLAT_NAME;
	}
	
	public static function isMyServer( $serverId )
	{
		$group = RPCContext::getInstance ()->getFramework()->getGroup();
		//如果是在跨服机器上，所有的serverId都返回false
		if( empty($group)  )
		{
			return false;
		}
	
		$arrServerId = Util::getAllServerId();
		return in_array( $serverId, $arrServerId);
	}

	
	public static function getPreRound($round)
	{
		if( $round <= LordwarRound::OUT_RANGE )
		{
			return LordwarRound::OUT_RANGE;
		}
		return $round - 1;
	}
	
	public static function getNextRound($round)
	{
		if( $round >= LordwarRound::CROSS_2TO1 )
		{
			return LordwarRound::CROSS_2TO1;
		}
		return $round + 1;
	}
	
	public static function isInnerRound($round)
	{
		return $round <= LordwarRound::INNER_2TO1;
	}
	
	public static function isCrossRound($round)
	{
		return $round >= LordwarRound::CROSS_AUDITION;
	}
	
	public static function getPromotionSubroundNum($field)
	{
		if( $field == LordwarField::INNER )
		{
			return 5; //TODO 这个地方要改一下
		}
		if( $field == LordwarField::CROSS )
		{
			return 5;
		}
	}
	
	public static function isServerIn( $serverId, $sess )
	{
		$teamId = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess)->getTeamIdByServerId($serverId);
		if($teamId < 0)
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	public static function getWorshiNum($worshipTime)
	{
		if( Util::isSameDay( $worshipTime ) )
		{
			return 1;
		}
		else return 0;
	}
	
	public static function getPromotionRewardSource($field, $teamType, $rank)
	{
		$index = -1;
		if( $rank == 1 )
		{
			$index = 2;
		}
		else if( $rank == 2 )
		{
			$index = 1;
		}
		else
		{
			$index = 0;
		}
		
		if( !isset( LordwarConf::$PROMOTION_REWARD_SOURCE[$field][$teamType][$index] ) )
		{
			throw new InterException('not found reward source. field:%s, teamType:%d, rank:%d', $field, $teamType, $rank);
		}
		
		return LordwarConf::$PROMOTION_REWARD_SOURCE[$field][$teamType][$index];
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */