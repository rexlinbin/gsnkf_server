<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TeamManager.class.php 175402 2015-05-28 08:50:11Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/TeamManager.class.php $
 * @author $Author: BaoguoMeng $(wuqilin@babeltime.com)
 * @date $Date: 2015-05-28 08:50:11 +0000 (Thu, 28 May 2015) $
 * @version $Revision: 175402 $
 * @brief 
 *  
 **/

/**
 * @author wuqilin
 *
 */
class TeamManager
{
	private static $gArrInstance;
	
	
	private $mActivityName;	//活动名字
		
	private $mSess;		//活动届数
	
	private $mReferTime;	//参考时间
	
	private $mArrTeamData;	//分组数据
	
	private $mMapServerTeam;//serverId对应的teamId

	private $mHasAllData = false;//mArrTeamData中是否包含所有的分组信息
	
	/**
	 * 将某个服的所属分组放到mem中
	 * {
	 * 		teamId:int  此服不在分组内
	 * 		setTime:int
	 * }
	 */
	const MEM_PRE_TEAM_OF_SERVER = 'world_team_';	
	
	const MEM_VALID_TIME = 3600;
	
	/**
	 * @return TeamManager
	 */
	public static function getInstance($activityName, $sess, $referTime = 0)
	{
		if( empty($activityName) )
		{
			throw new InterException('invalid activityName');
		}
		$key = $activityName."_".$sess;
		if( empty(self::$gArrInstance[$key]) )
		{
			self::$gArrInstance[$key] = new TeamManager($activityName, $sess, $referTime);
		}
		return self::$gArrInstance[$key];
	}
	
	public static function releaseInstance($activityName, $sess, $referTime = 0)
	{
		$key = $activityName."_".$sess;
		unset( self::$gArrInstance[$key] );
	}
	
	
	private function __construct($activityName, $sess, $referTime = 0)
	{
		$this->mActivityName = $activityName;
		$this->mSess = $sess;
		$this->mReferTime = empty($referTime) ? Util::getTime() : $referTime;
	}
	
	/**
	 * 获取所有的分组信息
	 * @return
	 * {
	 * 		teamId=>{serverId}
	 * }
	 */
	public function getAllTeam()
	{
		if( ! $this->mHasAllData )
		{
			if (in_array($this->mActivityName, WolrdActivityName::$arrNeedNearConfig))
			{
				$this->mArrTeamData = self::getAllTeamNearFromPlat($this->mActivityName, $this->mReferTime);
			}
			else 
			{
				$this->mArrTeamData = self::getAllTeamFromPlat($this->mActivityName, $this->mSess);
			}
			$this->mHasAllData = true;
		}
		
		return $this->mArrTeamData;
	}
	
	/**
	 * 获取teamId对应的serverId数组
	 * @return 空数组表示teamId下没有服，可能teamId是个不合法的。 接口出错时，会throw
	 * {
	 * 		serverId
	 * }
	 */
	public function getServersByTeamId($teamId)
	{
		if( !isset($this->mArrTeamData[$teamId]) )
		{
			$arrRet = array();
			if (in_array($this->mActivityName, WolrdActivityName::$arrNeedNearConfig))
			{
				$arrRet = self::getServerByTeamIdNearFromPlat($this->mActivityName, $this->mReferTime, $teamId);
			}
			else 
			{
				$arrRet = self::getServerByTeamIdFromPlat($this->mActivityName, $this->mSess, $teamId);
			}
			 
			 if( empty( $arrRet[$teamId] ) )
			 {
			 	$this->mArrTeamData[$teamId] = array();
			 }
			 else
			 {
			 	$this->mArrTeamData[$teamId] = $arrRet[$teamId];
			 }
		}
		
		return $this->mArrTeamData[$teamId];
	}
	
	/**
	 * 根据serverId获取serverId所在组的所有server
	 * @return serverId不在分组內时，返回空数组。 接口出错时，会throw
	 * {
	 * 		serverId
	 * }
	 */
	public function getServersByServerId($serverId)
	{
		$teamId = $this->getTeamIdByServerId($serverId);
		
		if( $teamId < 0 )
		{
			Logger::fatal('serverId:%d not in any team', $serverId);
			return array();
		}
		
		return $this->getServersByTeamId($teamId);
	}
	
	/**
	 * 根据serverId获取对应的teamId， 如果没有对应team，返回-1
	 * 
	 */
	public function getTeamIdByServerId($serverId, $check = false)
	{
		if( empty($serverId) )
		{
			Logger::fatal('invalid serverId:%d', $serverId);
			return -1;
		}
		
		$teamId = -1;
		$ret = self::getTeamIdByServerIdFromMem($this->mActivityName, $serverId);
		if( empty($ret) )
		{
			if (in_array($this->mActivityName, WolrdActivityName::$arrNeedNearConfig))
			{
				$ret = self::getTeamByServerIdNearFromPlat($this->mActivityName, $this->mReferTime, $serverId);
			}
			else 
			{
				$ret = self::getTeamByServerIdFromPlat($this->mActivityName, $this->mSess, $serverId);
			}
			if( ! empty( $ret ) )
			{
				list($teamId, $arrServerId) = each($ret);
				if( isset($this->mArrTeamData[$teamId]) )
				{
					if( $this->mArrTeamData[$teamId] != $arrServerId )
					{
						Logger::fatal('arrServerId not match. cache:%s, ret:%s', $this->mArrTeamData[$teamId], $arrServerId);
					}
				}
				else
				{
					$this->mArrTeamData[$teamId] = $arrServerId;
				}
			}
			
			self::setTeamIdToMem($this->mActivityName, $serverId, $teamId);
		}
		else
		{
			$teamId = $ret['teamId'];
		}
		
		//==========检查分组信息 在要检查的地方加上参数就行，现在期望的是在打海选的时候检查，因为代价不是很低
		if( $check )
		{
			$this->checkTeamDistribution();
		}
		//==========检查分组信息
		
		return $teamId;
	}
	
	public function isMyServerInAct()
	{
		$myServerId = Util::getServerIdOfConnection();
		if( empty($myServerId) )
		{
			throw new InterException('not found serverId');
		}
		$teamId = $this->getTeamIdByServerId($myServerId);
		return $teamId >= 0;
	}
	

	public static function genMemKeyTeamOfServer($activityName, $serverId)
	{
		return self::MEM_PRE_TEAM_OF_SERVER.$activityName."_".$serverId;
	}
	
	
	/**
	 * 
	 * @return mem中没有，或者出错都返回空array
	 * {
	 * 		teamId:int
	 * 		setTime:int
	 * }
	 */
	public static function getTeamIdByServerIdFromMem($activityName, $serverId)
	{
		$key = self::genMemKeyTeamOfServer($activityName, $serverId);
		
		try 
		{
			$ret = McClient::get($key);
		}
		catch (Exception $e)
		{
			Logger::fatal('getTeamIdByServerFromMem failed:%s', $e->getMessage());
			return array();
		}
		if( empty($ret) )
		{
			return array();
		}
		if(  $ret['setTime'] + self::MEM_VALID_TIME < Util::getTime() )
		{
			Logger::debug('data in mem expire：%s', $ret);
			return array();
		}

		return $ret;
	}
	
	public static function setTeamIdToMem($activityName, $serverId, $teamId)
	{
		$key = self::genMemKeyTeamOfServer($activityName, $serverId);
		$arrValue = array(
			'teamId' => $teamId,
			'setTime' => time(),
		);
		try
		{
			$ret = McClient::set($key, $arrValue, self::MEM_VALID_TIME);
		}
		catch (Exception $e)
		{
			Logger::fatal('setTeamIdToMem failed:%s', $e->getMessage());
		}
		
	}
	
	
	/**
	 * 从平台获取所有分组信息
	 * 
	 * @return
	 * {
	 * 		teamId=>{serverId}
	 * }
	 */
	public static function getAllTeamFromPlat($activityName, $sess)
	{
		$platfrom = ApiManager::getApi ();
		$argv = array (
				'platName' => PlatformConfig::PLAT_NAME,
				'activity' => $activityName,
				'spanId' => $sess,
		);
		
		try
		{
			$arrRet = $platfrom->users ( 'getTeamAll', $argv );
		}
		catch (Exception $e)
		{
			throw new SysException('getAllTeamFromPlat failed:%s', $e->getMessage());
		}
		if(!is_array($arrRet))
		{
			throw new SysException('getAllTeamFromPlat invalid data:%s', $arrRet);
		}
		
		Logger::debug('getAllTeamFromPlat:%s', $arrRet);
		return $arrRet;
	}
	
	/**
	 * 从平台获取所有分组信息，获取距离时间戳最近的配置信息
	 *
	 * @return
	 * {
	 * 		teamId=>{serverId}
	 * }
	 */
	public static function getAllTeamNearFromPlat($activityName, $referTime)
	{
		$platfrom = ApiManager::getApi ();
		$argv = array (
				'platName' => PlatformConfig::PLAT_NAME,
				'activity' => $activityName,
				'beginTime' => $referTime,
		);
		
		try
		{
			$arrRet = $platfrom->users ( 'getTeamAllNear', $argv );
		}
		catch (Exception $e)
		{
			throw new SysException('getAllTeamNearFromPlat failed:%s', $e->getMessage());
		}
		if(!is_array($arrRet))
		{
			throw new SysException('getAllTeamNearFromPlat invalid data:%s', $arrRet);
		}
		
		Logger::debug('getAllTeamNearFromPlat:%s', $arrRet);
		return $arrRet;
	}
	
	/**
	 * 从平台获取某一个组所有的服
	 * 
	 * @return   空数组表示teamId下没有服，接口出错throw
	 * {
	 * 		teamId=>{serverId}
	 * }
	 */
	public static function getServerByTeamIdFromPlat($activityName, $sess, $teamId)
	{
		$platfrom = ApiManager::getApi ();
		$argv = array (
				'platName' => PlatformConfig::PLAT_NAME,
				'activity' => $activityName,
				'spanId' => $sess,
				'teamId' => $teamId,
		);
		
		try 
		{
			$arrRet = $platfrom->users ( 'getServerByTeamId', $argv );
		}
		catch (Exception $e)
		{
			throw new SysException('getServerByTeamIdFromPlat failed:%s', $e->getMessage());
		}
		if(!is_array($arrRet) )
		{
			throw new SysException('getServerByTeamIdFromPlat invalid data:%s', $arrRet);
		}

		Logger::debug('getServerByTeamIdFromPlat:%s', $arrRet);
		return $arrRet;
	}
	
	/**
	 * 从平台获取某一个组所有的服，获取距离时间戳最近的配置信息
	 *
	 * @return   空数组表示teamId下没有服，接口出错throw
	 * {
	 * 		teamId=>{serverId}
	 * }
	 */
	public static function getServerByTeamIdNearFromPlat($activityName, $referTime, $teamId)
	{
		$platfrom = ApiManager::getApi ();
		$argv = array (
				'platName' => PlatformConfig::PLAT_NAME,
				'activity' => $activityName,
				'beginTime' => $referTime,
				'teamId' => $teamId,
		);
	
		try
		{
			$arrRet = $platfrom->users ( 'getServerByTeamIdNear', $argv );
		}
		catch (Exception $e)
		{
			throw new SysException('getServerByTeamIdNearFromPlat failed:%s', $e->getMessage());
		}
		if(!is_array($arrRet) )
		{
			throw new SysException('getServerByTeamIdNearFromPlat invalid data:%s', $arrRet);
		}
	
		Logger::debug('getServerByTeamIdNearFromPlat:%s', $arrRet);
		return $arrRet;
	}
	
	/**
	 * 从平台获取某个serverId所在组的所有服
	 * 
	 * @return  空数组表示serverId不在分组内，接口出错throw
	 * {
	 * 		teamId=>{serverId}
	 * }
	 */
	public static function getTeamByServerIdFromPlat($activityName, $sess, $serverId)
	{
		$platfrom = ApiManager::getApi ();
		$argv = array (
				'platName' => PlatformConfig::PLAT_NAME,
				'activity' => $activityName,
				'spanId' => $sess,
				'serverId' => $serverId,
		);
	
		try
		{
			$arrRet = $platfrom->users ( 'getTeamByServerId', $argv );
		}
		catch (Exception $e)
		{
			throw new SysException('getTeamByServerIdFromPlat failed:%s', $e->getMessage());
		}
		if(!is_array($arrRet) )
		{
			throw new SysException('getTeamByServerIdFromPlat invalid data:%s', $arrRet);
		}
		
		Logger::debug('getTeamByServerIdFromPlat:%s', $arrRet);
		
		if( empty($arrRet) )
		{
			return array();
		}
		
		list($teamId, $arrServerId) = each($arrRet);
		if( !in_array($serverId, $arrServerId) )
		{
			throw new SysException('getTeamByServerIdFromPlat invalid data:%s', $arrRet);
		}
		
		return array( $teamId => $arrServerId );
	}
	
	/**
	 * 从平台获取某个serverId所在组的所有服，获取距离时间戳最近的配置信息
	 *
	 * @return  空数组表示serverId不在分组内，接口出错throw
	 * {
	 * 		teamId=>{serverId}
	 * }
	 */
	public static function getTeamByServerIdNearFromPlat($activityName, $referTime, $serverId)
	{
		$platfrom = ApiManager::getApi ();
		$argv = array (
				'platName' => PlatformConfig::PLAT_NAME,
				'activity' => $activityName,
				'beginTime' => $referTime,
				'serverId' => $serverId,
		);
	
		try
		{
			$arrRet = $platfrom->users ( 'getTeamByServerIdNear', $argv );
		}
		catch (Exception $e)
		{
			throw new SysException('getTeamByServerIdNearFromPlat failed:%s', $e->getMessage());
		}
		if(!is_array($arrRet) )
		{
			throw new SysException('getTeamByServerIdNearFromPlat invalid data:%s', $arrRet);
		}
	
		Logger::debug('getTeamByServerIdNearFromPlat:%s', $arrRet);
	
		if( empty($arrRet) )
		{
			return array();
		}
	
		list($teamId, $arrServerId) = each($arrRet);
		if( !in_array($serverId, $arrServerId) )
		{
			throw new SysException('getTeamByServerIdNearFromPlat invalid data:%s', $arrRet);
		}
	
		return array( $teamId => $arrServerId );
	}
	
	
	public function checkTeamDistributionInner()
	{ 
		$allSingleServerId  = Util::getAllServerId();//self::getAllSingleServer(GameConf::$MERGE_SERVER_DATASETTING);
		if( count( $allSingleServerId ) <= 1  )
		{
			return;
		}
		Logger::debug('all servers in my group: %s', $allSingleServerId);
		$myServerId = Util::getServerIdOfConnection();
		$allServersInTeam = $this->getServersByServerId($myServerId);
		if(empty( $allServersInTeam ))//如果没有分组信息的话，这个服就不用检查了
		{
			return;
		}
		Logger::debug('all servers in my team: %s', $allServersInTeam);
		foreach ( $allSingleServerId as $oneServerId )
		{
			if( !in_array( $oneServerId, $allServersInTeam) )
			{
				throw new ConfigException('server %d, not in the same team of its brother server %d',$oneServerId, $myServerId );
				return false;
			}
		}
		
	}
 	
	
	public function checkTeamDistributionCross()
	{
		$distributedServerIdArr = array();
		$allTeam = $this->getAllTeam();
		
		foreach ( $allTeam as $teamId => $serverIdArr )
		{
			foreach ( $serverIdArr as $serverId  )
			{
				//这里的serverid都是最初始的id
				if( isset( $distributedServerIdArr[$serverId] ) )
				{
					throw new ConfigException( 'serverId: %s  alreay distributed, all distribution info: %s', $serverId, $allTeam );
				}
				else
				{
					$distributedServerIdArr[$serverId] = $teamId;
				}
			}
		}
		
		$distributedIds = array_keys( $distributedServerIdArr );
		
		//合服的某个服不参加跨服战是检查不到的
		$serverMgr = ServerInfoManager::getInstance();
		$offset = 0;
		$allServerDb = array();
		do 
		{
			$partIds = array_slice( $distributedIds , $offset, 100);
			if( empty( $partIds ) )
			{
				break;
			}
			$partServerDb = $serverMgr->getArrDbName($partIds);
			$allServerDb += $partServerDb;
			
			if( count( $partIds ) < 100 )
			{
				break;
			}
			$offset += 100;
			
		}
		while(true);
		
		$dbToServer = array();
		foreach ( $allServerDb as $oneServer => $oneDb )
		{
			$dbToServer[ $oneDb ][] = $oneServer;
		}
		
		foreach ( $dbToServer as $dbCheck => $serverArrCheck )
		{
			if( count( $serverArrCheck  ) <= 1 )
			{
				continue;
			}
			
			$teamIdTmp = NULL;
			foreach ( $serverArrCheck as $oneServerIdCheck )
			{
				if( NULL == $teamIdTmp )
				{
					$teamIdTmp = $distributedServerIdArr[ $oneServerIdCheck ];
				}
				else
				{
					if( $teamIdTmp != $distributedServerIdArr[ $oneServerIdCheck ] )
					{
						throw new ConfigException( 'mergeservers in different team: %d, %d',$teamIdTmp, $distributedServerIdArr[ $oneServerIdCheck ] );
					}
				}
			}
		}
		
	}
	
	
	
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */