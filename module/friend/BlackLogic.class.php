<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BlackLogic.class.php 138613 2014-11-05 09:36:21Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/friend/BlackLogic.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-11-05 09:36:21 +0000 (Wed, 05 Nov 2014) $
 * @version $Revision: 138613 $
 * @brief 
 *  
 **/
class BlackLogic 
{

	public static function getBlackers($uid)
	{
		$vaBlack = BlackDao::getVaBlack($uid);
		if ( empty( $vaBlack['va_black']['blackers'] ) || !is_array( $vaBlack['va_black']['blackers'] ) )
		{
			return array();
		}
		else
		{
			$blackersUid = $vaBlack['va_black']['blackers'];
			
			//===================修复黑名单没有显示问题
			if ( count($blackersUid) > 100 ) 
			{
				//主要是为了兼容一下线上的错误数据，没有更新，只有下一次拉黑的时候才会把数据修复
				$unsetNum = count($blackersUid) - 100;
				foreach ( $blackersUid as $blackIndex => $blackUid )
				{
					if( $blackIndex < $unsetNum )
					{
						unset( $blackersUid[$blackIndex] );
					}
				}
				$blackersUid = array_merge( $blackersUid );
			}
			//===================修复黑名单没有显示问题
			
			$blackersInfo = EnUser::getArrUserBasicInfo($blackersUid, array('uid', 'uname', 'status', 'utid', 'level', 'fight_force'));//TODO
			$blackersInfo = array_merge( $blackersInfo );
			return $blackersInfo;
		}
	}
	
	
	public static function blackYou($uid, $beBlackUid)
	{
		$vaBlack = BlackDao::getVaBlack($uid);
		if( !isset( $vaBlack['va_black']['blackers'] ) )
		{
			$vaBlack['va_black']['blackers'] = array();
		}
		
		//最好先做这个，后边的更新错了也没事
		if (FriendLogic::isFriend( $uid, $beBlackUid))
		{
			FriendLogic::delFriend($uid, $beBlackUid);
		}
		
		if ( in_array( $beBlackUid , $vaBlack['va_black']['blackers']) )
		{
			throw new FakeException( 'uid: %d already in black list', $beBlackUid );
		}
		$vaBlack['va_black']['blackers'][] = $beBlackUid;
		
		//===================修复黑名单没有显示问题
		$blackersUid = $vaBlack['va_black']['blackers'];
		if ( count($blackersUid) > 100 )
		{
			//新的会顶掉旧的
			$unsetNum = count($blackersUid) - 100;
			foreach ( $blackersUid as $blackIndex => $blackUid )
			{
				if( $blackIndex < $unsetNum )
				{
					unset( $blackersUid[$blackIndex] );
				}
			}
			$blackersUid = array_merge( $blackersUid );
		}
		$vaBlack['va_black']['blackers'] = $blackersUid;
		//===================修复黑名单没有显示问题
		
		BlackDao::insertOrUpdate($uid,$vaBlack);
	}
	
	public static function unBlackYou($uid, $unBlackUid)
	{
		$vaBlack = BlackDao::getVaBlack($uid);
		if( !isset( $vaBlack['va_black']['blackers'] ) )
		{
			throw new FakeException('no black uid');
		}
	
		if ( !in_array( $unBlackUid , $vaBlack['va_black']['blackers']) )
		{
			throw new FakeException( 'uid: %d already not in black list', $unBlackUid );
		}
	
		foreach ( $vaBlack['va_black']['blackers'] as $key => $oneUid )
		{
			if ( $oneUid == $unBlackUid )
			{
				unset($vaBlack['va_black']['blackers'][$key]);
			}
		}
		
		
		//===================修复黑名单没有显示问题
		$blackersUid = array_merge( $vaBlack['va_black']['blackers'] );;
		if ( count($blackersUid) > 99 )
		{
			//新的会顶掉旧的
			$unsetNum = count($blackersUid) - 99;
			foreach ( $blackersUid as $blackIndex => $blackUid )
			{
				if( $blackIndex < $unsetNum )
				{
					unset( $blackersUid[$blackIndex] );
				}
			}
			$blackersUid = array_merge( $blackersUid );
		}
		$vaBlack['va_black']['blackers'] = $blackersUid;
		//===================修复黑名单没有显示问题
				
		BlackDao::insertOrUpdate($uid, $vaBlack);
	}
	
	public static function isInBlack($uid, $fuid)
	{
		$blackUids = self::getBlackUids($uid);
		if ( in_array( $fuid , $blackUids) )
		{
			return true;
		}
		
		return false;
	}
	
	public static function getBlackUids($uid)
	{
		$vaBlack = BlackDao::getVaBlack($uid);
		if( !isset( $vaBlack['va_black']['blackers'] ) )
		{
			return array();
		}
		return $vaBlack['va_black']['blackers'];
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */