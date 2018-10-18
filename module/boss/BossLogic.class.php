<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BossLogic.class.php 243431 2016-05-18 09:05:02Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/boss/BossLogic.class.php $
 * @author $Author: MingTian $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-05-18 09:05:02 +0000 (Wed, 18 May 2016) $
 * @version $Revision: 243431 $
 * @brief 
 *  
 **/

class BossLogic
{
	/* (non-PHPdoc)
	 * @see IBoss::getBossOffset()
	*/
	public static function getBossOffset()
	{
		return GameConf::BOSS_OFFSET;
	}

	/* (non-PHPdoc)
	 * @see IBoss::enterBossCopy()
	*/
	public static function enterBoss( $uid, $bossId )
	{
		$ret = array();
		//id合法性和有效性
		BossUtil::checkBossIdValidate( $bossId );
		//获取该boss的信息
		$bossInfo = BossDAO::getBoss( $bossId ); 
		//刷新伤害阵容  ----- 应策划要求该功能废弃掉
/* 		if ( !isset( $bossInfo[BossDef::BOSS_VA][BossDef::SUPERHERO] ) 
		|| !Util::isSameDay( $bossInfo[BossDef::SUPERHERO_REFRESH_TIME] ) )
		{
			$bossInfo[BossDef::BOSS_VA][BossDef::SUPERHERO] = BossUtil::getSuperHero($bossId);
			//更新数据库
			BossDAO::setVaBoss($bossId, $bossInfo[BossDef::BOSS_VA]);
		} */
		
		//这个地方之所以用false是因为后面需要true的时候又重新取了一把！！！！！！！！！
		$atkerInst = Atker::getInstance($uid, $bossId, false);
		$atkerInfo = $atkerInst->getAtkerInfo();
		
		$heroBriefInfo = array();
		if( !empty( $atkerInfo[BossDef::VA_BOSS_ATK]['formation'][$bossId]['arrHero'] ) )
		{
			$formation = $atkerInfo[BossDef::VA_BOSS_ATK]['formation'][$bossId]['arrHero'];
			foreach ( $formation as $heroIndex => $heroInfo )
			{
				$heroBriefInfo[$heroInfo['position']] = self::getNeedInfo($heroInfo, BossDef::$heroNeedInfo);
			}
		}
		$atkerInfo[BossDef::VA_BOSS_ATK]['formation'][$bossId] = $heroBriefInfo;
		
		$killer = array();
		if (isset( $bossInfo[BossDef::BOSS_VA][BossDef::BOSS_KILLER]))
		{
			$killer = $bossInfo[BossDef::BOSS_VA][BossDef::BOSS_KILLER];
		}
		$ret[BossDef::BOSS_KILLER] = $killer;
		$ret[BossDef::TOPTHREE] = self::getAtkerRank($bossId, true);
		$ret[BossDef::FORMATION_SWITCH] = $atkerInfo[BossDef::FORMATION_SWITCH];
		
		//不是boss时间返回的值
		if ( BossUtil::isBossTime( $bossId ) == FALSE )
		{
			$ret['boss_time'] = 0;
			$ret['level'] = $bossInfo[BossDef::BOSS_LEVEL];
			$ret['boss_dead'] = 0;
			$ret[BossDef::VA_BOSS_ATK]['formation'][$bossId] = $heroBriefInfo;
			return $ret;
		}
		
		//boss时间	
		$bossHp = $bossInfo[BossDef::BOSS_HP];
		if ( $bossHp <= 0 )
		{
			$ret['boss_time'] = 0;
			$ret['level'] = $bossInfo[BossDef::BOSS_LEVEL];
			$ret['boss_dead'] = 1;
			$ret[BossDef::VA_BOSS_ATK]['formation'][$bossId] = $heroBriefInfo;
			return $ret;
		}
		
		//得到自己的攻击信息
		Atker::release();//TODO这个地方要小心
		$atkerInst = Atker::getInstance($uid, $bossId );
		$atkerInfo = $atkerInst->getAtkerInfo();
		
		//场景标识
		RPCContext::getInstance()->setSession( 'boss.bossId' , $bossId );
		$bossIdInSession = RPCContext::getInstance()->getSession( 'boss.bossId' );
		Logger::debug('bossid in session when enter is: %d', $bossIdInSession);
		RPCContext::getInstance()->setSession( SPECIAL_ARENA_ID::SESSION_KEY , SPECIAL_ARENA_ID::BOSS );

		$retMerge = array_merge( $atkerInfo, $bossInfo );
		$ret = array_merge( $retMerge, $ret );
		//补上boss的最大血量，攻击者的排名和加成
		$startTime = BossUtil::getBossStartTime( $bossId );
		$endTime = BossUtil::getBossEndTime( $bossId );
		$myAtkHpTotalNow = $atkerInst->getAtkHp();
		
		$ret['boss_time'] = 1;
		$ret['boss_dead'] = 0;
		$ret[BossDef::BOSS_MAXHP] = BossUtil::getBossMaxHp( $bossId , $bossInfo[ BossDef::BOSS_LEVEL ]);
		$ret[BossDef::ATK_RANK] = self::getRank($myAtkHpTotalNow, $bossId, $startTime, $endTime);
		
		return $ret;
	}

	public static function getRank( $atkHp, $bossId, $startTime, $endTime )
	{
		$rank = BossUtil::getAtkerRank( $atkHp, $bossId, $startTime, $endTime );
		return $rank;
	}
	
	
	public static function getNeedInfo( $massArr, $fieldArr )
	{
		$needInfo = array();
		foreach ( $fieldArr as $key )
		{
			if( !isset( $massArr[$key] ) )
			{
				//throw new InterException( 'you want key: %s, not in massInfo: %s', $key, $massArr );
			}
			else
			{
				$needInfo[$key] = $massArr[$key];
			}
		}
	
		return $needInfo;
	}
	public static function getMyRank( $uid )
	{
		$bossId = RPCContext::getInstance()->getSession('boss.bossId');
		self::checkBossValid($bossId);
		$atkerInst = Atker::getInstance($uid, $bossId);
		$atkhp = $atkerInst->getAtkHp();
		$startTime = BossUtil::getBossStartTime( $bossId );
		$endTime = BossUtil::getBossEndTime( $bossId );
		
		return self::getRank($atkhp, $bossId, $startTime, $endTime);
	}

	/* (non-PHPdoc)
	 * @see IBoss::leaveBossCopy()
	*/
	public static function leaveBoss()
	{
		RPCContext::getInstance()->unsetSession( SPECIAL_ARENA_ID::SESSION_KEY );
	}

	/* (non-PHPdoc)
	 * @see IBoss::inspire()
	*/
	public static function inspireBySilver( $uid )
	{
		$bossId = RPCContext::getInstance()->getSession( 'boss.bossId' );
		self::checkBossValid( $bossId );

		$atkerInst = Atker::getInstance( $uid, $bossId );
		$atkerInfo = $atkerInst->getAtkerInfo();
		self::checkInspireCond( $atkerInfo );

		//检查冷却时间
		$conf = btstore_get()->BOSS_INSPIRE_REVIVE[1];
		$cdtime = $conf[ BossDef::INSPIRE_SILVER_CD ];
		if ( $atkerInfo[ BossDef::LAST_INSPIRE_TIME ] + $cdtime > Util::getTime() )
		{
			throw new FakeException( 'inspire in cd, inspireTime: %d', $atkerInfo[ BossDef::LAST_INSPIRE_TIME ] );
		}
		
		$silvernum = $conf[BossDef::INSPIRE_NEED_SILVER];
		$user = EnUser::getUserObj();
		if ( $user->subSilver( $silvernum ) == FALSE )
		{
			throw new FakeException( 'lack silver: %d', $silvernum );
		}
		
		$success = $conf[BossDef::INSPIRE_BASE_RATIO] - $atkerInfo[BossDef::INSPIRE]*$conf[BossDef::INSPIRE_SUCCESS_RATIO];
		$success = $success < 0? 0: $success;
		$rand = rand( 0 , 10000);
		//成不成的反正要扣银币
		$user->update();
			
		if ( $rand > $success )
		{
			Logger::INFO('inspire failed!:rand:%d, need:%d', $rand, $success);
			$atkerInst->setSliverInspireTime();
			$atkerInst->update();
			return false;
		}
		$atkerInst->inspire();
		$atkerInst->update();
		
		return true;
	}

	/* (non-PHPdoc)
	 * @see IBoss::inspireByGold()
	*/
	public static function inspireByGold( $uid )
	{
		$bossId = RPCContext::getInstance()->getSession( 'boss.bossId' );
		self::checkBossValid( $bossId );

		$atkerInst = Atker::getInstance( $uid, $bossId );
		$atkerInfo = $atkerInst->getAtkerInfo();
		self::checkInspireCond( $atkerInfo );

		//减少金币
		$conf = btstore_get()->BOSS_INSPIRE_REVIVE[1];
		$inspireGold = $conf[BossDef::INSPIRE_NEED_GOLD];
		$user = EnUser::getUserObj();
		if ( $user->subGold($inspireGold, StatisticsDef::ST_FUNCKEY_BOSS_INSPIRE ) == FALSE )
		{
			throw new FakeException( 'lack gold: %d', $inspireGold );
		}
		$atkerInst->inspire( false );
			
		//更新用户
		$user->update();
		$atkerInst->update();
		
		return true;
	}

	public static function checkInspireCond( $atkerInfo )
	{
		$conf = btstore_get()->BOSS_INSPIRE_REVIVE[1];
		$inspireLimit = $conf[BossDef::INSPIRE_LIMIT];
		
		if ( $atkerInfo[BossDef::INSPIRE] >= $inspireLimit)
		{
			throw new FakeException( 'already max inspire: %d', $atkerInfo[BossDef::INSPIRE] );
		}
	}

	public static function checkBossValid( $bossId )
	{
		Logger::debug('bossid in check is: %d', $bossId);
		if ( empty( $bossId ) )
		{
			throw new FakeException( 'empty bossid' ) ;
		}
		BossUtil::checkBossIdValidate($bossId);
		if ( BossUtil::isBossTime( $bossId ) == FALSE )
		{
			throw new FakeException( 'invalid time, bossid: %d', $bossId );
		}
	}


	public static function attack($uid)
	{
		$bossId = RPCContext::getInstance()->getSession( 'boss.bossId' );
		self::checkBossValid( $bossId );

		$startTime = BossUtil::getBossStartTime( $bossId );
		$endTime = BossUtil::getBossEndTime( $bossId );

		$atkerInst = Atker::getInstance($uid, $bossId);
		$atkerInfo = $atkerInst->getAtkerInfo();

		$atkTime = $atkerInfo[BossDef::LAST_ATK_TIME];
		//检查当前是否战斗在冷却
		if ( !self::isAttackTime($atkTime, $atkerInfo[BossDef::FLAGS] ) )
		{
			throw new FakeException( 'in cd atktime: %d, flag: %d', $atkTime, $atkerInfo[BossDef::FLAGS] );
		}

		return self::__attack($uid,$bossId, $startTime, $endTime);
	}

	public static function __attack($uid, $bossId, $boss_start_time, $boss_end_time )
	{
		$success_key = 'success';
		$atkerInst = Atker::getInstance( $uid, $bossId );
		$atkerInfo = $atkerInst->getAtkerInfo();

		$startTime =  BossUtil::getBossStartTime( $bossId );
		$endTime = BossUtil::getBossEndTime( $bossId );
		
		//用户战斗信息
		$user = EnUser::getUserObj( $uid );
		if( $atkerInfo[BossDef::FORMATION_SWITCH] == BossDef::SWITCH_OPEN && !empty( $atkerInfo[BossDef::VA_BOSS_ATK]['formation'][$bossId] ) )
		{
			$btlAtkerInfo = $atkerInfo[BossDef::VA_BOSS_ATK]['formation'][$bossId];
		}
		else 
		{
			$btlAtkerInfo = $user->getBattleFormation();
		}
		
		//加成
		$inspire = $atkerInfo[BossDef::INSPIRE];
		$additionArr = btstore_get()->BOSS_INSPIRE_REVIVE[1][BossDef::ADDITION_ARR]->toArray();
		foreach ( $additionArr as $addIndex => $addInfo )
		{
			//index 0 为属性id 1为属性值
			$additionArr[$addIndex][1] = $addInfo[1] *$inspire;
		}
		//鼓舞得到的全体加成
		$superHeroArr = self::getSuperHero( $bossId );
		$conf = btstore_get()->BOSS[$bossId][BossDef::SUPERHERO_NUM_ARR];
	//==============================================	
		$superHeroAdd = 0;
		foreach ( $btlAtkerInfo['arrHero'] as $key => $value )
		{
			if ( in_array( $value[PropertyKey::HTID] , $superHeroArr['good']) )
			{
				$superHeroAdd += $conf[0][1];
			}
			elseif ( in_array( $value[PropertyKey::HTID] , $superHeroArr['better']) )
			{
				$superHeroAdd += $conf[1][1];
			}
			elseif ( in_array( $value[PropertyKey::HTID] , $superHeroArr['best']) )
			{
				$superHeroAdd += $conf[2][1];
			}
		}
	//===============================================
	//对每个英雄加成	
		foreach ( $btlAtkerInfo['arrHero'] as $key => $value )
		{
			foreach ( $additionArr as $inspireAddIndex => $inspireAddInfo )
			{
				if ( !isset( $btlAtkerInfo['arrHero'][$key][PropertyKey::$MAP_CONF[$inspireAddInfo[0]]] ) )
				{
					$btlAtkerInfo['arrHero'][$key][PropertyKey::$MAP_CONF[$inspireAddInfo[0]]] = $inspireAddInfo[1] + $superHeroAdd;
				}
				else 
				{
					$btlAtkerInfo['arrHero'][$key][PropertyKey::$MAP_CONF[$inspireAddInfo[0]]] += ($inspireAddInfo[1]+ $superHeroAdd);
				}
			}
		}
		
		$bossInfo = BossDAO::getBoss( $bossId );
		//如果boss血量为0,则boss战斗结束
		if ( $bossInfo[BossDef::BOSS_HP] <= 0 )
		{
			return array($success_key => FALSE);
		}
		$bossLv = $bossInfo[ BossDef::BOSS_LEVEL ];

		//boss战斗信息
		$baseId = BossUtil::getBossBaseId( $bossId, $bossLv );
		$btlBossInfo = BossUtil::getBossFormationInfo( $bossId, $bossLv);
		if ( count( $btlBossInfo[ 'arrHero' ] ) != 1  )
		{
			//throw new ConfigException( 'hero num in boss is > 1' );
		}
		//设置boss血量
		foreach ( $btlBossInfo[ 'arrHero' ]  as $key => $value )
		{
			$btlBossInfo[ 'arrHero' ][$key][PropertyKey::CURR_HP] = $bossInfo[BossDef::BOSS_HP];
		}

		//其他相关信息
		$btlExInfo = BossUtil::getBtlExInfo( $baseId );
		$callback = 'BossLogic::atkCallBack';

		$atkRet = EnBattle::doHero
		($btlAtkerInfo,$btlBossInfo,0/* $btlExInfo['type'] */, $callback);

		//更新血量，玩家不需要更新
		$bossHp =  $atkRet['server']['team2'][0]['hp'];
		$bossCostHp = $atkRet['server']['team2'][0]['costHp'];

		$bossAffectRows = BossDAO::subBossHP($bossId, $bossCostHp);
		//判定击杀问题
		if ( $bossHp < 0 || ( $bossAffectRows == 0 && $bossCostHp > 0 ) )
		{
			//locker
			$lock = new Locker();
			$lock->lock(BossDef::LOCK_PREFIX . $bossId);

			$_boss_info = BossDAO::getBoss( $bossId );
			if ( $_boss_info[BossDef::BOSS_HP] == 0 )
			{
				$lock->unlock(BossDef::LOCK_PREFIX . $bossId );
				return array($success_key => FALSE);
			}

			//设置boss血量为0
			BossDAO::setBossHP($bossId, 0);
			$lock->unlock(BossDef::LOCK_PREFIX . $bossId);

			//数据的一致性问题
			if ( $bossAffectRows == 0 && $bossHp > 0 )
			{
				//单次攻击奖励回滚
				$user->rollback();
				//设置boss血量为易击杀的
				$_boss_hp = intval($bossCostHp / 10);
				if ( $_boss_hp <= 0 )
				{
					$_boss_hp = 1;
				}
				foreach ( $btlBossInfo[ 'arrHero' ] as $key => $value )
				{
					$btlBossInfo[ 'arrHero' ][$key]['currHp'] = $_boss_hp;
				}

				$atkRet = EnBattle::doHero($btlAtkerInfo,$btlBossInfo,$btlExInfo['type'], $callback);
				if ( $atkRet['server']['team2'][0]['hp'] != 0 )
				{
					Logger::FATAL("1/10 boss cost hp:%d, but boss not kill!", $bossCostHp);
				}
				$bossCostHp = $atkRet['server']['team2'][0]['costHp'];
			}

			$bossHp = 0;
			
			//通知场景中的人boss死了
			$push = $user->getTemplateUserInfo();
			$push['bossId'] = $bossId;
			
			RPCContext::getInstance()->sendFilterMessage( 'arena', SPECIAL_ARENA_ID::BOSS, PushInterfaceDef::BOSS_KILL, array( $push ));
			RPCContext::getInstance()->setSession(BossDef::SESSION_KILLER . $bossId, 1);
			
			$killerInfo = $user->getTemplateUserInfo();
			$killerInfo[BossDef::KILL_TIME] = Util::getTime();
			$bossInfo[BossDef::BOSS_VA][BossDef::BOSS_KILLER] = $killerInfo;
			BossDAO::setVaBoss($bossId, $bossInfo[BossDef::BOSS_VA]);
			
			Util::asyncExecute('boss.reward', array( $bossId , $bossLv, $startTime, $endTime, $uid));
		}

		//增加冷却时间和攻击血量
		$atkerInst->addAtkHp( $bossCostHp );
		$atkerInst->setAtkTime( Util::getTime() );
		$atkerInst->addAtkNum( 1 );
		$atkerInst->setSubCd( 0 );
		$atkerInst->update();
		
		$user->update();
		$bag = BagManager::getInstance()->getBag($uid);
		$bag-> update();
		
		//发送消息给boss场景中的其他人
		$atkList = BossUtil::getBossAttackHpTop($bossId, $startTime, $endTime, 10);
		//这是有排名的列表
		$arrUid = Util::arrayExtract( $atkList , 'uid');
		$return = array (
				BossDef::BOSS_HP => $bossHp,
				BossDef::ATK_UNAME => $user->getUname(),
				BossDef::BOSS_COST_HP => $bossCostHp,
				'uid'=> $uid,
				BossDef::ATK_LIST => $arrUid,
		);

		$sended = false;
		
		//现在是百分之10
		if ( $bossHp <= 0 ||rand(0, BossConf::BOSS_SEND_MAX_PROBABILITY) < BossConf::BOSS_SEND_PROBABILITY )
		{
			RPCContext::getInstance()->
			sendFilterMessage('arena', SPECIAL_ARENA_ID::BOSS, PushInterfaceDef::BOSS_UPDATE, $return);
			
			$sended = true;
			Logger::debug('send boss front, %s', $return);
		}
		
		if (!$sended)
		{
			Logger::debug('send boss front not, %s', $return);
		}

		//返回给当前用户消息
		$return['attack_hp'] = $atkerInst->getAtkHp();
		$return['success'] = TRUE;
		$return['fight_ret'] = $atkRet['client'];
		$return['rank'] = self::getRank($return['attack_hp'], $bossId, $startTime, $endTime);
		
		//通知成就系统
		$atkTime = Util::getTime();
		EnAchieve::updateBoss($uid, $atkTime);
		EnNewServerActivity::updateAttackBoss($uid, $bossCostHp);
		
		return $return;
	}


	/* (non-PHPdoc)
	 * @see IBoss::subCdTime()
	*/
	public static function revive( $uid )
	{
		$bossId = RPCContext::getInstance()->getSession( 'boss.bossId' );
		self::checkBossValid( $bossId );
		
		$user = EnUser::getUserObj();
		$vip  = $user->getVip();
		$isOpenRevive = btstore_get()->VIP[$vip]['bossReviveOpen'];
		if ( $isOpenRevive != 1 )
		{
			throw new FakeException( 'vip: %d low to revive', $vip);
		}
		
		$atkerInst = Atker::getInstance( $uid, $bossId);
		$atkerInfo = $atkerInst->getAtkerInfo();

		$attackTime = $atkerInfo[BossDef::LAST_ATK_TIME];
		$flags = $atkerInfo[BossDef::FLAGS];

		//检测是否需要
		if ( self::isAttackTime($attackTime, $flags) == TRUE )
		{
			throw new FakeException( 'no need sub cd time!attack_time:%d, flags:%d',$attackTime, $flags );
		}
		if ( $flags & BossDef::FLAGS_SUB_CD)
		{
			throw new FakeException( 'has sub cdtime!' );
		}
		
		//减少金币
		$conf = btstore_get()->BOSS_INSPIRE_REVIVE[1];
		$reviveNum = $atkerInst->getReviveNum();
		$needGold = $conf[BossDef::REBIRTH_GOLD_BASE] + $reviveNum * $conf[BossDef::REBIRTH_GOLD_INC];
		
		if ( $user->subGold($needGold, StatisticsDef::ST_FUNCKEY_BOSS_REVIVE) == FALSE )
		{
			throw new FakeException( 'lack gold' );
		}
		
		$flags |= BossDef::FLAGS_SUB_CD;
		$atkerInst->setSubCd( $flags );
		$atkerInst->addReviveNum( 1 );

		//用户更新
		$user->update();
		$atkerInst->update();

		return TRUE;
	}

	/* (non-PHPdoc)
	 * @see IBoss::over()
	*/
	public static function over( $uid )
	{
		$bossId = RPCContext::getInstance()->getSession( 'boss.bossId' );
		BossUtil::checkBossIdValidate($bossId);
		
		$user = EnUser::getUserObj( $uid );

		$bossInfo = BossDAO::getBoss( $bossId );
		$bossLv = $bossInfo[BossDef::BOSS_LEVEL];
		$bossHp = $bossInfo[BossDef::BOSS_HP];
		$startTime = $bossInfo[BossDef::START_TIME];
		$endTime = BossUtil::getBossEndTime($bossId, $startTime);
		$userLevel = $user->getLevel();
		
		if ( Util::getTime() < $endTime && $bossHp != 0 )
		{
			Logger::DEBUG('boss over request expired!');
			return array('is_expired' => 1);
		}

		$isKiller = RPCContext::getInstance()->getSession(BossDef::SESSION_KILLER . $bossId ) == 1? 1:0;
		$atkerInst = Atker::getInstance($uid, $bossId, false);
		$atkHp = $atkerInst->getAtkHp();
		$order = self::getRank($atkHp, $bossId, $startTime, $endTime);
		
		//通知前端boss结束
		$return = array(
				'is_expired'			=> 0,
				'boss_id'				=> $bossId,
				'is_killer'				=> $isKiller,
				'attack_hp'				=> $atkHp,
		);

		if ( $isKiller )
		{
			$return['reward_kill'] = BossUtil::getBossReward($bossId, 0, $bossLv, $userLevel);
			RPCContext::getInstance()->unsetSession(BossDef::SESSION_KILLER . $bossId);
		}
		else
		{
			$return['reward_kill'] = array();
		}
		if ( $atkHp <= 0 )
		{
			$return['rank']			= 0;
			$return['reward_rank']	= array(); //BossUtil::getBossReward($bossId, 1, $bossLv);
		}
		else
		{
			$return['rank']			=	$order;
			$return['reward_rank']	=	BossUtil::getBossReward($bossId, $order, $bossLv, $userLevel);
		}

		return $return;
	}

	/**
	 *
	 * 给battle端的callback
	 *
	 * @param array $attack_ret
	 *
	 * @return array
	 * <code>
	 * {
	 * 		'belly':int
	 * 		'prestige':int
	 * 		'experience':int
	 * }
	 * </code>
	 */
	public static function atkCallBack( $atkRet )
	{
		$bossId = RPCContext::getInstance()->getSession( 'boss.bossId' );
		self::checkBossValid( $bossId );
		$user = EnUser::getUserObj();
		$level = $user->getLevel();
		
		//增加奖励
		$silver = self::silverPerAtk( $bossId );
		$prestige = self::prestigePerAtk($bossId );
		
		if ( $user->addSilver( $silver ) == FALSE )
		{
			throw new InterException( 'attack callback add silver failed' );
		}
		if ( $user->addPrestige( $prestige ) == FALSE )
		{
			throw new InterException( 'attack callback add soul failed' );
		}

		//此处user和bag均没有更新

		return array (
				BossDef::REWARD_SILVER => $silver,
				BossDef::REWARD_PRESTIGE => $prestige,
		);

	}

	/**
	 *
	 * boss结束
	 *
	 * @param int $boss_id
	 * @param int $start_time
	 * @param int $end_time
	 *
	 * @return
	 */
	public static function bossEnd($boss_id, $start_time, $end_time)
	{
		$boss_info = BossDAO::getBoss($boss_id);
		$level = $boss_info[BossDef::BOSS_LEVEL];

		//如果boss存活,则boss level降低
		if ( $boss_info[BossDef::BOSS_HP] > 0 )
		{
			$level -= 1;
			$min_level = BossUtil::getBossMinLevel($boss_id);
			if ( $level < $min_level )
			{
				$level = $min_level;
			}
		}
		//如果boss死亡且boss被杀时间满足升级条件,则boss level增加
		elseif( isset( $boss_info[BossDef::BOSS_VA][BossDef::BOSS_KILLER][BossDef::KILL_TIME] ) )
		{
			$killTime =  $boss_info[BossDef::BOSS_VA][BossDef::BOSS_KILLER][BossDef::KILL_TIME];
			$lvTime = btstore_get()->BOSS[$boss_id][BossDef::LV_TIME];
			$lastTime = $killTime - $start_time;
			if( $lastTime <= $lvTime && $lastTime > 0 )
			{
				$level += 1;
				$max_level = BossUtil::getBossMaxLevel($boss_id);
				if ( $level > $max_level )
				{
					$level = $max_level;
				}
			}
		}
		$boss_max_hp = BossUtil::getBossMaxHp($boss_id, $level);
		$time = BossUtil::getBossStartTime($boss_id, $end_time);

		//如果开始时间已经小于当前时间,则选取下一个时间
		if ( $time < Util::getTime() && $time != 0 )
		{
			$time = BossUtil::getBossEndTime($boss_id);
			//如果得到的时间为0,则表示boss的活动已经结束
			if ( $time == 0 )
			{
				Logger::INFO('boss:%d activity is over!', $boss_id);
				return;
			}
			$time = BossUtil::getBossStartTime($boss_id, $time);
		}

		//如果得到的时间为0,则表示boss的活动已经结束
		if ( $time == 0 )
		{
			Logger::INFO('boss:%d activity is over!', $boss_id);
			return;
		}
		else if ( $time == $boss_info[BossDef::START_TIME] )
		{
			Logger::WARNING('boss:%d timer has execute!', $boss_id);
			return;
		}
		//否则开始下一次的活动
		else
		{
			BossDAO::setBoss($boss_id, $boss_max_hp, $level, $time);
			$time -= BossConf::BOSS_COMING_TIME;
			TimerTask::addTask(0, $time, 'boss.bossComing', array($boss_id));
		}
	}

	/**
	 *
	 * 发送奖励
	 *
	 * @param int $boss_id				boss id
	 * @param int $start_time			boss开始时间点
	 * @param int $end_time				boss结束时间点
	 * @param int $killer				击杀者uid
	 *
	 * @return NULL
	 */
	public static function reward($boss_id, $boss_level, $start_time, $end_time, $killer=NULL)
	{
		sleep( BossConf::BOSS_REWARD_SLEEP_TIME);
		
		$topThreeAndKiller = array(
			'killer' => array( 0 ,array()),
			'rank' => array_fill( 1 , 3, array(0,0,array())),
		);
		
		if ( $killer!==NULL )
		{
			$killer_reward = self::rewardUser( $killer, $boss_id,$boss_level, 0);
			$topThreeAndKiller['killer'] = array($killer, $killer_reward);
			Logger::info('boss kill reward killer: %s, reward: %s',$killer, $killer_reward);
		}
		
		
		$boss_attack_list = BossUtil::getBossAttackListSorted($boss_id, $start_time, $end_time);
		$boss_max_hp = BossUtil::getBossMaxHp($boss_id, $boss_level);

		$order = 0;
		foreach ( $boss_attack_list as $uid => $attack_hp )
		{
			try 
			{
				$order++;
				$user = EnUser::getUserObj($uid);
				$reward = self::rewardUser($uid, $boss_id, $boss_level, $order );
				$user->update();
				if ( $order <= 3 )
				{
					$topThreeAndKiller['rank'][$order]= array(
						$uid, BossUtil::getBossAttackHPPercent($attack_hp, $boss_max_hp, $reward),
					);
				}
				Logger::info('boss rank reward uid: %d,order:%d', $uid, $order);
			}
			catch(Exception $e)
			{
				Logger::FATAL('send boss reward to user:%d failed!order:%d', $uid, $order);
			}
			
			try 
			{
				EnAchieve::updateBossRank($uid, $boss_id, $order);
			}
			catch (Exception $e)
			{
				Logger::FATAL('udate boss achieve to user:%d failed!order:%d', $uid, $order);
			}
		}
		
		ChatTemplate::sendBossResult($boss_id, $topThreeAndKiller );

	}

	/**
	 *
	 * 是否可以攻击
	 *
	 * @param int $last_attack_time
	 * @param int $flags
	 *
	 * @return boolean
	 */
	private static function isAttackTime($last_attack_time, $flags)
	{
	 	$conf = btstore_get()->BOSS_INSPIRE_REVIVE[1];
	 	$cdTime = $conf[BossDef::ATK_CD];
		$next_attack_time = $last_attack_time + $cdTime;
		if ( $flags & BossDef::FLAGS_SUB_CD_TIME )
		{
			$next_attack_time = Util::getTime()-10;;
		}

		if ( $next_attack_time <= Util::getTime() )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 *
	 * 初始化boss(为script/BossInit.class.php提供支持)
	 *
	 * @param int $boss_id
	 *
	 * @return NULL
	 */
	public static function initBoss($boss_id)
	{
		$boss_init_level = BossUtil::getBossInitLevel($boss_id);
		//如果server start time被设置
		$server_start_time = strtotime(GameConf::SERVER_OPEN_YMD);
		if ( $server_start_time < Util::getTime() )
		{
			$server_start_time = Util::getTime();
		}

		$start_time = BossUtil::getBossStartTime($boss_id, $server_start_time);
		$end_time = BossUtil::getBossEndTime($boss_id, $server_start_time);

		//如果刚好处于一个boss战斗周期内
		if ( $start_time <= Util::getTime() && $end_time > Util::getTime() )
		{
			$start_time = BossUtil::getBossStartTime($boss_id, $end_time);
		}

		//如果boss已经结束
		if ( $start_time == 0 )
		{
			Logger::INFO('boss:%d activity is over!', $boss_id);
			return;
		}

		$coming_time = $start_time - BossConf::BOSS_COMING_TIME;
		TimerTask::addTask(0, $coming_time, 'boss.bossComing', array($boss_id));
		Logger::INFO('init boss:%d, start_time:%s', $boss_id, date('Y-m-d H:i:s', $start_time));
		BossDAO::initBoss($boss_id, BossUtil::getBossMaxHp($boss_id, $boss_init_level),
		$boss_init_level, $start_time);
	}

	public static function getSuperHero( $bossId )
	{
		return array(
			'good' => array(),
			'better' => array(),
			'best' => array(),	
		);
		//特殊武将功能废弃
		
		//不用加锁了
		$boss = BossDAO::getBoss( $bossId );
		$va = $boss[BossDef::BOSS_VA];
		if (  !isset( $va[BossDef::SUPERHERO] ) )
		{
			throw new FakeException( 'superhero null when get' );
		}
		$superHeroArr = $va[BossDef::SUPERHERO];
		if (!Util::isSameDay( $boss[BossDef::SUPERHERO_REFRESH_TIME] ))
		{
			$superHeroArr = BossUtil::getSuperHero($bossId);
		}
		
		return $superHeroArr;
	}
	
	private static function silverPerAtk( $boss_id )
	{
		return BossUtil::getSilverPerAttack($boss_id) ;
	}

	private static function prestigePerAtk( $boss_id)
	{
		return BossUtil::getPrestigePerAttack($boss_id) ;
	}
	
	public static function getAtkerRank( $bossId, $justThree = false )
	{
 		$bossStartTime = BossUtil::getBossStartTime($bossId);
		$bossEndTime = BossUtil::getBossEndTime( $bossId );
		//如果到头了 或者是 boss还没开始
		if ( empty( $bossStartTime) || Util::getTime() < $bossStartTime )
		{
			//获取上一次的排名
			$bossStartTime = BossUtil::getBeforeBossStartTime( $bossId );
			$bossEndTime = BossUtil::getBeforeBossEndTime( $bossId );
		}
		
		//一次也没有开boss
		if ( empty( $bossStartTime ) )
		{
			return array();
		}
		//获取排名列表
		$rankNum = $justThree? 3:10;
		$ret = BossUtil::getBossAttackHpTop($bossId, $bossStartTime, $bossEndTime, $rankNum);
		//这是有排名的列表
		$arrUid = Util::arrayExtract( $ret , 'uid');
		$arrSquand = EnUser::getArrUserSquad( $arrUid );
		$userRankInfo = EnUser::getArrUser($arrUid, array('uid', 'uname', 'level', 'guild_id','vip'));
		
		$arrGuildId = Util::arrayExtract($userRankInfo, 'guild_id');
		$arrGuildInfo = EnGuild::getArrGuildInfo($arrGuildId, array(GuildDef::GUILD_NAME));
		
		foreach ( $ret as $key => $oneAtker )
		{
			$ret[$key]['uid'] = $oneAtker['uid'] ;
			$ret[$key]['level'] = $userRankInfo[$oneAtker['uid']]['level'] ;
			$ret[$key]['name'] = $userRankInfo[$oneAtker['uid']]['uname'] ;
			$ret[$key]['vip'] = $userRankInfo[$oneAtker['uid']]['vip'];
			$ret[$key]['hpCost'] = $oneAtker[BossDef::ATK_HP];
			$ret[$key]['squad'] = $arrSquand[$oneAtker['uid']];
			if ( !empty( $userRankInfo[$oneAtker['uid']]['guild_id'] ) )
			{
				$guildId =  $userRankInfo[$oneAtker['uid']]['guild_id'];
				if( !empty($arrGuildInfo[$guildId][GuildDef::GUILD_NAME]) )
				{
					$ret[$key][GuildDef::GUILD_NAME] = $arrGuildInfo[$guildId][GuildDef::GUILD_NAME];
				}
			}
			unset( $ret[$key][BossDef::ATK_HP] );
		}
		
		return $ret;
	}
	
	private static function rewardUser( $uid, $boss_id,$boss_level, $order )
	{
		$userLevel = EnUser::getUserObj($uid)->getLevel();
		
		$standardArr = BossUtil::getBossReward($boss_id, $order, $boss_level, $userLevel);
		
		if (!empty( $standardArr ) && $order != 0)
		{
			EnReward::sendReward($uid, RewardSource::BOSS_RANK, $standardArr);
		}
		elseif( !empty( $standardArr ) && $order == 0 ) 
		{
			EnReward::sendReward($uid, RewardSource::BOSS_KILL, $standardArr);
		}
		
		
		return $standardArr;
	}
	
	public static function setBossFormation($bossId, $uid)
	{
		$user = EnUser::getUserObj( $uid );
		$btlAtkerInfo = $user->getBattleFormation();
		BossUtil::checkBossIdValidate($bossId);
		$atker = Atker::getInstance($uid, $bossId, false);
		$atker->setBossFormation($btlAtkerInfo);
		
		$atker->update();
	}
	
	public static function setFormationSwitch( $uid, $bossId, $switch )
	{
		if( $switch != BossDef::SWITCH_OPEN && $switch != BossDef::SWITCH_CLOSE )
		{
			throw new FakeException( 'invalid switch: %s', $switch );
		}
		BossUtil::checkBossIdValidate($bossId);
		$atker = Atker::getInstance($uid, $bossId, false);
		$atker->setFormationSwitch($switch);
		$atker->update();
	}
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
