<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SendRechargeReward.class.php 164971 2015-04-02 03:49:19Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/SendRechargeReward.class.php $
 * @author $Author: wuqilin $(zhengguohao@babeltime.com)
 * @date $Date: 2015-04-02 03:49:19 +0000 (Thu, 02 Apr 2015) $
 * @version $Revision: 164971 $
 * @brief 
 *  
 **/
class SendRechargeReward extends BaseScript
{
	/**
	 * 越南官方渠道充值返利
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption)
	{
		if( count( $arrOption ) < 2)
		{
			echo "invalid param\n";
			return;
		}
		
		$rewardTime = strtotime('2015-04-02 11:00:00');

		$pid = intval($arrOption[0]);
		$name = $arrOption[1];
		$gold = intval($arrOption[2]);
		
		$arrReward = array(
				RewardType::GOLD => $gold
		);

		try {
			
			$wait = true;
			
			$ret = UserDao::getArrUserByPid($pid, array('uid', 'pid','uname', 'level', 'vip','gold_num'));
			if ( empty($ret) )
			{
				printf("not found pid:%d\n", $pid);
				return;
			}
			$user = $ret[0];
			if ( $user['uname'] != $name )
			{
				Logger::warning('name not match. pid:%d, args:%s,  db:%s', $pid, $name, $user['uname']);
			}
			
			$uid = $user['uid'];
			printf("pid:%d, uid:%d, uname:%s, vip:%d, level:%d, gold:%d.\n",
			$user['pid'], $user['uid'], $user['uname'], $user['vip'], $user['level'], $user['gold_num']);
			
			$data = new CData ();
			$ret = $data->select ( array(RewardDef::SQL_RID) )
						->from ( RewardDef::SQL_TABLE )
						->where( RewardDef::SQL_UID , '=', $uid)
						->where( RewardDef::SQL_SEND_TIME, '>', $rewardTime)
						->where( RewardDef::SQL_SOURCE , '=', RewardSource::SYSTEM_GENERAL )
						->query();
			
			if(!empty($ret))
			{
				Logger::warning('uid:%d already reward, ignore', $uid);
			}
			else
			{
				if($wait)
				{
					printf("reward:%s\n", var_export($arrReward, true) );
					$ret = trim(fgets(STDIN));
					if( $ret != 'y' )
					{
						printf("ignore\n");
						return;
					}
				}
				Logger::info('uid:%d pid:%d recharge reward. gold:%d', $uid, $pid, $gold);
				
				EnReward::sendReward($uid, RewardSource::SYSTEM_GENERAL, $arrReward);
			}
		}
		catch (Exception $e)
		{
			Logger::fatal('failed:%s', $e->getMessage() );
		}
		
		printf("send reward ok\n");
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */