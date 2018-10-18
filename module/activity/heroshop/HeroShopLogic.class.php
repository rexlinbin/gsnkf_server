<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HeroShopLogic.class.php 259698 2016-08-31 08:07:55Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/heroshop/HeroShopLogic.class.php $
 * @author $Author: BaoguoMeng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-31 08:07:55 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259698 $
 * @brief 
 *  
 **/
class HeroShopLogic 
{
    public static function isHeroShopOpen()
    {
        $user = EnUser::getUserObj();
        if($user->getLevel() >= HeroShopDef::HEROSHOP_OPEN_LEVEL)
        {
            return TRUE;
        }
        return FALSE;
    }
    
    
    public static function getMyShopInfo ()
    {
        if(self::isHeroShopOpen() == FALSE)
        {
            throw new FakeException('heroshop is not open for this user whose level is %d.',EnUser::getUserObj()->getLevel());
        }
        if(EnActivity::isOpen(ActivityName::HERO_SHOP) == FALSE 
                && (self::isDuringActivityDelay() == FALSE))
        {
            throw new FakeException('can not enter heroshop.the time %d is not in acttime or delay time.',Util::getTime());
        }
        $rankInfoInMc = RankInfo::getInstance()->getRankInfoFromMc();
        $rfrMc = FALSE;
        if(empty($rankInfoInMc))
        {
            $rfrMc = TRUE;
        }
        $shopInst = MyHeroShop::getInstance();
        $shopInfo = $shopInst->getShopInfo();
        $shopInst->save();
        if($rfrMc)
        {
            Logger::warning('getMyShopInfo no memcache info or new user or new user in this round.refresh');
            RPCContext::getInstance()->executeTask(SPECIAL_UID::RFR_HEROSHOPINFO_INMC, 'heroshop.refreshRank', array($shopInfo['score']));
        }
        return self::resetRankInfo4Front($shopInfo);
    }
    /**
     * 根据rankinfo获取当前用户的排名，如果此用户不在rankinfo中，根据此用户的积分、积分时间、uid取其排名
     * @param array $shopInfo
     * @return array
     */
    private static function resetRankInfo4Front($shopInfo)
    {
        $rankInfo = RankInfo::getInstance()->getRankInfo();
        ksort($rankInfo);
        $uid = $shopInfo['uid'];
        $score = $shopInfo['score'];
        $scoreTime = $shopInfo['score_time'];
        $rankIndex = 0;
        $change = TRUE;
        //查找此用户是否在rankinfo列表中
        foreach($rankInfo as $rank => $userInfo)
        {
            if($userInfo['uid'] == $uid)
            {
                if($userInfo['score'] == $score && ($userInfo['score_time'] == $scoreTime))
                {
                    $change = FALSE;
                }
                $rankIndex = $rank;
                break;
            }
        }
        //更新当前用户在排名中的顺序的条件：（三个条件同时满足）
        //1.积分改变了 $changed($change=true表示当前用户在列表中积分增加了或者是非排名列表的用户)
        //2.排名列表小于RANK_SIZE 或者   当前用户积分大于排名列表最小积分
        //3.用户积分大于0
        if($change 
                && (count($rankInfo) < self::getSizeOfRealTimeRank() 
                        || ($score > $rankInfo[count($rankInfo)]['score'])) 
                && ($score > 0))
        {
            $userObj = EnUser::getUserObj($uid);
            $userInfo = array(
                    HeroShopDef::SQL_FIELD_UID => $uid,
                    'uname' => $userObj->getUname(),
                    HeroShopDef::SQL_FIELD_SCORE => $score,
                    HeroShopDef::SQL_FIELD_SCORE_TIME => $scoreTime);
            //如果当前用户没在rankinfo列表中 插入到列表中的条件：（满足一个即可）
            //1.排名列表小于RANK_SIZE
            //2.当前用户积分小于排名中最小积分
            if(empty($rankIndex) 
                    && ((count($rankInfo) < self::getSizeOfRealTimeRank()) 
                            || ($score >= $rankInfo[count($rankInfo)]['score'])))
            {
                if(count($rankInfo) == self::getSizeOfRealTimeRank())
                {
                    $rankIndex = count($rankInfo);
                    $rankInfo[count($rankInfo)] = $userInfo;
                }
                else if(count($rankInfo) < self::getSizeOfRealTimeRank())
                {
                    $rankIndex = count($rankInfo)+1;
                    $rankInfo[count($rankInfo)+1] = $userInfo;
                }
                else
                {
                    Logger::fatal('the size of rank is lagger than %d. rankinfo is %s.',
                            self::getSizeOfRealTimeRank(),$rankInfo);
                }
            }
            else if(!empty($rankIndex))
            {
                $rankInfo[$rankIndex] = $userInfo;
            }
            //将当前用户插入到排名列表中之后  重新进行排名
            $newRankInfo = $rankInfo;
            for($i=$rankIndex-1;$i>0;$i--)
            {
                $userInfo = $rankInfo[$i];
                if($score > $userInfo['score'] ||
                        ($userInfo['score'] == $score && ($scoreTime < $userInfo['score_time'])) ||
                        ($userInfo['score'] == $score && ($scoreTime == $userInfo['score_time']) && ($uid < $userInfo['uid'])))
                {
                    $tmp = $rankInfo[$i];
                    $rankInfo[$i] = $rankInfo[$i+1];
                    $rankInfo[$i+1] = $tmp;
                }
                else
                {
                    break;
                }
            }
        }
        $userRank = 0;
        foreach($rankInfo as $rank => $userInfo)
        {
            if($userInfo['uid'] == $uid)
            {
                $userRank = $rank;
            }
            $rankInfo[$rank]['rank'] =$rank;
        }
        if(empty($userRank))
        {
            $userRank = self::getUserRank($uid);
        }
        return array(
                'rank_info'=>$rankInfo,
                'rank'=>$userRank,
                'shop_info'=>$shopInfo
                );
    }
    
    private static function isDuringActivityDelay()
    {
        $endTime = self::getActEndTime();
        $delay = self::getDelayAfterEnd();
        if(Util::getTime() < $endTime || (Util::getTime() > $endTime+$delay))
        {
            return FALSE;
        }
        if(strtotime(GameConf::SERVER_OPEN_YMD.GameConf::SERVER_OPEN_TIME) 
                > self::getActNeedOpenTime())
        {
            return FALSE;
        }
        return TRUE;
    }
    
    public static function getConfFreeCd()
    {
        $actConf = EnActivity::getConfByName(ActivityName::HERO_SHOP);
        return $actConf['data'][HeroShopBtstore::BT_FREE_BUY_CD];
    }
    
    public static function buyHero ($type)
    {
        if(self::isHeroShopOpen() == FALSE)
        {
            throw new FakeException('heroshop is not open for this user whose level is %d.',EnUser::getUserObj()->getLevel());
        }
        if(EnActivity::isOpen(ActivityName::HERO_SHOP) == FALSE)
        {
            throw new FakeException('now %d is not during act time.',Util::getTime());
        }
        $ret = array();
        $shopInst = MyHeroShop::getInstance();
        $preScore = $shopInst->getScore();
        $userObj = EnUser::getUserObj();
        $actConf = EnActivity::getConfByName(ActivityName::HERO_SHOP);
        if($type == HeroShopDef::BUY_HERO_TYPE_FREE)
        {
            self::buyHeroFreely($actConf);
        }   
        else if($type == HeroShopDef::BUY_HERO_TYPE_GOLD)
        {
            self::buyHeroByGold($actConf);
        }   
        else if($type == HeroShopDef::BUY_HERO_TYPE_GOLD_FREE)
        {
           self::buyHeroByFreeGold($actConf); 
        }  
        else
        {
            throw new FakeException('error params.type is %s.',$type);
        }
        $shopInst->addGoldBuyNum(1);
        $dropId = self::getDropId($type);
        if(!empty($dropId))
        {
            $arrDrop    =    Drop::dropMixed($dropId);
            if(count($arrDrop[DropDef::DROP_TYPE_HERO]) != 1 || (current($arrDrop[DropDef::DROP_TYPE_HERO]) != 1))
            {
                throw new FakeException('drop one more hero.dropInfo %s.',$arrDrop);
            }
            $arrHtid = array_keys($arrDrop[DropDef::DROP_TYPE_HERO]);
            $ret['htid'] = $arrHtid[0];
            $heroMng = $userObj->getHeroManager();
            $ret['hid'] = $heroMng->addNewHero($ret['htid']);
        }
        else
        {
            Logger::fatal('no drop id.');
        }
        $score = $shopInst->getScore();  
        $scoreGap = $actConf['data'][HeroShopBtstore::BT_PRESCORE_GET_FREENUM];
        if((!empty($scoreGap)) && (($score > $preScore) && ($score/$scoreGap >= 1) && ($score % $scoreGap == 0)))
        {
            $shopInst->addFreeNum();
        }        
        $userObj->update();
        $shopInst->save();
        $scoreInMc = RankInfo::getInstance()->getMinScore();
        //请求刷新积分排名
        if($score >= $scoreInMc)
        {
            Logger::trace('executeTask to refresh rank in memcache.uid %d score %d scoreinmc %d.',$userObj->getUid(),$score,$scoreInMc);
            RPCContext::getInstance()->executeTask(SPECIAL_UID::RFR_HEROSHOPINFO_INMC, 'heroshop.refreshRank', array($score));
        }
        return ($ret + self::resetRankInfo4Front($shopInst->getShopInfo()));
    }
    
    private static function buyHeroFreely($actConf)
    {
        $shopInst = MyHeroShop::getInstance();
        if($shopInst->getFreeCd() > Util::getTime())
        {
            throw new FakeException('can not buy hero freely.not in freecd.');
        }
        $shopInst->resetFreeCd();
        $shopInst->addScore($actConf['data'][HeroShopBtstore::BT_FREE_GETSCORE]);
    }
    
    private static function buyHeroByGold($actConf)
    {
        $userObj = EnUser::getUserObj();
        $shopInst = MyHeroShop::getInstance();
        $needGold = $actConf['data'][HeroShopBtstore::BT_GOLDBUY_NEEDGOLD];
        if(($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_HEROSHOP_GOLD_BUY)==FALSE))
        {
            throw new FakeException("gold buy hero.not enough gold num.");
        }
        $shopInst->addScore($actConf['data'][HeroShopBtstore::BT_GOLD_GETSCORE]);
    }
    
    private static function buyHeroByFreeGold($actConf)
    {
        $shopInst = MyHeroShop::getInstance();
        $shopInst->addScore($actConf['data'][HeroShopBtstore::BT_GOLD_GETSCORE]);
        if($shopInst->subFreeNum() == FALSE)
        {
            throw new FakeException('no free num');
        }
    }
    
    private static function getDropId($type)
    {
        $actConf = EnActivity::getConfByName(ActivityName::HERO_SHOP);
        $shopId = $actConf['data'][HeroShopBtstore::BT_GOLD_BUY_SHOP_ID];
        if($type == HeroShopDef::BUY_HERO_TYPE_FREE)
        {
            $shopId = $actConf['data'][HeroShopBtstore::BT_FREE_BUY_SHOP_ID];
        }
        $shopInst = MyHeroShop::getInstance();
        $buyNum = $shopInst->getBuyNum();
        $specailBuyNum = $shopInst->getSpecailBuyNum();
        $specialNumA = btstore_get()->SHOP[$shopId]['recruit_special_num']->toArray();
        $specialNumB = btstore_get()->SHOP[$shopId]['recruit_special_serial']->toArray();
        $specailCParam = btstore_get()->SHOP[$shopId][ShopDef::RECRUIT_ANOTHER_SERIAL]->toArray();
        if(isset($specailCParam[$specailBuyNum]) && (Util::isSpecialDrop($specailCParam[$specailBuyNum], $buyNum)))
        {
            $dropC = btstore_get()->SHOP[$shopId][ShopDef::RECRUIT_ANOTHER_DROP];
            Logger::info('buyHero getDropId %d is spcecail C.buy num %d specail C use num %d',$dropC,$buyNum,$specailBuyNum+1);
            $shopInst->addSpecialBuyNum(1);//使用C掉落表一次
            return $dropC;
        }
//         if($specailBuyNum == 0 && (Util::isSpecialDrop($specailCParam, $buyNum)))
//         {
//             $dropC = btstore_get()->SHOP[$shopId][ShopDef::RECRUIT_ANOTHER_DROP];
//             Logger::info('buyHero getDropId %d is spcecail C.buy num %d specail num %d',$dropC,$buyNum,$specailBuyNum);
//             $shopInst->addSpecialBuyNum(1);//使用C掉落表一次
//             return $dropC;
//         }
        else if(ShopLogic::inSpecialSerial($buyNum, $specialNumA) || 
                (ShopLogic::inSpecialSerial($buyNum, $specialNumB)))
        {
            $specailDrop = btstore_get()->SHOP[$shopId]['recruit_special_drop'][0];
            Logger::trace('buyHero getDropId specail %d.',$specailDrop);
            return $specailDrop;
        }
        if($type == HeroShopDef::BUY_HERO_TYPE_FREE)
        {
            $dropId = btstore_get()->SHOP[$shopId]['recruit_default_free'][0];
            Logger::trace('buyHero getDropId free %d.',$dropId);
            return $dropId;
        }
        $arrGoldDrop = btstore_get()->SHOP[$shopId]['recruit_default_gold']->toArray();
        if(empty($arrGoldDrop))
        {
            return 0;
        }
        $goldDrop = btstore_get()->SHOP[$shopId]['recruit_default_gold'][0];
        Logger::trace('buyHero getDropId gold %d.',$goldDrop);
        return $goldDrop;
    }
    
    /**
     * 
     * @param int $uid
     * @param int $score
     */
    public static function refreshRank($score)
    {
        $rankInfo = RankInfo::getInstance()->getRankInfo();
        ksort($rankInfo);
        //下面两种情况刷新：（保证buyHero操作用户积分是0的情况下，不会触发刷新）
        //1.score是0   这是memcache重启之后激发的刷新（getMyShopInfo触发）
        //2.当前的排名列表小于RANK_SIZE，并且score>排名列表中的最小积分
        if((!empty($score)) && (count($rankInfo) >= self::getSizeOfRealTimeRank()) && 
                ($score < $rankInfo[count($rankInfo)]['score']))
        {
            return;
        }
        //更新DB中的rankinfo到memcache
        RankInfo::getInstance()->rfrRankInfoDbToMc();
        $rankInfo = RankInfo::getInstance()->getRankInfo();
        $rfrTime = RankInfo::getInstance()->getBroadcastTime();
        if(($rfrTime + HeroShopDef::RFR_RANK_INFO_TIMEGAP < Util::getTime()))
        {
            foreach($rankInfo as $rank => $userInfo)
            {
                $rankInfo[$rank]['rank'] =$rank;
            }
            RankInfo::getInstance()->setBroadcastTime(Util::getTime());
            RPCContext::getInstance()->sendFilterMessage('arena', SPECIAL_ARENA_ID::HERO_SHOP,
                    PushInterfaceDef::HERO_SHOP_RFR_RANK, $rankInfo);
            //保证只有在用户积分变化的时候checkTimer
            if(!empty($score) && (EnActivity::isOpen(ActivityName::HERO_SHOP)))
            {
                self::checkRewardTimer();
            }
        }
        RankInfo::getInstance()->saveInfoToMc();
    }
    
    public static function checkRewardTimer()
    {
        Logger::trace('checkRewardTimer');
        if(EnActivity::isOpen(ActivityName::HERO_SHOP) == FALSE)
        {
            throw new FakeException('HeroShopLogic.checkRewardTimer act heroshop is not open or now is not in delay time.');
        }
        $taskName = 'heroshop.rewardUserOnActClose';
        $endTime = self::getActEndTime()+HeroShopDef::HEROSHOP_REWARDTIMER_DELAY;
        $ret = EnTimer::getArrTaskByName($taskName,array(TimerStatus::RETRY,TimerStatus::UNDO), 0);
        $findValid = FALSE;
        foreach($ret as $index => $timer)
        {
            if($timer['status'] == TimerStatus::RETRY)
            {
                Logger::fatal('the timer %d is retry.but the activity not end.',$timer['tid']);
                TimerTask::cancelTask($timer['tid']);
                continue;
            }
            if($timer['status'] == TimerStatus::UNDO)
            {
                if($timer['execute_time'] != $endTime)
                {
                    Logger::fatal('invalid timer %d.execute_time %d',$timer['tid'],$timer['execute_time']);
                    TimerTask::cancelTask($timer['tid']);
                }
                else if($findValid)
                {
                    Logger::fatal('one more valid timer.timer %d.',$timer['tid']);
                    TimerTask::cancelTask($timer['tid']);
                }
                else
                {
                    Logger::trace('checkRewardTimer findvalid');
                    $findValid = TRUE;
                }
            }
        }
        if($findValid == FALSE)
        {
            Logger::fatal('no valid timer.addTask for heroshop.rewardUserOnActClose.');
            TimerTask::addTask(SPECIAL_UID::RFR_HEROSHOPINFO_INMC,
                     $endTime, $taskName, array());
        }
    }
    
    public static function rewardUser()
    {
        try
        {
            $arrRewardConf = EnActivity::getConfByName(ActivityName::HEROSHOP_REWARD);
            $heroShopConf = EnActivity::getConfByName(ActivityName::HERO_SHOP);
            $rewardTblId = $heroShopConf['data'][HeroShopBtstore::BT_REWARDTBL_ID];
            $startTime = self::getActStartTime();
            $endTime = self::getActEndTime();
            if(Util::getTime() < $endTime)
            {
                throw new InterException('My God.reward time %d is smaller than activity end time %d.',Util::getTime(),$endTime);
            }
            if(empty($rewardTblId))
            {
                throw new ConfigException('empty rewardTblid.conf data is %s.',$heroShopConf);
            }
            if(!isset($arrRewardConf['data'][$rewardTblId]))
            {
                throw new ConfigException('no reward tbl id %d in rewardconftbl %s.',$rewardTblId,$arrRewardConf);
            }
            $rewardConf = $arrRewardConf['data'][$rewardTblId];
            $mergeServerRatio = self::getMergeServerRatio();
            $tryNum = 1;
            $arrUserInfo = array();
            while($tryNum <= HeroShopDef::REWARD_TRY_NUM)
            {
                try
                {
                    $arrUserInfo = HeroShopDao::getShopInfoByScoreAndRank(
                            $rewardConf['min_score'],
                            $rewardConf['max_rank'] * $mergeServerRatio,
                            self::getActStartTime());
                }
                catch(Exception $e)
                {
                    Logger::warning('HeroShopDao::getShopInfoByScoreAndRank fail for try %d',$tryNum);
                    if($tryNum == HeroShopDef::REWARD_TRY_NUM)
                    {
                        throw $e;
                    }
                    $tryNum++;
                    continue;
                }
                Logger::warning('HeroShopDao::getShopInfoByScoreAndRank success for try %d',$tryNum);
                break;
            }
            foreach($arrUserInfo as $index => $userInfo)
            {
                $uid = $userInfo[HeroShopDef::SQL_FIELD_UID];
                $rewardTime = $userInfo[HeroShopDef::SQL_FIELD_REWARD_TIME];
                $scoreTime = $userInfo[HeroShopDef::SQL_FIELD_SCORE_TIME];
                $score = $userInfo[HeroShopDef::SQL_FIELD_SCORE];
                if($scoreTime < $startTime || ($rewardTime > $startTime))
                {
                    Logger::warning('this user %d can not participate in this activity.score_time %d start_time %s.reward_time %d',$uid,$scoreTime,$startTime,$rewardTime);
                    continue;
                }
                if($score < 1)
                {
                    continue;
                }
                $rank = intval($index/$mergeServerRatio) + 1;
                $scoreReward = self::getRewardByScore($score, $rewardConf);
                $rankReward = self::getRewardByRank($rank, $rewardConf);
                try 
                {
                    $retScore = self::sendToRewardCenter($uid, $scoreReward, RewardSource::HERO_SHOP_INTEGRAL,$score);
                    $retRank = self::sendToRewardCenter($uid, $rankReward, RewardSource::HERO_SHOP_RANK,$index+1);
                    $userInfo[HeroShopDef::SQL_FIELD_REWARD_TIME] = Util::getTime();
                    HeroShopDao::updatePartShopInfo($uid, $userInfo);
                    Logger::info('HeroShop.rewardUser success for user.uid %d score %d rewardrank %d rank %d.scoreReward %s ,rankReward %s.',$userInfo['uid'],$score,$rank,$index+1,$retScore,$retRank);
                }
                catch(Exception $e)
                {
                    Logger::fatal('HeroShop.rewardUser fail for user.uid %d score %d rewardrank %d rank %d.',$userInfo['uid'],$score,$rank,$index+1);
                }
            }
        }
        catch(Exception $e)
        {
            Logger::fatal('HeroShopLogic.rewardUser failed.throw exeception,message is %s.',$e->getMessage());
            throw $e;
        }
        
    }
    
    public static function getRewardByScore($score,$rewardConf)
    {
        $reward = $rewardConf['score_lv'];
        ksort($reward);
        $ret = array();
        foreach($reward as $scoreConf => $scoreReward)
        {
            if($score < $scoreConf)
            {
                break;
            }
            $ret = $scoreReward;
        }
        return $ret;
    }
    
    public static function getRewardByRank($rank,$rewardConf)
    {
        $reward = $rewardConf['rank_lv'];
        krsort($reward);
        $ret = array();
        foreach($reward as $rankConf => $rankReward)
        {
            if($rank > $rankConf)
            {
                break;
            }
            $ret = $rankReward['reward'];
        }
        return $ret;
    }
    
    private static function getUserRank($uid)
    {
        $shopInfo = MyHeroShop::getInstance()->getShopInfo();
        $score = $shopInfo[HeroShopDef::SQL_FIELD_SCORE];
        $scoreTime = $shopInfo[HeroShopDef::SQL_FIELD_SCORE_TIME];
        $rank = HeroShopDao::getRankByScoreAndTime($score, $scoreTime, $uid, self::getActStartTime());
        return $rank;
    }
    
    private static function sendToRewardCenter($uid,$reward,$source,$value)
    {
        $level = EnUser::getUserObj($uid)->getLevel();
        $rewardConfType2Type = array(
                RewardConfType::SILVER => RewardType::SILVER,
                RewardConfType::SILVER_MUL_LEVEL => RewardType::SILVER,
                RewardConfType::GOLD => RewardType::GOLD,
                RewardConfType::SOUL => RewardType::SOUL,
                RewardConfType::SOUL_MUL_LEVEL => RewardType::SOUL,
        		RewardConfType::EXP_MUL_LEVEL => RewardType::EXP_NUM,
                RewardConfType::JEWEL => RewardType::JEWEL,
                RewardConfType::ITEM => RewardType::ARR_ITEM_TPL,
                RewardConfType::HERO => RewardType::ARR_HERO_TPL,
                RewardConfType::EXECUTION=>RewardType::EXE,
                RewardConfType::STAMINA => RewardType::STAMINA,
                );
        $reward2Center = array();
        foreach($reward as $type => $rewardConf)
        {
            if(!isset($rewardConfType2Type[$type]))
            {
                throw new FakeException('no such reward conf type %d.reward %s.',$type,$reward);
            }
            $centerType = $rewardConfType2Type[$type];
            if($type == RewardConfType::SILVER_MUL_LEVEL ||
                    ($type == RewardConfType::SOUL_MUL_LEVEL) ||
        			($type == RewardConfType::EXP_MUL_LEVEL))
            {
                if(!isset($reward2Center[$centerType]))
                {
                    $reward2Center[$centerType] = 0;
                }
                $reward2Center[$centerType] +=  $rewardConf * $level;
            }
            else if($type == RewardConfType::SILVER ||
                    ($type == RewardConfType::SOUL))
            {
                if(!isset($reward2Center[$centerType]))
                {
                    $reward2Center[$centerType] = 0;
                }
                $reward2Center[$centerType] += $rewardConf;
            }
            else
            {
                $reward2Center[$centerType] = $rewardConf;
            }
        }
        if(empty($reward2Center))
        {
            return $reward2Center;
        }
        if($source == RewardSource::HERO_SHOP_INTEGRAL)
        {
            $reward2Center[RewardDef::EXT_DATA]['score'] = $value;
        }
        else if($source == RewardSource::HERO_SHOP_RANK)
        {
            $reward2Center[RewardDef::EXT_DATA]['rank'] = $value;
        }
        EnReward::sendReward($uid, $source, $reward2Center);
        return $reward2Center;
    }
    
    public static function getActStartTime()
    {
        $actConf = EnActivity::getConfByName(ActivityName::HERO_SHOP);
        return $actConf['start_time'];
    }
    
    public static function getActEndTime()
    {
        $actConf = EnActivity::getConfByName(ActivityName::HERO_SHOP);
        return $actConf['end_time'];
    }
    
    public static function getDelayAfterEnd()
    {
        $actConf = EnActivity::getConfByName(ActivityName::HERO_SHOP);
        if(!isset($actConf['data'][HeroShopBtstore::BT_ACT_CLOSE_DELAY]))
        {
            throw new FakeException('no delay time set.');
        }
        return $actConf['data'][HeroShopBtstore::BT_ACT_CLOSE_DELAY];
    }
    
    public static function getActNeedOpenTime()
    {
        $actConf = EnActivity::getConfByName(ActivityName::HERO_SHOP);
        return $actConf['need_open_time'];
    }
    
    public static function getSizeOfRealTimeRank()
    {
        Logger::info('getSizeOfRealTimeRank %d %d',self::getMergeServerRatio(),HeroShopDef::RANK_SIZE);
        $size =  self::getMergeServerRatio() * HeroShopDef::RANK_SIZE;
        if($size > CData::MAX_FETCH_SIZE)
        {
            throw new ConfigException('getSizeOfRealTimeRank %d > MAXFETCHSIZE %d',$size,CData::MAX_FETCH_SIZE);
        }
        return $size;
    }
    
    public static function getMergeServerRatio()
    {
        $actConf = EnActivity::getConfByName(ActivityName::HERO_SHOP);
        $startTime = $actConf['start_time'];
        $endTime = $actConf['end_time'];
        $startDate = strtotime(date( "Y-m-d",$startTime));
        $endDate = strtotime(date( "Y-m-d",$endTime))+SECONDS_OF_DAY-1;
        return EnMergeServer::getMergeServerCount($startDate, $endDate);
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */