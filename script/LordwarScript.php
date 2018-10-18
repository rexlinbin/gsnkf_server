<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(wuqilin@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/


/**
 * 个人跨服战crontab脚本
 * 参数：  field[inner/cross]  op[runRound/rewardSupport/rewardPromotion/msg/prepareCross]
 * 
 * 
 * 服内
 * 00 19 * * * $BTSCRIPT $SCRIPT_ROOT/LordwarScript.php inner runRound
 * 
 * 服外
 * 00 19 * * * $BTSCRIPT $SCRIPT_ROOT/LordwarScript.php cross prepareCross
 * 00 19 * * * $BTSCRIPT $SCRIPT_ROOT/LordwarScript.php cross runRound
 * 
 * 
 * 
 * @author wuqilin
 *
 */


class MyFileLock
{
	protected	$mPath;
	protected 	$mHandle;
	//protected 	$mStatus;
	
	function __construct($path)
	{
		$this->mPath = $path;
	} 
	
	public function getHandle()
	{
		$this->mHandle = fopen ( $this->mPath, 'r' );
		if (empty ( $this->mHandle ))
		{
			throw new InterException( 'open file:%s failed', $this->mPath );
		}
		return $this->mHandle;
	}
	
	public function lock()
	{
		if( empty( $this->mHandle ) )
		{
			$this->getHandle();
		}
		$ret = flock ( $this->mHandle, LOCK_EX | LOCK_NB);
		
		Logger::debug('lock file:%s, ret:%d', $this->mPath, $ret);
		return $ret;
	}
	
	public function unlock()
	{
		if( empty( $this->mHandle ) )
		{
			throw new InterException('not lock, cant unlock');
		}
		
		$ret = flock ( $this->mHandle, LOCK_UN );
		if (! $ret)
		{
			throw new InterException('unlock failed. path:%s', $this->mPath );
		}
		return $ret;
	}
}


class LordwarScript extends BaseScript
{
	
	protected function executeScript ($arrOption)
	{
		
		if( count($arrOption) < 2 )
		{
			Logger::fatal('invalid param');
			return;
		}
		
		
		$field = $arrOption[0];
		$op = $arrOption[1];
		

		$group = RPCContext::getInstance()->getFramework()->getGroup();
		if( empty( $group )  )
		{
			$lockPath = __FILE__;
		}
		else 
		{
			$lockPath = CONF_ROOT.'/gsc/'.$group;
		}
		$lockObj = new MyFileLock($lockPath);
		if( $lockObj->lock() == false )
		{
			Logger::warning('some other process running, quit. field:%s, op:%s', $field, $op);
			return;
		}
		
		
		$force = false;
		
		if($field == LordwarField::CROSS )
		{
			RPCContext::getInstance()->getFramework()->setDb( LordwarUtil::getCrossDbName() );
			
			//跨服db上只有跨服活动的配置，这就导致db上的主干版本号始终低于平台版本号。
			$curVersion = ActivityConfLogic::getTrunkVersion();
			ActivityConfLogic::doRefreshConf($curVersion, true, false );
			LordwarConfMgr::getInstance(LordwarField::CROSS);
		}
		
		if( isset( $arrOption[2] ) )
		{
			$round = intval($arrOption[2]);
			$force = true;
			Logger::info('set round:%d', $round);
		}
		else 
		{
			$confMgr = LordwarConfMgr::getInstance($field);
			$curRoundByConf = $confMgr->getRound();
			if( $curRoundByConf == LordwarRound::OUT_RANGE )
			{
				Logger::info('no lordwar');
				return;
			}
			
			/*
			 	如果已经过了最后一个阶段超过一天的时间，就啥也不干了。
			 	最合理的方式其实应该是再加一个结束round，但是一开始没有考虑到这个问题，后续再加影响太大，所以就这么检查了
			 */
			if( $curRoundByConf >= LordwarRound::CROSS_2TO1 )
			{
				$lastRoundStartTime = $confMgr->getRoundStartTime($curRoundByConf);
				if( time() - $lastRoundStartTime >= SECONDS_OF_DAY )
				{
					Logger::info('last round:%d start time:%s. no work now', $curRoundByConf, date('Y-m-d H:i:s', $lastRoundStartTime) );
					return;
				}
			}
			
			$round = $curRoundByConf;
			Logger::info('get round from conf:%d', $curRoundByConf);
		}
		Logger::info('start lordwar. field:%s, op:%s, round:%d', $field, $op, $round);
		
		
		switch ($field)
		{
			case LordwarField::INNER:
				self::runInner($op, $round, $force);
				break;
			
			case LordwarField::CROSS;
				self::runCross($op, $round, $force);
				break;
			
			default:
				Logger::fatal('invalid field:%s', $field);
				break;
		}
		
		Logger::info('lordwar done. field:%s, op:%s, round:%d', $field, $op, $round);
		$lockObj->unlock();
		//printf("done\n");

	}
	
	public static function runInner($op, $round, $force)
	{
		$serverId = Util::getFirstServerIdOfGroup();
		$confMgr = LordwarConfMgr::getInstance();
		$sess = $confMgr->getSess();
		$teamMgr = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess);
		$myTeamId = $teamMgr->getTeamIdByServerId($serverId);
		if( $myTeamId < 0 )
		{
			Logger::info('this server not in any team. serverId:%d', $serverId);
			return;
		}
		
		
		if( $op == 'runRound' )
		{
			if( $round ==  LordwarRound::INNER_AUDITION)
			{
				LordwarLogic::audition(LordwarField::INNER, $force);
				LordwarLogic::push(LordwarField::INNER, LordwarPush::NOW_STATUS);
			}
			else if( in_array($round, LordwarRound::$INNER_PROMO) )
			{
				LordwarLogic::promotion(LordwarField::INNER, $round, $force);
			
				//服内助威奖励
				LordwarLogic::reward(LordwarField::INNER, LordwarReward::SUPPORT);
				
				LordwarLogic::push(LordwarField::INNER, LordwarPush::NOW_STATUS);
			}
			else
			{
				Logger::info('nothing to do inner. op:%s, round:%d', $op, $round);
			}
		}
		else if( $op == 'rewardPromotion' )
		{
			if( $round == LordwarRound::INNER_2TO1 )
			{
				//服内晋级赛排名奖励
				LordwarLogic::reward(LordwarField::INNER, LordwarReward::RPOMOTION);
				LordwarLogic::push(LordwarField::INNER, LordwarPush::NOW_STATUS);
			}
			else
			{
				Logger::info('nothing to do inner. op:%s, round:%d', $op, $round);
			}
		}
		else if( $op == 'rewardSupport')
		{
			if( in_array($round, LordwarRound::$CROSS_PROMO) )
			{
				//跨服助威奖励
				LordwarLogic::reward(LordwarField::INNER, LordwarReward::SUPPORT);
				//LordwarLogic::push(LordwarField::INNER, LordwarPush::NOW_STATUS);
				//发完奖，会推送，这里不推送了
			}
			else
			{
				Logger::info('nothing to do inner. op:%s, round:%d', $op, $round);
			}
		}
		else if( $op == 'checkReward' )
		{
			Logger::fatal('inner machine, no need to check the reward');
		}
		else if( $op == 'msg' )
		{
			//TODO
		}
		else
		{
			Logger::fatal('invalid op:%s', $op);
		}
	}
	
	public static function runCross($op, $round, $force)
	{
		
		if( $op == 'runRound' )
		{
			if( $round == LordwarRound::CROSS_AUDITION)
			{
				LordwarLogic::audition(LordwarField::CROSS, $force);
				LordwarLogic::push(LordwarField::CROSS, LordwarPush::NOW_STATUS);
			}
			else if( in_array($round, LordwarRound::$CROSS_PROMO) )
			{
				$return = LordwarLogic::promotion(LordwarField::CROSS, $round, $force);
				//shiuyu LordwarLogic::push(LordwarField::CROSS, LordwarPush::NOW_STATUS);
			}
			else
			{
				Logger::info('nothing to do cross. op:%s, round:%d', $op, $round);
			}
		}
		else if( $op == 'rewardPromotion' )
		{
			if( $round == LordwarRound::CROSS_2TO1 )
			{
				//跨服晋级赛排名奖励
				LordwarLogic::reward(LordwarField::CROSS, LordwarReward::RPOMOTION);
				LordwarLogic::push(LordwarField::CROSS, LordwarPush::NOW_STATUS);
			}
			else
			{
				Logger::info('nothing to do cross. op:%s, round:%d', $op, $round);
			}
		}
		else if( $op == 'rewardSupport')
		{
			Logger::fatal('cross machine, no need to rewardSupport');
		}
		else if( $op == 'checkReward' )
		{
			if( in_array($round, LordwarRound::$CROSS_PROMO) )
			{
				LordwarLogic::checkSupportRewardSendEndOnCross();
				LordwarLogic::push(LordwarField::CROSS, LordwarPush::NOW_STATUS);
			}
			else
			{
				Logger::info('nothing to do cross. op:%s, round:%d', $op, $round);
			}
		}
		else if( $op == 'msg' )
		{
			//TODO
		}
		else if( $op == 'prepareCross' )
		{
			if( $round == LordwarRound::INNER_2TO1 )
			{
				LordwarLogic::registerForCross();
			}
			else
			{
				Logger::info('nothing to do cross. op:%s, round:%d', $op, $round);
			}
			
		}
		else
		{
			Logger::fatal('invalid op:%s', $op);
		}
	}
}






/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */