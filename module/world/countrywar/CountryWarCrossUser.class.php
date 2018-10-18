<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarCrossUser.class.php 234217 2016-03-22 13:30:39Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/CountryWarCrossUser.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-03-22 13:30:39 +0000 (Tue, 22 Mar 2016) $
 * @version $Revision: 234217 $
 * @brief 
 *  
 *  作用：
 *  管理一个crossuser的单例，并提供一些批量拉取的接口等的静态方法用来直接操作crossuser表
 *  调用场景：
 *  innerscene和crossscene都会调用
 *  tips：
 *  这里用了serverId,pid做的对象标识，在整个功能里也尽量使用serverId,pid做唯一标识，uuid只用于和lcserver的交互
 *  crossuser这张表自己会改,别人会改,脚本也会改.部分字段用了自增减
 *  这里提供了获取排名的方法，用了memadd（同悬赏榜），获取奖励排名的时候直接从db取
 **/

class CountryWarCrossUser
{
	private static $instance = null;
	private $crossUserInfo = NULL;
	private $crossUserInfoBak = NULL;
	private $serverId = NULL;
	private $pid = NULL;
	private $startTime = NULL;
	private $subCocoinArr = NULL;
	
	/**
	 * @param unknown $serverId
	 * @param unknown $pid
	 * @return CountryWarCrossUser
	 */
	static function getInstance( $serverId, $pid )
	{
		if( !isset( self::$instance[$serverId][$pid] ) )
		{
			self::$instance[$serverId][$pid] = new self( $serverId, $pid );
		}
		return self::$instance[$serverId][$pid];
	}
	
	static function releaseInstance()
	{
		if( isset( self::$instance ) )
		{
			self::$instance = null;
		}
	}
	
	function __construct( $serverId, $pid )
	{
		$this->pid = $pid;
		$this->serverId = $serverId;
		$this->startTime = CountryWarConfig::roundStartTime(Util::getTime());
		$this->crossUserInfo = CountryWarCrossUserDao::getInfoByServerIdPid($this->serverId, $this->pid);
		if( empty( $this->crossUserInfo ) )
		{
			$this->init();
		}
		$this->crossUserInfoBak = $this->crossUserInfo;
		$this->refresh();
	}
	
	/**
	 * getloginInfo或者signup的时候会触发更新crossdb的信息这是最后一道关卡，这样就保证了crossuser表肯定是在服内lc触发的，
	 * 一些相关信息就可以直接从session中取(login的时候要设置pid serverid dbname)，
	 * 否则的话就要直接从服内的db中取,db可以在crossscece的session中拿到,像取战斗数据是转的请求
	 * @throws InterException
	 */
	private function init()
	{
		if( !CountryWarUtil::isInnerScene() )
		{
			throw new InterException( 'not in inner scene' );
		}
		$userBaseInfo = $this->getBaseInfoFromInner();
		$initArr = array(
				CountryWarCrossUserField::PID => $this->pid,
				CountryWarCrossUserField::SERVER_ID => $this->serverId,
				CountryWarCrossUserField::SIGN_TIME =>0,
				CountryWarCrossUserField::UNAME => $userBaseInfo['uname'],
				CountryWarCrossUserField::HTID => $userBaseInfo['htid'],
				CountryWarCrossUserField::FIGNT_FORCE => $userBaseInfo['fight_force'],
				CountryWarCrossUserField::VIP => $userBaseInfo['vip'],
				CountryWarCrossUserField::LEVEL => $userBaseInfo['level'],
				CountryWarCrossUserField::TEAM_ROOM_ID => 0,
				CountryWarCrossUserField::COUNTRY_ID => 0,
				CountryWarCrossUserField::SIDE => 0,
				CountryWarCrossUserField::FINAL_QUALIFY => 0,
				CountryWarCrossUserField::FANS_NUM => 0,
				CountryWarCrossUserField::COCOIN_NUM => 0,
				CountryWarCrossUserField::COPOINT_NUM => 0,
				CountryWarCrossUserField::RECOVER_PERCENT => CountryWarConf::DEFAULT_RECOVER_PERCENT,
				CountryWarCrossUserField::AUDITION_POINT => 0,
				CountryWarCrossUserField::AUDITION_POINT_TIME => 0,
				CountryWarCrossUserField::FINAL_POINT => 0,
				CountryWarCrossUserField::FINAL_POINT_TIME => 0,
				CountryWarCrossUserField::AUDITION_INSPIRE_NUM => 0,
				CountryWarCrossUserField::FINALTION_INSPIRE_NUM => 0,
				CountryWarCrossUserField::UPDATE_TIME => Util::getTime(),
				CountryWarCrossUserField::VA_EXTRA => array( 'dress' =>$userBaseInfo['dress'] ),
		);
		CountryWarCrossUserDao::insertInfo($initArr);
		$this->crossUserInfo = $initArr;
	}
	
	private function refresh()
	{
		$lastUpdateTime = $this->crossUserInfo[CountryWarCrossUserField::UPDATE_TIME];
		Logger::debug('last update time:%s, start time:%s',$lastUpdateTime,$this->startTime );
		if( $lastUpdateTime <= $this->startTime )
		{
			$this->crossUserInfo[CountryWarCrossUserField::SIGN_TIME] = 0;
			$this->crossUserInfo[CountryWarCrossUserField::TEAM_ROOM_ID] = 0;
			$this->crossUserInfo[CountryWarCrossUserField::COUNTRY_ID] = 0;
			$this->crossUserInfo[CountryWarCrossUserField::SIDE] = 0;
			$this->crossUserInfo[CountryWarCrossUserField::FINAL_QUALIFY] = 0;
			$this->crossUserInfo[CountryWarCrossUserField::FANS_NUM] = 0;
			$this->crossUserInfo[CountryWarCrossUserField::AUDITION_POINT] = 0;
			$this->crossUserInfo[CountryWarCrossUserField::AUDITION_POINT_TIME] = 0;
			$this->crossUserInfo[CountryWarCrossUserField::FINAL_POINT] = 0;
			$this->crossUserInfo[CountryWarCrossUserField::FINAL_POINT_TIME] = 0;
			$this->crossUserInfo[CountryWarCrossUserField::AUDITION_INSPIRE_NUM] = 0;
			$this->crossUserInfo[CountryWarCrossUserField::FINALTION_INSPIRE_NUM] = 0;
			$this->crossUserInfo[CountryWarCrossUserField::UPDATE_TIME] = Util::getTime();
 		}
			Logger::debug('allcrossuserinfo after refresh:%s', $this->crossUserInfo);
	}

	/**
	 * 刷新基本信息的问题，初始化的时候肯定是在服内，同时在每次进入之前（现在是getEnterInfo的时候）刷一把这样就
	 * 没有在跨服拉取服内信息的需求了
	 * @return multitype:
	 */
	private function getBaseInfoFromInner()
	{
		if( !CountryWarUtil::isInnerScene() )
		{
			Logger::fatal('not allowed to call this');
			return;
		}
		$uid = RPCContext::getInstance()->getUid();
		$user = EnUser::getUserObj($uid);
		$baseInfo['uname'] = $user->getUname();
		$baseInfo['htid'] = $user->getHeroManager()->getMasterHeroObj()->getHtid();
		$baseInfo['fight_force'] = $user->getFightForce();
		$baseInfo['vip'] = $user->getVip();
		$baseInfo['level'] = $user->getLevel();
		$baseInfo['dress'] = $user->getDressInfo();
		return $baseInfo;
		
	}
	
	public function getBaseInfo()
	{
		return array(
				
				CountryWarCrossUserField::UNAME => $this->crossUserInfo[CountryWarCrossUserField::UNAME],
				CountryWarCrossUserField::HTID => $this->crossUserInfo[CountryWarCrossUserField::HTID],
				CountryWarCrossUserField::FIGNT_FORCE => $this->crossUserInfo[CountryWarCrossUserField::FIGNT_FORCE],
				CountryWarCrossUserField::VIP => $this->crossUserInfo[CountryWarCrossUserField::VIP],
				CountryWarCrossUserField::LEVEL => $this->crossUserInfo[CountryWarCrossUserField::LEVEL],
				'dress' => $this->crossUserInfo[CountryWarCrossUserField::VA_EXTRA]['dress'],
				
		);
	}
	
	public function setBaseInfo()
	{
		$baseInfo = $this->getBaseInfoFromInner();
		$this->crossUserInfo[CountryWarCrossUserField::UNAME] = $baseInfo['uname'];
		$this->crossUserInfo[CountryWarCrossUserField::HTID] = $baseInfo['htid'];
		$this->crossUserInfo[CountryWarCrossUserField::FIGNT_FORCE] = $baseInfo['fight_force'];
		$this->crossUserInfo[CountryWarCrossUserField::VIP] = $baseInfo['vip'];
		$this->crossUserInfo[CountryWarCrossUserField::LEVEL] = $baseInfo['level'];
		$this->crossUserInfo[CountryWarCrossUserField::VA_EXTRA]['dress'] = $baseInfo['dress'];
	}
	
	public function getTeamRoomId()
	{
		return $this->crossUserInfo[CountryWarCrossUserField::TEAM_ROOM_ID];
	}
	
	public function sign($countryId)
	{
		$this->crossUserInfo[CountryWarCrossUserField::SIGN_TIME] = Util::getTime();
		$this->crossUserInfo[CountryWarCrossUserField::COUNTRY_ID] = $countryId;
	}
	
	public function getUuid()
	{
		return $this->crossUserInfo[CountryWarCrossUserField::UUID];
	}
	
	public function getUname()
	{
		return $this->crossUserInfo[CountryWarCrossUserField::UNAME];
	}
	
	public function getLevel()
	{
		return $this->crossUserInfo[CountryWarCrossUserField::LEVEL];
	}
	
	public function getHtid()
	{
		return $this->crossUserInfo[CountryWarCrossUserField::HTID];
	}
	
	public function getCocoinNum()
	{
		return $this->crossUserInfo[CountryWarCrossUserField::COCOIN_NUM];
	}
	
	public function getCopointNum()
	{
		return $this->crossUserInfo[CountryWarCrossUserField::COPOINT_NUM];
	}
	
	public function getQualify()
	{
		return $this->crossUserInfo[CountryWarCrossUserField::FINAL_QUALIFY];
	}
	
	public function subCopoint( $num )
	{
		if( $num > $this->crossUserInfo[CountryWarCrossUserField::COPOINT_NUM] )
		{
			return false;
		}
		$this->crossUserInfo[CountryWarCrossUserField::COPOINT_NUM] -=$num;
		return true;
	}
	
	public function addCopoint( $num )
	{
		$this->crossUserInfo[CountryWarCrossUserField::COPOINT_NUM] += $num;
	}
	
	public function getDress()
	{
		if( isset( $this->crossUserInfo[CountryWarCrossUserField::VA_EXTRA]['dress'] ) )
		{
			return $this->crossUserInfo[CountryWarCrossUserField::VA_EXTRA]['dress'];
		}
		return array();
	}
	
	public function getSide()
	{
		return $this->crossUserInfo[CountryWarCrossUserField::SIDE];
	}
	
	public function getSignTime()
	{
		return $this->crossUserInfo[CountryWarCrossUserField::SIGN_TIME];
	}
	
	public function getCountryId()
	{
		return $this->crossUserInfo[CountryWarCrossUserField::COUNTRY_ID];
	}
	
	public function getRecoverPercent()
	{
		return $this->crossUserInfo[CountryWarCrossUserField::RECOVER_PERCENT];
	}
	
	public function inspireFull()
	{
		if( CountryWarUtil::isStage(CountryWarStage::AUDITION) )
		{
			return $this->crossUserInfo[CountryWarCrossUserField::AUDITION_INSPIRE_NUM] >= CountryWarConfig::inspireLimit();
		}
		elseif( CountryWarUtil::isStage(CountryWarStage::FINALTION) )
		{
			return $this->crossUserInfo[CountryWarCrossUserField::FINALTION_INSPIRE_NUM] >= CountryWarConfig::inspireLimit();
		}
		else
		{
			Logger::warning('inspireAtkFull,not valid stage:%s', CountryWarConfig::getStageByTime(Util::getTime()));
		}
		
	}
	
	public function inspireDfdFull()
	{
		if( CountryWarUtil::isStage(CountryWarStage::AUDITION) )
		{
			return $this->crossUserInfo[CountryWarCrossUserField::AUDITION_INSPIRE_DFD_NUM] >= CountryWarConfig::inspireLimit();
		}
		elseif( CountryWarUtil::isStage(CountryWarStage::FINALTION) )
		{
			return $this->crossUserInfo[CountryWarCrossUserField::FINALTION_INSPIRE_DFD_NUM] >= CountryWarConfig::inspireLimit();
		}
		else 
		{
			Logger::warning('method:inspireDfdFull,not valid stage:%s', CountryWarConfig::getStageByTime(Util::getTime()));
		}
	}
	
	public function inspire()
	{
		if( CountryWarUtil::isStage(CountryWarStage::AUDITION) )
		{
			$this->crossUserInfo[CountryWarCrossUserField::AUDITION_INSPIRE_NUM] += 1;
		}
		elseif( CountryWarUtil::isStage(CountryWarStage::FINALTION) )
		{
			$this->crossUserInfo[CountryWarCrossUserField::FINALTION_INSPIRE_NUM] += 1;
		}
		else
		{
			Logger::warning('inspireAtk,not valid stage:%s', CountryWarConfig::getStageByTime(Util::getTime()));
		}
		
	}
	
	public function inspireDfd()
	{
		if( CountryWarUtil::isStage(CountryWarStage::AUDITION) )
		{
			$this->crossUserInfo[CountryWarCrossUserField::AUDITION_INSPIRE_DFD_NUM] += 1;
		}
		elseif( CountryWarUtil::isStage(CountryWarStage::FINALTION) )
		{
			$this->crossUserInfo[CountryWarCrossUserField::FINALTION_INSPIRE_DFD_NUM] += 1;
		}
		else
		{
			Logger::warning('method:inspireDfd,not valid stage:%s', CountryWarConfig::getStageByTime(Util::getTime()));
		}
	}

	public function getInspireLevel()
	{
		if( CountryWarUtil::isStage(CountryWarStage::AUDITION) )
		{
			return $this->crossUserInfo[CountryWarCrossUserField::AUDITION_INSPIRE_NUM];
		}
		elseif( CountryWarUtil::isStage(CountryWarStage::FINALTION) )
		{
			return $this->crossUserInfo[CountryWarCrossUserField::FINALTION_INSPIRE_NUM];
		}
		Logger::warning('method:getInspireLevel,not valid stage:%s', CountryWarConfig::getStageByTime(Util::getTime()));
	}
	
	public function isRangeRoom()
	{
		return $this->crossUserInfo[CountryWarCrossUserField::TEAM_ROOM_ID] > 0;
	}
	
	public function addCocoin($gainCocoin)
	{
		$this->crossUserInfo[CountryWarCrossUserField::COCOIN_NUM] += $gainCocoin;
	}
	
	public function subCocoin($needCocoin,$type)
	{
		if( $needCocoin < 0 )
		{
			throw new InterException('need cocoin:%s negtive',$needCocoin);
		}
		if( $this->crossUserInfo[CountryWarCrossUserField::COCOIN_NUM] < $needCocoin )
		{
			Logger::fatal('method:subCocoin,need cocoin:%s, now:%s',$needCocoin,$this->crossUserInfo[CountryWarCrossUserField::COCOIN_NUM]);
			return false;
		}
		
		$this->crossUserInfo[CountryWarCrossUserField::COCOIN_NUM] -= $needCocoin;
		if( !isset($this->subCocoinArr[$type] ) )
		{
			$this->subCocoinArr[$type][0] = 0;
		}
		$this->subCocoinArr[$type][0] += $needCocoin;
		$this->subCocoinArr[$type][1] = $this->crossUserInfo[CountryWarCrossUserField::COCOIN_NUM];
		return true;
	}
	
	public function setRecoverPara( $percent )
	{
		$this->crossUserInfo[CountryWarCrossUserField::RECOVER_PERCENT] = $percent;
	}
	
	/**
	 * 特殊字段处理和检查：
	 * 自增减
	 * @param unknown $key
	 * @return Ambigous <IncOperator, DecOperator>
	 */
	private function dealSpecialKey( $key )
	{
		$info = $this->crossUserInfo[$key];
		if( $key == CountryWarCrossUserField::FANS_NUM )
		{
			$delta = $this->crossUserInfo[$key] - $this->crossUserInfoBak[$key];
			if( $delta > 0 )
			{
				$info = new  IncOperator($delta);
			}
			else
			{
				$info = new  DecOperator(-$delta);
			}
		} 
		return $info;
	}
	
	public function addAuditionPoint( $num )
	{
		$this->crossUserInfo[CountryWarCrossUserField::AUDITION_POINT] += $num;
		$this->crossUserInfo[CountryWarCrossUserField::AUDITION_POINT_TIME] = Util::getTime();
		$this->crossUserInfo[CountryWarCrossUserField::COPOINT_NUM] += $num;
	}
	
	public function addFinaltionPoint( $num )
	{
		$this->crossUserInfo[CountryWarCrossUserField::FINAL_POINT] += $num;
		$this->crossUserInfo[CountryWarCrossUserField::FINAL_POINT_TIME] = Util::getTime();
		$this->crossUserInfo[CountryWarCrossUserField::COPOINT_NUM] += $num;
	}
	
	public function isFinalMember() 
	{
		if( $this->crossUserInfo[CountryWarCrossUserField::FINAL_QUALIFY] > 0 )
		{
			return true;
		}
		return false;
	}
	
	public function getCanExchangeGoldNum()
	{
		$nowCocoin = $this->crossUserInfo[CountryWarCrossUserField::COCOIN_NUM];
		$canExchangeGold = floor( (CountryWarConfig::cocoinMax()-$nowCocoin)/CountryWarConfig::exchangeRatio() );
		$canExchangeGold = $canExchangeGold <0?0:$canExchangeGold;
		return $canExchangeGold;
	}
	
	public function addFans( $num )	
	{
		$this->crossUserInfo[CountryWarCrossUserField::FANS_NUM] += $num;
	}
	
	public function update()
	{
		if( $this->crossUserInfo == $this->crossUserInfoBak )
		{
			Logger::warning('no need to update: %s', $this->crossUserInfo);
			return;
		}
		$updateFields = array();
		foreach ( $this->crossUserInfo as $key => $info )
		{
			if( $info != $this->crossUserInfoBak[$key] )
			{
				$info = $this->dealSpecialKey($key);
				$updateFields[$key] = $info;
			}
		}
		if( !isset( $updateFields[CountryWarCrossUserField::UPDATE_TIME] ) )
		{
			$updateFields[CountryWarCrossUserField::UPDATE_TIME] = Util::getTime();
		}
		CountryWarCrossUserDao::update( $this->serverId, $this->pid, $updateFields );
		$this->crossUserInfoBak = $this->crossUserInfo;
		if( $this->subCocoinArr != null )
		{
			foreach ( $this->subCocoinArr as $type => $numArr )
			{
				Statistics::gold($type, -$numArr[0], $numArr[1]);
			}
		}
		
	}
	
	//==================直接db=========================================//
	
	static function getTopNByBattleId( $battleId,$type,$N = NULL, $mustFromDb = false, $excludeEmptyUser = true, $needSetTimeAfter = 0 )
	{
		if( !in_array( $type , CountryWarRankType::$ALL) )
		{
			throw new InterException( 'invalid type:%s',$type );
		}
		if( $mustFromDb )
		{
			$validTime = -1;
		}
		else
		{
			$validTime = CountryWarConf::RANK_LIST_VALID_TIME;
		}
		Logger::debug('getTopNByBattleId,args:%s,%s,%s',$battleId,$N,$mustFromDb);
		$ret = self::getRankListFromMemOrDb( $battleId, $type, $validTime, $needSetTimeAfter);
		Logger::debug('pointlist:%s,battleId:%s,type:%s', $ret,$battleId,$type );
		if( !empty( $ret ) )
		{
			if( CountryWarRankType::SUPPORT == $type )
			{
				usort( $ret ,  array( 'CountryWarUtil','cprRankMemberSupport' ));
			}
			elseif( CountryWarRankType::AUDITION == $type )
			{
				usort( $ret ,  array( 'CountryWarUtil','cprRankMemberAudition' ));
				if($excludeEmptyUser)
				{
					foreach ( $ret as $index => $retone )
					{
						if( empty( $retone[CountryWarCrossUserField::AUDITION_POINT] ) )
						{
							unset( $ret[$index] );
						}
					}
					$ret = array_merge( $ret );
				}
			}
			else
			{
				usort( $ret ,  array( 'CountryWarUtil','cprRankMemberFinaltion' ));
				if($excludeEmptyUser)
				{
					foreach ( $ret as $index => $retone )
					{
						if( empty( $retone[CountryWarCrossUserField::FINAL_POINT] ) )
						{
							unset( $ret[$index] );
						}
					}
					$ret = array_merge( $ret );
				}
			}
			
		}
		
		if( NULL != $N )
		{
			$ret = array_slice( $ret, 0, $N);
		}
		$serverIdArr = Util::arrayExtract( $ret , CountryWarCrossUserField::SERVER_ID);
		$serverNameArr = ServerInfoManager::getInstance()->getArrServerName($serverIdArr);
		foreach ( $ret as $index => $info )
		{
			if( isset( $info[CountryWarCrossUserField::VA_EXTRA]['dress'] ) )
			{
				$ret[$index]['dress'] = $info[CountryWarCrossUserField::VA_EXTRA]['dress'];
			}
			else
			{
				$ret[$index]['dress'] = array();
			}
			$ret[$index][CountryWarFrontField::SERVER_NAME] = $serverNameArr[$info[CountryWarCrossUserField::SERVER_ID]]; 
		}
		
		return $ret;
	}

	static function getRankListFromMemOrDb( $battleId,$type, $validTime, $needSetTimeAfter )
	{
		if( $validTime <= 0 )
		{
			$topNInfo = CountryWarCrossUserDao::getTopNInfo( $battleId, $type );
			Logger::info('rank from db');
			Logger::debug('ranklist:%s',$topNInfo);
			return $topNInfo;
		}
	
		if( $validTime > 30 )
		{
			$validTime = 30;
			Logger::fatal('validtime too long,%s, reset to 30', $validTime);
		}
	
		$addKey = CountryWarUtil::getMemAddKey($battleId);
		$rankKey = CountryWarUtil::getMemRankKey($battleId);
		McClient::setDb(CountryWarUtil::getCrossDbName());
		$dataInMem = McClient::get($rankKey);
	
		if( !isset( $dataInMem[CountryWarDef::RANK_MEM_SET_TIME] )
		||  !isset( $dataInMem[CountryWarDef::RANK_MEM_LIST] )
		|| $dataInMem[CountryWarDef::RANK_MEM_SET_TIME] + $validTime <= Util::getTime()
		|| $dataInMem[CountryWarDef::RANK_MEM_SET_TIME] < $needSetTimeAfter)
		{
			McClient::setDb(CountryWarUtil::getCrossDbName());
			$addRet = McClient::add($addKey, array(1) , $validTime);
			if( $addRet == "STORED" )
			{
				$topNInfo = CountryWarCrossUserDao::getTopNInfo( $battleId, $type );
				$forSetArr[CountryWarDef::RANK_MEM_SET_TIME] = Util::getTime();
				$forSetArr[CountryWarDef::RANK_MEM_LIST] = $topNInfo;
				McClient::setDb(CountryWarUtil::getCrossDbName());
				McClient::set($rankKey, $forSetArr, CountryWarConf::RANK_MEM_EXPIRETIME);
				Logger::info('info from db to refresh mem done');
				
				return $topNInfo;
			}
			else
			{
				if( empty( $dataInMem[CountryWarDef::RANK_MEM_SET_TIME] ) || $dataInMem[CountryWarDef::RANK_MEM_SET_TIME] < $needSetTimeAfter )
				{
					$topNInfo = CountryWarCrossUserDao::getTopNInfo( $battleId, $type );
					Logger::info('rank from db because after time:%s',$needSetTimeAfter);
					Logger::debug('ranklist:%s',$topNInfo);
					return $topNInfo;
				}
				
				if( empty( $dataInMem[CountryWarDef::RANK_MEM_LIST] ) )
				{
					Logger::info('info from db to refresh mem NOT_STORED and empty last memlist');
					return array();//就没有排名
				}
				else
				{
					Logger::info('info from db to refresh mem NOT_STORED and not empty last memlist');
					return $dataInMem[CountryWarDef::RANK_MEM_LIST];
				}
			}
				
		}
		else
		{
			Logger::debug('from mem');
			if( empty( $dataInMem[CountryWarDef::RANK_MEM_LIST] ) )
			{
				return array();
			}
			else
			{
				return $dataInMem[CountryWarDef::RANK_MEM_LIST];
			}
		}
	}
	
	/**
	 * 获取排名，主要是在发奖的时候用
	 * @param unknown $battleId
	 * @param unknown $type
	 * @param unknown $serverId
	 * @param unknown $pid
	 * @return Ambigous <NULL, multitype:, unknown>|number
	 */
 	static function getUserRank( $battleId,$type, $serverId,$pid )
	{
		$needAfterTime = 0;
		if( CountryWarUtil::isFinalBattleId($battleId) )
		{
			$needAfterTime = CountryWarConfig::getStageStartTime(Util::getTime(), CountryWarStage::WORSHIP);
		}
		else
		{
			$needAfterTime = CountryWarConfig::getStageStartTime(Util::getTime(), CountryWarStage::SUPPORT);
		}
		
		$topList = self::getTopNByBattleId($battleId,$type,null,false,true,$needAfterTime);
		foreach ( $topList as $rank => $userInfo )
		{
			if( $userInfo[CountryWarCrossUserField::SERVER_ID] == $serverId && $userInfo[CountryWarCrossUserField::PID] == $pid )
			{
				return $rank;
			}
		}
		return -1;
	} 
	
	/**
	 * 标记进入决赛的人
	 * @param unknown $battleId,原来有用，现在只是为了打个日志
	 * @param unknown $userList
	 * @return multitype:
	 */
	static function markFinalMembers( $battleId, $userList )
	{
		if( empty( $userList ) )
		{
			Logger::warning('nobody need to mark,battleId:%s ', $battleId);
			return array();
		}
		$uuidArr = Util::arrayExtract( $userList , CountryWarCrossUserField::UUID );
		CountryWarCrossUserDao::markFinalMembers($battleId, $uuidArr);
		Logger::info('mark final member done,battleId:%s, user:%s ', $battleId, $uuidArr);
	}
	
	/**
	 * 获取所有进入决赛的人
	 */
	static function getAllFinalMembers($roundStartTime, $teamId)
	{
		$battleId = CountryWarUtil::getFinalBattleIdByTeamId( $teamId );
		$supportStartTime = CountryWarConfig::getStageStartTime(Util::getTime(), CountryWarStage::SUPPORT);
		return self::getTopNByBattleId( $battleId,CountryWarRankType::SUPPORT,null,false,false,$supportStartTime );
	}
	
	/**
	 * 为没有进房间的人获取该分组的最牛逼的人，膜拜用,
	 * @param unknown $roundStartTime
	 * @param unknown $teamId
	 * @return Ambigous <multitype:, mixed, multitype:unknown , unknown>
	 */
	static function getHighestInfoByWarIdTeamId($roundStartTime, $teamId)
	{
		$battleId = CountryWarUtil::getFinalBattleIdByTeamId($teamId);
		$ret = self::getTopNByBattleId($battleId, CountryWarRankType::FINALTION,1,false,true,$roundStartTime);
		//$ret = CountryWarCrossUserDao::getHighestInfoByWarIdTeamId($roundStartTime, $teamId);
		if( !empty( $ret ) )
		{
			return $ret[0];
		}
		else
		{
			return array();
		}
		//return $ret;
	}
	
	static function divideOneUser(  $serverId, $pid , $updataFields)
	{
		CountryWarCrossUserDao::update( $serverId, $pid, $updataFields );
	}
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */