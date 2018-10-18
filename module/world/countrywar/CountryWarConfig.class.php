<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarConfig.class.php 260482 2016-09-05 04:02:25Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/CountryWarConfig.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-09-05 04:02:25 +0000 (Mon, 05 Sep 2016) $
 * @version $Revision: 260482 $
 * @brief 
 *  处理配置相关，所有的时间配置，btstore配置都从这里获取，并作简单的处理，让外部用起来更方便
 *  至于这个处理做的多深再考虑下（比如要不要提供判定某个值是否符合配置的方法）
 *  
 **/
class CountryWarConfig
{	
	
	//////////////////时间阶段相关////////////////////////
	static function getStageByTime( $timeStamp )
	{
		$timeConf = self::getStampConf($timeStamp);
		$stage = 'invalid';
		$nearestConf = NULL;
		foreach ( $timeConf as $stageName => $stageConf )
		{
			if( $stageConf[1] <= $timeStamp  )
			{
				if( empty( $nearestConf ) || $stageConf[1]> $nearestConf[1]  )
				{
					$nearestConf = $stageConf;
					$stage = $stageName;
				}
			}
		}
		
		Logger::debug('stage and start time is: %s, %s', $stage, $nearestConf[1]);
		return $stage;
		
	}
	
	static function getStampConf( $timeStamp )
	{
		Logger::trace('function getStampConf begin ');
		$w = date( 'w', $timeStamp );
		if( $w == 0 )
		{
			$w = 7;
		}
		$weekdaysbetween = null;
		if( $w >CountryWarStage::VERY_BEGIN_WEEKDAY )
		{
			$weekdaysbetween = $w-CountryWarStage::VERY_BEGIN_WEEKDAY;
		}
		elseif ( $w <CountryWarStage::VERY_BEGIN_WEEKDAY )
		{
			$weekdaysbetween = $w+7-CountryWarStage::VERY_BEGIN_WEEKDAY;
		}
		else
		{
			$weekdaysbetween = 0;
		}
		
		$beginStamp = strtotime(date( "Ymd", $timeStamp - ($weekdaysbetween)*SECONDS_OF_DAY ).' '.CountryWarStage::VERY_BEGIN_TIME);
		if( $beginStamp > $timeStamp )
		{
			$beginStamp -= 7*SECONDS_OF_DAY;
		}
		Logger::debug('all time conf:%s',CountryWarStage::$ALL_STAGE);
		Logger::debug('beginstamp:%s',$beginStamp);
		foreach ( CountryWarStage::$ALL_STAGE as $stageName => $stageConf )
		{
			$convertConf[ $stageName ] = $stageConf;
			$convertConf[ $stageName ][1] = $beginStamp + $stageConf[0]*SECONDS_OF_DAY +  $stageConf[1];
		}
		Logger::debug('time conf to stamp: %s', $convertConf );
		
		return $convertConf;
	}
	
 	static function getStageStartTime( $timeStamp, $stage )
	{
		Logger::debug('function getStageStartTime begin');
		$timeConf = self::getStampConf($timeStamp);
		if( !isset( $timeConf[$stage] ) )
		{
			throw new InterException( 'invalid stage: %s', $stage );
		}
		
		Logger::debug('stage: %s, startTime:%s', $stage, $timeConf[$stage][1]);
		
		return $timeConf[$stage][1];
	} 

	static function timeConfig( $checkTime )
	{
		Logger::debug('function timeConfig begin');
		return array(
				CountryWarFrontField::TEAM_BEGIN => self::getStageStartTime($checkTime, CountryWarStage::TEAM),
				CountryWarFrontField::SIGN_BEGIN => self::getStageStartTime($checkTime, CountryWarStage::SINGUP),
				CountryWarFrontField::RANGE_BEGIN => self::getStageStartTime($checkTime, CountryWarStage::RANGE_ROOM),
				CountryWarFrontField::AUDITION_BEGIN => self::getStageStartTime($checkTime, CountryWarStage::AUDITION),
				CountryWarFrontField::SUPPORt_BEGIN => self::getStageStartTime($checkTime, CountryWarStage::SUPPORT),
				CountryWarFrontField::FINALTION_BEGIN => self::getStageStartTime($checkTime, CountryWarStage::FINALTION),
				CountryWarFrontField::WORSHIP_BEGIN => self::getStageStartTime($checkTime, CountryWarStage::WORSHIP),
		);
				
	}
	
	static function warId($checkTime)
	{
		return self::roundStartTime($checkTime);
	}
	static function roundStartTime($checkTime)
	{
		return self::getStageStartTime($checkTime,CountryWarStage::TEAM);
	}
	////////////////btstore相关////////////////////////
	static function battlePrepareSeconds()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::BATTLE_PREPARE_SECONDS );
		return $conf;
	}
	static function reqLevel()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::REQ_LEVEL );
		return $conf;
	}
	static function needOpenDays()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::REQ_OPEN_DAYS );
		return $conf;
	}
	static function battleMaxNum()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::BATTLE_MAX_NUM );
		return $conf;
	}
	static function countryAddition()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::COUNTRY_ADDITION_ARR );
		$conf = self::checkBtstore($conf);
		$addArr = array();
		foreach ( $conf as $index => $oneAdd )
		{
			if( !isset( $addArr[$oneAdd[0]] ) )
			{
				$addArr[$oneAdd[0]] = 0;
			}
			$addArr[$oneAdd[0]] += $oneAdd[1];
		}
		return $addArr;
	}
	static function signReward()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::SIGN_REWARD_ARR );
		$conf = self::checkBtstore($conf);
		return $conf;
	}
	static function inspireCocoin()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::INSPIRE_REQ_COCOIN );
		return $conf;
	}
	static function inspireAddition()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::INSPIRE_ADDITION_ARR );
		$conf = self::checkBtstore($conf);
		return $conf;
	}
	static function inspireLimit()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::INSPIRE_LIMIT );
		return $conf;
	}
	static function countrySupportReward()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::COUNTRY_SUPPORT_REWARD );
		$conf = self::checkBtstore($conf);
		return $conf;
	}
	static function memberSupportReward()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::MEMBER_SUPPORT_REWARD );
		$conf = self::checkBtstore($conf);
		return $conf;
	}
	static function randomCountryRatio()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::RANDOM_COUNTRY_RATIO );
		return $conf;
	}
	static function manualCountryRatioArr()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::MANUAL_COUNTRY_RATIO_ARR );
		$conf = self::checkBtstore($conf);
		return $conf;
	}
	
	static function qualifyNumPerAuditionBattle()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::COUNTRY_FINAL_MEMBERNUM );
		return $conf;
	}
	
	static function finalInitResource()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::FINAL_INIT_RESOURCE );
		return $conf;
	}
	static function touchdownRobResource()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::TOUCHDOWN_ROB_RESOURCE );
		return $conf;
	}
	static function joinPoint()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::JOIN_POINT );
		return $conf;
	}
	static function killPointArr()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::KILL_POINT_ARR );
		$conf = self::checkBtstore($conf);
		return $conf;
	}
	static function touchdownPoint()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::TOUCH_DOWN_POINT );
		return $conf;
	}
	static function terminalKillPointArr()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::TERMINAL_KILL_POINT_ARR );
		$conf = self::checkBtstore($conf);
		return $conf;
	}
	static function recoverCocoin()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::RECOVER_REQ_COCOIN );
		return $conf;
	}
	static function openTransferNum()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::OPEN_TRANSFER_REQ_NUM );
		return $conf;
	}
	
	static function joinCd()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::JOIN_CD );
		return $conf;
	}
	static function clearJoinCdCocoin()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::CLEAR_JOIN_CD_REQ_COCOIN );
		return $conf;
	}
	static function recoverRangeArr()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::RECOVER_RANGE_ARR );
		$conf = self::checkBtstore($conf);
		return $conf;
	}
	static function worshipReward()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::WORSHIP_REWARD_ARR );
		$conf = self::checkBtstore($conf);
		return $conf;
	}
	static function exchangeRatio()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::EXCHANGE_RATIO );
		return $conf;
	}
	
	static function roadNum()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::ROAD_ARR );
		return count($conf);
	}
	
	static function cocoinMax()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::COCOIN_MAX );
		return $conf;
	}
	
	static function roadConfig()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::ROAD_ARR );
		$conf = self::checkBtstore($conf);
		$moveSpeed = 1;
		$roadConfig = array();
		foreach ($conf as $index => $route)
		{
			$roadConfig[] = $route[1]*1000 * $moveSpeed;
		}
		
		return $roadConfig;
	}
	
	static function rankReward($stage,$rank)
	{
		$reward = array();
		if( $rank < 0 )
		{
			Logger::debug('no reward for rank:%s',$rank);
			return $reward;
		}
		$rank++;
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR_REWARD, $stage);
		foreach ( $conf as $id => $rewardInfo )
		{
			if( $rank >= $rewardInfo[CountryWarCsvField::RANK_MIN] && $rank <= $rewardInfo[CountryWarCsvField::RANK_MAX] )
			{
				$reward = $rewardInfo[CountryWarCsvField::REWARD_ARR];
				break;
			}
		}
		
		return $reward;
	}
	
	static function winSideReward()
	{
		$conf = self::getGeneralConf( CW_BTSTORE_NAME::COUNTRY_WAR, CountryWarCsvField::WINSIDE_REWARD_ARR );
		$conf = self::checkBtstore($conf);
		return $conf;
	}
	static function getGeneralConf()
	{
		$conf = array();
		$first = true;
		$argsArr = func_get_args();
		Logger::debug('try to get conf: %s', $argsArr);
		foreach ($argsArr as $n)
		{
			if( $first )
			{
				$conf = btstore_get()->$n;
				$first = false;
			}
			else
			{
				if( !isset( $conf[$n] ) )
				{
					throw new InterException('no such conf: %s', $n);
				}
				else
				{
					$conf = $conf[$n];
				}
			}
		}
	
		return $conf;
	}
	static function checkBtstore($checkObj)
	{
		$obj = $checkObj;
		if( $checkObj instanceof BtstoreElement )
		{
			$obj = $checkObj->toArray();
		}
		return $obj;
	}
	
	////////////////具有一定业务意义////////////////////////
	static function attackDefendArr()
	{
		$attackDefendArr = array(0,0,0);
		$inspireAdd = self::inspireAddition();
		foreach ( $inspireAdd as $index =>$additionArr )
		{
			foreach ( $additionArr as $oneAddition )
			{
 				if( $oneAddition[0] == 56 ) 
				{
					$attackDefendArr[0] += $oneAddition[1];
				}
				if( $oneAddition[0] == 14 )
				{
					$attackDefendArr[1] += $oneAddition[1];
				}
				if( $oneAddition[0] == 15 )
				{
					$attackDefendArr[2] += $oneAddition[1];
				} 
			}
		}
		
		return $attackDefendArr;
	}
	
	static function battleDuration($stage)
	{
		if( $stage == CountryWarStage::AUDITION )
		{
			$duration = self::getStageStartTime(Util::getTime(), CountryWarStage::SUPPORT) - self::getStageStartTime(Util::getTime(), CountryWarStage::AUDITION)-1;
		}
		if( $stage == CountryWarStage::FINALTION )
		{
			$duration = self::getStageStartTime(Util::getTime(), CountryWarStage::WORSHIP) - self::getStageStartTime(Util::getTime(), CountryWarStage::FINALTION)-1;
		}
	
		return $duration;
	}
	
	static function getCreateConfig( $stage )
	{
		$fieldConf = array
		(
				'refreshTimeMs' => 1000,								// 场景刷新时间(ms)
				'refreshOutMs' => 1000,									// 将场景数据刷新到前端的周期(ms)。目前需要refreshOutMs=refreshTimeMs，不配置也默认refreshOutMs=refreshTimeMs
				'roadNum' => self::roadNum(),							// 有几个通道
				'maxGroupSize' => 240,			    					// 每个阵营上的最大人数
				'maxGroupOnlineSize' => 240,							// 每个阵营中的最大在线人数
				'battleDuration' => self::battleDuration($stage),		// 战斗持续时间(s)，传给lcserver时间，包含准备时间和真正的战斗时间
				'prepareTime' => self::battlePrepareSeconds(),			// 战斗准备时间(s)
				'presenceIntervalMs' => 1000,       					// 传送阵到通道的时间间隔(ms)，这个值必须是refreshTimeMs的整数倍
				'joinCdTime' => self::joinCd(),							// 战败或者占领粮仓后，重新出战冷却时间(s)
				'joinReadyTime' => self::joinCd(),						// 退出战场后，重新进入战场冷却时间(s)
				'maxWaitQueue' => 100,             						// 传送阵中最大等待人数
				'speed' => 1,              								// 战斗单位移动速度!!!每个ms移动的距离
				'roadLength' => self::roadConfig(),					// 通道长度
				'collisionRange' => 1000,           					// 检测碰撞的范围
				'addRoadThr' => self::openTransferNum(),				// 场内达到这个人数后，就通知前端增加通道
				'robLimit' => self::finalInitResource()*2,         		// 能够抢多的粮草数量上限
				'robSpeed' => self::touchdownRobResource(),            	// 抢粮的速度，既每次达阵能够获得的粮草数
					
				'battleEndCondition' => array		// 战斗结束条件
				(
						'dummy' => true
				),
	
				'battleExtra' => array				// 战斗额外配置
				(
						'dummy' => true,
						'isPvp' => 1,
				),
					
				'replayConf' => array				// 战报相关配置
				(
						'bgId' => 0,
						'type' => 0,
						'musicId' => 0,
				),
		);
	
		$battleInfo = array
		(
				'fieldConf' => $fieldConf,			// 战场配置
				'attacker' => array					// 攻击者
				(
						'id' => 1,
						'name' => 'attacker',
						'totalMemberCount' =>1,
						'resource' => self::finalInitResource(),
				),
				'defender' => array					// 防守者
				(
						'id' => 2,
						'name' => 'defender',
						'totalMemberCount' => 1,
						'resource' => self::finalInitResource(),
				),
				'callMethods' => array				// 后端回调
				(
						'fightWin' => 'countrywarcross.onFightWin',
						'fightLose' => 'countrywarcross.onFightLose',
						'touchDown' => 'countrywarcross.onTouchDown',
						'battleEnd' => 'countrywarcross.onBattleEnd'
				),
				'frontCallbacks' => array			// 前端回调
				(
						'refresh' => PushInterfaceDef::COUNTRY_WAR_REFRESH,
						'fightWin' => PushInterfaceDef::COUNTRY_WAR_FIGHT_WIN,
						'fightLose' => PushInterfaceDef::COUNTRY_WAR_FIGHT_LOSE,
						'touchDown' => PushInterfaceDef::COUNTRY_WAR_TOUCH_DOWN,
						'fightResult' => PushInterfaceDef::COUNTRY_WAR_FIGHT_RESULT,
						'battleEnd' => PushInterfaceDef::COUNTRY_WAR_BATTLE_END,
						'reckon' => PushInterfaceDef::COUNTRY_WAR_RECKON,
				),
				'inspireConf' => array// 鼓舞相关
				(
						'maxAttackLevel' => self::inspireLimit(),
						'maxDefendLevel' => self::inspireLimit(),
						'attackDefendArr' => self::attackDefendArr(),
				),	
		);
	
		Logger::trace("getCreateConfig:%s", $battleInfo);
		return $battleInfo;
	}
	
	
	static function killPoint( $killNum )
	{
		if( empty($killNum) )
		{
			return 0;
		}
		$killPointArr = self::killPointArr();
		foreach ( $killPointArr as $index => $oneArr )
		{
			if( $killNum >= $oneArr[0] )
			{
				$point = $oneArr[1];
			}
		}
		if( !isset( $point ) )
		{
			throw new  ConfigException( 'no conf for killnum:%s',$killNum );
		}
		return $point;
	}
	
	
	static function terminalKillPoint( $terminalKillNum )
	{
		$point = 0;
		if( empty( $terminalKillNum ) )
		{
			return $point;
		}
		$killPointArr = self::terminalKillPointArr();
		foreach ( $killPointArr as $index => $oneArr )
		{
			if( $terminalKillNum >= $oneArr[0] )
			{
				$point = $oneArr[1];
			}
		}
		return $point;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */