<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SendArrUidReward.php 207028 2015-11-04 03:44:06Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/SendArrUidReward.php $
 * @author $Author: wuqilin $(wuqilin@babeltime.com)
 * @date $Date: 2015-11-04 03:44:06 +0000 (Wed, 04 Nov 2015) $
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


class SendArrUidReward extends BaseScript
{

	protected function executeScript ($arrOption)
	{
		
		$do = false;
		if( isset($arrOption[0] ) && $arrOption[0] == 'do' )
		{
			$do = true;
		}
		$specUid = 0;
		if( isset($arrOption[1] ) )
		{
			$specUid = intval($arrOption[1]);
		}

		$beginTime = strtotime('2015-10-20 00:00:00');
		$endTime = strtotime('2015-10-22 23:59:59');
		$rewardTime = strtotime('2015-10-23 18:30:00');
		
		$rewardInfo = array(
					RewardType::ARR_ITEM_TPL => array(),
					RewardDef::TITLE => '悬赏榜问题补偿',
					RewardDef::MSG => '补偿如下：',
				);

		$arrRewardItem = array(
				50000 => array( 30111 => 3 ),
				30000 => array( 30111 => 2 ),
				10000 => array( 30111 => 1 ),
				5000 => array( 30110 => 2 ),
				1 => array( 30110 => 1 ),
		);
		
		$arrField = array(
			'uid',
			'gold_num',
			'mtime',
			'order_type',
		);
		$data = new CData();
		$arrRet = $data->select($arrField)->from('t_bbpay_gold')
			->where('mtime', 'between', array($beginTime, $endTime))
			->where('status', '=', '1')
			//->where('order_type', '=', 0)
			->query();
		
		if ( empty($arrRet) )
		{
			Logger::info('no user to reward');
			printf("done\n");
			return;
		}
		
		$arrUserInfo = array();
		foreach( $arrRet as $row )
		{
			$uid = $row['uid'];
			$goldNum = $row['gold_num'];
			if( !isset( $arrUserInfo[$uid] ) )
			{
				$arrUserInfo[$uid] = 0;
			}
			$arrUserInfo[$uid] += $goldNum;
		}
		
		foreach( $arrUserInfo as $uid => $goldNum )
		{
			if( $specUid > 0 && $specUid != $uid )
			{
				printf("specUid:%d ignore uid:%d\n", $specUid, $uid);
				continue;
			}
			try
			{
				$ret = UserDao::getUserByUid($uid, array('pid', 'uid', 'uname', 'vip', 'level') );
				if(empty($ret))
				{
					printf("not found uid:%d\n", $uid);
					Logger::warning('not found uid:%d', $uid);
					continue;
				}
				$pid = $ret['pid'];
				$uname = $ret['uname'];
				
				$arrItem = array();
				foreach( $arrRewardItem as $goldMin => $value )
				{
					if( $goldNum >= $goldMin )
					{
						$arrItem = $value;
						break;
					}
				}
				if( empty($arrItem) )
				{
					$msg = sprintf('cant found reward for uid:%d, goldNum:%d', $uid, $goldNum);
					printf("%s\n", $msg);
					Logger::warning('%s', $msg);
					continue;
				}
				
				$arrReward = $rewardInfo;
				$arrReward[RewardType::ARR_ITEM_TPL] =  $arrItem;
				
				printf("try send reward to pid:%d, uid:%d, uname:%s, vip:%d, level:%d, goldNum:%d, reward:%s\n",
				$pid, $uid, $uname, $ret['vip'], $ret['level'], $goldNum, var_export($arrItem, true));
			
				$data = new CData ();
				$ret = $data->select ( array(RewardDef::SQL_RID, RewardDef::SQL_VA_REWARD) )->from ( RewardDef::SQL_TABLE )
							->where( RewardDef::SQL_UID , '=', $uid)
							->where( RewardDef::SQL_SEND_TIME, '>', $rewardTime)
							->where( RewardDef::SQL_SOURCE , '=', RewardSource::SYSTEM_GENERAL )
							->query();
				if(!empty($ret) )
				{
					$found = false;
					foreach( $ret as $row )
					{
						if ($row[RewardDef::SQL_VA_REWARD][RewardDef::TITLE] == $rewardInfo[RewardDef::TITLE])
						{
							$found = true;
						}
					}
					if($found)
					{
						$msg = sprintf('uid:%d already reward, ignore', $uid);
						printf("%s\n", $msg);
						Logger::warning('%s', $msg);
						continue;
					}	
				}
				
				if($do)
				{
					$msg = sprintf('send reward. uid:%d, uname:%s', $uid, $uname);
					printf("%s\n", $msg);
					Logger::info('%s', $msg);
					EnReward::sendReward($uid, RewardSource::SYSTEM_GENERAL, $arrReward);
				}
			
			}
			catch( Exception $e )
			{
				Logger::fatal('failed:%s', $e->getMessage() );
			}
		}
		
		
		
		printf("send reward ok\n");
	}

	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */