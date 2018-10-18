<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RobTombLogic.class.php 202938 2015-10-17 10:46:51Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/robtomb/RobTombLogic.class.php $
 * @author $Author: wuqilin $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-10-17 10:46:51 +0000 (Sat, 17 Oct 2015) $
 * @version $Revision: 202938 $
 * @brief 
 *  
 **/
class RobTombLogic
{
    
    public static function getRobInfo($uid)
    {
        if(EnActivity::isOpen(ActivityName::ROB_TOMB) == FALSE)
        {
            throw new FakeException('robtomb act is not open.');
        }
        $userLv = EnUser::getUserObj($uid)->getLevel();
        if($userLv < self::getActNeedLevel())
        {
            throw new FakeException('this user %d cur lv %d,act need lv %d.',$uid,$userLv,self::getActNeedLevel());
        }
        $myRob = MyRobTomb::getInstance($uid);
        $robInfo = $myRob->getRobInfo();
        $myRob->save();
        return $robInfo;
    }
    
    public static function rob($num,$robType,$uid)
    {
        if($num > RobTombDef::ROB_MAX_NUM)
        {
            throw new FakeException('rob num %d is max than maxnum %d.',$num,RobTombDef::ROB_MAX_NUM);
        }
        if(EnActivity::isOpen(ActivityName::ROB_TOMB) == FALSE)
        {
            throw new FakeException('robtomb act is not open.');
        }
        if(self::canUserRob($uid) == FALSE)
        {
            throw new FakeException('can not rob');
        }
        //TODO: $num==1  是否只有num=1时优先使用免费次数
        if($robType == RobTombDef::ROB_TYPE_GOLD && ($num == 1) 
                && (self::hasRobNum($uid, RobTombDef::ROB_TYPE_FREE)))
        {
            throw new FakeException('has free rob num.rob num is 1,but you want to user gold rob.');
        }
        if(self::hasRobNum($uid, $robType, $num) == FALSE)
        {
            throw new FakeException('user %d has no enough rob num %d of robtype %d.',$uid,$num,$robType);
        }
        $robRet = array();
        $robInst = MyRobTomb::getInstance($uid);
        $bag = BagManager::getInstance()->getBag($uid);
        $heroMng = EnUser::getUserObj($uid)->getHeroManager();
        $ret = array();
        for($i=0;$i<$num;$i++)
        {
            $robOnceRet = self::robOnce($robType,$uid);
            foreach($robOnceRet as $dropType => $dropInfo)
            {
                if(count($dropInfo) != 1)
                {
                    throw new FakeException('drop error.dropinfo is %s.',$robOnceRet);
                }
                if($dropType != DropDef::DROP_TYPE_HERO &&
                        ($dropType != DropDef::DROP_TYPE_ITEM) &&
                        ($dropType != DropDef::DROP_TYPE_TREASFRAG))
                {
                    throw new FakeException('drop type %d is not supported.',$dropType);
                }
                $dropStrType = DropDef::$DROP_TYPE_TO_STRTYPE[$dropType];
                foreach($dropInfo as $tmplId => $tmplNum)
                {
                    if(!isset($robRet[$dropType][$tmplId]))
                    {
                        $robRet[$dropType][$tmplId] = 0;
                    }
                    $robRet[$dropType][$tmplId] += $tmplNum;
                    if(!isset($ret[$i][$dropStrType][$tmplId]))
                    {
                        $ret[$i][$dropStrType][$tmplId] = 0;
                    }
                    $ret[$i][$dropStrType][$tmplId] += $tmplNum;
                }
            }
        }
        foreach($robRet as $dropType => $dropInfo)
        {
            if($dropType == DropDef::DROP_TYPE_HERO)
            {
                $heroMng->addNewHeroes($dropInfo);
            }
            else if($dropType == DropDef::DROP_TYPE_ITEM)
            {
                $bag->addItemsByTemplateID($dropInfo,TRUE);
            }
            else if($dropType == DropDef::DROP_TYPE_TREASFRAG)
            {
                EnFragseize::addTreaFrag($uid, $dropInfo);
            }
        }
        $robInst->save();
        EnUser::getUserObj($uid)->update();
        $bag->update();
        return $ret;
    }
    
    /**
     * @param unknown_type $uid
     * @throws ConfigException
     */
    private static function robOnce($robType,$uid)
    {
        $robInst = MyRobTomb::getInstance($uid);
        if(self::hasRobNum($uid, $robType) == FALSE)
        {
            throw new FakeException('no rob num of type %d.',$robType);
        }
        //优先使用免费次数时，先判断一下，实际使用哪种类型
        if ($robType == RobTombDef::ROB_TYPE_PRI_FREE)
        {
        	$totalFreeNum = self::getFreeNumByUid($uid);
        	$freeNum = $robInst->getFreeRobNum ();
        	if ($freeNum < $totalFreeNum)
        	{
        		$robType = RobTombDef::ROB_TYPE_FREE;
        	}
        	else
        	{
        		$robType = RobTombDef::ROB_TYPE_GOLD;
        	}
        	Logger::debug("pri_free rob, freeNum:%d, totalFree:%d, realType:%d", $freeNum, $totalFreeNum, $robType);
        }
        $robNum = 0;
        if($robType == RobTombDef::ROB_TYPE_FREE)
        {
            $robInst->robFree(1);
            $robNum = $robInst->getFreeRobNum();
        }
        else if($robType == RobTombDef::ROB_TYPE_GOLD)
        {
            $robInst->robGold(1);
            $robNum = $robInst->getGoldRobNum();
            $needGold = self::getRobNeedGold();
            if(EnUser::getUserObj($uid)->subGold($needGold, StatisticsDef::ST_FUNCKEY_ROB_TOMB_GOLDROB) == FALSE)
            {
                throw new FakeException('gold rob tomb failed.');
            }
        }
        else
        {
            throw new FakeException('no such rob type %d.',$robType);
        }
        $arrDropId = self::getArrDropId($robType,$uid);
        if(empty($arrDropId))
        {
            throw new FakeException('why drop id is empty array');
        }
        $arrDropId = self::excludeBlackDropId($arrDropId, $uid);
        if(empty($arrDropId))
        {
            throw new ConfigException('after exclude black dropid,the drop array is empty.');
        }
        $arrDropdeId = Util::noBackSample($arrDropId, 1);
        if(empty($arrDropdeId))
        {
            throw new FakeException('drop no drop id.arrDropInfo %s.',$arrDropId);
        }
        $dropId = $arrDropdeId[0];
        $arrDropLimit = self::getArrDropLimit();
        if(isset($arrDropLimit[$dropId]))
        {
            $robInst->addDropToBlackList($dropId, 1);
        }
        $arrDropGot = Drop::dropMixed($dropId);
        if(count($arrDropGot) != 1)
        {
            throw new FakeException('drop error.drop info is %s.',$arrDropGot);
        }
        Logger::info('user %d robonce.robtype:%d robnum %d dropid %d dropGot %s',
                $uid,$robType,$robNum,$dropId,$arrDropGot);
        return $arrDropGot;
    }
    
    private static function getArrDropId($robType,$uid)
    {
        if($robType == RobTombDef::ROB_TYPE_FREE)
        {
            return self::getArrFreeDropId();
        }
        else if($robType == RobTombDef::ROB_TYPE_GOLD)
        {
            $goldRobNum = MyRobTomb::getInstance($uid)->getAccumGoldRobNum();
            $arrAccumNum = self::getArrAccumRobNum();
            if(!empty($arrAccumNum))
            {
                $lastAccumNum = self::getLastAccumRobNum();
                $lastIncAccumNum = self::getLastIncAccumRobNum();
                if(ShopLogic::inSpecialSerial($goldRobNum, $arrAccumNum))
                {
                    Logger::info('goldRobNum %d user accum drop id %s.',$goldRobNum,self::getArrAccumDropId());
                    return self::getArrAccumDropId();
                }
            }
            return self::getArrGoldDropId();
        }
        return array();
    }
    
    private static function canUserRob($uid)
    {
        $userLv = EnUser::getUserObj($uid)->getLevel();
        if($userLv < self::getActNeedLevel())
        {
            Logger::warning('this user %d cur lv %d,act need lv %d.',$uid,$userLv,self::getActNeedLevel());
            return FALSE;
        }
        if(BagManager::getInstance()->getBag($uid)->isFull())
        {
            Logger::warning('user bag is full,can not rob.');
            return FALSE;
        }
        return TRUE;
    }
    
    
    public static function hasRobNum($uid,$robType,$num=1)
    {
        if($robType == RobTombDef::ROB_TYPE_FREE)
        {
            $totalFreeNum = self::getFreeNumByUid($uid);
            $robInst = MyRobTomb::getInstance($uid);
            $freeRobNum = $robInst->getFreeRobNum();
            if($freeRobNum+$num <= $totalFreeNum)
            {
                return TRUE;
            }
            return FALSE;
        }
        else if($robType == RobTombDef::ROB_TYPE_GOLD)
        {
            $totalGoldNum = self::getGoldNumByUid($uid);
            $robInst = MyRobTomb::getInstance($uid);
            $goldRobNum = $robInst->getGoldRobNum();
            if($goldRobNum+$num <= $totalGoldNum)
            {
                return TRUE;
            }
            return FALSE;
        }
        else if($robType == RobTombDef::ROB_TYPE_PRI_FREE)
        {
        	$totalFreeNum = self::getFreeNumByUid ( $uid );
        	$totalGoldNum = self::getGoldNumByUid ( $uid );
        	$robInst = MyRobTomb::getInstance ( $uid );
        	$freeRobNum = $robInst->getFreeRobNum (); // 免费次数
        	$goldRobNum = $robInst->getGoldRobNum (); // 金币次数
        	if (($freeRobNum + $goldRobNum + $num) <= ($totalFreeNum + $totalGoldNum)) 
        	{
        		return TRUE;
        	}
        }
        else
        {
        	throw new FakeException('invalid robType:%d',$robType); 
        }
    }
    
    public static function getActStartTime()
    {
        $ret = EnActivity::getConfByName(ActivityName::ROB_TOMB);
        return $ret['start_time'];
    }
    
    public static function getActEndTime()
    {
        $ret = EnActivity::getConfByName(ActivityName::ROB_TOMB);
        return $ret['end_time'];
    }
    
    public static function getFreeNumByUid($uid)
    {
        $vip = Enuser::getUserObj($uid)->getVip();
        $num = btstore_get()->VIP[$vip]['robTombFreeNum'];
        return $num;
    }
    
    public static function getGoldNumByUid($uid)
    {
        $vip = Enuser::getUserObj($uid)->getVip();
        $num = btstore_get()->VIP[$vip]['robTombGoldNum'];
        return $num;
    }
    
    public static function getActNeedLevel()
    {
        $ret = EnActivity::getConfByName(ActivityName::ROB_TOMB);
        return $ret['data'][RobTombDef::BTSTORE_ROB_NEED_LEVEL];
    }
    
    private static function getArrFreeDropId()
    {
        $ret = EnActivity::getConfByName(ActivityName::ROB_TOMB);
        return $ret['data'][RobTombDef::BTSTORE_FREE_DROP_ID];
    }
    
    private static function getArrGoldDropId()
    {
        $ret = EnActivity::getConfByName(ActivityName::ROB_TOMB);
        return $ret['data'][RobTombDef::BTSTORE_GOLD_DROP_ID];
    }
    
    public static function getArrAccumDropId()
    {
        $ret = EnActivity::getConfByName(ActivityName::ROB_TOMB);
        return $ret['data'][RobTombDef::BTSTORE_ACCUM_DROP_ID];
    }
    
    public static function getArrAccumRobNum()
    {
        $ret = EnActivity::getConfByName(ActivityName::ROB_TOMB);
        return $ret['data'][RobTombDef::BTSTORE_ACCUM_NUM];
    }
    
    public static function getLastAccumRobNum()
    {
        $ret = EnActivity::getConfByName(ActivityName::ROB_TOMB);
        return $ret['data'][RobTombDef::BTSTORE_LAST_ACCUMNUM];
    }
    
    public static function getLastIncAccumRobNum()
    {
        $ret = EnActivity::getConfByName(ActivityName::ROB_TOMB);
        return $ret['data'][RobTombDef::BTSTORE_LAST_INC_ACCUMNUM];
    }
    
    public static function excludeBlackDropId($arrDropId,$uid)
    {
        $arrDropLimit = self::getArrDropLimit();
        //根据掉落表使用次数和配置的黑名单   找出需要排除的掉落表  并排除
        $blackList = MyRobTomb::getInstance($uid)->getBlackList();
        foreach($blackList as $dropId => $dropNum)
        {
            if(!isset($arrDropLimit[$dropId]))
            {
                continue;
            }
            if($dropNum == $arrDropLimit[$dropId])
            {
                $arrExclude[] = $dropId;
                if(isset($arrDropId[$dropId]))
                {
                    unset($arrDropId[$dropId]);
                }
            }
            else if($dropNum > $arrDropLimit[$dropId])
            {
                Logger::warning('this dropid %d is used %d (max than limit %d).',$dropId,$dropNum,$arrDropLimit[$dropId]);
                continue;
            }
        }
        return $arrDropId;
    }    
    
    public static function getArrDropLimit()
    {
        $ret = EnActivity::getConfByName(ActivityName::ROB_TOMB);
        return $ret['data'][RobTombDef::BTSTORE_DROPID_LIMIT];
    }
    public static function getRobNeedGold()
    {
        $ret = EnActivity::getConfByName(ActivityName::ROB_TOMB);
        return $ret['data'][RobTombDef::BTSTORE_ROB_NEED_GOLD];
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */