<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MonthSignLogic.class.php 141745 2014-11-24 09:31:08Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/sign/MonthSignLogic.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-11-24 09:31:08 +0000 (Mon, 24 Nov 2014) $
 * @version $Revision: 141745 $
 * @brief 
 *  
 **/
class MonthSignLogic
{
	static $sArrFields = array(
		'uid','sign_time','reward_vip','sign_num'
	);
	
	public static function getMonthSignInfo( $uid )
	{
	/* 	if ( !EnSwitch::isSwitchOpen( SwitchDef::SIGN ) )//TODO
		{
			return array();
		} */
		$ret = SignDao::getMonthSignInfo($uid, self::$sArrFields );
		if ( empty( $ret ) )
		{
			return self::initSignInfo( $uid );
			
		}
		
		//今天第一次登录
		//$confIdCur = self::getSignConfId(Util::getTime());
		///$confInfoCur = btstore_get()->SIGN_MONTH[$confIdCur];
		
		if( !Util::isSameMonth( $ret['sign_time'] ) )
		{
			$ret['reward_vip'] = SignDef::MONTH_VIP_UNUSED;
			$ret['sign_num'] = 0;
			$ret['sign_time'] = 0;
			//SignDao::updateMonthSign($uid, $ret);
		}		
			
		return $ret;
	}
	
	public static function initSignInfo( $uid )
	{
		$initValArr = array(	
				'uid'  			=> $uid ,
				'sign_time'		=> 0 ,
				'sign_num' 	 	=> 0 ,
				'reward_vip'	=> SignDef::MONTH_VIP_UNUSED ,
				);
		
		SignDao::insertMonthSign( $uid, $initValArr );
		return $initValArr;
	}
	
	public static function getSignConfId( $time )
	{
		$month = intval( date("n",$time));
		$monthPosition = $month - SignDef::MONTH_START;
		if( $monthPosition < 0 )
		{
			$monthPosition = $monthPosition + 12;
		}
		
		$arrConf = btstore_get()->SIGN_MONTH;
		$arrCyc = $arrConf['arrCyc'];
		
		$monthPosition = $monthPosition%count( $arrCyc );
		
		$confId = $arrCyc[$monthPosition];
		Logger::debug('month is: %d confId is: %d ', $month, $confId);
		
		return $confId;
	}
	
	public static function gainMonthSignReward( $uid )
	{
		$confId = self::getSignConfId(Util::getTime());
		$signInfo = self::getMonthSignInfo($uid);
		
		$day = $signInfo['sign_num'];
		if( !Util::isSameDay( $signInfo['sign_time'] ) )
		{
			$day++;
		}
		
		$conf = btstore_get()->SIGN_MONTH[$confId];
		if( !isset( $conf['arrDayPrize'][$day] ) )
		{
			throw new FakeException( 'no config for day: %d', $day );
		}
		
		$userObj = EnUser::getUserObj($uid);
		$userVip = $userObj->getVip();
		$reward = $conf['arrDayPrize'][$day];
		$ratio = 1;
		if( isset( $conf['arrDoublePrize'][$day] )  && $userVip >= $conf['arrDoublePrize'][$day] )
		{
			$ratio = 2;
		}
		
		if( Util::isSameDay( $signInfo['sign_time'] ) )
		{
			if( isset( $conf['arrDoublePrize'][$day] ) && $conf['arrDoublePrize'][$day] > $signInfo['reward_vip'] && $conf['arrDoublePrize'][$day] <= $userVip  )
			{
				$ratio = 2-1;
			}
			else 
			{
				throw new FakeException( 'invalid to receive again' );
			}
		}
		else 
		{
			$signInfo['sign_num'] ++;
		}
		$signInfo['reward_vip'] = $userVip;
		
		if( !is_int( $ratio ) )
		{
			throw new InterException( 'ratio not an int' );
		}
		
		$reward = $reward->toArray();
		$finalReward = array();
		for($i = 1; $i <= $ratio; $i++)
		{
			$finalReward = array_merge( $finalReward, $reward );
		}
		
		RewardUtil::reward3DArr($uid, $finalReward, StatisticsDef::ST_FUNCKEY_MONTH_SIGN_REWARD);
		
		$signInfo['sign_time'] = Util::getTime();
		SignDao::updateMonthSign($uid, $signInfo);
		BagManager::getInstance()->getBag($uid)->update();
		$userObj->update();
		
		EnActive::addTask(ActiveDef::MONTHSIGN, 1);
		Logger::debug('finish reward in month sign: %s', $finalReward);
		
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */