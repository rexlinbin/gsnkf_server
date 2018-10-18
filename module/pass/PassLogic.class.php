<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PassLogic.class.php 251704 2016-07-15 07:50:53Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pass/PassLogic.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-07-15 07:50:53 +0000 (Fri, 15 Jul 2016) $
 * @version $Revision: 251704 $
 * @brief 
 *  
 **/
class PassLogic
{
	public static function enter( $uid )
	{
		$passInfo = self::getPassInfo( $uid );
		$passInfo = self::securityUnset( $passInfo, PassDef::$unsetFieldForFront );
		$passInfo['va_pass'] = self::securityUnset( $passInfo['va_pass'], PassDef::$unsetFieldInVaForFront );
		
		
		//TODO 清缓存的问题和前段登录
		RPCContext::getInstance()->setSession( PassDef::SESSKEY , 0);
		return $passInfo;
	}
	
	public static function securityUnset( $massData, $keyArr )
	{
		if( !is_array( $massData ) )
		{
			throw new InterException( 'first para: %s', $massData );
		}
		
		foreach ( $keyArr as $oneKey )
		{
			if ( isset( $massData[$oneKey] ))
			{
				unset( $massData[$oneKey] );
			}
		}
		
		if( empty( $massData ) )
		{
			return array();
		}
		
		return $massData;
	}
	
	public static function getPassInfo( $uid )
	{
		$passObj = PassObj::getInstance( $uid );
		
		return $passObj->getPassInfo();
	}
	
	public static function getOpponentList( $uid, $id )
	{
		$passObj = PassObj::getInstance( $uid );
		
		if( $passObj->allHeroDead() )
		{
			throw new FakeException( 'all hero dead, see P' );
			//都死了，还看个p
		}

		$passObj = PassObj::getInstance( $uid );
		$baseId = $passObj->getBase();
		$arrOpponents = $passObj->getVaParticular( PassDef::VA_OPPINFO );
		$passBaseConf = btstore_get()->PASS_BASE[$baseId];
		
		$arenaObj = new EnArenaOpponent( ArenaOpponentType::PASS );
		if( empty( $arrOpponents ) )
		{
			$opponentInfoArr = self::getOpponentByRange( $baseId , array( $uid ));
			$saveOpponents = self::getInitOpponentInfo($opponentInfoArr);
			$passObj->setVaParticular(PassDef::VA_OPPINFO, $saveOpponents );
			$passObj->update();
		}
		else
		{
			$opponentInfoArr = self::getOpponentByUidArr( $uid );
		}
		
		$partInfo = $passObj->getVaParticular( PassDef::VA_OPPINFO );
		$return = array();
		foreach ( $opponentInfoArr as $degreeKey => $massValue )
		{ 
			$return[$degreeKey] = self::getNeedInfo( $massValue, PassDef::$oppoUserFieldForFront );
			foreach ( $massValue['arrHero'] as $pos => $massHeroInfo )
			{
				$return[$degreeKey]['arrHero'][$pos] = self::getNeedInfo($massHeroInfo, PassDef::$oppoHeroFieldForFront);
				Logger::debug('all field are: %d', count( $return[$degreeKey]['arrHero'][$pos] ));
				$return[$degreeKey]['arrHero'][$pos][PropertyKey::CURR_RAGE] = 
				$partInfo[$degreeKey]['arrHero'][$massHeroInfo['hid']][PassDef::RAGE];
			}
			$return[$degreeKey]['attackBefore'] = $partInfo[$degreeKey]['attackBefore'];
		}
		
		if( empty( $return ) )
		{
			Logger::fatal('no oppo info when want');
		}
		Logger::debug('my data from arena: %s',$return );
		return $return;
		
	}
	
	public static function getOpponentByRange( $id, $excludArr )
	{
		$arenaObj = new EnArenaOpponent( ArenaOpponentType::PASS );
		$passBaseConf = btstore_get()->PASS_BASE[$id];
		$rangeArr = $passBaseConf['opponentRangeArr'];
			
		$rangeArr = array( $rangeArr[3], $rangeArr[2], $rangeArr[1]  );
		$opponentInfoArr = $arenaObj->getFmtByArrRange( $rangeArr, $excludArr );
		Logger::debug('raw data from arena: %s',$opponentInfoArr );
		$opponentInfoArr = array_merge( $opponentInfoArr );
		$opponentInfoArr = array(
				PassCfg::DEGREE_HARD => $opponentInfoArr[0],
				PassCfg::DEGREE_NOMAL => $opponentInfoArr[1],
				PassCfg::DEGREE_SIMPLE => $opponentInfoArr[2],
		);
		
		return $opponentInfoArr;
	}
	
	public static function getInitOpponentInfo( $opponentInfoRaw )
	{
		foreach ( $opponentInfoRaw as $degree => $opponentInfo )
		{
			foreach ( $opponentInfo['arrHero'] as $pos => $heroInfo )
			{
				$saveOpponents[$degree]['arrHero'][$heroInfo[PropertyKey::HID]][PassDef::HP_PERCENT] =  PassCfg::FULL_PERCENT ;
				$saveOpponents[$degree]['arrHero'][$heroInfo[PropertyKey::HID]][PassDef::RAGE] = $heroInfo[PropertyKey::CURR_RAGE];
			}
			$saveOpponents[$degree]['uid'] = $opponentInfo['uid'];
			$saveOpponents[$degree]['attackBefore'] = PassDef::ATTACK_BEFORE_NOT;
		}
		if( empty( $saveOpponents ) )
		{
			throw new InterException( 'not get opponent info' );
		}
			
		return $saveOpponents;
	}
	
	public static function getOpponentByUidArr( $uid )
	{
		$passObj = PassObj::getInstance($uid);
		$arenaObj = new EnArenaOpponent( ArenaOpponentType::PASS );
		
		$opponentPartInfoArr = $passObj->getVaParticular( PassDef::VA_OPPINFO );
		foreach ( $opponentPartInfoArr as $degreeInVa => $opponentPartInfo )
		{
			$uidArr[$degreeInVa] = $opponentPartInfo['uid'];
		}
		if( empty( $uidArr ) )
		{
			throw new InterException( 'can not get opponent paet info from va' );
		}
		$opponentInfoArrArena = $arenaObj->getFmtByArrUid( array_merge( $uidArr ) );
		Logger::debug('raw data from arena: %s',$opponentInfoArrArena );
		foreach ( $uidArr as $aDegree => $aUid )
		{
			$opponentInfoArr[$aDegree] = $opponentInfoArrArena[$aUid];
		}
		
		return $opponentInfoArr;
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

	
	public static function attack( $uid, $id, $degree, $viceHidArr )
	{
		$passSess = RPCContext::getInstance()->getSession( PassDef::SESSKEY );
		if ( empty( $passSess ) )
		{
			EnUser::getUserObj( $uid )->modifyArtificailBattleData( self::getMemKey($uid) ); 
			RPCContext::getInstance()->setSession( PassDef::SESSKEY, 1 );
		}
		
		$bag = BagManager::getInstance()->getBag( $uid );
		if( $bag->isFull() )
		{
			throw new FakeException( 'bag is full' );
		}
		
		$passObj = PassObj::getInstance( $uid );
		//这里不用判定有没有打过，在obj里处理了，现在获取的就已经是有效轮次了，所以只需要做轮次匹配的判断，但是
		//一种情况除外，打到头了
		$passInfo = $passObj->getPassInfo();
		if( isset( $passInfo['va_pass'][PassDef::VA_HEROINFO] )
		 && !isset( $passInfo['va_pass'][PassDef::VA_UNION] ) )
		{
			throw new InterException( 'data invalid. need exe sql' );
		}
		
		if( $passObj->allBaseDone() )
		{
			throw new FakeException( 'already pass all' );
		}
		$base = $passObj->getBase();
		if( $id != $base )
		{
			throw new FakeException( 'invalid to attack base: %s, cur passInfo is: %s', $id, $passInfo );
		}
		
		if( $passObj->baseIsPass() )
		{
			throw new FakeException( 'already pass base: %s', $base );
		}
		
		//这个位置能不能打
		$oppoInfo = $passObj->getVaParticular( PassDef::VA_OPPINFO );

		self::setHeros( $uid, $viceHidArr );
		
		if( $passObj->allHeroDead( $uid ) )
		{
			throw new InterException( 'all hero dead, impossible' );
		}
		
		self::checkAttackNum($uid);
		$attackInfoRaw = self::getMyAttackInfoRaw( $uid );
		if( empty( $attackInfoRaw ) )
		{
			throw new InterException( 'empty attackInfoRaw' );
		}
		Logger::debug( 'attackInfoRaw: %s', $attackInfoRaw );
		
		$myAttackInfo = self::getRealAttackInfo( $uid, $attackInfoRaw );
		Logger::debug('aftergetrealAttack: %s',$myAttackInfo );
		$opponentAttackInfo = self::getOppoAttackInfo( $uid, $id, $degree );//这里uid是攻击者的uid
		
		//攻方先手
		$btlRet = EnBattle::doHero( $myAttackInfo, $opponentAttackInfo );
		$btlSucc = BattleDef::$APPRAISAL[$btlRet[ 'server' ]['appraisal']] <= BattleDef::$APPRAISAL['D'];
		
		$oppoInfo[$degree]['attackBefore'] = PassDef::ATTACK_BEFORE;
		$passObj->setVaParticular( PassDef::VA_OPPINFO , $oppoInfo );
		
		if( $btlSucc )
		{
			$attackerNewPartInfo = self::getNewPropertyInfo( $uid, $btlRet, true, $myAttackInfo );
			$passObj->setVaParticular( PassDef::VA_HEROINFO ,$attackerNewPartInfo );
			Logger::debug('attackerNewPartInfo: %s', $attackerNewPartInfo);
			
			$passObj->addPassNum(1);
			
			$chestShowInfo = self::getChestShowInfo( $base );
			$buffShowInfo = self::getBuffShowInfo( $uid, $base );
			$passObj->setVaParticular( PassDef::VA_CHESTSHOW, $chestShowInfo );
			$passObj->setVaParticular( PassDef::VA_BUFFSHOW, $buffShowInfo );
			
			$baseConf = btstore_get()->PASS_BASE[$base];
			$baseRewardConf = $baseConf['basePassRewardArr'][$degree];
			$passGradeArr = self::getPassRewardGrade( $base, $btlRet, $myAttackInfo );
			$gainPoint = intval( $passGradeArr['gradeArr'][0]*$baseRewardConf[0]/PassCfg::CONF_BASE );
			$gainStar = intval( $passGradeArr['gradeArr'][1]*$baseRewardConf[1]/PassCfg::CONF_BASE );
			$passObj->addPoint( $gainPoint );
			$passObj->addStar( $gainStar );
			
			$heroInfoAfterRecover = self::getHeroInfoAfterRecover( $uid, $attackInfoRaw );
			$passObj->setVaParticular( PassDef::VA_HEROINFO , $heroInfoAfterRecover );
			Logger::debug('heroInfoAfterRecover: %s', $heroInfoAfterRecover);
			
			$permernentReward = self::getRewardFromChestConf( $baseConf['permenentRewardId']);
			RewardUtil::reward3DArr($uid, $permernentReward, StatisticsDef::ST_FUNCKEY_PASS_PERMERNENT_REWARD, true );
			
			// 更新下一次扫荡的关卡数 , 不能再扫荡
			$SweepInfo = $passObj->getSweepInfo();
			$SweepInfo['isSweeped'] = true;
			$SweepInfo['count'] = $base;
			$passObj->setVaParticular(PassDef::VA_SWEEPINFO, $SweepInfo);
		}
		else 
		{
			// 不能再扫荡
			$SweepInfo = $passObj->getSweepInfo();
			$SweepInfo['isSweeped'] = true;
			$passObj->setVaParticular(PassDef::VA_SWEEPINFO, $SweepInfo);
			
			$passObj->addLoseNum(1);
		}
		
		$passObj->update();
		BagManager::getInstance()->getBag( $uid )->update();
		EnUser::getUserObj( $uid )->update();

		$passObj->refreshIfDone();
		$passInfo = self::getPassInfo( $uid );
		$passInfo = self::securityUnset($passInfo, PassDef::$unsetAfterAttack );
		
		$return =  array(
				'appraisal' => $btlRet[ 'server' ]['appraisal'],
				'fightStr' => $btlRet[ 'client' ],
		);
		
		$return = $return + $passInfo;
		
		if( $btlSucc )
		{
			$return['hpGrade'] = $passGradeArr['hpGrade'];
		}
		
		return $return;
	}
	
	public static function checkAttackNum( $uid )
	{
		$confNum = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_PASS_FREE_NUM];
		$passObj = PassObj::getInstance($uid);
		$buyNum = $passObj->getBuyNum();
		$loseNum = $passObj->getLoseNum();
		if( $loseNum >= $confNum + $buyNum )
		{
			throw new FakeException( 'no num' );
		}
	}
	
	public static function getHeroInfoAfterRecover( $uid, $atkInfoRaw )
	{
		$passObj = PassObj::getInstance( $uid );
		$base = $passObj->getBase();
		$baseConf = btstore_get()->PASS_BASE[$base];
		$heroInfo = $passObj->getVaParticular( PassDef::VA_HEROINFO );
		
		if( $base['switch'] == PassDef::HERITAGE )
		{
			$recoverInfo = $baseConf['recoverHpRageArr'];
			$heroInfo = self::addHpRage( $heroInfo, $recoverInfo[0], $recoverInfo[1] );
		}
		elseif( $base['switch'] == PassDef::REVIVE )
		{
			$heroInfo = self::reviveHero( $uid, $heroInfo, $atkInfoRaw );
		}
		else
		{
			throw new ConfigException( 'invalid switch in pass', $base['switch'] );
		}
		
		return $heroInfo;
	}
	
	public static function addHpRage( $heroPartInfo, $hpPercent, $rageNum )
	{
		$hpPercent = PassCfg::FULL_PERCENT / PassCfg::CONF_BASE * $hpPercent;
		foreach ( $heroPartInfo as $hid => $val )
		{
			if( $val[PassDef::HP_PERCENT] > 0 )
			{
				$heroPartInfo[$hid][PassDef::HP_PERCENT] += $hpPercent;
				$heroPartInfo[$hid][PassDef::RAGE] += $rageNum;
				if( $heroPartInfo[$hid][PassDef::HP_PERCENT] > PassCfg::FULL_PERCENT )
				{
					$heroPartInfo[$hid][PassDef::HP_PERCENT] = PassCfg::FULL_PERCENT;
				}
			}
		}
		
		return $heroPartInfo;
	}
	
	public static function addHpRageByHidArr( $uid, $hidArr, $hpPercent, $rageNum )
	{
		$hpPercent = PassCfg::FULL_PERCENT / PassCfg::CONF_BASE * $hpPercent;
		$passObj = PassObj::getInstance( $uid );
		$heroInfoArr = $passObj->getVaParticular( PassDef::VA_HEROINFO );
		foreach ( $hidArr as $hid )
		{
			if( !isset( $heroInfoArr[$hid] ) )
			{
				throw new FakeException( 'hid: %s not uid: %s s hero: %s',$hid, $uid, $heroInfoArr );
			}
			if( $heroInfoArr[$hid][PassDef::HP_PERCENT] <= 0 )
			{
				throw new FakeException( 'want to recover a dead, hidArr: %s, heroArr: %s', $hidArr, $heroInfoArr );
			}
			$heroInfoArr[$hid][PassDef::HP_PERCENT] += $hpPercent;
			$heroInfoArr[$hid][PassDef::RAGE] += $rageNum;
			if( $heroInfoArr[$hid][PassDef::HP_PERCENT] > PassCfg::FULL_PERCENT )
			{
				$heroInfoArr[$hid][PassDef::HP_PERCENT] = PassCfg::FULL_PERCENT;
			}
			
		}
		
		return $heroInfoArr;
	}
	
	public static function reviveHero( $uid, $heroPartInfo, $atkInfoRaw )
	{
		$heroArr = Util::arrayIndex( $atkInfoRaw['arrHero'], 'hid' );
		foreach ($heroArr as $hid => $info )
		{
			$heroPartInfo[$hid][PassDef::HP_PERCENT] = PassCfg::FULL_PERCENT;
			$heroPartInfo[$hid][PassDef::RAGE] = $info[PropertyKey::CURR_RAGE];
		}
/* 		foreach ( $heroPartInfo as $hid => $oneHeroInfo )
		{
			$heroPartInfo[$hid][PassDef::HP_PERCENT] = PassCfg::FULL_PERCENT;
			$heroPartInfo[$hid][PassDef::RAGE] = $heroArr[$hid][PropertyKey::CURR_RAGE];
		}
		 */
		return $heroPartInfo;
	}
	
	public static function reviveHeroByHidArr( $uid, $hidArr, $recoverHp )
	{
		$passObj = PassObj::getInstance( $uid );
		$atkInfo = self::getMyAttackInfoRaw( $uid );
		$heroArr = Util::arrayIndex( $atkInfo['arrHero'], 'hid' );
		$heroInfoArr = $passObj-> getVaParticular( PassDef::VA_HEROINFO );
		if( $recoverHp <= 0 )
		{
			throw new ConfigException( 'revive but hp is 0, failed' );
		}
		
		foreach ( $hidArr as $hid )
		{
			if( !isset( $heroInfoArr[$hid] ) )
			{
				throw new FakeException( 'want to revive hid: %s, not in my list: %s', $hid, $$heroInfoArr );
			}
			
			if( $heroInfoArr[$hid][PassDef::HP_PERCENT] > 0 )
			{
				throw new FakeException( 'hid: %s not dead, my hero list: %s', $hid, $heroInfoArr );
			}
			$heroInfoArr[$hid][PassDef::HP_PERCENT] = PassCfg::FULL_PERCENT/PassCfg::CONF_BASE * $recoverHp;
			if( !isset( $heroArr[$hid] ) )
			{
				$heroInfoArr[$hid][PassDef::RAGE] = HeroLogic::getHeroRage($uid, $hid);
			}
			else
			{
				$heroInfoArr[$hid][PassDef::RAGE] = $heroArr[$hid][PropertyKey::CURR_RAGE];
			}
			
		}
		
		return $heroInfoArr;
		
	}
	
	
	public static function getChestShowInfo( $baseId )
	{
		$baseConf = btstore_get()->PASS_BASE;
		$oneBaseConf = $baseConf[$baseId];
		$chestShowInfo = array(
				'freeChest' => PassDef::CHEST_STATUS_DEAL,
				'goldChest' => PassDef::CHEST_STATUS_DEAL
		);
		
		$freeChestConf = $oneBaseConf['freeChestArr']->toArray();
		$goldChestConf = $oneBaseConf['goldChestArr']->toArray();
		if( !empty( $freeChestConf ) )
		{
			$chestShowInfo['freeChest'] = PassDef::CHEST_STATUS_UNDEAL;
		}
		if( !empty( $goldChestConf ) )
		{
			$chestShowInfo['goldChest'] = PassDef::CHEST_STATUS_UNDEAL;
		}
		
		Logger::debug( 'chest show info: %s, baseInfo: %s', $chestShowInfo, $oneBaseConf );
		
		return $chestShowInfo;
	}
	
	public static function getBuffShowInfo( $uid, $baseId )
	{
		$passObj = PassObj::getInstance($uid);
		$deadHeroNum = $passObj->getDeadHeroNum();

		$baseConf = btstore_get()->PASS_BASE[$baseId];
		$buffShowInfo = array_fill(0, 3, array('status' => PassDef::BUFF_STATUS_DEAL, 'buff' => array() ) );
		$randomBuffArr = $baseConf['buffArr']->toArray();
		
		$passBuff = btstore_get()->PASS_BUFF;
		
		foreach ( $randomBuffArr as $pos => $oneBuffArr )
		{
			$samples = array();
			foreach ( $oneBuffArr as $id )
			{
				if( isset( $passBuff[ $id ] ) )
				{
					//这里有依赖
					if( $deadHeroNum <= 0 && $passBuff[$id]['buffArr'][0][0]==PassDef::TYPE_REVIVE )
					{
						continue;
					}
					$samples[$id] = $passBuff[ $id ];
					
				}
				else 
				{
					throw new ConfigException( 'want to roll: %s , bug no config', $id );
				}
			}
			
			if( empty( $samples ) )
			{
				continue;
			}
			
			$retOne = Util::backSample( $samples, 1 );
			$buffShowInfo[$pos]['status'] = PassDef::BUFF_STATUS_UNDEAL;
			$buffShowInfo[$pos]['buff'] = $retOne[0];
		}
		
		return $buffShowInfo;
		
	}
	
	
	public static function getRewardFromChestConf( $idArr )
	{
		$rewardArr = array();
		if( empty( $idArr ) )
		{
			return $rewardArr;
		}
		
		$chestConf = btstore_get()->PASS_CHEST;
		foreach ( $idArr as $index => $id )
		{
			if( !isset( $chestConf[$id] ) )
			{
				throw new ConfigException( 'no chest: %s in chest conf', $id );
			}
			$chestRewardArr = $chestConf[$id]['rewardArr'] ->toArray();//这样好么？还是一下好
			$rewardArr = array_merge( $rewardArr, $chestRewardArr );
		}
		
		return $rewardArr;
	}
	
	public static  function getNewPropertyInfo( $uid, $atkRet, $attackerOrDefender, $atkInfo )
	{
		$return = PassObj::getInstance($uid)->getVaParticular( PassDef::VA_HEROINFO );
		$targetServer = $atkRet['server']['team1'];
		$joiner = $atkInfo['arrHero'];
       	$joinerBak = $joiner;
		Logger::debug( 'targetServer: %s, joiner: %s', $targetServer, $joiner );
		foreach( $targetServer as $index => $heroInfo )
		{
			Logger::debug('heroInfoinloop: %s ', $heroInfo);
			//不会出现一个英雄活过来的情况吧。。。。
			$posForThisHero = null;
			foreach ( $joiner as $pos => $posInfo )
			{
				if( $heroInfo[PropertyKey::HID] == $posInfo[PropertyKey::HID] )
				{
					$posForThisHero = $pos;
					break;
				}
			}
			if( !isset( $posForThisHero ) )
			{
				throw new InterException( 'atkRet has a hid: %s not in atkinfo', $heroInfo[PropertyKey::HID] );
			}
			
			$return[$heroInfo[PropertyKey::HID]][PassDef::HP_PERCENT]
			= intval( ( $heroInfo['hp']/$joiner[$posForThisHero][PropertyKey::MAX_HP] ) * PassCfg::FULL_PERCENT );
			$return[$heroInfo[PropertyKey::HID]][PassDef::RAGE] = $heroInfo['rage'];
			unset($joinerBak[$posForThisHero]);
		}
		if( !empty( $joinerBak ) )
		{
			//这是说...死了...
			foreach( $joinerBak as $atkPos => $atkInfo )
			{
				$return[$atkInfo[PropertyKey::HID]][PassDef::HP_PERCENT] = 0;
				$return[$atkInfo[PropertyKey::HID]][PassDef::RAGE] = 0;
			}
		}

		Logger::debug('returninfo: %s', $return);
		return $return;
	}
	
	public static function getPassRewardGrade( $baseId, $btlRet, $atkInfo )
	{
		$heroHpArr = Util::arrayIndex( $btlRet['server']['team1'] , 'hid');
		
		$totalCostPercent = 0;
		foreach ( $atkInfo['arrHero'] as $pos => $oneHeroInfo )
		{
			if( $oneHeroInfo[PropertyKey::CURR_HP] > 0 )
			{
				$totalCostPercent += 
				intval( $heroHpArr[$oneHeroInfo[PropertyKey::HID]]['costHp']/$oneHeroInfo[PropertyKey::MAX_HP] 
						*PassCfg::FULL_PERCENT
						);
			}
		}
		$baseConf = btstore_get()->PASS_BASE[$baseId];
		$gradeArr = $baseConf['gradeArr'];
		
		$gradeArrNeed = array();
		$grade = 0;
		foreach ( $gradeArr as $hpPercent =>  $oneGradeArr )
		{
			$hpPercent =  intval( PassCfg::FULL_PERCENT/PassCfg::CONF_BASE * $hpPercent );
			if( $totalCostPercent <= $hpPercent )
			{
				$grade = $hpPercent;
				$gradeArrNeed = $oneGradeArr;
			}
		}
		
		if( empty( $gradeArrNeed ) )
		{
			throw new ConfigException( 'success but no grade, totoalHpCost: %s ', $totalCostPercent );
		}
		
		return array( 'hpGrade' => intval($grade/PassCfg::FULL_PERCENT*PassCfg::CONF_BASE), 'gradeArr' => $gradeArrNeed  );
		
		
	}
	
	public static function getOppoAttackInfo( $attackerUid, $baseId, $degree )
	{
		$passObj = PassObj::getInstance( $attackerUid );
		$oppoInfoPart = $passObj->getVaParticular( PassDef::VA_OPPINFO );
		if( !isset( $oppoInfoPart[$degree]['uid'] ) )
		{
			throw new InterException( 'attack empty, info: %s', $oppoInfoPart );
		}
		
		$baseConf = btstore_get()->PASS_BASE[$baseId];
		$adaptAttr = $baseConf['adaptArr'][$degree];
		$adaptAttr = HeroUtil::adaptAttr( $adaptAttr );
	
		$arenaObj = new EnArenaOpponent( ArenaOpponentType::PASS );
		$uidWant =  $oppoInfoPart[$degree]['uid'];
		$opponentInfoArr =$arenaObj->getFmtByArrUid( array( $uidWant ) );
		
		if( !isset( $opponentInfoArr[$uidWant] ) )
		{
			throw new InterException( 'bao guo, bu dui a, want : %s, return: %s ', $oppoInfoPart[$degree]['uid'], $opponentInfoArr[0]['uid'] );
		}
		$oppoInfoInfo = $opponentInfoArr[$uidWant];
		
		foreach ( $oppoInfoInfo['arrHero'] as $pos => $val )
		{

			foreach ( $adaptAttr as $onePropertyKey => $onePropertyVal )
			{
				if( !isset( $oppoInfoInfo['arrHero'][$pos][$onePropertyKey] ) )
				{
					$oppoInfoInfo['arrHero'][$pos][$onePropertyKey] = 0;
				}
				$oppoInfoInfo['arrHero'][$pos][$onePropertyKey] += $onePropertyVal;
			}
			
			$oppoInfoInfo['arrHero'][$pos][PropertyKey::CURR_HP] =
			intval( $oppoInfoPart[$degree]['arrHero'][$val[PropertyKey::HID]][PassDef::HP_PERCENT]/PassCfg::FULL_PERCENT * $val[PropertyKey::MAX_HP] );
			$oppoInfoInfo['arrHero'][$pos][PropertyKey::CURR_RAGE] = $oppoInfoPart[$degree]['arrHero'][$val[PropertyKey::HID]][PassDef::RAGE];
		}
		
		return $oppoInfoInfo;
		
	}
	
	public static function getRealAttackInfo( $uid, $attackInfoRaw )
	{
		$passObj = PassObj::getInstance( $uid );
		$heroInfoPart = $passObj->getVaParticular( PassDef::VA_HEROINFO );
		$buffInfoPart = $passObj->getVaParticular( PassDef::VA_BUFFINFO );
		
		//购买的buff的加成,羁绊和阵法已经用外面的了
		$properKeyToVal = HeroUtil::adaptAttr( $buffInfoPart );
		
		foreach ( $attackInfoRaw['arrHero'] as $pos => $val  )
		{
			foreach ( $properKeyToVal as $propertyKey => $propertyVal )
			{
				if( !isset( $attackInfoRaw['arrHero'][$pos][$propertyKey] ) )
				{
					$attackInfoRaw['arrHero'][$pos][$propertyKey] = 0;
					Logger::fatal('uid: %s, property: %s not exist in battle raw info', $uid, $propertyKey);
				}
				$attackInfoRaw['arrHero'][$pos][$propertyKey] += $propertyVal;
				$attackInfoRaw['arrHero'][$pos][PropertyKey::MAX_HP] = self::getMaxHp( $attackInfoRaw['arrHero'][$pos] );
			}
			
			$realHp =  $heroInfoPart[$val['hid']][PassDef::HP_PERCENT]/PassCfg::FULL_PERCENT * $val[PropertyKey::MAX_HP] ;
			$intHp = intval($realHp);
			if( $realHp > 0 && $intHp <= 0 )
			{
				$intHp = 1;
			}
			$attackInfoRaw['arrHero'][$pos][PropertyKey::CURR_HP] = $intHp;
			$attackInfoRaw['arrHero'][$pos][PropertyKey::CURR_RAGE] = $heroInfoPart[$val['hid']][PassDef::RAGE];

		}
	
		return $attackInfoRaw;
	}
	
	public static function getMaxHp( $heroInfo )
	{	
		$hpBase = $heroInfo[PropertyKey::HP_BASE];
		$hpRatio = $heroInfo[PropertyKey::HP_RATIO];
		$hpFinal = $heroInfo[PropertyKey::HP_FINAL];
		$hp = intval(( $hpBase * (1 + $hpRatio / UNIT_BASE)  + $hpFinal )*
				(1+ ($heroInfo[PropertyKey::REIGN]-5000)/UNIT_BASE));
		return $hp;
	}
	
	
	
	public static function setHeros( $uid, $viceHidArr )
	{
		$passObj = PassObj::getInstance( $uid );
		if( $passObj->heroIsSet() )
		{
			return;
		}
		
		if( count( $viceHidArr )>PassCfg::VICE_NUM)
		{
			throw new FakeException( 'too much vice hero: %s', $viceHidArr );
		}
		
		$battleFormationNotReal = EnUser::getUserObj( $uid )->getBattleFormation();
		$enquipOut = HeroLogic::getBasicEquipInfoOfFmtHero($uid);
		Logger::debug('enquipOut ionfo: $s', $enquipOut);
		
		if( count( $battleFormationNotReal['arrHero'] ) <= 0 )
		{
			Logger::fatal('no hero in formation');
		}
		$heroSave = array();
		$formationSave = array();
		foreach ( $battleFormationNotReal['arrHero'] as $pos => $heroInfo  )
		{
			$formationSave[$pos] = $heroInfo['hid'];
			$heroSave[$heroInfo['hid']] = self::getNeedInfo( $heroInfo , PassDef::$myHeroInfoInVa );
			$heroSave[$heroInfo['hid']][PassDef::EQUIP] = $enquipOut[$heroInfo['hid']];
			$heroSave[$heroInfo['hid']][PassDef::HP_PERCENT] = PassCfg::FULL_PERCENT;
		}
		
		$heroMgr = EnUser::getUserObj($uid)->getHeroManager();
		foreach ( $viceHidArr as $oneHid )
		{
			if( isset( $heroSave[$oneHid] ) )
			{
				throw new InterException( 'same hero in main and vice: %s', $oneHid );
			}
			
			$htid = $heroMgr->getHeroObj($oneHid)->getHtid();
			$heroSave[$oneHid][PassDef::RAGE] = HeroLogic::getHeroRage($uid, $oneHid);
			$heroSave[$oneHid][PassDef::HP_PERCENT] = PassCfg::FULL_PERCENT;
		}
		$unionSave = UserLogic::getUnionProfit($uid);//self::calculateUnion($uid);
		foreach ( $unionSave as $hid => $unionInfo )
		{
			if( !in_array( $hid, $formationSave ) )
			{
				throw new FakeException( 'invalid: %s', $unionSave );
			}
			$unionSave[$hid] = HeroUtil::adaptAttrReverse($unionInfo);
		}
		$secondFriendsAttrSave = HeroUtil::adaptAttrReverse( EnFormation::getAttrExtraProfit($uid) );
				
		$passObj->setVaParticular( PassDef::VA_UNION, $unionSave);
		$passObj->setVaParticular( PassDef::VA_HEROINFO, $heroSave );
		$passObj->setVaParticular( PassDef::VA_FORMATION, $formationSave );
		$passObj->setVaParticular( PassDef::VA_BENCH , array_merge( $viceHidArr ) );
		$passObj->setVaParticular( PassDef::VA_SECONDATTR, $secondFriendsAttrSave);
	}

	public static function calculateUnion( $uid, $union )
	{
		$unionForPass = array();
		if( empty( $union ) )
		{
			$unionProfit = UserLogic::getUnionProfit($uid);
			foreach ( $unionProfit as $oneHid => $unionOut )
			{
				$unionProfit[$oneHid] = HeroUtil::adaptAttrReverse( $unionOut );
			}
		}
		else 
		{
			$unionProfit = $union;
		}
		foreach ( $unionProfit as $hid => $info  )
		{
			foreach ( $info as  $properKey => $properVal )
			{
				if( !isset( $unionForPass[$properKey] ) )
				{
					$unionForPass[$properKey] = 0;
				}
				$unionForPass[$properKey] += $properVal;
			}
		}
		
		
		foreach ( $unionForPass as $key => $val )
		{
			$unionForPass[$key] = intval( $val/6 );
		}
		
	    return $unionForPass;
	  
	}
	
	public static function getMyAttackInfoRaw( $uid )
	{
		$passObj = PassObj::getInstance( $uid );
		$formation  = $passObj->getVaParticular( PassDef::VA_FORMATION );
		if( empty( $formation ) )
		{
			throw new InterException( 'empty hero in va' );
		}
			
		$user = EnUser::getUserObj( $uid );
		$formation = $passObj->getVaParticular( PassDef::VA_FORMATION );
		$equipHidToEquipArr = $passObj->getEquip( $formation );
		$unionInfo = $passObj->getVaParticular( PassDef::VA_UNION );

		$formation = self::filterFormation($formation);
		$unionInfoPrepared = self::prepareUnion($uid, $unionInfo, $formation);
		
		Logger::debug('args: %s, %s, %s, %s',$formation,$equipHidToEquipArr, $unionInfoPrepared, $uid );
		$secondGroupFriendAttr = self::prepareSecondAttr($uid, $formation);
		$battleInfoRaw = $user->getArtificailBattleInfo( 
				$formation,
				$equipHidToEquipArr, 
				array( 
						HeroDef::ADD_ATTR_BY_UNIONPROFIT => $unionInfoPrepared,
						HeroDef::ADD_ATTR_BY_ATTR_EXTRA => $secondGroupFriendAttr,
						),
				self::getMemKey( $uid ) 
		);
		
		$arrCarInfo = EnChariot::getChariotSkill($uid);
		if (!empty($arrCarInfo))
		{
			$battleInfoRaw['arrCar'] = $arrCarInfo;
		}
		
		foreach ( $battleInfoRaw['arrHero'] as $pos => $val )
		{
			if( !isset( $formation[$pos] ) || $val['hid'] != $formation[$pos] )
			{
				throw new InterException( 'return info err: %s, formation: %s', $battleInfoRaw, $formation );
			}
		}

		Logger::debug('battleInfoRaw: %s', $battleInfoRaw);
		return $battleInfoRaw;
			
	}
	
	public static function prepareUnion($uid,  $unionRaw, $formation )
	{
		$preparedUnion = array();
		$unionForBench = self::calculateUnion($uid, $unionRaw);
		foreach ( $formation as $pos => $val )
		{
			if( $val > 0 )
			{
				if( isset( $unionRaw[$val] ) )
				{
					$preparedUnion[$val] = $unionRaw[$val];
				}
				else
				{
					$preparedUnion[$val] = $unionForBench;
				}
				$preparedUnion[$val] = HeroUtil::adaptAttr( $preparedUnion[$val] );
			}
		}
		
		return $preparedUnion;
	}
	
	public static function prepareSecondAttr($uid, $formation)
	{
		$passObj = PassObj::getInstance($uid);
		$attr = $passObj->getVaParticular( PassDef::VA_SECONDATTR );
		foreach ( $formation as $pos => $val )
		{
			if( $val > 0 )
			{
				$secondAttr[$val] = HeroUtil::adaptAttr($attr);
			}
		}
		
		return $secondAttr;
	}
	
	public static function filterFormation( $formation )
	{
		foreach ( $formation as $pos => $val )
		{
			if( $val <= 0 )
			{
				unset( $formation[$pos] );
			}
		}
		
		return $formation;
	}
	
	public static function getMemKey( $uid )
	{
		return $key = PassDef::PASS_MEM_KEY.'_'.$uid;
	}
	
	
	public static function dealChest( $uid, $baseId, $isLuxury, $num )
	{
		Logger::trace('now deal chest, isLuxury: %s', $isLuxury);
		$passObj = PassObj::getInstance( $uid );
		$baseId = $passObj->getBase();
		$buyNum = 0;
		$nextNum = 1;
		if( !$isLuxury && $num != 1 )
		{
			throw new FakeException( 'need 1 for general chest' );
		}
		if( !$passObj->baseIsPass() )
		{
			throw new FakeException( 'base: %s not pass', $baseId );
		}
		
		if( $passObj->chestIsDone() )
		{
			throw new FakeException( 'chest done' );
		}
		
		$baseConf = btstore_get()->PASS_BASE[$baseId];
		$chestConf = btstore_get()->PASS_CHEST;
		$chestkey = $isLuxury?  'goldChest' : 'freeChest'  ;
		$chestArr = $isLuxury? $baseConf['goldChestArr'] : $baseConf['freeChestArr'];
		$weight = $isLuxury? 'goldWeight' : 'freeWeight';
		
		$chestShow = $passObj->getVaParticular( PassDef::VA_CHESTSHOW );
		if( !isset( $chestShow[$chestkey] ) || $chestShow[$chestkey] == PassDef::CHEST_STATUS_DEAL )
		{
			throw new FakeException( 'no chest, or already received for base: %s, uid: %s', $baseId, $uid );
		}

		$user = EnUser::getUserObj( $uid );
		if( !$isLuxury )
		{
			$chestShow['freeChest'] = PassDef::CHEST_STATUS_DEAL;
			$passObj->setVaParticular( PassDef::VA_CHESTSHOW, $chestShow );
		}
		else
		{
			$buyNum = $passObj->getLuxuryNum();
			$nextNum = $buyNum + $num;
			$goldNeedArr = $baseConf['chestNeedGoldArr'];
			$needGold = 0;
			for ( $i = $buyNum+1; $i <= $nextNum; $i++  )
			{
				$find = false;
				foreach ( $goldNeedArr as $oneNum => $gold )
				{
					if( $oneNum >= $i)
					{
						$needGold += $goldNeedArr[$oneNum];
						$find = true;
						break;
					}
				}
				if( !$find )
				{
					throw new FakeException( 'no conf for num: %s', $nextNum );
				}
			}
			
			if( !$user->subGold( $needGold , StatisticsDef::ST_FUNCKEY_PASS_GOLD_CHEST_COST ) )
			{
				throw new FakeException( 'lack gold' );
			}
			$passObj->addLuxuryNum( $num );
		}
		
		$rewardArr = array();
		$rewardIdArr = array();
		for ( $i = $buyNum+1; $i <= $nextNum; $i++  )
		{
			foreach ( $chestArr as $chestArrIndex => $chestArrPortion  )
			{
				$chestSamples = array();
				foreach ( $chestArrPortion[1] as $chestId )
				{
					if( !isset( $chestConf[$chestId] ) )
					{
						throw new ConfigException( 'want to roll chestid: %s, no conf', $chestId );
					}
					$chestSamples[$chestId] = $chestConf[$chestId];
				}
				Logger::debug('chestSamples are: %s', $chestSamples );
				$rollRet = Util::noBackSample( $chestSamples , $chestArrPortion[0], $weight );
				foreach ( $rollRet as $rollIndex => $rollId )
				{
					$rewardIdArr[] = $rollId;
					$partReward = $chestConf[$rollId]['rewardArr']->toArray();
					$rewardArr = array_merge( $rewardArr, $partReward );
				}
			}
		}
		
		if( empty( $rewardArr ) )
		{
			throw new ConfigException( 'empty reward for base: %s', $baseId );
		}
		Logger::debug('rewardArr in chest are: %s', $rewardArr);
		$passObj->update();
		RewardUtil::reward3DArr( $uid, $rewardArr, StatisticsDef::ST_FUNCKEY_PASS_GOLD_CHEST_REWARD, true );
		BagManager::getInstance()->getBag( $uid )->update();
		$user->update();
		
		Logger::debug('deal extra chest, baseId: %s, isLuxury : %s, rewardArr: %s ', $baseId, $isLuxury, $rewardArr);
		
		return $rewardIdArr;
	}

	public static function leaveLuxuryChest( $uid, $baseId )
	{
		$passObj = PassObj::getInstance( $uid );
		$chestShowInfo = $passObj->getVaParticular( PassDef::VA_CHESTSHOW );
		self::checkBase( $uid, $baseId );
		if( !isset( $chestShowInfo['goldChest'] ) || $chestShowInfo['goldChest'] == PassDef::CHEST_STATUS_DEAL )
		{
			throw new FakeException( 'already deal' );
		}
		$chestShowInfo['goldChest'] = PassDef::CHEST_STATUS_DEAL;
		$passObj->setVaParticular( PassDef::VA_CHESTSHOW , $chestShowInfo );
		//也就不需要检查之前的步骤做了没有了吧
		$passObj->update();
	}
	
	public static function checkBase( $uid, $needCheckBase )
	{
		$passObj = PassObj::getInstance( $uid );
		$curBase = $passObj->getBase();
		if( $curBase != $needCheckBase )
		{
			throw new FakeException( 'cur base: %s, want base: %s', $curBase, $needCheckBase );
		}
	}
	
	public static function dealBuff( $uid, $baseId, $pos, $hidArr = array() )
	{
		$passObj = PassObj::getInstance( $uid ); 
		$buffShow = $passObj->getVaParticular( PassDef::VA_BUFFSHOW );
		if( PassDef::LEAVE_BUFF == $pos )
		{
			foreach ( $buffShow as $buffPos => $buffInfo )
			{
				$buffShow[$buffPos]['status'] = PassDef::BUFF_STATUS_DEAL;
			}
			$passObj->setVaParticular( PassDef::VA_BUFFSHOW , $buffShow );
			$passObj->update();
			return;
		}
		
		
		if( $passObj->particularBuffIsDone($pos))
		{
			throw new FakeException( 'all buff dealed' );
		}
		
		$buffId = $buffShow[$pos]['buff'];
		$buffArr = btstore_get()->PASS_BUFF[$buffId]['buffArr'];
		$needStarNum = btstore_get()->PASS_BUFF[$buffId]['needStarNum'];
		Logger::debug('deal extra, buff baseId: %s, pos: %s, hidArr: %s, buffId: %s, buffArr: %s ', $baseId, $pos, $hidArr, $buffId, $buffArr);
		if(!$passObj->subStar( $needStarNum ))
		{
			throw new FakeException( 'lack star' );
		}
		
		$reviveArr = array();
		$recoverHpArr = array();
		$recoverRageArr = array();
		$additionArr = array();
		foreach ( $buffArr as $index => $buffInfo )
		{
			if( PassDef::TYPE_ADDITION == $buffInfo[0] )
			{
				$additionArr[] = array( $buffInfo[1] => $buffInfo[2] );
			}
			elseif( PassDef::TYPE_RECOVER_HP == $buffInfo[0] )
			{
				$recoverHpArr[] = array($buffInfo[1], $buffInfo[2]);
			}
			elseif( PassDef::TYPE_RECOVER_RAGE == $buffInfo[0] )
			{
				$recoverRageArr[] = array($buffInfo[1], $buffInfo[2]);
			}
			elseif( PassDef::TYPE_REVIVE )
			{
				$reviveArr[] = array($buffInfo[1], $buffInfo[2]);
			}
		}
	 	
		if((count( $reviveArr ) + count( $recoverHpArr ) + count( $recoverRageArr )) > 1)
		{
			throw new ConfigException( 'revive and recover , too much' );
		}
		
		if( !empty( $additionArr ) )
		{
			$addtion = Util::arrayAdd2V( $additionArr );
			$buffNowHave = $passObj->getVaParticular( PassDef::VA_BUFFINFO );
			$additonAll = array( $buffNowHave, $addtion );
			$additionTotal = Util::arrayAdd2V( $additonAll );
			$passObj->setVaParticular( PassDef::VA_BUFFINFO , $additionTotal );
		}
		
		if( !empty( $recoverHpArr ) ) 
		{
			self::checkHidArrValid($hidArr, $recoverHpArr[0][0]);
			$heroInfo = self::addHpRageByHidArr($uid, $hidArr, $recoverHpArr[0][1], 0);
		}
		elseif( !empty( $recoverRageArr ) )
		{
			self::checkHidArrValid($hidArr, $recoverRageArr[0][0] );
			$heroInfo = self::addHpRageByHidArr($uid, $hidArr, 0, $recoverRageArr[0][1]);
		}
		elseif( !empty( $reviveArr ) )
		{
			self::checkHidArrValid($hidArr, $reviveArr[0][0] );
			$heroInfo = self::reviveHeroByHidArr($uid, $hidArr, $reviveArr[0][1] );
		}
		if( !empty( $heroInfo ) )
		{
			$passObj->setVaParticular( PassDef::VA_HEROINFO , $heroInfo );
		}
		$buffShow[$pos]['status'] = PassDef::BUFF_STATUS_DEAL;
		$passObj->setVaParticular( PassDef::VA_BUFFSHOW, $buffShow );
		
		$passObj->update();
		
	}
	
	public static function checkHidArrValid( $hidArr, $num )
	{
		if(  count( $hidArr ) > $num || empty( $hidArr ) )
		{
			throw new FakeException( 'want cover: %s ,allowed: %s', count( $hidArr ), $num );
		}
		
		return true;
	}
	
	public static function setPassFormation( $uid, $passFormation, $bench )
	{
		self::checkAndChange($uid, $passFormation, $bench);
		$passObj = PassObj::getInstance( $uid );
		$passObj->setVaParticular( PassDef::VA_FORMATION , self::filterFormation( $passFormation ));
		$passObj->setVaParticular( PassDef::VA_BENCH , self::filterFormation( $bench ) );
		$passObj->update();
		$user = EnUser::getUserObj( $uid );
		
		return 'ok';
	}
	
	
	public static function changeEquip( $uid, $hid1, $hid2 )
	{
		$passObj = PassObj::getInstance( $uid );
		$heroPartInfo = $passObj->getVaParticular( PassDef::VA_HEROINFO );
		if( !isset( $heroPartInfo[ $hid1 ] ) || !isset( $heroPartInfo[ $hid2 ] ) )
		{
			throw new FakeException( 'hid1: %s or hid2: %s not in heroinfo: %s', $hid1, $hid2, $heroPartInfo );
		}
		$equip1 = array();
		$equip2 = array();
		if( isset( $heroPartInfo[ $hid1 ][PassDef::EQUIP] ) )
		{
			$equip1 = $heroPartInfo[ $hid1 ][PassDef::EQUIP];
		}
		if( !empty( $heroPartInfo[ $hid2 ][PassDef::EQUIP] ) )
		{
			return;
		}
		
		$heroPartInfo[$hid1][PassDef::EQUIP] = $equip2;
		$heroPartInfo[$hid2][PassDef::EQUIP] = $equip1;
		$passObj ->setVaParticular( PassDef::VA_HEROINFO , $heroPartInfo);
		
		return $heroPartInfo;
	}

	
	public static function getExchange( $formation1, $formation2 )
	{
		foreach ( $formation1 as $pos1 => $hid1 )
		{
			if( !isset( $formation2[$pos1] ) || $formation2[$pos1] <= 0 )
			{
				throw new FakeException( 'formation1: %s, formation2:%s', $formation1, $formation2 );
			}
			elseif ( $hid1 != $formation2[$pos1] )
			{
				return array($hid1 => 1, $formation2[$pos1] => 1);
			}
		}
		return array();
	}
	
	public static function getChange( $uid, $formation, $bench )
	{	
		$passObj = PassObj::getInstance( $uid );
		
		$formation = self::filterFormation( $formation );
		$bench = self::filterFormation( $bench );
		$originalFormation = self::filterFormation( 
				$passObj->getVaParticular( PassDef::VA_FORMATION ) 
			);
		$originalBench = self::filterFormation(
				$passObj->getVaParticular( PassDef::VA_BENCH )
			 );

		$benchNum = count( $bench );
		$originalBenchNum = count( $originalBench );
		$formationNum = count( $formation  );
		$originalFormationNum = count( $originalFormation );
		
		if( $benchNum == $originalBenchNum )
		{
			//A换，B换或者AB间换
			if( $bench ==  $originalBench )
			{
				if( $formationNum == $originalFormationNum )
				{
					//不牵扯到装备的变化是不管的
				}
			}
			elseif( $formation == $originalFormation )
			{
				//不牵扯到装备的变化是不管的
			}
			else
			{
				//这个就要管一下了
				$exc1 = self::getExchange($formation, $originalFormation);
				$exc2 = self::getExchange( $bench , $originalBench );
				if( $exc1 != $exc2 || empty( $exc1 ) )
				{
					throw new FakeException( 'invalid change, formation: %s, %s, %s, %s', $formation, $originalFormation, $bench, $originalBench );
				}
				return $exc1;
			}
			
		}
		elseif ( $benchNum > $originalBenchNum )
		{
			//A->B
			//不牵扯到装备的变化是不管的
		}
		else 
		{
			//B->A
			//不牵扯到装备的变化是不管的
		}
		
		return array();
	}
	
	public static function checkAndChange( $uid, $formationArg, $benchArg )
	{
		if( array_keys( $formationArg ) != array_unique( array_keys( $formationArg ) ) )
		{
			throw new FakeException( 'has same pos: %s', $formationArg );
		}
		if( array_keys( $benchArg ) != array_unique( array_keys( $benchArg ) ) )
		{
			throw new FakeException( 'has same pos: %s', $benchArg );
		}
		$formation = array_unique( self::filterFormation( $formationArg ) );
		$bench = array_unique( self::filterFormation( $benchArg ) );
		if( empty( $formation ) )
		{
			throw new FakeException('no one in formation' );
		}
		
		$user = EnUser::getUserObj( $uid );
		$userLevel = $user->getLevel();
		$arrOpenPos = MyFormation::getArrOpenPos( $userLevel );
		$diff1 = array_diff( array_unique( array_keys( $formation ) ), $arrOpenPos );
		$diff2 = array_diff( array_unique( array_keys( $bench ) ), self::getVicePos( PassCfg::VICE_NUM ) );
		if( !empty( $diff1 ) || !empty( $diff2 ) )
		{
			throw new FakeException( 'invalid pos: %s, %s, %s', $formation, $bench, $arrOpenPos  );
		}
		//位置的合法性检查完毕
		
		$passObj = PassObj::getInstance( $uid );
		$arrHeroInfo = $passObj->getVaParticular( PassDef::VA_HEROINFO );
		$allHeroBack = array_unique( array_keys( $arrHeroInfo ) );
		$allHeroFront =  array_unique( array_merge( $formation, $bench ) ) ;
		$diff3 = array_diff($allHeroFront, $allHeroBack);
		$diff4 = array_diff($allHeroBack, $allHeroFront);
		if( !empty( $diff3 ) || !empty( $diff4 ) )
		{
			throw new FakeException( 'front and back diff: %s, %s', $allHeroFront, $allHeroBack );
		}
		
		$allDie = true;
		foreach ( $formation as $formationHid )
		{
			if( $arrHeroInfo[$formationHid][PassDef::HP_PERCENT] > 0 )
			{
				$allDie = false;//break;
			}
		}
		if( $allDie )
		{
			throw new FakeException( 'all hero die: %s, %s', $formation, $arrHeroInfo );
		}
		
		$originalFormation = self::filterFormation( $passObj->getVaParticular( PassDef::VA_FORMATION ) );
		$originalBench = self::filterFormation( $passObj->getVaParticular( PassDef::VA_BENCH ) );
		$diff5 = array_diff( $formation , $originalFormation );
		$diff6 = array_diff( $bench , $originalBench );
		$countDiff5 = count( $diff5 );//替换上场的
		$countDiff6 = count( $diff6 );//换下来的
		Logger::debug( 'diff info: %s, %s',$diff5, $diff6 );
		if( $countDiff5 == 1 && $countDiff6 == 1 )
		{
			reset( $diff5 );
			reset( $diff6 );
			//这是需要换装备的
			$hid1 = current( $diff5 );
			$hid2 = current( $diff6 );
			Logger::debug('hid info: %s, %s', $hid1, $hid2 );
			self::changeEquip($uid, $hid2, $hid1);
		}
		elseif( in_array( $countDiff5 , array(0,1)) && in_array( $countDiff6 , array(0,1)) ) 
		{
			Logger::debug('normal: %s, %s', $formationArg, $benchArg);
		}
		else 
		{
			throw new FakeException( 'invalid formation change: %s, %s', $formationArg, $benchArg );
		}
	}
	
	public static function getVicePos( $viceNum )
	{
		$arr = array();
		for ( $i = 0;$i<$viceNum;$i++ )
		{
			$arr[] = $i;
		}
		return $arr;
	}
	
	public static function getRankList( $uid )
	{
		$timeArr = self::getTimeRange();
		$wheres = array( 
				array( 'reach_time', '>', $timeArr[0] ),
				
		 );
		$top50 = PassDao::getRankList( PassDef::$rankShowFields, $wheres, 0, 50 );
		
		$uidArr = Util::arrayExtract( $top50 , 'uid');
		$userMoreInfoArr = EnUser::getArrUserBasicInfo($uidArr, PassDef::$rankShowUserFields );
		
		$arrGuildId = Util::arrayExtract( $userMoreInfoArr , 'guild_id' );
		$arrGuildInfo = EnGuild::getArrGuildInfo( $arrGuildId, PassDef::$rankShowGuildFields );
		
		
		foreach ( $top50 as $rank => $topInfo )
		{
			$top50[$rank] = $topInfo + $userMoreInfoArr[$topInfo['uid']];
			if( !empty( $top50[$rank]['guild_id'] ) )
			{
				$top50[$rank]['guild_name'] = $arrGuildInfo[$top50[$rank]['guild_id']]['guild_name'];
				
			}
			
			if( $topInfo['uid'] == $uid )
			{
				$myRank = $rank + 1;
				$point = $topInfo['point'];
				$passNum = $topInfo['pass_num'];
			}
		}
		
		if( !isset( $myRank ) )
		{
			$myRank = self::getMyRank( $uid );
			$point = PassObj::getInstance($uid)->getPoint();
			$passNum = PassObj::getInstance($uid)->getPassNum();
		}
		
		$top50AndMyRank = array( 'top' => $top50, 'myRank' => $myRank,'point' => $point, 'pass_num' => $passNum );
		
		return $top50AndMyRank;
	}
	
	public static function rewardForRank()
	{
 		if( !self::isHandsOffTime( time() ) )
		{
			throw new InterException( 'pass is going on, can not resend' );
		}
		
		$timeArr = self::getTimeRange();//5点到5点
		$validTimeUntil = $timeArr[1];
		$wheres = array( 
				array( 'reach_time', '>', $timeArr[0] ),
		 );
		
		$rank = 1;
		$offset = 0;
		$limit = CData::MAX_FETCH_SIZE;
		$rewardFinish = false;
		
		//收集所有需要通知的uid，然后一起通知
		$arrNotifyUid = array();
		MailConf::$NO_CALLBACK = true;
		RewardCfg::$NO_CALLBACK = true;
		$sleepCount = 0;
		
		do
		{
			$rewardUserArrPortion = false;
			for ($i = 0; $i < 3; $i++)
			{
				try 
				{
					$rewardUserArrPortion = PassDao::getRankList( PassDef::$rankRewardFields, $wheres, $offset, $limit );
				}
				catch (Exception $e)
				{
					Logger::warning('get rewardUserArrPortion from t_pass failed onece!');
					continue;
				}
				break;
			}
			if ( $rewardUserArrPortion === false )
			{
				throw new InterException('get rewardUserArrPortion from t_pass failed finally,next rank: %s!', $rank );
				//$rewardUserArrPortion = array();
			}
		
			foreach ( $rewardUserArrPortion as $userIndex => $userInfo )
			{
				if( time() > $validTimeUntil )
				{
					throw new InterException( 'reward time too short, next user: %s, rank is: %s',$userInfo['uid'], $rank );
				}
				self::rewardUser( $userInfo['uid'], $rank );
				$arrNotifyUid[] = $userInfo['uid'];
				$rank++;
				if( $rank > PassCfg::REWARD_MAX_RANK )
				{
					$rewardFinish = true;
					break;
				}
				$sleepCount++;
				if( $sleepCount == PassCfg::SLEEP_COUNT )
				{
					usleep( PassCfg::USECONDS );
					$sleepCount = 0;
				}
			}
			if( count( $rewardUserArrPortion ) < $limit || $rewardFinish )
			{
				break;
			}
			$offset += count( $rewardUserArrPortion ); 
		}
		while ( true );
		Logger::info( 'send pass reward done, user num: %s', count( $arrNotifyUid ));
		
		RPCContext::getInstance()->sendMsg($arrNotifyUid, PushInterfaceDef::MAIL_CALLBACK, array() );
		RPCContext::getInstance()->sendMsg($arrNotifyUid, PushInterfaceDef::REWARD_NEW, array() );
		
		return;
	}
	
	
	public static function rewardUser( $uid, $rank, $checkRewardTime = TRUE )
	{
		try
		{
			$rewardArr = self::getRewardArr( $rank );
			$passObj = PassObj::getInstance( $uid );
			$lastRewardTime = $passObj->getRewardTime();
			if( $checkRewardTime )
			{
				if( self::alreadyReward( $lastRewardTime ) )
				{
					Logger::warning('already send once, last reward time: %s', $lastRewardTime);
					return;
				}
			}
			
			$passObj->setRewardTime( Util::getTime() );
			$passObj->update();
			PassObj::releaseInstance( $uid );
			
			if( empty( $rewardArr ) )
			{
				Logger::warning('uid: %s, rank: %s, reward is empty', $uid, $rank );
				return;
			}
			
			RewardUtil::reward3DtoCenter( $uid, array( $rewardArr ), RewardSource::PASS_RANK_REWARD, array( 'rank' => $rank ) );
			MailTemplate::sendPassRank( $uid, $rank, $rewardArr );
			
			Logger::info('send pass reward success, uid: %s, rank: %s', $uid, $rank );
		}
		catch ( Exception $e )
		{
			Logger::fatal('send pass reward fail, uid: %s, rank: %s', $uid, $rank );
		}
		
		
	}
	
	public static function getRewardArr( $rank )
	{
		$rewardConf = btstore_get()->PASS_REWARD;
		foreach ( $rewardConf as $rewardId => $rewardInfo )
		{
			if( $rank >= $rewardInfo['rankMin'] && $rank <= $rewardInfo['rankMax'] )
			{
				return $rewardInfo['rewardArr'];
			}
		}
		Logger::warning(' rank: %s, no reward',$rank );
		return array();
	}

	
	public static function alreadyReward( $lastRewardTime )
	{
		$handsoffLasttime =  PassCfg::HANDSOFF_LASTTIME;
		
		$timeArr = self::getTimeRange();
		if( $lastRewardTime >= $timeArr[1] - $handsoffLasttime )
		{
			return true;
		}
	
		return false;
	}
	
	public static function isHandsOffTime( $time )
	{
		$handsoffLasttime = PassCfg::HANDSOFF_LASTTIME;
		
		$timeArr = self::getTimeRange();
		if( $time >= $timeArr[1] - $handsoffLasttime )
		{
			return true;
		}
		
		return false;
	}
	
	public static function getTimeRange()
	{
		$handsoffLasttime = PassCfg::HANDSOFF_LASTTIME;
		$handsoffBegintime = PassCfg::HANDSOFF_BEGINTIME;
		
		$curTime = Util::getTime();
		$sendRewardEndTime = strtotime( date( 'Ymd', $curTime ).$handsoffBegintime )  + $handsoffLasttime ;
		if( $curTime > $sendRewardEndTime )
		{
			$timeArr = array( $sendRewardEndTime, $sendRewardEndTime + SECONDS_OF_DAY );
		}
		else 
		{
			$timeArr = array( $sendRewardEndTime - SECONDS_OF_DAY, $sendRewardEndTime );
		}
		
		if( ($timeArr[1] - $timeArr[0]) < $handsoffLasttime )
		{
			throw new ConfigException( 'time too short' );
		}
		Logger::debug('time range: %s', $timeArr);
		
		return $timeArr;
	}
	
	public static function getMyRank( $uid )
	{
		$passObj = PassObj::getInstance( $uid );
		
		$point = $passObj->getPoint();
		if( empty( $point ) )
		{
			return 0;	
		}
		
		$passNum = $passObj->getPassNum();
		$reachTime = $passObj->getReachTime();
		$timeArr = self::getTimeRange();
		
		$wheresOne[] = array( 'point','>', $point );
		$wheresOne[] = array( 'reach_time', '>', $timeArr[0] );
		
		$wheresTwo[] = array( 'point', '=', $point  );
		$wheresTwo[] = array( 'pass_num', '>', $passNum  );
		$wheresTwo[] = array( 'reach_time', '>', $timeArr[0] );
		
		$wheresThree[] = array( 'point', '=', $point  );
		$wheresThree[] = array( 'pass_num', '=', $passNum  );
		$wheresThree[] = array( 'reach_time', 'BETWEEN', array( $timeArr[0], $reachTime-1 ) );
		
		$wheresFour[] = array( 'point', '=', $point  );
		$wheresFour[] = array( 'pass_num', '=', $passNum  );
		$wheresFour[] = array( 'reach_time', '=',$reachTime  );
		$wheresFour[] = array( 'uid', '<',$uid  );
		
		$allWheresArr = array( $wheresOne, $wheresTwo, $wheresThree, $wheresFour );
		$rank = 1;
		foreach ( $allWheresArr as $index => $oneWhere )
		{
			$count = PassDao::getCount( $allWheresArr[$index] );
			$rank += $count;
		}
		
		return $rank;
	}
	
	public static function buyAttackNum( $uid, $num )
	{
		$user = EnUser::getUserObj($uid);
		$vip = $user->getVip();
		$vipConf = btstore_get()->VIP[$vip];
		$passNumConf = $vipConf['passBuyNum'];

		$passObj = PassObj::getInstance($uid);
		$buyNum = $passObj->getBuyNum();

		$gold = 0;
		for ( $i = 0; $i < $num; $i ++ )
		{
			$index = $buyNum + $i;
			if( !isset( $passNumConf[$index] ) )
			{
				throw new FakeException( 'invalid num: %s', $index );
			}
			$gold += $passNumConf[$index];
		}

		if( $gold < 0 )
		{
			throw new FakeException( 'can not buy the: %s, vip: %s ', $buyNum, $vip );
		}
		
		if( !$user->subGold($gold, StatisticsDef::ST_FUNCKEY_PASS_BUY_NUM) )
		{
			throw new FakeException( 'lack gold: %s', $gold );
		}
		
		$passObj->addBuyNum($num);
		$user->update();
		$passObj->update();
		
	}
	
	public static function sweep($uid, $buyChest, $buyBuff)
	{
		$buyChest = intval($buyChest);
		$buyBuff = intval($buyBuff);
		
		$passObj = PassObj::getInstance($uid);
		
		$SweepInfo = $passObj->getVaParticular('sweepInfo');
		if(empty($SweepInfo))
		{
			throw new FakeException('No sweep info.');
		}
		
		// 可扫荡次数
		$FinalLevel = intval($SweepInfo['count'] * PassCfg::SWEEP_RATIO);
		if(	$FinalLevel <= 0 )
		{
			throw new FakeException('sweep count <= 0');
		}
		$SweepInfo['count'] = $FinalLevel;
		
		// 是否扫荡过
		if( $SweepInfo['isSweeped'])
		{
			throw new FakeException('sweep before');
		}
		$SweepInfo['isSweeped'] = true;
		
		// 检查起始关卡
		$Base = $passObj->getBase();
		if( $Base != 1)
		{
			throw new FakeException('base != 1');
		}
	
		$RetArr = array();
		// 复用原先的代码。 先随机奖励，然后再逐一处理奖励。 
		while($Base <= $FinalLevel)
		{
			$passObj->addPassNum(1);
			
			// 获得积分和星星
			$MaxDegree = self::getMaxDegree($Base);
			$baseConf = btstore_get()->PASS_BASE[$Base];
			$baseRewardConf = $baseConf['basePassRewardArr'][$MaxDegree];
			$gainPoint = intval( PassCfg::SWEEP_POINT * $baseRewardConf[0] );
			$gainStar = intval( PassCfg::SWEEP_STAR * $baseRewardConf[1] );
			$passObj->addPoint( $gainPoint );
			$passObj->addStar( $gainStar );
			
			//奖励
			$ChestArr = self::getChestShowInfo($Base);
			if(! $buyChest)
			{
				$ChestArr['goldChest'] = PassDef::CHEST_STATUS_DEAL;
			}
			
			if($buyBuff)
			{
				$BuffArr = self::getBuffShowInfo($uid, $Base);
			}
			else
			{
				$BuffArr = array();
			}
			
			$passObj->setVaParticular( PassDef::VA_CHESTSHOW, $ChestArr );
			$passObj->setVaParticular( PassDef::VA_BUFFSHOW, $BuffArr );
			
			//宝箱奖励
			$ChestReward = array();
			
			$GoldPaid = 0;
			if($ChestArr['freeChest'] == PassDef::CHEST_STATUS_UNDEAL)
			{
				$ChestRewardInfo = self::dealChestUnsave($uid, $Base, false, 1);
				$ChestReward = array_merge($ChestReward, $ChestRewardInfo['reward']);
			}
			
			if($ChestArr['goldChest'] == PassDef::CHEST_STATUS_UNDEAL)
			{
				$ChestRewardInfo = self::dealChestUnsave($uid, $Base, true, $buyChest);
				$GoldPaid += $ChestRewardInfo['gold'];
				$ChestReward = array_merge($ChestReward, $ChestRewardInfo['reward']);
			}

			//Buff奖励
			$BuffReward = array();
			
			if($buyBuff)
			{
				foreach($BuffArr as $Pos => $Buff)
				{
					if($Buff['status'] == PassDef::BUFF_STATUS_UNDEAL)
					{
						$BuffInfo = self::dealBuffUnSave($uid, $Base, $Pos);
						if(!empty($BuffInfo))
						{
							$BuffReward[] = $BuffInfo;
						}
					}
				}
			}
			
			$RetArr [$Base] = array(
				$ChestReward,
				$BuffReward,
				$passObj->getStar(),
				$GoldPaid,
			);
			$passObj->refreshIfDone();
			$Base = $passObj->getBase();
		}
		
				// 记录购买信息
		$SweepInfo['buyChest'] = $buyChest;
		$SweepInfo['buyBuff'] = $buyBuff;
		$passObj->setVaParticular(PassDef::VA_SWEEPINFO, $SweepInfo);
		
		$passObj->update();
		
		BagManager::getInstance()->getBag( $uid )->update();
		EnUser::getUserObj()->update();

		return $RetArr;
	}
	
	public static function dealChestUnsave( $uid, $baseId, $isLuxury, $num )
	{
		Logger::trace('now deal chest, isLuxury: %s', $isLuxury);
		$passObj = PassObj::getInstance( $uid );
		$baseId = $passObj->getBase();
		$buyNum = 0;
		$nextNum = 1;
		if( !$isLuxury && $num != 1 )
		{
			throw new FakeException( 'need 1 for general chest' );
		}
		if( !$passObj->baseIsPass() )
		{
			throw new FakeException( 'base: %s not pass', $baseId );
		}
		
		if( $passObj->chestIsDone() )
		{
			throw new FakeException( 'chest done' );
		}
		
		$baseConf = btstore_get()->PASS_BASE[$baseId];
		$chestConf = btstore_get()->PASS_CHEST;
		$chestkey = $isLuxury?  'goldChest' : 'freeChest'  ;
		$chestArr = $isLuxury? $baseConf['goldChestArr'] : $baseConf['freeChestArr'];
		$weight = $isLuxury? 'goldWeight' : 'freeWeight';
		
		$chestShow = $passObj->getVaParticular( PassDef::VA_CHESTSHOW );
		if( !isset( $chestShow[$chestkey] ) || $chestShow[$chestkey] == PassDef::CHEST_STATUS_DEAL )
		{
			throw new FakeException( 'no chest, or already received for base: %s, uid: %s', $baseId, $uid );
		}

		$user = EnUser::getUserObj( $uid );
		$needGold = 0;
		if( !$isLuxury )
		{
			$chestShow['freeChest'] = PassDef::CHEST_STATUS_DEAL;
			$passObj->setVaParticular( PassDef::VA_CHESTSHOW, $chestShow );
		}
		else
		{
			// 原先需求的话需要自动放弃，这里则不需要
			$chestShow['goldChest'] = PassDef::CHEST_STATUS_DEAL;
			$passObj->setVaParticular( PassDef::VA_CHESTSHOW, $chestShow );
			
			$buyNum = $passObj->getLuxuryNum();
			$nextNum = $buyNum + $num;
			$goldNeedArr = $baseConf['chestNeedGoldArr'];
			
			$MaxChest = self::getMaxChest($baseId);
			if( $nextNum > $MaxChest)
			{
				$nextNum = $MaxChest;
			}
			for ( $i = $buyNum+1; $i <= $nextNum; $i++  )
			{
				$find = false;
				foreach ( $goldNeedArr as $oneNum => $gold )
				{
					if( $oneNum >= $i)
					{
						$needGold += $goldNeedArr[$oneNum];
						$find = true;
						break;
					}
				}
				if( !$find )
				{
					throw new FakeException( 'no conf for num: %s', $nextNum );
				}
			}
			
			if( !$user->subGold( $needGold , StatisticsDef::ST_FUNCKEY_PASS_GOLD_CHEST_COST ) )
			{
				throw new FakeException( 'lack gold' );
			}
			$passObj->addLuxuryNum( $num );
		}
		
		$rewardArr = array();
		$rewardIdArr = array();
		for ( $i = $buyNum+1; $i <= $nextNum; $i++  )
		{
			foreach ( $chestArr as $chestArrIndex => $chestArrPortion  )
			{
				$chestSamples = array();
				foreach ( $chestArrPortion[1] as $chestId )
				{
					if( !isset( $chestConf[$chestId] ) )
					{
						throw new ConfigException( 'want to roll chestid: %s, no conf', $chestId );
					}
					$chestSamples[$chestId] = $chestConf[$chestId];
				}
				Logger::debug('chestSamples are: %s', $chestSamples );
				$rollRet = Util::noBackSample( $chestSamples , $chestArrPortion[0], $weight );
				foreach ( $rollRet as $rollIndex => $rollId )
				{
					$rewardIdArr[] = $rollId;
					$partReward = $chestConf[$rollId]['rewardArr']->toArray();
					$rewardArr = array_merge( $rewardArr, $partReward );
				}
			}
		}
		
		if( empty( $rewardArr ) )
		{
			throw new ConfigException( 'empty reward for base: %s', $baseId );
		}
		Logger::debug('rewardArr in chest are: %s', $rewardArr);
		RewardUtil::reward3DArr( $uid, $rewardArr, StatisticsDef::ST_FUNCKEY_PASS_GOLD_CHEST_REWARD, true );
		
		Logger::debug('deal extra chest, baseId: %s, isLuxury : %s, rewardArr: %s ', $baseId, $isLuxury, $rewardArr);
		
		return array('reward' => $rewardIdArr, 'gold' => $needGold);
	}
	
	public static function dealBuffUnsave( $uid, $baseId, $pos, $hidArr = array() )
	{
		$passObj = PassObj::getInstance( $uid ); 
		$buffShow = $passObj->getVaParticular( PassDef::VA_BUFFSHOW );
		
		if( $passObj->particularBuffIsDone($pos))
		{
			throw new FakeException( 'all buff dealed' );
		}
		
		$buffId = $buffShow[$pos]['buff'];
		$buffArr = btstore_get()->PASS_BUFF[$buffId]['buffArr'];
		$needStarNum = btstore_get()->PASS_BUFF[$buffId]['needStarNum'];
		Logger::debug('deal extra, buff baseId: %s, pos: %s, buffId: %s, buffArr: %s ', $baseId, $pos, $buffId, $buffArr);
		
		//这里不需要属性类型外的buff
		$additionArr = array();
		foreach ( $buffArr as $index => $buffInfo )
		{
			if( PassDef::TYPE_ADDITION == $buffInfo[0] )
			{
				$additionArr[] = array( $buffInfo[1] => $buffInfo[2] );
			}
		}

		$buffShow[$pos]['status'] = PassDef::BUFF_STATUS_DEAL;
		$passObj->setVaParticular( PassDef::VA_BUFFSHOW, $buffShow );
		
		if( !empty( $additionArr ) )
		{
			//如果随机到了属性buff那么再扣星
			if(!$passObj->subStar( $needStarNum ))
			{
				//如果星不够就直接返回
				$buffShow[$pos]['status'] = PassDef::BUFF_STATUS_DEAL;
				$passObj->setVaParticular( PassDef::VA_BUFFSHOW, $buffShow );
				return array();
			}
			
			$addtion = Util::arrayAdd2V( $additionArr );
			$buffNowHave = $passObj->getVaParticular( PassDef::VA_BUFFINFO );
			$additonAll = array( $buffNowHave, $addtion );
			$additionTotal = Util::arrayAdd2V( $additonAll );
			$passObj->setVaParticular( PassDef::VA_BUFFINFO , $additionTotal );
		
			return array($buffId, $needStarNum);
		}
		else
		{
			return array();
		}
	}
	
	// 每关最高难度
	private static function getMaxDegree($Base)
	{
		$Conf = btstore_get()->PASS_BASE[$Base];
		
		$Max = 1;
		foreach($Conf['basePassRewardArr'] as $Degree => $DegreeConf)
		{
			if($Degree > $Max)
			{
				$Max = $Degree;
			}
		}
		return $Max;
	}
	
	// 每关最多宝箱
	private static function getMaxChest($Base)
	{
		$baseConf = btstore_get()->PASS_BASE[$Base];
		$chestArr = $baseConf['chestNeedGoldArr'];
		
		$Max = 1;
		foreach($chestArr as $Chest => $ChestConf)
		{
			if($Chest > $Max)
			{
				$Max = $Chest;
			}
		}
		return $Max;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
