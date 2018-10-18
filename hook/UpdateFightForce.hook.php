<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UpdateFightForce.hook.php 218745 2015-12-30 09:57:41Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/hook/UpdateFightForce.hook.php $
 * @author $Author: ShiyuZhang $(wuqilin@babeltime.com)
 * @date $Date: 2015-12-30 09:57:41 +0000 (Wed, 30 Dec 2015) $
 * @version $Revision: 218745 $
 * @brief 
 *  
 **/

class UpdateFightForce
{
	function execute ($arrResponse)
	{
		if( WorldUtil::isCrossGroup() )
		{
			Logger::debug('is cross group');
			return $arrResponse;
		}
		
		$uid = RPCContext::getInstance()->getUid();
		if ($uid < FrameworkConfig::MIN_UID)
		{
			return $arrResponse;
		}

		$userObj = EnUser::getUserObj($uid);
		$userObj->updateFightForce();

		return $arrResponse;
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */