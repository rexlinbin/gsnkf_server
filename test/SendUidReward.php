<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SendArrUidReward.php 207028 2015-11-04 03:44:06Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/card/rpcfw/test/SendArrUidReward.php $
 * @author $Author: wuqilin $(wuqilin@babeltime.com)
 * @date $Date: 2015-11-04 11:44:06 +0800 (星期三, 04 十一月 2015) $
 * @version $Revision: 207028 $
 * @brief 
 *  
 **/

/**
 * 给一批用户发奖励
 * 最初为开服活动"抽卡消费回馈"用
 *
 * @author wuqilin
 *
 *
 */

// grep gold_recruit rpc.log |grep num:10  | sed 's/.*\[uid:\([0-9]*\)\].*/\1/' | sort | uniq


class SendUidReward extends BaseScript
{

	protected function executeScript ($arrOption)
	{
		
		$do = false;
		if( isset($arrOption[0] ) && $arrOption[0] == 'do' )
		{
			$do = true;
		}
		
		$uid = intval($arrOption[1]);

		$rewardTime = strtotime('2015-12-28 11:30:00');
		
		$arrReward = array(
					//RewardType::ARR_ITEM_TPL => array(),
					RewardType::GOLD => 250,
					RewardType::ZG => 1200,
					//RewardDef::TITLE => '悬赏榜问题补偿',
					//RewardDef::MSG => '补偿如下：',
				);

		$ret = UserDao::getUserByUid($uid, array('pid', 'uid', 'uname', 'vip', 'level') );
		if(empty($ret))
		{
			printf("not found uid:%d\n", $uid);
			Logger::warning('not found uid:%d', $uid);
			return;
		}
		$pid = $ret['pid'];
		$uname = $ret['uname'];

		printf("try send reward to pid:%d, uid:%d, uname:%s, vip:%d, level:%d\n",
								$pid, $uid, $uname, $ret['vip'], $ret['level'], $arrReward);
	
		$data = new CData ();
		$ret = $data->select ( array(RewardDef::SQL_RID, RewardDef::SQL_VA_REWARD) )->from ( RewardDef::SQL_TABLE )
					->where( RewardDef::SQL_UID , '=', $uid)
					->where( RewardDef::SQL_SEND_TIME, '>', $rewardTime)
					->where( RewardDef::SQL_SOURCE , '=', RewardSource::SYSTEM_GENERAL )
					->query();
		if(!empty($ret) )
		{
			$found = true; 
			if( !empty($arrReward[RewardDef::TITLE]) )
			{
				$found = false;
				foreach( $ret as $row )
				{
					if ( $row[RewardDef::SQL_VA_REWARD][RewardDef::TITLE] == $arrReward[RewardDef::TITLE])
					{
						$found = true;
					}
				}
			}
			if($found)
			{
				$msg = sprintf('uid:%d already reward, ignore', $uid);
				printf("%s\n", $msg);
				Logger::warning('%s', $msg);
				return;
			}	
		}
		
		if($do)
		{
			$msg = sprintf('send reward. uid:%d, uname:%s', $uid, $uname);
			printf("%s\n", $msg);
			Logger::info('%s', $msg);
			EnReward::sendReward($uid, RewardSource::SYSTEM_GENERAL, $arrReward);
		}
		
		printf("send reward ok\n");
	}

	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */