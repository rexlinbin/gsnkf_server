<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ACopyLogic.class.php 246716 2016-06-16 13:12:52Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/acopy/ACopyLogic.class.php $
 * @author $Author: GuohaoZheng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-06-16 13:12:52 +0000 (Thu, 16 Jun 2016) $
 * @version $Revision: 246716 $
 * @brief 
 *  
 **/
class ACopyLogic
{
	public static function getActivityCopyList()
	{
	    $myACopy = MyACopy::getInstance();
		$copyList = $myACopy->getActivityCopyList();
		if(isset($copyList[ACT_COPY_TYPE::GOLDTREE_COPYID]))
		{
		    $copyInfo = $copyList[ACT_COPY_TYPE::GOLDTREE_COPYID];
		    if(!empty($copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_BATTLEINFO]))
		    {
		        $battleInfo = $copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_BATTLEINFO];
		        $arrHero = array();
		        foreach($battleInfo['arrHero'] as $pos => $heroInfo)
		        {
		            $arrHero[$pos] = array(
		                    PropertyKey::HTID => $heroInfo[PropertyKey::HTID],
		                    PropertyKey::LEVEL => $heroInfo[PropertyKey::LEVEL],
		                    PropertyKey::EVOLVE_LEVEL => $heroInfo[PropertyKey::EVOLVE_LEVEL],
		                    );
		        }
		        $copyList[ACT_COPY_TYPE::GOLDTREE_COPYID]['va_copy_info']
		            [ACT_COPY_FIELD::VA_GOLD_TREE_BATTLEINFO] 
		                = array(
		                        'arrHero' => $arrHero,
		                        );
		    }
		}
		if (isset($copyList[ACT_COPY_TYPE::EXPUSER_COPYID]))
		{
			$copyInfo = $copyList[ACT_COPY_TYPE::EXPUSER_COPYID];
			if (empty($copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][ACT_COPY_FIELD::VA_EXP_USER_BASE_ID]))
			{
				$copyList[ACT_COPY_TYPE::EXPUSER_COPYID][NORMAL_COPY_FIELD::VA_COPY_INFO][ACT_COPY_FIELD::VA_EXP_USER_BASE_ID] = 0;
			}
			
			$baseId = $copyList[ACT_COPY_TYPE::EXPUSER_COPYID][NORMAL_COPY_FIELD::VA_COPY_INFO][ACT_COPY_FIELD::VA_EXP_USER_BASE_ID];
			$level = EnUser::getUserObj()->getLevel();
			
			$ExpUserConf = btstore_get()->EXPUSER;
			foreach ($ExpUserConf as $key => $value)
			{
				if ($key > $baseId && $level >= $value[EXP_USER_FIELD::BASE_LEVEL])
				{
					$baseId = $key;
					break;
				}
			}
			$copyList[ACT_COPY_TYPE::EXPUSER_COPYID][NORMAL_COPY_FIELD::VA_COPY_INFO][ACT_COPY_FIELD::VA_EXP_USER_BASE_ID] = $baseId;
		}
		$myACopy->save();
		return $copyList;
	}
	/**
	 * 获取某个活动的具体信息
	 * @param int $copyId
	 */
	public static function getActivityCopyInfo($copyId)
	{
		$copyInfo = MyACopy::getInstance()->getActivityCopyInfo($copyId);
		if(empty($copyInfo))
		{
		    throw new FakeException('the copy with copyid %s is not open.',$copyId);
		}
		return $copyInfo;
	}
	/**
	 * 战斗接口
	 * @param int $copyId
	 * @param int $armyId
	 * @param int $act_level
	 */
	public static function atkActBase($copyId,$baseLv,$armyId,$fmt)
	{
		$baseId = intval(btstore_get()->ACTIVITYCOPY[$copyId]['base_id']);
		//判断是否能够攻击此部队
		$canAtk = self::canAttack($copyId,$baseLv,$armyId);
		if($canAtk != 'ok')
		{
			throw new FakeException('can not attack,the reason is %s.',$canAtk);
		}
		$atkRet = BaseDefeat::doBattle(BaseDefeat::MODULE_ACOPY, $armyId, $baseId, $fmt, $baseLv);
		//可能导致升级 开启新的活动副本
// 		$newCopy = MyACopy::getInstance()->checkOpenNewCopy();
// 		$atkRet['newcopyorbase'] = $newCopy;
		MyACopy::getInstance()->save();
		EnUser::getUserObj()->update();	
		BagManager::getInstance()->getBag()->update();
		AtkInfo::getInstance()->saveAtkInfo();	
		return $atkRet;
	}
	
	public static function atkGoldTree($copyId,$fmt,$byItem)
	{
	    $actObj = MyACopy::getInstance()->getActivityCopyObj($copyId);
	    if($actObj == NULL || ($actObj->getType() != ACT_COPY_TYPE::GOLDTREE))
	    {
	        throw new FakeException('the copy with copyid not exist.',$copyId);
	    }
	    $baseId = intval(btstore_get()->ACTIVITYCOPY[$copyId]['base_id']);
	    $baseLv = BaseLevel::SIMPLE;
	    $lvName = CopyConf::$BASE_LEVEL_INDEX[$baseLv];
	    $armyIds = btstore_get()->BASE[$baseId][$lvName][$lvName.'_army_arrays']->toArray();
	    if(count($armyIds) < 1)
	    {
	        throw new ConfigException('no army to defend goldtree.');
	    }
	    $armyId = $armyIds[0];
	    AtkInfo::getInstance()->initAtkInfo($copyId, $baseId, $baseLv);
	    $canAtk = self::canAttack($copyId,BaseLevel::SIMPLE,$armyId);
	    if($canAtk != 'ok')
	    {
	        if(!($canAtk == 'no_defeat_num' && ($byItem)))
	        {
	            throw new FakeException('can not attack,the reason is %s.',$canAtk);
	        }
	    }
	    $bag = BagManager::getInstance()->getBag();
	    if($byItem)
	    {
	        if($actObj->getCanDefeatNum() > 0)
	        {
	            throw new FakeException('has defeat num %d.can not use item to atk gold tree.',$actObj->getCanDefeatNum());
	        }
	        $conf =  btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_RESETGOLDTREE_NEED_ITEM]->toArray();
    	    if(!isset($conf[$copyId]))
    	    {
    	        throw new FakeException('copyid %d is not in normal config %s',$copyId,$conf);
    	    }
    	    $needItem = $conf[$copyId];
    	    if(!empty($needItem) && ($bag->deleteItembyTemplateID($needItem, 1) == FALSE))
    	    {
    	        throw new FakeException('delete item %d failed.',$needItem);
    	    }
    	}
	    else
	    {
	        if($actObj->subCanDefeatNum() == FALSE)
	        {
	            throw new FakeException('not enough defeatnum.now is %d',$actObj->getCanDefeatNum());
	        }
	    }
	    $level = $actObj->getLevel();
	    $arrMonsterLv = array_fill(0, 6, $level);
        $atkRet = BaseDefeat::doBattle(BattleType::GOLD_TREE, $armyId, $baseId, $fmt, $baseLv, false, null, $arrMonsterLv);
        $atkRet['hurt'] = $atkRet['reward']['hurt'];
        $bag->update();
        unset($atkRet['reward']['hurt']);
        return $atkRet;
	}
	
	public static function atkExpTreasure($copyId,$armyId,$fmt)
	{
	    $actObj = MyACopy::getInstance()->getActivityCopyObj($copyId);
	    if($actObj == NULL || ($actObj->getType() != ACT_COPY_TYPE::EXPTREASURE))
	    {
	        throw new FakeException('the copy with copyid not exist.',$copyId);
	    }
	    $baseId = intval(btstore_get()->ACTIVITYCOPY[$copyId]['base_id']);
	    $baseLv = BaseLevel::SIMPLE;
	    $lvName = CopyConf::$BASE_LEVEL_INDEX[$baseLv];
	    $armyIds = btstore_get()->BASE[$baseId][$lvName][$lvName.'_army_arrays']->toArray();
	    if(count($armyIds) < 1)
	    {
	        throw new ConfigException('no army to defend goldtree.');
	    }
	    $canAtk = self::canAttack($copyId,BaseLevel::SIMPLE,$armyId);
	    if($canAtk != 'ok')
	    {
	        throw new FakeException('can not attack,the reason is %s.',$canAtk);
	    }
	    $atkRet = BaseDefeat::doBattle(BattleType::EXP_TREASURE, $armyId, $baseId, $fmt, $baseLv);
	    return $atkRet;
	}
	
	public static function atkExpHero($copyId,$armyId,$fmt)
	{
	    $actObj = MyACopy::getInstance()->getActivityCopyObj($copyId);
	    if($actObj == NULL || ($actObj->getType() != ACT_COPY_TYPE::EXPHERO))
	    {
	        throw new FakeException('the copy with copyid not exist.',$copyId);
	    }
	    $baseId = intval(btstore_get()->ACTIVITYCOPY[$copyId]['base_id']);
	    $baseLv = BaseLevel::SIMPLE;
	    $lvName = CopyConf::$BASE_LEVEL_INDEX[$baseLv];
	    $armyIds = btstore_get()->BASE[$baseId][$lvName][$lvName.'_army_arrays']->toArray();
	    if(count($armyIds) < 1)
	    {
	        throw new ConfigException('no army to defend goldtree.');
	    }
	    $canAtk = self::canAttack($copyId,BaseLevel::SIMPLE,$armyId);
	    if($canAtk != 'ok')
	    {
	        throw new FakeException('can not attack,the reason is %s.',$canAtk);
	    }
	    $atkRet = BaseDefeat::doBattle(BattleType::EXP_HERO, $armyId, $baseId, $fmt, $baseLv);
	    return $atkRet;
	}
	
	public static function atkGoldTreeByGold($copyId,$fmt)
	{
	    $actObj = MyACopy::getInstance()->getActivityCopyObj($copyId);
	    if($actObj->getCanDefeatNum() > 0)
	    {
	        throw new FakeException('gold tree has free atk num');
	    }
	    $vip = EnUser::getUserObj()->getVip();
	    $goldAtkNum = $actObj->getGoldAtkNum();
	    $vipConf = btstore_get()->VIP[$vip]['goldAtkTree']->toArray();
	    Logger::trace('atkGoldTreeByGold has atk %d.all num is %d.vipconf %s.',$goldAtkNum,count($vipConf),$vipConf);
	    if($goldAtkNum >= count($vipConf))
	    {
	        throw new FakeException('vip level is %d.gold atk num is %d.no gold atk num.',$vip,$goldAtkNum);
	    }
	    $needGold = $vipConf[$goldAtkNum];
	    $userObj = EnUser::getUserObj();
	    if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_COPY_ATK_GOLDTREE) == FALSE)
	    {
	        throw new FakeException('sub gold failed');
	    }
	    $ret = self::atkGoldTree($copyId, $fmt, TRUE);
	    return $ret;
	}
	
	/**
	 * 判断一个部队是否能够攻击
	 * @param int $copyId
	 * @param int $armyId
	 * @param int $base_level
	 */
	private static function canAttack($copyId,$baseLv,$armyId,$baseId = 0)
	{
		if (empty($baseId))
		{
			$baseId = intval(btstore_get()->ACTIVITYCOPY[$copyId]['base_id']);
		}
		if(CopyUtil::isArmyinBase($baseId, $baseLv, $armyId) == false)
		{
			throw new ConfigException('army %s is not in base % level %s.',$armyId,$baseId,$baseLv);
		}
		$actObj = MyACopy::getInstance()->getActivityCopyObj($copyId);
		$can = $actObj->canEnterAct();
		if($can != 'ok')
		{
			return $can;
		}
		if(CopyUtil::checkFightCdTime() == false)
		{
			return 'not cool down,can not attack.';
		}
		if(CopyUtil::checkDefeatPreArmy($armyId) == false)
		{
			return 'not defeat the pre army.';
		}
		
		return 'ok';
	}

	/**
	 * 战斗结果处理
	 * @param array $actRet
	 */
	public static function battleCallBack($btRet)
	{
		$copyId     = AtkInfo::getInstance()->getCopyId();
		$actObj		= MyACopy::getInstance()->getActivityCopyObj($copyId);
		$ret		= $actObj->battleCallBack($btRet);
		return $ret;
	}
	/**
	 * 获取活动攻击的攻略和排名信息
	 * @param int $copyId
	 * @param int $act_level
	 */
	public static function getActDefeatInfo($copyId,$base_level = 1)
	{
		$base_id	= intval(btstore_get()->ACTIVITY[$copyId]['base_id']);
		$baseDefeat = array();
		$baseDefeat['replay']	= CopyUtil::getReplayList($base_id, $base_level);
		$baseDefeat['rank']		= CopyUtil::getPreBaseAttackPlayer($base_id, $base_level);
		return $baseDefeat;
	}
	/**
	 *	活动据点的接口   进入活动据点的某个难度级别进行攻击
	 * @param int $copyId
	 * @param int $base_id
	 * @param int $base_level
	 * @throws Exception
	 * @return string
	 */
	public static function enterBaseLevel($copyId,$base_level)
	{
		$actObj = MyACopy::getInstance()->getActivityCopyObj($copyId);
		if($actObj == NULL)
		{
		    throw new FakeException('the copy with copyid not exist.',$copyId);
		}
		$ret = $actObj->canEnterAct();
		if($ret != 'ok')
		{
			throw new FakeException('can not enter base,reason is %s.',$ret);
		}
		//进入活动  初始化attackinfo
		self::initAttackInfo($copyId,$base_level);
		AtkInfo::getInstance()->saveAtkInfo();
		MyACopy::getInstance()->save();
		return 'ok';
	}

	/**
	 * 进入副本或者副本
	 * @param int $copyId
	 * @param int $base_level
	 * @return array
	 */
	private static function initAttackInfo($copyId,$baseLv)
	{
		$baseId = intval(btstore_get()->ACTIVITYCOPY[$copyId]['base_id']);
		AtkInfo::getInstance()->initAtkInfo($copyId, $baseId, $baseLv);
	}
	
	
	public static function buyGoldTreeAtkNum($num)
	{
	    $userObj = EnUser::getUserObj();
	    $goldTreeId = ACT_COPY_TYPE::GOLDTREE_COPYID;
	    $goldTree = MyACopy::getInstance()->getActivityCopyObj($goldTreeId);
	    $buyNum = $goldTree->getBuyAtkNum();
	    $buyNumLimit = btstore_get()->VIP[$userObj->getVip()]['goldtreeBuyNum'][0];
	    if($buyNum+$num > $buyNumLimit)
	    {
	        throw new FakeException('can not buy.current buynum is %d limit is %d,want to buy %d',$buyNum,$buyNumLimit,$num);
	    }
	    $initGold = btstore_get()->VIP[$userObj->getVip()]['goldtreeBuyNum'][1];
	    $incGold = btstore_get()->VIP[$userObj->getVip()]['goldtreeBuyNum'][2];
	    $needGold = 0;
	    for($i=0;$i<$num;$i++)
	    {
	        $needGold += ($initGold + ($buyNum + $i) * $incGold);
	    }
	    if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_BUY_GOLDTREE_ATKNUM) == FALSE)
	    {
	        throw new FakeException('sub gold failed.');
	    }
	    $goldTree->addCanDefeatNum($num);
	    $goldTree->addBuyAtkNum($num);
	    $userObj->update();
	    MyACopy::getInstance()->save();
	}
	
	
	public static function buyExpTreasAtkNum($num)
	{
	    $userObj = EnUser::getUserObj();
	    $expTreasId = ACT_COPY_TYPE::EXPTREAS_COPYID;
	    $expTreas = MyACopy::getInstance()->getActivityCopyObj($expTreasId);
	    $buyNum = $expTreas->getBuyAtkNum();
	    $buyNumLimit = btstore_get()->VIP[$userObj->getVip()]['exptreasBuyNum'][0];
	    if($buyNum+$num > $buyNumLimit)
	    {
	        throw new FakeException('can not buy.current buynum is %d limit is %d,want to buy %d',$buyNum,$buyNumLimit,$num);
	    }
	    $initGold = btstore_get()->VIP[$userObj->getVip()]['exptreasBuyNum'][1];
	    $incGold = btstore_get()->VIP[$userObj->getVip()]['exptreasBuyNum'][2];
	    $needGold = 0;
	    for($i=0;$i<$num;$i++)
	    {
	        $needGold += ($initGold + ($buyNum + $i) * $incGold);
    	}
    	if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_BUY_EXPTREAS_ATKNUM) == FALSE)
    	{
    	    throw new FakeException('sub gold failed.');
    	}
    	$expTreas->addCanDefeatNum($num);
    	$expTreas->addBuyAtkNum($num);
    	$userObj->update();
    	MyACopy::getInstance()->save();
	}
	
	public static function getGoldTreeBattleInfo($uid)
	{
	    $acopyInst = MyACopy::getInstance($uid);
	    $goldTree = $acopyInst->getActivityCopyObj(ACT_COPY_TYPE::GOLDTREE_COPYID);
	    $isBtInfoValid = $goldTree->isBattleInfoValid();
	    if(FALSE == $isBtInfoValid)
	    {
	        return EnUser::getUserObj($uid)->getBattleFormation();
	    }
	    return $goldTree->getBattleInfo();
	}
	
	public static function atkExpUser($copyId, $baseId, $armyId, $fmt,$uid)
	{
		$baseLv = BaseLevel::SIMPLE;
		if($armyId <= 0)
		{
			throw new ConfigException('wrong army id to defend expUser,army id %d.',$armyId);
		}
		
		$canAtk = self::canAtkExpUser($copyId, $baseLv, $baseId, $armyId, $uid);
		
		if($canAtk != 'ok')
		{
			throw new FakeException('can not attack,the reason is %s.',$canAtk);
		}
		
		$uid = RPCContext::getInstance()->getUid();
		$user = EnUser::getUserObj($uid);
		$arrMonsterLv = array();
		$module = BattleType::EXP_USER;
		
		$playerArr	= $user->getBattleFormation($fmt);
		$enemyArr = EnFormation::getMonsterBattleFormation($armyId,$baseLv,$arrMonsterLv);
		
		if(CopyUtil::checkFormation($playerArr['arrHero']) == FALSE)
		{
			$hpInfo = array();
			foreach($playerArr['arrHero'] as $heroInfo)
			{
				$hpInfo[$heroInfo[PropertyKey::HID]] = $heroInfo[PropertyKey::CURR_HP];
			}
			throw new FakeException('all hero in formation has no Hp.Hpinfo:%s',$hpInfo);
		}
		
		$btType = btstore_get()->ARMY[$armyId]['fight_type'];
		
		$atkRet = EnBattle::doHero($playerArr, $enemyArr, $btType);
		
		$ret = self::doneExpUser($atkRet, $module, $baseId);
		 
		$baseId = $ret['newcopyorbase'];
		unset($ret['newcopyorbase']);
		
		if (!empty($baseId))
		{
			$ret[EXP_USER_FIELD::BASE_ID] = $baseId;
		}
		
		return $ret;
	}
	
	private static function canAtkExpUser($copyId, $baseLv, $baseId, $armyId, $uid)
	{
		$actObj = MyACopy::getInstance()->getActivityCopyObj($copyId);
		$ret = $actObj->canEnterAct();
		if($ret != 'ok')
		{
			return $ret;
		}
		
		$expUserConf = btstore_get()->EXPUSER;
		
		if ($armyId != $expUserConf[$baseId][EXP_USER_FIELD::ARMY_ID])
		{
			return 'army id not match';
		}
		
		$userObj = EnUser::getUserObj($uid);
		$level = $userObj->getLevel();
		$needLevel = $expUserConf[$baseId][EXP_USER_FIELD::BASE_LEVEL];
		if ($level < $needLevel)
		{
			Logger::info('can not fight,level:%d, needLevel: %d.',$level,$needLevel);
			return "no enough level for this base";
		}
		
		$expUserId = ACT_COPY_TYPE::EXPUSER_COPYID;
		$expUser = MyACopy::getInstance()->getActivityCopyObj($expUserId);
		$copyInfo = $expUser->getCopyInfo();
		
		$maxBaseId = 0;
		
		if (!empty($copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][ACT_COPY_FIELD::VA_EXP_USER_BASE_ID]))
		{
			$maxBaseId = $copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][ACT_COPY_FIELD::VA_EXP_USER_BASE_ID];
		}
		
		$expUserConf = btstore_get()->EXPUSER;
		
		if ($baseId > $maxBaseId + 1)
		{
			Logger::info('can not fight,baseId:%d , maxBaseId:%d.',$baseId,$maxBaseId);
			return "not defeat the pre base";
		}
		
		return 'ok';
	}
	
	public static function doneExpUser($atkRet, $module, $baseId)
	{
		$armyId	= $atkRet['server']['uid2'];
		$atkRet['server']['pass'] = FALSE;
		$atkRet['server']['fail'] = FALSE;
		
		if (btstore_get()->ARMY[$armyId]['force_pass'] != CopyDef::FORCE_PASS
				&&(BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] > BattleDef::$APPRAISAL['D']))
		{
			$atkRet['server']['fail'] = TRUE;
		}
		else 
		{
			$atkRet['server']['pass'] = TRUE;
		}
		
		$newCorB = 0;
		$newCorB = ExpUser::doneExpUser($atkRet['server'],$baseId);
		
		$reward = array();
		if ($atkRet['server']['pass'])
		{
			$rewards = ExpUser::getPassReward($baseId);
			$reward = CopyUtil::rewardUser($rewards);
			Enuser::getUserObj()->update();
			BagManager::getInstance()->getBag()->update();
		}
		
		return array(
				'err'=>'ok',
				'reward' => $reward,
				'appraisal' => $atkRet['server']['appraisal'],
				'fightRet' => $atkRet['client'],
				'newcopyorbase'=>$newCorB
		);
	}
	
	public static function buyExpUserAtkNum($uid, $num)
	{
		$userObj = EnUser::getUserObj($uid);
		$expUserId = ACT_COPY_TYPE::EXPUSER_COPYID;
		$expUser = MyACopy::getInstance()->getActivityCopyObj($expUserId);
		
		$vip = $userObj->getVip();
		
		$buyNum = $expUser->getBuyAtkNum();
		$buyNumLimit = btstore_get()->VIP[$vip]['expUserBuyNum'][0];
		
		if ($buyNum + $num > $buyNumLimit)
		{
			throw new FakeException('num beyond limit. num:%d,hasBuyNum:%d,limit:%d',$num,$buyNum,$buyNumLimit);
		}
		$initGold = btstore_get()->VIP[$vip]['expUserBuyNum'][1];
		$incGold = btstore_get()->VIP[$vip]['expUserBuyNum'][2];
		
		$needGold = 0;
		for($i=0;$i<$num;$i++)
		{
			$needGold += ($initGold + ($buyNum + $i) * $incGold);
		}
		if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_BUY_EXPTREAS_ATKNUM) == FALSE)
		{
			throw new FakeException('sub gold failed.');
		}
		
		$expUser->addCanDefeatNum($num);
		$expUser->addBuyAtkNum($num);
		$userObj->update();
		MyACopy::getInstance($uid)->save();
		
		return 'ok';
	}
	
	public static function atkDestiny($copyId, $baseId, $armyId, $fmt)
	{
	    $actObj = MyACopy::getInstance()->getActivityCopyObj($copyId);
	    if($actObj == NULL || ($actObj->getType() != ACT_COPY_TYPE::DESTINY))
	    {
	        throw new FakeException('the copy with copyid not exist.',$copyId);
	    }
	    $baseId = intval(btstore_get()->ACTIVITYCOPY[$copyId]['base_id']);
	    $baseLv = BaseLevel::SIMPLE;
	    $lvName = CopyConf::$BASE_LEVEL_INDEX[$baseLv];
	    $armyIds = btstore_get()->BASE[$baseId][$lvName][$lvName.'_army_arrays']->toArray();
	    if(count($armyIds) < 1)
	    {
	        throw new ConfigException('no army to defend goldtree.');
	    }
	    $canAtk = self::canAttack($copyId,BaseLevel::SIMPLE,$armyId);
	    if($canAtk != 'ok')
	    {
	        throw new FakeException('can not attack,the reason is %s.',$canAtk);
	    }
	    $atkRet = BaseDefeat::doBattle(BattleType::DESTINY, $armyId, $baseId, $fmt, $baseLv);
	    return $atkRet;
	}
	
	public static function buyDestinyNum($uid, $num=1)
	{
	    $userObj = EnUser::getUserObj($uid);
	    
	    $destinyUser = MyACopy::getInstance()->getActivityCopyObj(ACT_COPY_TYPE::DESTINY_COPYID);
	    $canAtkNum = $destinyUser->getCanDefeatNum();
	    
	    if ( !empty( $canAtkNum ) )
	    {
	        throw new FakeException("remain atk num. canAtkNum:%d.", $canAtkNum);
	    }
	    
	    $buyNum = $destinyUser->getBuyAtkNum();
	    
	    $arrNumLimit = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_ADESACT_BUY_LIMIT];
	    
	    if ( $buyNum + $num > $arrNumLimit[0] )
	    {
	        throw new FakeException("buyNum. beyond limit. hasBuyNum:%d limit:%d.", $buyNum, $arrNumLimit[0]);
	    }
	    
	    $baseGold = intval( $arrNumLimit[1] );
	    $deltGold = intval( $arrNumLimit[2] );
	    
	    $needGold = 0;
	    for ( $i = 0; $i < $num; $i++ )
	    {
	        $needGold += $baseGold + ($buyNum + $i) * $deltGold;
	    }
	    
	    if( FALSE == $userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_BUY_DESTINY_ATK_NUM) )
	    {
	        throw new FakeException('sub gold failed.');
		}
		
		$destinyUser->addCanDefeatNum($num);
		$destinyUser->addBuyAtkNum($num);
		$userObj->update();
		MyACopy::getInstance($uid)->save();
		
		return 'ok';
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */