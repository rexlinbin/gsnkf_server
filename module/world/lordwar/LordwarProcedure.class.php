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


class LordwarTeamRound
{
	
	private $mData;
	
	private $mBack;
	
	public function __construct( $data)
	{
		$this->mData = $data;
		$this->mBack = $this->mData;
	}
	

	public function getStatus()
	{
		return $this->mData['status'];
	}
	
	public function getData()
	{
		return $this->mData;
	}
	
	/**
	 * 获取当前小回合（5局三胜中的一局）
	 */
	public function getSubRound()
	{
		if( empty($this->mData['va_procedure']['recordArr']) )
		{
			return 0;
		}
		return count($this->mData['va_procedure']['recordArr']);
	}
	
	public function needUpdate()
	{
		return $this->mData != $this->mBack;
	}
	
	
	/**
	 * TODO: 现在数据的具体逻辑都在外面，需要移进来
	 */
	public function setData($data)
	{
		$this->mData = $data;
	}
	
	public function setStatus($status)
	{
		$this->mData['status'] = $status;
	}
	
	public function initDataFromLastRound($lastRoundData)
	{
		if( !empty( $this->mData['va_procedure'] ) )
		{
			throw new InterException('has data. cant init. curData:%s, setData:%s', $this->mData, $lastRoundData);
		}
		$vaData = $lastRoundData['va_procedure'];
		foreach( $vaData['lordArr'] as $lordIndex => $lordInfo )
		{
			if( isset( $lordInfo['loseNum'] ) )
			{
				$vaData['lordArr'][$lordIndex]['loseNum'] = 0;
			}
		}
		$this->mData['va_procedure'] = array(
			'lordArr' => $vaData['lordArr'],
			'recordArr' => array(),
		);
	}
	
	public function update()
	{
		/*
		先就简单的insertOrUpdate  一般情况下就只有insert
		$arrField = array();
		foreach ($this->mData as $key => $value)
		{
			if ($this->mBack[$key] != $value)
			{
				$arrField[$key] = $this->mBack[$key];
			}
		}
			
		if (empty($arrField))
		{
			Logger::debug('no change');
			return;
		}
		*/
		if( $this->mData == $this->mBack )
		{
			Logger::debug('no change');
			return;
		}
		
		$dbName = '';
		if( $this->mData['round'] >= LordwarRound::CROSS_AUDITION )
		{
			$dbName = LordwarUtil::getCrossDbName();
		}
		
		Logger::info('updateLordProcedure. dbName:%s, teamId:%d, round:%d, teamType:%d, status: %d', 
				$dbName, $this->mData['team_id'], $this->mData['round'], $this->mData['team_type'], $this->mData['status']);
		LordwarDao::updateLordProcedure($dbName, $this->mData);
		
		$this->mBack = $this->mData;
	}
}

class LordwarTeam
{
	private $mSess;
	
	private $mTeamId;
	
	private $mCurRound;
	
	private $mArrTeamRoundObj; //  [round][teamType]
	
	public function __construct($sess, $teamId)
	{
		$this->mSess = $sess;
		$this->mTeamId = $teamId;
		
		$this->init();
	}
	
	public function initWithDefault($defaultRound, $defaultStatus)
	{
		$this->mCurRound = $defaultRound;
			
		$winTeamData = self::getInitTeamRoundData($this->mTeamId, LordwarTeamType::WIN, $this->mCurRound, $this->mSess, $defaultStatus);
		$loseTeamData = self::getInitTeamRoundData($this->mTeamId, LordwarTeamType::LOSE, $this->mCurRound, $this->mSess, $defaultStatus);
		$this->mArrTeamRoundObj[$this->mCurRound][LordwarTeamType::WIN] = new LordwarTeamRound($winTeamData);
		$this->mArrTeamRoundObj[$this->mCurRound][LordwarTeamType::LOSE] = new LordwarTeamRound($loseTeamData);
	}
	
	public function initFromDb($dbName, $defaultRound, $defaultStatus)
	{
		$arrRet = LordwarDao::getLastRoundData( $dbName, $this->mSess, $this->mTeamId );
		if( empty($arrRet) )
		{
			$this->initWithDefault($defaultRound, $defaultStatus);
			Logger::debug('empty t_procedure, init ');
		}
		else
		{
			$curRoundByDb = -1;
			foreach ( $arrRet as $teamData )
			{
				if( $curRoundByDb >= 0 && $curRoundByDb != $teamData['round'] )
				{
					throw new InterException('invalid procedure data:%s', $arrRet);
				}
				$curRoundByDb = $teamData['round'];
			}
			$this->mCurRound = $curRoundByDb;
		
			foreach ( LordwarTeamType::$TEAM_TYPE_ALL as $teamType )
			{
				if( isset( $arrRet[$teamType] ) )
				{
					$this->mArrTeamRoundObj[$this->mCurRound][$teamType] = new LordwarTeamRound(  $arrRet[$teamType] );
				}
				else
				{
					Logger::warning('new Lordwar with init data. teamId:%d, round:%d, teamType:%d',
					$this->mTeamId, $this->mCurRound, $teamType);
					$initTeamData = self::getInitTeamRoundData($this->mTeamId, $teamType, $this->mCurRound, $this->mSess);
					$this->mArrTeamRoundObj[$this->mCurRound][$teamType] = new LordwarTeamRound($initTeamData);
				}
			}
		}
		return $arrRet;
	}
	
	public function init()
	{
		/*
		 	本来获取当前round时，不想依赖配置，完全根据数据判断。但是有个坑爹的服内和跨服导致问题比较麻烦
		 	1）单独找个地方存储当前状态的方式不太适合，这个数据放在服内数据库，或者跨服数据库都不太合适
		 	2）直接从数据库中获取最新的两条数据时，要分别从服内，跨服两地方看看，效率较低。
		 	所以还是依赖了时间配置。依赖点：
		 		数据库中的round <= 根据时间配置得到的round
		 	
		 	另外恶心的地方：需要根据当前是否是跨服海选阶段，做一些特殊判断
		 */
		$curRoundByConf = LordwarConfMgr::getInstance()->getRound();
		
		if( $curRoundByConf < LordwarRound::INNER_AUDITION )
		{
			$this->mCurRound = $curRoundByConf;
			Logger::info('curRoundByConf:%d, teamId:%d', $curRoundByConf, $this->mTeamId);
		}
		else if( $curRoundByConf < LordwarRound::CROSS_AUDITION  )
		{
			/*
			 	本来在服内阶段时，都只需要考虑在服内机器上获取数据。
			 	但是在服内决赛阶段，跨服机器上需要准备跨服赛的数据，所以这里做了特殊判断，在服内决赛阶段，跨服机器上能正常init
			 */
			if( LordwarProcedure::getField() == LordwarField::CROSS )
			{
				if(  $curRoundByConf == LordwarRound::INNER_2TO1  )
				{
					$this->initWithDefault(LordwarRound::CROSS_AUDITION, LordwarStatus::NO);
					Logger::info('init team procedure with default in cross machine');
				}
				else
				{
					throw new InterException('cant init team procedure in cross machine now. curRoundByConf:%d', $curRoundByConf);
				}
			}
			else
			{
				$this->initFromDb('', LordwarRound::INNER_AUDITION, LordwarStatus::FIGHTING);
				Logger::info('init team procedure from inner db. teamId:%d, round:%d', $this->mTeamId, $this->mCurRound);
			}
		}
		else
		{
			/*
			 	跨服阶段，先从跨服机器上获取数据，如果有数据。则说明服内已经成功完成
			 	如果没有数据，就分情况了：
			 	1）当前在服内机器上：返回当前服的数据
			 	2）当前在跨服机器上：返回 round=CROSS_AUDITION  status=NO
			 */
			$arrRet = $this->initFromDb(LordwarUtil::getCrossDbName(), LordwarRound::CROSS_AUDITION, LordwarStatus::NO);
			if( empty($arrRet) )
			{
				if( LordwarProcedure::getField() == LordwarField::INNER )
				{
					$this->mArrTeamRoundObj = array();
					$this->initFromDb('', LordwarRound::INNER_AUDITION, LordwarStatus::FIGHTING);
					Logger::warning('no data in cross db, init from inner db. teamId:%d, round:%d', $this->mTeamId, $this->mCurRound);
				}
				else
				{
					Logger::warning('no data in cross db, init with default. teamId:%d, round:%d', 
							$this->mTeamId, $this->mCurRound);
				}
			}
			else
			{	
				foreach($this->mArrTeamRoundObj[$this->mCurRound] as $teamType => $obj)
				{
					$status = $obj->getStatus();
					if( $status == LordwarStatus::PREPARE )
					{
						if( $this->mCurRound != LordwarRound::CROSS_AUDITION )
						{
							Logger::fatal('round:%d should not has prepre status');
						}
						$obj->setStatus(LordwarStatus::FIGHTING);
						Logger::info('change status prepare to fighting. teamId:%d, round:%d, teamType:%d', $this->mTeamId, $this->mCurRound, $teamType);
					}
				}
				
				Logger::info('init team procedure from cross db. teamId:%d, round:%d', $this->mTeamId, $this->mCurRound);
			}
		}
		
		$status = $this->getCurStatus();
		if( $this->mCurRound < $curRoundByConf && $status == LordwarStatus::DONE )
		{
			Logger::info('is time to init next round');
			$this->initNextRound();
		}
		
	}
	
	public function initNextRound()
	{
		$curRoundByConf = LordwarConfMgr::getInstance()->getRound();

		if( $this->mCurRound < LordwarUtil::getPreRound(LordwarRound::INNER_AUDITION)
				|| $this->mCurRound >= LordwarRound::CROSS_2TO1 )
		{
			Logger::fatal('curRound:%d, cant initNextRound', $this->mCurRound);
			return false;
		}
		
		$nextRound = LordwarUtil::getNextRound($this->mCurRound);
		if( $nextRound > $curRoundByConf )
		{
			Logger::fatal('not reach the time. mCurRound:%d, curRoundByConf:%d', $this->mCurRound, $curRoundByConf);
			return false;
		}
	
		$curStatus = $this->getCurStatus();
		if( $curStatus != LordwarStatus::DONE )
		{
			Logger::fatal('curRound:%d, curStatus:%d cant initNextRound', $this->mCurRound, $curStatus);
			return false;
		}
		
		$this->mCurRound = $nextRound;
		
		$winTeamData = self::getInitTeamRoundData($this->mTeamId, LordwarTeamType::WIN, $this->mCurRound, $this->mSess);
		$loseTeamData = self::getInitTeamRoundData($this->mTeamId, LordwarTeamType::LOSE, $this->mCurRound, $this->mSess);
		$this->mArrTeamRoundObj[$this->mCurRound] = array(
				LordwarTeamType::WIN => new LordwarTeamRound($winTeamData),
				LordwarTeamType::LOSE => new LordwarTeamRound($loseTeamData),
		);
		
		Logger::info('initNextRound. teamId:%d, round:%d', $this->mTeamId, $this->mCurRound);
		return;
	}
	
	
	public function getCurRound()
	{
		return $this->mCurRound;
	}
	
	public function getCurStatus()
	{
		if( $this->mCurRound < LordwarRound::INNER_AUDITION )
		{
			return LordwarStatus::NO;
		}
		$status = min( $this->mArrTeamRoundObj[$this->mCurRound][LordwarTeamType::WIN]->getStatus(),
						 $this->mArrTeamRoundObj[$this->mCurRound][LordwarTeamType::LOSE]->getStatus());
		return $status;
	}
	
	public function getStatusByRound($round)
	{
		if( empty( $this->mArrTeamRoundObj[$round][LordwarTeamType::WIN] )
			|| empty( $this->mArrTeamRoundObj[$round][LordwarTeamType::LOSE]) )
		{
			throw new InterException('get some other round:%d, curRound:%d', $round, $this->mCurRound );
		}
		$status = min( $this->mArrTeamRoundObj[$round][LordwarTeamType::WIN]->getStatus(),
				$this->mArrTeamRoundObj[$round][LordwarTeamType::LOSE]->getStatus());
		
		return $status;
	}
	
	
	/**
	 * 理论上只会获取当前round和上一个round的对象。目前如果想获取其他对象，会inter
	 * 起只有当前round的status＝NO时，才能获取上一个round的对象
	 * @return LordwarTeamRound
	 */
	public function getTeamRound($round, $teamType)
	{
		Logger::debug('all valid team round info are : %s',$this->mArrTeamRoundObj );
		if( isset( $this->mArrTeamRoundObj[$round][$teamType] ) )
		{
			return $this->mArrTeamRoundObj[$round][$teamType];
		}
		else
		{	
			
			throw new InterException('get some other round:%d, teamType:%d', $round, $teamType);
		}
		
	}

	
	/**
	 * 只更新当前round的数据
	 */
	public function update()
	{
		
		foreach( $this->mArrTeamRoundObj as $round => $arr )
		{
			foreach( $arr as $teamTyp => $obj )
			{
				if( $round == $this->mCurRound )
				{
					$obj->update();
				}
				else
				{
					if( $obj->needUpdate() )
					{
						Logger::fatal('no cur round need update. data:%s', $obj->getData() );
					}
				}
			}
		}
	}
	
	public static function getInitTeamRoundData($teamId, $teamType, $round, $sess, $status = LordwarStatus::FIGHTING)
	{
		$arrValue = array(
				'team_id' => $teamId,
				'team_type' => $teamType,
				'round' => $round,
				'sess' => $sess,
				'status' => $status,
				'update_time' => Util::getTime(),
				'va_procedure' => array(),
		);
		
		return $arrValue;
	}
	
	public static function getTeamRoundData($teamId, $sess, $round)
	{
		$dbName = "";
		if ( LordwarUtil::isCrossRound($round) )
		{
			$dbName = LordwarUtil::getCrossDbName();
		}
		
		return LordwarDao::getRoundData($dbName, $sess, $teamId, $round);
	}
	
	public static function getPromotionBtlView( $teamId,$teamType,$sess, $round  )
	{
		$dbName = "";
		if ( LordwarUtil::isCrossRound($round) )
		{
			$dbName = LordwarUtil::getCrossDbName();
		}
		
		$roundData = LordwarDao::getRoundData($dbName, $sess, $teamId, $round);
		if ( isset($roundData[$teamType]['va_procedure']['recordArr']) )
		{
			return $roundData[$teamType]['va_procedure']['recordArr'];
		}
		else
		{
			return array();
		}
	}
}



/**
 * 目前只能管理当前届的数据，如果以后需要获取上一届数据，可以通过静态函数的方式返回数据
 * @author wuqilin
 *
 */
class LordwarProcedure
{
	
	private static $gInstance;
	
	private static $gField; //是在服内机器上，还是跨服机器上
	
	private $mSess;
	
	private $mArrTeamObj;
	
	/**
	 * @return LordProcedure
	 */
	public static function getInstance($sess, $field)
	{
		self::setField($field);
		
		if( empty(self::$gInstance) )
		{
			self::$gInstance = new LordwarProcedure($sess);
		}
		else
		{
			if( self::$gInstance->getSess() != $sess )
			{
				throw new InterException('already set sess:%d, cant change to sess:%d', self::$gInstance->getSess(), $sess);
			}
		}
		return self::$gInstance;
	}
	
	public static function releaseInstance($sess)
	{
		unset( self::$gInstance );
	}
	
	
	public static function getDefaultField()
	{
		$group =  RPCContext::getInstance()->getFramework()->getGroup();
		if( empty( $group ) )
		{
			return LordwarField::CROSS;
		}
		else
		{
			return LordwarField::INNER;
		}
	}

	public static function getField()
	{
		if( empty( self::$gField ))
		{
			$defaultField = self::getDefaultField();
			Logger::warning('not set field. return default field:%s', $defaultField);
			return $defaultField;
		}
		return self::$gField;
	}
	
	public static function setField($field)
	{
		$defaultField = self::getDefaultField();
		if( $field != $defaultField )
		{
			Logger::fatal('field:%s != default:%s', $field, $defaultField );
		}
		if( !empty(self::$gField) && self::$gField != $field )
		{
			throw new InterException('already set field:%s, cant change to field:%s', self::$gField, $field);
		}
		Logger::debug('set filed:%s', $field);
		self::$gField = $field;
	}
	
	private function __construct($sess)
	{
		$this->mSess = $sess;
	}
	
	public function getSess()
	{
		return $this->mSess;
	}
	
	
	/**
	 * @return LordwarTeam
	 */
	public function getTeamObj($teamId)
	{
		if( empty( $this->mArrTeamObj[$teamId] ) )
		{
			$this->mArrTeamObj[$teamId] = new LordwarTeam($this->mSess, $teamId);
		}
		return $this->mArrTeamObj[$teamId];
	}


}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */