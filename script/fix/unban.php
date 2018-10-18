<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: unban.php 252639 2016-07-21 04:29:17Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/fix/unban.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-07-21 04:29:17 +0000 (Thu, 21 Jul 2016) $
 * @version $Revision: 252639 $
 * @brief 
 *  
 **/
 
class unban extends BaseScript
{
	protected function executeScript($arrOption)
	{
		if (count($arrOption) < 2) 
		{
			printf("usage unban uid_file do|check\n");
			exit();
		}
		
		$arrLine = file($arrOption[0]);
		
		$do = false;
		if ($arrOption[1] == 'do')
		{
			$do = true;
		}
		
		foreach ($arrLine as $aUid)
		{
			$aUid = intval($aUid);
			RPCContext::getInstance()->resetSession();
			RPCContext::getInstance()->setSession('global.uid', $aUid);
			
			$userObj = EnUser::getUserObj($aUid);
			$banInfo = $userObj->getBanInfo();
			if (!empty($banInfo['time']))
			{
				if($banInfo['time'] > Util::getTime())
				{
					printf("unban uid:%d, banTime:%s\n", $aUid, strftime('%Y-%m-%d %H:%M:%S', $banInfo['time']));
					Logger::info("unban uid:%d, banTime:%s, doFlag:%d", $aUid, strftime('%Y-%m-%d %H:%M:%S', $banInfo['time']), $do);
					if ($do) 
					{
						$this->kickOffUserIfNeed($aUid);
						$userObj->unsetBan();
						$userObj->update();
					}
				}
				else
				{
					printf("not ban uid:%d\n", $aUid);
					Logger::info("not ban uid:%d, doFlag:%d", $aUid, $do);
				}
			}
			else 
			{
				printf("not ban uid:%d\n", $aUid);
				Logger::info("not ban uid:%d, doFlag:%d", $aUid, $do);
			}
		}
	}
	
	public function kickOffUserIfNeed($uid)
	{
		$proxy = new ServerProxy();	
		usleep(100);
		$ret = $proxy->checkUser($uid, true);
		if( $ret )
		{
			sleep(1);
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */