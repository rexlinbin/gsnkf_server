<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CreateRoleIpFilter.hook.php 218745 2015-12-30 09:57:41Z ShiyuZhang $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/hook/CreateRoleIpFilter.hook.php $
 * @author $Author: ShiyuZhang $(hoping@babeltime.com)
 * @date $Date: 2015-12-30 09:57:41 +0000 (Wed, 30 Dec 2015) $
 * @version $Revision: 218745 $
 * @brief
 *
 **/
class CreateRoleIpFilter
{

	const IP_RANGE_FILE = '/home/pirate/rpcfw/conf/IpRange.cfg.php';

	public function execute($arrRequest)
	{
		if( WorldUtil::isCrossGroup() )
		{
			Logger::debug('is cross group');
			return $arrRequest;
		}
		
		$method = $arrRequest ['method'];
		if ($method != "user.createUser")
		{
			Logger::debug ( "method:%s not monitored by this hook", $method );
			return $arrRequest;
		}

		$clientIp = RPCContext::getInstance ()->getFramework ()->getClientIp ();
		$arrIpRange = require_once (CreateRoleIpFilter::IP_RANGE_FILE);
		if (Util::ipContains ( $arrIpRange, ip2long ( $clientIp ) ))
		{
			Logger::fatal ( "ip:%s not allowed in this game", $clientIp );
			throw new Exception ( 'close' );
		}
		else
		{
			Logger::debug ( "ip:%s is ok for create user", $clientIp );
		}

		return $arrRequest;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */