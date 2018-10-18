<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NormalsignLogic.class.php 141745 2014-11-24 09:31:08Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/sign/NormalsignLogic.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-11-24 09:31:08 +0000 (Mon, 24 Nov 2014) $
 * @version $Revision: 141745 $
 * @brief 
 *  
 **/

class NormalsignLogic 
{
	public static $arrFields =  array ( 
			'uid' ,
			'sign_time' ,
			'sign_num' ,
	 );

	public static function getNormalInfo( $uid  )
	{
		$ret = SignDao::getNormalInfo($uid, self::$arrFields );
		$maxDays = self::getMaxNormalDays();
		if ( empty( $ret ) )
		{
			$ret = self::initSignInfo( $uid );
		}
		//如果今天没有签到
		elseif ( !Util::isSameDay( $ret['sign_time'] ) )
		{
			//如果昨天也没有签到
			if ( !Util::isSameDay( $ret['sign_time'] + 86400 ) )
			{
				$ret['sign_num'] = 0;
				SignDao::updateNormal($uid, $ret);
			}
		}

		return $ret;
	}
	
	public static function initSignInfo( $uid )
	{
		$initValArr = array(	
				'uid'  			=> $uid ,
				'sign_time'		=> 0 ,
				'sign_num' 	=> 0 ,
				);
		
		SignDao::insertNormal( $uid, $initValArr );
		return $initValArr;
	}
	
	public static function getMaxNormalDays()
	{
		//连续签到的循环天数
		return count( btstore_get() -> SIGN_NORMAL [ 1 ] );
	}
	public static function getNormalSignReward( $uid , $days )
	{
		//已经领过了
		$signInfo = self::getNormalInfo( $uid );
		if ( Util::isSameDay( $signInfo[ 'sign_time' ] ))
		{
			throw new FakeException( 'gain days: %d already', $days );
		}
		//判定是不是该领这一天的
		$maxDays = self::getMaxNormalDays();
		if ( $signInfo[ 'sign_num' ] > $maxDays )
		{
			 throw new InterException( 'maxdays beyond' );
		}
		if ( $signInfo[ 'sign_num' ] == $maxDays && $days != $maxDays  )
		{
			throw new FakeException( 'front days: %d, backed days: %d', $days,  $signInfo[ 'sign_num' ]  );
		}
		if ($signInfo[ 'sign_num' ] < $maxDays && $days != $signInfo[ 'sign_num' ] +1 )
		{
			throw new FakeException( 'front days: %d, backed days: %d', $days,  $signInfo[ 'sign_num' ]  );
		}

		//已经冲到最后一天就别往上加天数了
		if ( $signInfo[ 'sign_num' ] < $maxDays )
		{
			$signInfo[ 'sign_num' ] ++;
		}
		//时间改一下主要用来看今天是不是已经领过奖励了和要不要重置签到的天数
		$signInfo['sign_time'] = Util::getTime();
		$arrReward = btstore_get()->SIGN_NORMAL[ 1 ][ $days ];
		$ret = RewardUtil::reward($uid, $arrReward, StatisticsDef::ST_FUNCKEY_SIGN_REWARD);
		
		SignDao::updateNormal( $uid , $signInfo );
		if ( $ret[ 'bagModify' ] )
		{
			BagManager::getInstance()->getBag( $uid )->update();
		}
		EnUser::getUserObj( $uid )->update();
		EnActive::addTask( ActiveDef::SIGN, 1 );
	}
	
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */