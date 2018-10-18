<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DealSpecialItem.php 128433 2014-08-21 09:02:48Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/DealSpecialItem.php $
 * @author $Author: MingTian $(wuqilin@babeltime.com)
 * @date $Date: 2014-08-21 09:02:48 +0000 (Thu, 21 Aug 2014) $
 * @version $Revision: 128433 $
 * @brief 
 *  
 **/




class DealSpecialItem extends BaseScript
{

	protected function executeScript ($arrOption)
	{
		$uid = intval($arrOption[0]);
		$srcItemTplId = intval($arrOption[1]);
		$desItemTplId = intval($arrOption[2]);
		$srcSubNum = intval($arrOption[3]);
		$ret = UserDao::getUserByUid($uid, array('pid', 'uid', 'uname') );
		if(empty($ret))
		{
			printf("not found uid:%d\n", $uid);
			Logger::warning('not found uid:%d', $uid);
			continue;
		}
		$uname = $ret['uname'];
		printf("uid:%d, uname:%s\n", $uid, $uname);
		Util::kickOffUser($uid);
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		$userObj = EnUser::getUserObj($uid);
		$bag = BagManager::getInstance()->getBag($uid);
		$srcItemNum = $bag->getItemNumByTemplateID($srcItemTplId);
		$desItemNum = $bag->getItemNumByTemplateID($desItemTplId);
		printf("源物品数量:%d\n目标物品数量:%d\n", $srcItemNum, $desItemNum);
		$srcSubNum = min($srcItemNum, $srcSubNum);
		$desAddNum = $srcSubNum;
		printf("扣源物品数量:%d\n加目标物品数量:%d\n",  $srcSubNum,  $desAddNum);
		
		printf("input y|n\n");
		$ret = trim(fgets(STDIN));
		if( $ret == 'y' )
		{
			$ret = $bag->deleteItembyTemplateID($srcItemTplId, $srcSubNum);
			if( !$ret )
			{
				printf("sub item:%d failed\n", $srcItemTplId);
				return;
			}
			$ret = $bag->addItemByTemplateID($desItemTplId, $desAddNum, true);
			if( $ret )
			{
				$ret = $bag->update();
				$msg = sprintf('uid:%d, uname:%s, sub item:%d num:%d; add item:%d, num;%d', 
						$uid, $uname, $srcItemTplId, $srcSubNum, $desItemTplId, $desAddNum);
				printf("%s\n", $msg);
				Logger::info('%s', $msg);
				var_dump($ret);
			}
			else
			{
				$msg = sprintf('add item failed');
				printf("%s\n", $msg);
				Logger::info('%s', $msg);
			}
			
		}
		else 
		{
			printf("ignore");
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
