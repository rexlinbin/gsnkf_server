<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TowerLogic.class.php 255643 2016-08-11 08:43:09Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/tower/TowerLogic.class.php $
 * @author $Author: GuohaoZheng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-11 08:43:09 +0000 (Thu, 11 Aug 2016) $
 * @version $Revision: 255643 $
 * @brief 
 *  
 **/
class TowerLogic
{
    public static function getTowerInfo($uid)
    {
        if(EnSwitch::isSwitchOpen(SwitchDef::TOWER,$uid) == FALSE)
        {
            throw new FakeException("switch TOWER is not open.");
        }
        $sweepInfo = MyTower::getInstance($uid)->getSweepInfo();
        if(!empty($sweepInfo))
        {
            $sweepGap = self::getSweepGap();
            $startLv = $sweepInfo[TOWERTBL_FIELD::SWEEP_INFO_START_LEVEL];
            $endLv = $sweepInfo[TOWERTBL_FIELD::SWEEP_INFO_END_LEVEL];
            $lvNum = self::getLevelGap($startLv, $endLv);
            //扫荡时间到了  发奖    结束扫荡
            if($sweepInfo[TOWERTBL_FIELD::SWEEP_INFO_START_TIME]
                    + $lvNum * $sweepGap <= Util::getTime())
            {
                Logger::info('getTowerInfo.sweep end.starttime %d.now %d.',$sweepInfo[TOWERTBL_FIELD::SWEEP_INFO_START_TIME],Util::getTime());
                $startLv = $sweepInfo[TOWERTBL_FIELD::SWEEP_INFO_START_LEVEL];
                
                MyTower::getInstance($uid)->endSweep($endLv, $lvNum);
                self::randSpecialTowerLv($startLv, $lvNum, $uid);
                MyTower::getInstance($uid)->save();
                self::sendSweepReward($startLv, $lvNum, $uid);
                if($lvNum > 0)
                {
                    EnActive::addTask(ActiveDef::TOWER,$lvNum);
                    EnMission::doMission($uid, MissionType::TOWER, $lvNum);
                }
            }
        }
        $hellSweepInfo = MyTower::getInstance($uid)->getHellSweepInfo();
        if ( !empty( $hellSweepInfo ) )
        {
            $hellSweepGap = self::getHellSweepGap();
            
            $startLv = $hellSweepInfo[TOWERTBL_FIELD::SWEEP_INFO_START_LEVEL];
            $endLv = $hellSweepInfo[TOWERTBL_FIELD::SWEEP_INFO_END_LEVEL];
            $lvNum = self::getHellLevelGap($startLv, $endLv);
            
            if ( $hellSweepInfo[TOWERTBL_FIELD::SWEEP_INFO_START_TIME] + $lvNum * $hellSweepGap <= Util::getTime() )
            {
                MyTower::getInstance($uid)->endHellSweep($endLv, $lvNum);
                MyTower::getInstance($uid)->save();
                self::sendHellSweepReward($startLv, $lvNum, $uid, $endLv);
                if($lvNum > 0)
                {
                    EnActive::addTask(ActiveDef::HELL_TOWER,$lvNum);
                }
            }
        }
        $tower = MyTower::getInstance($uid)->getTowerInfo();
        MyTower::getInstance($uid)->save();
        return $tower;
    }
    
    public static function doBattle($towerLv,$armyId,$uid, $type=1)
    {
        if(EnSwitch::isSwitchOpen(SwitchDef::TOWER) == FALSE)
        {
            throw new FakeException("switch TOWER is not open.");
        }
        self::canAttack($towerLv, $armyId, $uid, $type);
        
        $baseId = 0;
        $battleType = 0;
        
        switch ( $type )
        {
            case HellTowerDef::TOWER_TYPE_NORMAL:
                $baseId = intval( btstore_get()->TOWERLEVEL[$towerLv]['base_id'] );
                $battleType = BattleType::TOWER;
                break;
            case HellTowerDef::TOWER_TYPE_HELL:
                $baseId = intval( btstore_get()->HELL_TOWER_LEVEL[$towerLv][HellTowerLevelDef::BASE_ID] );
                $battleType = BattleType::HELL_TOWER;
                break;
            default:
                throw new FakeException("unknown type:%d.", $type);
                break;
        }
        
        $ret = BaseDefeat::doBattle($battleType, $armyId, $baseId);
        if(isset($ret['reward']['item']))
        {
            unset($ret['reward']['item']);
        }
        return $ret;
    }
    
    private static function canAttack($towerLv,$armyId,$uid, $type=1)
    {
        if(!isset(btstore_get()->TOWERLEVEL[$towerLv]))
        {
            throw new ConfigException('no thus level %s in btstore.',$towerLv);
        }
        //查看session中的attackinfo是否是空的
        $atkInfo = AtkInfo::getInstance($uid)->getAtkInfo();
        if(empty($atkInfo))
        {
            throw new InterException('no attackinfo in session.');
        }
        //部队和据点是否相符
        $baseId = 0;
        $levelStatus = 0;
        $hasFailNum = 0;
        $curLevel = 0;
        switch ( $type )
        {
            case HellTowerDef::TOWER_TYPE_NORMAL:
                $baseId = intval( btstore_get()->TOWERLEVEL[$towerLv]['base_id'] );
                $levelStatus = MyTower::getInstance($uid)->getLevelStatus($towerLv);
                $hasFailNum = MyTower::getInstance($uid)->hasFailNum();
                $curLevel = MyTower::getInstance($uid)->getCurLevel();
                $sweepInfo = MyTower::getInstance($uid)->getSweepInfo();
                if( !empty($sweepInfo) )
                {
                    throw new FakeException('can not attack tower.user is in sweep status.');
                }
                break;
            case HellTowerDef::TOWER_TYPE_HELL:
                $baseId = intval( btstore_get()->HELL_TOWER_LEVEL[$towerLv][HellTowerLevelDef::BASE_ID] );
                $levelStatus = MyTower::getInstance($uid)->getHellLevelStatus($towerLv);
                $hasFailNum = MyTower::getInstance($uid)->hasHellFailNum();
                $curLevel = MyTower::getInstance($uid)->getCurHellLevel();
                $hellSweepInfo = MyTower::getInstance($uid)->getHellSweepInfo();
                if( !empty( $hellSweepInfo ) )
                {
                    throw new FakeException('can not attack tower.user is in hell sweep status.');
                }
                break;
            default:
                throw new FakeException("unknown type:%d.", $type);
                break;
        }
        
        if(CopyUtil::isArmyinBase($baseId,BaseLevel::SIMPLE, $armyId) == false)
        {
            throw new ConfigException('this army %s is not in level %s.',$armyId,$towerLv);
        }
        //前置部队是否击败
        if(CopyUtil::checkDefeatPreArmy($armyId) == false)
        {
            throw new FakeException('not defeat the prearmy of army %s.',$armyId);
        }
        //此塔层是否能攻击
        if($levelStatus < TowerLevelStatus::ATTACK)
        {
            throw new FakeException('this level %s status is %s,can not attack.',$towerLv,$levelStatus);
        }
        //判断是否在冷却时间内
        if(!CopyUtil::checkFightCdTime())
        {
            throw new FakeException('can not fight,not cool down');
        }
        //是否有攻击次数
        if($hasFailNum == false)
        {
            throw new FakeException('no defeat num.');
        }
    
        if( $curLevel != $towerLv)
        {
            throw new FakeException('atk level %d is not current level %d.',$towerLv,MyTower::getInstance()->getCurLevel());
        }
        
        if(BagManager::getInstance()->getBag()->isFull())
        {
            throw new FakeException('bag is full can not atk tower level');
        }
        return TRUE;
    }
    
    
	public static function getBattleReward()
	{
	    $towerLv = TowerAtkInfo::getInstance()->getTowerLv();
	    $reward = self::getTowerRewardByLv(array($towerLv));
	    if(isset($reward['item']))
	    {
	        $itemIds = array();
	        foreach($reward['item'] as $itemTmplId => $num)
	        {
	            $itemIds = array_merge($itemIds,ItemManager::getInstance()->addItem($itemTmplId,$num));
	        }
	        $reward['item'] = $itemIds;
	    }
	    if(isset($reward['silver']))
	    {
	        $uid = RPCContext::getInstance()->getUid();
	        $addition = EnCityWar::getCityEffect($uid, CityWarDef::TOWER);
	        Logger::info('EnCityWar::getCityEffect act. addition is %d',$addition);
	        $reward['silver'] = intval($reward['silver'] * (1 + $addition/UNIT_BASE));
	    }
	    return $reward;
	}
	
	private static function getTowerRewardByLv($arrTowerLv)
	{
	    $reward = array();
	    foreach($arrTowerLv as $towerLv)
	    {
	        if(!isset(btstore_get()->TOWERLEVEL[$towerLv]['reward']))
	        {
	            return array();
	        }
	        $towerReward = btstore_get()->TOWERLEVEL[$towerLv]['reward'];
	        foreach($towerReward as $type => $value)
	        {
	            switch($type)
	            {
	                case 'item':
	                    foreach($value as $tmplId => $num)
	                    {
	                        if(!isset($reward['item'][$tmplId]))
	                        {
	                            $reward['item'][$tmplId] = 0;
	                        }
	                        $reward['item'][$tmplId] += $num;
	                    }
	                    break;
	                default:
	                    if(!isset($reward[$type]))
    	                {
    	                    $reward[$type] = 0;
    	                }
    	                $reward[$type] += $value;
	            }
	        }
	    }
	    return $reward;
	}
	
	public static function buyDefeatNum($uid, $type=1)
	{
	    if(EnSwitch::isSwitchOpen(SwitchDef::TOWER) == FALSE)
	    {
	        throw new FakeException("switch TOWER is not open.");
	    }
	    switch ( $type )
	    {
	        case HellTowerDef::TOWER_TYPE_NORMAL:
	            $canFailNum = MyTower::getInstance($uid)->getCanFailNum();
	            if($canFailNum > 0)
	            {
	                throw new FakeException('current fail num is %d.can not buy.',$canFailNum);
	            }
	            $initGold = self::getBuyFailInitGold();
	            $incGold = self::getBuyFailIncGold();
	            $numLimit = self::getDailyGoldBuyNum($uid);
	            $goldLimit = self::getBuyFailGoldLimit();
	            $curNum = MyTower::getInstance($uid)->getGoldBuyNum();
	            if($curNum >= $numLimit)
	            {
	                throw new FakeException('cur gold buy num is %d.num limit is %d.',$curNum,$numLimit);
	            }
	            $spend = ($curNum) * $incGold + $initGold ;
	            if($spend > $goldLimit)
	            {
	                $spend = $goldLimit;
	            }
	            if(EnUser::getUserObj($uid)->subGold($spend, StatisticsDef::ST_FUNCKEY_TOWER_BUY_DEFEAT_NUM) == false)
	            {
	                throw new InterException('fail to sub gold.');
	            }
	            MyTower::getInstance($uid)->addGoldBuyNum(1);
	            MyTower::getInstance($uid)->addFailNum(1);
	            break;
	        case HellTowerDef::TOWER_TYPE_HELL:
	            $canFailNum = MyTower::getInstance($uid)->getHellCanFailNum();
	            if($canFailNum > 0)
	            {
	                throw new FakeException('current fail num is %d.can not buy.',$canFailNum);
	            }
	            
	            $conf = btstore_get()->HELL_TOWER;
	            
	            $curNum = MyTower::getInstance($uid)->getHellGoldBuyNum();
	            $numLimit = intval( $conf[HellTowerFloorDef::MAX_FAIL_NUM] );
	            if($curNum >= $numLimit)
	            {
	                throw new FakeException('cur gold buy num is %d.num limit is %d.',$curNum,$numLimit);
	            }
	            
	            $initGold = intval( $conf[HellTowerFloorDef::BASE_GOLD] );
	            $incGold = intval( $conf[HellTowerFloorDef::GROW_GOLD] );
	            $spend = $initGold + $curNum * $incGold;
	            
	            if(EnUser::getUserObj($uid)->subGold($spend, StatisticsDef::ST_FUNCKEY_BUY_HELLTOWER_DEFEATNUM) == false)
	            {
	                throw new InterException('fail to sub gold.');
	            }
	            MyTower::getInstance($uid)->addGoldBuyHell(1);
	            MyTower::getInstance($uid)->addHellFailNum(1);
	            break;
	        default:
	            throw new FakeException("unknown type:%d.", $type);
	            break;
	    }
	    
		Enuser::getUserObj($uid)->update();
		MyTower::getInstance($uid)->save();
		return $spend;
	}
	
	public static function doneBattle($atkRet)
	{
	    Logger::trace('TowerLogic::doneBattle');
		$armyId	= $atkRet['uid2'];
		$brid = $atkRet['brid'];
		$pass = $atkRet['pass'];
		$fail = $atkRet['fail'];
		$towerLv = TowerAtkInfo::getInstance()->getTowerLv();
		$baseId = AtkInfo::getInstance()->getBaseId();
		$newTowerLvs = array();
		$addSpecail = FALSE;
		$uid = RPCContext::getInstance()->getUid();
		if ($fail == TRUE)
		{
		    AtkInfo::getInstance()->setBasePrgOnDefeatArmy($armyId, ATK_INFO_ARMY_STATUS::DEFEAT_FAIL);
			MyTower::getInstance()->subCanFailNum();
		}		
		else 
		{
		    AtkInfo::getInstance()->setBasePrgOnDefeatArmy($armyId, $brid);
		}
		if($pass == TRUE)
	    {
	        if(CopyUtil::passCondition($baseId, BaseLevel::SIMPLE, $atkRet) == 'ok')
	        {
	            $newTowerLvs = MyTower::getInstance()->passLevel($towerLv);
	            $addSpecail = self::randSpecialTowerLv($towerLv, 1, $uid);
	            //通关塔层后，通知打点函数(比如 新服活动“开服7天乐”的任务，以后有类似的任务需求直接放到informPassTowerTask函数里面就可以了)
	            self::informPassTowerTask($towerLv);
	        }
	        else
	        {
	            MyTower::getInstance()->subCanFailNum();
	            Logger::info('sub can_fail_num.not satify the pass condition.');
	        }
	        
	    }
	    if($pass || $fail)
	    {
	        EnActive::addTask(ActiveDef::TOWER);
	        EnMission::doMission($uid, MissionType::TOWER);
	    }
		Enuser::getUserObj()->update();
		MyTower::getInstance()->save();
		BagManager::getInstance()->getBag()->update();
		AtkInfo::getInstance()->saveAtkInfo();
		$towerInfo = MyTower::getInstance()->getTowerInfo();
		if(empty($newTowerLvs))
		{
		    return array('pass'=>FALSE);
		}
		if($addSpecail)
		{
		    return array('pass'=>TRUE,'tower_info'=>$towerInfo);
		}
		return array('pass'=>TRUE);
	}
	
	public static function enterLevel($towerLv,$uid, $type=1)
	{
	    if(EnSwitch::isSwitchOpen(SwitchDef::TOWER,$uid) == FALSE)
	    {
	        throw new FakeException("switch TOWER is not open.");
	    }
		if(BagManager::getInstance()->getBag()->isFull())
	    {
	        throw new FakeException('bag is full can not atk tower level');
	    }
	    
	    $needLv = 0;
	    $baseId = 0;
	    $battleType = 0;
	    if ( HellTowerDef::TOWER_TYPE_NORMAL == $type )
	    {
	        if(MyTower::getInstance($uid)->getLevelStatus($towerLv) < TowerLevelStatus::ATTACK)
	        {
	            throw new FakeException('can not enter this level %s,its status is %s',$towerLv,MyTower::getInstance()->getLevelStatus($towerLv));
	        }
	        
	        if(MyTower::getInstance($uid)->getCurLevel() != $towerLv)
	        {
	            throw new FakeException('the tower %d is not current level %d.',$towerLv,MyTower::getInstance($uid)->getCurLevel());
	        }
	        
	        if(MyTower::getInstance($uid)->hasFailNum() == false)
	        {
	            throw new FakeException('can not enter this level %s.no defeat num.',$towerLv);
	        }
	        
	        if(MyTower::getInstance($uid)->getTowerStatus() == TowerDef::CUR_LEVEL_STATUS_PASS)
	        {
	            throw new FakeException('pass all towerlevel.cur level is %d.max level is %d.',
	                MyTower::getInstance()->getCurLevel(),MyTower::getInstance()->getMaxLevel());
	        }
	        
	        $needLv = intval( btstore_get()->TOWERLEVEL[$towerLv]['need_lv'] );
	        $baseId = intval( btstore_get()->TOWERLEVEL[$towerLv]['base_id'] );
	        $battleType = BattleType::TOWER;
	    }
	    elseif ( HellTowerDef::TOWER_TYPE_HELL == $type )
	    {
	        if(MyTower::getInstance($uid)->getHellLevelStatus($towerLv) < TowerLevelStatus::ATTACK)
	        {
	            throw new FakeException('can not enter this hell level %s,its status is %s',$towerLv,MyTower::getInstance()->getLevelStatus($towerLv));
	        }
	        
	        if(MyTower::getInstance($uid)->getCurHellLevel() != $towerLv)
	        {
	            throw new FakeException('the tower %d is not current level %d.',$towerLv,MyTower::getInstance($uid)->getCurHellLevel());
	        }
	        
	        if(MyTower::getInstance($uid)->hasHellFailNum() == false)
	        {
	            throw new FakeException('can not enter this level %s.no defeat num.',$towerLv);
	        }
	        
	        if(MyTower::getInstance($uid)->getHellTowerStatus() == TowerDef::CUR_LEVEL_STATUS_PASS)
	        {
	            throw new FakeException('pass all towerlevel.cur level is %d.max level is %d.',
	                MyTower::getInstance()->getCurHellLevel(),MyTower::getInstance()->getHellMaxLevel());
	        }
	        
	        $needLv = intval( btstore_get()->HELL_TOWER_LEVEL[$towerLv][HellTowerLevelDef::NEED_LEVEL] );
	        $baseId = intval( btstore_get()->HELL_TOWER_LEVEL[$towerLv][HellTowerLevelDef::BASE_ID]);
	        $battleType = BattleType::HELL_TOWER;
	    }
	    else 
	    {
	        throw new FakeException("unknown type:%d.", $type);
	    }
		
		$userLv = EnUser::getUserObj($uid)->getLevel();
		if($userLv < $needLv)
		{
		    throw new FakeException('this level %d is not open.need user level %d.now user level is %d.',
		            $towerLv,$needLv,$userLv);
		}
		
		AtkInfo::getInstance($uid)->initAtkInfo($towerLv, $baseId, BaseLevel::SIMPLE, $battleType);
		AtkInfo::getInstance($uid)->saveAtkInfo();
	}
	
	public static function resetTower($uid, $type=1)
	{
	    if(EnSwitch::isSwitchOpen(SwitchDef::TOWER,$uid) == FALSE)
	    {
	        throw new FakeException("switch TOWER is not open.");
	    }
	    
	    switch ( $type )
	    {
	        case HellTowerDef::TOWER_TYPE_NORMAL:
	            $sweepInfo = MyTower::getInstance($uid)->getSweepInfo();
	            if(!empty($sweepInfo))
	            {
	                throw new FakeException('can not reset tower.user is in sweep status.');
	            }
	            if(MyTower::getInstance($uid)->getCurLevel() == TowerDef::FIRST_TOWER_LEVEL_ID)
	            {
	                throw new FakeException('current level is first level.can not reset tower.');
	            }
	            if(MyTower::getInstance($uid)->resetTower() == FALSE)
	            {
	                throw new FakeException('can not reset tower.not enough reset num');
	            }
	            break;
	        case HellTowerDef::TOWER_TYPE_HELL:
	            $hellSweepInfo = MyTower::getInstance($uid)->getHellSweepInfo();
	            if ( !empty( $hellSweepInfo ) )
	            {
	                throw new FakeException('can not reset tower.user is in sweep status.');
	            }
	            if(MyTower::getInstance($uid)->getCurHellLevel() == HellTowerDef::FIRST_HELL_TOWER_LEVEL_ID)
	            {
	                throw new FakeException('current level is first level.can not reset tower.');
	            }
	            if(MyTower::getInstance($uid)->resetHellTower() == FALSE)
	            {
	                throw new FakeException('can not reset tower.not enough reset num');
	            }
	            break;
	        default:
	            throw new FakeException("unknown type:%d.", $type);
	            break;
	    }
	    MyTower::getInstance($uid)->save();
	    return 'ok';
	}
	
	public static function sweep($startTowerLv,$endTowerLv,$uid, $type=1)
	{
	    if(EnSwitch::isSwitchOpen(SwitchDef::TOWER,$uid) == FALSE)
	    {
	        throw new FakeException("switch TOWER is not open.");
	    }
	    if($endTowerLv < $startTowerLv)
	    {
	        throw new FakeException('endlv %d smaller than startlv %d.',$endTowerLv,$startTowerLv);
	    }
	    
	    switch ( $type )
	    {
	        case HellTowerDef::TOWER_TYPE_NORMAL:
        	    $sweepInfo = MyTower::getInstance($uid)->getSweepInfo();
        	    if(!empty($sweepInfo))
        	    {
        	        throw new FakeException('has sweepinfo in db.');
        	    }
        	    if($startTowerLv != MyTower::getInstance()->getCurLevel())
        	    {
        	        throw new FakeException('mytower startlv %d is not equal to current lv %d.',$startTowerLv,MyTower::getInstance()->getCurLevel());
        	    }
        	    if(MyTower::getInstance($uid)->getCanFailNum() < 1)
        	    {
        	        throw new FakeException('no fail num can not sweep');
        	    }
        	    $maxTowerLv =  MyTower::getInstance($uid)->getMaxLevel();
        	    if($endTowerLv > $maxTowerLv)
        	    {
        	        throw new FakeException('end tower level %d is max than max atk tower level %d.',$endTowerLv,$maxTowerLv);
        	    }
        	    if(MyTower::getInstance($uid)->getTowerStatus() == TowerDef::CUR_LEVEL_STATUS_PASS)
        	    {
        	        throw new FakeException('can not sweep.sweep start level %d.cur level %d.current status %d',
        	            $startTowerLv,
        	            MyTower::getInstance($uid)->getCurLevel(),
        	            MyTower::getInstance($uid)->getTowerStatus());
        	    }
        	    MyTower::getInstance($uid)->startSweep($startTowerLv, $endTowerLv);
	            break;
	        case HellTowerDef::TOWER_TYPE_HELL:
	            $hellSweepInfo = MyTower::getInstance($uid)->getHellSweepInfo();
	            if ( !empty( $hellSweepInfo ) )
	            {
	                throw new FakeException('can not sweep tower.user is in sweep status.');
	            }
	            if($startTowerLv != MyTower::getInstance()->getCurHellLevel())
	            {
	                throw new FakeException('mytower startlv %d is not equal to current lv %d.',$startTowerLv,MyTower::getInstance()->getCurHellLevel());
	            }
	            if(MyTower::getInstance($uid)->getHellCanFailNum() < 1)
	            {
	                throw new FakeException('no fail num can not sweep');
	            }
	            $maxTowerLv =  MyTower::getInstance($uid)->getHellMaxLevel();
	            if($endTowerLv > $maxTowerLv)
	            {
	                throw new FakeException('end tower level %d is max than max atk tower level %d.',$endTowerLv,$maxTowerLv);
	            }
	            if(MyTower::getInstance($uid)->getHellTowerStatus() == TowerDef::CUR_LEVEL_STATUS_PASS)
	            {
	                throw new FakeException('can not sweep.sweep start level %d.cur level %d.current status %d',
	                    $startTowerLv,
	                    MyTower::getInstance($uid)->getCurHellLevel(),
	                    MyTower::getInstance($uid)->getHellTowerStatus());
	            }
	            MyTower::getInstance($uid)->startHellSweep($startTowerLv, $endTowerLv);
	            break;
	        default:
	            throw new FakeException("unknown type:%d.", $type);
	            break;
	    }
	    
	    MyTower::getInstance($uid)->save();
	    return MyTower::getInstance($uid)->getTowerInfo();
	}
	
	public static function endSweep($uid, $type=1)
	{
	    if(EnSwitch::isSwitchOpen(SwitchDef::TOWER,$uid) == FALSE)
	    {
	        throw new FakeException("switch TOWER is not open.");
	    }
	    
	    switch ( $type )
	    {
	        case HellTowerDef::TOWER_TYPE_NORMAL:
	            $sweepInfo = MyTower::getInstance($uid)->getSweepInfo();
	            if(empty($sweepInfo))
	            {
	                throw new FakeException('endSweep but no sweepinfo in db.');
	            }
	            $gap = TowerLogic::getSweepGap();
	            $startLv = $sweepInfo[TOWERTBL_FIELD::SWEEP_INFO_START_LEVEL];
	            $startTime = $sweepInfo[TOWERTBL_FIELD::SWEEP_INFO_START_TIME];
	            $endLv = $sweepInfo[TOWERTBL_FIELD::SWEEP_INFO_END_LEVEL];
	            $levelNum = intval((Util::getTime() - $startTime)/$gap);
	            $maxLvNum = self::getLevelGap($startLv, $endLv);
	            if($levelNum > $maxLvNum)
	            {
	                Logger::warning('sweep should be ended sooner.start_time %d.'.
	                    'startlv %d.endlv %d.lvNum %d.sweepgap %d.now is %d.',
	                    $startTime,$startLv,$endLv,$levelNum,$gap,Util::getTime());
	                    $levelNum = $maxLvNum;
	            }
	            $actualEndLv = self::getEndLevelAfterSweep($startLv, $levelNum);
	            MyTower::getInstance($uid)->endSweep($actualEndLv,$levelNum);
	            self::randSpecialTowerLv($startLv, $levelNum, $uid);
	            MyTower::getInstance($uid)->save();
	            $reward = self::sendSweepReward($startLv, $levelNum, $uid);
	            if($levelNum > 0)
	            {
	                EnActive::addTask(ActiveDef::TOWER,$levelNum);
	                EnMission::doMission($uid, MissionType::TOWER, $levelNum);
	            }
	            break;
	        case HellTowerDef::TOWER_TYPE_HELL:
	            $hellSweepInfo = MyTower::getInstance($uid)->getHellSweepInfo();
	            if ( empty( $hellSweepInfo ) )
	            {
	                throw new FakeException('endSweep but no sweepinfo in db.');
	            }
	            $gap = TowerLogic::getHellSweepGap();
	            $startLv = $hellSweepInfo[TOWERTBL_FIELD::SWEEP_INFO_START_LEVEL];
	            $startTime = $hellSweepInfo[TOWERTBL_FIELD::SWEEP_INFO_START_TIME];
	            $endLv = $hellSweepInfo[TOWERTBL_FIELD::SWEEP_INFO_END_LEVEL];
	            $levelNum = intval((Util::getTime() - $startTime)/$gap);
	            $maxLvNum = self::getHellLevelGap($startLv, $endLv);
	            if($levelNum > $maxLvNum)
	            {
	                Logger::warning('sweep should be ended sooner.start_time %d.'.
	                    'startlv %d.endlv %d.lvNum %d.sweepgap %d.now is %d.',
	                    $startTime,$startLv,$endLv,$levelNum,$gap,Util::getTime());
	                    $levelNum = $maxLvNum;
	            }
	            $actualEndLv = self::getHellEndLevelAfterSweep($startLv, $levelNum);
	            MyTower::getInstance($uid)->endHellSweep($actualEndLv,$levelNum);
	            MyTower::getInstance($uid)->save();
	            $reward = self::sendHellSweepReward($startLv, $levelNum, $uid, $actualEndLv);
	            if($levelNum > 0)
	            {
	                EnActive::addTask(ActiveDef::HELL_TOWER,$levelNum);
	            }
	            break;
	        default:
	            throw new FakeException("unknown type:%d.", $type);
	            break;
	    }
	    
	    return MyTower::getInstance($uid)->getTowerInfo();
	}
	
	public static function getLevelGap($startLv,$endLv)
	{
	    if($startLv > $endLv)
	    {
	        return 0;
	    }
	    $lvConf = btstore_get()->TOWERLEVEL;
	    $lvNum = 0;
	    foreach($lvConf as $level => $conf)
	    {
	        if($level >= $startLv && ($level <= $endLv))
	        {
	            $lvNum++;
	        }
	    }
	    return $lvNum;
	}
	
	private static function getEndLevelAfterSweep($startLv,$lvNum)
	{
	    if($lvNum == 0)
	    {
	        return $startLv;
	    }
	    $lvConf = btstore_get()->TOWERLEVEL;
	    $towerLv = 0;
	    foreach($lvConf as $level => $conf)
	    {
	        if($level >= $startLv)
	        {
	            $lvNum -= 1;
	        }
	        if($lvNum == 0)
	        {
	            return $level;
	        }
	    }
	}
	
	private static function sendSweepReward($startLv,$lvNum,$uid)
	{
	    Logger::trace('sendSweepReward startlv %d lvNum %d.',$startLv,$lvNum);
	    $reward = array();
	    if( $lvNum <= 0 )
	    {
	    	return $reward;
	    }
	    $conf = btstore_get()->TOWERLEVEL;
	    $arrTowerLv = array();
	    foreach($conf as $towerLv => $towerConf)
	    {
	        if($towerLv >= $startLv)
	        {
	            $arrTowerLv[] = $towerLv;
	            $lvNum -= 1;
	        }
	        if($lvNum == 0)
	        {
	            break;
	        }
	    }
	    $reward = self::getTowerRewardByLv($arrTowerLv);
	    if(isset($reward['item']))
	    {
	        $reward[RewardType::ARR_ITEM_TPL] = $reward['item'];
	        unset($reward['item']);
	    }
	    if(isset($reward['silver']))
	    {
	        $addition = EnCityWar::getCityEffect($uid, CityWarDef::TOWER);
	        Logger::info('EnCityWar::getCityEffect act. addition is %d',$addition);
	        $reward['silver'] = intval($reward['silver'] * (1 + $addition/UNIT_BASE));
	    }
	    EnReward::sendReward($uid, RewardSource::TOWER_SWEEP, $reward);
	    Logger::trace('sendSweepReward startlv %d lvNum %d.reward %s',$startLv,$lvNum,$reward);
	    return $reward;
	}
	
	public static function getTowerRank($rankNum,$guid)
	{
	    if(EnSwitch::isSwitchOpen(SwitchDef::TOWER) == FALSE)
	    {
	        throw new FakeException("switch TOWER is not open.");
	    }
	    if($rankNum > DataDef::MAX_FETCH)
	    {
	        throw new FakeException('ranknum %s is max than max_fetch %d.',$rankNum,DataDef::MAX_FETCH);
	    }
	    $rankInfo = TowerDAO::getRank($rankNum);
	    $arrUid = array();
	    foreach($rankInfo as $index => $userRank)
	    {
	        $arrUid[] = $userRank[TOWERTBL_FIELD::UID];
	    }
	    $arrUser = EnUser::getArrUserBasicInfo($arrUid,array('uname','htid','dress','level','vip','fight_force','guild_id'));
	    $userRank = array();
	    $arrGuildId = Util::arrayExtract($arrUser, 'guild_id');
	    $arrGuildInfo = EnGuild::getArrGuildInfo($arrGuildId,array(GuildDef::GUILD_NAME));
	    foreach($rankInfo as $index => $rank)
	    {
	        $uid = $rank[TOWERTBL_FIELD::UID];
	        $rankInfo[$index]['uname'] = $arrUser[$uid]['uname'];
	        $rankInfo[$index]['level'] = $arrUser[$uid]['level'];
	        $rankInfo[$index]['htid'] = $arrUser[$uid]['htid'];
	        $rankInfo[$index]['dress'] = $arrUser[$uid]['dress'];
	        $rankInfo[$index]['vip'] = $arrUser[$uid]['vip'];
	        $rankInfo[$index]['fight_force'] = $arrUser[$uid]['fight_force'];
	        $rankInfo[$index]['rank'] = $index+1;
	        $guildId = $arrUser[$uid]['guild_id'];
	        if(!empty($guildId))
	        {
	            $rankInfo[$index][GuildDef::GUILD_NAME] = $arrGuildInfo[$guildId][GuildDef::GUILD_NAME];
	        }
	        if($uid == $guid)
	        {
	            $userRank['rank'] = $index+1;
	            $userRank[TOWERTBL_FIELD::MAX_LEVEL] = $rank[TOWERTBL_FIELD::MAX_LEVEL];
	        }
	    }
	    if(empty($userRank))
	    {
	        $towerInst = MyTower::getInstance($guid);
	        $userRank[TOWERTBL_FIELD::MAX_LEVEL] = $towerInst->getMaxLevel();
	        $userRank['rank'] = TowerDAO::getRankOfUser($guid, 
	                $towerInst->getMaxLevel(), $towerInst->getMaxLevelTime());
	    }
	    return array('rank_list'=>$rankInfo,'user_rank'=>$userRank);
	}
	
	public static function getDailyFailNum()
	{
	    return intval(btstore_get()->TOWER['fail_num']);
	}
	
	public static function getDailyResetNum()
	{
	    return intval(btstore_get()->TOWER['daily_atk_num']);
	}
	
	public static function getDailyGoldBuyNum($uid)
	{
	    $vip = EnUser::getUserObj($uid)->getVip();
	    $numLimit = btstore_get()->VIP[$vip]['towerBuyLimit'];
	    return $numLimit;
	}
	
	public static function getSweepGap()
	{
	    return intval(btstore_get()->TOWER['sweep_need_time']);
	}
	
	public static function getBuyFailInitGold()
	{
	    $towerCnf = btstore_get()->TOWER;
	    $initGold = $towerCnf['buy_gold_init'];
	    return $initGold;
	}
	
	public static function getBuyFailIncGold()
	{
	    $towerCnf = btstore_get()->TOWER;
	    $incGold = $towerCnf['buy_gold_inc'];
	    return $incGold;
	}
	
	public static function getBuyFailGoldLimit()
	{
	    $towerCnf = btstore_get()->TOWER;
	    $goldLimit = $towerCnf['buy_gold_limit'];
	    return $goldLimit;
	}
	/**
	 * 隐藏关卡有有效时间和攻击次数限制
	 * @param int $towerLv
	 */
	public static function randSpecialTowerLv($startLv,$lvNum,$uid)
	{
		if( $lvNum <= 0 )
		{
			Logger::trace('lvNum:%d no need to rand special tower', $lvNum);
			return;
		}
	    $conf = btstore_get()->TOWERLEVEL;
	    $arrTowerLv = array();
	    foreach($conf as $towerLv => $towerConf)
	    {
	        if($towerLv >= $startLv)
	        {
	            $arrTowerLv[] = $towerLv;
	            $lvNum -= 1;
	        }
	        if($lvNum <= 0)
	        {
	            break;
	        }
	    }
	    $trigger = FALSE;
	    $myTower = MyTower::getInstance($uid);
	    foreach($arrTowerLv as $towerLv)
	    {
	        $chance = btstore_get()->TOWERLEVEL[$towerLv]['open_special_chance'];
	        $rand = rand(0, UNIT_BASE);
	        if($rand <= $chance)
	        {
	            $samples = btstore_get()->TOWERLEVEL[$towerLv]['open_special_lv']->toArray();
	            $arrSpecailLv = Util::noBackSample($samples, 1);
	            if(count($arrSpecailLv) < 1)
	            {
	                Logger::fatal('why sample nothing.sample %s',$samples);
	                continue;
	            }
	            Logger::info('defeat tower lv %d rand specail tower tmpl lv %d.rand %d chance %d.',$towerLv,$arrSpecailLv[0],$rand,$chance);
	            $trigger=TRUE;
	            $myTower->addSpecailTowerLv($arrSpecailLv[0]);
	        }
	    }
	    return $trigger;
	}
	
	public static function getSpecailTowerDuration()
	{
	    return btstore_get()->TOWER['special_lv_duration'];
	}
	/**
	 * 隐藏关卡总的攻击次数
	 * @param unknown_type $towerLvId
	 */
	public static function getSpecailTowerAtkNum()
	{
	    return btstore_get()->TOWER['special_lv_atk_num'];
	}
	
	
	public static function enterSpecailLevel($towerLvId,$uid)
	{
	    if(EnSwitch::isSwitchOpen(SwitchDef::TOWER,$uid) == FALSE)
	    {
	        throw new FakeException("switch TOWER is not open.");
	    }
// 	    if(BagManager::getInstance()->getBag()->isFull())
// 	    {
// 	        throw new FakeException('bag is full can not atk tower level');
// 	    }
	    $myTower = MyTower::getInstance($uid);
	    $towerList = $myTower->getSpecailTowerList();
	    if(!isset($towerList[$towerLvId]))
	    {
	        throw new FakeException('special tower %d not exist or has expire or has no defeat num.',$towerLvId);
	    }
	    $atkNum = $myTower->getSpecailTowerAtkNum($towerLvId);
	    if($atkNum >= self::getSpecailTowerAtkNum())
	    {
	        throw new FakeException('tower %d has no defeat num.',$towerLvId);
	    }
	    $baseId = $towerList[$towerLvId][TOWERTBL_FIELD::VA_TOWER_SPECAIL_TOWERID];
	    $needExec = CopyUtil::getBaseNeedExec($baseId, BaseLevel::SIMPLE);
	    if(EnUser::getUserObj($uid)->getCurExecution() < $needExec)
	    {
	        throw new FakeException('getCurExecution %d need %d',EnUser::getUserObj($uid)->getCurExecution(),$needExec);
	    }
	    AtkInfo::getInstance($uid)->initAtkInfo($towerLvId, $baseId, BaseLevel::SIMPLE, BattleType::TOWER);
	    AtkInfo::getInstance($uid)->saveAtkInfo();
	    $myTower->save();
	}
	
	public static function defeatSpecialTower($towerLvId,$armyId,$uid,$fmt=array())
	{
	    if(EnSwitch::isSwitchOpen(SwitchDef::TOWER) == FALSE)
	    {
	        throw new FakeException("switch TOWER is not open.");
	    }
	    self::canDefeatSpecail($towerLvId, $armyId, $uid);
	    $myTower = MyTower::getInstance($uid);
	    $towerList = $myTower->getSpecailTowerList();
	    $baseId = $towerList[$towerLvId][TOWERTBL_FIELD::VA_TOWER_SPECAIL_TOWERID];
	    $ret = BaseDefeat::doBattle(BattleType::SPECAIL_TOWER, $armyId, $baseId, $fmt);
	    $ret['newcopyorbase']['tower_info'] = $myTower->getTowerInfo();
	    return $ret;
	}
	
	private static function canDefeatSpecail($towerLvId,$armyId,$uid)
	{
	    $myTower = MyTower::getInstance($uid);
	    $atkNum = $myTower->getSpecailTowerAtkNum($towerLvId);
	    if($atkNum >= self::getSpecailTowerAtkNum())
	    {
	       throw new FakeException('tower %d has no defeat num.',$towerLvId);
	    }
	    $towerList = $myTower->getSpecailTowerList();
	    $baseId = $towerList[$towerLvId][TOWERTBL_FIELD::VA_TOWER_SPECAIL_TOWERID];
	    $lvName = CopyConf::$BASE_LEVEL_INDEX[BaseLevel::SIMPLE];
	    $arrArmy = btstore_get()->BASE[$baseId][$lvName][$lvName.'_army_arrays']->toArray();
	    if(in_array($armyId, $arrArmy) == FALSE)
	    {
	        throw new FakeException('army %d is not in tower %d.',$armyId,$towerLvId);
	    }
	}	
	
	public static function doneSpecailBattle($atkRet)
	{
	    Logger::trace('TowerLogic::doneBattle');
	    $armyId	= $atkRet['uid2'];
	    $brid = $atkRet['brid'];
	    $pass = $atkRet['pass'];
	    $fail = $atkRet['fail'];
	    $towerLv = TowerAtkInfo::getInstance()->getTowerLv();
	    $baseId = AtkInfo::getInstance()->getBaseId();
	    if ($fail == TRUE)
	    {
	        AtkInfo::getInstance()->setBasePrgOnDefeatArmy($armyId, ATK_INFO_ARMY_STATUS::DEFEAT_FAIL);
	    }
	    else
	    {
	        AtkInfo::getInstance()->setBasePrgOnDefeatArmy($armyId, $brid);
	    }
	    if($pass == TRUE && (CopyUtil::passCondition($baseId, BaseLevel::SIMPLE, $atkRet) == 'ok'))
	    {
	        MyTower::getInstance()->passSpecialTower($towerLv);
	    }
	    else
	    {
	        $pass = FALSE;
	        MyTower::getInstance()->addSpecailTowerAtkNum($towerLv);
	    }
	    if($pass || $fail)
	    {
	        $needExec = CopyUtil::getBaseNeedExec($baseId, BaseLevel::SIMPLE);
	        if(EnUser::getUserObj()->subExecution($needExec) == FALSE)
	        {
	            throw new FakeException('sub execution failed.');
	        }
	        EnActive::addTask(ActiveDef::TOWER);
	    }
	    Enuser::getUserObj()->update();
	    MyTower::getInstance()->save();
	    BagManager::getInstance()->getBag()->update();
	    AtkInfo::getInstance()->saveAtkInfo();
	    return array('pass'=>$pass);
	}
	
	public static function getSpecailBattleReward()
	{
	    $towerLv = TowerAtkInfo::getInstance()->getTowerLv();
	    $baseId = AtkInfo::getInstance()->getBaseId();
	    $ret = CopyUtil::getBasePassAward($baseId, BaseLevel::SIMPLE);
	    Logger::trace('getSpecailBattleReward baseid %d reward %s',$baseId,$ret);
	    return $ret;
	}
	
	public static function buyAtkNum($num, $type=1)
	{
	    $towerInst = MyTower::getInstance();
	    $userObj = EnUser::getUserObj();
	    
	    switch ( $type )
	    {
	        case HellTowerDef::TOWER_TYPE_NORMAL:
	            $buyNum = $towerInst->getBuyAtkNum();
	            $buyNumLimit = btstore_get()->VIP[$userObj->getVip()]['towerBuyNum'][0];
	            if($buyNum+$num > $buyNumLimit)
	            {
	                throw new FakeException('can not buy.current buynum is %d limit is %d,want to buy %d',$buyNum,$buyNumLimit,$num);
	            }
	            $initGold = btstore_get()->VIP[$userObj->getVip()]['towerBuyNum'][1];
	            $incGold = btstore_get()->VIP[$userObj->getVip()]['towerBuyNum'][2];
	            $needGold = 0;
	            for($i=0;$i<$num;$i++)
	            {
	               $needGold += ($initGold + ($buyNum + $i) * $incGold);
	            }
	            if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_BUY_TOWER_ATKNUM) == FALSE)
	            {
	               throw new FakeException('sub gold failed.');
	            }
	            $towerInst->addBuyAtkNum($num);
	            $towerInst->resetTowerByGold();
	            break;
	        case HellTowerDef::TOWER_TYPE_HELL:
	            $conf = btstore_get()->HELL_TOWER;
	            $buyNum = $towerInst->getHellBuyAtkNum();
	            $buyNumLimit = intval( $conf[HellTowerFloorDef::MAX_RESET_NUM] );
	            if ( $buyNum + $num > $buyNumLimit )
	            {
	                throw new FakeException('can not buy.current buynum is %d limit is %d,want to buy %d',$buyNum,$buyNumLimit,$num);
	            }
	            $needGold = intval( $conf[HellTowerFloorDef::BUY_RESET_GOLD] );
	            if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_BUY_HELLTOWER_ATKNUM) == FALSE)
	            {
	               throw new FakeException('sub gold failed.');
	            }
	            $towerInst->addHellBuyAtkNum($num);
	            $towerInst->resetHellTowerByGold();
	            break;
	        default:
	            throw new FakeException("unknown type:%d.", $type);
	            break;
	    }
	    $userObj->update();
	    $towerInst->save();
	}
	
	public static function buySpecialTower($uid,$num)
	{
	    $userObj = EnUser::getUserObj($uid);
	    $vip = $userObj->getVip();
	    $conf = btstore_get()->VIP[$vip]['openSpecailTower'];
	    $towerInst = MyTower::getInstance($uid);
	    $buyNum = $towerInst->getBuySpecialNum();
	    if (!isset($conf[$buyNum + $num])) 
	    {
	    	throw new FakeException('beyond limit num.current buynum:%d, want to buy %d', $buyNum, $num);
	    }
	    $spendNum = 0;
	    for ($i = 1; $i <= $num; $i++)
	    {
	    	$spendNum += $conf[$i + $buyNum];
	    }
        if($userObj->subGold($spendNum, StatisticsDef::ST_FUNCKEY_TOWER_BUY_STOWER) == FALSE)
        {
            throw new FakeException('sub gold failed');
        }
        $towerInst->addBuySpecialNum($num);
	    $maxTowerLv = $towerInst->getMaxLevel();
	    if($maxTowerLv < TowerDef::RAND_STOWER_NEED_TOWERLV)
	    {
	        throw new FakeException('rand towerlv need towerlv %d now is %d',
	                TowerDef::RAND_STOWER_NEED_TOWERLV,$maxTowerLv);
	    }
	    $firstTowerLv = TowerDef::FIRST_TOWER_LEVEL_ID;
	    for($i=0;$i<$num;$i++)
	    {
	        if($maxTowerLv > TowerDef::RAND_STOWER_MAXLV_DECREASE)
	        {
	            $maxTowerLv -= TowerDef::RAND_STOWER_MAXLV_DECREASE;
	        }
	        else
	        {
	            $maxTowerLv = $firstTowerLv;
	        }
	        $towerLv = rand($firstTowerLv,$maxTowerLv);
	        $samples = btstore_get()->TOWERLEVEL[$towerLv]['open_special_lv']->toArray();
	        $arrSpecailLv = Util::noBackSample($samples, 1);
	        if(count($arrSpecailLv) < 1)
	        {
	            Logger::fatal('why sample nothing.sample %s',$samples);
	            continue;
	        }
	        Logger::info('buy specail tower.maxtowerlv %d buy at tower lv %d specailtower %d',
	                $towerInst->getMaxLevel(),$towerLv,$arrSpecailLv[0]);
	        $towerInst->addSpecailTowerLv($arrSpecailLv[0]);
	    }
	    $userObj->update();
	    $towerInst->save();
	    return $towerInst->getTowerInfo();
	}
	
	public static function sweepByGold($uid,$endLv=0, $type=1)
	{
	    $inst = MyTower::getInstance($uid);
	    $userObj = EnUser::getUserObj($uid);
	    
	    switch ( $type )
	    {
	        case HellTowerDef::TOWER_TYPE_NORMAL:
	            $startLv = $inst->getCurLevel();
	            $maxLv = $inst->getMaxLevel();
	            if($endLv > $maxLv)
	            {
	                throw new FakeException('sweepByGold endlv %d maxlv %d',$endLv,$maxLv);
	            }
	            $sweepInfo = $inst->getSweepInfo();
	            if(empty($sweepInfo) && ($endLv == 0))
	            {
	                throw new FakeException('empty sweepinfo.but endlv is 0');
	            }
	            if(!empty($sweepInfo))
	            {
	                $endLv = $sweepInfo[TOWERTBL_FIELD::SWEEP_INFO_END_LEVEL];
	            }
	            if($startLv > $endLv)
	            {
	                throw new FakeException('sweepbygold startlv %d endlv %d',$startLv,$endLv);
	            }
	            $needGold = self::sweepNeedGold($startLv, $endLv, $sweepInfo);
	            if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_TOWER_SWEEP_BYGOLD) == FALSE)
	            {
	                throw new FakeException('sweepbygold.sub gold failed');
	            }
	            $levelNum = self::getLevelGap($startLv, $endLv);
	            $inst->endSweep($endLv, $levelNum);
	            self::randSpecialTowerLv($startLv, $levelNum, $uid);
	            $userObj->update();
	            $inst->save();
	            $reward = self::sendSweepReward($startLv, $levelNum, $uid);
	            if($levelNum > 0)
	            {
	                EnActive::addTask(ActiveDef::TOWER,$levelNum);
	                EnMission::doMission($uid, MissionType::TOWER, $levelNum);
	            }
	            break;
	        case HellTowerDef::TOWER_TYPE_HELL:
	            
	            $startLv = $inst->getCurHellLevel();
	            $maxLv = $inst->getHellMaxLevel();
	            if($endLv > $maxLv)
	            {
	                throw new FakeException('sweepByGold endlv %d maxlv %d',$endLv,$maxLv);
	            }
	            $sweepInfo = $inst->getHellSweepInfo();
	            if(empty($sweepInfo) && ($endLv == 0))
	            {
	                throw new FakeException('empty sweepinfo.but endlv is 0');
	            }
	            if(!empty($sweepInfo))
	            {
	                $endLv = $sweepInfo[TOWERTBL_FIELD::SWEEP_INFO_END_LEVEL];
	            }
	            if($startLv > $endLv)
	            {
	                throw new FakeException('sweepbygold startlv %d endlv %d',$startLv,$endLv);
	            }
	            $needGold = self::sweepHellNeedGold($startLv, $endLv, $sweepInfo);
	            if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_HELLTOWER_SWEEP_BY_GOLD) == FALSE)
	            {
	                throw new FakeException('sweepbygold.sub gold failed');
	            }
	            $levelNum = self::getHellLevelGap($startLv, $endLv);
	            $inst->endHellSweep($endLv, $levelNum);
	            $userObj->update();
	            $inst->save();
	            $reward = self::sendHellSweepReward($startLv, $levelNum, $uid, $endLv);
	            if($levelNum > 0)
	            {
	                EnActive::addTask(ActiveDef::HELL_TOWER,$levelNum);
	            }
	            break;
	        default:
	            throw new FakeException("unknown type:%d.", $type);
	            break;
	    }
	    
	    return $inst->getTowerInfo();
	}
	
	public static function sweepNeedGold($startLv,$endLv,$sweepInfo)
	{
	    if(!empty($sweepInfo))
	    {
	        $gap = TowerLogic::getSweepGap();
	        $startLv = $sweepInfo[TOWERTBL_FIELD::SWEEP_INFO_START_LEVEL];
	        $startTime = $sweepInfo[TOWERTBL_FIELD::SWEEP_INFO_START_TIME];
	        $endLv = $sweepInfo[TOWERTBL_FIELD::SWEEP_INFO_END_LEVEL];
	        $levelNum = intval((Util::getTime() - $startTime)/$gap);
	        $maxLvNum = self::getLevelGap($startLv, $endLv);
	        if($levelNum > $maxLvNum)
	        {
	            Logger::warning('sweepNeedGold.sweep should be ended sooner.start_time %d.'.
	                    'startlv %d.endlv %d.lvNum %d.sweepgap %d.now is %d.',
	                    $startTime,$startLv,$endLv,$levelNum,$gap,Util::getTime());
	            $levelNum = $maxLvNum;
	        }
	        $curLv = self::getEndLevelAfterSweep($startLv, $levelNum);
	        $startLv = $curLv;
	    }
	    $levelNum = self::getLevelGap($startLv, $endLv);
	    $goldPerLv = btstore_get()->TOWER['sweep_need_gold'];
	    return $goldPerLv * $levelNum;
	}
	
	/**
	 * 通关试炼塔层给其他模块（比如新服活动）的打点函数
	 */
	public static function informPassTowerTask($towerLv)
	{
		$uid = RPCContext::getInstance()->getUid();
		// 通知新服活动的通关试炼塔任务
		EnNewServerActivity::updatePassTower($uid, $towerLv);
	}
	
	/**
	 * 重置试炼塔给其他模块（比如新服活动）的打点函数
	 */
	public static function informResetTowerTask($restResetNum)
	{
		$uid = RPCContext::getInstance()->getUid();
		// 通知新服活动的通关试炼塔任务
		$confNum = TowerLogic::getDailyResetNum();
		$hadResetNum = $confNum - $restResetNum;
		EnNewServerActivity::updateResetTower($uid, $hadResetNum);
	}
	
	public static function getDailyHellResetNum()
	{
	    return intval( btstore_get()->HELL_TOWER[HellTowerFloorDef::NUM] );
	}
	
	public static function getDailyHellFailNum()
	{
	    return intval( btstore_get()->HELL_TOWER[HellTowerFloorDef::LOSE_NUM] );
	}
	
	public static function getShopInfo($uid)
	{
	    if ( FALSE == self::isHellTowerShopOpen($uid) )
	    {
	        return array(
	            'point' => 0,
	            'info' => array(),
	        );
	    }
	    
	    $myHellTowerShop = new TowerShop();
	    $hasBuyInfo = $myHellTowerShop->getShopInfo();
	    
	    $userObj = EnUser::getUserObj($uid);
	    $point = $userObj->getTowerNum();
	    
	    return array(
	        'point' => $point,
	        'info' => $hasBuyInfo,
	    );
	}
	
	public static function buy($uid, $id, $num=1)
	{
	    $myHellTowerShop = new TowerShop();
	    $myHellTowerShop->buy($id, $num);
	    return 'ok';
	}
	
	public static function isHellTowerShopOpen($uid)
	{
	    $userObj = EnUser::getUserObj($uid);
	    
	    $level = $userObj->getLevel();
	    $levelLimit = intval( btstore_get()->HELL_TOWER[HellTowerFloorDef::LEVEL] );
	    
	    if ( $level < $levelLimit )
	    {
	        return FALSE;
	    }
	    
	    return TRUE;
	}
	
    public static function getHellSweepGap()
    {
        return intval( btstore_get()->HELL_TOWER[HellTowerFloorDef::TIME] );
    }
    
    public static function getHellLevelGap($startLv,$endLv)
    {
        if($startLv > $endLv)
        {
            return 0;
        }
        $lvConf = btstore_get()->HELL_TOWER_LEVEL;
        $lvNum = 0;
        foreach($lvConf as $level => $conf)
        {
            if($level >= $startLv && ($level <= $endLv))
            {
                $lvNum++;
            }
        }
        return $lvNum;
    }
    
    private static function sendHellSweepReward($startLv,$lvNum,$uid, $endLv)
    {
        Logger::trace('sendHellSweepReward startlv %d lvNum %d.',$startLv,$lvNum);
        
        $reward = array();
        if( $lvNum <= 0 )
        {
            return $reward;
        }
        
        $conf = btstore_get()->HELL_TOWER_LEVEL;
        foreach($conf as $towerLv => $towerConf)
        {
            if($towerLv >= $startLv && $towerLv <= $endLv)
            {
                $eachReward = $towerConf[HellTowerLevelDef::REWARD];
                $reward = Util::arrayAdd3D($eachReward, $reward);
            }
        }
        
        RewardUtil::reward3DtoCenter($uid, array($reward), RewardSource::HELL_TOWER_SWEEP_REWARD);
        Logger::trace('sendSweepReward startlv %d lvNum %d.reward %s',$startLv,$lvNum,$reward);
        return $reward;
    }
    
    public static function doneHellBattle($atkRet)
    {
        Logger::trace('TowerLogic::doneBattle');
        $armyId	= $atkRet['uid2'];
        $brid = $atkRet['brid'];
        $pass = $atkRet['pass'];
        $fail = $atkRet['fail'];
        $towerLv = TowerAtkInfo::getInstance()->getTowerLv();
        $baseId = AtkInfo::getInstance()->getBaseId();
        $newTowerLvs = array();
        $uid = RPCContext::getInstance()->getUid();
        if ($fail == TRUE)
        {
            AtkInfo::getInstance()->setBasePrgOnDefeatArmy($armyId, ATK_INFO_ARMY_STATUS::DEFEAT_FAIL);
            MyTower::getInstance()->subHellCanFailNum();
        }
        else
        {
            AtkInfo::getInstance()->setBasePrgOnDefeatArmy($armyId, $brid);
        }
        if($pass == TRUE)
        {
            if(CopyUtil::passCondition($baseId, BaseLevel::SIMPLE, $atkRet) == 'ok')
            {
                $newTowerLvs = MyTower::getInstance()->passHellLevel($towerLv);
            }
            else
            {
                MyTower::getInstance()->subCanFailNum();
                Logger::info('sub can_fail_num.not satify the pass condition.');
            }
             
        }
        if($pass || $fail)
        {
            EnActive::addTask(ActiveDef::HELL_TOWER);
        }
        Enuser::getUserObj()->update();
        MyTower::getInstance()->save();
        BagManager::getInstance()->getBag()->update();
        AtkInfo::getInstance()->saveAtkInfo();
        $towerInfo = MyTower::getInstance()->getTowerInfo();
        if(empty($newTowerLvs))
        {
            return array('pass'=>FALSE);
        }
        return array('pass'=>TRUE);
    }
    
    public static function getHellBattleReward()
    {
        $towerLv = TowerAtkInfo::getInstance()->getTowerLv();
        $eachReward = btstore_get()->HELL_TOWER_LEVEL[$towerLv][HellTowerLevelDef::REWARD];
        
        $uid = RPCContext::getInstance()->getUid();
        
        $reward = array();
        foreach ( $eachReward as $info )
        {
            switch ( $info[0] )
            {
                case RewardConfType::HELL_TOWER:
                    if ( !isset( $reward['tower_num'] ) )
                    {
                        $reward['tower_num'] = 0;
                    }
                    $reward['tower_num'] += $info[2];
                    break;
                case RewardConfType::ITEM_MULTI:
                    if ( !isset( $reward['item'] ) )
                    {
                        $reward['item'] = array();
                    }
                    $itemIds = ItemManager::getInstance()->addItem($info[1],$info[2]);
                    $reward['item'] = array_merge($reward['item'], $itemIds);
                    break;
                default:
                    throw new InterException("unknown reward type:%d.", $info[0]);
                    break;
            }
        }
        return $reward;
    }
    
    private static function getHellEndLevelAfterSweep($startLv,$lvNum)
    {
        if($lvNum == 0)
        {
            return $startLv;
        }
        $lvConf = btstore_get()->HELL_TOWER_LEVEL;
        $towerLv = 0;
        foreach($lvConf as $level => $conf)
        {
            if($level >= $startLv)
            {
                $lvNum -= 1;
            }
            if($lvNum == 0)
            {
                return $level;
            }
        }
    }
    
    public static function sweepHellNeedGold($startLv,$endLv,$sweepInfo)
    {
        if(!empty($sweepInfo))
        {
            $gap = TowerLogic::getSweepGap();
            $startLv = $sweepInfo[TOWERTBL_FIELD::SWEEP_INFO_START_LEVEL];
            $startTime = $sweepInfo[TOWERTBL_FIELD::SWEEP_INFO_START_TIME];
            $endLv = $sweepInfo[TOWERTBL_FIELD::SWEEP_INFO_END_LEVEL];
            $levelNum = intval((Util::getTime() - $startTime)/$gap);
            $maxLvNum = self::getHellLevelGap($startLv, $endLv);
            if($levelNum > $maxLvNum)
            {
                Logger::warning('sweepNeedGold.sweep should be ended sooner.start_time %d.'.
                    'startlv %d.endlv %d.lvNum %d.sweepgap %d.now is %d.',
                    $startTime,$startLv,$endLv,$levelNum,$gap,Util::getTime());
                    $levelNum = $maxLvNum;
            }
            $curLv = self::getHellEndLevelAfterSweep($startLv, $levelNum);
            $startLv = $curLv;
        }
        $levelNum = self::getHellLevelGap($startLv, $endLv);
        $goldPerLv = btstore_get()->HELL_TOWER[HellTowerFloorDef::SWEEP_GOLD];
        return $goldPerLv * $levelNum;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */