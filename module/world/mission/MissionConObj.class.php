<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MissionConObj.class.php 202960 2015-10-17 12:15:32Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/mission/MissionConObj.class.php $
 * @author $Author: wuqilin $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-10-17 12:15:32 +0000 (Sat, 17 Oct 2015) $
 * @version $Revision: 202960 $
 * @brief 
 *  
 **/
class MissionConObj
{
	private static $instance = NULL;
	private $missConf = NULL;
	private $field = NULL;
	
	static function getInstance( $field )
	{
		if ( !isset( self::$instance[$field] ) ) 
		{
			self::$instance[$field] = new self( $field );
		}
		return self::$instance[$field];
	}
	
	static function release()
	{
		if( !empty( self::$instance ) )
		{
			self::$instance = null;
		}
	}
	function __construct( $field )
	{
		$this->field = $field;
		
		if( $this->field == MissionDef::FIELD_CROSS )
		{
			$conf = ActivityConfLogic::getConf4Backend(ActivityName::MISSION, 0);
		}
		else
		{
			$conf = EnActivity::getConfByName( ActivityName::MISSION );
		}
		$this->missConf = $conf;
		
		$this->refreshConf();
	}
	
	private function getConfFromDB( $sess )
	{
		if( $sess < MissionConf::FIRST_SESS )
		{
			return ActivityConfLogic::$NULL_CONF_BACKEND; 
		}
		$lastConf = MissionDao::getLastActConf($sess);
		if( empty( $lastConf[ MissionDBField::VA_MISSCONFIG ] ) )
		{
			return ActivityConfLogic::$NULL_CONF_BACKEND;
		}
		else 
		{
			return  $lastConf[ MissionDBField::VA_MISSCONFIG ];
		}
	}
	
	private function refreshConf()
	{
		if ( $this->missConf == ActivityConfLogic::$NULL_CONF_BACKEND || !isset( $this->missConf['data'][MissionCsvField::SESS] )  ) 
		{
			$this->setSessionInfo(0, 0, Util::getTime() + MissionConf::REF_GAP_TIME);
			return;
		}
		$doMissStartTime = $this->missConf['start_time'];
		$doMissEndTime = $this->getMissionEndTime();
		$this->setSessionInfo($doMissStartTime, $doMissEndTime, Util::getTime() + MissionConf::REF_GAP_TIME);
		
		$curTime = Util::getTime();
		$sess = $this->missConf['data'][MissionCsvField::SESS];
		if( $curTime < $this->missConf['start_time'] )
		{
			$lastConf =  $this->getConfFromDB($sess-1);
			$this->missConf = $lastConf;
		}
		else 
		{
			$lastConf =  $this->getConfFromDB($sess);
			if( $lastConf != $this->missConf)
			{
				$this->update();
			}
		}
	}
	
	public function setSessionInfo( $st,$ed,$nt )
	{
		$sessionInfo  = array(
				MissionDef::MISS_STT => $st,
				MissionDef::MISS_EDT => $ed,
				MissionDef::MISS_NTT => $nt,
		);
		RPCContext::getInstance()->setSession(MissionDef::MISSIONTIME_SESSION_KEY, $sessionInfo);
	}
	
	public function isConfValid()
	{
		return $this->missConf == ActivityConfLogic::$NULL_CONF_BACKEND? false:true;
	}
	
	private function update()
	{
		if( $this->field != MissionDef::FIELD_INNER )
		{
			Logger::fatal('not in inner machine, should not update');
			return;
		}
		$values = array( 
					MissionDBField::SESS => $this->missConf['data'][MissionCsvField::SESS],
					MissionDBField::VA_MISSCONFIG => $this->missConf,
					MissionDBField::CONF_UPDATE_TIME => Util::getTime(),
			 );
		MissionDao::updateInnerConfig($values);
	}
	
	public function getStartTime()
	{
		return $this->missConf['start_time'];
	}
	
	public function getSess()
	{
		$sess = 0;
		if( isset( $this->missConf['data'][MissionCsvField::SESS] ) )
		{
			$sess =  $this->missConf['data'][MissionCsvField::SESS];
		}
		return $sess;
	}

	public function getConfData($key)
	{
		if( isset(  $this->missConf['data'][$key]  ) )
		{
			return   $this->missConf['data'][$key];
		}
		else
			return array();
	}
	
	public function isMissionTime()
	{
		$curTime = Util::getTime();
		
		if( $this->isConfValid() )
		{
			$doMissionEndTime = $this->getMissionEndTime();
			if( $curTime <= $doMissionEndTime )
			{
				return true;
			}
		}
		return false;
	}
	
	public function getMissionEndTime()
	{
		$startTime = $this->missConf['start_time'];
		$doMissionLastTimeArr = $this->getConfData(MissionCsvField::MISSION_LASTTIME);
		
		$doMissionLastTime = 0;
		foreach ( $doMissionLastTimeArr as $index => $num )
		{
			if( isset( MissionDef::$missionTimeArr[$index] ) )
			{
				$doMissionLastTime += ($num*MissionDef::$missionTimeArr[$index]);
			}
			else
			{
				throw new ConfigException( 'invalid format for time: %s', $doMissionLastTimeArr );
			}
		}
		$doMissionEndTime = $startTime + $doMissionLastTime;
		
		return $doMissionEndTime;
	}
	
	public function isLevelOkForMission($lv)
	{
		$needLv = $this->getConfData( MissionCsvField::NEEDLV );
		if( $lv < $needLv )
		{
			return false;
		}
		return true;
	}
	
	public function getDonateNumMax()
	{
		return $this->getConfData( MissionCsvField::DONATE_ITEM_LIMIT );
	}
	
	public function isGoldLevelExist( $gold )
	{
		$goldLevelArr = $this->getConfData( MissionCsvField::DONATE_GOLD_RANGEARR );
		foreach ( $goldLevelArr as $goldLevel )
		{
			if( $gold == $goldLevel )
			{
				return true;
			}
		}
		
		return false;
	}
	
	public function getFamePerGold()
	{
		return $this->getConfData( MissionCsvField::FAME_PERGOLD );
	}
	
	public function isRewardTime()
	{
		
		if( !$this->isConfValid() )
		{
			Logger::debug('here1');
			return false;
		}
		if( $this->isMissionTime() )
		{
			Logger::debug('here2');
			return false;
		}
		$missionEndTime = $this->getMissionEndTime();
		if( Util::getTime() < $missionEndTime + 120 )
		{
			Logger::debug('here3');
			return false;
		}
		
		$conf = EnActivity::getConfByName(ActivityName::MISSION);
		if( !isset( $conf['start_time'] ) )
		{
			Logger::debug('here4');
			Logger::fatal('no conf?');
			return false;
		}
		$nextStartTime = $conf['start_time'];
		if( $nextStartTime >Util::getTime() && Util::getTime() + 120 >= $nextStartTime )
		{
			Logger::debug('here5');
			return false;
		}
		Logger::debug('here6');
		return true;
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
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */