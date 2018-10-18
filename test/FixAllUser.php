<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FixAllUser.php 78297 2013-12-02 10:21:15Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/FixAllUser.php $
 * @author $Author: wuqilin $(wuqilin@babeltime.com)
 * @date $Date: 2013-12-02 10:21:15 +0000 (Mon, 02 Dec 2013) $
 * @version $Revision: 78297 $
 * @brief 
 *  
 **/

class FixAllUser extends BaseScript
{

	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		$data = new CData();
		
		$forceFix = 0;
		if(isset($arrOption[0]))
		{
			$forceFix = intval($arrOption[0]);
		}
		
		$batchNum = 10;
		$offset = 0;
		while (true) 
		{
			printf("offset:%d, limit:%d\n", $offset, $batchNum);
			Logger::info('get users. offset:%d, limit:%d', $offset, $batchNum);
			
			$arrRet = $data->select(array('uid','status'))->from('t_user')
					->where('uid','>',0)->orderBy('uid', true)
					->limit($offset, $batchNum)->query();
			$offset += $batchNum;
			
			foreach($arrRet as $value)
			{
				$uid = $value['uid'];
				$status = $value['status'];
				if( $status == UserDef::STATUS_ONLINE )
				{
					if($forceFix)
					{
						Logger::info('uid:%d, status:%d kick', $uid, $status);
						Util::kickOffUser($uid);
					}
					else 
					{
						Logger::info('uid:%d, status:%d ignore', $uid, $status);
						continue;
					}
				}
				
				RPCContext::getInstance()->resetSession();
				RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
				
				$userObj = EnUser::getUserObj($uid);
				$userObj->fixLevel();
				$userObj->fixFlopNum();
		
				$heroClass = new Hero();
				$heroClass->getAllHeroes();
				
				$userObj->update();
				
				$bagObj = BagManager::getInstance()->getBag($uid);
				$bagObj->bagInfo();
				$bagObj->update();
				
				unset($userObj);
				unset($heroClass);
				unset($bagObj);
				
				EnUser::release($uid);
				BagManager::getInstance()->release($uid);
				Logger::info('uid:%d update', $uid);
			}
			usleep(50);
			
			if( count($arrRet) <  $batchNum)
			{
				break;
			}
		}
		Logger::info('fix all users');
		printf("done \n");
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */