<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RouletteLogic.class.php 246293 2016-06-14 07:18:30Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/roulette/RouletteLogic.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2016-06-14 07:18:30 +0000 (Tue, 14 Jun 2016) $
 * @version $Revision: 246293 $
 * @brief 
 *  
 **/
class RouletteLogic
{
	public static function getRouletteInfo($uid)
	{
		if (FALSE == EnActivity::isOpen(ActivityName::ROULETTE))
		{
			throw new FakeException('Activity roulette is not open!');
		}
		
		$myRoulette = MyRoulette::getInstance($uid);
		$rouletteInfo = $myRoulette->getRouletteInfo();
		
		$rouletteInfo['isReceived'] = RouletteDef::NOT_RECEIVED;
		if (isset($rouletteInfo[RouletteDef::SQL_VA_BOX_REWARD][RouletteDef::SQL_IS_RANK_REWARDED])
				&& $rouletteInfo[RouletteDef::SQL_VA_BOX_REWARD][RouletteDef::SQL_IS_RANK_REWARDED] > self::getActStartTime())
		{
			$rouletteInfo['isReceived'] = RouletteDef::HAS_RECEIVED;
		}
		
		//生成宝箱状态给前端用
		$arrRewarded = $myRoulette->getArrRewarded();
		$maxRewardIndex = self::getBoxNum( $myRoulette->getRouletteIntegeral() );
		$arrBoxStatus = array();
		
		$integeralBox = self::getIntegeralBox();
		$sumBoxNum = count($integeralBox);
		for ($i = 0; $i < $sumBoxNum; $i++)
		{
			$index = $i + 1;
			$status = 1;//不可领
			if ( in_array($index, $arrRewarded) )
			{
				$status = 3; //已领 
			}
			else if ( $index <= $maxRewardIndex )
			{
				$status = 2;//可领
			}
			$arrBoxStatus[$i] = array('status' => $status);
		}
		$rouletteInfo[RouletteDef::SQL_VA_BOX_REWARD] = $arrBoxStatus;
		//这个字段名（SQL_VA_BOX_REWARD）不合适，是联调完再修改的代码，保持这样，如果后续有新需求再修改

		
		$myRoulette->save();
		
		unset($rouletteInfo['accum_free_num']);
		unset($rouletteInfo['last_refresh_time']);
		unset($rouletteInfo[RouletteDef::SQL_LAST_ROLL_TIME]);
		
		return $rouletteInfo;
	}
	
	/**
	 * 积分轮盘抽奖
	 * @param int $num   次数 
	 * @param int $uid   uid
	 * @throws FakeException
	 * @return array     返回奖品列表，包括奖品类型，物品id以及数量
	 */
	public static function rollRoulette($num,$uid)
	{
		$conf = EnActivity::getConfByName(ActivityName::ROULETTE);
		if ( !isset($conf['data']['drops']) || empty($conf['data']['drops']) )
		{
			throw new ConfigException('@cehua: Need new conf.');
		}
		
		if($num <= 0)
		{
			throw new FakeException('Wrong num!. Num is zero or negative number.');
		}
		
		if (FALSE == EnActivity::isOpen(ActivityName::ROULETTE))
		{
			throw new FakeException('Activity roulette is not open.');
		}
		
		$day = EnActivity::getActivityDay(ActivityName::ROULETTE);
		
		//对更新时正开着积分轮盘的兼容
		$rollDay = 0;
		if (isset($conf['data'][RouletteDef::BTSTORE_ROULETTE_ROLL_DAY]))
		{
			$rollDay = $conf['data'][RouletteDef::BTSTORE_ROULETTE_ROLL_DAY];
			$rollStartTime = intval(strtotime(date("Y-m-d",self::getActStartTime())));
			$rollEndTime = $rollStartTime + $rollDay * SECONDS_OF_DAY - 1;
		}
		if (empty( $rollDay ) )
		{
			$rollEndTime = self::getActEndTime();
		}
		
		$time = Util::getTime();
		if ($time >= $rollEndTime)
		{
			throw new FakeException('Now: %d,RouletteEndTime %d,can not roulette.',$time,$rollEndTime);
		}
/* 150724 去掉金币抽奖最大次数限制
		if (FALSE == self::isNumPermit($uid, $num))
		{
			throw new FakeException('Roulette num is beyond limit.');
		}
*/		
		//前端也是检查所有背包的。其实此处检查多余
		if (FALSE == self::isBagPermit($uid))
		{
			throw new FakeException('Bag is full. Can not roulette!');
		}
		
		$rouletteInstance = MyRoulette::getInstance($uid);
		$bag = BagManager::getInstance()->getBag($uid);

		$ret = array();
		$rewardArr = array();
		//循环转
		for ($i = 0; $i < $num; $i++)
		{
			
			$dropId = self::rouletteOnce($uid);
			$rouletteOnceRet = self::getArrDropItems($dropId);
			
			$onceGetKey = Util::noBackSample($rouletteOnceRet, 1);
			
			$onceGet = $rouletteOnceRet[$onceGetKey[0]];
			unset($onceGet['weight']);
			
			Logger::info('User %d rouletteonce. DropId %d. OnceGet %s.',$uid,$dropId,$onceGet);
			
			$rewardArr[] = array(
					$onceGet['type'],
					$onceGet['id'],
					$onceGet['num'],
			);
			
			$ret[$i] = array(
					'type' => $onceGet['type'],
					'id' => $onceGet['id'],
					'num' => $onceGet['num'],
					'point' => $dropId + 1,
			);
		}
		
		Logger::info('User %d roulette %d.Reward %s.RetFont %s ',$uid, $num,$rewardArr, $ret);
	
		$rouletteInstance->updateRollTime();
		$rouletteInstance->save();
		
		$res = RewardUtil::reward3DArr($uid, $rewardArr, StatisticsDef::ST_FUNCKEY_ROULETTE_REWARD);
		EnUser::getUserObj($uid)->update();
		$bag->update();
		
		RPCContext::getInstance()->executeTask(SPECIAL_UID::RFR_ROULETTEINFO_INMC, 'roulette.checkRewardTimer', array());
		
		EnFestivalAct::notify($uid, FestivalActDef::TASK_SCORE_WHEEL_POINT, $rouletteInstance->getRouletteIntegeral());
		
		return $ret;
	}
	
	/**
	 * 转一次
	 * @param int $uid
	 * @throws FakeException
	 * @return array 返回一次抽奖的结果 （物品类型，物品id以及数量）
	 */
	public static function rouletteOnce($uid)
	{
		
		//今日已用免费次数和今日总免费次数 & 总已用金币次数和总金币次数
		$rouletteInstance = MyRoulette::getInstance($uid);
		
		$totalFreeNum = self::getTotalFreeNumByUid($uid);
		$freeRouletteNum = $rouletteInstance->getDayFreeRouletteNum();
/*		
		$totalGoldNum = self::getTotalGoldNumByUid($uid);
		$goldRouletteNum = $rouletteInstance->getAccumGoldRouletteNum();
*/		
		$bag = BagManager::getInstance()->getBag($uid);
		
		$integeral = self::getIntegeral();
		
		if ( $freeRouletteNum < $totalFreeNum )
		{
			$rouletteInstance->rouletteFreeOnce();
			$rouletteInstance->addIntegeral($integeral);
		}
		else
		{
			$rouletteInstance->rouletteGoldOnce();
			$rouletteInstance->addIntegeral($integeral);
			
			$needGold = self::getRouletteNeedGold();
			if (FALSE == EnUser::getUserObj($uid)->subGold($needGold,StatisticsDef::ST_FUNCKEY_ROULETTE_GOLD_ROULETTE))
			{
				throw new FakeException('Roulette gold failed.');
			}
		}
		
		$accumFreeNum = $rouletteInstance->getAccumFreeRouletteNum();
		$accumGoldNum = $rouletteInstance->getAccumGoldRouletteNum();
		
		$totalNum = $accumFreeNum + $accumGoldNum;
		
		$accumDropList = self::getAccumDrop();
		
		$dropId = NULL;
		$sumTeam = count(self::getArrDropId());
		
		foreach ($accumDropList as $accumNum => $index)
		{
			if ($accumNum == $totalNum)
			{
				$teamNum = self::getTeamNumByAccumNum($totalNum);
				$dropId = $teamNum - 1;
				
				if ( $dropId < 0 || $dropId >= $sumTeam)
				{
					throw new ConfigException('@cehua, accum drop err, $dropId %d.',$dropId);
				}
				
				break;
			}
		}
		
		if ( $dropId === NULL )
		{
			$dropId = NULL;
			
			$arrDropId = self::getArrDropId();
			
			if (empty($arrDropId))
			{
				throw new FakeException('Drop id is empty array.');
			}
			
			foreach ($arrDropId as $key => $index)
			{
				$arrDropId[$key]['weight'] = self::getWeightByTeamNum($key+1);
			}
			
			$arrDropdeId = Util::nobackSample($arrDropId, 1);
			
			if(empty($arrDropdeId))
			{
				throw new FakeException('Drop no drop id. ArrDropInfo %s.',$arrDropId);
			}
			
			$dropId = $arrDropdeId[0];
		}
		
//		$arrDropGot = self::getDropInfoByIndexNum($dropId);
		
		return $dropId;
	}
	
	public static function receiveBoxReward($num,$uid)
	{
		if (FALSE == self::isBagPermit($uid))
		{
			throw new FakeException('Bag is full. Can not receive!');
		}
		
		$bagInfo = BagManager::getInstance()->getBag($uid);
		$boxReward = self::getBoxRewardInfoByBoxNum($num);
		$rewardArr = $boxReward;
		
		$rouletteInstance = MyRoulette::getInstance($uid);
		
		$rouletteInstance->receiveReward($num);
		
		$rouletteInstance->save();
		
		$res = RewardUtil::reward3DArr($uid, $rewardArr, StatisticsDef::ST_FUNCKEY_ROULETTE_BOX_REWARD);
		EnUser::getUserObj($uid)->update();
		$bagInfo->update();
		
		return 'ok';
	}
	
	public static function getActStartTime()
	{
		$ret = EnActivity::getConfByName(ActivityName::ROULETTE);
		return $ret['start_time'];
	}
	
	public static function getActEndTime()
	{
		$ret = EnActivity::getConfByName(ActivityName::ROULETTE);
		return $ret['end_time'];
	}
	
	//积分轮盘金币抽奖一次所需金币数
	public static function getRouletteNeedGold()
	{
		$ret = EnActivity::getConfByName(ActivityName::ROULETTE);
		return $ret['data'][RouletteDef::BTSTORE_ROULETTE_NEED_GOLD];
	}
	
	//每日总免费限制次数
	public static function getTotalFreeNumByUid($uid)
	{
		$vip = EnUser::getUserObj($uid)->getVip();
		$freeNum = btstore_get()->VIP[$vip]['rouletteFreeNum'];
		return $freeNum;
	}
	
	//总金币限制次数
	public static function getTotalGoldNumByUid($uid)
	{
		$vip = EnUser::getUserObj($uid)->getVip();
		$totalNum = btstore_get()->VIP[$vip]['rouletteTotalNum'];
		return $totalNum;
	}
	
	//是否还有次数
	public static function isNumPermit($uid,$num)
	{
		$totalDayFreeNum = self::getTotalFreeNumByUid($uid);
		$rouletteInstance = MyRoulette::getInstance($uid);
		$todayAccumFreeNum = $rouletteInstance->getDayFreeRouletteNum();
		
		$totalSumGoldNum = self::getTotalGoldNumByUid($uid);
		$totalAccumGoldNum = $rouletteInstance->getAccumGoldRouletteNum();
		
		if (($totalAccumGoldNum + $todayAccumFreeNum + $num) <= ($totalDayFreeNum + $totalSumGoldNum) )
		{
			return TRUE;
		}
		return FALSE;
	}
	
	//背包是否满
	private static function isBagPermit($uid)
	{
		if (BagManager::getInstance()->getBag($uid)->isFull())
		{
			Logger::warning('Bag is full. Can not roll roulette.');
			return FALSE;
		}
		return TRUE;
	}
	
	//获得大轮信息
	private static function getArrDropId()
	{
		$ret = EnActivity::getConfByName(ActivityName::ROULETTE);
		return $ret['data'][RouletteDef::BTSTORE_ROULETTE_REWARD];
	}
	
	//根据索引获得掉落物品组
	private static function getArrDropItems($key)
	{
		$ret = EnActivity::getConfByName(ActivityName::ROULETTE);
		return $ret['data']['drops'][$key];
	}
	
	//一次抽奖的积分
	private static function getIntegeral()
	{
		$ret = EnActivity::getConfByName(ActivityName::ROULETTE);
		return $ret['data'][RouletteDef::BTSTORE_ROULETTE_INTEGERAL];
	}
	
	//权重表
	private static function getArrWeight()
	{
		$ret = EnActivity::getConfByName(ActivityName::ROULETTE);
		return $ret['data'][RouletteDef::BTSTORE_ROULETTE_FIELD_WEIGHT];
	}
	
	//根据组别得到权重
	private static function getWeightByTeamNum($teamNum)
	{
		$arrWeight = self::getArrWeight();
		return $arrWeight[$teamNum];
	}
	
	//消费次数-确定掉落组表
	private static function getAccumDrop()
	{
		$ret = EnActivity::getConfByName(ActivityName::ROULETTE);
		return $ret['data'][RouletteDef::BTSTORE_ROULETTE_ACCUM_DROP];
	}
	
	//根据已用次数获得确定掉落物品组别
	private static function getTeamNumByAccumNum($accumNum)
	{
		$dropInfo = self::getAccumDrop();
		return $dropInfo[$accumNum];
	}
	
	//根据组别确定具体掉落物品
	private static function getDropIdByTeamNum($teamNum)
	{
		$arrDropId = self::getArrDropId();
		return $arrDropId[$teamNum-1];
	}
	
	//根据索引获得要掉落的组别
	private static function getDropInfoByIndexNum($indexNum)
	{
		$dropInfo = self::getArrDropId();
		return $dropInfo[$indexNum];
	}
	
	//根据箱子号获取奖励物品
	private static function getBoxRewardInfoByBoxNum($num)
	{
		$ret = EnActivity::getConfByName(ActivityName::ROULETTE);
		$boxInfo = $ret['data']['box_reward'];
		
		if (1 == $num)
		{
			return $boxInfo[0];
		}
		elseif (2 == $num)
		{
			return $boxInfo[1];
		}
		elseif (3 == $num)
		{
			return $boxInfo[2];
		}
		else 
		{
			throw new FakeException('Wrong BoxNum : %d',$num);
		}
	}
	
	//根据积分-宝箱对应
	private static function getIntegeralBox()
	{
		$ret = EnActivity::getConfByName(ActivityName::ROULETTE);
		return $ret['data']['box_integeral'];
	}
	
	//根据积分获得可开启箱子的最大箱号
	public static function getBoxNum($integeral)
	{
		$boxIntegeral = self::getIntegeralBox();
		$ret = 0;
		foreach ($boxIntegeral as $key => $index)
		{
			if ($integeral >= $index)
			{
				$ret = $key;
			}
		}
		return $ret;
	}
	
	public static function getRankList($uid = 0)
	{
		if (empty($uid))
		{
			$uid = RPCContext::getInstance()->getUid();
			if ( empty( $uid ) )
			{
				throw new FakeException( 'invalid uid: %d', $uid );
			}
		}
		$startTime = self::getActStartTime();
		$serverNum = self::getServerNum();
		$limit = RouletteDef::RANK_LIST_NUM * $serverNum;
		$retRankInfo = RouletteDao::getRankList($uid, $startTime, $limit);
		
		$arrUid = Util::arrayExtract($retRankInfo, 'uid');
		$arrUserInfo = EnUser::getArrUserBasicInfo($arrUid, array('uid','uname','htid','level','dress','vip'));
		
		$arrUserGuild = EnGuild::getMultiMember($arrUid, array(GuildDef::GUILD_ID));
		
		$arrGuildId = array();
		foreach ($arrUserGuild as $uid => $value)
		{
			$arrGuildId[] = $value[GuildDef::GUILD_ID];
		}
		
		$arrGuildName = EnGuild::getArrGuildInfo($arrGuildId,array('guild_name'));
		
		$arrPoint = Util::arrayIndexCol($retRankInfo, 'uid', RouletteDef::SQL_ACHIEVE_INTEGERAL);
		
		$arrRankList = array();
		$user = array();
		
		foreach ($retRankInfo as $key => $value)
		{
			$user = $value;
			
			$uid = $value['uid'];
			
			$guildId = empty($arrUserGuild[$uid][GuildDef::GUILD_ID])?0:$arrUserGuild[$uid][GuildDef::GUILD_ID];
			
			if (!empty($guildId))
			{
				$user['guild_name'] = $arrGuildName[$guildId][GuildDef::GUILD_NAME];
			}
			
			$user['name'] = $arrUserInfo[$uid]['uname'];
			$user['htid'] = $arrUserInfo[$uid]['htid'];
			$user['level'] = $arrUserInfo[$uid]['level'];
			$user['dressInfo'] = $arrUserInfo[$uid]['dress'];
			$user['vip'] = $arrUserInfo[$uid]['vip'];
			
			$user['integeral'] = $arrPoint[$uid];
			
			$user['rank'] = $key + 1;
			
			$arrRankList[$key] = $user;
		}
		
		$uid = RPCContext::getInstance()->getUid();
		$userRank = self::getUserRank($uid);
		
		return array(
				'rank' => $userRank,
				'list' => $arrRankList
		);
	}
	
	public static function getUserRank($uid)
	{
		$myRoulette = MyRoulette::getInstance($uid);
		$rouletteInfo = $myRoulette->getRouletteInfo();
		
		$point = $rouletteInfo[RouletteDef::SQL_ACHIEVE_INTEGERAL];
		$rollTime = $rouletteInfo[RouletteDef::SQL_LAST_ROLL_TIME];
		$rfrTime = $rouletteInfo[RouletteDef::SQL_LAST_RFR_TIME];
		$startTime = self::getActStartTime();
		
		$ret = 0;
		if (0 == $point)
		{
			return $ret;
		}
		
		$ret = RouletteDao::getRankByPointAndTime($uid, $point, $rollTime, $startTime);
		
		return $ret;
	}
	
// 	public static function receiveRankReward($uid = 0)
// 	{
// 		if (empty($uid))
// 		{
// 			$uid = RPCContext::getInstance()->getUid();
// 			if ( empty( $uid ) )
// 			{
// 				throw new FakeException( 'invalid uid: %d', $uid );
// 			}
// 		}
		
// 		if (FALSE == self::isBagPermit($uid))
// 		{
// 			throw new FakeException('Bag is full. Can not receive!');
// 		}
// 		//最小积分判断
// 		$actConf = EnActivity::getConfByName(ActivityName::ROULETTE);
// 		$minPoint = PHP_INT_MAX;
// 		if (isset($actConf['data'][RouletteDef::BTSTORE_ROULETTE_MIN_POINT]))
// 		{
// 			$minPoint = $actConf['data'][RouletteDef::BTSTORE_ROULETTE_MIN_POINT];
// 		}
		
// 		$rouletteInstance = MyRoulette::getInstance($uid);
// 		$rouletteInfo = $rouletteInstance->getRouletteInfo();
// 		$integeral = $rouletteInfo[RouletteDef::SQL_ACHIEVE_INTEGERAL];
		
// 		if ( $integeral < $minPoint)
// 		{
// 			throw new FakeException('User %d integeral %d , minPoint %d, not enough to receive rank reward.',$uid,$integeral,$minPoint);
// 		}
// 		//是否在领奖期判断
// 		if (!isset($actConf['data'][RouletteDef::BTSTORE_ROULETTE_ROLL_DAY]))
// 		{
// 			throw new FakeException('old config, can not receive reward.');
// 		}
// 		else 
// 		{
// 			$rollDay = $actConf['data'][RouletteDef::BTSTORE_ROULETTE_ROLL_DAY];
// 			$rollStartTime = intval(strtotime(date("Y-m-d",self::getActStartTime())));
// 			$rollEndTime = $rollStartTime + $rollDay * SECONDS_OF_DAY - 1;
			
// 			$now = Util::getTime();
// 			if ($now < $rollEndTime)
// 			{
// 				throw new FakeException('roll time! Can not receive reward.Now: %d, rollEndTime: %d',$now,$rollEndTime);
// 			}
			
// 			$rewardTime = self::getRewardTime();
// 			if ($now >= $rewardTime)
// 			{
// 				throw new FakeException('reward time! can not receive reward.Now:%d, rewardTime:%d.',$now,$rewardTime);
// 			}
// 		}
		
// 		//已经领过判断
// 		if (isset($rouletteInfo[RouletteDef::SQL_VA_BOX_REWARD][RouletteDef::SQL_IS_RANK_REWARDED])
// 				&& $rouletteInfo[RouletteDef::SQL_VA_BOX_REWARD][RouletteDef::SQL_IS_RANK_REWARDED] > self::getActStartTime())
// 		{
// 			throw new FakeException('user %d has received rank reward.time:%d',$uid,$rouletteInfo[RouletteDef::SQL_VA_BOX_REWARD]);
// 		}
		
// 		$startTime = self::getActStartTime();
// 		//使用自己模块getUserRank也可以，不过此处为了减少数据库访问次数就这么搞了
// 		$limit = RouletteDef::RANK_LIST_NUM;
// 		$retRankInfo = RouletteDao::getRankList($uid, $startTime, $limit);
		
// 		$rank = 0;
		
// 		foreach ($retRankInfo as $key => $value)
// 		{
// 			if ($uid == $value['uid'])
// 			{
// 				$rank = $key + 1;
// 			}
// 		}
		
// 		if (empty($rank))
// 		{
// 			throw new FakeException('User %d is not in rank list.',$uid);
// 		}
		
// 		//@cehua: WangC&BianH 执意奖励档位写死，只能这么搞了
// 		$arrRankLevel = RouletteDef::$rank_level;
// 		$level = -1;
// 		foreach ($arrRankLevel as $key => $value)
// 		{
// 			if ($rank <= $value)
// 			{
// 				$level = $key;
// 				break;
// 			}
// 		}
		
// 		if ($level < 0)
// 		{
// 			throw new FakeException('no reward . user %d rank %d.reward level %d.',$uid,$rank,$level);
// 		}
		
// 		$arrRankRewardConf = array();
// 		if (empty($actConf['data'][RouletteDef::BTSTORE_ROULETTE_RANK_REWARD]))
// 		{
// 			Logger::info('roulette rank reward is empty!');
// 			return 'ok';
// 		}
// 		else 
// 		{
// 			$arrRankRewardConf = $actConf['data'][RouletteDef::BTSTORE_ROULETTE_RANK_REWARD];
// 		}
// 		if (empty($arrRankRewardConf[$level]))
// 		{
// 			throw new FakeException('no level %d,level list has changed.',$level);
// 		}
		
// 		$arrReward = $arrRankRewardConf[$level];
		
// 		$rouletteInstance->receiveRankReward();
// 		$rouletteInstance->save();
		
// 		$bag = BagManager::getInstance()->getBag($uid);
// 		$ret = RewardUtil::reward3DArr($uid, $arrReward, StatisticsDef::ST_FUNCKEY_ROULETTE_RANK_REWARD);
		
// 		EnUser::getUserObj($uid)->update();
// 		$bag->update();
		
// 		return 'ok';
// 	}
	
	public static function checkRewardTimer()
	{
		if (FALSE == EnActivity::isOpen(ActivityName::ROULETTE))
		{
			Logger::info('Act roulette is not open, check timer return.');
			return ;
		}
		
		$conf = EnActivity::getConfByName(ActivityName::ROULETTE);
		
		$taskName = 'roulette.rewardUserBfClose';
		$startTime = $conf['start_time'];
		$endTime = $conf['end_time'];
		$rollDay = $conf['data'][RouletteDef::BTSTORE_ROULETTE_ROLL_DAY];
		
		$zeroStartTime = intval(strtotime(date("Y-m-d",$startTime)));
		$rollEndTime = $zeroStartTime + $rollDay * SECONDS_OF_DAY - 1;
		
		$rewardTime = $rollEndTime + 1 + RouletteDef::REWARD_HOUE_AF_ROLL_END * 3600;
		
		if ($rollEndTime > $rewardTime)
		{
			Logger::info('rollEndTime beyond rewardTime,no timer. rollEndTime:%d, rewardTime:%d.',$rollEndTime,$rewardTime);
			return ;
		}
		
		$ret = EnTimer::getArrTaskByName($taskName, array(TimerStatus::UNDO, TimerStatus::RETRY), $startTime);
		$findValid = FALSE;
		
		foreach ($ret as $index => $timer)
		{
			if($timer['status'] == TimerStatus::RETRY)
			{
				Logger::fatal('the timer %d is retry.but the roulette activity not end.',$timer['tid']);
				TimerTask::cancelTask($timer['tid']);
				continue;
			}
			
			if($timer['status'] == TimerStatus::UNDO)
			{
				if($timer['execute_time'] != $rewardTime)
				{
					Logger::fatal('invalid timer %d.execute_time %d',$timer['tid'],$timer['execute_time']);
					TimerTask::cancelTask($timer['tid']);
				}
				else if($findValid)
				{
					Logger::fatal('one more valid timer.timer %d.',$timer['tid']);
					TimerTask::cancelTask($timer['tid']);
				}
				else
				{
					Logger::trace('checkRewardTimer findvalid');
					$findValid = TRUE;
				}
			}
		}
		
		if($findValid == FALSE)
		{
			Logger::fatal('no valid timer.addTask for roulette.checkRewardTimer.');
			TimerTask::addTask(SPECIAL_UID::RFR_ROULETTEINFO_INMC,$rewardTime, $taskName, array());
		}
	}
	
	public static function getRewardTime()
	{
		$actEndTime = self::getActEndTime();
		$rewardTime = $actEndTime - RouletteDef::REWARD_BF_CLOSE;
		return $rewardTime;
	}
	
	public static function rewardUser()
	{			
		$conf = EnActivity::getConfByName(ActivityName::ROULETTE);
		
		$startTime = $conf['start_time'];
		$endTime = $conf['end_time'];
		$rollDay = $conf['data'][RouletteDef::BTSTORE_ROULETTE_ROLL_DAY];
		$minPoint = $conf['data'][RouletteDef::BTSTORE_ROULETTE_MIN_POINT];
		
		$zeroStartTime = intval(strtotime(date("Y-m-d",$startTime)));
		$rollEndTime = $zeroStartTime + $rollDay * SECONDS_OF_DAY - 1;
		if ($rollEndTime >= $endTime)
		{
			Logger::info('roll day is beyond endtime, maybe old config.');
			return ;
		}
		
		$rewardStartTime = $rollEndTime + 1 + RouletteDef::REWARD_HOUE_AF_ROLL_END * 3600;
		if ($rollEndTime >= $rewardStartTime)
		{
			throw new ConfigException('rewardTime is less than rollEndTime, rewardTime:%d, rollEndTime:%d.',$rewardStartTime,$rollEndTime);
		}
		
		$now = Util::getTime();
		if ($now < $rewardStartTime)
		{
			throw new InterException('Yet time to reward.');
		}
		
		$uid = 0;
		$serverNum = self::getServerNum();
		$limit = RouletteDef::RANK_LIST_NUM * $serverNum;
		$retRankInfo = RouletteDao::getRankList($uid, $startTime, $limit);
		
		foreach ($retRankInfo as $key => $userInfo)
		{
			try {
				if ($userInfo[RouletteDef::SQL_ACHIEVE_INTEGERAL] < $minPoint)
				{
					break;
				}
					
				if (isset($userInfo[RouletteDef::SQL_VA_BOX_REWARD][RouletteDef::SQL_IS_RANK_REWARDED])
						&& $userInfo[RouletteDef::SQL_VA_BOX_REWARD][RouletteDef::SQL_IS_RANK_REWARDED] > $startTime)
				{
					Logger::info('user %d has received rank reward.',$userInfo[RouletteDef::SQL_FIELD_UID]);
					continue;
				}
					
				$rank = $key + 1;
				$rankReward = self::getRankRewardByRank($userInfo[RouletteDef::SQL_FIELD_UID], $rank, $serverNum);
					
				$userInfo[RouletteDef::SQL_VA_BOX_REWARD][RouletteDef::SQL_IS_RANK_REWARDED] = Util::getTime();
				RouletteDao::updateRouletteInfo($userInfo[RouletteDef::SQL_FIELD_UID], $userInfo);
					
				$rankReward = array($rankReward);
				RewardUtil::reward3DtoCenter($userInfo[RouletteDef::SQL_FIELD_UID], $rankReward, RewardSource::ROULETTE_RANK_REWARD,array('rank'=>$rank));
					
				Logger::info('roulette.rewardUser. uid:%d,point:%d,rank:%d.reward:%s.',$userInfo[RouletteDef::SQL_FIELD_UID],$userInfo[RouletteDef::SQL_ACHIEVE_INTEGERAL],$rank,$rankReward);
			}
			catch (Exception $e)
			{
				Logger::info('roulette.rewardUser. reward user %d failed, message is %s.',$uid,$e->getMessage());
			}
		}
	}
	
	public static function getRankRewardByRank($uid, $rank, $serverNum)
	{
		$conf = EnActivity::getConfByName(ActivityName::ROULETTE);
		
		$arrRankLevel = RouletteDef::$rank_level;
		
		$level = -1;
		foreach ($arrRankLevel as $key => $value)
		{
			if ($rank <= $value * $serverNum)
			{
				$level = $key;
				break;
			}
		}
		
		if ($level < 0)
		{
			throw new FakeException('no reward . user %d rank %d.reward level %d. serverNum %d.',$uid,$rank,$level,$serverNum);
		}
		
		$rankReward = array();
		if(!empty($conf['data'][RouletteDef::BTSTORE_ROULETTE_RANK_REWARD][$level]))
		{
			$rankReward = $conf['data'][RouletteDef::BTSTORE_ROULETTE_RANK_REWARD][$level];
		}
		
		return $rankReward;
	}
	
	public static function getServerNum()
	{
		$conf = EnActivity::getConfByName(ActivityName::ROULETTE);
		
		$startTime = $conf['start_time'];
		$zeroStartTime = intval(strtotime(date("Y-m-d",$startTime)));
		
		$rollEndTime = self::getRollEndTime();
		
		$serverNum = EnMergeServer::getMergeServerCount($zeroStartTime, $rollEndTime);
		
		return $serverNum;
	}
	
	public static function getRollEndTime()
	{
		$conf = EnActivity::getConfByName(ActivityName::ROULETTE);
		
		$startTime = $conf['start_time'];
		$rollDay = $conf['data'][RouletteDef::BTSTORE_ROULETTE_ROLL_DAY];
		
		$zeroStartTime = intval(strtotime(date("Y-m-d",$startTime)));
		$rollEndTime = $zeroStartTime + $rollDay * SECONDS_OF_DAY - 1;
		
		return $rollEndTime;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */