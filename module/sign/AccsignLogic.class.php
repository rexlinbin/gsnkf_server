<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AccsignLogic.class.php 150687 2015-01-07 07:42:13Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/sign/AccsignLogic.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-01-07 07:42:13 +0000 (Wed, 07 Jan 2015) $
 * @version $Revision: 150687 $
 * @brief 
 *  
 **/

class AccsignLogic
{
	public static $arrFields =  array ( 
			'uid' ,
			'sign_time' ,
			'sign_num' ,
			'va_sign',
	 );

	public static function getSignInfo( $uid  )
	{
		$ret = SignDao::getSingnInfo($uid, self::$arrFields );
		if ( empty( $ret ) )
		{
			$ret = self::initSignInfo( $uid );
		}
		if ( !Util::isSameDay( $ret['sign_time'] ) )
		{
			$ret = self::refreshSign($uid);
		}
		return $ret;
	}
	
	public static function initSignInfo( $uid )
	{
		$initValArr = array(	
				'uid'  			=> $uid ,
				'sign_time'		=> 0 ,//初始化为0 紧接着就会被修改
				'sign_num' 	=> 0 ,
				'va_sign'		=> array(),
				);
		
		SignDao::insert( $uid, $initValArr );
		return $initValArr;
	}
	
	public static function refreshSign( $uid )
	{
		//为解决用户不退出跨十二点的问题
		$signInfo = SignDao::getSingnInfo($uid, self::$arrFields);
		//今天已经签到过了
		if ( Util::isSameDay($signInfo[ 'sign_time' ] ))
		{
			return 'sign already';
		}
		//签到
		$signInfo[ 'sign_num' ] ++;
		$signInfo['sign_time'] = Util::getTime();
		SignDao::update( $uid , $signInfo );
		Logger::debug('signInfo now is: %s', $signInfo);
		return $signInfo;
	}
	
	public static function getAccSignReward( $uid , $prizeIndex ) 
	{
		$signInfo = self::getSignInfo( $uid );
		
		//获取奖励配置并判定
		$accConf = btstore_get()->SIGN_ACC[ $prizeIndex ];
		if ( empty( $accConf ) )
		{
			throw  new FakeException( 'nosuch prizeIndex: %d' , $prizeIndex );
		}
		$needDays = $accConf[ 'needDays' ];
		if ( $signInfo[ 'sign_num' ] < $needDays )
		{
			throw new FakeException( 'uid: %d ,needDays:  %d , signed days : %d ' , $uid , $needDays ,$signInfo[ 'sign_num' ]);
		}
		if ( in_array( $prizeIndex , $signInfo[ 'va_sign' ]) )
		{
			throw new FakeException( 'already gain the prizeIndex %d' , $prizeIndex );
		}
		$signInfo[ 'va_sign' ][] = $prizeIndex;
		
		$arrReward = btstore_get()->SIGN_ACC[ $prizeIndex ][ 'rewardArr' ];
		//满足 发奖
		$ret = RewardUtil::reward($uid, $arrReward, StatisticsDef::ST_FUNCKEY_SIGN_REWARD );
		
		SignDao::update( $uid , $signInfo );
		if ( $ret[ 'bagModify' ] )
		{
			BagManager::getInstance()->getBag( $uid )->update();
		}
		EnUser::getUserObj( $uid )->update();
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */