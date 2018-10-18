<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: VipAutoIcs.php 78030 2013-11-29 11:06:54Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/VipAutoIcs.php $
 * @author $Author: wuqilin $(lanhongyu@babeltime.com)
 * @date $Date: 2013-11-29 11:06:54 +0000 (Fri, 29 Nov 2013) $
 * @version $Revision: 78030 $
 * @brief 
 *  
 **/

/**
 * vip加1,一直加到5
 * Enter description here ...
 * @author idyll
 *
 */
class VipAutoIcs extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript ($arrOption)
	{
		$num=1000;
		$offset = 0;
		$limit = 100;

		Logger::fatal('attention. add vip for all user.');
		while ($num-- > 0)
		{
			$arrUserInfo = UserDao::getArrUser($offset, $limit, array('uid', 'vip', 'va_user'));
			//Logger::debug('vip auto ics, arr user:%s', $arrUserInfo);
			if (empty($arrUserInfo))
			{
				Logger::fatal('attention. exit VipAutoIcs');
				break;
			}
			
			foreach ($arrUserInfo as $userInfo)
			{
				Logger::debug('idyll userInfo:%s', $userInfo);
				
				if ($userInfo['vip'] >=7)
				{
					continue;
				}
				//今天已经设置过了，给脚本重新执行用
				if (isset($userInfo['va_user']['vip_ics_time']) 
					&& Util::isSameDay($userInfo['va_user']['vip_ics_time']))
				{
					continue;		
				}				
				
				$user = EnUser::getUserObj($userInfo['uid']);
				$user->setVip4Test($user->getVip()+1);
			
				$user->update();
				Logger::debug('set vip %d for user %d', $user->getVip(), $user->getUid());
								
			}			
			usleep(200);			
			$offset += $limit;
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */