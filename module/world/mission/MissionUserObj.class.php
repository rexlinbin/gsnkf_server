<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MissionUserObj.class.php 202423 2015-10-15 07:36:59Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/mission/MissionUserObj.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-10-15 07:36:59 +0000 (Thu, 15 Oct 2015) $
 * @version $Revision: 202423 $
 * @brief 
 *  
 **/
class MissionUserObj
{
	private static $instance = NULL;
	private $missUserInfo = NULL;
	private $missUserInfoBak = NULL;
	private $uid = NULL;
	
	private $MissionConfObj = NULL;
	
	static function getInstance($uid)
	{
		if( !isset( self::$instance[$uid] ) )
		{
			self::$instance[$uid] = new self( $uid );
		}
		return self::$instance[$uid];
	}
	
	static function releaseInstance( $uid )
	{
		if( isset( self::$instance[$uid] ) )
		{
			unset( self::$instance[$uid] );
		}
	}
	
	function __construct( $uid )
	{
		$guid = RPCContext::getInstance()->getUid();
		if( empty( $guid ) || $uid != $guid )
		{
			throw new InterException( 'invalid uid: %s, guid: %s', $uid, $guid );
		}
		$this->uid = $uid;
		$this->missUserInfo = RPCContext::getInstance()->getSession(MissionDef::MISS_USER_SESSKEY);
		if( empty( $this->missUserInfo ) )
		{
			$this->missUserInfo = MissionDao::getInnerUserInfo($uid);
			if( empty( $this->missUserInfo ) )
			{
				$this->missUserInfo = $this->init();
			}
			RPCContext::getInstance()->setSession(MissionDef::MISS_USER_SESSKEY, $this->missUserInfo);
		}
		$this->missUserInfoBak = $this->missUserInfo;
		
		$this->refresh();
		
	}
	
	private function init()
	{		
		$initArr = array(
				MissionDBField::UID => $this->uid ,
				MissionDBField::FAME => 0,
				MissionDBField::DONATE_ITEM_NUM => 0,
				MissionDBField::SPEC_MISS_FAME => 0,
				MissionDBField::UPDATE_TIME => Util::getTime(),
				MissionDBField::RANK_REWARD_TIME => 0,
				MissionDBField::DAY_REWATD_TIME => 0,
				MissionDBField::VA_MISSION_USER => array(),
		);
		
		MissionDao::insertInnerUserInfo($initArr);
		return $initArr;
	}
	
	private function refresh()
	{
		$this->MissionConfObj = MissionConObj::getInstance(MissionDef::FIELD_INNER);
		$rewardConf = array();
		if( $this->MissionConfObj->isConfValid() )
		{
			$startTime = $this->MissionConfObj->getStartTime();
			$missionEndTime = $this->MissionConfObj->getMissionEndTime();
			$checkTime = $missionEndTime + 120;
			if( Util::getTime() > $checkTime 
			&& $this->missUserInfo[MissionDBField::RANK_REWARD_TIME] < $startTime
			&& $this->missUserInfo[MissionDBField::UPDATE_TIME] > $startTime )
			{
				$rewardConf = $this->MissionConfObj->getConfData( MissionCsvField::RANK_REWARDARR );
				$this->missUserInfo[MissionDBField::RANK_REWARD_TIME] = Util::getTime();
				Logger::debug('send newer: %s', $rewardConf);
			}
			
			if( !Util::isSameDay( $this->missUserInfo[MissionDBField::UPDATE_TIME] ) )
			{
				$this->missUserInfo[MissionDBField::VA_MISSION_USER] = array();
				$this->missUserInfo[MissionDBField::SPEC_MISS_FAME] = 0;
			}
			
			if( $this->missUserInfo[MissionDBField::UPDATE_TIME] < $startTime )
			{
				$this->missUserInfo[MissionDBField::FAME] = 0;
				$this->missUserInfo[MissionDBField::DONATE_ITEM_NUM] = 0;
				$this->missUserInfo[MissionDBField::SPEC_MISS_FAME] = 0;
				$this->missUserInfo[MissionDBField::UPDATE_TIME] = Util::getTime();
				$this->missUserInfo[MissionDBField::DAY_REWATD_TIME] = 0;
				$this->missUserInfo[MissionDBField::VA_MISSION_USER] = array();
			}
		}

		if( !empty( $rewardConf ) )
		{
			if( $this->MissionConfObj->isRewardTime() )
			{
				$serverId = Util::getServerIdOfConnection();
				$pid = EnUser::getUserObj($this->uid)->getPid();
				$crossUserObj = MissionCrossUserObj::getInstance($pid, $serverId);
				$rank = $crossUserObj->getMyRank();
				foreach ( $rewardConf as $index => $rewardInfo)
				{
					if( $rank + 1 >= $rewardInfo[0] && $rank >=0 )
					{
						$rewardId = $rewardInfo[1];
					}
				}
				if( isset( $rewardId ) )
				{
					$rewardDetail = MissionConObj::getGeneralConf(MissionBts::MISSION_REWARD, $rewardId, MissionCsvField::REWARDARR);
					$rewardDetail= $rewardDetail->toArray();
					Logger::debug('missionRewardDetail:%s',$rewardDetail );
				}
			}
		}
		$this->update();
		if( isset( $rewardDetail ) )
		{
			Logger::info('uid: %s rewarddetail is: %s', $this->uid, $rewardDetail);
			RewardUtil::reward3DtoCenter($this->uid, array($rewardDetail), RewardSource::MISSION_RANK_REWARD, array( 'rank' => $rank+1 ));
		}
		
		//没有需要每天刷新的东西，除了每日的领奖时间
	}
	public function update()
	{
		if( $this->missUserInfoBak == $this->missUserInfo )
		{
			Logger::debug('nothing change');
			return;
		}
		
		$updateArr = array();
		foreach ( $this->missUserInfo as $key => $val )
		{
			if( $this->missUserInfoBak[$key] != $val )
			{
				$updateArr[$key] = $val;
			}
		}
		
		if( empty( $updateArr ) )
		{
			Logger::warning('nothing change, mudi:%s, origina:%s', $this->missUserInfo, $this->missUserInfoBak);
			return;
		}
		$updateArr[MissionDBField::UPDATE_TIME] = Util::getTime();
		MissionDao::updatInnerUserInfo($this->uid, $updateArr);
		$this->missUserInfo[MissionDBField::UPDATE_TIME] = Util::getTime();
		RPCContext::getInstance()->setSession(MissionDef::MISS_USER_SESSKEY, $this->missUserInfo);
		$this->missUserInfoBak = $this->missUserInfo;
	}
	
	public function getUserInfo()
	{
		return $this->missUserInfo;
	}
	
	public function getDayRewardTime()
	{
		return $this->missUserInfo[MissionDBField::DAY_REWATD_TIME];
	}
	
	public function getFame()
	{
		return $this->missUserInfo[MissionDBField::FAME];
	}
	
	public function addFame($gainFame)
	{
		$this->missUserInfo[MissionDBField::FAME] += $gainFame;
		return $this->missUserInfo[MissionDBField::FAME];
	}
	 
	public function addDonateItemNum($willDonateNum)
	{
		$this->missUserInfo[MissionDBField::DONATE_ITEM_NUM] += $willDonateNum;
	}
	
	public function isMissionFinish($type, $limitNum)
	{
		if( isset( $this->missUserInfo[MissionDBField::VA_MISSION_USER][MissionVAField::MISSION_INFO][$type]['num'] )
		&&  $this->missUserInfo[MissionDBField::VA_MISSION_USER][MissionVAField::MISSION_INFO][$type]['num'] >= $limitNum )
		{
			return true;
		}
		return false;
	}
	
	public function getUndoNum( $type, $limitNum )
	{
		$undoNum = $limitNum;
		if( isset( $this->missUserInfo[MissionDBField::VA_MISSION_USER][MissionVAField::MISSION_INFO][$type]['num'] ) )
		{
			$undoNum = $limitNum - $this->missUserInfo[MissionDBField::VA_MISSION_USER][MissionVAField::MISSION_INFO][$type]['num'];
			//return true;
		}
		if( $undoNum <= 0 )
		{
			$undoNum =0;
		}
		return $undoNum;
	}
	
	public function addSpecFame($gainFame)
	{
		$this->missUserInfo[MissionDBField::SPEC_MISS_FAME] += $gainFame;
	}
	
	public function doMission( $type, $data )
	{
		if(!is_numeric($data))
		{
			throw new InterException( 'not num:%s', $data );
		}
		
		$limitNum = MissionConObj::getGeneralConf(MissionBts::MISSION_DETAIL,$type, MissionCsvField::MAX_NUM );
		if( !isset( $this->missUserInfo[MissionDBField::VA_MISSION_USER][MissionVAField::MISSION_INFO][$type]['num'] ) )
		{
			$this->missUserInfo[MissionDBField::VA_MISSION_USER][MissionVAField::MISSION_INFO][$type]['num'] = 0;
		}
		$this->missUserInfo[MissionDBField::VA_MISSION_USER][MissionVAField::MISSION_INFO][$type]['num'] += $data;
		if($this->missUserInfo[MissionDBField::VA_MISSION_USER][MissionVAField::MISSION_INFO][$type]['num'] > $limitNum )
		{
			$this->missUserInfo[MissionDBField::VA_MISSION_USER][MissionVAField::MISSION_INFO][$type]['num']= $limitNum;
		}
	}
	
	public function setDayRewardTime($time)
	{
		$this->missUserInfo[MissionDBField::DAY_REWATD_TIME] = $time;
	}
	
	public function getDonateItemNum()
	{
		return $this->missUserInfo[MissionDBField::DONATE_ITEM_NUM];
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */