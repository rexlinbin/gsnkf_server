<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FragseizeObj.class.php 206640 2015-11-03 02:59:19Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/fragseize/FragseizeObj.class.php $
 * @author $Author: ShijieHan $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-11-03 02:59:19 +0000 (Tue, 03 Nov 2015) $
 * @version $Revision: 206640 $
 * @brief 
 *  
 **/
class FragseizeObj
{
	/**
	 * 以宝物id为key存该宝物的所有碎片信息
	 * @var array
	 * $allFrag => array[
	 * 	$tid 宝物id => array[
	 * 		$frag_id 碎片id => array(frag_num => 碎片数量, seize_num => 抢夺数量)
	 * 	]
	 * ]
	 */
	private $allFrag;
	private $allFragBak;
	private $uid;
	
	/**
	 * 存用户的个人信息
	 * @var array
	 */
	private $seizer;
	private $seizerBak;
	
	private static $instance = NULL;
	
	/**
	 * 获取唯一实例
	 * @return FragseizeObj
	 */
	public static function getInstance( $uid )
	{
		if ( !isset( self::$instance[ $uid ] ) )
		{
			self::$instance[ $uid ] = new self( $uid );
		}
		return self::$instance[ $uid ];
	}
	
	public static function release( $uid )
	{
		if ( isset( self::$instance[ $uid ] ))
		{
			unset( self::$instance[ $uid ] ) ;
		}
	}
	
	/**
	 * 初始化没有做什么事情，只是将uid设定，uid也是控制整个数据操作类的依据
	 * @param int $uid
	 * @throws FakeException
	 */
	private function __construct( $uid )
	{
		if ( empty( $uid ) )
		{
			throw new FakeException( 'invalid uid: %d', $uid );
		}
		$this->uid = $uid;
		$this->allFragBak = $this->allFrag = null;
		$this->seizer = $this->seizerBak = null;
	}
	
	public function getAllFrags()
	{
		$allfrags = $this->loadAll();
		
		return $allfrags;
	}
	/**
	 * 只在拉取所有碎片的时候调用
	 * @return multitype:number unknown
	 */
	public function loadAll()
	{
		//没有排除掉0的
		$allFrags = FragseizeDAO::getFragByUid( $this->uid );
		$tmpfragseize = array();
		if (  !empty( $allFrags ) )
		{
			foreach ( $allFrags as $key => $val )
			{
				$tmpfragseize[ $val[ FragseizeDef::FRAG_ID ] ] = $val[ FragseizeDef::FRAG_NUM ];
			}
		}
		
		//插入初始化的数据
		$fragDB = array();
		foreach ( FragseizeConf::$default as $id => $num )
		{
			//如果有一个碎片也没有的那就给他加上所有默认的
			if ( !isset( $tmpfragseize[ $id ] ) )
			{
				$fragDB[ $id ] = $num;
				//修改副数据
				$tmpfragseize[ $id ] = $num;
			}
		}
		
		if ( !empty( $fragDB ) )
		{
			//修改主数据
			$this->addDefaultFrag( $fragDB );
		}
		
		return $tmpfragseize;
	}
	
	public function addDefaultFrag( $fragArr )
	{
		$addArr = array();
		foreach ( $fragArr as $id => $num )
		{
			if ( $num <= 0 )
			{
				throw new InterException( 'num should not be negtive' );
			}
			$addArr[ $id ][FragseizeDef::FRAG_NUM] = $num;
			$addArr[ $id ][FragseizeDef::SEIZE_NUM] = 0;
			
		}
		FragseizeDAO::updateFrags($this->uid, $addArr);
	}
	
	public function loadNeed( $tid )
	{	
		//不开启不会被抢（包括新手引导）需要配置保证
		//如果缓存中没有就直接从数据中取
		if ( !isset( $this->allFrag[ $tid ] ) )
		{
			$needFrags = TreasureItem::getFragments( $tid );
			if ( empty( $needFrags ) )
			{
				throw new ConfigException( 'no frag need: tid: %d ?', $tid );
			}
			//也是没有排除0的 且 havefrags是 needfrags 的子集
			$haveFrags = FragseizeDAO::getFragByFragidArr($this->uid, $needFrags);
			$haveFrags = Util::arrayIndex( $haveFrags , FragseizeDef::FRAG_ID );
			
			//如果有一个默认碎片是没有的就把所有的默认碎片给他加上,在这里先记下来，稍后会加上
			$fragDB = array();
			foreach ( FragseizeConf::$default as $defaultId => $defaultNum )
			{
				if ( !isset( $haveFrags[ $defaultId ] ) && in_array( $defaultId , $needFrags ) )
				{
					$fragDB[ $defaultId ] = $defaultNum;
					
					$haveFrags[ $defaultId ][FragseizeDef::FRAG_NUM] = $defaultNum;
					$haveFrags[ $defaultId ][FragseizeDef::SEIZE_NUM] =0;
				}
			}

			//完善该用户该宝物的碎片信息，loadedFrags和 needFrags碎片种类相同，方便update
			$loadedFrags = array();
			foreach ( $needFrags as $fragId )
			{
				if ( !isset( $haveFrags[ $fragId ] ) )
				{
					$loadedFrags[$fragId][FragseizeDef::FRAG_NUM] = 0;
					$loadedFrags[$fragId][FragseizeDef::SEIZE_NUM] = 0;
				}
				else 
				{
					//手动添加的没有这个字段
					if ( isset( $haveFrags[ $fragId ][FragseizeDef::FRAG_ID] ) )
					{
						unset( $haveFrags[ $fragId ][FragseizeDef::FRAG_ID] );
					}
					$loadedFrags[$fragId] = $haveFrags[ $fragId ];
				}
			}
			
			//加上默认碎片，即使所加碎片对应宝物的种类不同也支持
			if ( !empty( $fragDB ) )
			{
				$this->addDefaultFrag( $fragDB );
			}
			
			//赋值 
			$this->allFrag[ $tid ] = $loadedFrags;
			$this->allFragBak[ $tid ] = $loadedFrags;
		}
	}
	
	public function loadSeizer()
	{
		if ( $this->seizer == null )
		{
			$globalUid = RPCContext::getInstance()->getUid();
			if ( $globalUid == $this->uid )
			{
				//用户拉取自己的信息
				$this->seizer = RPCContext::getInstance()->getSession( 'fragseize.seizer' );
				if ( empty( $this->seizer ) )
				{
					$this->seizer = FragseizeDAO::getSeizer( $this->uid );
					if ( empty( $this->seizer ) )
					{
						$this->seizer = self::initSeizer();
					}
					if ( empty( $this->seizer ) )
					{
						throw new InterException( 'no data in seizer' );
					}
					RPCContext::getInstance()->setSession( 'fragseize.seizer' , $this->seizer);
				}
			}
			else 
			{
				//拉去别人的信息，有可能拉取到的数据是空的
				$this->seizer = FragseizeDAO::getSeizer( $this->uid );
				//为了不要初始化别人的数据，造个临时数据，此项成立的前提是针对t_seizer表， 不能有修改别人数据的操作
				if (empty( $this->seizer ))
				{
					$this->seizer =  array(
							'uid' => $this->uid, 
							FragseizeDef::WHITE_END_TIME => Util::getTime(),
							FragseizeDef::FIRST_TIME => 0,
		 			);
				}
			}
			
			$this->seizerBak = $this->seizer;
		}
		return $this->seizer;
	}
	
	public function initSeizer()
	{
		$initArr = array(
				'uid' => $this->uid, 
				FragseizeDef::WHITE_END_TIME => Util::getTime(),
				FragseizeDef::FIRST_TIME => 0,
		 );
		FragseizeDAO::insertSeizer( $this->uid, $initArr );
		return $initArr;
	}
	
	
	public function getFragsByTid( $tid )
	{
		//只获取数量信息
		$this->loadNeed($tid);
		$fragNums = array();
		foreach ( $this->allFrag[ $tid ] as $fragId => $fragInfo )
		{
			$fragNums[ $fragId ] = $fragInfo[FragseizeDef::FRAG_NUM];
		}
		return $fragNums;
	}
	
	public function addFrags( $fragArr )
	{
		foreach ( $fragArr as $fragId => $fragNum )
		{
			if ( empty( $fragId ) )
			{
				throw new FakeException( 'invalid fragId: %d', $fragId );
			}
			$treasureId = TreasFragItem::getTreasureId( $fragId );
			if ( empty( $treasureId ) )
			{
				throw new ConfigException( 'no treasureId for fragId: %d', $fragId );
			}
			
			
			$tid = TreasFragItem::getTreasureId( $fragId );
			$this->loadNeed($tid);
			if ( !isset( $this->allFrag[ $tid ][ $fragId ] ) )
			{
				throw new InterException( 'add fraid: %d, not in loaded info: %s', 
						$fragId, $this->allFrag[ $tid ] );
			}
			else
			{
				$this->allFrag[ $tid ][ $fragId ][FragseizeDef::FRAG_NUM] += $fragNum;
			}
			
		}
	}
	
	public function subFrags( $fragArr )
	{
		foreach ( $fragArr as $fragId => $fragNum )
		{
			$tid = TreasFragItem::getTreasureId( $fragId );
			$this->loadNeed($tid);
			
			if ( !isset( $this->allFrag[ $tid ][ $fragId ] ) )
			{
				throw new InterException( 'sub fraid: %d, not in loaded info: %s',
						$fragId, $this->allFrag[ $tid ] );
			}
			else 
			{
				$this->allFrag[ $tid ][ $fragId ][ FragseizeDef::FRAG_NUM ] -= $fragNum;
				if ( $this->allFrag[ $tid ][ $fragId ][ FragseizeDef::FRAG_NUM ] < 0 )
				{
					throw new InterException( 'bullshit! negtive num for frags: %s', $this->allFrag[ $tid ] );
				}
			}
		}
		
	}
	
	public function getFragSeizeNum( $fragId )
	{
		$tid = TreasFragItem::getTreasureId( $fragId );
		$this->loadNeed($tid);
		return $this->allFrag[ $tid ][ $fragId ][FragseizeDef::SEIZE_NUM];
	}
	
	public function setSeizeNum( $fragId, $seizeNum )
	{
		$tid = TreasFragItem::getTreasureId( $fragId );
		$this->loadNeed($tid);
		$this->allFrag[ $tid ][ $fragId ][FragseizeDef::SEIZE_NUM] = $seizeNum;
	}
	
	public function getWhiteEndTime()
	{
		self::loadSeizer();
		return  $this->seizer[ FragseizeDef::WHITE_END_TIME ];
	}
	
	public function setWhiteTime( $whiteEndTime )
	{
		self::loadSeizer();
		$this->seizer[FragseizeDef::WHITE_END_TIME] = $whiteEndTime;
	}
	
	public function setAtk()
	{
		self::loadSeizer();
		if ( $this->seizer[ FragseizeDef::FIRST_TIME ] == 0 )
		{
			$this->seizer[ FragseizeDef::FIRST_TIME ] = Util::getTime();
		}
	}
	
	public function getAtk()
	{
		self::loadSeizer();
		return $this->seizer[ FragseizeDef::FIRST_TIME ];
	}
	
	public function updateSeizer()
	{
		$updateArr = array();
		foreach ( $this->seizer as $key => $val )
		{
			if ( $this->seizerBak[ $key ] != $val )
			{
				$updateArr[ $key ] = $val;
			}
		}
		if ( empty($updateArr) ) 
		{
			return;
		}
		
		FragseizeDAO::updateSeizer( $this->uid, $updateArr );
		RPCContext::getInstance()->setSession( 'fragseize.seizer' , $this->seizer );
		$this->seizerBak = $this->seizer;
	}
	
	public function updateFrags()
	{
		$addArr = array();
		$subArr = array();
		
		$updateArr = array();
		
		$bakForBak = $this->allFragBak;
		Logger::debug('now and bak : %s %s', $this->allFrag, $this->allFragBak);
		foreach ( $this->allFrag as $tid => $tInfo )
		{
			foreach ( $tInfo as $fragId => $fragInfo )
			{
				if ( !isset( $bakForBak[ $tid ][ $fragId ] ) )
				{
					throw new InterException( 'bak and modi different: bak: %s, modi: %s', $bakForBak, $this->allFrag );
				}
				
				if ( isset( $updateArr[ $fragId ] ) )
				{
					throw new ConfigException( 'two tid use same fragid: %d', $fragId );
				}
				$deltaFragNum = $fragInfo[FragseizeDef::FRAG_NUM] - $bakForBak[ $tid ][ $fragId ][FragseizeDef::FRAG_NUM];
				$deltaSeizeNum = $fragInfo[FragseizeDef::SEIZE_NUM] - $bakForBak[ $tid ][ $fragId ][FragseizeDef::SEIZE_NUM];
				
				$updateArr[ $fragId ][FragseizeDef::FRAG_NUM] = $deltaFragNum;
				//已经连续夺取不到的次数是不需要自增的
				$updateArr[ $fragId ][FragseizeDef::SEIZE_NUM] = $fragInfo[FragseizeDef::SEIZE_NUM];
				Logger::debug('now the shit updateArr is one:%s', $updateArr);
				if ( $deltaFragNum == 0 && $deltaSeizeNum == 0 )
				{
					unset( $updateArr[ $fragId ] );
				}
				logger::debug( ' $deltaFragNum and $deltaSeizeNum: %d %d,  ',$deltaFragNum, $deltaSeizeNum  );
				Logger::debug('now the shit updateArr is two:%s', $updateArr);
				unset( $bakForBak[ $tid ][ $fragId ] );
			}
			
			if ( !empty( $bakForBak[ $tid ] ) )
			{
				throw new InterException( 'bak id more for tid %d info: %s ', $tid, $bakForBak[ $tid ]);
			}
						
		}
		
		if ( empty( $updateArr ) )
		{
			return;
		}
		
		FragseizeDAO::updateFrags( $this->uid , $updateArr);
		$this->allFragBak = $this->allFrag;
	}

	/**
	 * $treasureId对应的碎片是否足够合成一个宝物
	 * @param $treasureId int 宝物模板id
	 * @throws ConfigException
	 * @return bool
	 */
	public function ifFragEnoughForTreasure($treasureId)
	{
		//合成需要的碎片
		$arrNeedFragId = TreasureItem::getFragments($treasureId);
		$this->loadNeed($treasureId);
		if(empty($arrNeedFragId))
		{
			throw new ConfigException("empty arrNeedFrag for treasure:[%d] fragments", $treasureId);
		}
		foreach($arrNeedFragId as $needFragId)
		{
			if(!isset($this->allFrag[$treasureId][$needFragId])
				|| $this->allFrag[$treasureId][$needFragId][FragseizeDef::FRAG_NUM] <= 0)
			{
				return false;
			}
		}
		return true;
	}

	public function ifHaveFrag($fragId)
	{
		//获取碎片的对应宝物
		$treasureId = TreasFragItem::getTreasureId($fragId);
		$this->loadNeed($treasureId);
		if(isset($this->allFrag[$treasureId][$fragId])
			&& $this->allFrag[$treasureId][$fragId][FragseizeDef::FRAG_NUM] > 0)
		{
			return true;
		}
		return false;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */