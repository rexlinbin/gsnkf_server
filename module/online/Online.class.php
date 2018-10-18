<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Online.class.php 70228 2013-10-23 10:00:39Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/online/Online.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-10-23 10:00:39 +0000 (Wed, 23 Oct 2013) $
 * @version $Revision: 70228 $
 * @brief 
 *  
 **/
class Online implements IOnline
{
	public function getOnlineInfo()
	{
		Logger::trace( 'begin getOnlineInfo' );
		$onlineInfo = OnlineLogic::getOnlineInfo();
		if ( isset( $onlineInfo[ 'begin_time' ] ) )
		{
			unset( $onlineInfo[ 'begin_time' ] );
		}
		if ( isset( $onlineInfo[ 'end_time' ] ) )
		{
			unset( $onlineInfo[ 'end_time' ] );
		}
		
		Logger::trace('finish getOnlineInfo');
		return $onlineInfo;
	}

	public  function gainGift( $step )
	{
		Logger::trace( 'begin gainGift' );
		$giftArr = OnlineLogic::gainGift( $step );
		Logger::trace('finish gainGift');
		//return $giftArr;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */