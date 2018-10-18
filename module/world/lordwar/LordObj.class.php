<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: LordObj.class.php 133782 2014-09-22 09:51:44Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/lordwar/LordObj.class.php $
 * @author $Author: wuqilin $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-09-22 09:51:44 +0000 (Mon, 22 Sep 2014) $
 * @version $Revision: 133782 $
 * @brief 
 *  
 **/
class LordObj 
{
	//来一个跨服对象
	private $lordInfo;
	private $lordInfoBak;
	private $pid;
	private $serverId;
	private static $instance = null;

	/**
	 * 获取唯一实例
	 * @return LordObj
	 */
	public static function getInstance( $serverId, $pid  )
	{
		$key = LordwarUtil::getKey($serverId, $pid);
		if ( !isset( self::$instance[$key] ))
		{
			self::$instance[$key] = new self( $serverId, $pid );
		}
		return self::$instance[$key];
	}
	
	public static function release( $serverId,$pid )
	{
		$key = LordwarUtil::getKey($serverId, $pid);
		//现在海选，晋级赛中是通过release，重新获取对象。来更新战斗数据的
		//if (isset( self::$instance[$key] ) && count(self::$instance) >= LordwarDef::MAX_CACHE_USER_NUM )
		if( isset( self::$instance[$key] ) )
		{
			unset( self::$instance[$key] );
		}
	}
	
	function __construct($serverId,$pid)
	{
		$isMyserver = LordwarUtil::isMyServer($serverId);
		
		$lordInfo = LordwarInnerDao::getLordInfo($serverId,$pid);
		if( $isMyserver )
		{
			if ( empty($lordInfo) )
			{
				$lordInfo = $this->initLord($serverId,$pid);
			}
		}
		else if( empty( $lordInfo ) )
		{
			throw new FakeException("not found obj serverId: %d, pid: %d!", $serverId, $pid);
		}
		
		$this->lordInfo = $lordInfo;
		$this->lordInfoBak = $lordInfo;
		$this->serverId = $serverId;
		$this->pid = $pid;
		$this->adaptRef();
	}
	
	public function initLord($serverId,$pid)
	{
		//只有报名的时候才修改服务器id
		$uid = RPCContext::getInstance()->getUid();
		if( empty($uid) )
		{
			throw new FakeException( 'user not online, can not init' );
		}		
		$initValues = array(
				'pid'=> $pid,
				'uid'=> $uid,
				'server_id' => $serverId,
				'winner_losenum' => 0,
				'loser_losenum'=> 0,
				'team_type' => 0,
				'support_pid' => 0,
				'support_serverid' => 0,
				'support_round' => 0,
				'worship_time' => 0,
				'update_fmt_time' => 0,
				'bless_receive_time' => 0,
				'register_time' => 0,
				'last_join_time' => 0,
				'va_lord' => array(),
				'va_lord_extra' => array(),
		);
		
		LordwarInnerDao::insertLord($initValues);
		
		return $initValues;
	}
	
	public function adaptRef()
	{
		//if empty
		$confMgr = LordwarConfMgr::getInstance();
		$startTime = $confMgr->getBaseConf( 'start_time' );
		if($this->lordInfo['last_join_time'] > $startTime)
		{
			Logger::debug('no need refresh');
			return;
		}
		
		/* if ( !$confMgr->isRegisterTime() || $confMgr->isRegisterTime( $this->lordInfo['register_time'] ) ) 
		{
			//历史数据是没有刷新的 如果你没有报名的话
			return;
		} */
		//否则，刷
		$this->lordInfo['winner_losenum'] = 0;
		$this->lordInfo['loser_losenum'] = 0;
		$this->lordInfo['team_type'] = 0;
		$this->lordInfo['support_pid'] = 0;
		$this->lordInfo['support_serverid'] = 0;
		$this->lordInfo['support_round'] = 0;
		$this->lordInfo['worship_time'] = 0;
		$this->lordInfo['update_fmt_time'] = 0;
		$this->lordInfo['bless_receive_time'] = Util::getTime();
		$this->lordInfo['register_time'] = 0;
		$this->lordInfo['last_join_time'] = Util::getTime();
		$this->lordInfo['va_lord'] = array();
		$this->lordInfo['va_lord_extra'] = array();
	}
	
	public function getLordInfo()
	{
		return $this->lordInfo;
	}
	
	public function getSupportRound()
	{
		return $this->lordInfo['support_round'];
	}
	
	public function getVa()
	{
		return $this->lordInfo['va_lord'];
	}
	
	public function getLordVaExtra()
	{
		return $this->lordInfo['va_lord_extra'];
	}
	
	public function getMySupport($saveChange = true)
	{
		if ( empty( $this->lordInfo['va_lord']['supportList'] ) ) 
		{
			return array();
		}
		
		$confMgr = LordwarConfMgr::getInstance();
		$sess = $confMgr->getSess();
		
		//$serverId = Util::getServerIdOfConnection();
		$serverId = $this->serverId;
		$teamId = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess)->getTeamIdByServerId($serverId);
		
		$list = $this->lordInfo['va_lord']['supportList'];
		$data_modifyed = false;
		foreach ( $list as $round => $data )
		{
			if ( !isset($data['win']) )
			{
				//这个地方最高效的方法是去获取最后一条打完的round数据。但是不好写，所以这么写
				$roundData = LordwarTeam::getTeamRoundData($teamId, $sess, $round);
				$needResult = 0;
				foreach ( $roundData as $teamType => $teamData )
				{
					if ( $teamData['status'] < LordwarStatus::FIGHTEND )
					{
						continue;
					}
					$lordArr = $teamData['va_procedure']['lordArr'];
			
					$needResult++;
					foreach ( $lordArr as $lord )
					{
						if ( $data['serverId'] == $lord['serverId'] &&
							$data['pid'] == $lord['pid'] )
						{
							$list[$round]['win'] = 
								$lord['rank'] == LordwarRound::$ROUND_RET_NUM[$round] ? 1 : 0;
							$data_modifyed = true;
							break;
						}
					}	
				}
				
				if( $needResult >= count(LordwarTeamType::$TEAM_TYPE_ALL) && !isset($list[$round]['win']) )
				{
					Logger::fatal('not found obj. serverId:%d, pid:%d', $data['serverId'], $data['pid']);
					$list[$round]['win'] = 0;
					$data_modifyed = true;
				}

			}
		}
		
		if ( $data_modifyed == true && $saveChange == true )
		{
			$this->lordInfo['va_lord']['supportList'] = $list;
			$this->update();
		}
		return $list;
	}
	
	public function support($round, $needInfo)
	{
		$this->lordInfo['support_serverid'] = $needInfo['serverId'];
		$this->lordInfo['support_pid'] = $needInfo['pid'];
		$this->lordInfo['support_round'] = $round;
		if( isset( $this->lordInfo['va_lord']['supportList'][$round] ) )
		{
			throw new FakeException( 'already support round: %d', $round );
		}
		$this->lordInfo['va_lord']['supportList'][$round] = $needInfo;
	}
	
	public function supportRewardEnd($round, $rewardTime)
	{
		if ( $this->lordInfo['support_round'] == $round )
		{
			$this->lordInfo['support_serverid'] = 0;
			$this->lordInfo['support_pid'] = 0;
			$this->lordInfo['support_round'] = 0;
		}
		else
		{
			Logger::warning('cur support round not match. curRound:%d, rewardRound:%d', $this->lordInfo['support_round'], $round);
		}
		$this->lordInfo['va_lord_extra']['supportList'][$round]['rewardTime'] = $rewardTime;
	}
	
	public function promotionRewardEnd($round, $rewardTime, $rank)
	{
		$this->lordInfo['va_lord_extra']['promotionList'][$round] = array(
				'rewardTime' => $rewardTime,
				'rank' => $rank,
		);
	}
	
	public function setFmtTime($time)
	{
		$this->lordInfo['update_fmt_time'] = $time;
	}
	
	public function setVa($va)
	{
		$this->lordInfo['va_lord'] = $va;
	}
	
	public function register()
	{
		$serverId = Util::getServerIdOfConnection();
		if( $serverId != $this->lordInfo['server_id'] )
		{
			Logger::fatal('now serverId:%s, init serverId: %s', $serverId,$this->lordInfo['server_id'] );
		} 
		$this->lordInfo['register_time'] = Util::getTime();
		$this->lordInfo['server_id'] = $serverId;
		
		//修改uid, 处理合服的情况
		$this->lordInfo['uid'] = RPCContext::getInstance()->getUid();
		
		$this->serverId = $serverId;
	}
	
	public function uploadFmt( $calCd = true )
	{
		$uid = RPCContext::getInstance()->getUid();
		$userObj = EnUser::getUserObj($uid);
		$serverId = Util::getServerIdOfConnection();
		
		$battleFmt = $userObj->getBattleFormation();
		
		$vip = $userObj->getVip();
		$masterHtid = $userObj->getHeroManager()->getMasterHeroObj()->getHtid();
		$dress = $userObj->getDressInfo();
		
		$dbName = LordwarUtil::getCrossDbName();
		$serverMgr = ServerInfoManager::getInstance($dbName);
		$serverName = $serverMgr->getServerNameByServerId($serverId);
		$name = $userObj->getUname();
		$level = $userObj->getLevel();
		
		$recordInfo = array(
				'uid' => $uid,
				'pid' => $this->lordInfo['pid'],
				'uname' => $name,
			 	'htid' => $masterHtid,
				'level' => $level,
				'vip' => $vip,
			 	'dress' => $dress,
			 	//'fightForce' => $battleFmt['fightForce'],
				'serverName' => $serverName,
				'serverId' => $serverId,
				'fightForce' => $userObj->getFightForce(),
		);
		$battleFmt['recordInfo'] = $recordInfo;
		
		$this->lordInfo['va_lord']['fightPara'] = $battleFmt;
		if($calCd)
		{
			$this->lordInfo['update_fmt_time'] = Util::getTime();
		}
		
	}
	
	public function setLosenum( $teamType, $num )
	{
		if( $teamType == LordwarTeamType::LOSE )
		{
			$this->lordInfo['loser_losenum'] = $num;
		}
		elseif ($teamType == LordwarTeamType::WIN )
		{
			$this->lordInfo['winner_losenum'] = $num;
		}
		else
		{
			throw new InterException( 'not in win or lose type' );
		}
	}
	
	public function saveBattleRecord($round,$btlRet,$teamType,$subRound = null )
	{
		if( $btlRet['atk']['serverId'] == $this->serverId && $btlRet['atk']['pid'] == $this->pid  )
		{
			$btlRet['atk'] = array();
		}
		elseif( $btlRet['def']['serverId'] == $this->serverId && $btlRet['def']['pid'] == $this->pid )
		{
			$btlRet['def'] = array();
		}
		
		$btlRet['teamType'] = $teamType;//后期补上给前段显示用
		
		if( $subRound == null )
		{
			$this->lordInfo['va_lord_extra']['record'][$round][] = $btlRet;//重新打晋级赛没问题，重新打海选要清数据，不然还有以前的战斗信息
		}
		else
		{
			$this->lordInfo['va_lord_extra']['record'][$round][$subRound] = $btlRet;
		}
		
	}
	
	public function getRecordInfo()
	{
		return $this->lordInfo['va_lord']['fightPara']['recordInfo'];
	}
	
	public function getUpdateFmtTime()
	{
		return $this->lordInfo['update_fmt_time'];
	}
	
	
	public function setTeamType($teamType)
	{
		$this->lordInfo['team_type']  = $teamType;
	}
	
	public function getWorshipTime()
	{
		return $this->lordInfo['worship_time'];
	}
	
	public function worship( $time )
	{
		if( Util::isSameDay($this->lordInfo['worship_time']) )
		{
			throw new FakeException( 'already worship today' );
		}
		
		$this->lordInfo['worship_time'] = $time;
	}
	
	public function update()
	{
		if ( $this->lordInfo == $this->lordInfoBak )
		{
			Logger::debug('nothing change, data: %s', $this->lordInfo);
			return;
		}
		
		if(LordwarUtil::isMyServer($this->serverId))
		{
			$db = null;
		}
		else 
		{
			$db = ServerInfoManager::getInstance(LordwarUtil::getCrossDbName())->getDbNameByServerId($this->serverId);
		}
		
		$updateArr = array();
		foreach (  $this->lordInfo as $key => $info  )
		{
			if( $info != $this->lordInfoBak[$key] )
			{
				$updateArr[$key] = $info;
			}
		}
		
		if( empty( $updateArr ) )
		{
			Logger::fatal('nothing change? LordInfo:%s ', $this->lordInfo);
			return;
		}
		if( !isset( $updateArr['last_join_time'] ) )
		{
			$updateArr['last_join_time'] = Util::getTime();
		}
		
		LordwarInnerDao::update($this->serverId,$this->pid,$updateArr, $db);
		$this->lordInfoBak = $this->lordInfo;
	}
	
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */