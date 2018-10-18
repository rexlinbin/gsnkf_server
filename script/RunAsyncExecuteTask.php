<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RunAsyncExecuteTask.php 60629 2013-08-21 09:51:53Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/RunAsyncExecuteTask.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-08-21 09:51:53 +0000 (Wed, 21 Aug 2013) $
 * @version $Revision: 60629 $
 * @brief 
 *  运行RPCContext中asyncExecuteTask执行的任务.
 *  运行方式：
 *  btscript RunAsyncExecuteTask.php CgsBDW1ldGhvZAYtZ3JvdXB3YXIuZG9SZXdhcmRPbkVuZAlhcmdzCQMBBAgLdG9rZW4GFTIzMTE2NTQ2MDIPYmFja2VuZAYbMTkyLjE2OC4xLjIzNRdyZWN1cnNMZXZlbAQCEWNhbGxiYWNrCgsBGWNhbGxiYWNrTmFtZQYLZHVtbXkBD3ByaXZhdGUDAQ==
 *  	后面的字符串为base64编码后的请求参数
 **/

class runAsyncExecuteTask extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript ($arrOption)
	{
		$requestStr = $arrOption[0];
		
		$arrRequest = base64_decode($requestStr);
		
		
		$uncompress = false;
		$arrRequest = Util::amfDecode ( $arrRequest, $uncompress );
		
		var_dump($arrRequest);
		
		RPCContext::getInstance ()->getFramework ()->executeRequest($arrRequest);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */