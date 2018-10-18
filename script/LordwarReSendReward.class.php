<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: LordwarReSendReward.class.php 140889 2014-11-20 03:34:47Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/LordwarReSendReward.class.php $
 * @author $Author: ShiyuZhang $(wuqilin@babeltime.com)
 * @date $Date: 2014-11-20 03:34:47 +0000 (Thu, 20 Nov 2014) $
 * @version $Revision: 140889 $
 * @brief 
 *  
 **/

/**
 *
 * 示例：
 * btscript gamexxx LordwarReSendReward.class.php rewardType uid
 * @author wuqilin
 *
 */
class LordwarReSendReward extends BaseScript
{
	
	public function sendSupportRewardToUser($serverId, $pid, $uid, $maxRewardRound, $innerAuditionStartTime, $doSend = false)
	{
		$confMgr = LordwarConfMgr::getInstance();
	
		$lordObj = new LordObj($serverId, $pid);

		$mysupportList = $lordObj->getMySupport(false);
		$va_extra = $lordObj->getLordVaExtra();
		
		if( empty($va_extra['supportList']) )
		{
			$supportRewardList = array();
		}
		else
		{
			$supportRewardList = $va_extra['supportList'];
		}
		
		$rewardTime = Util::getTime();
		foreach ( $mysupportList as $round => $support )
		{
			if( $round > $maxRewardRound )
			{
				Logger::info('round:%d > max:%d.ignore', $round, $maxRewardRound);
				continue;
			}
			if ( isset($support['win']) && $support['win'] == 1 )
			{
				if(  empty( $supportRewardList[$round]['rewardTime'] )
					|| $supportRewardList[$round]['rewardTime'] < $innerAuditionStartTime )
				{
					$stage = LordwarConfMgr::getStageByRound($round);
					if( $stage == LordwarField::INNER )
					{
						$rewardSource = RewardSource::LORDWAR_SUPPORT_INNER;
					}
					else if( $stage == LordwarField::CROSS )
					{
						$rewardSource = RewardSource::LORDWAR_SUPPORT_CROSS;
					}
					else
					{
						throw new InterException('invalid stage:%s', $stage);
					}
					$rewardArr = $confMgr->getReward(LordwarReward::SUPPORT, $stage);
					
					$lordObj->supportRewardEnd($round, $rewardTime);
					if( $doSend )
					{
						$lordObj->update();	
						RewardUtil::reward3DtoCenter($uid, array( $rewardArr ), $rewardSource, array());
					}

					Logger::INFO("reward support. serverId:%d, pid:%d, uid:%d, round:%d, source:%d, reward:%s", 
							$serverId, $pid, $uid, $round, $rewardSource, $rewardArr);
				}
				else
				{
					Logger::INFO("already reward. serverId:%d, pid:%d, uid:%d, round:%d, time:%s",
							$serverId, $pid, $uid, $round, date('Y-m-d H:i:s', $supportRewardList[$round]['rewardTime']) );
				}
			}
		}
	}
	
	
	public function sendSupportRewardToAll( $doSend = false, $arrUid = array() )
	{
		$confMgr = LordwarConfMgr::getInstance();
		$sess = $confMgr->getSess();
		$serverId = Util::getServerId();
		$teamId = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess)->getTeamIdByServerId($serverId);
		$innerAuditionStartTime = $confMgr->getRoundStartTime(LordwarRound::INNER_AUDITION);
		$activityStartTime = $confMgr->getBaseConf('start_time');
		
		$procedure = LordwarProcedure::getInstance($sess, LordwarField::INNER);
		$teamObj = $procedure->getTeamObj($teamId);
		$curRound = $teamObj->getCurRound();
		$maxRewardRound = $curRound;
		if( $teamObj->getCurStatus() < LordwarStatus::DONE )
		{
			$maxRewardRound = LordwarUtil::getPreRound($curRound);
		}
		
		$arrUser = array();
		if( empty($arrUid) )
		{
			$data = new CData();
			
			$offset = 0;
			$arrRet = array();
			for ( $i = 0; $i < 65535; $i++ )
			{
				$result = $data->select(array('uid', 'pid', 'server_id'))->from('t_lordwar_inner_user')
				->where('last_join_time', '>=', $activityStartTime)
				->orderBy('uid', true)->limit($offset, DataDef::MAX_FETCH)->query();
			
				$arrRet= array_merge($arrRet, $result);
				if ( count($result) < DataDef::MAX_FETCH )
				{
					break;
				}
				$offset += DataDef::MAX_FETCH;
			}
			$arrUser = Util::arrayIndex($arrRet, 'uid');
		}
		else
		{
			$data = new CData();
			$result = $data->select(array('uid', 'pid', 'server_id'))->from('t_lordwar_inner_user')
					->where('uid', 'in', $arrUid)
					->query();
			$arrUser = Util::arrayIndex($result, 'uid');
		}
	
		$allServerId = Util::getAllServerId();
		foreach( $arrUser as $value )
		{
			if( !in_array($value['server_id'], $allServerId)  )
			{
				Logger::warning('invalid data:%s', $value);
				break;
			}
			$pid = $value['pid'];
			$uid = $value['uid'];
			$this->sendSupportRewardToUser($value['server_id'], $pid, $uid, $maxRewardRound, $innerAuditionStartTime, $doSend);
			
			usleep(50);
		}
		
	}
	
	/*
	public function sendPromotionRewardToUser($serverId, $pid, $uid, $arrRoundData, $innerAuditionStartTime, $doSend = false)
	{
		return;
		
		$confMgr = LordwarConfMgr::getInstance();
		
		$lordObj = new LordObj($serverId, $pid);
		
		$va_extra = $lordObj->getLordVaExtra();
		
		$rewardTime = Util::getTime();
		foreach ( array(LordwarRound::CROSS_2TO1, LordwarRound::INNER_2TO1) as $round )
		{
			if ( !empty($va_extra['promotionList'][$round]) )
			{
				$roundData = $arrRoundData[$round];
				$stage = LordwarConfMgr::getStageByRound($round);
				foreach ( $roundData as $teamType => $data)
				{
					foreach( $data['lordArr'] as $lord )
					{
						if ( $lord['serverId'] == $serverId && $lord['pid'] == $pid
						&& ( empty($va_extra['promotionList'][$round]) ||
								$va_extra['promotionList'][$round]['rewardTime'] < $innerAuditionStartTime ) )
						{
							//TODO
							$rank = $lord['rank'];
							$rewardArr = $confMgr->getReward(LordwarReward::RPOMOTION,
									$stage, $teamType, $lord['rank']);
		
							Logger::INFO("reward promotion user:%d on round:%d, rank:%d",
							$uid, $round, $rank);
		
							$lordObj->promotionRewardEnd($round, $rewardTime, $lord['rank']);
						}
					}
				}
			}
		}
	}
	*/
	
	protected function executeScript ($arrOption)
	{
		if ( count($arrOption) < 2 )
		{
			printf("invalid args!btscript gamexxx LordwarReSendReward.class.php [do|check] rewardType");
			return;
		}
		
		$doSend = false;
		if( $arrOption[0] == 'do')
		{
			$doSend = true;
		}
		
		$rewardType = $arrOption[1];
	
		if( $rewardType == 'support' )
		{
			$arrUid = array();
			if( isset( $arrOption[2] ) )
			{
				$arrUid = array( intval($arrOption[2]) );
			}
			$this->sendSupportRewardToAll($doSend, $arrUid);
		}
		else
		{
			Logger::fatal('invalid reward tyep:%s', $rewardType);
		}
		
		
		printf("done\n");
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */