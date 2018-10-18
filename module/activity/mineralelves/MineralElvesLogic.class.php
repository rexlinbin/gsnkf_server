<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MineralElvesLogic.class.php 257552 2016-08-22 03:08:18Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/mineralelves/MineralElvesLogic.class.php $
 * @author $Author: QingYao $(yaoqing@babeltime.com)
 * @date $Date: 2016-08-22 03:08:18 +0000 (Mon, 22 Aug 2016) $
 * @version $Revision: 257552 $
 * @brief 
 *  
 **/
class MineralElvesLogic
{
	public static function getSelfMineralElves($uid=0)
	{
		if ($uid==0)
		{
			return array();
		}
		return MineralElvesDao::getMineralElvesByUid($uid, Util::getTime());
	}
	
	public static function getMineralElves($time)
	{
		return MineralElvesDao::getMineralElves($time);
	}
	
	public static function getMineralElvesByDomainId($domain_id)
	{
		$info=MineralElvesDao::getMineralElvesByDomainId($domain_id,Util::getTime());
		if (empty($info))
		{
			try
			{
				self::checkMineralElvesNormal();
			}
			catch (Exception $e)
			{
				Logger::fatal('occur exception when checkMineralElvesNormal, exception[%s]', $e->getTraceAsString());
			}
			return array();
		}
		
		if (!empty($info['uid']))
		{
			$user=EnUser::getUserObj($info['uid']);
			$level=$user->getLevel();
			$uname=$user->getUname();
			$guildId=$user->getGuildId();
			$info['uname']=$uname;
			$info['level']=$level;
			if ($guildId!=0)
			{
				$guildObj = GuildObj::getInstance($guildId);
				$guildname=$guildObj->getGuildName();
				$info['guild_name']=$guildname;
			}
		}
		return array($info);
	}
	
	public static function occupyMineralElves($domain_id,$uid)
	{
		//看看此人有没有占了别的宝藏，至多只能占一个
		if (MineralElvesDao::getMineralElvesNumByUid($uid, Util::getTime())>0)
		{
			return 'occupylimit';
		}
		$locker=new Locker();
		$locker->lock(self::getLockName($domain_id));
		try {
			//没有精灵不能占
			$elvesInfo=MineralElvesDao::getMineralElvesByDomainId($domain_id,Util::getTime());
			if (empty($elvesInfo))
			{
				$locker->unlock(self::getLockName($domain_id));
				return 'noelves';
			}
			//不能占自己的，这种情况应该不会出现？
			if ($elvesInfo['uid']==$uid)
			{
				throw new FakeException('can not occupy when you already occupy it!');
			}
			//没人占过的话打NPC，有人占了的打占了的人
			$atkRet=self::fightForMineralElves($uid,$elvesInfo['uid']);
			$isSuccess = (BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'])?TRUE:FALSE;
			if ($isSuccess)
			{
				//赢了就换主改数据
				MineralElvesDao::updateMineralElves($domain_id, $uid);
				Logger::info('player:%d win capture:%d occupy domain_id:%d',$uid,$elvesInfo['uid'],$domain_id);
			}else
			{
				Logger::info('player:%d try to occupy capture:%d domain_id:%d but failed',$uid,$elvesInfo['uid'],$domain_id);
			}
			
		}catch (Exception $e)
		{
			$locker->unlock(self::getLockName($domain_id));
			throw $e; 
		}
		$locker->unlock(self::getLockName($domain_id));
		
		$user=EnUser::getUserObj($uid);
		if ($isSuccess)
		{
			$captureName=0;
			if (!empty($elvesInfo['uid']))
			{
				$capture = EnUser::getUserObj($elvesInfo['uid']);
				$captureName=$capture->getUname();
			}
			$beforeUid=$elvesInfo['uid'];
			$elvesInfo['uid']=$uid;
			$capture = EnUser::getUserObj($elvesInfo['uid']);
			$msgInfo=array(
					'domain_id'=>$domain_id,
					'pre_capture'=>$captureName,
					'now_capture'=>$user->getUname(),
					'rob_time'=>Util::getTime());
			//这个是跑马灯的消息
			$num=rand(0, 10000);
			if ($num<MineralElvesDef::MINERAL_ELVES_SEND_MSG_RAND)
			{
				RPCContext::getInstance()->sendMsg(array(0),PushInterfaceDef::MINERALELVES_ROB,$msgInfo);
			}else 
			{
				RPCContext::getInstance()->sendMsg(array($beforeUid,$uid),PushInterfaceDef::MINERALELVES_ROB,$msgInfo);
			}
			$uname=$user->getUname();
			$level=$user->getLevel();
			$guildId=$user->getGuildId();
			$elvesInfo['uname']=$uname;
			$elvesInfo['level']=$level;
			if ($guildId!=0)
			{
				$guildObj = GuildObj::getInstance($guildId);
				$guildname=$guildObj->getGuildName();
				$elvesInfo['guild_name']=$guildname;
			}
			//给前端发消息
			RPCContext::getInstance()->sendFilterMessage('arena',SPECIAL_ARENA_ID::MINERALELVES, PushInterfaceDef::MINERALELVES_UPDATE_ELVES, array($elvesInfo));
		}
	
		return array(
				'fight_ret'=>$atkRet['client'],
				'appraisal' => $atkRet['server']['appraisal'],
				'elves_info'=>$elvesInfo,
		);
	}
	
	public static function __genMineralElves($args)
	{
		$curtime=Util::getTime();
		if ($args['end_time']<$curtime)//如果这一轮都已经结束了就不生成了
		{
			return ;
		}
		try 
		{
			$conf=self::getMineralElvesConf();
			$zerotime=strtotime(date("Ymd", $curtime).'000000');
			$dayStartTime=$zerotime+$conf['start_time'];
			$dayEndTime=$zerotime+$conf['end_time'];
			//根据配置从每个矿区前 $conf['page'] 中随机出 $conf['num'] 个矿页
			$arrDomainId=self::getRandDomainIdArrOfElves($conf['page'], $conf['num']);
			foreach ($arrDomainId as $aDomainId)
			{
				MineralElvesDao::addMineralElves($args['start_time'], $args['end_time'], $aDomainId);
				Logger::info('elves show in domainid:%d starttime:%d endtime:%d',$aDomainId,$args['start_time'],$args['end_time']);
			}
		}
		catch (Exception $e)
		{
			Logger::fatal('occur exception when __genMineralElves, exception[%s]', $e->getTraceAsString());
		}
		//发奖的timer
		if (!self::findSendRewardUndoTimer('mineralelves.__sendMineralElvesPrize', $args['end_time'])) 
		{
			TimerTask::addTask(0, $args['end_time'], 'mineralelves.__sendMineralElvesPrize', array($args));
		}
		//计算下一轮的时间
		$nextStageStartTime=$args['end_time']+$conf['wait_time'];
		$nextStageEndTime=$nextStageStartTime+$conf['last_time'];
		if ($nextStageEndTime>$dayEndTime)
		{
			//说明今天的已经结束，算下下个周期的
			$nextStageStartTime=$dayStartTime+SECONDS_OF_DAY;
			while (!in_array(intval(date ( 'w', $nextStageStartTime)), $conf['week_day']))
			{
				$nextStageStartTime+=SECONDS_OF_DAY;
			}
			$nextStageEndTime=$nextStageStartTime+$conf['last_time'];
		}
		//活动结束的话就不弄了
		if ($nextStageEndTime>$args['act_end_time'])
		{
			Logger::warning('activity is not open!');
			return ;
		}
		$args=array(
				'start_time'=>$nextStageStartTime,
				'end_time'=>$nextStageEndTime,
				'act_start_time'=>$args['act_start_time'],
				'act_end_time'=>$args['act_end_time'],
		);
		TimerTask::addTask(0, $nextStageStartTime-30, 'mineralelves.__genMineralElves', array($args));
		
	}
	
	public static function __sendMineralElvesPrize($args)
	{
		try
		{
			//取出所有的
			$elvesInfo=self::getMineralElves($args['end_time']);
			//配置
			$conf=self::getMineralElvesConf();
			$rewardconf=$conf['reward'];
			//挨个发奖
			foreach ($elvesInfo as $info)
			{
				if ($info['uid']==0)
				{
					continue;
				}
				//现在每次只发一份奖励，以后说不定发多份？
				$rewardKeyArr=Util::noBackSample($rewardconf, 1);
				$rewardArr=array();
				foreach ($rewardKeyArr as $rewardKey)
				{
					$rewardinfo=$rewardconf[$rewardKey]['reward_info'];
					$rewardArr[]=$rewardinfo;
				}
				$reward = RewardUtil::format3DtoCenter($rewardArr);
				EnReward::sendReward($info['uid'], RewardSource::MINERALELVES, $reward);
				Logger::info('send mineral elves reward:%s for occupy info:%s',$rewardinfo,$info);
			}
		}
		catch (Exception $e)
		{
			Logger::fatal('occur exception when __sendMineralElvesPrize, exception[%s]', $e->getTraceAsString());
		}
		
	}
	
	
	public static function getMineralElvesConf()
	{
		if (!EnActivity::isOpen(ActivityName::MINERALELVES))
		{
			throw new FakeException('mineral elves not open!');
		}
		$conf = EnActivity::getConfByName( ActivityName::MINERALELVES );
		return $conf['data'];
	}
	
	private static function getLockName($domain_id)
	{
		return 'mineral_elves_lock'.$domain_id;
	}
	
	private static function fightForMineralElves($uid,$captureUid)
	{
		$user = EnUser::getUserObj($uid);
		$btFmt = $user->getBattleFormation();
	
		$armyBtFmt = array();
		$armyId = 0;
		$callback = array();
		$btType = 0;
		$winCon     = array();
		$extraInfo  = array();
		if (empty($captureUid))
		{
			$conf=self::getMineralElvesConf();
			$armyId=$conf['npc'];
			$armyBtFmt = EnFormation::getMonsterBattleFormation($armyId);
		}
		else
		{
			$capture = EnUser::getUserObj($captureUid);
			$armyBtFmt	=	$capture->getBattleFormation();
		}
		if(!empty($armyId))//打NPC部队
		{
			$btType = btstore_get()->ARMY[$armyId]['fight_type'];
			$winCon = CopyUtil::getVictoryConditions($armyId);
			$extraInfo = CopyUtil::getExtraBtInfo($armyId, BattleType::MINERAL);
			$atkRet = EnBattle::doHero($btFmt, $armyBtFmt, $btType, $callback, $winCon, $extraInfo);
		}
		else//打人
		{
			$player = EnUser::getUserObj($btFmt['uid']);
			$capture = EnUser::getUserObj($armyBtFmt['uid']);
			$type = EnBattle::setFirstAtk(0, $player->getFightForce() >= $capture->getFightForce());
			$atkRet = EnBattle::doHero($btFmt, $armyBtFmt, $type, $callback, $winCon, $extraInfo);
		}
		return $atkRet;
	}
	
	private static function getRandDomainIdArrOfElves($page,$num)
	{
		if ($page>20)
		{
			throw new FakeException('page:%d max',$page);
		}
		$sampleArr=array();
		$normalDomainId=10000;
		$highDomainId=50000;
		$goldDomainId=60000;
		for ($i=1;$i<=$page;$i++)
		{
			$sampleArr[++$normalDomainId]['weight']=100;
			$sampleArr[++$highDomainId]['weight']=100;
			$sampleArr[++$goldDomainId]['weight']=100;
		}
		$arrDomainIdArr=Util::noBackSample($sampleArr, $num);
		return $arrDomainIdArr;
	}
	
	public static function checkMineralElvesNormal()
	{
		//可能会由于停服等原因造成timer执行问题
		//检查当前时间是否是在活动期间
		$conf=self::getMineralElvesConf();
		//若是在活动期间，看看是正在矿期间还是等待期间
		$curtime=Util::getTime();
		if (!in_array(Util::getTodayWeek(), $conf['week_day']))
		{
			return ;
		}
		$zerotime=strtotime(date("Ymd", $curtime).'000000');
		$dayStartTime=$zerotime+$conf['start_time'];
		$dayEndTime=$zerotime+$conf['end_time'];
		if ($curtime<$dayStartTime||$curtime>$dayEndTime)
		{
			return ;
		}
		$actime=$curtime-$dayStartTime;
		//现在是第几轮
		$round=intval(floor($actime/($conf['last_time']+$conf['wait_time'])));
		//现在是等待时间还是宝藏时间
		if ($actime%($conf['last_time']+$conf['wait_time'])<$conf['last_time'])
		{
			$stageStartTime=$dayStartTime+$round*($conf['last_time']+$conf['wait_time']);
			$stageEndTime=$stageStartTime+$conf['last_time'];
			$args=array(
					'start_time'=>$stageStartTime,
					'end_time'=>$stageEndTime,
					'act_start_time'=>self::getActivityStartTime(),
					'act_end_time'=>self::getActivityEndTime(),
			);
			
			//此时应该是宝藏时间，应该有宝藏
			$elvesInfo=self::getMineralElves($curtime);
			//也应该有发奖timer
			$elvesPrizeTask=EnTimer::getArrTaskByName('mineralelves.__sendMineralElvesPrize',array(TimerStatus::UNDO),$curtime);
			
			// 宝藏和发奖timer都有
			if (!empty($elvesInfo)&&!empty($elvesPrizeTask))
			{
				//一切正常
				return;
			}
			
			if (empty($elvesInfo)) // 无宝藏，直接产生宝藏
			{
				if (!empty($elvesPrizeTask))
				{
					foreach ($elvesPrizeTask as $value)
					{
						TimerTask::cancelTask($value['tid']);
					}
				}
				
				self::__genMineralElves($args);
			}
			else// 有宝藏，无发奖timer，加上发奖timer
			{
				TimerTask::addTask(0, $args['end_time'], 'mineralelves.__sendMineralElvesPrize', array($args));
			}
		}
		else //这就是等待时间，要检查下一个timer
		{
			$nextStageStartTime=$dayStartTime+($round + 1)*($conf['last_time']+$conf['wait_time']);
			$nextStageEndTime=$nextStageStartTime+$conf['last_time'];
			
			if ($nextStageEndTime > $dayEndTime) 
			{
				return ;
			}
			
			$elvesInfo=self::getMineralElves($nextStageEndTime);
			if (empty($elvesInfo)) 
			{
				$elvesTaskArr=EnTimer::getArrTaskByName('mineralelves.__genMineralElves',array(TimerStatus::UNDO), $nextStageStartTime - 30);
				if (!empty($elvesTaskArr))
				{
					return ;
				}
				
				$args=array(
						'start_time'=>$nextStageStartTime,
						'end_time'=>$nextStageEndTime,
						'act_start_time'=>self::getActivityStartTime(),
					'act_end_time'=>self::getActivityEndTime(),
				);
				TimerTask::addTask(0, $nextStageStartTime-30, 'mineralelves.__genMineralElves', array($args));
			}
		}
	}
	
	private static function getActivityStartTime()
	{
		$config=EnActivity::getConfByName(ActivityName::MINERALELVES);
		return $config['start_time'];
	}
	private static function getActivityEndTime()
	{
		$config=EnActivity::getConfByName(ActivityName::MINERALELVES);
		return $config['end_time'];
	}
	
	public static function clearOccupyInfo($uid)
	{
		$elvesInfo=MineralElvesDao::getMineralElvesByUid($uid, Util::getTime());
		MineralElvesDao::updateMineralElves($elvesInfo['domain_id'], 0);
	}
	
	public static function findSendRewardUndoTimer($taskName, $startTime)
	{
		$ret = EnTimer::getArrTaskByName($taskName, array(TimerStatus::UNDO), $startTime);
		
		$findValid = FALSE;
		foreach ($ret as $index => $timer)
		{
			if($timer['execute_time'] != $startTime)
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
				Logger::fatal('checkSendRewardUndoTimer findvalid');
				$findValid = TRUE;
			}
		}
		
		return $findValid;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */