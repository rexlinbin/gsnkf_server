<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnFriend.class.php 107954 2014-05-13 08:56:17Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/friend/EnFriend.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-05-13 08:56:17 +0000 (Tue, 13 May 2014) $
 * @version $Revision: 107954 $
 * @brief 
 *  
 **/
class EnFriend
{
	/**
	 * 好友上线通知
	 * @param int $uid
	 */
	static function loginNotify( $uid )
	{
		$arrUid = FriendLogic::getOnlineFriendIds( $uid );
		if (empty ( $arrUid ))
		{
			return;
		}
		RPCContext::getInstance ()->sendMsg ( $arrUid, PushInterfaceDef::FRIEND_LOGIN,
		array ($uid ) );
	}
	/**
	 * 好友下线通知
	 * @param int $uid
	 */
	static function logoffNotify( $uid )
	{
	
		$arrUid = FriendLogic::getOnlineFriendIds ( $uid );
		if (empty ( $arrUid ))
		{
			return;
		}
		RPCContext::getInstance ()->sendMsg ( $arrUid, PushInterfaceDef::FRIEND_LOGOFF,
		array ( $uid ) );
	}
	
	/**
	 * 获得好友数量
	 * @param int $uid 
	 * @return number
	 */
	static function getFriendNum($uid)
	{
		return FriendLogic::getFriendNum($uid);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */