<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AddBlackIp.class.php 239815 2016-04-22 11:26:01Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/AddBlackIp.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-04-22 11:26:01 +0000 (Fri, 22 Apr 2016) $
 * @version $Revision: 239815 $
 * @brief 
 *  
 **/
 
class AddBlackIp extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		if (empty($arrOption)) 
		{
			printf("Usage: btscript AddBlackIp black_ip_file\n");
			exit();
		}
		
		$file = $arrOption[0];
		if (!is_file($file)) 
		{
			printf("invalid file\n");
			exit();
		}
		
		$arrBlackIp = file_get_contents($file);
		$arrBlackIp = explode("\n", $arrBlackIp);
		foreach ($arrBlackIp as $aInfo)
		{
			if (empty($aInfo)) 
			{
				continue;
			}
			
			$weight = EnIpBlocker::BLACK_IP_FOREVER_WEIGHT;
			$arrField = explode(" ", $aInfo);
			$ipstr = $arrField[0];
			if (count($arrField) >= 2) 
			{
				$weight = $arrField[1];
			}

			$ip = ip2long($ipstr);
			EnIpBlocker::addBlackIp($ip, $weight);
			printf("add black ip:%s, weight:%d\n", $ipstr, $weight);
		}
		printf("done\n");
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */