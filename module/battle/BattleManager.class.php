<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BattleManager.class.php 250333 2016-07-07 03:45:10Z BaoguoMeng $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/battle/BattleManager.class.php $
 * @author $Author: BaoguoMeng $(hoping@babeltime.com)
 * @date $Date: 2016-07-07 03:45:10 +0000 (Thu, 07 Jul 2016) $
 * @version $Revision: 250333 $
 * @brief
 *
 **/

class BattleQueue
{

	/**
	 * 当前所代理的队列
	 * @var array
	 */
	private $arrFormationList;
	/**
	 * 队列中的成员信息
	 * @var array
	 */
	private $arrMemberInfo;
	
	/**
	 * 当前队列是否已经完结
	 * @var bool
	 */
	private $isEnd;

	/**
	 * 战斗评价
	 * @var int
	 */
	private $isWin;

	/**
	 * 队列名称
	 * @var string
	 */
	private $name;

	/**
	 * 队列等级
	 * @var level
	 */
	private $level;
	
	/**
	 * 队伍id。如果是城池战，teamId=guildId
	 */
	private $teamId;

	/**
	 * 总数
	 * @var int
	 */
	private $totalCount;

	/**
	 * 队伍id
	 * @var int
	 */
	private $id;
	
	/**
	 * 如果是跨服军团战会有guildId 和serverId
	 */
	private $guildId;
	
	private $serverId;

	/**
	 * 设置战斗评价
	 * @param string $appraise
	 */
	public function setWin($isWin)
	{

		$this->isWin = $isWin;
	}

	public function __construct($arrFormationList, $id)
	{

		$this->arrFormationList = $arrFormationList ['members'];
		$this->getMemberInfo($arrFormationList);
		$this->name = $arrFormationList ['name'];
		$this->level = $arrFormationList ['level'];
		$this->isEnd = false;
		$this->isWin = false;
		$this->id = $id;
		$this->totalCount = count ( $this->arrFormationList );
		
		$this->teamId = $this->id;
		if( isset(  $arrFormationList ['teamId'] ) )
		{
			$this->teamId = $arrFormationList ['teamId'];
		}
		if ( isset( $arrFormationList['server_id'] ) )
		{
			$this->serverId = $arrFormationList['server_id'];
		}
		if ( isset( $arrFormationList['guild_id'] ) )
		{
			$this->guildId = $arrFormationList['guild_id'];
		}
		
		
		Logger::debug ( "name:%s, level:%d, member count:%d", $this->name, $this->level,
				$this->totalCount );
	}

	private function getMemberInfo($arrFormationList)
	{
	    Logger::trace('arrFormationList is %s.',$arrFormationList);
	    $this->arrMemberInfo = array();
	    foreach($arrFormationList['members'] as $index => $memberInfo)
	    {
	        if($memberInfo['isPlayer'])
	        {
	            $masterHero = array();
	            foreach($memberInfo['arrHero'] as $heroInfo)
	            {
	                $htid = $heroInfo[PropertyKey::HTID];
	                if(HeroUtil::isMasterHtid($htid))
	                {
	                    $masterHero = $heroInfo;
	                }
	            }
	            if(empty($masterHero))
	            {
	                if ( isset( $memberInfo['masterHeroInfo']  ) )
	                {
	                	$this->arrMemberInfo[$index]['dress'] = $memberInfo['masterHeroInfo']['dress'];
	                	$this->arrMemberInfo[$index]['htid'] = $memberInfo['masterHeroInfo']['htid'];
	                }
	                else 
	                {
	                	Logger::fatal('user %d has no master hero. %s',$memberInfo['uid'], $memberInfo);
	                	$heroMng = EnUser::getUserObj($memberInfo['uid'])->getHeroManager();
	                	$this->arrMemberInfo[$index]['dress'] = array();
	                	$this->arrMemberInfo[$index]['htid'] = $heroMng->getMasterHeroObj()->getHtid();
	                }
	            }
	            else
	            {
	                $arrDress = HeroUtil::simplifyDressInfo($masterHero[PropertyKey::EQUIP_INFO]);
	                $this->arrMemberInfo[$index]['dress'] = $arrDress;
	                $this->arrMemberInfo[$index]['htid'] = $masterHero[PropertyKey::HTID];
	            }
	        }
	        $this->arrMemberInfo[$index]['maxHp'] = 0;
	        foreach($memberInfo['arrHero'] as $heroInfo)
	        {
	            $this->arrMemberInfo[$index]['maxHp'] += $heroInfo[PropertyKey::MAX_HP];
	        }
	        $this->arrMemberInfo[$index]['uid'] = $memberInfo['uid'];
	        $this->arrMemberInfo[$index]['name'] = $memberInfo['name'];
	        
	        if( isset( $memberInfo['maxWin'] ) )
	        {
	        	$this->arrMemberInfo[$index]['maxWin'] = $memberInfo['maxWin'];
	        }
	        if ( isset( $memberInfo['fightForce'] ) )
	        {
	        	$this->arrMemberInfo[$index]['fight_force'] = $memberInfo['fightForce'];
	        }
	    }
	}
	
	public function getId()
	{
		return $this->id;
	}

	public function getInfo()
	{

		$info = array (
		        'memberCount' => $this->totalCount, 
		        'name' => $this->name,
				'level' => $this->level,
				'teamId' => $this->teamId,
		        'memberList' => $this->arrMemberInfo);
		
		if ( !empty($this->serverId) )
		{
			$info['server_id'] = $this->serverId;
		}
		if ( !empty($this->guildId) )
		{
			$info['guild_id'] = $this->guildId;
		}
		return $info;
	}

	public function getTotalCount()
	{

		return $this->totalCount;
	}

	public function pop()
	{

		$arrFormation = array_shift ( $this->arrFormationList );
		if (empty ( $arrFormation ))
		{
			return false;
		}

		$arrHero = $arrFormation ['arrHero'];
		$arrHero = BattleUtil::unsetEmpty ( $arrHero );
		$arrFormation ['arrHero'] = $arrHero;
		return $arrFormation;
	}

	public function push($arrFormation)
	{

		array_unshift ( $this->arrFormationList, $arrFormation );
	}

	public function isEnd()
	{

		return empty ( $this->arrFormationList );
	}
	
	public function getLeftUid()
	{
		return Util::arrayExtract($this->arrFormationList, 'uid');
	}

	public function isWin()
	{

		return $this->isWin;
	}
}

class BattleArena
{

	/**
	 * @var BattleQueue
	 */
	private $attackerQueue;

	/**
	 * @var BattleQueue
	 */
	private $defenderQueue;

	/**
	 * 当前的防守方
	 * @var array
	 */
	private $defender;

	/**
	 * 当前的攻击方
	 * @var array
	 */
	private $attacker;

	/**
	 * @var PHPProxy
	 */
	private $proxy;

	/**
	 * 请求已发出
	 * @var bool
	 */
	private $sent;

	/**
	 * 最大连接赢回合数
	 * @var int
	 */
	private $maxWin;

	/**
	 * 战斗结束条件
	 * @var array
	 */
	private $arrEndCondition;
	
	/**
	 * battle的extra配置
	 * @var array
	 */
	private $arrBattleExtra;

	/**
	 * 上个回合持续回合数
	 * @var int
	 */
	private $lastBattleRoundNum;

	/**
	 * 擂台的位置
	 * @var int
	 */
	private $position;

	/**
	 * 战斗管理器
	 * @var BattleManager
	 */
	private $manager;

	/**
	 * 背景id
	 * @var int
	 */
	private $bgid;

	/**
	 * 音乐id
	 * @var int
	 */
	private $musicId;

	/**
	 * 处理的回调函数
	 * @var callback
	 */
	private $callback;

	/**
	 * 战斗结算类型
	 * @var int
	 */
	private $type;

	/**
	 * 是否正在运行
	 * @var bool
	 */
	private $isRunning;
	
	/**
	 * 最大连胜场次   TODO:这个记录的不是实际连胜了多少场，而是每个人最大允许的连胜次数。目前不知道有何用处
	 * @var array
	 */
	private $arrMaxWin;
	
	/**
	 * 指定数据库
	 */
	private $db;

	/**
	 * 战斗失败时，是否throw
	 */
	private $stopWhenBattleFailed;
	
	/**
	 * 构造函数
	 * @param BattleQueue $attackerQueue
	 * @param BattleQueue $defenderQueue
	 * @param array $arrCondition
	 * @param int $maxWin
	 */
	public function __construct($manager, $attackerQueue, $defenderQueue, $position, $maxWin,
			$arrExtra)
	{

		$this->proxy = new PHPProxy ( 'battle', null, true );

		$this->maxWin = $maxWin;		
		$this->position = $position;
		$this->manager = $manager;
		$this->attackerQueue = $attackerQueue;
		$this->defenderQueue = $defenderQueue;
		$this->defender = $this->defenderQueue->pop ();
		$this->attacker = false;
		
		$this->db = null;
		if ( isset( $arrExtra['db'] ) )
		{
			$this->db = $arrExtra['db'];
		}
		
		$this->stopWhenBattleFailed = false;
		if ( isset( $arrExtra['stopWhenBattleFailed'] )
			&& $arrExtra['stopWhenBattleFailed'] )
		{
			$this->stopWhenBattleFailed = true;
		}
		
		
		//单场战斗相关
		$this->arrEndCondition = $arrExtra ['arrEndCondition'];
		if (empty ( $this->arrEndCondition ))
		{
			$this->arrEndCondition = array ('dummy' => true );
		}
		$this->bgid = $arrExtra ['subBgid'];
		$this->musicId = $arrExtra ['subMusicId'];
		$this->callback = $arrExtra ['subCallback'];
		$this->type = $arrExtra ['subType'];
		
		// battle的extra配置
		$this->arrBattleExtra = array();
		if (isset($arrExtra['isPvp'])) 
		{
			$this->arrBattleExtra['isPvp'] = $arrExtra['isPvp'];
		}
		if (isset($arrExtra['damageIncreConf'])) 
		{
			$this->arrBattleExtra['damageIncreConf'] = $arrExtra['damageIncreConf'];
		}
		if (empty($this->arrBattleExtra))
		{
			$this->arrBattleExtra = array('dummy' => true);
		}
		
		//战斗过程
		$this->lastBattleRoundNum = 0;
		$this->isRunning = true;
		$this->curArenaRound = 0;
		$this->arrMaxWin = array ();
	}

	public function getLastBattleRoundNum()
	{
		return $this->lastBattleRoundNum;
	}
	
	public function getPosition()
	{
		return $this->position;
	}
	
	public function sendRequest()
	{

		$this->sent = false;
		if (! $this->isRunning)
		{
			Logger::debug ( "arena:%d is not running", $this->position );
			return;
		}

		Logger::debug ( "arena:%d start battle", $this->position );
		$this->attacker = $this->attackerQueue->pop ();
		if ($this->defender === false || $this->attacker === false)
		{
			if ($this->defender !== false)
			{
				Logger::debug ( "arena:%d put user:%d back", $this->position, $this->defender ['uid'] );
				$this->defenderQueue->push ( $this->defender );
			}

			if ($this->attacker !== false)
			{
				Logger::debug ( "arena:%d put user:%d back", $this->position, $this->attacker ['uid'] );
				$this->attackerQueue->push ( $this->attacker );
			}

			Logger::debug ( "no attacker or defender, arena:%d end", $this->position );
			$this->isRunning = false;
			return;
		}
		
		if( !empty( $this->attacker['arrCar'] ) )
		{
			$carIdOffset = BattleDef::$CAR_ID_OFFSET[1];
			foreach ($this->attacker['arrCar'] as $index => $aCarInfo)
			{
				$this->attacker['arrCar'][$index]['cid'] = ++$carIdOffset;
			}
		}
		if( !empty( $this->defender['arrCar'] ) )
		{
			$carIdOffset = BattleDef::$CAR_ID_OFFSET[2];
			foreach ($this->defender['arrCar'] as $index => $aCarInfo)
			{
				$this->defender['arrCar'][$index]['cid'] = ++$carIdOffset;
			}
		}
		
		$curArrBattleExtra = $this->arrBattleExtra;
		if (!isset($curArrBattleExtra['isPvp'])
			&& isset($this->attacker['isPlayer'])
			&& $this->attacker['isPlayer']
			&& isset($this->defender['isPlayer'])
			&& $this->defender['isPlayer'])
		{
			$curArrBattleExtra['isPvp'] = 1;
		}

		$this->proxy->doHero ( BattleUtil::prepareBattleFormation ( $this->attacker ),
				BattleUtil::prepareBattleFormation ( $this->defender ), 0,
				$this->arrEndCondition, $curArrBattleExtra );
		$this->sent = true;
		Logger::debug ( "battle request sent" );
	}

	private function resetHp($arrHero1, $arrHero2)
	{

		$arrHero2 = Util::arrayIndex ( $arrHero2, 'hid' );
		foreach ( $arrHero1 as $index => $hero )
		{
			$hid = $hero ['hid'];
			if (! empty ( $arrHero2 [$hid] ['hp'] ))
			{
				$arrHero1 [$index] ['currHp'] = $arrHero2 [$hid] ['hp'];
			}
			else
			{
				unset ( $arrHero1 [$index] );
			}
		}
		return array_merge ( $arrHero1 );
	}

	public function isRunning()
	{
		return $this->isRunning;
	}

	public function readResponse()
	{

		if (! $this->sent)
		{
			Logger::debug ( "not request sent, ignore read response now" );
			return array();
		}

		try
		{
			$arrRet = $this->proxy->getReturnData ();
			Logger::debug ( "response read from server" );

			$arrClient = $arrRet ['client'];

			if (! empty ( $this->callback ))
			{
				$arrReward = call_user_func ( $this->callback, $arrRet ['server'] );
				$arrClient ['reward'] = $arrReward;
				$arrRet ['server'] ['reward'] = $arrReward;
			}
			
			$this->manager->setAfterBattleInfo($this->attacker['uid'], $arrRet ['server'] ['team1']);
			$this->manager->setAfterBattleInfo($this->defender['uid'], $arrRet ['server'] ['team2']);
			
			//单场战斗战报
			$brid = $this->manager->nextBrid ();
			$arrClient ['url_brid'] = BabelCrypt::encryptNumber ( $brid );
			$arrClient ['brid'] = $brid;
			$arrClient ['bgid'] = $this->bgid;
			$arrClient ['musicId'] = $this->musicId;
			$arrClient ['type'] = $this->type;
			
			$arrClient ['firstAttack'] = 1; //组队都是team1先手
			
			$arrClient ['team1'] = BattleUtil::prepareClientFormation ( $this->attacker, $arrRet ['server'] ['team1'] );
			$arrClient ['team2'] = BattleUtil::prepareClientFormation ( $this->defender, $arrRet ['server'] ['team2'] );

			$compressed = true;
			$data = Util::amfEncode ( $arrClient, $compressed, 0, BattleDef::BATTLE_RECORD_ENCODE_FLAGS );

			BattleDao::addRecord ( $brid, $data, $this->db );
		}
		catch ( Exception $e )
		{
			Logger::fatal ( "battle failed:%s,\n%s", $e->getMessage (), $e->getTraceAsString () );
			if ($this->stopWhenBattleFailed )
			{
				throw new InterException('battle failed:%s', $e->getMessage() );
			}
			$this->defender = $this->defenderQueue->pop ();
			return array();
		}
		
		$appraise = $arrRet ['server'] ['appraisal'];
		
		$battleInfo = array(
				'brid' => $brid,
				'attacker' => $this->attacker['uid'],
				'defender' => $this->defender['uid'],
				'appraise' => $appraise,
		);
		$this->manager->addBattleResult($brid, $arrClient);

		
		$battleData = $arrRet ['client'] ['battle'];
		if(  count( $battleData ) < 1 )
		{
			$this->lastBattleRoundNum = 0;
			Logger::fatal('battle round = 0');
		}
		else
		{
			$this->lastBattleRoundNum = $battleData[count($battleData)-1]['round'];
		}
		Logger::trace('lastBattleRoundNum %s.',$this->lastBattleRoundNum);
		//根据战斗记过处理相关
		if ($appraise == 'E' || $appraise == 'F')
		{
			Logger::debug ( "attacker:%d vs defender:%d failed", $this->attacker ['uid'], $this->defender ['uid'] );
			$this->attackerQueue->setWin ( false );
			$this->defenderQueue->setWin ( true );
		}
		else
		{
			Logger::debug ( "attacker:%d vs defender:%d win", $this->attacker ['uid'], $this->defender ['uid'] );
			$this->attackerQueue->setWin ( true );
			$this->defenderQueue->setWin ( false );
		}

		/**
		 * 平局时两边都下场，attacker在sendRequest时pop
		 * 谁胜利，谁处在守擂方
		 */
		if ($appraise == 'E')
		{
			//平局
			$this->defender = $this->defenderQueue->pop ();
		}
		else if ($appraise == 'F')
		{
			//失败
			$this->manager->incWin ( $this->defender ['uid'] );
			if (isset ( $this->defender ['maxWin'] ))
			{
				$maxWin = $this->defender ['maxWin'];
			}
			else
			{
				$maxWin = $this->maxWin;
			}
			$this->arrMaxWin [$this->defender ['uid']] = $maxWin;

			if ($this->manager->getWin ( $this->defender ['uid'] ) < $maxWin)
			{
				$this->defender ['arrHero'] = $this->resetHp ( $this->defender ['arrHero'], $arrRet ['server'] ['team2'] );
			}
			else
			{
				Logger::debug ( "user:%d wins %d battle, next defender", $this->defender ['uid'], $maxWin );
				$this->defender = $this->defenderQueue->pop ();
			}
		}
		else
		{
			//胜利
			$this->manager->incWin ( $this->attacker ['uid'] );
			if (isset ( $this->attacker ['maxWin'] ))
			{
				$maxWin = $this->attacker ['maxWin'];
			}
			else
			{
				$maxWin = $this->maxWin;
			}

			$this->arrMaxWin [$this->attacker ['uid']] = $maxWin;

			if ($this->manager->getWin ( $this->attacker ['uid'] ) < $maxWin)
			{
				$this->attacker ['arrHero'] = $this->resetHp ( $this->attacker ['arrHero'], $arrRet ['server'] ['team1'] );
				$this->defender = $this->attacker;
				$tmp = $this->defenderQueue;
				$this->defenderQueue = $this->attackerQueue;
				$this->attackerQueue = $tmp;
				Logger::debug ( "attacker switch to defender" );
			}
			else
			{
				Logger::debug ( "user:%d wins %d battle, next attacker", $this->attacker ['uid'], $maxWin );
				$this->defender = $this->defenderQueue->pop ();
			}
		}
		
		return $battleInfo;
	}


	
}

class BattleManager
{

	/**
	 * 擂台个数
	 * @var int
	 */
	private $arenaCount;
	
	/**
	 * 最大连胜次数
	 * @var int
	 */
	private $maxWin;

	/**
	 * 擂台对象数组
	 * @var array
	 */
	private $arrArena;

	/**
	 * 战斗队列
	 * @var BattleQueue
	 */
	private $queue1;

	/**
	 * 战斗队列
	 * @var BattleQueue
	 */
	private $queue2;

	/**
	 * 本场战斗所有的录相id
	 * @var array
	 */
	private $arrBrid;

	/**
	 * brid的位移
	 * @var int
	 */
	private $bridOffset;

	/**
	 * 主战场背景id
	 * @var int
	 */
	private $bgid;

	/**
	 * 音乐id
	 * @var int
	 */
	private $musicId;

	/**
	 * 回调函数
	 * @var callback
	 */
	private $callback;

	/**
	 * 战斗类型
	 * @var int
	 */
	private $type;

	/**
	 * 用户的连续次数
	 * @var array
	 */
	private $mapUidWin;
	
	/**
	 * 用户初始胜利次数。
	 * 在军团跨服战中使用，某个用户再上一场战斗中已经胜利了x1场，并且一直活了下来，在下场显示胜利场数时需要加上x1
	 * @var array
	 */
	private $mapUidInitWin;
	
	/**
	 * 每个玩家武将战斗后的信息
	 */
	private $mapUidAfterBattleInfo;
	
	/**
	 * 战报记录
	 */
	private $arrBattleResult;
	
	/**
	 * 需要的结果
	 * 
	 * defaultAttackWin => true/false   是否默认攻方胜
	 * simpleRecord => int   没多少回合合并成在一起
	 * saveSimpleRecord =   保存的主战报中，是否存简化战报
	 * db
	 * isGuildWar
	 * mapUidInitWin
	 * stopWhenBattleFailed
	 * 
	 */
	private $arrNeedResult;
	
	/**
	 * 指定数据库
	 */
	private $db;

	/**
	 * 是否是跨服军团战
	 */
	private $isGuildWar;
	
	/**
	 * 构造函数
	 * @param int $arenaCount 同时进行的战斗场次
	 * @param int $maxWin 最长允许的连赢场次
	 * @param array $arrFormationList1 战斗队列1
	 * @param array $arrFormationList2 战斗队列2
	 * @param array $arrEndCondition 结束条件
	 */
	function __construct($arrFormationList1, $arrFormationList2, $arenaCount, $maxWin, $arrExtra)
	{
		Logger::debug ( "maxWin:%d", $maxWin );
		$this->queue1 = new BattleQueue ( $arrFormationList1, 1 );
		$this->queue2 = new BattleQueue ( $arrFormationList2, 2 );
		$this->arrArena = array ();
		if (($this->queue1->getTotalCount () * $this->queue2->getTotalCount ()) == 0)
		{
			$maxBridNum = 1;
		}
		else
		{
			$maxBridNum = $this->queue1->getTotalCount () + $this->queue2->getTotalCount ();
		}
		
		$this->db = null;
		if ( isset( $arrExtra['db'] ) )
		{
			$this->db = $arrExtra['db'];
		}
		$this->isGuildWar = false;
		if ( isset( $arrExtra['isGuildWar'] ) && $arrExtra['isGuildWar'] )
		{
			$this->isGuildWar = true;
		}
		if ( isset( $arrExtra['mapUidInitWin'] ) )
		{
			$this->mapUidInitWin = $arrExtra['mapUidInitWin'];
		}
		
		$this->arrBrid = IdGenerator::nextMultiId ( 'brid', $maxBridNum, $this->db );
		$this->bridOffset = 0;
		$this->bgid = $arrExtra ['mainBgid'];
		$this->musicId = $arrExtra ['mainMusicId'];
		$this->callback = $arrExtra ['mainCallback'];
		$this->type = $arrExtra ['mainType'];
		$this->mapUidWin = array ();
		$this->maxWin = $maxWin;
		$this->arrNeedResult = isset($arrExtra['arrNeedResult']) ? $arrExtra['arrNeedResult'] : array();
		for($counter = 0; $counter < $arenaCount; $counter ++)
		{
			$this->arrArena [] = new BattleArena ( $this, $this->queue1, $this->queue2, $counter,
					$maxWin, $arrExtra );
		}
		
		
		
	}

	public function incWin($uid)
	{

		if (isset ( $this->mapUidWin [$uid] ))
		{
			$this->mapUidWin [$uid] ++;
		}
		else
		{
			$this->mapUidWin [$uid] = 1;
		}
	}
	
	public function setAfterBattleInfo($uid, $arrAfterBattleInfo)
	{
		$this->mapUidAfterBattleInfo[$uid] = $arrAfterBattleInfo;
	}

	public function getWin($uid)
	{

		if (isset ( $this->mapUidWin [$uid] ))
		{
			return $this->mapUidWin [$uid];
		}
		else
		{
			return 0;
		}
	}

	public function isWin()
	{

		if (! $this->queue1->isEnd ())
		{
			//队列1没有结束，1赢
			Logger::debug ( "queue1 is not end, queue1 wins" );
			return true;
		}
		else if (! $this->queue2->isEnd ())
		{
			//队列2没有结束，2赢
			Logger::debug ( "queue2 is not end, queue2 wins" );
			return false;
		}
		else
		{
			//两个队伍都结束了
			if ($this->queue1->isWin ()) //1 win
			{
				Logger::debug ( "queue1 definitely wins" );
				return true;
			}
			else
			{
				Logger::debug ( "queue1 definitely lose" );
				return false;
			}
		}
	}

	function nextBrid()
	{

		if (! isset ( $this->arrBrid [$this->bridOffset] ))
		{
			Logger::fatal ( "not enough brid" );
			throw new Exception ( 'inter' );
		}
		return $this->arrBrid [$this->bridOffset ++];
	}
	
	function addBattleResult($brid, $battleResult)
	{
		if( isset( $this->arrBattleResult[$brid] ) )
		{
			Logger::fatal('already set brid:%d', $brid);
		}
		$this->arrBattleResult[$brid] = $battleResult;
	}

	/**
	 * 开始战斗
	 * @param array $callback
	 */
	function start()
	{
		$curRound = 0;
		$arrProcess = array(); //记录arena战斗顺序
		
		if( isset( $this->arrNeedResult['defaultAttackWin'] ) )
		{
			$defaultAttackWin =  (bool) ( $this->arrNeedResult['defaultAttackWin'] );
			$this->queue1->setWin($defaultAttackWin);
			Logger::debug('set defaultAttackWin:%d', $defaultAttackWin);
		}
		
		$lastBattleRet = null;//在跨服军团战中
		while ( true )
		{
			Logger::debug('round:%d start', $curRound);
			$isRunning = false;
			foreach ( $this->arrArena as $arena )
			{
				$arena->sendRequest ();
				$isRunning = $isRunning || $arena->isRunning ();
			}
			if (! $isRunning)
			{
				break;
			}
			foreach ( $this->arrArena as $arena )
			{
				$ret = $arena->readResponse ();
				$arrProcess[$curRound][ $arena->getPosition() ] = $ret;
				$lastBattleRet = $ret;
			}

			$curRound ++;
			usort ( $this->arrArena, array ($this, 'arenaCmp' ) );
		}

		$isWin = $this->isWin ();
		Logger::debug ( "battle done, composing result, %s wins. arrProcess:%s", 
					$isWin ? "attacker" : "defender", $arrProcess );
		
		if ($this->callback)
		{
			//目前此处没有具体需求，不确定需要哪些信息，先简单写一下
			$arrReward = call_user_func_array ( $this->callback, array ($isWin ) );
			$arrClient ['reward'] = $arrReward;
		}
		
		//组队战整体战报
		$brid = $this->nextBrid ();
		$arrClient ['team1'] = $this->queue1->getInfo ();
		$arrClient ['team2'] = $this->queue2->getInfo ();
		$arrClient ['url_brid'] = BabelCrypt::encryptNumber ( $brid );
		$arrClient ['brid'] = $brid;
		$arrClient ['bgid'] = $this->bgid;
		$arrClient ['musicId'] = $this->musicId;
		$arrClient ['type'] = $this->type;
		
		$arrClient ['maxWin'] = $this->maxWin;
		//$arrClient ['isWin']改成$arrClient ['result']了
		$arrClient ['result'] = $isWin;
		$arrClient ['arrProcess'] = $arrProcess;
		
		$arrProcessWithSimpleRecord = $arrProcess;
		if( isset( $this->arrNeedResult['simpleRecord'] ) )
		{
			$simpleRoundNum = $this->arrNeedResult['simpleRecord'];
			$arrProcessWithSimpleRecord = self::simplifyBattleRecord($arrProcess, $this->arrBattleResult, $simpleRoundNum);
			
			if( isset( $this->arrNeedResult['saveSimpleRecord'] ) )
			{
				Logger::debug('saveSimpleRecord');
				$arrClient ['arrProcess'] = $arrProcessWithSimpleRecord;
			}
		}
		
		if ( $this->isGuildWar )
		{
			$arrClient['url_brid'] = RecordType::GDW_PREFIX.$arrClient['url_brid'];
			$arrClient['brid'] = RecordType::GDW_PREFIX.$arrClient['brid'];
			$arrClient['mapUidWin'] = $this->mapUidWin;
		}
		
		if ( !empty($this->mapUidInitWin) )
		{
			$arrClient['mapUidInitWin'] = $this->mapUidInitWin;
		}
		
		
		$compressed = true;
		$data = Util::amfEncode ( $arrClient, $compressed, 0, BattleDef::BATTLE_RECORD_ENCODE_FLAGS );
		BattleDao::addRecord ( $brid, $data, $this->db );
		Logger::trace('BattleDao::addRecord data size is %d',strlen($data));
		$arrRet = array (
				'client' => $data,
				'server' => array (
						'result' => $isWin, 
						'brid' => $arrClient['brid'],
				        'maxWin' => $this->maxWin,
						'arrProcess' => $arrProcessWithSimpleRecord,
				        'team1'=>$arrClient['team1'],
				        'team2'=>$arrClient['team2'],
						) 
				);
		
		if ( $this->isGuildWar )
		{
			//跨服军团战需要每个玩家的连胜次数，参加战斗但是没死玩家的血量
			$mapSurvivorHpInfo = array();
			if ( empty($lastBattleRet) )
			{
				Logger::warning('no lastBattleRet');
			}
			else
			{
				if ( $lastBattleRet['appraise'] == 'E' )
				{
					Logger::trace('last battle E');
				}
				else if( $lastBattleRet['appraise'] == 'F' )
				{
					$mapSurvivorHpInfo[$lastBattleRet['defender']] = $this->mapUidAfterBattleInfo[$lastBattleRet['defender']];
				}
				else 
				{
					$mapSurvivorHpInfo[$lastBattleRet['attacker']] = $this->mapUidAfterBattleInfo[$lastBattleRet['attacker']];
				}
			}
			$arrRet['mapUidWin'] = $this->mapUidWin;
			$arrRet['mapSurvivorHpInfo'] = $mapSurvivorHpInfo;
			$arrRet['arrLeftUid'] = array_merge($this->queue1->getLeftUid(), $this->queue2->getLeftUid());
		}
		
		return $arrRet;
	}
	
	
	/**
	 * 根据主战报获取子战报详细信息
	 * 
	 * @param array $arrRecordData
	 * @return array
	 * [
	 * 		replay_id => array
	 * 		{
	 * 			atk_server_id
	 * 			atk_guild_id
	 * 			def_server_id
	 * 			def_guild_id
	 * 			result:int
	 * 			userList => array
	 * 			{
	 * 				uid => {name, fight_force}
	 * 			}
	 * 			//atk_left:{} 还未上场的人
	 * 			//def_left:{}
	 * 			process => array
	 * 			[
	 * 				{
	 * 					result							战斗结果 2:胜，1：平， 0：败
	 * 					brid							战报Id
	 * 					atk_uid							攻方玩家uid
	 * 					def_uid							防守玩家uid
	 * 					atk_max_win						连胜次数，只有在此玩家下场时，才有这个字段
	 * 					def_max_win						
	 * 				}
	 * 			]
	 * 		}
	 * ]
	 * 
	 */
	public static function genBattleProcess($arrRecordData, $arrUserField = array() )
	{
		if( empty($arrUserField) )
		{
			$arrUserField = array('name', 'fight_force', 'htid');
		}
		
		$arrResult = array();
		foreach ( $arrRecordData as $key => $recordData )
		{
			if ( isset($recordData['record_data'])  )
			{
				$recordData = Util::amfDecode($recordData['record_data'], true );
			}
			
			//如果是跨服战报，需要给每个小战报加上前缀
			$bridPrefix = '';
			if ( !is_numeric($recordData['brid']) )
			{
				if ( preg_match ( '/([a-zA-Z_]+)/', $recordData['brid'], $matches ) )
				{
					$bridPrefix = $matches[1];
				}
				else
				{
					throw new InterException('invalid brid:%s', $recordData['brid']);
				}
			}
			
			//得到所有用户信息
			$userList = array();
			$mapUidMaxWin = array();
			$arr = array_merge($recordData['team1']['memberList'], $recordData['team2']['memberList']);
			foreach( $arr as $value )
			{
				$info = array();
				foreach($arrUserField as $field)
				{
					if ( !isset($value[$field])  )
					{
						throw new InterException('not found field:%s', $field);
					}
					$info[$field] = $value[$field];
				}
				$userList[$value['uid']] = $info;
				
				if ( isset($value['maxWin']) )
				{
					$mapUidMaxWin[$value['uid']] = $value['maxWin'];
				}
				else
				{
					$mapUidMaxWin[$value['uid']] = $recordData['maxWin'];
				}
			}
			$arrUidTeam1 = Util::arrayExtract($recordData['team1']['memberList'], 'uid');
			$arrUidTeam2 = Util::arrayExtract($recordData['team2']['memberList'], 'uid');
			
			//战斗过程
			$arrPorcess = array();
			$mapLastRound = array();	//记录每个人最后出现的回合，和最大的战报id。用来定位每个玩家什么时候下场的
			$lastBrid = 0;
			foreach ( $recordData['arrProcess'] as $arenRound => $roundInfo)
			{
				foreach ( $roundInfo as $position => $battleInfo )
				{
					if ( empty($battleInfo) )
					{
						continue;
					}
					if ( $lastBrid < $battleInfo['brid'] )
					{
						$lastBrid = $battleInfo['brid'];
					}
					$mapLastRound[$battleInfo['attacker']] = $arenRound;
					$mapLastRound[$battleInfo['defender']] = $arenRound;
				}
			}
			
			$mapUidWin = $recordData['mapUidWin'];
			//玩家在之前的胜利次数
			if ( isset($recordData['mapUidInitWin']) )
			{
				foreach( $recordData['mapUidInitWin'] as $uid => $num)
				{
					if ( isset($mapUidWin[$uid]) )
					{
						$mapUidWin[$uid] += $num;
					}
					else
					{
						$mapUidWin[$uid] = $num;
					}
				}
			}
			
			foreach ( $recordData['arrProcess'] as $arenRound => $roundInfo )
			{
				$processOfRound = array();
				foreach ( $roundInfo as $position => $battleInfo )
				{
					if ( empty($battleInfo) )
					{
						continue;
					}
					$oneBattle = array(
						'atk_uid' => $battleInfo['attacker'],
						'def_uid' => $battleInfo['defender'],
						'brid' => $battleInfo['brid'],
					);
					if ( BattleDef::$APPRAISAL[$battleInfo['appraise']] < BattleDef::$APPRAISAL['E'] )
					{
						$oneBattle['result'] = 2;
					}
					else if( BattleDef::$APPRAISAL[$battleInfo['appraise']] == BattleDef::$APPRAISAL['E'] )
					{
						$oneBattle['result'] = 1;
					}
					else
					{
						$oneBattle['result'] = 0;
					}
					
					//最后一场战斗的处理方式和其他不一样
					if ( $battleInfo['brid'] < $lastBrid )
					{
						if ( $arenRound == $mapLastRound[$battleInfo['attacker']]
							&& isset($mapUidWin[$battleInfo['attacker']]) )
						{
							$oneBattle['atk_max_win'] = $mapUidWin[$battleInfo['attacker']];
						}
						if ( $arenRound == $mapLastRound[$battleInfo['defender']] 
							&& isset($mapUidWin[$battleInfo['defender']]) )
						{
							$oneBattle['def_max_win'] = $mapUidWin[$battleInfo['defender']];
						}
					}
					else 
					{
						if ( $battleInfo['appraise'] == 'E' )
						{
							if ( isset($mapUidWin[$battleInfo['attacker']]) )
							{
								$oneBattle['atk_max_win'] = $mapUidWin[$battleInfo['attacker']];
							}
							if ( isset($mapUidWin[$battleInfo['defender']]) )
							{
								$oneBattle['def_max_win'] = $mapUidWin[$battleInfo['defender']];
							}
						}
						else
						{
							if( $battleInfo['appraise'] == 'F' )
							{
								$winUserKey = 'defender';
								$winUserMaxKey = 'def_max_win';
								$loseUserKey = 'attacker';
								$loseUserMaxKey = 'atk_max_win';
							}
							else
							{
								$winUserKey = 'attacker';
								$winUserMaxKey = 'atk_max_win';
								$loseUserKey = 'defender';
								$loseUserMaxKey = 'def_max_win';
							}
							
							if ( isset($mapUidWin[$battleInfo[$loseUserKey]]) )
							{
								$oneBattle[$loseUserMaxKey] = $mapUidWin[$battleInfo[$loseUserKey]];
							}
							if ( isset($mapUidMaxWin[$battleInfo[$winUserKey]])
								&& $mapUidWin[$battleInfo[$winUserKey]] >= $mapUidMaxWin[$battleInfo[$winUserKey]] )
							{
								$oneBattle[$winUserMaxKey] = $mapUidWin[$battleInfo[$winUserKey]];
							}
						}
					}
					
					if ( in_array($battleInfo['attacker'], $arrUidTeam1) )
					{
						$processOfRound[$position] = $oneBattle;
					}
					else if ( in_array($battleInfo['attacker'], $arrUidTeam2) )
					{
						//如果攻击者是team2的，就需要交换一下
						$processOfRound[$position] = array(
								'atk_uid' => $battleInfo['defender'],
								'def_uid' => $battleInfo['attacker'],
								'brid' => $battleInfo['brid'],
								'result' => 2 - $oneBattle['result'],
						);
						if ( isset($oneBattle['def_max_win']) )
						{
							$processOfRound[$position]['atk_max_win'] = $oneBattle['def_max_win'];
						}
						if ( isset($oneBattle['atk_max_win']) )
						{
							$processOfRound[$position]['def_max_win'] = $oneBattle['atk_max_win'];
						}
					}
					else
					{
						throw new InterException('invalid record data. uid:%d not in any team', $battleInfo['attacker']);
					}
					if( !empty($bridPrefix) )
					{
						$processOfRound[$position]['brid'] = $bridPrefix.$processOfRound[$position]['brid'];
					}
				}
				
				//如果只有一个擂台，就不分擂台了
				if ( count( $roundInfo ) == 1  )
				{
					if ( empty($processOfRound[0]) )
					{
						Logger::fatal('round:%d is empty. brid:%s', $arenRound, $recordData['brid']);
					}
					else
					{
						$arrPorcess[] = $processOfRound[0];
					}
				}
				else
				{
					$arrPorcess[] = $processOfRound;
				}
			}
			
			$arrResult[$key] = array(
				'atk_server_id' => $recordData['team1']['server_id'],
				'atk_guild_id' => $recordData['team1']['guild_id'],
				'def_server_id' => $recordData['team2']['server_id'],
				'def_guild_id' => $recordData['team2']['guild_id'],
				'result' => $recordData['result'] ? 2 : 0,
				'userList' => $userList,
				'arrProcess' => $arrPorcess,
			);
			
		}
		
		return $arrResult;
	}
	
	public static function simplifyBattleRecord($arrProcess, $arrBattleRecord, $simpleRoundNum = 1)
	{
		foreach($arrProcess as $arenaRound => $roundInfo )
		{
			foreach( $roundInfo as $postion => $battleInfo )
			{
				if( empty( $battleInfo  ) )
				{
					continue;
				}
				$brid = $battleInfo['brid'];
				/**
				 * $arrAction
				 * @var Array
				 * [
				 *     rage:
				 *     attacker:
				 *     defender:
				 *     action:
				 *     round:
				 *     arrReaction:array
				 *     arrChild:array
				 *     [
				 *         rage:
				 *         attacker:
				 *         defender:
				 *         action:
				 *         round:
				 *         arrReaction:array
				 *     ]
				 * ]
				 */
				$arrAction = $arrBattleRecord[ $brid  ]['battle'];
				$arrTeamInfo1 = $arrBattleRecord[ $brid ]['team1'];
				$arrTeamInfo2 = $arrBattleRecord[ $brid ]['team2'];
				$arrHidTeam1 = Util::arrayExtract($arrTeamInfo1['arrHero'], 'hid');
				//$arrHidTeam2 = Util::arrayExtract($arrTeamInfo2, 'hid');
				
				//先记录下每个武将的初始血量。计算扣血时，不能超过这个值（战报中，击杀一个单位时的伤害值可能大于该单位当时的血量）
				$allHeroInfo = array_merge($arrTeamInfo1['arrHero'], $arrTeamInfo2['arrHero']);
				$arrHp = array();
				foreach( $allHeroInfo as $heroInfo )
				{
					$hid = $heroInfo['hid'];
					if( isset( $arrHp[$hid] ) )
					{
						throw new InterException('hid:%d already exist', $hid);
					}
					$arrHp[ $hid ] = isset( $heroInfo['currHp'] ) ? $heroInfo['currHp'] : $heroInfo['maxHp'];
				}
				
				$arrInfo = array();
				foreach( $arrAction as $action  )
				{
					$round = $action['round'];
					if( !isset( $arrInfo[$round] ) )
					{
						$arrInfo[$round] = array(0, 0);
					}
					$attackerHid = $action['attacker'];
					
					//将子技能的结果和主技能合在一起
					if( isset( $action['arrChild'] ) )
					{
						$masterAction = $action;
						unset( $masterAction['arrChild'] );
						$arrSingleAction = array_merge( array($masterAction), $action['arrChild'] );
					}
					else
					{
						$arrSingleAction = array($action);
					}
					
					foreach( $arrSingleAction as $singleAction )
					{
						//attacker的血量变化
						if ( isset(  $singleAction['buffer']  ) )
						{
							$hpAttacker = self::getHpInBuffer( $singleAction['buffer']  );
							
							if( $hpAttacker + $arrHp[$attackerHid] < 0)
							{
								$hpAttacker = -$arrHp[$attackerHid];
							}
							$arrHp[$attackerHid] += $hpAttacker;
							
							if( in_array( $attackerHid, $arrHidTeam1) )
							{
								$arrInfo[$round][0] += $hpAttacker;
							}
							else 
							{
								$arrInfo[$round][1] += $hpAttacker;
							}
						}
						
						//defender的血量变化
						if( isset($singleAction['arrReaction']) )
						{
							foreach( $singleAction['arrReaction'] as $reaction)
							{
								$defendHid = $reaction['defender'];
								$hpDefender = 0;
								if( isset( $reaction['buffer'] ) )
								{
									$hpDefender += self::getHpInBuffer( $reaction['buffer'] );
								}
								
								if( isset(  $reaction['arrDamage'] ) )
								{
									foreach( $reaction['arrDamage'] as $damage)
									{
										$hpDefender -= $damage['damageValue'];
									}
								}
								
								if( $hpDefender + $arrHp[$defendHid] < 0)
								{
									$hpDefender = -$arrHp[$defendHid];
								}
								$arrHp[$defendHid] += $hpDefender;
								
								if( in_array( $reaction['defender'] , $arrHidTeam1)  )
								{
									$arrInfo[$round][0] += $hpDefender;
								}
								else 
								{
									$arrInfo[$round][1] += $hpDefender;
								}
							}
						}
					}
				}
				if( $simpleRoundNum > 1 )
				{
					$arrSimpleInfo = array();
					$i = 0;
					$hp1 = 0;
					$hp2 = 0;
					foreach($arrInfo as $info)
					{
						$i ++;
						$hp1 += $info[0];
						$hp2 += $info[1];
						if( $i >= $simpleRoundNum)
						{
							$arrSimpleInfo[] = array($hp1, $hp2);
							$hp1 = 0;
							$hp2 = 0;
							$i = 0;
						}
					}
					if( $i != 0 )
					{
						$arrSimpleInfo[] = array($hp1, $hp2);
					}
				}
				else 
				{
					$arrSimpleInfo = array_merge($arrInfo);
				}
				
				
				$arrProcess[$arenaRound][$postion]['simpleRecord'] = $arrSimpleInfo;
			}
		}
		return $arrProcess;
	}
	
	public static function getHpInBuffer($arrBuffer)
	{
		$arrHpBufferId = array(9, 78);//影响hp的bufferId
		$hp = 0;
		foreach( $arrBuffer as $buffer )
		{
			if ( in_array($buffer['type'], $arrHpBufferId) )
			{
				$hp += $buffer['data'];
			}
		}
		return $hp;
	}

	/**
	 * 比较两个擂台谁在前面
	 * @param BattleArena $arena1
	 * @param BattleArena $arena2
	 */
	function arenaCmp($arena1, $arena2)
	{

		if ($arena1->getLastBattleRoundNum () == $arena2->getLastBattleRoundNum ())
		{
			return 0;
		}
		else if ($arena1->getLastBattleRoundNum () > $arena2->getLastBattleRoundNum ())
		{
			return 1;
		}
		else
		{
			return - 1;
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
