<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CopyUtil.class.php 255128 2016-08-08 10:46:51Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ncopy/CopyUtil.class.php $
 * @author $Author: GuohaoZheng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-08 10:46:51 +0000 (Mon, 08 Aug 2016) $
 * @version $Revision: 255128 $
 * @brief 
 *  
 **/
class CopyUtil
{
	/**
	 * 获取副本的类型（普通，精英，活动）
	 * @param int $copy_id
	 * @throws Exception
	 *
	 */
	public static function getTypeofCopy($copyId)
	{
		if(isset(btstore_get()->COPY[$copyId]))
		{
			return CopyType::NORMAL;
		}
		else if(isset(btstore_get()->ELITECOPY[$copyId]))
		{
			return CopyType::ELITE;
		}
		else if(isset(btstore_get()->ACTIVITYCOPY[$copyId]))
		{
			return CopyType::ACTIVITY;
		}
		return CopyType::INVALIDTYPE;
	}

	/**
	 * 领取据点通关奖励
	 * @param $baseId
	 * @param $baseLv
	 * @param $uid
	 * @return array
	 * [
	 *     exp=>int
	 *     silver=>int
	 *     soul=>int
	 *     item=>array
	 *     [
	 *         itemId
	 *     ]
	 * ]
	 * for example:
	 * array(4) 
	 * {
     *    ["exp"]=>
     *    int(800)
     *    ["silver"]=>
     *    int(120)
     *    ["soul"]=>
     *    int(60)
     *    ["item"]=>
     *    array(2) 
     *    {
     *          [0]=>
     *          int(1030226)
     *          [1]=>
     *          int(1030227)
     *    }
     *}
	 */
	public static function getBasePassAward($baseId,$baseLv,$uid=0)
	{
		$reward = array();
		if(empty($uid))
		{
		    $uid = RPCContext::getInstance()->getUid();
		}
		$userObj = EnUser::getUserObj($uid);
		$lvName  = CopyConf::$BASE_LEVEL_INDEX[$baseLv];
		$exp = btstore_get()->BASE[$baseId][$lvName][$lvName.'_reward_exp'];
		$reward['exp'] = intval($userObj->getLevel() * $exp);
		$reward['silver'] = btstore_get()->BASE[$baseId][$lvName][$lvName.'_reward_silver'];
		$reward['soul'] = btstore_get()->BASE[$baseId][$lvName][$lvName.'_reward_soul'];
		$reward['item'] = CopyUtil::dropItembyBasePass($baseId, $baseLv);
		$copyId = btstore_get()->BASE[$baseId]['copyid'];
		if(CopyUtil::getTypeofCopy($copyId) != CopyType::NORMAL)
		{
		    return $reward;
		}
		$wealConf = EnWeal::getWeal(WealDef::NCOPY_FUND);
		if(empty($wealConf) || (is_array($wealConf) == FALSE))
		{
		    return $reward;
		}
	    foreach($wealConf as $type => $value)
	    {
	        $addRatio = $value/UNIT_BASE - 1;
	        if($addRatio < 0 || ($addRatio > 10))
	        {
	            Logger::warning('wealconf for ncopy_fund error.%s.',$wealConf);
	            continue;
	        }
	        switch($type)
	        {
	            case WealDef::NCOPY_FUND_EXP:
	                $reward['exp'] += intval($userObj->getLevel() * $exp * ($addRatio));
	                break;
	            case WealDef::NCOPY_FUND_SILVER:
	                $reward['silver'] += intval(btstore_get()->BASE[$baseId][$lvName][$lvName.'_reward_silver'] * ($addRatio));
	                break;
	            case WealDef::NCOPY_FUND_SOUL:
	                $reward['soul'] += intval(btstore_get()->BASE[$baseId][$lvName][$lvName.'_reward_soul'] * ($addRatio));
	                break;
	        }
	    }
		return $reward;
	}
	
	/**
	 * 
	 * @param int $baseId
	 * @param int $baseLv
	 * @param int $uid
	 * @return array
	 * [
	 *     exp=>int
	 *     silver=>int
	 *     soul=>int
	 *     item=>array
	 *     [
	 *         itemTmplId=>itemNum
	 *     ]
	 * ]
	 * 	 for example:     
	 *     ["exp"]=>
     *     int(800)
     *     ["silver"]=>
     *     int(120)
     *     ["soul"]=>
     *     int(60)
     *     ["item"]=>
     *     array(2) 
     *     {
     *        [40021]=>
     *        int(1)
     *        [60002]=>
     *        int(1)
     *     }
	 */
	public static function getBasePassRewardWithItemTmpl($baseId,$baseLv,$uid)
	{
	    $reward = array();
	    $userObj = EnUser::getUserObj($uid);
	    $lvName  = CopyConf::$BASE_LEVEL_INDEX[$baseLv];
	    $exp = btstore_get()->BASE[$baseId][$lvName][$lvName.'_reward_exp'];
	    $reward['exp'] = intval($userObj->getLevel() * $exp);
	    $reward['silver'] = btstore_get()->BASE[$baseId][$lvName][$lvName.'_reward_silver'];
	    $reward['soul'] = btstore_get()->BASE[$baseId][$lvName][$lvName.'_reward_soul'];
	    $reward['item'] = CopyUtil::dropItemTmplByBasePass($baseId, $baseLv);
	    $copyId = btstore_get()->BASE[$baseId]['copyid'];
	    if(CopyUtil::getTypeofCopy($copyId) != CopyType::NORMAL)
	    {
	        return $reward;
	    }
	    $wealConf = EnWeal::getWeal(WealDef::NCOPY_FUND);
	    if(empty($wealConf) || (is_array($wealConf) == FALSE))
	    {
	        return $reward;
	    }
	    foreach($wealConf as $type => $value)
	    {
	        $addRatio = $value/UNIT_BASE - 1;
	        if($addRatio < 0 || ($addRatio > 10))
	        {
	            Logger::warning('wealconf for ncopy_fund error.%s.',$wealConf);
	            continue;
	        }
	        switch($type)
	        {
	            case WealDef::NCOPY_FUND_EXP:
	                $reward['exp'] += intval($userObj->getLevel() * $exp * ($addRatio));
	                break;
	            case WealDef::NCOPY_FUND_SILVER:
	                $reward['silver'] += intval(btstore_get()->BASE[$baseId][$lvName][$lvName.'_reward_silver'] * ($addRatio));
	                break;
	            case WealDef::NCOPY_FUND_SOUL:
	                $reward['soul'] += intval(btstore_get()->BASE[$baseId][$lvName][$lvName.'_reward_soul'] * ($addRatio));
	                break;
	        }
	    }
	    return $reward;
	}
	/**
	 * 获取战胜某个部队的条件
	 * @param int $armyId
	 * @return $ret 结构如下：
	 * {
	 * 	'team1'=>
	 * 	'team2'=>
	 *	'attackround'=>
	 *	'defendround'=>
	 * }
	 */
	public static function getVictoryConditions($armyId)
	{
		$ret = array();
		if(isset(btstore_get()->ARMY[$armyId]['end_condition']))
		{
			$ret = btstore_get()->ARMY[$armyId]['end_condition']->toArray();
		}				
		return $ret;
	}
	/**
	 * 合并副本、据点通关奖励
	 * @param array $reward
	 * <code>
	 * array
	 * {
	 * 	//据点通关奖励
	 * 	'base'=>
	 * 		array
	 * 		{
	 * 			'exp'=>,'silver'=>,'soul'=>,
	 * 			'item'=>array(int)
	 * 		}
	 *  //副本通关奖励
	 *  'copy'=>
	 *  	array
	 *  	{
	 *  		'silver'=>,
	 *  		'item'=>array(i*2=>itemid,i*2-1=>item_num)副本奖励中的item_id是物品模板id
	 *  	}
	 * }
	 * </code>
	 */
	public static function mergeRewardofBaseandCopy($reward_copy,$reward_base)
	{
		$reward = array('soul'=>0,'silver'=>0,'exp'=>0);
		if(isset($reward_base['soul']))
		{
			$reward['soul'] = intval($reward_base['soul']);
		}
		if(isset($reward_copy['soul']))
		{
			$reward['soul'] += intval($reward_copy['soul']);
		}
		if(isset($reward_base['silver']))
		{
			$reward['silver'] = intval($reward_base['silver']);
		}
		if(isset($reward_copy['silver']))
		{
			$reward['silver']+= intval($reward_copy['silver']);
		}
		if(isset($reward_base['exp']))
		{
			$reward['exp'] = intval($reward_base['exp']);
		}
		if(isset($reward_copy['exp']))
		{
			$reward['exp'] += intval($reward_copy['exp']);
		}
		//据点通关奖励物品数组
		$item_ids = array();
		if(isset($reward_base['item']))
		{
			$item_ids = $reward_base['item'];
		}
		//将副本通关奖励中的物品加到据点通关奖励物品数组中
		if(isset($reward_copy['item']))
		{
			$copy_item_num = count($reward_copy['item']);
			for( $i=0; $i<$copy_item_num; $i++ )
			{
				//将item加入到ItemManager中
				$item_ids = array_merge($item_ids,
						ItemManager::getInstance()->addItem(intval($reward_copy['item'][$i][0]),
								intval($reward_copy['item'][$i][1])));
			}
		}
		if(!empty($item_ids))
		{			
			$reward['item'] = $item_ids;
		}
		//将0,array()值
		foreach($reward as $type=>$value)
		{
			if(empty($value))
			{
				unset($reward[$type]);
			}
		}		
		return $reward;
	}
	/**
	 * 给用户发放奖励       包括卡牌、物品、将魂、银两、经验、金币等
	 * 副本通关奖励   据点通关奖励  副本的箱子奖励等
	 * @param array $reward
	 * <code>
	 * array
	 * {
	 * 	'soul'=>,
	 *  'silver'=>,
	 *  'exp'=>,
	 * 	'gold'=>,
	 *  'stamina'=>
	 *  'execution'=>
	 * 	'item'=>array  物品id是唯一的，已经确定好的item_id
	 *         [
	 *             itemId
	 *         ]
	 * 	'hero'=>array
	 *         [
	 *             array
	 *             [
	 *                 htid:
	 *                 mstId:optional
	 *                 level:
	 *             ]
	 *         ]
	 * }
	 * </code>
	 * @return array
	 */
	public static function rewardUser($initreward,$withItemTmpl=FALSE,$uid=0)
	{
        $reward = $initreward;
        if(empty($uid))
        {
            $uid = RPCContext::getInstance()->getUid();
        }
		$user = Enuser::getUserObj($uid);
		//添加将魂
		if(isset($reward['soul']) && ($reward['soul'] > 0))
		{
			$user->addSoul($reward['soul']);
		}
		//银两加奖励
		if(isset($reward['silver'])&& ($reward['silver'] > 0))
		{
			$user->addSilver($reward['silver']);
		}
		//经验奖励
		if(isset($reward['exp'])&& ($reward['exp'] > 0))
		{
			$reward['exp'] = $user->addExp($reward['exp']);
		}
		//金币奖励
		if(isset($reward['gold'])&& ($reward['gold'] > 0))
		{
			$type = StatisticsDef::ST_FUNCKEY_COPY_GETPRIZE;
			$user->addGold($reward['gold'], $type);
		}
		if(isset($reward['item']) && (!empty($reward['item'])))
		{
		    if($withItemTmpl == FALSE)
		    {
		        //据点通关奖励物品数组
		        $item_ids = $reward['item'];
		        foreach($item_ids as $index => $itemId)
		        {
		            $itemObj = ItemManager::getInstance()->getItem($itemId);
		            $items[$index] = array(
		                    ItemDef::ITEM_SQL_ITEM_ID => $itemId,
		                    ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID => $itemObj->getItemTemplateID(),
		                    ItemDef::ITEM_SQL_ITEM_NUM => $itemObj->getItemNum()
		                    );
		        }
		    }
			else
			{
			    $arrItemTmplId = $reward['item'];
			    $item_ids = ItemManager::getInstance()->addItems($arrItemTmplId);
			    foreach ( $arrItemTmplId as $itemTplId => $itemNum )
			    {
			        $items[] = array(
			                ItemDef::ITEM_SQL_ITEM_ID => $itemTplId,
			                ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID => $itemTplId,
			                ItemDef::ITEM_SQL_ITEM_NUM => $itemNum
			        );
			    }
			}
			self::addItem2Bag($item_ids,$uid);
			$reward['item'] = $items;
		}
		if(isset($reward['hero']))
		{
			$rewardHero    =    array();
			$heroMng    =    EnUser::getUserObj()->getHeroManager();
			foreach($reward['hero'] as $index=>$htidInfo)
			{
			    $heroLv = 1;
			    if(isset($htidInfo['level']))
			    {
			        $heroLv = $htidInfo['level'];
			    }
			    $ret    =    $heroMng->addNewHeroWithLv($htidInfo['htid'], $heroLv);
			    if(isset($htidInfo['mstId']))
			    {
			        $rewardHero[]=array('mstId'=>$htidInfo['mstId'],'htid'=>$htidInfo['htid']);
			    }
			    else
			    {
			        $rewardHero[]=array('htid'=>$htidInfo['htid']);
			    }
			}
			unset($reward['hero']);
			$reward['hero']=$rewardHero;
		}
		//耐力
		if(isset($reward['stamina'])&& ($reward['stamina'] > 0))
		{
			$user->addStamina($reward['stamina']);
		}	
		//行动力奖励
		if(isset($reward['execution'])&& ($reward['execution'] > 0))
		{
			$user->addExecution($reward['execution']);
		}
		//试炼币
		if(isset($reward['tower_num'])&& ($reward['tower_num'] > 0))
		{
		    $user->addTowerNum($reward['tower_num']);
		}
		Logger::trace('copyUtil.reward %s.',$reward);
		return $reward;
	}
	
	/**
	 * 武将在掉落时候，转化为将魂的数量
	 * 
	 * @param number $htid		武将模板id
	 * @param number $level		武将等级，默认1
	 * @return number
	 */
	public static function getHero2SoulNum($htid, $level = 1)
	{
		$soul = 0;
		
		// 本身的将魂
		$soul += intval(Creature::getCreatureConf($htid, CreatureAttr::SOUL));
		
		// 根据不同的等级计算升级的将魂
		$expTblId	= Creature::getCreatureConf($htid, CreatureAttr::EXP_ID);
		$expTbl		= btstore_get()->EXP_TBL[$expTblId]->toArray();
		$soul += intval($expTbl[$level]);
		
		return $soul;
	}

	public static function addItem2Bag($item_ids,$uid)
	{
		$bag = BagManager::getInstance()->getBag($uid);
		// 需要返回给前端的所有掉落物品详细信息
		$itemArr = array();
		if(empty($item_ids))
		{
			return array('item'=>$itemArr);
		}		
		//将奖励的物品逐个塞入背包中
		foreach($item_ids as $item_id)
		{		
			// 先获取数据信息，保存。
			$itemTmp = ItemManager::getInstance()->itemInfo($item_id);
			if(empty($itemTmp))
			{
				throw new FakeException('no such item with itemid %s.',$item_id);
			}	
			// 塞一个货到背包里，可以使用临时背包
			if ($bag->addItem($item_id, TRUE) == FALSE)
			{
				throw new FakeException('fail to add item %s to bag.',$item_id);
			}
			else
			{				
				// 保留物品详细信息，传给前端
				$itemArr[] = $itemTmp;
			}			
		}				
		return array('item'=>$itemArr);
	}
	
	public static function isArmyinBase($baseId,$level,$armyId)
	{
		//如果据点难度是npc
		if($level == BaseLevel::NPC)
		{
			$baseArmies = btstore_get()->BASE[$baseId]['npc']['npc_army_arrays'];
		}
		else
		{
			$lvName = CopyConf::$BASE_LEVEL_INDEX[$level];
			$baseArmies = btstore_get()->BASE[$baseId][$lvName][$lvName.'_army_arrays'];
		}
		foreach($baseArmies as $army)
		{
			if($army == $armyId)
			{
				return true;
			}
		}
		return false;
	}
	/**
	 * 触发事件：战斗失败后点击返回按钮或者战斗成功后点击任意区域
	 * @param int $copy_id
	 * @param int $baseId
	 * @param int $level
	 */
	public static function leaveBaseLevel($copyId,$baseId,$baseLv)
	{
		$uid = RPCContext::getInstance()->getUid();
		//清除session中的信息
		RPCContext::getInstance()->unsetSession(CopySessionName::ATTACKINFO);
		//清除memcache中的信息
		AtkInfo::delMcAttackInfo($uid);
	}

	/**
	 * 判断当前是否处于战斗冷却时间  是否能战斗
	 * @param int $uid
	 * @return boolean
	 */
	public static function checkFightCdTime()
	{
		if(Util::getTime() < Enuser::getUserObj()->getFightCdTime())
		{
			Logger::debug('not cool down yet,can not fight');
			return FALSE;
		}
		return TRUE;
	}
	/**
	 * 此部队在据点中的前置部队是否已经击败
	 * @param array $atkInfo
	 * @param int $armyId
	 * @return boolean
	 */
	public static function checkDefeatPreArmy($armyId)
	{
		$baseId = AtkInfo::getInstance()->getBaseId();
		$baseLv = AtkInfo::getInstance()->getBaseLv();
		if(empty($baseId) || ($baseLv < 0))
		{
		    throw new FakeException('there is no atkinfo in memcache.please entercopy or enterbaselevel first.');
		}
		if(self::isFirstArmy($baseId,$baseLv,$armyId) == TRUE)
		{
			return TRUE;
		}
		$basePrg 	=	AtkInfo::getInstance()->getBasePrg();
		if(empty($basePrg))
		{
			throw new InterException('no base progress attackinfo in memcache.');
		}
		$arrArmy = array_keys($basePrg);
		$lastArmy = $arrArmy[count($arrArmy)-1];
		foreach($basePrg as $army => $armyStatus)
		{
			if($army == $armyId)
			{
			    if($armyStatus == ATK_INFO_ARMY_STATUS::DEFEAT_FAIL)
			    {
			        Logger::warning('this army %d has beed defeated fail.status %d.',$armyId,$armyStatus);
			        return FALSE;
			    }
			    else if ($armyStatus == ATK_INFO_ARMY_STATUS::NOT_DEFEAT)
			    {
			        return TRUE;
			    }
			    //此部队已经打败了
			    else
			    {
			        if($army == $lastArmy)
			        {
			            Logger::warning('this army %d has beed defeated and this army is last army of this base.army status %d.',$armyId,$armyStatus);
			            return FALSE;
			        }
			        else 
			        {
			            Logger::warning('this army %d has beed defeated,but this army is not last army,so can defeat it once more.army status %d.',$armyId,$armyStatus);
			            return TRUE;
			        }
			    }
			}
			else if($armyStatus == ATK_INFO_ARMY_STATUS::DEFEAT_FAIL || 
			        ($armyStatus == ATK_INFO_ARMY_STATUS::NOT_DEFEAT))
			{
			    Logger::warning('this army is %d.the pre army is %d is not defeated.',$armyId,$army);
				return FALSE;
			}
		}
		return FALSE;
	}
	/**
	 * 判断一个部队是否是一个据点的第一个部队
	 * @param int $baseId
	 * @param int $armyId
	 */
	public static function isFirstArmy($baseId,$baseLv,$armyId)
	{
		$lvName = CopyConf::$BASE_LEVEL_INDEX[$baseLv];
		$armies = btstore_get()->BASE[$baseId][$lvName][$lvName.'_army_arrays'];
		Logger::debug('check army:%s is first army of base %s,armys:%s.',$armyId,$baseId,$armies);
		if(intval($armies[0]) == $armyId)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 查看玩家的阵型是否能够战斗 如果阵型中的所有卡牌的血量都为0  不能战斗
	 * @param array $battleInfo
	 */
	public static function checkFormation($fmt)
	{
		foreach($fmt as $pos=>$heroInfo)
		{
		    if(isset($heroInfo[PropertyKey::CURR_HP]) && ($heroInfo[PropertyKey::CURR_HP] > 0))
		    {
		        return TRUE;
		    }
		    if(!isset($heroInfo[PropertyKey::CURR_HP]) && ($heroInfo[PropertyKey::MAX_HP] > 0))
		    {
		        return TRUE;
		    }
		}
		return FALSE;		
	}

	/**
	 * 获取据点某个难度级别的攻略信息
	 * @param int $baseId
	 * @param int $level
	 */
	public static function getReplayList($baseId,$baseLv)
	{
		if(!isset(btstore_get()->BASE[$baseId]))
		{
			throw new ConfigException('no thus base %s.',$baseId);
		}
		if(!isset(btstore_get()->BASE[$baseId][CopyConf::$BASE_LEVEL_INDEX[$baseLv]]))
		{
			throw new ConfigException('this base %s have no thus level %s.',$baseId,CopyConf::$BASE_LEVEL_INDEX[$baseLv]);
		}
		$rpList = NCopyDao::getReplayList($baseId, $baseLv);
		return $rpList;
	}
	/**
	 * 获取前十通关某据点难度的玩家以及攻略
	 * @param int $baseId
	 * @param int $level
	 */
	public static function getPreBaseAttackPlayer($baseId,$baseLv)
	{
		if(!isset(btstore_get()->BASE[$baseId]))
		{
			throw new ConfigException('no thus base %s.',$baseId);
		}
		if(!isset(btstore_get()->BASE[$baseId][CopyConf::$BASE_LEVEL_INDEX[$baseLv]]))
		{
			throw new ConfigException('this base %s have no thus level %s.',$baseId,CopyConf::$BASE_LEVEL_INDEX[$baseLv]);
		}
		$ret = NCopyDao::getPreBaseAttackPlayers($baseId, $baseLv);
		return $ret;
	}
	/**
	 * 获取前十通关某副本的玩家信息
	 * @param int $copy_id
	 */
	public static function getPreCopyPassPlayer($copyId)
	{
		if(!isset(btstore_get()->COPY[$copyId]))
		{
			throw new FakeException('no this copy %s',$copyId);
		}
		$ret = NCopyDao::getPreCopyPassPlayers($copyId);
		return $ret;
	}

	/**
	 * 获取玩家、敌方的战斗属性
	 * @param int $armyId
	 * @param boolean $isnpc
	 * @return
	 */
	public static function getBattleArr($armyId,$fmt,$baseLv,$uid,
	        $isnpc=false,$herolist=NULL,
	        $arrMonsterLv=array(),$module=BattleType::ECOPY)
	{
		$user	= EnUser::getUserObj();
		$uid	= $user->getUid();
		if($module == BattleType::GOLD_TREE)
		{
		    $playerArr = ACopyLogic::getGoldTreeBattleInfo($uid);
		}
		else
		{
		    if($isnpc == TRUE)
		    {
		        $playerArr = EnFormation::getNpcBattleFormation($armyId, $herolist,$uid,$baseLv);
		    }
		    else
		    {
		        $playerArr	= $user->getBattleFormation($fmt);
		    }
		}
		//将血量信息加入到玩家阵型中
		if($module != BattleType::ECOPY)
		{
		    $playerFmt = AtkInfo::getInstance()->addHpInfo2Formation($playerArr['arrHero']);
		    $playerArr['arrHero'] = $playerFmt;
		}
		//敌方的阵型信息
		Logger::trace('EnFormation::getMonsterBattleFormation monster level %s',$arrMonsterLv);
		$enemyArr = EnFormation::getMonsterBattleFormation($armyId,$baseLv,$arrMonsterLv);
		return array('playerArr'=>$playerArr,'enemyArr'=>$enemyArr);
	}

	/**
	 *
	 * @param int $baseId
	 * @param int $baseLv
	 * @param int $card_id
	 */
	public static function reviveCard($baseId,$baseLv,$cardId)
	{
		$can = self::canRevive($baseId,$baseLv,$cardId);
		if($can['can'] != 'ok')
		{
			Logger::debug('can not revive card.the reason is %s.',$can);
			return $can['can'];
		}
		Enuser::getUserObj()->update();
		AtkInfo::getInstance()->addReviveNum();
		AtkInfo::getInstance()->setToMaxHp($cardId);
		AtkInfo::getInstance()->saveAtkInfo();
		return 'ok';
	}
	
	private static function canRevive($baseId,$baseLv,$cardId)
	{
		$lvName			=	CopyConf::$BASE_LEVEL_INDEX[$baseLv];
		$revive_modal	=	intval(btstore_get()->BASE[$baseId][$lvName][$lvName.'_revive_modal']);
		if($revive_modal == ReviveModal::CANNOT)
		{
		    throw new FakeException('not in revive modal');
		}
		//卡牌的血量是否为0
		$atkInfo = AtkInfo::getInstance()->getAtkInfo();
		if(empty($atkInfo))
		{
		    throw new FakeException('no atkinfo in session.');
		}
		if(!isset($atkInfo[ATK_INFO_FIELDS::CARDINFO][ATK_INFO_FIELDS::CARDINFO_HP_FIELD][$cardId]))
		{
		    throw new FakeException('no this hero %d.',$cardId);
		}
		if($atkInfo[ATK_INFO_FIELDS::CARDINFO][ATK_INFO_FIELDS::CARDINFO_HP_FIELD][$cardId][ATK_INFO_FIELDS::CARDINFO_CUR_HP] > 0)
		{
		    throw new FakeException('this card %d is not dead.',$cardId);
		}        
		$reviveNum = AtkInfo::getInstance()->getReviveNum() + 1;
		$reviveSpend = ($reviveNum-1) * CopyConf::$REVIVE_SPEND_INC + CopyConf::$REVIVE_SPEND;
		$userObj = EnUser::getUserObj();
		if($userObj->subSilver($reviveSpend) === FALSE)
		{
		    Logger::warning('reviveCard subSilver failed.need %s have %s.',$reviveSpend,$userObj->getSilver());
			return array('can'=>'silver');
		}
		return array('can'=>'ok','spend'=>$reviveSpend);
	}
	
	/**
	 * 据点通关掉落物品
	 * @param int $baseId
	 * @param int $baseLv
	 */
	public static function dropItembyBasePass($baseId,$baseLv)
	{
		$lvName = CopyConf::$BASE_LEVEL_INDEX[$baseLv];
		$drop_tbl_ids = btstore_get()->BASE[$baseId][$lvName][$lvName.'_droptbl_ids']->toArray();
		$item_ids = ItemManager::getInstance()->dropItems($drop_tbl_ids);
		return $item_ids;
	}
	/**
	 * 据点通关掉落物品
	 * @param int $baseId
	 * @param int $baseLv
	 * @return array
	 * [
	 *     itemTmplId=>itemNum
	 * ]
	 */
	public static function dropItemTmplByBasePass($baseId,$baseLv)
	{
	    $lvName = CopyConf::$BASE_LEVEL_INDEX[$baseLv];
	    $arrDropTblIds = btstore_get()->BASE[$baseId][$lvName][$lvName.'_droptbl_ids']->toArray();
	    $arrDropItemTmpl = array();
	    foreach($arrDropTblIds as $dropId)
	    {
	        $arrDrop = Drop::dropItem($dropId);
	        foreach($arrDrop as $itemTmpl => $itemNum)
	        {
                if(!isset($arrDropItemTmpl[$itemTmpl]))
                {
                    $arrDropItemTmpl[$itemTmpl] = 0;
                }
                $arrDropItemTmpl[$itemTmpl] += $itemNum;
	        }
	    }
	    return $arrDropItemTmpl;
	}
	
	/**
	 * 重新再战   点击“重头再战按钮”时，重新进入到该据点并遭遇第一波怪物卡牌。
	 * @param int $copy_id
	 * @param int $baseId
	 * @param int $level
	 */
	public static function reFight($copyId,$baseId,$level)
	{
		AtkInfo::getInstance()->clearAtkInfoOnRefight();
		AtkInfo::getInstance()->saveAtkInfo();
		return 'ok';
	}
	/**
	 * 判断一个部队是否是一个据点的最后一个部队
	 * @param int $baseId
	 * @param int $baseLv
	 * @param int $armyId
	 * @return boolean
	 */
	public static function isLastArmyofBase($baseId,$baseLv,$armyId)
	{		
		$lvName = CopyConf::$BASE_LEVEL_INDEX[$baseLv];
		$armies = btstore_get()->BASE[$baseId][$lvName][$lvName.'_army_arrays'];
		if($armies[count($armies)-1] == $armyId)
		{
			return true;
		}
		return false;
	}
	
	public static function getLastArmyofBase($baseId,$baseLv)
	{
		$lvName = CopyConf::$BASE_LEVEL_INDEX[$baseLv];
		$armies = btstore_get()->BASE[$baseId][$lvName][$lvName.'_army_arrays'];
		if(empty($armies))
		{
			throw new ConfigException('no armies in base %s,level %s.',$baseId,$baseLv);
		}
		return $armies[count($armies)-1];
	}
	/**
	 * 
	 * @param int $armyId
	 * @param int $btType 战斗类型   如普通副本  精英副本  活动副本 爬塔等  在BattleType类中有定义
	 * @param int $baseId
	 */
	public static function getExtraBtInfo($armyId,$btType,$baseId=false)
	{
		$musicId	=	0;
		if(!empty(btstore_get()->ARMY[$armyId]['music_id']))
		{
			$musicId	=	intval(btstore_get()->ARMY[$armyId]['music_id']);
		}
		else if(!empty($baseId)&&(!empty(btstore_get()->BASE[$baseId]['music_id'])))
		{
			$musicId	=	intval(btstore_get()->BASE[$armyId]['music_id']);
		}
		$bgId    =    0;
		if(!empty($baseId) && (!empty(btstore_get()->BASE[$baseId]['background_id'])))
		{
		    $bgId    =    intval(btstore_get()->BASE[$baseId]['background_id']);
		}
		return array('bgid' => $bgId,
				'musicId' => $musicId,
				'type' => $btType);
	}
	
	public static function dropHeroOnDefeatMst($mstId, $addHero = TRUE)
	{
	    $htidInfo    =    EnBattle::dropHero($mstId);
	    if($addHero && !empty($htidInfo))
	    {
	        Enuser::getUserObj()->getHeroManager()->addNewHeroWithLv($htidInfo['htid'], $htidInfo['level']);
	    }
	    return $htidInfo;
	}
	
	public static function dropHeroOnDefeatArmy($armyId, $addHero = TRUE)
	{
	    $htidInfos    =    array();
	    $teamID    =    intval(btstore_get()->ARMY[$armyId]['teamid']);
	    $fmt    =    btstore_get()->TEAM[$teamID]['fmt'];
	    foreach($fmt as $pos => $mstId)
	    {
	        if(empty($mstId))
	        {
	            continue;
	        }
	        $htidInfo    =    self::dropHeroOnDefeatMst($mstId, $addHero);
	        if(empty($htidInfo))
	        {
	            continue;
	        }
	        $htidInfos[]    =    $htidInfo;
	    }
	    return $htidInfos;
	}
	/**
	 * 
	 * @param unknown_type $baseId
	 * @param unknown_type $baseLv
	 * @return array
	 * [
	 *     htid=>num
	 * ]
	 */
	public static function dropHeroOnDefeatBaseLv($baseId,$baseLv, $addHero = TRUE)
	{
	    $htidInfos    =    array();
	    $lvName    =    CopyConf::$BASE_LEVEL_INDEX[$baseLv];
	    $armies    =    btstore_get()->BASE[$baseId][$lvName][$lvName.'_army_arrays'];
	    foreach($armies as $armyId)
	    {
	        $dropHtidInfo    =    self::dropHeroOnDefeatArmy($armyId, $addHero);
	        $htidInfos    =    array_merge($htidInfos,$dropHtidInfo);
	    }
	    return $htidInfos;
	    
	    $htidWithNum = array();
	    foreach($htidInfos as $index => $htid)
	    {
	        if(!isset($htidWithNum[$htid]))
	        {
	            $htidWithNum[$htid] = 0;
	        }
	        $htidWithNum[$htid]++;
	    }
	    return $htidWithNum;
	}
	
	/**
	 * 根据品质过滤掉落的武将，将武将转化为将魂
	 * 
	 * @param array $arrDropHeroInfo		掉落的所有武将
	 * @param array $arrQuality				需要过滤的品质
	 * @return array
	 */
	public static function dropHeroByQualityFilter($arrDropHeroInfo, $arrQuality)
	{
		$userObj = EnUser::getUserObj();
		
		$needLevel = intval(btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_DROP_HERO_2_SOUL_NEED_LEVEL]);
		$addSoul = 0;
		if ($userObj->getLevel() >= $needLevel)
		{
			foreach ($arrDropHeroInfo as $index => $aDropHeroInfo)
			{
				$aHtid = $aDropHeroInfo['htid'];
				$aLevel = $aDropHeroInfo['level'];
				$color = Creature::getHeroConf($aHtid, CreatureAttr::QUALITY);
				if (in_array($color, $arrQuality))
				{
					$soul = CopyUtil::getHero2SoulNum($aHtid, $aLevel);
					$addSoul += $soul;
					unset($arrDropHeroInfo[$index]);
					Logger::debug("dropHeroByQualityFilter, htid[%d], level[%d], soul[%d], all addSoul[%d]", $aHtid, $aLevel, $soul, $addSoul);
				}
			}
		}
		
		return array($arrDropHeroInfo, $addSoul);
	}
	
	public static function isNormalBasePassed($copyId,$baseId,$copyInfo)
	{
	    if(empty($copyInfo))
	    {
	        return FALSE;
	    }
	    if(count($copyInfo) == 1)
	    {
	        return TRUE;
	    }
	    $progress    =    $copyInfo['va_copy_info']['progress'];
	    if(!isset($progress[$baseId]) || ($progress[$baseId] < BaseStatus::SIMPLEPASS))
	    {
	        return FALSE;
	    }
	    return TRUE;
	}
	public static function isEliteBasePassed($copyId,$baseId,$ecopyInfo)
	{
	    if(empty($ecopyInfo))
	    {
	        return FALSE;
	    }
	    $progress    =    $ecopyInfo['va_copy_info']['progress'];
	    if(!isset($progress[$copyId]) || ($progress[$copyId] < EliteCopyStatus::PASS))
	    {
	        return FALSE;
	    }
	    return TRUE;
	}
	
	public static function isArrBasePassed($arrBase)
	{
	    $ecopyInfo = array();
	    $baseInfo = array();
	    foreach($arrBase as $index => $baseId)
	    {
	        $baseInfo[$baseId] = 0;
	        $copyId    =    btstore_get()->BASE[$baseId]['copyid'];
	        $copyType    =    CopyUtil::getTypeofCopy($copyId);
	        switch($copyType)
	        {
	            case CopyType::NORMAL:
	                if(MyNCopy::getInstance()->isCopyPassed($copyId) == TRUE)
	                {
	                    $baseInfo[$baseId] = 1;
	                }
	                else
	                {
	                    $copyInfo = MyNCopy::getInstance()->getCopyInfo($copyId);
	                    if(self::isNormalBasePassed($copyId, $baseId, $copyInfo) == TRUE)
	                    {
	                        $baseInfo[$baseId] = 1;
	                    }
	                }
	                break;
	            case CopyType::ELITE:
	                if(EnSwitch::isSwitchOpen(SwitchDef::ELITECOPY) && (empty($ecopyInfo)))
	                {
	                    $ecopyInfo    =    MyECopy::getInstance()->getEliteCopyInfo();
	                }
	                if(self::isEliteBasePassed($copyId, $baseId, $ecopyInfo) == TRUE)
	                {
	                    $baseInfo[$baseId] = 1;
	                }
	                break;
	        }
	    }
	    return $baseInfo;
	}
	public static function isLastBaseOfCopy($baseId)
	{
	    $copyId = btstore_get()->BASE[$baseId]['copyid'];
	    $baseNum = btstore_get ()->COPY [$copyId] ['base_num'];
	    $lastBase = btstore_get ()->COPY [$copyId] ['base'] [$baseNum - 1];
	    if($lastBase == $baseId)
	    {
	        return TRUE;
	    }
	    return FALSE;
	}
	
	public static function getFirstBaseOfCopy($copyId)
	{
	    return btstore_get ()->COPY [$copyId] ['base'] [0];
	}
	
	
	public static function passCondition($baseId,$baseLv,$atkRet)
	{
	    Logger::trace('passCondition %s.',$atkRet);
	    $lvName = CopyConf::$BASE_LEVEL_INDEX[$baseLv];
	    $scoredCond = btstore_get()->BASE[$baseId][$lvName.'_scored_condition'];
	    $heroStarInfo = array();
	    foreach($scoredCond as $type => $value)
	    {
	        if(empty($value))
	        {
	            continue;
	        }
	        switch($type)
	        {
	            case GET_SCORE_COND_TYPE::ROUND_NUM:
	                Logger::trace('type %s,value %s',$type,NCopyAtkInfo::getInstance()->getRoundNum());
	                if(NCopyAtkInfo::getInstance()->getRoundNum() > $value)
	                {
	                    return 'round';
	                }
	                break;
	            case GET_SCORE_COND_TYPE::COST_HP:
	                Logger::trace('type %s,value %s',$type,NCopyAtkInfo::getInstance()->getCostHp());
	                $maxHp = 0;
	                $team1 = $atkRet['team1'];
	                foreach($team1 as $index => $cardInfo)
	                {
	                    $hid = $cardInfo['hid'];
	                    $maxHp += AtkInfo::getInstance()->getMaxHpofHero($hid);
	                }
	                $costHp = NCopyAtkInfo::getInstance()->getCostHp();
	                if($costHp/$maxHp > $value/UNIT_BASE)
	                {
	                    return 'costHp';
	                }
	                break;
	            case GET_SCORE_COND_TYPE::REVIVE_NUM:
	                Logger::trace('type %s,value %s',$type,NCopyAtkInfo::getInstance()->getReviveNum());
	                if(NCopyAtkInfo::getInstance()->getReviveNum() > $value)
	                {
	                    return 'revive_num';
	                }
	                break;
	            case GET_SCORE_COND_TYPE::DEAD_NUM:
	                Logger::trace('type %s,value %s',$type,NCopyAtkInfo::getInstance()->getDeadCardNum());
	                if(NCopyAtkInfo::getInstance()->getDeadCardNum() > $value)
	                {
	                    return 'dead_num';
	                }
	                break;
	            case GET_SCORE_COND_TYPE::THREE_STAR_HERONUM:
	                if(empty($heroStarInfo))
	                {
	                    $team1 = $atkRet['team1'];
	                    $heroStarInfo = self::getStarHeroNum($team1);
	                }
	                Logger::trace('type %s,value %s',$type,$heroStarInfo['three']);
	                if($heroStarInfo['three'] < $value )
	                {
	                    return 'three_star_hero';
	                }
	                break;
	            case GET_SCORE_COND_TYPE::FOUR_STAR_HERONUM:
	                if(empty($heroStarInfo))
	                {
	                    $team1 = $atkRet['team1'];
	                    $heroStarInfo = self::getStarHeroNum($team1);
	                }
	                Logger::trace('type %s,value %s',$type,$heroStarInfo['four']);
	                if($heroStarInfo['four'] < $value )
	                {
	                    return 'four_star_hero';
	                }
	                break;
	        }
	    }
	    return 'ok';
	}
	
	
	private static function getStarHeroNum($team1)
	{
	    $heroMng = EnUser::getUserObj()->getHeroManager();
	    $ret = array('three'=>0,'four'=>0);
	    foreach($team1 as $index => $cardInfo)
	    {
	        $hid = $cardInfo['hid'];
	        $heroObj = $heroMng->getHeroObj($hid);
	        if(empty($heroObj))
	        {
	            throw new InterException('no such heroobj %s.',$hid);
	        }
	        $star = $heroObj->getStarLv();
	        if($star == 3)
	        {
	            $ret['three']++;
	        }
	        else if($star == 4)
	        {
	            $ret['four']++;
	        }
	    }
	    return $ret;
	}
	
	public static function getArmyInBase($baseId,$baseLv=BaseLevel::SIMPLE)
	{
	    if(!isset(CopyConf::$BASE_LEVEL_INDEX[$baseLv]))
	    {
	        return array();
	    }
	    $lvName = CopyConf::$BASE_LEVEL_INDEX[$baseLv];
	    $arrArmy = btstore_get()->BASE[$baseId][$lvName][$lvName.'_army_arrays'];
	    return $arrArmy;
	}
	
	public static function isNCopyPassed($copyInfo)
	{
	    $base_num = btstore_get ()->COPY [$copyInfo['copy_id']] ['base_num'];
	    $last_base = btstore_get ()->COPY [$copyInfo['copy_id']] ['base'] [$base_num - 1];
	    $progress = $copyInfo ['va_copy_info'] ['progress'];
	    if (isset ( $progress [$last_base] ) && ($progress [$last_base] >= BaseStatus::SIMPLEPASS))
	    {
	        return TRUE;
	    }
	    return FALSE;
	}
	
	public static function getBaseNeedExec($baseId,$baseLv)
	{
	    if(!isset(CopyConf::$BASE_LEVEL_INDEX[$baseLv]))
	    {
	        throw new FakeException('no such baselevel %d',$baseLv);
	    }
	    $baseLvName = CopyConf::$BASE_LEVEL_INDEX[$baseLv];
	    $needExec = btstore_get()->BASE[$baseId][$baseLvName][$baseLvName.'_need_power'];
	    return $needExec;
	}
	
	public static function getVipBuyNumLimit($type,$uid)
	{
	    $vip = EnUser::getUserObj($uid)->getVip();
	    if(!isset(btstore_get()->VIP[$vip][$type]))
	    {
	        throw new FakeException('VIP has no field %s conf is %s',$type,btstore_get()->VIP[$vip]->toArray());
	    }
	    return btstore_get()->VIP[$vip][$type][0];
	}
	
	public static function getFestivalDropReward($type)
	{
	    $dropId = EnFestival::getFestival($type);
	    if(empty($dropId))
	    {
	        return array();
	    }
	    $arrItemId = ItemManager::getInstance()->dropItems(array($dropId));
	    Logger::trace('getNCopyOrangeCardDrop drop %s',$arrItemId);
	    return $arrItemId;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */