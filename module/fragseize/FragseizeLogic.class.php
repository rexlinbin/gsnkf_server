<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FragseizeLogic.class.php 258614 2016-08-26 09:04:39Z ShuoLiu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/fragseize/FragseizeLogic.class.php $
 * @author $Author: ShuoLiu $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-08-26 09:04:39 +0000 (Fri, 26 Aug 2016) $
 * @version $Revision: 258614 $
 * @brief 
 *  
 **/
class FragseizeLogic
{
	/**
	 * 获取所有碎片信息(直接从库中拉取的)
	 * @param unknown $uid
	 * @return multitype:number unknown
	 */
	public static function getSeizerInfo( $uid )
	{
		$fragInst = FragseizeObj::getInstance( $uid );
		return $fragInst->getAllFrags();
	}
	
	/**
	 * 是否处于全局免战
	 * @return boolean
	 */
	public static function worldPeace()
	{
		$conf = btstore_get()->LOOT;
		$peaceTime = $conf[ 'peaceTime' ]->toArray();
		foreach ( $peaceTime as $index => $onePeaceTime )
		{
			$peaceBeginTime = $onePeaceTime[0];
			$peaceEndTime = $onePeaceTime[1];
			$hms = strftime("%H:%M:%S", Util::getTime());
			if ( $hms > $peaceBeginTime && $hms <= $peaceEndTime )
			{
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * 获取个人免战结束时间
	 * @param unknown $uid
	 * @return unknown
	 */
	public static function getWhiteFlagEndTime( $uid )
	{
		$fragInst = FragseizeObj::getInstance( $uid );
		$whiteEndTime = $fragInst->getWhiteEndTime();
		
		return $whiteEndTime;
	}
	
	/**
	 * 个人免战开启
	 * @param unknown $uid
	 * @param unknown $type
	 * @param unknown $whiteItemFront
	 * @throws FakeException
	 */
	public static function whiteFlag( $uid, $type )
	{
		$conf = btstore_get()->LOOT;
		$fragInst = FragseizeObj::getInstance( $uid );
		
		
		//金币免战
		if ( $type == FragseizeDef::WHITE_BYGOLD )
		{
			$user = EnUser::getUserObj( $uid );
			if ( !$user->subGold( $conf['whiteGold'] , StatisticsDef::ST_FUNCKEY_WHITE_FLAG ) )
			{
				throw new FakeException( 'lack gold need: %d now: %d', $conf['whiteGold'], $user->getGold() );
			}
			$user->update();
		}
		//免战牌免战
		else 
		{
			$whiteItem = $conf['whiteItem']->toArray();
			$bag = BagManager::getInstance()->getBag( $uid );
			foreach ( $whiteItem as $index => $val )
			{
				if ( !$bag->deleteItembyTemplateID( $val[0] , $val[1]) )
				{
					throw new FakeException(  'lack item: %d need num: %d', $val[0], $val[1]);
				}
				
			}
			$bag->update();
		}
		
		//如果加上这次免战之后超过时间的上限，则置为上限时间
		$whiteEndTime = $fragInst->getWhiteEndTime();
		if ( $whiteEndTime < Util::getTime() )
		{
			$whiteEndTime = Util::getTime();
		}
		if ( $whiteEndTime + $conf['whiteOnce'] > Util::getTime() + $conf['whiteLimit'] )
		{
			$newWhiteTime = Util::getTime() + $conf['whiteLimit'];
			Logger::debug('white time: %d beyond limit, just add to limit', $whiteEndTime + $conf['whiteOnce']);
		}
		else 
		{
			$newWhiteTime = $whiteEndTime + $conf['whiteOnce'];
		}
		//设置免战结束时间并更新数据库
		$fragInst->setWhiteTime( $newWhiteTime );
		$fragInst->updateSeizer();
	}
	
	/**
	 * 宝物合成
	 * @param unknown $uid
	 * @param unknown $treasureId
	 * @throws FakeException
	 * @throws InterException
	 * @return string|boolean
	 */
	public static function fuse( $uid, $treasureId, $fuseNum = 1 )
	{
		if( $fuseNum > 1 )
		{
			$userObj = EnUser::getUserObj($uid);
			$userVip = $userObj->getVip();
			$userLevel = $userObj->getLevel();
			$needLevelforVip = btstore_get()->VIP[ $userVip ]['fragseizeQuickFuse'];
			if( $userLevel < $needLevelforVip )
			{
				throw new FakeException( 'fusing server not allowed for level:%d', $userLevel );	
			}
		}
		
		$bag = BagManager::getInstance()->getBag( $uid );
		if ( $bag->isFullByTemplate( array( $treasureId ) ) )
		{
			throw new FakeException( 'trea bag is full' );
		}
		
		//合成需要的碎片和数量
		$subFragArr = array();
		$needFrags = TreasureItem::getFragments( $treasureId );
		foreach ( $needFrags as $fragid )
		{
			$subFragArr[ $fragid ] = 1;//合成一个宝物时每种碎片的数量
		}
		if ( empty( $subFragArr ) )
		{
			throw new FakeException( 'no fragId need for treasureId: %d?', $treasureId );
		}

		//合成牵扯到减少碎片，需要锁一下
		$locker = new Locker();
		$locker->lock( self::lockKey($uid, $treasureId) );
		try 
		{
			$fragInst = FragseizeObj::getInstance($uid);
			$fragsForTreaOwn = $fragInst->getFragsByTid( $treasureId );
			
			$minNum = -1;
			foreach ( $fragsForTreaOwn as $ownFragId => $ownFragNum )
			{
				if( $minNum < 0 )
				{
					$minNum = $ownFragNum;
				}
				else
				{
					if($ownFragNum < $minNum)
					{
						$minNum = $ownFragNum;
					}
				}
			}
			$canFuseNum = $minNum/1;//合成一次消耗一个
				
				
			if( $fuseNum > $canFuseNum || $fuseNum <= 0 || $fuseNum > FragseizeConf::MAX_FUSE_NUM  )
			{
				$locker->unlock( self::lockKey($uid, $treasureId) );
				Logger::warning( 'lack frasgs or beyond max can, need: %s own: %s', $subFragArr, $fragsForTreaOwn );
				return 'fail';
			}
			
			foreach ( $subFragArr as $fragId => $fragNum )
			{
				if ( !isset( $fragsForTreaOwn[ $fragId ] ) || $fragsForTreaOwn[ $fragId ] < $fragNum * $fuseNum )
				{
					//锁上后发现不够返回fail
					$locker->unlock( self::lockKey($uid, $treasureId) );
					Logger::warning( 'lack frsgs, need: %s own: %s', $subFragArr, $fragsForTreaOwn );
					return 'fail';
				}
				$subFragArr[$fragId] = $fuseNum*$fragNum;
			}
			
			$fragInst->subFrags( $subFragArr );
			$fragInst->updateFrags();
		}
		catch ( Exception $e )
		{
			$locker->unlock( self::lockKey($uid, $treasureId) );
			throw new InterException( 'fuse fail');
		}
		$locker->unlock( self::lockKey($uid, $treasureId) );

		//添加宝物并更新
		if ( !$bag->addItemByTemplateID( $treasureId , $fuseNum ) )
		{
			throw new InterException( 'add treasure failed, treasureId: %d', $treasureId );
		}
		
		$bag->update();
		
		return true;
	}
	

	public static function lockKey( $uid, $treasureId )
	{
		return FragseizeDef::FRAG_LOKER_PRE.$uid.'_'.$treasureId;
	}

	/**
	 * 获取可抢夺玩家列表
	 * @param unknown $uid
	 * @param unknown $fragId
	 * @param unknown $num
	 * @throws FakeException
	 * @return multitype:
	 */
	public static function getRecRicher( $uid, $fragId, $num )
	{
		//世界和平的话就不要取玩家了
		$userInfoRaw = self::worldPeace()? array():self::getRealUsers($uid, $fragId, $num);
		
		//补npc，至少一个， 最多四个
		$npcInfo = array();
		if ( count( $userInfoRaw ) < $num )
		{
			$npcNum = $num - count( $userInfoRaw );
			$npcInfo = self::getNPCS($uid, $fragId, $npcNum );
		}
		else
		{
			$npcInfo = self::getNPCS($uid, $fragId, 1 );
			$userInfoRaw = array_merge( $userInfoRaw );
			$userInfoRaw = array_slice( $userInfoRaw , 0, $num -1);
		}
		$userInfoRaw = array_merge( $userInfoRaw, $npcInfo );
	
		//把传给前端的放到mem中，等调夺宝接口是用以校验
		$memUid = array();
		$memAid = array();
		foreach ( $userInfoRaw as $key => $val )
		{
			if ( $val[ 'npc' ] == 0 )
			{
				$memUid[] = $val[ 'uid' ];
			}
			else
			{
				$memAid[] = $val[ 'uid' ];
			}
		}
		$memIds = array(
				'uid' => $memUid,
				'aid' => $memAid,
		);
		McClient::set( 'seize_'.$uid, $memIds );
	
		return $userInfoRaw;
	}
	
	public static function getRealUsers( $uid, $fragId, $num )
	{
		//获取碎片的对应宝物
		$treasureId = TreasFragItem::getTreasureId( $fragId );
		if ( empty( $treasureId ) )
		{
			throw new FakeException( 'azhu, gao pi a!' );
		}
		$conf = btstore_get()->LOOT;
		
		$userInfoRaw = array();
		$user = EnUser::getUserObj( $uid );
		$userLv = $user->getLevel();
		
		$mcInfo = McClient::get( 'seizeAll_'.$uid );
		if (self::useMem() && !empty($mcInfo))
		{
		    //走缓存
		    $userInfoRaw = $mcInfo['info'];
		    Logger::info("use memcache success!");
		}
		else {
			$minLv = $userLv - $conf[ 'levelOffset' ] <= 0? 1: $userLv - $conf[ 'levelOffset' ];
			$maxLv = $userLv + $conf[ 'levelOffset' ] >= UserConf::MAX_LEVEL?UserConf::MAX_LEVEL:$userLv + $conf[ 'levelOffset' ];
			$minLv = $minLv > FragseizeConf::GOD_PROTECT_LEVEL? $minLv: FragseizeConf::GOD_PROTECT_LEVEL + 1;
			
		    if ( $minLv <= $maxLv )
		    {
		        //列表获取1. 从碎片表中取一批有该碎片的数据
		        $userInfoRaw = FragseizeDAO::getUidArrByFragId( $fragId );
		        //注意，此处userinfo变为以uid为键名的数组，从此之后一直是
		        $userInfoRaw = Util::arrayIndex( $userInfoRaw , 'uid');
		    }
		    //列表获取2. 根据上获取的uid从user表中取信息，过滤掉在免战当中的，然后将碎片数量追加上
		    if ( !empty( $userInfoRaw ) )
		    {
		        $arrUid = Util::arrayExtract( $userInfoRaw , 'uid' );
		        $arrUid = FragseizeDAO::getSeizerNotInWhiteFlag( $arrUid );
		        $arrField = array( 'uid', 'utid','level','uname','vip' );
		        $userInfoDetails = EnUser::getArrUser($arrUid, $arrField, true);
		        foreach ( $userInfoDetails as $oneUid => $userInfo )
		        {
		            if ( $oneUid == $uid || $userInfo[ 'level' ] < $minLv || $userInfo[ 'level' ] > $maxLv )
		            {
		                unset( $userInfoDetails[ $oneUid ] );
		            }
		            else
		            {
		                $userInfoDetails[ $oneUid ][ FragseizeDef::FRAG_NUM ] = $userInfoRaw[ $oneUid ][  FragseizeDef::FRAG_NUM ];
		            }
		        }
		        $userInfoRaw = $userInfoDetails;
		    }
		    
		    //列表获取3. 该碎片是玩家某个宝物的唯一一个碎片 则过滤掉该玩家，得到最终满足条件的玩家
		    if ( !empty( $userInfoRaw ) )
		    {
		        $uidArrNow = Util::arrayExtract( $userInfoRaw , 'uid');
		        $fIdArr    = TreasureItem::getFragments( $treasureId );
		        $fragsOfTreasureOfTheseUids = FragseizeDAO::getRecByUidFragarr( $uidArrNow , $fIdArr);
		        $tmp = array();
		        foreach ( $fragsOfTreasureOfTheseUids as $key => $onePiece )
		        {
		            $tmp[ $onePiece[ 'uid' ] ][ $onePiece[ FragseizeDef::FRAG_ID ] ] = $onePiece[ FragseizeDef::FRAG_NUM ];
		        }
		        foreach ( $tmp as $tmpUid => $tmpInfo )
		        {
		            $fragTotal = array_sum( $tmpInfo );
		            if ( $fragTotal <= 1 )
		            {
		                unset( $userInfoRaw[ $tmpUid ] );
		            }
		        }
		    }
		    if(self::useMem())
		    {
		    	//不为空才存入缓存，不然以后1分钟内的每一次都只有npc了
		    	if (!empty($userInfoRaw))
		    	{
		    		McClient::set( 'seizeAll_'.$uid, array('info'=>$userInfoRaw), 60);
		    		Logger::info("set memcache success!");
		    	}
		    }
		}
		
		//TODO 这前面获得的信息全部放入缓存里面，保存1分钟左右，并且打条日志，且这个从mem中取得加上一个判断，判断走函数的返回值
		//TODO 目前函数只返回true，以便日后添加上开服时间限制之类的东西
		
		//列表获取4.从合适的玩家中取玩家并补上相关信息，如阵容、抢夺概率、是否是npc
		if ( empty( $userInfoRaw ) )
		{
			Logger::debug('only NPC');
		}
		else if ( $num > 1 )
		{
			$userInfoRaw = array_merge( $userInfoRaw );
			$infoRawNum = count( $userInfoRaw );
			if ( $infoRawNum >= $num )
			{
				$randNum = rand( 0 , $infoRawNum - $num + 1);
				$userInfoRaw = array_slice( $userInfoRaw , $randNum, $num-1 );
			}
			$arrUid = Util::arrayExtract( $userInfoRaw , 'uid');
			$arrSquad = EnUser::getArrUserSquad( $arrUid );
			foreach ( $userInfoRaw as $key => $val )
			{
				$userInfoRaw[ $key ][ 'squad' ] = array_slice($arrSquad[ $val['uid'] ], 0, 3)  ;
				$userInfoRaw[ $key ][ 'percent' ] = self::getSeizeSuccRate( $userLv , $userInfoRaw[ $key ]['level'], $fragId);
				$userInfoRaw[ $key ][ 'npc' ] = 0;
			}
		}
		//列表获取5.应策划要求，把用户做个随机， 随机[0-3]个（在此之前用户已经是$num-1个之内了）
		if( !empty( $userInfoRaw ) )
		{
			foreach ( $userInfoRaw as $aUid => $userInfo )
			{
				$randnum = rand( 0 , 100);
				if ( $randnum > 50  )
				{
					unset( $userInfoRaw[ $aUid ] );
				}
			}
		}
		return $userInfoRaw;
	}
	
	/**
	 * 获取与玩家匹配的NPC列表
	 * @param int $uid
	 * @param int $fragId
	 * @param int $num
	 * @throws ConfigException
	 * @return multitype:multitype:number NULL Ambigous <number, mixed>
	 */
	public static function getNPCS( $uid, $fragId, $num )
	{
		$user = EnUser::getUserObj( $uid );
		$level = $user->getLevel();
		
		$randArmyArr = self::getAdaptArr($level);
		
		$armyAndWeight = array();
		foreach ( $randArmyArr as $key => $oneArmyId )
		{
			$armyAndWeight[] = array( 'armyId' => $oneArmyId, 'weight' => 1000 );
		}
		$retArr = Util::noBackSample($armyAndWeight, $num);

		$percent = TreasFragItem::getNpcRobRatio( $fragId );
		$ret = array();
		foreach ( $retArr as $key => $val )
		{
			$ret[] = array(
					'npc' => 1,
					'uid' => $armyAndWeight[ $val ][ 'armyId' ],
					'percent' => $percent,
			);
		}
		if ( count( $ret ) < $num )
		{
			throw new ConfigException( 'no enough npcs in conf' );
		}
		return $ret;
	}
	
	public static function quickSeize($uid, $beseizeUid,$fragId,$seizeTimes)
	{
		if( EnUser::getUserObj($uid)->getLevel() < 50 )
		{
			throw new FakeException( 'user level < 30' );
		}
		
		$ret = self::seize($uid, $beseizeUid, $fragId, true ,$seizeTimes);
		if( $ret == 'fail' || $ret == 'white' )
		{
			return 'fail';
		}
		
		foreach ( $ret['card'] as $oneIndex => $oneCardInfo )
		{
			unset( $ret['card'][$oneIndex]['show1'] );
			unset( $ret['card'][$oneIndex]['show2'] );
			$ret['card'][$oneIndex] = $ret['card'][$oneIndex]['real'];
		}
		
		if ( isset( $ret['fightStr'] ) )
		{
			unset($ret['fightStr']);
		}
		
		return $ret;
	}
	
	
	/**
	 * 夺宝
	 * @param unknown $uid
	 * @param unknown $beUid
	 * @param unknown $fragId
	 * @param unknown $isNPC
	 * @throws FakeException
	 * @throws InterException
	 * @return string|multitype:
	 */
	public static function seize( $uid, $beUid, $fragId, $isNPC, $seizeNum = 1 )//一键夺宝添加
	{
		//mem校验
		$mcSeize = McClient::get( 'seize_'.$uid );
		if ( empty( $mcSeize ) )
		{
			throw new FakeException( 'nothing in mc, cal getRecRicher first' );
		}
		if  ( ($isNPC && !in_array( $beUid, $mcSeize[ 'aid' ] )) ||
			( !$isNPC && !in_array( $beUid, $mcSeize[ 'uid' ] ) ) )
		{
			throw new FakeException( 'not god giv u, boy. req uid: %d, mc: %s', $beUid, $mcSeize );
		}
		$treasureId = TreasFragItem::getTreasureId( $fragId );
		
		$conf = btstore_get()->LOOT;
		$needStamina = intval( $conf[ 'stamina' ] )*$seizeNum;//一键夺宝添加
		$user = EnUser::getUserObj( $uid );
		$userStamina = $user->getStamina();
		if ( $userStamina < $needStamina )
		{
			throw new FakeException( 'lack stamina, need: %d', $needStamina );
		}
		
		//用户自查
		if ( !self::selfOk($uid, $fragId) )
		{
			return 'fail';
		}
		if ( $isNPC )
		{
			return self::seizeNPC($uid, $beUid, $fragId, $seizeNum );//一键夺宝添加
		}
		
		//抢夺用户、被抢用户战斗准备 
		
		$btlUser = $user->getBattleFormation();
		$userFF = $user->getFightForce();
		//被夺用户在免战中（包括全局免战和个人免战）
		if ( self::enemyInWhiteFlag( $beUid ) )
		{
			return 'white';
		}
		
		$beUser = EnUser::getUserObj( $beUid );
		//不能被打劫的等级，这个要策划保证（用户在走完夺宝新手引导之前不能被打劫），不然就蛋疼菊紧乳酸了。。。
		if ( $beUser->getLevel() <= FragseizeConf::GOD_PROTECT_LEVEL )
		{
			Logger::debug('beUserLevel low');
			return 'fail';
		}
		$btlSeizedUser = $beUser->getBattleFormation();
		$robUserFF = $beUser->getFightForce();
		
		//夺
		$locker = new Locker();
		$locker->lock( self::lockKey($beUid, $treasureId) );
		try 
		{
			if ( !self::enemyOk($beUid, $fragId) )
			{
			    //TODO 前面既然加了缓存，这里就要加个判断了，判断这个uid是否还有这个fragId是否只有一个这个fragId
			    //TODO 如果不是，说明缓存失效了，得清缓存或者咋地
			    
				$locker->unlock( self::lockKey( $beUid , $treasureId) );
				if (self::useMem())
				{
				    McClient::del('seizeAll_'.$uid);
				    Logger::info("clear memcache success!");
				}
				return 'fail';
			}
			$type = EnBattle::setFirstAtk(0, $userFF >= $robUserFF);
			$btlRet = EnBattle::doHero( $btlUser, $btlSeizedUser, $type );
			$btlSucc = BattleDef::$APPRAISAL[$btlRet[ 'server' ]['appraisal']] <= BattleDef::$APPRAISAL['D'];
			
			$canSeize = self::canSeize( $user->getLevel(), $beUser->getLevel(), $fragId );
			if ( self::canSeizeBySeizeNum($uid, $fragId) )
			{
				$canSeize = true;
			}
			
			if ( $btlSucc && $canSeize )
			{
				//被夺用户的数据修改及更新
				$fragInstBe = FragseizeObj::getInstance( $beUid );
				$fragInstBe->subFrags( array( $fragId => 1 ) );
				$fragInstBe->updateFrags();
				
				RPCContext::getInstance ()->sendMsg ( array (intval($beUid) ),
				PushInterfaceDef::FRAGSEIZE_SEIZE, array ('fragId' => $fragId, 'fragNum' => 1) );
					
			}
		}
		catch ( Exception $e )
		{
			$locker->unlock( self::lockKey( $beUid , $treasureId) );
			throw new InterException( 'sub failed, $fragId: %d', $fragId );
		}
		$locker->unlock( self::lockKey( $beUid , $treasureId) );

		//只要打了就解除免战，在此尝试重置免战时间，
		$fragInst = FragseizeObj::getInstance( $uid );
		if ( $fragInst->getWhiteEndTime() > Util::getTime() )
		{
			$fragInst->setWhiteTime( Util::getTime() );
		}
		//尝试重置第一次夺宝时间，初始为0
		$fragInst->setAtk();
		$fragInst->updateSeizer();
		
		//战斗发起方发奖、更新
		$rewardRet = self::reward( $uid,$beUid, $btlSucc, $canSeize, $fragId );
		$ext = array(
				'appraisal' => $btlRet[ 'server' ]['appraisal'],
				'fightStr' => $btlRet[ 'client' ],
				'fightFrc' => $beUser->getFightForce(),
		);
		$ret = array_merge( $rewardRet, $ext );
		//发邮件
		if ( $btlSucc )
		{
			$userInfo = $user->getTemplateUserInfo();
			$robsilver = 0;
			if ( isset( $ret[ 'card' ][ 'real' ][ 'rob' ] ) && $ret[ 'card' ][ 'real' ][ 'rob' ] > 0 )
			{
				$robsilver = $ret[ 'card' ][ 'real' ][ 'rob' ];
			}
			$seizefragId = 0;
			if ( $canSeize )
			{
				$seizefragId = $fragId;
			}
			if ( $robsilver != 0 || $seizefragId != 0 )
			{
				MailTemplate::sendFragseize( $beUid , $userInfo, $seizefragId, $btlRet[ 'server' ][ 'brid' ], $robsilver);
			}
		}

		return $ret;
	}
	/**
	 * 被夺方是否处于免战
	 * @param unknown $beUid
	 * @return boolean
	 */
	public static function enemyInWhiteFlag( $beUid )
	{
		if ( self::worldPeace() )
		{
			return true;
		}
		$fragInst = FragseizeObj::getInstance($beUid);
		return $fragInst->getWhiteEndTime() > Util::getTime() ;
	}
	
	/**
	 * 被抢夺放是否满足逻辑和策划要求
	 * @param unknown $beUid
	 * @param unknown $fragId
	 * @return boolean
	 */
	public static function enemyOk( $beUid, $fragId )
	{
		$treasureId = TreasFragItem::getTreasureId( $fragId );
		//（要夺取的碎片对应的）宝物的所有碎片Id， 称之为宝物A的期望id
		$fragExpect = TreasureItem::getFragments( $treasureId );
		
		$fragInstSeized = FragseizeObj::getInstance( $beUid );
		$fragExpectOwn = $fragInstSeized->getFragsByTid( $treasureId );
		
		if ( !isset( $fragExpectOwn[ $fragId ] ) || $fragExpectOwn[ $fragId ] <= 0 )
		{
			Logger::debug( 'needid: %d, isarr: %s',$fragId, $fragExpectOwn[ $fragId ]  );
			return false;
		}
		$fragNumTotal = array_sum( $fragExpectOwn );
		if ( $fragNumTotal <= 1 )
		{
			Logger::debug( 'beUid: %d, expectTotal: %d',$beUid, $fragNumTotal );
			return false;
		}
		return true;
	}
	/**
	 * 抢夺npc， 不需要加解锁，单独拿出来
	 * @param int $uid
	 * @param int $armyId
	 * @param int $fragId
	 * @return multitype:
	 */
	public static function seizeNPC( $uid, $armyId, $fragId,$seizeNum )//一键夺宝添加
	{
		$user = EnUser::getUserObj( $uid );
		self::checkAdaptNPC($uid, $armyId);
		$btlNPC = EnFormation::getMonsterBattleFormation( $armyId );
		$btlUser = $user->getBattleFormation();

		$btlRet = EnBattle::doHero( $btlUser, $btlNPC );
		$btlSucc = BattleDef::$APPRAISAL[$btlRet[ 'server' ]['appraisal']] <= BattleDef::$APPRAISAL['D'];

		$seizeRet = self::doSeizeNpc($uid, $fragId, $btlSucc, $seizeNum);
		$rewardRet = $seizeRet['rewardRet'];

		$ext = array(
				'appraisal' => $btlRet[ 'server' ]['appraisal'],
				'fightStr' => $btlRet[ 'client' ],
				'fightFrc' => $btlNPC[ 'fightForce' ],
		);
		$ret = array_merge( $rewardRet, $ext );
		return $ret;
	}

	public static function doSeizeNpc($uid, $fragId, $btlSucc, $seizeNum, $ifUpdate=true)
	{
		$seizePercent = TreasFragItem::getNpcRobRatio( $fragId );

		//一键夺宝添加==========
		$canSeize = false;
		$doNum = 1;
		if($btlSucc)
		{
			$quickDonum = 0;
			for ( $i = 0; $i< $seizeNum; $i++ )
			{
				$quickDonum ++;
				$randNum = rand( 0, FragseizeConf::RIO_BASE );
				$canSeize = $randNum < $seizePercent? true:false;

				if ( self::canSeizeBySeizeNum($uid, $fragId, $i ) && $btlSucc )
				{
					$canSeize = true;
				}

				if( $canSeize == true )
				{
					break;
				}
			}
			$doNum = $quickDonum;
			if ( $doNum < 1 )
			{
				throw new FakeException( 'doNum:%d impossible',$doNum );
			}
		}
		//一键夺宝添加==========
		$rewardRet = self::reward( $uid, 0, $btlSucc, $canSeize, $fragId, $doNum, $ifUpdate);//一键夺宝添加
		return array(
			'res' => $canSeize,
			'rewardRet' => $rewardRet,
		);
	}
	
	public static function canSeizeBySeizeNum( $uid, $fragId, $extraNum = 0 )
	{
		$fragInst = FragseizeObj::getInstance($uid);
		$seizeNum = $fragInst->getFragSeizeNum($fragId);
		$fragNeedSeizeNum = TreasFragItem::getSpecialNum( $fragId );
		if ( ($seizeNum + $extraNum) >= $fragNeedSeizeNum && $fragNeedSeizeNum > 0 )
		{
			return true;
		}
		return false;
	}
	
	/**
	 * 是否能够夺到碎片
	 * @param unknown $seizeLv
	 * @param unknown $seizedLv
	 * @param unknown $fragId
	 * @throws ConfigException
	 * @return boolean
	 */
	public static function canSeize( $seizeLv, $seizedLv, $fragId )
	{
		$rio = self::getSeizeSuccRate($seizeLv, $seizedLv, $fragId);
		
		if( $rio < 0 || $rio > FragseizeConf::RIO_BASE )
		{
			throw new ConfigException( 'rio: %d', $rio );
		}	

		$randNum = rand( 0 , FragseizeConf::RIO_BASE );
		if ( $randNum < $rio )
		{
			return true;
		}
		return false;
	}
	
	public static function getSeizeSuccRate( $seizeLv, $seizedLv, $fragId )
	{
		$conf = btstore_get()->LOOT;
		$baseRio = TreasFragItem::getBaseRobRatio( $fragId );
		$maxRio = $conf[ 'reizeMaxRio' ];
		$minRio = $conf[ 'reizeMinRio' ];
		$lvRio = $conf[ 'reizeLvRio' ];
		
		$tmpPram = $baseRio+( $seizedLv - $seizeLv )* $lvRio;
		$midParam = $tmpPram > 0? $tmpPram:0;
		
		$rioTmp =  $minRio < $midParam? $minRio:$midParam;
		$rio = $maxRio > $rioTmp? $maxRio:$rioTmp;
		
		return $rio;
	}
	

	public static function reward( $uid, $beUid, $btlSucc, $canSeize, $fragId, $doNum = 1, $ifUpdate=true)//一键夺宝添加
	{
		$conf = btstore_get()->LOOT;
		$needStamina = intval( $conf[ 'stamina' ] ) * $doNum;//一键夺宝添加
		$user = EnUser::getUserObj( $uid );
		if ( !$user->subStamina( $needStamina ) )
		{
			throw new InterException( 'lackStamina!' );
		}
		$level = $user->getLevel();
		
		if ( !$btlSucc )
		{
			$exp = $conf[ 'expBaseLose' ] * $level;
			$silver = $conf[ 'silverBaseLose' ] * $level < $conf['loseSilver']
				? $conf[ 'silverBaseLose' ] * $level : $conf['loseSilver'];
		}
		else //一键夺宝添加
		{
			$exp = $conf[ 'expBaseWin' ] * $level * $doNum;
			$silver = $conf[ 'silverBaseWin' ] * $level * $doNum < $conf['winSilver'] * $doNum
				? $conf[ 'silverBaseWin' ] * $level * $doNum : $conf['winSilver'] * $doNum;
		}
		
		$addExp = $user->addExp( $exp );
		$user->addSilver( $silver );
		//抽卡所获得的信息
		$cardInfo = array();
		if ( $btlSucc )
		{
			for ( $i= 0; $i < $doNum;$i++ )//一键夺宝添加
			{
				$flopInfo = EnFlop::flop( $uid, $beUid, $conf[ 'flopId' ], $ifUpdate );
				$cardInfo[] = $flopInfo['client'];
			}
			
/* 			if ( isset( $cardInfo[ 'real' ]['item'] ) )
			{
				$userInfo = array(
						'uid' => $uid,
						'uname' => $user->getUname(),
						'utid'	=> $user->getUtid(),
				);
				$itemArr = array( $cardInfo[ 'real' ]['item'][ 'id' ] => $cardInfo[ 'real' ]['item'][ 'num' ] );
				ChatTemplate::sendFlopItem( $userInfo , $itemArr, FlopDef::FLOP_TYPE_FRAGSEIZE);
			} */
		}
		
		$fragInst = FragseizeObj::getInstance( $uid );
		if ( ( $btlSucc && $canSeize && !empty( $fragId )) )
		{
			$fragInst->addFrags( array( $fragId => 1 ) );
			$fragInst->setSeizeNum($fragId, 0);
			$return[ 'reward' ][ 'fragNum' ] = 1;
		}
		else if ( $btlSucc && !$canSeize && !empty( $fragId ) )
		{
			$seizeNum = $fragInst->getFragSeizeNum( $fragId );
			$fragInst->setSeizeNum( $fragId, $seizeNum + $doNum );
		}

		if($ifUpdate)
		{
			$fragInst->updateFrags();
			BagManager::getInstance()->getBag( $uid )->update();
			$user->update();
		}

		$return[ 'reward' ][ 'exp' ] = $addExp;
		$return[ 'reward' ][ 'silver' ] = $silver;
		$return[ 'card' ] = $cardInfo;
		$return['donum'] = $doNum;

		return $return;
	}
	
	
	public static function checkAdaptNPC( $uid, $armyId )
	{
		$user = EnUser::getUserObj( $uid );
		$level = $user->getLevel();
		
		$randArmyArr = self::getAdaptArr($level);
		if ( !in_array( $armyId , $randArmyArr ) )
		{
			throw new FakeException( 'invalid army: %d, for level: %d', $armyId, $level );
		}
	}
	
	
	public static function getAdaptArr( $level )
	{
		$conf = btstore_get()->LOOT;
		$allArmy = $conf[ 'LvAndNpc' ];
		$randArmyArr = array();
		foreach ( $allArmy as $key => $lvArmy )
		{
			if ( $level <= $lvArmy[ 'level' ]  )
			{
				$randArmyArr = $lvArmy[ 'armys' ];
				break;
			}
			else
			{
				continue;
			}
		}
		if ( empty( $randArmyArr ) )
		{
			throw new ConfigException( 'no NPC for level: %d', $level );
		}
		
		return $randArmyArr->toArray();
	}
	
	public static function selfOk( $uid, $fragId )
	{
		$tid = TreasFragItem::getTreasureId( $fragId );
		$fragsNeed = TreasureItem::getFragments( $tid );
		
		$fragInst = FragseizeObj::getInstance( $uid );
		$treaFragsOwn = $fragInst->getFragsByTid( $tid );
		
		if ( isset( $treaFragsOwn[ $fragId ] ) && $treaFragsOwn[ $fragId ] > 0 )
		{
			if ( $fragInst->getAtk() != 0 )
			{
				return false;
			}
		}
		
		$conf = btstore_get()->LOOT;
		//优化toArray
		$insitTrea = $conf[ 'insistTrea' ];//->toArray();

		foreach($insitTrea as $eachInsitTrea)
		{
			if($tid == $eachInsitTrea)
			{
				return true;
			}
		}
		/**
		if ( in_array( $tid , $insitTrea) )
		{
			return true;
		}
		*/

		foreach ( $treaFragsOwn as $id => $num )
		{
			if ( in_array( $id , $fragsNeed ) && $num > 0 )
			{
				return true;
			}
		}
		
		Logger::debug('self not ok');
		return false;
	}
	
	public static function checkFragId( $fragId )
	{
		TreasFragItem::getTreasureId( $fragId );
	}
	
	public static function checkTreasureId( $treasureId )
	{
		TreasureItem::getFragments( $treasureId );
	}

	/**
	 * 一键夺宝检查 是否满足停止条件
	 * @param $ifUse int 是否自动吃药
	 * @param $ifEatMedicine int 本次是否吃药
	 * enough => 宝物足够
	 * bagFull => 背包满
	 * noStamina => 没有体力,并且没勾选自动吃药
	 * noMedicine => 没药了
	 * medicine => 吃药了
	 * ok => 一切ok
	 */
	public static function checkOneKeySeizeStop($uid, $treasureId, $ifUse=0, $ifEatMedicine=true)
	{
		//宝物碎片是否足够合成一个宝物
		$fragSeizeObj = FragseizeObj::getInstance($uid);
		if($fragSeizeObj->ifFragEnoughForTreasure($treasureId))
		{
			return 'enough';
		}

		//对应背包是否已满
		$bag = BagManager::getInstance()->getBag($uid);
		if( $bag->isFullByTemplate( array($treasureId) ) )
		{
			return 'bagFull';
		}

		$lootConf = btstore_get()->LOOT;
		$needStamina = intval($lootConf['stamina']);
		$userObj = EnUser::getUserObj($uid);
		if($userObj->getStamina() < $needStamina)
		{
			if($ifUse == 0)
			{
				return 'noStamina';
			}
			if($ifUse == 1)
			{
				//耐力丹
				if($bag->getItemNumByTemplateID(FragseizeDef::STAMINA_ITEM_TEMPLATE_ID) <= 0)
				{
					return 'noMedicine';
				}

				if($ifEatMedicine)
				{
					//使用背包中的耐力丹(一个耐力丹增加10点耐力,夺宝消耗2点耐力,所以每次吃一个)
					$arrStaminaItemId = $bag->getItemIdsByTemplateID(FragseizeDef::STAMINA_ITEM_TEMPLATE_ID);
					if(empty($arrStaminaItemId))
					{
						throw new InterException('arrStaminaItemId is empty');
					}
					//这里直接使用耐力丹增加体力,最后再更新背包
					$toUseStaminaItemId = $arrStaminaItemId[0];
					$toUseStaminaItem = ItemManager::getInstance()->getItem($toUseStaminaItemId);

					//物品是否足够
					if ( $bag->decreaseItem($toUseStaminaItemId, 1) == false )
					{
						throw new FakeException('use item:%d need num for %d', $toUseStaminaItemId, 1);
					}

					$useAcqInfo = $toUseStaminaItem->useAcqInfo();
					//使用得到耐力
					if ( !empty($useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_STAMINA]) )
					{
						$gain = $useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_STAMINA];
						if ( $userObj->addStamina($gain) == false )
						{
							throw new InterException('add stamina:%d failed', $gain);
						}
					}

					return 'medicine';
				}

			}
		}

		return 'ok';
	}

	public static function oneKeySeize($uid, $treasureId, $ifUse)
	{
		$ret = array();
		$fragSeizeObj = FragseizeObj::getInstance($uid);

		$checkStatus = self::checkOneKeySeizeStop($uid, $treasureId, $ifUse, false);
		$ret['res'] = $checkStatus;
		if($checkStatus != 'ok')
		{
			return $ret;
		}

		$userObj = EnUser::getUserObj($uid);
		$bag = BagManager::getInstance()->getBag($uid);
		//合成宝物需要的碎片
		$arrNeedFragId = TreasureItem::getFragments($treasureId);

		$totalSilver = 0;
		$totalExp = 0;
		$cardInfo = array();
		$ifStop = false;

		//遍历宝物对应的碎片
		$seizeNum = 0;
		foreach($arrNeedFragId as $needFragId)
		{
			if($ifStop)
			{
				break;
			}
			if($fragSeizeObj->ifHaveFrag($needFragId))
			{
				continue;
			}

			$aimFragId = $needFragId;
			//用户自查
			if (!self::selfOk($uid, $aimFragId))
			{
				$ret['res'] = 'fail';
				return $ret;
			}
            
			while(!$ifStop)
			{
				$ifEatMedicine = true;
				if($seizeNum >= FragseizeDef::MAX_SEIZE_NUM_ONCE)
				{
					$ifEatMedicine = false;
				}
				//满足停止条件了,就更新数据并返回 下次修改,改成break+标记,不直接return
				$checkStatus = self::checkOneKeySeizeStop($uid, $treasureId, $ifUse, $ifEatMedicine);
				$ret['res'] = $checkStatus;

				if(($checkStatus != 'ok' && $checkStatus != 'medicine') || $seizeNum >= FragseizeDef::MAX_SEIZE_NUM_ONCE)
				{
					$ifStop = true;
					break;
				}

				//里面有扣除体力 false表示不马上更新数据库
				$seizeRet = self::doSeizeNpc($uid, $aimFragId, true, 1, false);
				$seizeNum++;
				$onceResult = array();
				//吃药 前端要显示本次吃药了
				if($checkStatus == 'medicine')
				{
					$onceResult['medicine'] = 1;
				}
				if($seizeRet['res'] == true)
				{
					$onceResult['fragId'] = $aimFragId;
				}
				$totalExp += $seizeRet['rewardRet']['reward']['exp'];
				$totalSilver += $seizeRet['rewardRet']['reward']['silver'];

				$arrKey1 = array('rob', 'silver', 'gold', 'soul');
				$arrKey2 = array('item', 'hero', 'treasFrag');
				//合并抽卡结果
				foreach($seizeRet['rewardRet']['card'] as $oneIndex => $oneCardInfo)
				{
					foreach($arrKey1 as $key1)
					{
						if(isset($oneCardInfo['real'][$key1]))
						{
							if(isset($cardInfo[$key1]))
							{
								$cardInfo[$key1] += $oneCardInfo['real'][$key1];
							}
							else
							{
								$cardInfo[$key1] = $oneCardInfo['real'][$key1];
							}
						}
					}
					foreach($arrKey2 as $key2)
					{
						if(isset($oneCardInfo['real'][$key2]))
						{
							$id = $oneCardInfo['real'][$key2]['id'];
							$num = $oneCardInfo['real'][$key2]['num'];
							if(isset($cardInfo[$key2][$id]))
							{
								$cardInfo[$key2][$id] += $num;
							}
							else
							{
								$cardInfo[$key2][$id] = $num;
							}
						}
					}
				}

				$ret['detail'][] = $onceResult;
				$ret['card'] = $cardInfo;
				$ret['silver'] = $totalSilver;
				$ret['exp'] = $totalExp;

				//抢到了就停止
				if($seizeRet['res'])
				{
					break;
				}
			}
		}

		//如果顺利抢完了所有的,返回状态也是enough
		if($fragSeizeObj->ifFragEnoughForTreasure($treasureId))
		{
			$ret['res'] = 'enough';
		}

		if($ret['res'] == 'medicine')
		{
			$ret['res'] = 'ok';
		}

		$userObj->update();
		$bag->update();
		$fragSeizeObj->updateFrags();
		if($seizeNum > 0)
		{
			EnActive::addTask(ActiveDef::FRAGSEIZE, $seizeNum);
			EnDesact::doDesact($uid, DesactDef::FRAGSEIZE, $seizeNum);
			EnMission::doMission($uid, MissionType::FRAGSIZE, $seizeNum);
			EnWeal::addKaPoints( KaDef::FRAGSEIZE, $seizeNum );
			EnAchieve::updateSeize($uid, $seizeNum);
			
			// 夺宝次数统计 - 夺宝没有记录总次数，次数自己累加
			EnFestivalAct::notify($uid, FestivalActDef::TASK_FRAG_NUM, $seizeNum);
			//进行夺宝num次
			EnWelcomeback::updateTask(WelcomebackDef::TASK_TYPE_FRAGSEIZE, $seizeNum);
		}

		return $ret;
	}
	
	public static function useMem()
	{
	    return true;
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
