<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CheckNegative.hook.php 218745 2015-12-30 09:57:41Z ShiyuZhang $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/hook/CheckNegative.hook.php $
 * @author $Author: ShiyuZhang $(hoping@babeltime.com)
 * @date $Date: 2015-12-30 09:57:41 +0000 (Wed, 30 Dec 2015) $
 * @version $Revision: 218745 $
 * @brief
 *
 **/
class CheckNegative
{
	private static $EXCLUDE_METHOD = array(
		'user.setVaConfig',
		'user.login'
	);

	public function execute($arrRequest)
	{
		if( WorldUtil::isCrossGroup() )
		{
			Logger::debug('is cross group');
			return $arrRequest;
		}
		
		$uid = RPCContext::getInstance ()->getUid ();
		if (empty ( $uid ))
		{
			return $arrRequest;
		}

		if ( !empty($arrRequest ['private']) )
		{
			return $arrRequest;
		}

		if ( in_array( $arrRequest['method'], self::$EXCLUDE_METHOD ) )
		{
			return $arrRequest;
		}

		$callback = array ($this, '_checkNegative' );

		$userdata=array(
			'uid' => $uid,
			'method' => $arrRequest['method'],
			'args' => $arrRequest ['args'],
			);
		$args = $arrRequest ['args'];

		if ( is_array($args) )
		{
			array_walk_recursive($args, $callback, $userdata);
		}

		return $arrRequest;
	}

	public function _checkNegative($data, $key, $userdata)
	{
		if ( is_numeric($data) && intval($data) < 0 )
		{
			Logger::FATAL("uid:%d method:%s args:%s is negative!",
				$userdata['uid'], $userdata['method'], $userdata['args'] );
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */