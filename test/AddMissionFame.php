<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DelUserItem.php 176159 2015-06-02 07:11:03Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/card/rpcfw/test/DelUserItem.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-06-02 15:11:03 +0800 (星期二, 02 六月 2015) $
 * @version $Revision: 176159 $
 * @brief 
 *  
 **/
class AddMissionFame extends BaseScript
{
	protected function executeScript($arrOption)
	{
		$uid = intval($arrOption[0]);
		$num = intval($arrOption[1]);
		
		
		$ret = UserDao::getUserByUid($uid, array('pid', 'uid', 'uname') );
		if(empty($ret))
		{
		    printf("not found uid:%d\n", $uid);
		    Logger::warning('not found uid:%d', $uid);
		    continue;
		}
		$uname = $ret['uname'];
		
		
		Util::kickOffUser($uid);
		
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		$userObj = EnUser::getUserObj($uid);
		
		$pid = $userObj->getPid();
		$serverId = $userObj->getServerId();
		$crossUserObj = MissionCrossUserObj::getInstance($pid, $serverId);
		$innerUserObj = MissionUserObj::getInstance($uid);
		
		$fameNow = $innerUserObj->addFame($num);
		//$innerUserObj->addSpecFame($num);
		$crossUserObj->setFame($fameNow);
		$userObj->addFameNum($num);
		
		printf("add fame to uid:%d, uname:%s. num:%s, curUser:%d, curList:%d\n", 
		      $uid, $uname, $num, $userObj->getFameNum(), $fameNow );
		
		$ret = trim(fgets(STDIN));
		if( $ret == 'y' )
		{
            $innerUserObj->update();
            $crossUserObj->update();
            $userObj->update();
            printf("add end\n");
		}
		else
		{
		    printf("ignore\n");
		}
        printf("done\n");
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */