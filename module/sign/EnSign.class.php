<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnSign.class.php 78835 2013-12-05 05:29:38Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/sign/EnSign.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-12-05 05:29:38 +0000 (Thu, 05 Dec 2013) $
 * @version $Revision: 78835 $
 * @brief 
 *  
 **/
class EnSign
{
	/**
	 * 累积签到使用
	 */
	public static function signTody()
	{
		$uid = RPCContext::getInstance()->getUid();
		if (empty( $uid ))
		{
			throw new FakeException( 'cant get uid from session' );
		}
		AccsignLogic::refreshSign( $uid );
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */