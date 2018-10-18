<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: InitUser.php 114911 2014-06-17 06:56:30Z wuqilin $
 * 
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/InitUser.php $
 * @author $Author: wuqilin $(lanhongyu@babeltime.com)
 * @date $Date: 2014-06-17 06:56:30 +0000 (Tue, 17 Jun 2014) $
 * @version $Revision: 114911 $
 * @brief 
 * 
 **/

/**
 * 这个脚本在开服前运行。
 * 使用uid 10001到10010 pid 1到10 调用UserLogic::createUser创建10个用户
 * 然后调用StarDao::insert插入名将模块
 * @author idyll
 *
 */
class InitUser extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript ($arrOption)
	{	
		$uid_init = SPECIAL_UID::MIN_ROBOT_UID;
		$pid_init = 1;
		$level = 30;
		
		$arrUname = I18nDef::$ARR_ROBOT_NAME;	
		
		$num = SPECIAL_UID::MAX_ROBOT_UID - SPECIAL_UID::MIN_ROBOT_UID + 1;
		
		if( count($arrUname) < $num )
		{
			Logger::fatal('no enought robot name');
			return;
		}
		
		for($i = 0; $i < $num; $i++)
		{
			$utid = mt_rand(1, 2);
			$uid = $uid_init + $i;				 
			$pid = $pid_init + $i;

			$ret = UserDao::getUserByUid($uid, array('uid', 'level'), true);
			if(empty($ret))
			{				
				Logger::info('uid:%d not exist. create it', $uid);
				UserLogic::createUser($pid, $utid, $arrUname[$i], $uid);
				
				//清档后，再次执行，user, hero_formation的数据会从mem中获取，获取的数据是错误的。这里强制刷新一下
				UserDao::updateUser($uid, array('uid' =>  $uid ) );
				FormationDao::update($uid, array('uid' =>  $uid ) );
				
				$this->setUserLevel($uid, $level);
				//比武表插数据
				CompeteLogic::init($uid);
				continue;
 			}
 			else 
 			{
 				//比武机器人的等级最开始是10级，20140211日改成30级
 				if( $ret['level'] != $level)
 				{
 					$this->setUserLevel($uid, $level);
 					Logger::info('uid:%d exist. but level:%d, new level:%d', $uid, $ret['level'], $level);
 				}
 				else
 				{
 					Logger::info('uid:%d exist', $uid);
 				}
 			}

								
		}
		
		//callback中都是一些给前端推送到消息，这个不需要推送
		RPCContext::getInstance()->delAllCallBack();
		echo "ok\n";
	}
	
	public function setUserLevel($uid, $level)
	{
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		$userObj = EnUser::getUserObj($uid);
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		$cur = $userObj->getAllExp ();
		$userObj->addExp( $expTable[$level] - $cur );
		$userObj->update ();
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */