<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CheckSpend.php 193924 2015-08-24 09:59:30Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/CheckSpend.php $
 * @author $Author: MingTian $(wuqilin@babeltime.com)
 * @date $Date: 2015-08-24 09:59:30 +0000 (Mon, 24 Aug 2015) $
 * @version $Revision: 193924 $
 * @brief 
 *  
 **/

class CheckSpend extends BaseScript
{

	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		$startTime = strtotime('2015-08-21 12:00:00');
		$endTime = strtotime('2015-08-23 23:59:00');
		$key = $startTime . '-' . $endTime;
		
		$fix = false;
		if(isset($arrOption[0]) &&  $arrOption[0] == 'fix')
		{
			$fix = true;
		}
		
		$id = intval($arrOption[1]);
		$delHtid = intval($arrOption[2]);
		$addHtid = intval($arrOption[3]);
		$specialUid = intval($arrOption[4]);
		
		$batchNum = 10;
		$offset = 0;
		$data = new CData();
		while (true) 
		{
			printf("offset:%d, limit:%d\n", $offset, $batchNum);
			Logger::info('get users. offset:%d, limit:%d', $offset, $batchNum);  
			
			$arrRet = $data->select(array('uid','va_user'))->from('t_user_extra')
						   ->where('uid','>',0)->orderBy('uid', true)
						   ->limit($offset, $batchNum)->query();
			$offset += $batchNum;
			
			foreach($arrRet as $value)
			{
				$uid = $value['uid'];
				$vaInfo = $value['va_user'];
				if (isset($vaInfo[UserExtraDef::SPEND_REWARD][$key]) && in_array($id, $vaInfo[UserExtraDef::SPEND_REWARD][$key])) 
				{
					printf("uid:%d has gained reward:%d\n", $uid, $id);
					Logger::info('uid:%d has gained reward:%d', $uid, $id);
					if ($fix) 
					{
						if (!empty($specialUid) && $uid != $specialUid) 
						{
							continue;
						}
						Util::kickOffUser($uid);
						RPCContext::getInstance()->resetSession();
						RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
						
						$user = EnUser::getUserObj($uid);
						printf("uid:%d pid:%d uname:%s fix reward:%d\n", $uid, $user->getPid(), $user->getUname(), $id);
						Logger::info('uid:%d pid:%d uname:%s fix reward:%d', $uid, $user->getPid(), $user->getUname(), $id);
						
						$del = false;
						$count = 0;
						$heroManager = $user->getHeroManager();
						$allHero = $heroManager->getAllHeroObj();
						foreach ($allHero as $hid => $hero)
						{
							if ($count > 1) 
							{
								break;
							}
							if ($hero->getHtid() == $delHtid && $hero->canBeDel() && $heroManager->delHeroByHid($hid))
							{
								$del = true;
								$count++;
							}
						}
						if ($del) 
						{
							$heroManager->addNewHero($addHtid);
							printf("uid:%d pid:%d uname:%s delHtid:%d addHtid:%d success\n", $uid, $user->getPid(), $user->getUname(), $delHtid, $addHtid);
							Logger::info('uid:%d pid:%d uname:%s delHtid:%d addHtid:%d success', $uid, $user->getPid(), $user->getUname(), $delHtid, $addHtid);
						}
						else 
						{
							printf("uid:%d pid:%d uname:%s delHtid:%d failed\n", $uid, $user->getPid(), $user->getUname(), $delHtid);
							Logger::info('uid:%d pid:%d uname:%s delHtid:%d failed', $uid, $user->getPid(), $user->getUname(), $delHtid);
						}
						$user->update();
					}
				}
			}
			usleep(50);
			
			if(count($arrRet) < $batchNum)
			{
				break;
			}
		}
		Logger::info('fix all users');
		printf("done \n");
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */