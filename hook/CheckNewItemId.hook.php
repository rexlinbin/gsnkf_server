<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CheckNewItemId.hook.php 218745 2015-12-30 09:57:41Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/hook/CheckNewItemId.hook.php $
 * @author $Author: ShiyuZhang $(wuqilin@babeltime.com)
 * @date $Date: 2015-12-30 09:57:41 +0000 (Wed, 30 Dec 2015) $
 * @version $Revision: 218745 $
 * @brief 
 *  
 **/

/**
 * 为了检查item_id使用率很低问题
 * @author wuqilin
 *
 */
class CheckNewItemId
{
	function execute ($arrResponse)
	{
		if( WorldUtil::isCrossGroup() )
		{
			Logger::debug('is cross group');
			return $arrResponse;
		}
		
		$arrNewItemId = ItemManager::getInstance()->getArrNewItemId();	
		$arrAddItemId = ItemManager::getInstance()->getArrAddItemId();
	
		$arrNewItemId = array_unique($arrNewItemId);
		$arrAddItemId = array_unique($arrAddItemId);
		
		if( !empty($arrNewItemId) )
		{
			Logger::info('checkNewItemId. method:%s, new:%d, add:%d', 
					RPCContext::getInstance()->getFramework()->getMethod(),
					count($arrNewItemId), count($arrAddItemId));
		}
		return $arrResponse;
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */