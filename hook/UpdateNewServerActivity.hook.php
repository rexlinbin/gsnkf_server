<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UpdateNewServerActivity.hook.php 242178 2016-05-11 10:56:55Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/hook/UpdateNewServerActivity.hook.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2016-05-11 10:56:55 +0000 (Wed, 11 May 2016) $
 * @version $Revision: 242178 $
 * @brief 
 *  
 **/
class UpdateNewServerActivity
{
	function execute ($arrResponse)
	{
		NewServerActivityForHook::commit();
		return $arrResponse;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */