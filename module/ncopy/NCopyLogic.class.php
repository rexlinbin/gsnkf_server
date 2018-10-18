<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NCopyLogic.class.php 258623 2016-08-26 09:13:07Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ncopy/NCopyLogic.class.php $
 * @author $Author: GuohaoZheng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-26 09:13:07 +0000 (Fri, 26 Aug 2016) $
 * @version $Revision: 258623 $
 * @brief 
 *  
 **/
class NCopyLogic
{

    private static $userCopy = array();
    private static $userCopyBuffer = array();
	public static function getCopyList()
	{
		MyNCopy::$fetchAllCopyFromDB = TRUE;
		$copyList = MyNCopy::getInstance()->getAllCopies();
		if(empty($copyList))
		{
			throw new FakeException('can not get copylist of normal type');
		}
		$uid = RPCContext::getInstance()->getUid();
		self::fixUserCopy($uid);
		//开启新副本或者更新据点攻击次数
		MyNCopy::getInstance()->save();
		NCopyLogic::saveUserCopy();
		return $copyList;
	}
	
	public static function fixUserCopy($uid)
	{
	    $userCopy = self::getUserCopy($uid);
	    $actualScore = MyNCopy::getInstance($uid)->getScore();
	    if(empty($userCopy[USER_COPY_FIELD::SCORE]) || 
	            ($userCopy[USER_COPY_FIELD::SCORE] != $actualScore))
	    {
	        Logger::info('fixUserCopy now score is %d.the actual score is %d',
	                $userCopy[USER_COPY_FIELD::SCORE],$actualScore);
	        self::setScore($actualScore,$uid);
	    }
	}
	
	public static function resetCopyInfo($copyList)
	{
	    foreach($copyList as $copyId => $copyInfo)
	    {
	        $bases = btstore_get()->COPY[$copyId]['base'];
	        $progress = $copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_PROGRESS];
	        foreach($progress as $baseId => $baseStatus)
	        {
	            if(empty($baseId))
	            {
	                continue;
	            }
	            if(!isset($copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_DEFEAT_NUM][$baseId]))
	            {
	                $copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_DEFEAT_NUM][$baseId]
	                    = intval(btstore_get()->BASE[$baseId][NORMAL_COPY_FIELD::BTSTORE_FIELD_FREE_ATK_NUM]);
	            }
	            if(!isset($copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_RESET_NUM][$baseId]))
	            {
	                $copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_RESET_NUM][$baseId]= 0;
	            }
	        }
	        $copyList[$copyId] = $copyInfo;
	    }
	    return $copyList;
	}

	private static function enterCopy($copyId)
	{
		$copyInfo = MyNCopy::getInstance()->getCopyInfo($copyId);
		if(empty($copyInfo))
		{
			throw new FakeException('can not get copyinfo of normal type with copyid:%s.',$copyId);
		}
		self::setCopySession($copyId);
		return $copyInfo;
	}
	
	public static function setCopySession($copyId)
	{
		RPCContext::getInstance()->setSession(CopySessionName::COPYID, $copyId);
	}
	
	public static function enterBaseLevel($copyId,$baseId,$baseLv)
	{
		$copyIdInSession = RPCContext::getInstance()->getSession(CopySessionName::COPYID);
		if(empty($copyIdInSession) || ($copyIdInSession!=$copyId))
		{
			self::enterCopy($copyId);
		}
		//判断btstore中是否存在此据点
		if(!isset(btstore_get()->BASE[$baseId]))
		{
			throw new FakeException('no this base with baseid:%s.',$baseId);
		}
		$lvName = CopyConf::$BASE_LEVEL_INDEX[$baseLv];
		if(!isset(btstore_get()->BASE[$baseId][$lvName]))
		{
		    throw new FakeException('no this base level with baseid:%s,baselv %s.',$baseId,$baseLv);
		}
		//判断副本是否存在
		$copyObj = MyNCopy::getInstance()->getCopyObj($copyId);
		if($copyObj == NULL)
		{
			throw new FakeException('no this copy with copyid:%s.',$copyId);
		}	
		$copyStatus = $copyObj->getStatusofBase($baseId);
		if($copyStatus == -1)
		{
		    throw new FakeException('this base with baseid:%s is not open.',$baseId);
		}
		else if($copyStatus == BaseStatus::CANSHOW)
		{
		    throw new FakeException('this base with baseid:%s is in show status. can not be attack.',$baseId);
		}	
		//判断是否可以进入此据点、此据点难度		
		$canEnter = $copyObj->canEnterBaseLevel($baseId,$baseLv);
		if($canEnter != 'ok')
		{
			throw new FakeException('can not enter this base_level:copyid %s,base_id %s level %s.reason:%s.',
			        $copyId,$baseId,$baseLv,$canEnter);
		}
		//判断攻击此据点需要的体力是否足够
		$need_power = intval(btstore_get()->BASE[$baseId][$lvName][$lvName.'_need_power']);
		$user = Enuser::getUserObj();
		if($need_power > $user->getCurExecution())
		{
// 		    Logger::warning('enterBaseLevel has not enough execution.');
		    return 'execution';
		}
		//如果能够进入据点的此难度级别进行攻击  初始化attackinfo 并写入session中
		AtkInfo::getInstance()->initAtkInfo($copyId, $baseId, $baseLv);
		AtkInfo::getInstance()->saveAtkInfo();
		MyNCopy::getInstance()->save();
		return 'ok';
	}

	/**
	 * 攻击某个部队
	 * @param int $copyId
	 * @param int $baseId
	 * @param int $level
	 * @param int $armyId
	 */
	public static function doBattle($copyId,$baseId,$baseLv,$armyId,$fmt,$herolist=null)
	{
		$isnpc = false;
		if($baseLv	==	BaseLevel::NPC)
		{
		    if(empty($herolist))
		    {
		        throw new FakeException('heroList can not be empty.please set it.');
		    }
			$isnpc = true;
		}
		self::canAttack($copyId, $baseId, $baseLv, $armyId);
		$ret = BaseDefeat::doBattle(BattleType::NCOPY, $armyId, $baseId, $fmt, $baseLv, $isnpc, $herolist);
		if(isset($ret['newcopyorbase']['getscore']))
		{
		    $ret['getscore'] = $ret['newcopyorbase']['getscore'];
		    unset($ret['newcopyorbase']['getscore']);
		}
		$mysRet = array();
		Logger::trace("['newcopyorbase']['pass'] %d",$ret['newcopyorbase']['pass']);
		if($ret['newcopyorbase']['pass'] == TRUE)
		{
		    if(self::randMysmerchant($copyId))
		    {
		        $uid = RPCContext::getInstance()->getUid();
		        $mysRet = EnMysMerchant::trigMysMerchant($uid);
		    }
		}
		$ret['score'] = MyNCopy::getInstance()->getCopyObj($copyId)->getScore();
		if(isset($ret['newcopyorbase']['normal']))
		{
		    $ret['newcopyorbase']['normal'] = self::resetCopyInfo($ret['newcopyorbase']['normal']);
		}
		$ret['extra']['mysmerchant'] = $mysRet;
		return $ret;
	}
	
	public static function randMysmerchant($copyId)
	{
	    $rand = rand(0, UNIT_BASE);
	    $chance = self::getOpenMymerchantChance($copyId);
	    Logger::debug('randMysmerchant rand %d chance %d',$rand,$chance);
	    if($rand > $chance)
	    {
	        return FALSE;
	    }
	    return TRUE;
	}
	
	public static function getBattleReward()
	{
	    $baseId     	= AtkInfo::getInstance()->getBaseId();
	    $baseLv  		= AtkInfo::getInstance()->getBaseLv();
	    $copyId     	= AtkInfo::getInstance()->getCopyId();
	    $copyObj     	= MyNCopy::getInstance()->getCopyObj($copyId);
	    $reward['base'] = CopyUtil::getBasePassAward($baseId, $baseLv);
	    $firstPassed	= $copyObj->firstPassBaseLevel($baseId,$baseLv);
	    $copyPassed		= $copyObj->isLastBase($baseId);
	    //如果是第一次通关了副本 领取副本通关奖励
	    $reward['copy'] = array();
	    if($copyPassed == true  && ($firstPassed == true)
	            &&($baseLv == BaseLevel::SIMPLE || ($baseLv == BaseLevel::NPC)))
	    {
	        $reward['copy']['item'] = btstore_get()->COPY[$copyId]['reward_item_ids']->toArray();
	        $reward['copy']['silver'] = btstore_get()->COPY[$copyId]['reward_silver'];
	    }
	    $reward = CopyUtil::mergeRewardofBaseandCopy($reward['copy'],$reward['base']);
	    if(isset($reward['silver']))
	    {
	        $uid = RPCContext::getInstance()->getUid();
	        $addition = EnCityWar::getCityEffect($uid, CityWarDef::NCOPY);
	        Logger::info('EnCityWar::getCityEffect act. addition is %d',$addition);
	        $reward['silver'] = intval($reward['silver'] * (1 + $addition/UNIT_BASE));
	    }
	    if(!isset($reward['item']))
	    {
	        $reward['item'] = array();
	    }
	    $reward['item'] = array_merge($reward['item'],self::getActExchangeDrop());
	    $reward['item'] = array_merge($reward['item'],self::getNCopyOrangeHeroFragDrop());
	    $reward['item'] = array_merge($reward['item'],CopyUtil::getFestivalDropReward(FestivalDef::COPY_TYPE_NORMAL));
	    $expRatio = EnWeal::getNSWeal();
	    if(isset($reward['exp']))
	    {
	        Logger::info('getBattleReward rewardexp is %d expratio %d',$reward['exp'],$expRatio);
	        $reward['exp'] = $reward['exp'] * $expRatio;
	    }
	    return $reward;
	}
	
	public static function getActExchangeDrop()
	{
	    $dropId = EnActExchange::getDrop();
	    if(empty($dropId))
	    {
	        return array();
	    }
	    $arrItemId = ItemManager::getInstance()->dropItems(array($dropId));
	    Logger::trace('getActExchangeDrop drop %s',$arrItemId);
	    return $arrItemId;
	}
	
	public static function getNCopyOrangeHeroFragDrop()
	{
	    $dropId = self::getNCopyOrangeHeroFragDropId();
	    if(empty($dropId))
	    {
	        return array();
	    }
	    $arrItemId = ItemManager::getInstance()->dropItems(array($dropId));
	    Logger::trace('getNCopyOrangeCardDrop drop %s',$arrItemId);
	    return $arrItemId;
	}
	
	public static function getNCopyOrangeHeroFragDropId()
	{
	    $uid = RPCContext::getInstance()->getUid();
	    $conf = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_ORANGE_HEROFRAG_DROP]->toArray();
	    if(count($conf) != 2)
	    {
	        throw new FakeException('config error for copyorangecarddrop.conf is %s',$conf);
	    }
	    $needLv = $conf[0];
	    if(EnUser::getUserObj($uid)->getLevel() < $needLv)
	    {
	        return 0;
	    }
	    $dropId = $conf[1];
	    return $dropId;
	}
	
	
	public static function doneBattle($atkRet)
	{
		$uid = $atkRet['uid1'];
		$pass = $atkRet['pass'];
		$fail = $atkRet['fail']; 
		$copyId = AtkInfo::getInstance()->getCopyId();
		$baseId = AtkInfo::getInstance()->getBaseId();
		$baseLv = AtkInfo::getInstance()->getBaseLv();
		$copyObj = MyNCopy::getInstance()->getCopyObj($copyId);
		$newCopyorBase	= array();
		if($fail == TRUE && ($baseLv == BaseLevel::NPC))
		{
		    Logger::warning('attak npc failed.copyid %d base id %d baselv %d.armyid %d.',$copyId,$baseId,$baseLv,$atkRet['uid2']);
		}
		$newCopyorBase['pass'] = FALSE;
		if($pass)
		{
		    if($baseLv == BaseLevel::NPC)
		    {
		        $baseLv = BaseLevel::SIMPLE;
		    }
		    $firstPass = $copyObj->firstPassBaseLevel($baseId,$baseLv);
		    $newCopyorBase['getscore'] = FALSE;
		    $newCopyorBase['pass'] = TRUE;
		    if($firstPass)
		    {
		        $passCondition = CopyUtil::passCondition($baseId, $baseLv, $atkRet);
		        if($passCondition != 'ok')
		        {
		            Logger::trace('can not get score reason %s.',$passCondition);
		        }
		        else
		        {
		            $copyObj->addPassedBase($baseId,$baseLv);
		            $copyObj->addScore(1);
		            NCopyLogic::addScore();
		            MyNCopy::getInstance()->addScore(1);
		            $newCopyorBase['getscore'] = TRUE;
		            $rpInfo = AtkInfo::getInstance()->getReplayInfo();
		            NCopyDao::addReplay($baseId, $baseLv, $rpInfo);
		            NCopyDao::addPreBaseAttackPlayer($uid, $baseId, $baseLv, $rpInfo);
		            if($baseLv == BaseLevel::SIMPLE)
		            {
		                EnSwitch::checkSwitchOnDefeatBase($baseId);
		                $newCopyorBase['normal'] = MyNCopy::getInstance()->checkOpenByBasePass($baseId);
		                $copyPassed = $copyObj->isLastBase($baseId);
		                if($copyPassed)
		                {
		                    $newCopyorBase['elite'] = MyECopy::getInstance()->checkOpenByNCopyPass($copyId);
		                    $newCopyorBase['actcopy'] = MyACopy::getInstance()->checkOpenByNCopyPass($copyId);
		                    NCopyDao::addPreCopyPassPlayer($uid, $copyId);
		                }
		            }
		        }
		        
		    }
		    $copyObj->subCanDefeatNum($baseId);
		    $copyInfo    =    $copyObj->getCopyInfo();
		    $newCopyorBase['normal'][$copyInfo['copy_id']] = $copyInfo;
		    EnActive::addTask(ActiveDef::NCOPY);
		    EnWeal::addKaPoints(KaDef::NCOPY);
		    if($copyObj->isLastBase($baseId)) 
		    	EnAchieve::updatePassNCopy($uid, $copyId);
		}
		if($pass)
		{
		    EnGuildTask::taskIt($uid, GuildTaskType::BASE, $baseId, 1);
		    EnMission::doMission($uid, MissionType::NORMAL_BASE);
			EnDesact::doDesact($uid, DesactDef::NCOPY_SUC, 1);
			EnNewServerActivity::updatePassCopy($uid, $baseId);
			EnFestivalAct::notify($uid, FestivalActDef::TASK_COPY_ANY_NUM, 1);
			EnWelcomeback::updateTask(WelcomebackDef::TASK_TYPE_NCOPY, 1);
		}
		Logger::trace('after attack base %s lv %s.newcopyorbase %s.',$baseId,$baseLv,$newCopyorBase);
		MyNCopy::getInstance()->save();
		MyECopy::saveByOtherModule();
		MyACopy::saveByOtherModule();
		EnSwitch::getSwitchObj()->save();
		EnUser::getUserObj()->update();
		BagManager::getInstance()->getBag()->update();
		AtkInfo::getInstance()->saveAtkInfo();
		NCopyLogic::saveUserCopy();
		return $newCopyorBase;		
	}
	
	/**
	 * 玩家是否能够攻击部队
	 * 1.冷却时间  2.此据点中的前置部队是否已经击败 3.阵型中是否有卡牌
	 * @param int $copyId
	 * @param int $baseId
	 * @param int $level
	 * @param int $armyId
	 */
	private static function canAttack($copyId,$baseId,$baseLv,$armyId)
	{		
		$copyIdInSession = RPCContext::getInstance()->getSession(CopySessionName::COPYID);
		if(empty($copyIdInSession))
		{
			throw new FakeException('the copyid in session is null.please enterBaseLevel first');
		}	
		if($copyIdInSession!=$copyId)
		{
		    throw new FakeException('the copyid in session is %d,but now you want to atk copy %d.what is wrong??please enterBaseLevel first',$copyIdInSession,$copyId);
		}
		$copyObj = MyNCopy::getInstance()->getCopyObj($copyId);
		if($copyObj == NULL)
		{
			throw new FakeException('no this copy with copyid:%s.',$copyId);
		}
		if($copyObj->canAttack($baseId, $baseLv) != 'ok')
		{
			throw new FakeException('can not attack this baselevel. baseid %s level %s.',$baseId,$baseLv);
		}
		if(CopyUtil::isArmyinBase($baseId, $baseLv, $armyId) == FALSE)
		{
			throw new FakeException('this army %s is not in base with baseid %s baseLevel %s.',$armyId,$baseId,$baseLv);
		}
		if(!CopyUtil::checkFightCdTime())
		{
			throw new FakeException('can not fight,fightcd %s now %s.not cool down.',EnUser::getUserObj()->getFightCdTime(),Util::getTime());
		}
		
		//防止刷号
		//RestrictUser::checkFightCdTime(RPCContext::getInstance()->getUid(), 2, 15);
		
		$atkInfo = AtkInfo::getInstance()->getAtkInfo();
		if(empty($atkInfo) || ($atkInfo[ATK_INFO_FIELDS::COPYID]!=$copyId) || ($atkInfo[ATK_INFO_FIELDS::BASEID]!=$baseId) || ($atkInfo[ATK_INFO_FIELDS::BASELEVEL]!=$baseLv))
		{
			throw new InterException('no corresponding attackinfo in session.');
		}
		if(CopyUtil::checkDefeatPreArmy($armyId) == false)
		{
			throw new FakeException('can not atk army %d in baseid %d baselevel %d',$armyId,$baseId,$baseLv);
		}
	}

	/**
	 * 获取奖励箱子,返回奖励的具体信息给前端
	 * @param int $caseID
	 * @return array $reward
	 */
	public static function getPrize($copyId,$caseID)
	{		
		$canGet = self::canGetPrize($copyId, $caseID);
		if($canGet != 'ok')
		{
			throw new FakeException('can not get prize.reason %s.',$canGet);
		}
		$copyObj = MyNCopy::getInstance()->getCopyObj($copyId);
		$copyObj -> getPrize($caseID);
		$prize_list = btstore_get ()->COPY [$copyId] ['prize'] [$caseID];
		$reward = array ('item'=>array(),'hero'=>array());
		foreach ( $prize_list as $prize )
		{ 
    		$type = intval ( $prize [0] );
    		$id = intval ( $prize [1] );
    		$num = intval ( $prize [2] );
    		switch ($type) 
    		{
    		    case CASE_REWARD_TYPE::REWARD_ITEM : //获取物品奖励
    		        $item_ids = ItemManager::getInstance ()->addItem($id,$num);
    		        $reward ['item'] = array_merge ( $reward ['item'], $item_ids );
            		break;
        		case CASE_REWARD_TYPE::REWARD_GOLD : //获取金币奖励
            		$reward ['gold'] = $num;
            		break;
        		case CASE_REWARD_TYPE::REWARD_SILVER : //获取银两奖励
            		$reward ['silver'] = $num;
            		break;
        		case CASE_REWARD_TYPE::REWARD_SOUL : //获取将魂奖励
            		$reward ['soul'] = $num;
            		break;
        		case CASE_REWARD_TYPE::REWARD_HERO : //获取卡牌奖励
            		$user = Enuser::getUserObj ();
            		$heroMng = $user->getHeroManager ();
            		$reward ['hero']    =    array_merge ( $reward ['hero'],
            		        $heroMng->addNewHeroes(array($id => $num)));
            		break;
    		}
		}
		MyNCopy::getInstance()->save();
		$reward = CopyUtil::rewardUser($reward);
		Enuser::getUserObj()->update();
		BagManager::getInstance()->getBag()->update();
		if(isset($reward['item']))
		{
		    $arrItem = array();
		    foreach($reward['item'] as $index => $itemInfo)
		    {
		        if(!isset($arrItem[$itemInfo[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID]]))
		        {
		            $arrItem[$itemInfo[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID]] = 0;
		        }
		        $arrItem[$itemInfo[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID]] += $itemInfo[ItemDef::ITEM_SQL_ITEM_NUM];
		    }
		    ChatTemplate::sendCopyBoxItem(EnUser::getUserObj()->getTemplateUserInfo(), $arrItem);
		}
		return $reward;
	}
	/**
	 * 查看此奖励箱子是否可以领取
	 * 1.没有此箱子
	 * 2.没有达成此箱子
	 * 3.此箱子已经领取
	 * @param int $copyId
	 * @param int $caseID
	 * @return string 'ok'
	 */
	private static function canGetPrize($copyId,$caseID)
	{
		$copyIdInSession = RPCContext::getInstance()->getSession(CopySessionName::COPYID);
		if(empty($copyIdInSession) || ($copyIdInSession!=$copyId))
		{
			self::enterCopy($copyId);
		}
		$copyObj = MyNCopy::getInstance()->getCopyObj($copyId);
		//判断是否能够领取奖励
		$canGet = $copyObj->canGetPrize($caseID);
		if($canGet!='ok')
		{
			throw new FakeException('can not get this prize with caseID %s of copy %s.Reason:%s.',$caseID,$copyId,$canGet);
		}
		return $canGet;
	}
	public static function getAtkInfoOnEnterGame()
	{
		$copyList = array();
		$uid = RPCContext::getInstance()->getSession('global.uid');
		$atkInfo = AtkInfo::getInstance()->getAtkInfo();
		Logger::trace('attackinfo get from mc is %s.',$atkInfo);
		if(empty($atkInfo))
		{
			return array();
		}
		
		$copyId = AtkInfo::getInstance()->getCopyId();
		
		if(!isset($atkInfo[ATK_INFO_FIELDS::STATUS]) ||
		        ($atkInfo[ATK_INFO_FIELDS::STATUS] == ATK_INFO_STATUS::PASS)||
		        ($atkInfo[ATK_INFO_FIELDS::STATUS] == ATK_INFO_STATUS::FAIL))
		{
		    return array();
		}
		
		$basePrg = AtkInfo::getInstance()->getBasePrg();
		$lastArmy = CopyUtil::getLastArmyofBase($atkInfo[ATK_INFO_FIELDS::BASEID], $atkInfo[ATK_INFO_FIELDS::BASELEVEL]);	
		//通关了据点   			  
		if(isset($basePrg[$lastArmy]) && ($basePrg[$lastArmy] > ATK_INFO_ARMY_STATUS::DEFEAT_FAIL))
		{
			Logger::trace('base passed.baseprogress is %s.',$basePrg);
			return array();
		}
		AtkInfo::getInstance()->saveAtkInfo();
		RPCContext::getInstance()->setSession(CopySessionName::COPYID, $copyId);
// 		$copyType	=	CopyUtil::getTypeofCopy($copyId);
// 		switch($copyType)
// 		{
// 			case CopyType::NORMAL:
// 				$copyList = self::getCopyList();
// 				$copyInfo = MyNCopy::getInstance()->getCopyInfo($copyId);
// 				NCopyLogic::setCopySession($copyId);
// 				RPCContext::getInstance()->setSession(CopySessionName::COPYLIST, $copyList);
// 				Logger::trace('the data get from Memcache and DB is copyid %s,copyinfo %s,copylist %s.',$copyId,$copyInfo,$copyList);
// 				break;
// 			case CopyType::ELITE:
// 				$copyList = MyECopy::getInstance()->getEliteCopyInfo();
// 				RPCContext::getInstance()->setSession(CopySessionName::ECOPYLIST, $copyList);
// 				break;
// 			case CopyType::ACTIVITY:
// 				$copyList = MyACopy::getInstance()->getActivityCopyList();
// 				RPCContext::getInstance()->setSession(CopySessionName::ACOPYLIST, $copyList);
// 				break;
// 			case CopyType::TOWER:
// 			    $copyList = MyTower::getInstance()->getTowerInfo();
// 			    RPCContext::getInstance()->setSession(TowerConf::$SESSION_TOWER_INFO, $copyList);
// 			    break;
// 			case CopyType::INVALIDTYPE:
// 				throw new FakeException('invalid copy type in atkinfo of memcache.');
// 				break;
// 		}
		return array(
		        'copylist'=>$copyList,
		        'attackinfo'=>$atkInfo,
// 		        'type'=>$copyType
		        );
	}

	
	public static function sweep($copyId,$baseId,$baseLv,$num)
	{
	    $userObj = EnUser::getUserObj();
	    if($userObj->getLevel()<CopyConf::$USER_LEVEL_CAN_SWEEP)
	    {
	        throw new FakeException('user level %s not to the sweep level %s.',$userObj->getLevel(),CopyConf::$USER_LEVEL_CAN_SWEEP);
	    }
	    if(NCopyLogic::isDuringSweepCD() == TRUE)
	    {
	        throw new FakeException('during sweep cd.can not sweep.');
	    }
	    if(BagManager::getInstance()->getBag()->isFull())
	    {
	        throw new FakeException('bag is full.can not sweep!!');
	    }
	    RPCContext::getInstance()->setSession(CopySessionName::COPYID, $copyId);
	    $copyObj    =    MyNCopy::getInstance()->getCopyObj($copyId);
	    if(empty($copyObj))
	    {
	        throw new FakeException('copy %s is null.it is not open or has some error.',$copyId);
	    }
	    if($copyObj->getStatusofBase($baseId) < ($baseLv+2))
	    {
	        throw new FakeException('base %s status is %s,can not sweep level %s.',
	                $baseId,$copyObj->getStatusofBase($baseId),$baseLv);
	    }
	    $lvName    =    CopyConf::$BASE_LEVEL_INDEX[$baseLv];
	    $needExec = intval(btstore_get()->BASE[$baseId][$lvName][$lvName.'_need_power']);
	    $needExec = $needExec * $num;
	    if(EnUser::getUserObj()->subExecution($needExec) == FALSE)
	    {
	        throw new FakeException('sweep need execution %s,has execution %s.not enough.',
	                $needExec,EnUser::getUserObj()->getCurExecution());
	    }
	    $copyObj->subCanDefeatNum($baseId,$num);
	    $reward = array();
	    $rewardRet = array();
	    $expRatio = EnWeal::getNSWeal();
	    for($i=0;$i<$num;$i++)
	    {
	        $rewardOnce = CopyUtil::getBasePassRewardWithItemTmpl($baseId, $baseLv, $userObj->getUid());
	        $extraDrop = EnActExchange::getDrop();
	        $heroFragDrop = self::getNCopyOrangeHeroFragDropId();
	        $festivalDrop = EnFestival::getFestival(FestivalDef::COPY_TYPE_NORMAL);
	        if(!empty($extraDrop))
	        {
	            $arrDrop = Drop::dropItem($extraDrop);
	            foreach($arrDrop as $itemTmpl => $itemNum)
	            {
	                if(!isset($rewardOnce['item'][$itemTmpl]))
	                {
	                    $rewardOnce['item'][$itemTmpl] = 0;
	                }
	                $rewardOnce['item'][$itemTmpl] += $itemNum;
	            }
	        }
	        if(!empty($heroFragDrop))
	        {
	            $arrDrop = Drop::dropItem($heroFragDrop);
	            foreach($arrDrop as $itemTmpl => $itemNum)
	            {
	                if(!isset($rewardOnce['item'][$itemTmpl]))
	                {
	                    $rewardOnce['item'][$itemTmpl] = 0;
	                }
	                $rewardOnce['item'][$itemTmpl] += $itemNum;
	            }
	        }
	        if(!empty($festivalDrop))
	        {
	            $arrDrop = Drop::dropItem($festivalDrop);
	            foreach($arrDrop as $itemTmpl => $itemNum)
	            {
	                if(!isset($rewardOnce['item'][$itemTmpl]))
	                {
	                    $rewardOnce['item'][$itemTmpl] = 0;
	                }
	                $rewardOnce['item'][$itemTmpl] += $itemNum;
	            }
	        }
	        if(isset($rewardOnce['silver']))
	        {
	            $addition = EnCityWar::getCityEffect($userObj->getUid(), CityWarDef::NCOPY);
	            Logger::info('EnCityWar::getCityEffect act. addition is %d',$addition);
	            $rewardOnce['silver'] = intval($rewardOnce['silver'] * (1 + $addition/UNIT_BASE));
	        }
	        $itemRet = array();
	        foreach($rewardOnce as $type => $rewardInfo)
	        {
	            if($type == 'item')
	            {
	                foreach($rewardInfo as $itemTmpl => $itemNum)
	                {
	                    if(!isset($reward['item'][$itemTmpl]))
	                    {
	                        $reward['item'][$itemTmpl] = 0;
	                    }
	                    $reward['item'][$itemTmpl] += $itemNum;
	                    $itemRet[] = array(
	                            ItemDef::ITEM_SQL_ITEM_ID => $itemTmpl,
	                            ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID => $itemTmpl,
	                            ItemDef::ITEM_SQL_ITEM_NUM => $itemNum
	                            );
	                }
	            }    
	            else
	            {
	                if(!isset($reward[$type]))
	                {
	                    $reward[$type] = 0;
	                }
	                $reward[$type] += intval($rewardInfo);
	            }
	        }
	        $rewardOnce['item'] = $itemRet;
	        //$rewardOnce['hero'] = CopyUtil::dropHeroOnDefeatBaseLv($baseId, $baseLv);
	        
	        // 掉落的白绿武将转化为将魂
	        $arrDropHeroInfo = CopyUtil::dropHeroOnDefeatBaseLv($baseId, $baseLv, FALSE);
	        list($arrDropHeroInfo, $addSoul) = CopyUtil::dropHeroByQualityFilter($arrDropHeroInfo, array(HERO_QUALITY::WHITE_HERO_QUALITY, HERO_QUALITY::GREEN_HERO_QUALITY));
	        if ($addSoul > 0)
	        {
	        	if (!isset($reward['soul']))
	        	{
	        		$reward['soul'] = 0;
	        	}
	        	$reward['soul'] += $addSoul;//用于发奖
	        	if (!isset($rewardOnce['soul']))
	        	{
	        		$rewardOnce['soul'] = 0;
	        	}
	        	$rewardOnce['soul'] += $addSoul;//用于显示
	        }
	        $htidWithNum = array();
	        foreach($arrDropHeroInfo as $index => $aDropHeroInfo)
	        {
	        	$aHtid = $aDropHeroInfo['htid'];
	        	if(!isset($htidWithNum[$aHtid]))
	        	{
	        		$htidWithNum[$aHtid] = 0;
	        	}
	        	$htidWithNum[$aHtid]++;
	        }
	        $rewardOnce['hero'] = $htidWithNum;
	        if (!isset($reward['hero'])) 
	        {
	        	$reward['hero'] = array();
	        }
	        $reward['hero'] = array_merge($reward['hero'], $arrDropHeroInfo);
	        
	        if(isset($rewardOnce['exp']))
	        {
	            $rewardOnce['exp'] = $rewardOnce['exp'] * $expRatio;
	        }
	        $rewardRet[] = $rewardOnce;
	    }
	    if(isset($reward['exp']))
	    {
	        Logger::info('sweep rewardexp %d expratio %d',$reward['exp'],$expRatio);
	        $reward['exp'] = $reward['exp'] * $expRatio;
	    }
	    CopyUtil::rewardUser($reward,TRUE,$userObj->getUid());
	    $extraReward = BaseDefeat::getExtraRewardByBaseId($baseId,$num);
	    MyNCopy::getInstance()->save();
	    $userObj->update();
	    $sweepCd = Util::getTime();
	    $noSweepCd = btstore_get()->VIP[$userObj->getVip()]['noSweepCD'];
	    $noSweepCdLv = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_SWEEP_NOCD_NEEDLV];
	    if($noSweepCd === 0
	            && $userObj->getLevel() < $noSweepCdLv)
	    {
	        $sweepCd = NCopyLogic::addSweepCD();
	        NCopyLogic::saveUserCopy();
	    }
	    BagManager::getInstance()->getBag()->update();
	    $ret['sweepcd'] = $sweepCd - Util::getTime();
	    $ret['reward'] = $rewardRet;
	    $ret['extra_reward'] = $extraReward;
	    EnActive::addTask(ActiveDef::NCOPY,$num);
	    EnWeal::addKaPoints(KaDef::NCOPY,$num);
	    $uid = $userObj->getUid();//shiyu
	    EnGuildTask::taskIt($uid, GuildTaskType::BASE, $baseId, $num);//shiyu
	    EnMission::doMission($uid, MissionType::NORMAL_BASE, $num);
		EnDesact::doDesact($uid, DesactDef::NCOPY_SUC, $num);
		EnFestivalAct::notify($uid, FestivalActDef::TASK_COPY_ANY_NUM, $num);
		EnWelcomeback::updateTask(WelcomebackDef::TASK_TYPE_NCOPY, $num);
	    BaseDefeat::sendChatOnGetReward($extraReward, $reward);
	    $mysRet = array();
	    for($i=0;$i<$num;$i++)
	    {
	        if(self::randMysmerchant($copyId))
	        {
	            $mysRet = EnMysMerchant::trigMysMerchant($userObj->getUid());
	        }
	    }
	    $ret['mysmerchant'] = $mysRet;
	    return $ret;
	}
	
	public static function addSweepCD()
	{
	    $sweepCd = Util::getTime()+CopyConf::$SWEEP_GAP_TIME;
	    $uid = RPCContext::getInstance()->getUid();
	    $userCopy = self::getUserCopy($uid);
        $userCopy[USER_COPY_FIELD::UID] = $uid;
        $userCopy[USER_COPY_FIELD::SWEEP_CD] = $sweepCd;
        self::$userCopy = $userCopy;
        return $sweepCd;
	}
	/**
	 * 在获取在session中的信息时调用   如sweep_cd
	 * user_copy中的信息copy_id,last_copy_time不在session中保存     现在只有sweep_cd在session中
	 * @param unknown_type $uid
	 * @return 
	 */
	public static function getUserCopy($uid=0)
	{
	    if(empty($uid))
	    {
	        $uid = RPCContext::getInstance()->getUid();
	    }
	    if(!empty(self::$userCopy) && (self::$userCopy[USER_COPY_FIELD::UID] == $uid))
	    {
	        return self::$userCopy;
	    }
        $userCopy = NCopyDAO::getUserCopyInfo($uid, USER_COPY_FIELD::$ALL_FIELD);
        self::$userCopy = $userCopy;
        self::$userCopyBuffer = $userCopy;
        if(empty($userCopy))
        {
            self::initUserCopy(CopyConf::$FIRST_NORMAL_COPY_ID);
        }
        self::rfrUserCopy();
	    return self::$userCopy;
	}
	
	private static function rfrUserCopy()
	{
	    $lastRfrTime = self::$userCopy[USER_COPY_FIELD::LAST_RFRTIME];
	    if(Util::isSameDay($lastRfrTime) == FALSE)
	    {
	        self::$userCopy[USER_COPY_FIELD::LAST_RFRTIME] = Util::getTime();
	        self::$userCopy[USER_COPY_FIELD::CLEAR_SWEEP_NUM] = 0;
	    }
	}
	
	public static function getSweepInfo($uid)
	{
	    $userCopy = self::getUserCopy($uid);
	    $sweepInfo = array(
	            USER_COPY_FIELD::SWEEP_CD => $userCopy[USER_COPY_FIELD::SWEEP_CD] - Util::getTime(),
	            USER_COPY_FIELD::CLEAR_SWEEP_NUM => $userCopy[USER_COPY_FIELD::CLEAR_SWEEP_NUM],
	            );
	    if($sweepInfo[USER_COPY_FIELD::SWEEP_CD] < 0)
	    {
	        $sweepInfo[USER_COPY_FIELD::SWEEP_CD] = 0;
	    }
	    return $sweepInfo;
	}
	
	public static function getClearSweepNum($uid)
	{
	    $userCopy = self::getUserCopy($uid);
	    return $userCopy[USER_COPY_FIELD::CLEAR_SWEEP_NUM];
	}
	
	public static function clearSweepCd($uid)
	{
	    $userCopy = self::getUserCopy($uid);
	    $clearNum = $userCopy[USER_COPY_FIELD::CLEAR_SWEEP_NUM];
	    self::$userCopy[USER_COPY_FIELD::SWEEP_CD] = Util::getTime();
	    self::$userCopy[USER_COPY_FIELD::CLEAR_SWEEP_NUM] += 1;
	}
	
	public static function isDuringSweepCD($uid = 0)
	{
	    $userCopy = self::getUserCopy($uid);
	    if(!isset($userCopy[USER_COPY_FIELD::SWEEP_CD]))
	    {
	        return 0;
	    }
	    $countDown = $userCopy[USER_COPY_FIELD::SWEEP_CD] - Util::getTime();
	    if($countDown < 0)
	    {
	        return FALSE;
	    }
	    return TRUE;
	}
	
	public static function addScore($uid=0)
	{
	    if(empty($uid))
	    {
	        $uid = RPCContext::getInstance()->getUid();
	    }
	    $userCopy = self::getUserCopy($uid);
	    self::$userCopy[USER_COPY_FIELD::SCORE] = $userCopy[USER_COPY_FIELD::SCORE]+1;
	    self::$userCopy[USER_COPY_FIELD::LAST_SCORE_TIME] = Util::getTime();
	}
	
	public static function setScore($score,$uid=0)
	{
	    if(empty($uid))
	    {
	        $uid = RPCContext::getInstance()->getUid();
	    }
	    $userCopy = self::getUserCopy($uid);
	    self::$userCopy[USER_COPY_FIELD::SCORE] = $score;
	    self::$userCopy[USER_COPY_FIELD::LAST_SCORE_TIME] = Util::getTime();
	}
	
	public static function openNewCopy($copyId)
	{
	    $uid = RPCContext::getInstance()->getUid();
	    self::getUserCopy($uid);
	    self::$userCopy[USER_COPY_FIELD::UID] = $uid;
	    self::$userCopy[USER_COPY_FIELD::COPY_ID] = $copyId;
	    self::$userCopy[USER_COPY_FIELD::LAST_COPY_TIME] = Util::getTime();
	}

	public static function saveUserCopy()
	{
	    if(self::$userCopyBuffer != self::$userCopy)
	    {
	        NCopyDAO::saveUserCopyInfo(self::$userCopy['uid'], self::$userCopy);
	        self::$userCopyBuffer = self::$userCopy;
	    }
	}
	
	public static function initUserCopy($copyId)
	{
	    $uid = RPCContext::getInstance()->getUid();
	    self::$userCopy[USER_COPY_FIELD::UID] = $uid;
	    self::$userCopy[USER_COPY_FIELD::COPY_ID] = $copyId;
	    self::$userCopy[USER_COPY_FIELD::LAST_COPY_TIME] = Util::getTime();
	    self::$userCopy[USER_COPY_FIELD::SCORE] = 0;
	    self::$userCopy[USER_COPY_FIELD::LAST_SCORE_TIME] = Util::getTime();
	    self::$userCopy[USER_COPY_FIELD::SWEEP_CD] = Util::getTime();
	    self::$userCopy[USER_COPY_FIELD::CLEAR_SWEEP_NUM] = 0;
	    self::$userCopy[USER_COPY_FIELD::LAST_RFRTIME] = Util::getTime();
	    NCopyDAO::insertUserCopyInfo(self::$userCopy);
	    self::$userCopyBuffer = self::$userCopy;
	}
	
	public static function resetAtkNum($baseId,$spendType=CopyDef::RESET_BASE_SPEND_TYPE_GOLD)
	{
	    $copyId = btstore_get()->BASE[$baseId]['copyid'];
	    $copyObj = MyNCopy::getInstance()->getCopyObj($copyId);
	    if(empty($copyObj))
	    {
	        throw new FakeException('resetAtkNum of base %d.but this copy %d not exist.copylist %s',$baseId,$copyId,MyNCopy::getInstance()->getAllCopies());
	    }
	    if($copyObj->getCanDefeatNum($baseId) > 0)
	    {
	        throw new FakeException('baseid %s have defeat num.',$baseId);
	    }
	    $resetTimes = $copyObj->getResetTimes($baseId);
	    $userObj = EnUser::getUserObj();
	    $bag = BagManager::getInstance()->getBag($userObj->getUid());
	    $needItem = self::getResetBaseNeedItem();
	    if($spendType == CopyDef::RESET_BASE_SPEND_TYPE_ITEM)
	    {
	        if(!empty($needItem) && $bag->deleteItembyTemplateID($needItem, 1) == FALSE)
	        {
	            throw new FakeException('bag delete item %d failed.',$needItem);
	        }
	    }
	    else if($bag->getItemNumByTemplateID($needItem) > 0)
	    {
	        throw new FakeException('has item %d,spend type is %d',$needItem,$spendType);
	    }
	    else
	    {
	        $needGold = CopyConf::$RESET_ATK_NUM_INIT_GOLD + ($resetTimes) * CopyConf::$RESET_ATK_NUM_GOLD_INC;
	        if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_COPY_RESET_ATKNUM) == FALSE)
	        {
	            throw new FakeException('sub gold failed.');
	        }
	        $copyObj->addResetNum($baseId);
	    }
	    $copyObj->resetBaseAtkNum($baseId);
	    $userObj->update();
	    $bag->update();
	    MyNCopy::getInstance()->saveCopy($copyId, $copyObj->getCopyInfo());
	    return 'ok';
	}
	
	public static function getResetBaseNeedItem()
	{
	    return btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_RESETBASENUM_NEED_ITEM];
	}
	
	public static function getOpenMymerchantChance($copyId)
	{
	    return btstore_get()->COPY[$copyId]['open_mysmerchant_chance'];
	}
	
	public static function getMaxPrizedNum($copyId)
	{
	    $arrCase = btstore_get()->COPY[$copyId]['prize']->toArray();
	    $caseNum = 0;
	    $prizedNum = 0;
	    foreach($arrCase as $case)
	    {
	        if(!empty($case))
	        {
	            $prizedNum += pow(2, $caseNum);
	            $caseNum++;
	        }
	    }
	    return $prizedNum;
	}
	
	
	public static function getMaxCopyScore($copyId)
	{
	    $arrScore = btstore_get()->COPY[$copyId]['star_arrays']->toArray();
	    $maxScore = 0;
	    foreach($arrScore as $score)
	    {
	        if($score > $maxScore)
	        {
	            $maxScore = $score;
	        }
	    }
	    return $maxScore;
	}
	
	public static function isCopyPassed($copyId, $copyInfo, $baseLv)
	{
	    $arrBaseId = btstore_get()->COPY[$copyId]['base'];
	    foreach($arrBaseId as $baseId)
	    {
	        if(empty($baseId))
	        {
	            continue;
	        }
	        for($i=$baseLv;$i>=BaseLevel::SIMPLE;$i--)
	        {
    	        $levelName = CopyConf::$BASE_LEVEL_INDEX[$i];
    	        if(isset(btstore_get()->BASE[$baseId][$levelName]))
    	        {
        	        $baseLv = $i;
        	        break;
    	        }
	        }
    	    if($copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_PROGRESS][$baseId]	< $baseLv + 2)
    	    {
    	        return FALSE;
    	    }
	    }
	    return TRUE;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
