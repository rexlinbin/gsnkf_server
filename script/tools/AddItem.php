<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AddItem.php 128559 2014-08-22 04:22:18Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/AddItem.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-08-22 04:22:18 +0000 (Fri, 22 Aug 2014) $
 * @version $Revision: 128559 $
 * @brief 
 *  
 **/
//文件格式position uid
/**
 * 此脚本在服未发幸运排名奖励的时候补发
 */
class AddItem extends BaseScript
{
	protected function executeScript($arrOption)
	{
		$usage = "usage::btscript game001 AddItem uid filename\n";
		
		if(empty($arrOption[0]))
		{
			echo "No input uid!\n";
			return;
		}
		$uid = intval($arrOption[0]);
		$ret = UserDao::getUserByUid($uid, array('pid', 'uid', 'uname'));
		if(empty($ret))
		{
			printf("not found uid:%d\n", $uid);
			Logger::warning('not found uid:%d', $uid);
			return;
		}
		$uname = $ret['uname'];
		printf("uid:%d, uname:%s\n", $uid, $uname);
		
		if(empty($arrOption[1]))
		{
			echo "No input file!\n";
			return;
		}
		$fileName = $arrOption[1];
		$file = fopen("$fileName", 'r');
		echo "read $fileName\n";
		
		Util::kickOffUser($uid);
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		$userObj = EnUser::getUserObj($uid);
		$bag = BagManager::getInstance()->getBag($uid);
		
		while (!feof($file))
		{
			$line = fgets($file);
			if (empty($line))
			{
				break;
			}
			
			$info = explode(" ", $line);
			$itemTplId = intval($info[0]);
			$itemNum = intval($info[1]);
			$bag->addItemByTemplateID($itemTplId, $itemNum, true);
		}
		$bag->update();		
		fclose($file);
		echo "ok\n";
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */