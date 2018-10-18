<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ECopyLogic.class.php 258624 2016-08-26 09:14:29Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ecopy/ECopyLogic.class.php $
 * @author $Author: GuohaoZheng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-26 09:14:29 +0000 (Fri, 26 Aug 2016) $
 * @version $Revision: 258624 $
 * @brief 
 *  
 **/
class ECopyLogic
{
	public static function getEliteCopyInfo()
	{
	    $myEcopy  = MyECopy::getInstance();
		$copyInfo = $myEcopy->getEliteCopyInfo();
		$myEcopy->save();
		return $copyInfo;
	}
	public static function enterCopy($copyId)
	{
	    if(MyECopy::getInstance()->openAnyCopy() == FALSE)
	    {
	        throw new FakeException('not open any elite copies!');
	    }
		$canDefeat = MyECopy::getInstance()->canDefeat($copyId);
		if($canDefeat != 'ok')
		{
			throw new FakeException('can not enter copy %s to attack,because of %s.'
					,$copyId,$canDefeat);
		}
		self::initAttackInfo($copyId);		
		AtkInfo::getInstance()->saveAtkInfo();
		MyECopy::getInstance()->save();
		return 'ok';
	}
	private static function initAttackInfo($copyId)
	{
		$baseId = btstore_get()->ELITECOPY[$copyId]['base_id'];
		$baseLv = BaseLevel::SIMPLE;
		AtkInfo::getInstance()->initAtkInfo($copyId, $baseId, $baseLv);
	}

	public static function doBattle($copyId,$armyId,$fmt)
	{
	    if(MyECopy::getInstance()->openAnyCopy() == FALSE)
	    {
	        throw new FakeException('not open any elite copies!');
	    }
		$err	=	self::canAttack($copyId, $armyId);
		if($err != 'ok')
		{
			throw new FakeException('can not attack army:%s.reason:%s.',$armyId,$err);
		}
		$baseId = btstore_get()->ELITECOPY[$copyId]['base_id'];
		$ret = BaseDefeat::doBattle(BattleType::ECOPY, $armyId, $baseId, $fmt);
		return $ret;
	}

	public static function doneBattle($atkRet)
	{
		$uid	= $atkRet['uid1'];
		$pass   = $atkRet['pass'];
		$copyId	= AtkInfo::getInstance()->getCopyId();
		$baseId	= AtkInfo::getInstance()->getBaseId();
		$baseLv	= AtkInfo::getInstance()->getBaseLv();
		$newcopy= array();
		$ret = array();
		if($pass)
		{
		    if(MyECopy::getInstance()->subCanDefeatNum() == FALSE)
		    {
		        throw new FakeException('elitecopy of user %s has no defeat num.',$uid);
		    }
		    //第一次通关副本  开启新的副本或者开启攻击状态     更新此据点难度的攻略信息、排名
		    if(MyECopy::getInstance()->getStatusofCopy($copyId) < EliteCopyStatus::PASS)
		    {
		        EnSwitch::checkSwitchOnDefeatBase($baseId);
		        $newcopy = MyECopy::getInstance()->passCopy($copyId);
		        $rpInfo = AtkInfo::getInstance()->getReplayInfo();
		        NCopyDAO::addReplay($baseId, $baseLv, $rpInfo);
		        NCopyDAO::addPreBaseAttackPlayer($uid, $baseId,$baseLv,$rpInfo);
		    }
	        $ecopyInfo = MyECopy::getInstance()->getEliteCopyInfo();
	        $ret = array('elite'=>$ecopyInfo);
	        EnActive::addTask(ActiveDef::ECOPY);
	        EnWeal::addKaPoints(KaDef::ECOPY);
	        EnAchieve::updatePassECopy($uid, $copyId);
	        EnMission::doMission($uid, MissionType::ECOPY);
	        EnFestivalAct::notify($uid, FestivalActDef::TASK_COPY_ELITE_NUM, 1);
	        EnWelcomeback::updateTask(WelcomebackDef::TASK_TYPE_ECOPY, 1);
		}
		MyECopy::getInstance()->save();
		EnSwitch::getSwitchObj()->save();
		Enuser::getUserObj()->update();
		BagManager::getInstance()->getBag()->update();
		AtkInfo::getInstance()->saveAtkInfo();
		return $ret;
	}
	
	public static function getBattleReward()
	{
	    $baseId     	= AtkInfo::getInstance()->getBaseId();
	    $baseLv  		= AtkInfo::getInstance()->getBaseLv();
	    //现在精英副本的奖励信息配置在据点表里
	     $rewardBase = CopyUtil::getBasePassAward($baseId,$baseLv);
	     if(isset($rewardBase['silver']))
	     {
	         $uid = RPCContext::getInstance()->getUid();
	         $addition = EnCityWar::getCityEffect($uid, CityWarDef::ECOPY);
	         Logger::info('EnCityWar::getCityEffect act. addition is %d',$addition);
	         $rewardBase['silver'] = intval($rewardBase['silver'] * (1 + $addition/UNIT_BASE));
	     }
	     $rewardBase['item'] = array_merge($rewardBase['item'],CopyUtil::getFestivalDropReward(FestivalDef::COPY_TYPE_ELITE));
	     return $rewardBase;
	 }
	 
	/**
	 * check是否能攻击某个部队
	 * @param unknown_type $copyId
	 * @param unknown_type $armyId
	 */
	private static function canAttack($copyId,$armyId)
	{
		$copy_info = MyECopy::getInstance()->getEliteCopyInfo();
		$progress = $copy_info['va_copy_info']['progress'];
		if(!isset($progress[$copyId]))
		{
			return 'no thus elite copy with copyid '.$copyId;
		}
		$can = MyECopy::getInstance()->canDefeat($copyId);
		if($can != 'ok')
		{
			return  $can;
		}
		if(CopyUtil::checkFightCdTime() == FALSE)
		{
			return 'not cool down,can not attack.';
		}
		if(CopyUtil::checkDefeatPreArmy($armyId) == FALSE)
		{
			return 'not defeat the pre army.';
		}
		if(EnSwitch::isSwitchOpen(SwitchDef::ELITECOPY) == FALSE)
		{
		    return 'switch not open';
		}
		return 'ok';
	}
	
	public static function buyAtkNum($num)
	{
	    $uid = RPCContext::getInstance()->getUid();
	    $userObj = EnUser::getUserObj($uid);
	    $ecopyInst = MyECopy::getInstance($uid);
	    $buyNum = $ecopyInst->getBuyAtkNum();
	    $buyNumLimit = btstore_get()->VIP[$userObj->getVip()]['ecopyBuyNum'][0];
	    if($buyNum+$num > $buyNumLimit)
	    {
	        throw new FakeException('can not buy.current buynum is %d limit is %d,want to buy %d',$buyNum,$buyNumLimit,$num);
	    }
	    $initGold = btstore_get()->VIP[$userObj->getVip()]['ecopyBuyNum'][1];
	    $incGold = btstore_get()->VIP[$userObj->getVip()]['ecopyBuyNum'][2];
	    $needGold = 0;
	    for($i=0;$i<$num;$i++)
	    {
	        $needGold += ($initGold + ($buyNum + $i) * $incGold);
	    }
	    if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_BUY_ECOPY_ATKNUM) == FALSE)
	    {
	        throw new FakeException('sub gold failed.');
	    }
	    $ecopyInst->addBuyAtkNum($num);
	    $ecopyInst->addCanDefeatNum($num);
	    $userObj->update();
	    $ecopyInst->save();
	}
	
	public static function sweep($uid, $copyId, $num=1)
	{
		$userObj = EnUser::getUserObj();
	    $ecopyInst = MyECopy::getInstance($uid);
	    if($ecopyInst->getStatusofCopy($copyId) < EliteCopyStatus::PASS)
	    {
	        throw new FakeException('elitecopy %d is not pass.copyinfo is %s',$copyId,$ecopyInst->getEliteCopyInfo());
	    }
	    if($ecopyInst->subCanDefeatNum($num) == FALSE)
	    {
	        throw new FakeException('elitecopy of user %s has no defeat num.',$uid);
	    }
	    $baseId = btstore_get()->ELITECOPY[$copyId]['base_id'];
	    $baseLv = BaseLevel::SIMPLE;
	    $reward = array();
	    $rewardRet = array();
	    for($i=0;$i<$num;$i++)
	    {
	        $rewardOnce = CopyUtil::getBasePassRewardWithItemTmpl($baseId, $baseLv, $uid);
	        $festivalDrop = EnFestival::getFestival(FestivalDef::COPY_TYPE_ELITE);
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
	            $addition = EnCityWar::getCityEffect($uid, CityWarDef::ECOPY);
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
	        
	        $rewardRet[] = $rewardOnce;
	    }
	    $needExec = intval(btstore_get()->ELITECOPY[$copyId]['need_power']);
	    if(EnUser::getUserObj($uid)->subExecution($needExec*$num) == FALSE)
	    {
	        throw new FakeException('sub execution failed');
	    }
	    CopyUtil::rewardUser($reward,TRUE,$uid);
	    $extraReward = BaseDefeat::getExtraRewardByBaseId($baseId,$num);
	    $ret['reward'] = $rewardRet;
	    $ret['extra_reward'] = $extraReward;
	    $ecopyInst->save();
	    BagManager::getInstance()->getBag($uid)->update();
	    EnUser::getUserObj($uid)->update();
	    EnActive::addTask(ActiveDef::ECOPY,$num);
	    EnWeal::addKaPoints(KaDef::ECOPY,$num);
	    EnAchieve::updatePassECopy($uid, $copyId);
	    EnMission::doMission($uid, MissionType::ECOPY, $num);
	    EnFestivalAct::notify($uid, FestivalActDef::TASK_COPY_ELITE_NUM, $num);
	    EnWelcomeback::updateTask(WelcomebackDef::TASK_TYPE_ECOPY, $num);
	    return $ret;
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */