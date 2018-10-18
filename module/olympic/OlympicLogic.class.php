<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: OlympicLogic.class.php 241880 2016-05-10 08:23:31Z BaoguoMeng $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/olympic/OlympicLogic.class.php $
 * @author $Author: BaoguoMeng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-05-10 08:23:31 +0000 (Tue, 10 May 2016) $
 * @version $Revision: 241880 $
 * @brief 
 *  
 **/
class OlympicLogic
{
    public static function getInfo()
    {
        $retInfo = array();
        $arrUid = array();
        //当前进行到哪个阶段、此阶段的结束时间
        $stageInfo = self::getStageInfo();
        $retInfo = $stageInfo;
        $retInfo['silver_pool'] = self::getCurSilverPool();
        //助威的玩家uid
        $uid = RPCContext::getInstance()->getUid();
        $userOlympic = UserOlympic::getInstance($uid);
        $retInfo['cheer_uid'] = $userOlympic->getCheerUid();

        $stage = $stageInfo['stage'];

        //比赛前阶段：上一届冠军
        $lastChampion = self::getLastChampion();
        $retInfo['last_champion']['uid'] = $lastChampion;
        $arrUid[] = $lastChampion;

        if($stage != OlympicStage::PRE_OLYMPIC)
        {
            //报名的32名玩家的信息
            $olympicRank = OlympicRank::getInstance();
            $arrRankInfo = $olympicRank->getAllSignUpUser();
            $arrRankInfo = Util::arrayIndex($arrRankInfo, OlympicRankDef::FIELD_UID);
            $retInfo['rank_list'] = $arrRankInfo;
            $arrUid = array_merge($arrUid,array_keys($arrRankInfo));
            $fightInfo = array();
            if($stage == OlympicStage::PRELIMINARY_MATCH)
            {
                //预选赛的战报信息
                $logInst = OlympicLog::getInstance(OlympicLogType::PRELIMINARY_MATCH);
                $fightInfo = array(
                        OlympicLogType::PRELIMINARY_MATCH => $logInst->getLogInfo()
                        );
                $userOlympicInst = UserOlympic::getInstance($uid);
                $retInfo['challenge_cd'] = $userOlympicInst->getChallengeTime() + $userOlympicInst->getChallengeCdTime();
            }
            /*else if($stage == OlympicStage::SIXTEEN_FINAL
                    || $stage == OlympicStage::EIGHTH_FINAL
                    || $stage == OlympicStage::QUARTER_FINAL)
            {
                //进16强赛到进4强赛的战报数据
                $arrType = array(
                        OlympicLogType::SIXTEEN_FINAL,
                        OlympicLogType::EIGHTH_FINAL,
                        OlympicLogType::QUARTER_FINAL
                        );
                $fightInfo = OlympicLog::getLogByArrType($arrType);
            }
            else if($stage == OlympicStage::SEMI_FINAL 
                    || $stage == OlympicStage::FINAL_MATCH
                    || $stage == OlympicStage::AFTER_OLYMPIC)
            {
                //半决赛到决赛阶段的战报数据
                $arrType = array(
                        OlympicLogType::SEMI_FINAL,
                        OlympicLogType::FINAL_MATCH,
                );
                $fightInfo = OlympicLog::getLogByArrType($arrType);
            }
            else 
            {
                throw new FakeException('stage %d error.no such stage',$stage);
            }*/
            else
            {
                $arrType = array(
                    OlympicLogType::SIXTEEN_FINAL,
                    OlympicLogType::EIGHTH_FINAL,
                    OlympicLogType::QUARTER_FINAL,
                    OlympicLogType::SEMI_FINAL,
                    OlympicLogType::FINAL_MATCH
                );
                $fightInfo =  OlympicLog::getLogByArrType($arrType);
            }
            $retInfo['fight_info'] = $fightInfo;
        }

        $arrUserInfo = EnUser::getArrUserBasicInfo($arrUid, array('uid','uname','htid','dress','vip','level','fight_force'));
        $arrUserOlympicInfo = array();
        if(!empty($arrUid))
        {
            $arrUserOlympicInfo = Util::arrayIndex(
                OlympicDao::getUserOlympicWithWhere(
                    array(UserOlympicDef::FIELD_UID, UserOlympicDef::FIELD_BE_CHEER_NUM),
                    array(array(UserOlympicDef::FIELD_UID, 'IN', $arrUid))
                ),
                UserOlympicDef::FIELD_UID
            );
        }
        if(isset($retInfo['last_champion']))
        {
            $uid = $retInfo['last_champion']['uid'];
            if(!empty($uid))
            {
                $retInfo['last_champion'] = $arrUserInfo[$uid];
                //连胜次数
                $retInfo['last_champion']['win_cont'] = self::getWinCont();
            }
        }
        if(isset($retInfo['rank_list']))
        {
            foreach($retInfo['rank_list'] as $uid => $userRankInfo)
            {
                $userInfo = array();
                if(isset($arrUserInfo[$uid]))
                {
                    $userInfo = $arrUserInfo[$uid];
                }
                $userOlympicInfo = array();
                if(isset($arrUserOlympicInfo[$uid]))
                {
                    $userOlympicInfo = $arrUserOlympicInfo[$uid];
                }

                $retInfo['rank_list'][$uid] = $userInfo + $userRankInfo + $userOlympicInfo;
            }
        }
        
        $retInfo['timeConf'] = array(
        		'preStartTime' => self::getPreOlympicStartTime() ,
        		'signStartTime' => self::getPreLiminaryMatchStartTime() ,
        		'signDuration' => OlympicStage::PRELIMINARY_MATCH_TIME,
        		'signFightGap' => OlympicStage::PRELIMINARY_FIGHT_GAP,
        		'fighGap' => OlympicStage::$ARR_FIGHT_DURATION,
        );
        
        return $retInfo;
    }

    public static function getFightInfo()
    {
        if(self::isPreOlympicStage())
        {
            throw new FakeException('pre olympic stage can not getFightInfo.time is %d',Util::getTime());
        }
        $arrType = array(
                OlympicLogType::SIXTEEN_FINAL,
                OlympicLogType::EIGHTH_FINAL,
                OlympicLogType::QUARTER_FINAL,
                OlympicLogType::SEMI_FINAL,
                OlympicLogType::FINAL_MATCH
        );
        $fightInfo =  OlympicLog::getLogByArrType($arrType);
        $olympicRank = OlympicRank::getInstance();
        $arrRankInfo = $olympicRank->getAllSignUpUser();
        $arrRankInfo = Util::arrayIndex($arrRankInfo, OlympicRankDef::FIELD_UID);
        $arrUid = array_keys($arrRankInfo);
        $arrUserInfo = EnUser::getArrUserBasicInfo($arrUid, array('uid','uname','htid','dress'));
        foreach($arrRankInfo as $uid => $userRankInfo)
        {
            $userInfo = $arrUserInfo[$uid];
            $arrRankInfo[$uid] = $userInfo + $userRankInfo;
        }
        return array('rank_list'=>$arrRankInfo,'fight_info'=>$fightInfo);
    }
    
    public static function signUp($uid, $signUpIndex)
    {
        $todayStatus = self::getTodayStatus();
        if($todayStatus == OlympicWeekStatus::NOTHING_DAY)
        {
            Logger::info('no olympic today');
            return;
        }

        if(!self::isPreliminaryMatch())
        {
            throw new FakeException('invalid stage, can not challenge');
        }
        if($signUpIndex > OlympicDef::MAX_SIGNUP_INDEX 
                || $signUpIndex < OlympicDef::MIN_SIGNUP_INDEX)
        {
            throw new FakeException('invalid index %d',$signUpIndex);
        }
        $stageInfo = self::getStageInfo();
        if($stageInfo['stage'] != OlympicStage::PRELIMINARY_MATCH &&
            $stageInfo['stage'] != OlympicStage::PRE_OLYMPIC)
        {
            throw new FakeException('can not signup stage info is %s',$stageInfo);
        }
        $rankInst = OlympicRank::getInstance();
        $preRankInfo = $rankInst->getInfoByUid($uid);
        if(!empty($preRankInfo))
        {
            throw new FakeException('user has signup info %s',$preRankInfo);
        }
        $locker	= new Locker();
        if($locker->lock(self::getOlympicLockKey($signUpIndex)) == FALSE)
        {
            throw new FakeException('lock %d fail.',$signUpIndex);
        }
        try
        {
            //清一下be_cheer_num
            $userOlympicInst = UserOlympic::getInstance($uid);
            $userOlympicInst->signUp();

            $rankInfo = $rankInst->getInfoBySignUpIndex($signUpIndex);
            if(!empty($rankInfo[OlympicRankDef::FIELD_UID]))
            {
                throw new FakeException('signupindex %d can not be signed up.user %d has sign up',
                        $signUpIndex,$uid);
            }
            $userObj = EnUser::getUserObj($uid);
            $level = $userObj->getLevel();
            $silverBase = btstore_get()->CHALLENGE[OlympicDef::CHALLENGE_DEFAULT_ID][ChallengeCsvDef::JOIN_COST_BELLY];
            if($userObj->subSilver($level * $silverBase) == FALSE)
            {
                throw new FakeException('sub silver failed');  
            }
            $rankInst->signUp($uid, $signUpIndex);

            //奖池--报名费用
            $addSilverPool = $level * $silverBase * self::getSilverMultiple();
            self::addSilverPoolAndSendMsg($addSilverPool);

            $userOlympicInst->update();
            $userObj->update();
            $rankInst->save();
            self::signUpSendMsg($uid, $signUpIndex);
        }
        catch(Exception $e)
        {
            $locker->unlock(self::getOlympicLockKey($signUpIndex));
            throw $e;
        }
        $locker->unlock(self::getOlympicLockKey($signUpIndex));
        return 'ok';
    }
    
    public static function challenge($uid,$signUpIndex)
    {
        $todayStatus = self::getTodayStatus();
        if($todayStatus == OlympicWeekStatus::NOTHING_DAY)
        {
            Logger::info('no olympic today');
            return;
        }
        if(!self::isPreliminaryMatch())
        {
            throw new FakeException('invalid stage, can not challenge');
        }

        if($signUpIndex > OlympicDef::MAX_SIGNUP_INDEX
                || $signUpIndex < OlympicDef::MIN_SIGNUP_INDEX)
        {
            throw new FakeException('invalid index %d',$signUpIndex);
        }
        $stageInfo = self::getStageInfo();
        if($stageInfo['stage'] != OlympicStage::PRELIMINARY_MATCH &&
            $stageInfo['stage'] != OlympicStage::PRE_OLYMPIC
        )
        {
            throw new FakeException('can not signup stage info is %s',$stageInfo);
        }

        $arrUserInfo = EnUser::getArrUserBasicInfo(array($uid), array('uid','uname','htid','dress','vip', 'level'));
        $userOlympicInst = UserOlympic::getInstance($uid);
        $challengeTime = $userOlympicInst->getChallengeTime();
        $cdTime = $userOlympicInst->getChallengeCdTime();
        if($cdTime + $challengeTime > Util::getTime())
        {
            throw new FakeException('in challenge cdtime.lastchallengetime %d cd %d now %d',
                    $challengeTime,$cdTime,Util::getTime());
        }
        $rankInst = OlympicRank::getInstance();
        $preRankInfo = $rankInst->getInfoByUid($uid);
        if(!empty($preRankInfo))
        {
            throw new FakeException('user has signup info %s',$preRankInfo);
        }
        $locker	= new Locker();
        if($locker->lock(self::getOlympicLockKey($signUpIndex)) == FALSE)
        {
            throw new FakeException('lock %d fail.',$signUpIndex);
        }
        try
        {
            $rankInfo = $rankInst->getInfoBySignUpIndex($signUpIndex);
            if(empty($rankInfo[OlympicRankDef::FIELD_UID]))
            {
                throw new FakeException('signupindex %d can not be challenged.no user sign up',
                        $signUpIndex);
            }
            $userObj = EnUser::getUserObj($uid);
            $level = $userObj->getLevel();
            $silverBase = btstore_get()->CHALLENGE[OlympicDef::CHALLENGE_DEFAULT_ID][ChallengeCsvDef::CHALLENGE_COST];
            if($userObj->subSilver($level * $silverBase) == FALSE)
            {
                throw new FakeException('sub silver failed');
            }
            $preUid = $rankInfo[OlympicRankDef::FIELD_UID];
            $userBtInfo = $userObj->getBattleFormation();
            $preUserBtInfo = EnUser::getUserObj($preUid)->getBattleFormation();
            $atkRet = EnBattle::doHero($userBtInfo, $preUserBtInfo);
            $isSuc = BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'];
            $userObj->update();
            //战报
            $attackerName = EnUser::getUserObj($uid)->getUname();
            $defenderName = EnUser::getUserObj($preUid)->getUname();

            $logInfo = array(
                'attacker' => $uid,
                'defender' => $preUid,
                'brid' => $atkRet['server']['brid'],
                'result' => $atkRet['server']['appraisal'],
            );
       
            $logInfo['userInfo'] = $arrUserInfo;
            $logInfo['attackerName'] = $attackerName;
            $logInfo['defenderName'] = $defenderName;
            if($isSuc)
            {
                $rankInst->signUp($uid, $signUpIndex);
            }
            OlympicLogic::sendFilterMsgNow(PushInterfaceDef::OLYMPIC_CHALLENGE, array($logInfo));
            //不论输赢
            $userOlympicInst->challenge();

            //奖池--挑战费用
            $addSilverPool = $level * $silverBase * self::getSilverMultiple();
            self::addSilverPoolAndSendMsg($addSilverPool);

            $rankInst->save();
            $userOlympicInst->update();
        }
        catch(Exception $e)
        {
            $locker->unlock(self::getOlympicLockKey($signUpIndex));
            throw $e;
        }
        $locker->unlock(self::getOlympicLockKey($signUpIndex));
        return array(
            'res'=>$atkRet['server']['appraisal'],
            'fight_ret'=>$atkRet['client'],
            'userInfo'=>$arrUserInfo
        );
    }
    
    public static function signUpSendMsg($uid,$signUpIndex)
    {
        $arrUserInfo = EnUser::getArrUserBasicInfo(array($uid), 
                array('uid','uname','htid','dress'));
        $userInfo = $arrUserInfo[$uid];
        $userInfo[OlympicRankDef::FIELD_SIGNUP_INDEX] = $signUpIndex;
        $userInfo[OlympicRankDef::FIELD_FINAL_RANK] = 0;
        $userInfo[OlympicRankDef::FIELD_OLYMPICINDEX] = 0;
        RPCContext::getInstance()->sendFilterMessage('arena',
                 SPECIAL_ARENA_ID::OLYMPIC, 
                PushInterfaceDef::OLYMPIC_SIGNUP_UPDATE, $userInfo);
    }

    /**
     * 增加奖池奖金 并给前端推消息
     */
    public static function addSilverPoolAndSendMsg($addSilverPool)
    {
        $avgLvOfArena = self::getAvgLvOfArena();
        $maxPrize = btstore_get()->CHALLENGE[OlympicDef::CHALLENGE_DEFAULT_ID][ChallengeCsvDef::MAX_PRIZE];
        $curSilverPool = self::getCurSilverPool();
        if($curSilverPool >= $avgLvOfArena * $maxPrize)
        {
            return;
        }
        else if($curSilverPool + $addSilverPool >= $avgLvOfArena * $maxPrize)
        {
            $addSilverPool = $avgLvOfArena * $maxPrize - $curSilverPool;
        }
        else
        {
            Logger::trace('add silverPool %d', $addSilverPool);
        }

        self::addCurSilverPool($addSilverPool);
        RPCContext::getInstance()->sendFilterMessage('arena',
            SPECIAL_ARENA_ID::OLYMPIC,
            PushInterfaceDef::OLYMPIC_SILVER_POOL,
            array('addSilverPool' => $addSilverPool)
        );
    }

    public static function clearChallengeCd($uid)
    {
        $todayStatus = self::getTodayStatus();
        if($todayStatus == OlympicWeekStatus::NOTHING_DAY)
        {
            Logger::info('no olympic today');
            return;
        }

        $userOlympicInst = UserOlympic::getInstance($uid);
        $challengeTime = $userOlympicInst->getChallengeTime();
        $cdTime = $userOlympicInst->getChallengeCdTime();
        if($challengeTime + $cdTime < Util::getTime())
        {
            throw new FakeException('clearChanllengeCd error.curtime is %d challengetime %d cdtime %d',
                    Util::getTime(),$challengeTime,$cdTime);
        }
        $needGoldPerSecond = self::getClearCdNeedGold();
        $needGold = ceil(($challengeTime + $cdTime - Util::getTime()) / 10) * $needGoldPerSecond;
        $userObj = EnUser::getUserObj($uid);
        if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_OLYMPIC_CLEAR_CD) == FALSE)
        {
            throw new FakeException('sub gold fail');
        }
        $userOlympicInst->clearCd();
        $userObj->update();
        $userOlympicInst->update();
        return array('gold' => $needGold);
    }
    
    public static function cheer($uid,$cheerUid)
    {
        $todayStatus = self::getTodayStatus();
        if($todayStatus == OlympicWeekStatus::NOTHING_DAY)
        {
            Logger::info('no olympic today');
            return;
        }

        $stageInfo = self::getStageInfo();
        $stageStatus = $stageInfo['status'];
        if($stageInfo['stage'] != OlympicStage::SEMI_FINAL ||
                $stageStatus != OlympicStageStatus::PREPARE)
        {
            throw new FakeException('now is not in cheer stage.can not cheer');
        }
        $userOlympicInst = UserOlympic::getInstance($uid);
        $preCheerUid = $userOlympicInst->getCheerUid();
        if(!empty($preCheerUid))
        {
            throw new FakeException('user %d has cheeruid %d',$uid,$preCheerUid);
        }

        $rankInst = OlympicRank::getInstance();
        $cheerUserRankInfo = $rankInst->getInfoByUid($cheerUid);
        if(empty($cheerUserRankInfo) || ($cheerUserRankInfo[OlympicRankDef::FIELD_FINAL_RANK] != 4))
        {
            throw new FakeException('can cheer user %d.user not sign up or not in top 4',$cheerUid);
        }
        
        $selfRankInfo = $rankInst->getInfoByUid($uid);
        if( !empty($selfRankInfo) )
        {
        	throw new FakeException('uid:%d rank:%d cant cheer',$uid, $selfRankInfo[OlympicRankDef::FIELD_FINAL_RANK]);
        }
        
        //扣银币
        $needSilver = self::getCheerNeedSilver();
        $userObj = EnUser::getUserObj($uid);
        $level = $userObj->getLevel();
        if($userObj->subSilver($level * $needSilver) == FALSE)
        {
            throw new FakeException('sub silver fail');
        }

        $userOlympicInst->cheer($cheerUid);
        //被助威
        $cheerOlympicInst = UserOlympic::getInstance($cheerUid);
        $cheerOlympicInst->beCheer();

        //给前端推消息 助威
        self::sendFilterMsgNow(PushInterfaceDef::OLYMPIC_CHEER, array('cheer_uid'=>$cheerUid));

        //奖池--助威费用
        $addSilverPool = $level * $needSilver * self::getSilverMultiple();
        self::addSilverPoolAndSendMsg($addSilverPool);

        $userObj->update();
        $userOlympicInst->update();
        $cheerOlympicInst->update();
        return 'ok';
    }
    
    public static function getChallengeCdTime()
    {
        return btstore_get()->CHALLENGE[OlympicDef::CHALLENGE_DEFAULT_ID][ChallengeCsvDef::CDTIME];
    }
    
    public static function getClearCdNeedGold()
    {
        return btstore_get()->CHALLENGE[OlympicDef::CHALLENGE_DEFAULT_ID][ChallengeCsvDef::CLEAR_CD_COST_GOLD];
    }
    
    public static function getCheerNeedSilver()
    {
        return btstore_get()->CHALLENGE[OlympicDef::CHALLENGE_DEFAULT_ID][ChallengeCsvDef::CHEER_COST_BELLY];
    }
    
    public static function getOlympicLockKey($signUpIndex)
    {
        return OlympicDef::OLYMPIC_LOCK_PREFIX.".".$signUpIndex;
    }

    public static function getCurSilverPool()
    {
        /*$logInst = OlympicLog::getInstance(OlympicLogType::REWARD_POOL);
        return $logInst->getSilverPool();*/
        $olympicGlobal = OlympicGlobal::getInstance();
        return $olympicGlobal->getSilverPool();
    }

    public static function clrCurSilverPool()
    {
        $olympicGlobal = OlympicGlobal::getInstance();
        $olympicGlobal->clrSilverPool();
    }

    //奖池
    public static function addCurSilverPool($addSilver)
    {
        $olympicGlobal = OlympicGlobal::getInstance();
        $olympicGlobal->addSilverPool($addSilver);
        $olympicGlobal->update();
    }
    
    public static function getLastChampion()
    {
    	/*$preOlympicStartTime = self::getPreOlympicStartTime();

        $logInst = OlympicLog::getInstance(OlympicLogType::REWARD_POOL, $preOlympicStartTime - SECONDS_OF_DAY);
        return $logInst->getLastCampion();*/
        $olympicGlobal = OlympicGlobal::getInstance();
        return $olympicGlobal->getLastChampion();
    }

    public static function getWinCont()
    {
        $olympicGlobal = OlympicGlobal::getInstance();
        return $olympicGlobal->getWinCont();
    }

    public static function getAvgLvOfArena()
    {
        $olympicGlobal = OlympicGlobal::getInstance();
        return $olympicGlobal->getAvgLvOfArena();
    }

    public static function updAvgLvOfArena($avgLv)
    {
        $oldAvgLv = self::getAvgLvOfArena();
        if(!empty($oldAvgLv) && $oldAvgLv >= $avgLv)
        {
            Logger::info('oldAvgLv is bigger than the new one');
            return;
        }
        $olympicGlobal = OlympicGlobal::getInstance();
        $olympicGlobal->updAvgLvOfArena($avgLv);
        $olympicGlobal->update();
    }

    public static function getDayBreak($time)
    {
        return strtotime(date("Y-m-d", $time));
    }
    
    /**
     * @return array
     * [
     *     stage:int
     *     stage_end_time:int
     * ]
     */
    public static function getStageInfo()
    {
        //先判断当前是不是比赛阶段
        $status = OlympicStageStatus::START;
        if(self::isPreOlympicStage())
        {
            $stage = OlympicStage::PRE_OLYMPIC;
        }
        else
        {
            $logInst = OlympicLog::getInstance(OlympicLogType::BATTLE_PROGRESS);
            $stageInfo = $logInst->getCurStageInfo();
            if(empty($stageInfo))
            {
                $stage = OlympicStage::PRE_OLYMPIC;
            }
            else
            {
                $stage = $logInst->getCurStage();
                $status = $logInst->getCurStageStatus();
            }
        }
        return array(
                'stage'=>$stage,
                'status'=>$status,
                );
    }

    //准备阶段
    private static function isPreOlympicStage()
    {
        $curTime = Util::getTime();
        $dayBreak = self::getDayBreak(Util::getTime());
        if($curTime > self::getPreOlympicStartTime() &&
            $curTime < self::getPreLiminaryMatchStartTime())
        {
            return true;
        }
        return false;
    }

    //预选赛阶段
    private static function isPreliminaryMatch()
    {
        $curTime = Util::getTime();
        $dayBreak = self::getDayBreak(Util::getTime());
        if($curTime > self::getPreLiminaryMatchStartTime() &&
            $curTime < self::getPreLiminaryMatchStartTime() + OlympicStage::PRELIMINARY_MATCH_TIME )
        {
            return true;
        }
        return false;
    }
    
    /**
     * 某个时间是大轮的第几天
     * 比赛前阶段的开始时间算每一小轮的开始时间
     */
    public static function whichDayOfSuperRound($time)
    {
        
    }
    
    public static function getCurRoundStartTime()
    {
        $curTime = Util::getTime();
        $preOlympicStartTime = self::getPreOlympicStartTime();
        if($curTime >= $preOlympicStartTime)
        {
            return $preOlympicStartTime;
        }
        else
        {
            return $preOlympicStartTime - SECONDS_OF_DAY;
        }
    }
    
    public static function getCurSuperRoundStartTime()
    {
        return self::getCurRoundStartTime();
    }
    /**
     * 
     * @param int $cheerTime
     */
    public static function canDailyRfrCheer($cheerTime)
    {
        $preOlympicStartTime = self::getPreOlympicStartTime();
        if($cheerTime < $preOlympicStartTime &&
                Util::getTime() > $preOlympicStartTime)
        {
            return TRUE;
        }
        return FALSE;
    }

    public static function getPreOlympicStartTime()
    {
        $dayBreak = self::getDayBreak(Util::getTime());
        return $dayBreak + OlympicStage::PRE_OLYMPIC_START +  GameConf::BOSS_OFFSET;
    }

    public static function getPreLiminaryMatchStartTime()
    {
        $dayBreak = self::getDayBreak(Util::getTime());
        return $dayBreak + OlympicStage::PRELIMINARY_MATCH_START +  GameConf::BOSS_OFFSET;
    }
    
    /**
     * 参照canDailyRfrCheer写
     * @param unknown_type $weeklyRfrTime
     */
    //TODO   
    public static function canWeeklyRfrData($weeklyRfrTime)
    {
        $week = self::getTodayWeek();
        $canTodayRfr = FALSE;
        $olympicConf = btstore_get()->CHALLENGE[OlympicDef::CHALLENGE_DEFAULT_ID][ChallengeCsvDef::CHALLENGE_EVENT];
        if($olympicConf[$week] == OlympicWeekStatus::COMPETE_DAY
                || $olympicConf[$week] == OlympicWeekStatus::COMPETE_REWARD_DAY)
        {
            $lastDayWeek = $week - 1;
            if($lastDayWeek == 0)
            {
                $lastDayWeek = 7;
            }      
            //一轮的最后一天的状态只能是这三种（不开启擂台赛，发奖，比赛和发奖）
            if($olympicConf[$week] == OlympicWeekStatus::NOTHING_DAY 
                    || $olympicConf[$week] == OlympicWeekStatus::REWARD_DAY 
                    || $olympicConf[$week] == OlympicWeekStatus::COMPETE_REWARD_DAY)
            {
                $canTodayRfr = TRUE;
            }
        }
        if($canTodayRfr == FALSE)
        {
            return FALSE;
        }
        $preOlympicStartTime = self::getPreOlympicStartTime();
        if($weeklyRfrTime < $preOlympicStartTime &&
                Util::getTime() > $preOlympicStartTime)
        {
            return TRUE;
        }
        return FALSE;
    }
    //TODO   需要给每个接口加上限制    
    //擂台赛不开的时候  不能调用某些接口   如果某个擂台赛不开    getInfo如何返回（返回前一天的奖池和冠军？？？？） crontab 加上
    public static function getTodayStatus()
    {
        $dayBreak = self::getDayBreak(Util::getTime());
        $week = self::getTodayWeek();
        if(Util::getTime() < self::getPreOlympicStartTime())
        {
            $week = $week - 1;
            if($week == 0)
            {
                $week = 7;
            }
        }
        $olympicConf = btstore_get()->CHALLENGE[OlympicDef::CHALLENGE_DEFAULT_ID][ChallengeCsvDef::CHALLENGE_EVENT];
        return $olympicConf[$week];
    }
    
	public static function startPreOlympicStage()
    {
        Logger::info('stage Pre_Olympic start.reset olympic rank');
        /*
        $rankInst = OlympicRank::getInstance();
        $arrRankInfo = $rankInst->getAllSignUpUser();
        foreach($arrRankInfo as $signUpIndex => $rankInfo)
        {
            $rankInst->dailyReset($signUpIndex);
        }
        $rankInst->save();
        */
        $todayStatus = self::getTodayStatus();
        if($todayStatus == OlympicWeekStatus::NOTHING_DAY)
        {
        	Logger::info('today no olympic');
            return;
        }

        OlympicRank::resetDate();//直接重置所有数据
        $logInst = OlympicLog::getInstance(OlympicLogType::BATTLE_PROGRESS);
        $logInst->updStageBeginTime(OlympicStage::PRE_OLYMPIC, time());
        $logInst->updStageStatus(OlympicStage::PRE_OLYMPIC, OlympicStageStatus::END);
        $logInst->save();

        $newAvgLvOfArean = self::getAvgLevelOfAreanFirstTen();
        self::updAvgLvOfArena($newAvgLvOfArean);

        $curSilverPool = self::getCurSilverPool();
        if(empty($curSilverPool))
        {
            self::initSilverPool();
        }
        
        $logInst2 = OlympicLog::getInstance(OlympicLogType::PRELIMINARY_MATCH);
        $logInst2->save();
    }

    /**
     * 取竞技场前十的排名玩家平均等级
     */
    public static function getAvgLevelOfAreanFirstTen()
    {
        $arrUid = EnArena::getRankList(0, 10, array('uid'));
        if(empty($arrUid) || count($arrUid) <= 0)
        {
            throw new InterException('arrUid is empty');
        }
        $totalLevel = 0;
        foreach($arrUid as $userInfo)
        {
            if(empty($userInfo))
            {
                continue;
            }
            $uid = $userInfo['uid'];
            if(ArenaLogic::isNpc($uid))
            {
                $totalLevel += OlympicDef::NPC_LEVLE;
            }
            else
            {
                $totalLevel += EnUser::getUserObj($uid)->getLevel();
            }
        }

        $avgLevel = ceil($totalLevel / count($arrUid));
        return $avgLevel;
    }

    public static function initSilverPool()
    {
        //先清空历史的奖池
        self::clrCurSilverPool();

        $avgLvOfArean = self::getAvgLvOfArena();

        $minPrize = btstore_get()->CHALLENGE[OlympicDef::CHALLENGE_DEFAULT_ID][ChallengeCsvDef::MIN_PRIZE];
        $maxPrize = btstore_get()->CHALLENGE[OlympicDef::CHALLENGE_DEFAULT_ID][ChallengeCsvDef::MAX_PRIZE];
        $silverBase = $avgLvOfArean * $minPrize;
        self::addCurSilverPool($silverBase);

        return $silverBase;
    }

    public static function getSilverMultiple()
    {
        return btstore_get()->CHALLENGE[OlympicDef::CHALLENGE_DEFAULT_ID][ChallengeCsvDef::CHEER_MULTIPLE];
    }
    
    //预选赛开始
    public static function startPreliminary()
    {
        Logger::info('startPreliminary');
        $todayStatus = self::getTodayStatus();
        if($todayStatus == OlympicWeekStatus::NOTHING_DAY)
        {
        	Logger::info('today no olympic');
            return;
        }

        $logInst = OlympicLog::getInstance(OlympicLogType::BATTLE_PROGRESS);
        $logInst->updStageStatus(OlympicStage::PRELIMINARY_MATCH, OlympicStageStatus::START);
        $logInst->save();
        self::sendFilterMsgNow(PushInterfaceDef::OLYMPIC_STAGE_END, array('stage'=>OlympicStage::PRELIMINARY_MATCH));
        Logger::info('stage Preliminary end');
        $logInst->updStageStatus(OlympicStage::PRELIMINARY_MATCH, OlympicStageStatus::END);
        $logInst->save();
    }

    //分组阶段
    public static function startGroup()
    {
        Logger::trace('stage startGroup start');
        $todayStatus = self::getTodayStatus();
        if($todayStatus == OlympicWeekStatus::NOTHING_DAY)
        {
        	Logger::info('today no olympic');
            return;
        }

        $logInst = OlympicLog::getInstance(OlympicLogType::BATTLE_PROGRESS);
        $curStage = $logInst->getCurStage();
        $curStageStatus = $logInst->getCurStageStatus();
        if($curStage != OlympicStage::PRELIMINARY_MATCH || $curStageStatus != OlympicStageStatus::END)
        {
            return;
        }
        //分组阶段开始
        $logInst->updStageStatus(OlympicStage::OLYMPIC_GROUP, OlympicStageStatus::START);
        $logInst->save();
        self::sendFilterMsgNow(PushInterfaceDef::OLYMPIC_STAGE_END, array('stage'=>OlympicStage::OLYMPIC_GROUP));
        //分组阶段结束
        $rankInst = OlympicRank::getInstance();
        $rankInst->reorderAllSignUpUser();
        $rankInst->save();
        $logInst->updStageStatus(OlympicStage::OLYMPIC_GROUP, OlympicStageStatus::END);
        $logInst->save();
        //16强准备阶段
        $logInst->updStageStatus(OlympicStage::SIXTEEN_FINAL, OlympicStageStatus::PREPARE);
        $logInst->save();
        self::sendFilterMsgNow(PushInterfaceDef::OLYMPIC_STAGE_END, array('stage'=>OlympicStage::SIXTEEN_FINAL));
        Logger::trace('stage startGroup end');
    }

    /**
     * 开始所有决赛的接口（16强，8强，4强，半决赛，决赛）
     * @param int $stageId
     */
    public static function startFinal($stageId)
    {
    	$todayStatus = self::getTodayStatus();
    	if($todayStatus == OlympicWeekStatus::NOTHING_DAY)
    	{
    		Logger::info('today no olympic');
    		return;
    	}
    	
        $rankInst = OlympicRank::getInstance();

        if(self::stageCanStart($stageId))
        {
            $allSignUpUser = Util::arrayIndex($rankInst->getAllSignUpUser(), OlympicRankDef::FIELD_OLYMPICINDEX);
            if(empty($allSignUpUser))
            {
                Logger::warning('allSignUpUser is empty.');
            }
            Logger::info('startFinal stage:%d allsignupuser is %s', $stageId,$allSignUpUser);
            $logInst = OlympicLog::getInstance(OlympicLogType::BATTLE_PROGRESS);
            $logInst->updStageBeginTime($stageId, time());
            $logInst->updStageStatus($stageId, OlympicStageStatus::START);
            $logInst->save();
            for($i=OlympicDef::MIN_SIGNUP_INDEX; $i<=OlympicDef::MAX_SIGNUP_INDEX; $i += OlympicDef::$step[$stageId])
            {
                // 获取对战两个人的信息
                $user = OlympicUtil::getEnemy($allSignUpUser, $i,
                    OlympicDef::$step[$stageId], OlympicDef::$next[$stageId]*2);
                // 如果两个位置都轮空，则不需要再执行什么了
                if (count($user) == 0)
                {
                    Logger::debug("startFinals continue.");
                    continue;
                }
                OlympicUtil::fight($user[0], $user[1], $stageId);
            }
            $logInst->updStageStatus($stageId, OlympicStageStatus::END);
            $logInst->save();
            $logInst->updStageStatus($stageId+1, OlympicStageStatus::PREPARE);
            $logInst->save();
            self::sendFilterMsgNow(PushInterfaceDef::OLYMPIC_STAGE_END, array('stage'=>$stageId+1));

            if($stageId == OlympicStage::FINAL_MATCH)
            {
                self::updWinContOfReigningChampion($stageId);
            }

            Logger::info('stage %s end',$stageId);
        }
        else
        {
        	Logger::fatal('stage:%d not run', $stageId);
        }
    }

    public static function updWinContOfReigningChampion($stageId)
    {
        if($stageId != OlympicStage::FINAL_MATCH)
        {
            return;
        }
        $lastChampion = self::getLastChampion();
        $reigningChampion = self::getReigningChampion();
        $olympicGlobal = OlympicGlobal::getInstance();
        if(!empty($reigningChampion) && $lastChampion == $reigningChampion)
        {
            $olympicGlobal->updWinCont(self::getWinCont() + 1);
        }
        $olympicGlobal->update();
    }

    /*
     * 从OlympicRank得到刚打出来的本届冠军
     */
    public static function getReigningChampion()
    {
        $rankInst = OlympicRank::getInstance();
        $arrRankInfo = $rankInst->getAllSignUpUser();
        $reigningChampion = 0;
        foreach($arrRankInfo as $signUpIndex => $rankInfo)
        {
            $rank = $rankInfo[OlympicRankDef::FIELD_FINAL_RANK];
            $uid = $rankInfo[OlympicRankDef::FIELD_UID];
            if(empty($rank))
            {
                throw new FakeException('');
            }
            if($rank == 1)
            {
                $reigningChampion = $uid;
            }
        }
        return $reigningChampion;
    }

    public static function startAfterOlympic()
    {
        Logger::info('stage afterOlympic start');
        $todayStatus = self::getTodayStatus();
        if($todayStatus == OlympicWeekStatus::NOTHING_DAY)
        {
            Logger::info('today no olympic');
            return;
        }
        //将助威成功次数、连胜次数写入数据库 上一届冠军
        if(self::stageCanStart(OlympicStage::AFTER_OLYMPIC))
        {
            //发送奖励
            self::rewardUser();
            self::updateCheerAndWin();
            self::rewardLucky();
            $logInst = OlympicLog::getInstance(OlympicLogType::BATTLE_PROGRESS);
            $logInst->updStageBeginTime(OlympicStage::AFTER_OLYMPIC, time());
            $logInst->updStageStatus(OlympicStage::AFTER_OLYMPIC, OlympicStageStatus::END);
            $logInst->save();
        }
        else
        {
        	Logger::fatal('startAfterOlympic not run');
        }
        Logger::info('stage afterOlympic end');
    }
    
    
    
    private static function stageCanStart($stageId)
    {
        if(!isset(OlympicStage::$PRE_STAGE[$stageId]))
        {
        	Logger::debug('not check stage:%d', $stageId);
            return TRUE;
        }
        $allDelayTime = 0;
        while(TRUE)
        {
            $logInst = OlympicLog::getInstance(OlympicLogType::BATTLE_PROGRESS);
            $stageInfo = $logInst->getCurStageInfo();
            if(empty($stageInfo))
            {
                $logInst->updStageStatus($stageId, OlympicStageStatus::ERR);
                $logInst->save();
                throw new FakeException('stageinfo is empty');
            }
            $curStage = $logInst->getCurStage();
            $curStageStatus = $logInst->getCurStageStatus();
            if($curStageStatus == OlympicStageStatus::ERR)
            {
            	Logger::warning('status erro. stage:%d', $curStage);
                return FALSE;
            }
            if($curStage == $stageId)
            {
                if($curStageStatus == OlympicStageStatus::PREPARE)
                {
                    break;
                }
                else
                {
                    $logInst->updStageStatus($stageId, OlympicStageStatus::ERR);
                    $logInst->save();
                    throw new FakeException('stage %d start. curstage is %d stagestatus is %d',$stageId,$curStage,$curStageStatus);
                }
            }
            else
            {
                $delayTime = OlympicDef::MIN_DELAY_TIME;
                $allDelayTime += $delayTime;
                //$logInst->updStageEndTime($curStage, time()+$delayTime);
                //$logInst->updStageStatus($curStage, OlympicStageStatus::DELAY);
                //$logInst->save();
                sleep($delayTime);
                Logger::warning('curStage:%d not ready to start. allDelay:%d',$curStage, $allDelayTime);
            }
            if($allDelayTime > OlympicDef::MAX_DELAY_TIME)
            {
                $logInst->updStageStatus($curStage, OlympicStageStatus::ERR);
                $logInst->save();
                throw new FakeException('stage %d delay time %d stop delay.',$stageId,$allDelayTime);
            }
            OlympicLog::release(OlympicLogType::BATTLE_PROGRESS);
            //这个地方加上 明天问下
            UserOlympic::release();
            CData::$QUERY_CACHE = NULL;
        }
        return TRUE;
    }
    
    public static function sendFilterMsgNow($callBackName, $arrArg, $err = "ok")
    {
    	$serverProxy = new ServerProxy();
    	$serverProxy->sendFilterMessage(
    			'arena',
    			SPECIAL_ARENA_ID::OLYMPIC,
    			array(
    					'callback'=>array('callbackName'=>$callBackName),
    					'ret'=>$arrArg,
    					'err' => $err,
    			)
    	);
    }
    
    /**
     * 周奖励也在这里发    
     * @throws FakeException
     */
    public static function rewardUser()
    {
        Logger::info('rewarduser start');
        $rankToRewardId = array(
                32 => 0,
                16 => 1,
                8 => 2,
                4 => 3,
                2 => 4,
                1 => 5
                );
        $rankInst = OlympicRank::getInstance();
        $arrRankInfo = $rankInst->getAllSignUpUser();
        foreach($arrRankInfo as $signUpIndex => $rankInfo)
        {
            $rank = $rankInfo[OlympicRankDef::FIELD_FINAL_RANK];
            $uid = $rankInfo[OlympicRankDef::FIELD_UID];
            if(empty($rank))
            {
                throw new FakeException('');
            }
            $rewardId = btstore_get()->CHALLENGE[OlympicDef::CHALLENGE_DEFAULT_ID][ChallengeCsvDef::PRIZE_ID][$rankToRewardId[$rank]];
            $rewardData = btstore_get()->CHALLENGEREWARD[$rewardId][ChallengeRewardCsvDef::REWARD]->toArray();
            if($rank == 1)
            {
                //将冠军写入log
                /*$olympicLog = OlympicLog::getInstance(OlympicLogType::REWARD_POOL);
                $olympicLog->updLastCampion($uid);
                $olympicLog->save();*/
                //发奖池奖励
                $lastChampion = OlympicLogic::getLastChampion();
                $olympicGlobal = OlympicGlobal::getInstance();
                if($lastChampion == $uid)
                {
                    Logger::info('this champion %d is the same with last champion %d, do not rewardPool', $uid, $lastChampion);
                }
                else
                {
                    //冠军变了、发奖、更新连胜、重置奖池
                    if(!empty($lastChampion))
                    {
                        self::rewardPool($uid, $lastChampion);
                        $olympicGlobal->updWinCont(1);
                        $silverBase = self::initSilverPool();
                        RPCContext::getInstance()->sendFilterMessage('arena',
                            SPECIAL_ARENA_ID::OLYMPIC,
                            PushInterfaceDef::OLYMPIC_SILVER_POOL,
                            array('totalSilverPool' => $silverBase)
                        );
                    }
                }

                //将冠军写入olympic_global
                $olympicGlobal->updLastChampion($uid);
                $olympicGlobal->update();

                RewardUtil::reward3DtoCenter($uid, array($rewardData), RewardSource::OLYMPIC_FIRST, array('rank' => $rank));
                MailTemplate::sendOlympic(MailTemplateID::OLYMP_FIRST, $uid, $rank);

                //跟新冠军次数成就
                EnAchieve::updateOlympicChampionNum($uid, self::getWinCont());
            }
            elseif($rank == 2)
            {
                RewardUtil::reward3DtoCenter($uid, array($rewardData), RewardSource::OLYMPIC_SECOND, array('rank' => $rank));
                MailTemplate::sendOlympic(MailTemplateID::OLYMP_SECOND, $uid, $rank);
            }
            else
            {
                RewardUtil::reward3DtoCenter($uid, array($rewardData), RewardSource::OLYMPIC_NORMAL_RANK, array('rank' => $rank));
                MailTemplate::sendOlympic(MailTemplateID::OLYMP_NORMAL_RANK, $uid, $rank);
            }
            //完成擂台赛排名成就
            EnAchieve::updateOlympicNormal($uid, $rank);

        }
        Logger::info('rewarduser end');
    }

    /**
     * 奖池奖励
     * $uid 本届冠军uid
     */
    public static function rewardPool($champion, $lastChampion)
    {
        Logger::info('rewardPool start');
        $winCont = self::getWinCont();  //上届冠军连胜次数

        $championRate = btstore_get()->CHALLENGE[OlympicDef::CHALLENGE_DEFAULT_ID][ChallengeCsvDef::CHAMPION_RATE]->toArray();  //冠军分成百分比
        $terminatorRate = btstore_get()->CHALLENGE[OlympicDef::CHALLENGE_DEFAULT_ID][ChallengeCsvDef::TERMINATOR_RATE]->toArray();   //终结者分成百分比
        $otherRate = btstore_get()->CHALLENGE[OlympicDef::CHALLENGE_DEFAULT_ID][ChallengeCsvDef::OTHER_RATE]->toArray();   //其他参与者分成百分比
        $rewardIndex = $winCont < count($championRate) ? $winCont : count($championRate);
        $silverPool = self::getCurSilverPool();

        //奖池奖励--冠军
        if(!empty($lastChampion))
        {
            $silverForChampion = intval(ceil($silverPool * $championRate[$rewardIndex - 1] / 10000));
            $rewardForChampion = array(
                RewardType::SILVER => $silverForChampion
            );
            EnReward::sendReward($lastChampion, RewardSource::OLYMPIC_REWARDPOOL, $rewardForChampion);
            MailTemplate::sendOlympicPoolBeCut($lastChampion, $winCont, $silverForChampion);
            Logger::info('sendReward For lastChampion:%d, silver:%d', $lastChampion, $silverForChampion);
        }

        //奖池奖励--终结者
        if(!empty($champion))
        {
            $silverForTerminator = intval(ceil($silverPool * $terminatorRate[$rewardIndex - 1] / 10000));
            $rewardForTerminator = array(
                RewardType::SILVER => $silverForTerminator
            );
            EnReward::sendReward($champion, RewardSource::OLYMPIC_REWARDPOOL, $rewardForTerminator);
            MailTemplate::sendOlympicPoolCut($champion, $winCont, $silverForTerminator, EnUser::getUserObj($lastChampion)->getTemplateUserInfo());
            Logger::info('sendReward For terminator:%d, silver:%d', $champion, $silverForTerminator);
        }

        //奖池奖励--其他人
        $rankInst = OlympicRank::getInstance();
        $arrRankInfo = $rankInst->getAllSignUpUser();
        $arrRandUid = array_keys(Util::arrayIndex($arrRankInfo, OlympicRankDef::FIELD_UID));
        $arrUser = OlympicDao::getUserOlympicWithWhere(UserOlympicDef::$ALL_FIELD,
            array(
                array(UserOlympicDef::FIELD_CHEERUID, '!=', 0), //UserOlympicDef::FIELD_UID, 'NOT IN', $arrRandUid,
                array(UserOlympicDef::FIELD_CHEER_TIME, '>', self::getPreOlympicStartTime()),
            )
        );
        $arrUid = Util::arrayExtract($arrUser, UserOlympicDef::FIELD_UID);
        //样本加上32强选手
        $arrUid = array_merge($arrUid, $arrRandUid);

        foreach($arrUid as $uid)
        {
            if(empty($uid) || $uid == $champion || $uid == $lastChampion)
            {
                continue;
            }
            //奖池奖励--终结者
            $silverForOther = intval(ceil($silverPool * $otherRate[$rewardIndex - 1] / 10000));
            $rewardForOther = array(
                RewardType::SILVER => $silverForOther
            );
            EnReward::sendReward($uid, RewardSource::OLYMPIC_REWARDPOOL, $rewardForOther);
            MailTemplate::sendOlympicPoolParticipate($uid, $winCont, $silverForOther, EnUser::getUserObj($lastChampion)->getTemplateUserInfo());
            Logger::info('sendReward For other:%d, silver:%d', $uid, $silverForOther);
        }
        Logger::info('rewardPool end');
    }

    /**
     * 助威奖励
     */
    public static function updateCheerAndWin()
    {
        Logger::info('updateCheerAndWin start');
        $rankInst = OlympicRank::getInstance();
        $allSignUpUser = Util::arrayIndex($rankInst->getAllSignUpUser(), OlympicRankDef::FIELD_OLYMPICINDEX);
        if(empty($allSignUpUser))
        {
            Logger::info(' allSignUpUser is empty ');
            return;
        }
        foreach($allSignUpUser as $signUpIndex => $rankInfo)
        {
            $rank = $rankInfo[OlympicRankDef::FIELD_FINAL_RANK];
            if($rank != 1)
            {
                continue;
            }
            $campion = $rankInfo[OlympicRankDef::FIELD_UID]; //冠军
        }
        if(empty($campion))
        {
            throw new InterException(' have no campion ');
        }
        //更新冠军的连胜次数    和其他人的连胜次数
        UserOlympic::getInstance($campion)->addWinNum(1);
        UserOlympic::getInstance($campion)->update();
        OlympicDao::updateUserOlympicWithWherre(array(UserOlympicDef::FIELD_WIN_ACCUMNUM => 0), array(UserOlympicDef::FIELD_UID, '!=', $campion));
        
        $arrCheerUser = Util::arrayIndex(
            OlympicDao::getUserOlympicCheer($campion, UserOlympicDef::$ALL_FIELD, self::getPreOlympicStartTime()),
            UserOlympicDef::FIELD_UID
        );
        $rewardId = btstore_get()->CHALLENGE[OlympicDef::CHALLENGE_DEFAULT_ID][ChallengeCsvDef::CHEER_PRIZE_ID];
        $rewardData = btstore_get()->CHALLENGEREWARD[$rewardId][ChallengeRewardCsvDef::REWARD]->toArray();
        foreach($arrCheerUser as $uid => $userInfo)
        {
            RewardUtil::reward3DtoCenter($uid, array($rewardData), RewardSource::OLYMPIC_CHEER);
            MailTemplate::sendOlympic(MailTemplateID::OLYMP_CHEER, $uid);
            UserOlympic::getInstance($uid)->addCheerValidNum(1);
            UserOlympic::getInstance($uid)->update();
        }
        Logger::info('updateCheerAndWin end');
    }

    /**
     * 幸运奖励
     */
    public static function rewardLucky()
    {
        Logger::info('rewardLucky start');
        $rankInst = OlympicRank::getInstance();
        $arrRankInfo = $rankInst->getAllSignUpUser();
        $arrRandUid = array_keys(Util::arrayIndex($arrRankInfo, OlympicRankDef::FIELD_UID));
        $arrUser = OlympicDao::getUserOlympicWithWhere(UserOlympicDef::$ALL_FIELD,
            array(
                array(UserOlympicDef::FIELD_CHEERUID, '!=', 0), //UserOlympicDef::FIELD_UID, 'NOT IN', $arrRandUid,
                array(UserOlympicDef::FIELD_CHEER_TIME, '>', self::getPreOlympicStartTime()),
            )
        ); //幸运奖抽样样本
        $arrUid = Util::arrayExtract($arrUser, UserOlympicDef::FIELD_UID);
        //样本加上32强选手
        $arrUid = array_merge($arrUid, $arrRandUid);

        $luckyNum = btstore_get()->CHALLENGE[OlympicDef::CHALLENGE_DEFAULT_ID][ChallengeCsvDef::LUCKY_NUM];
        if(count($arrUid) < $luckyNum)
        {
            $luckyNum = count($arrUid);
        }
        if($luckyNum == 0)
        {
            Logger::info('luckyNum is 0');
            return;
        }
        $rewardId = btstore_get()->CHALLENGE[OlympicDef::CHALLENGE_DEFAULT_ID][ChallengeCsvDef::CHEER_LUCKY_PRIZE_ID];
        $rewardData = btstore_get()->CHALLENGEREWARD[$rewardId][ChallengeRewardCsvDef::REWARD]->toArray();

        $arrLuckyUidKey = array();
        if($luckyNum == 1)
        {
            $arrLuckyUidKey = array(array_rand($arrUid, $luckyNum));
        }
        else
        {
            $arrLuckyUidKey = array_rand($arrUid, $luckyNum);
        }
        foreach($arrLuckyUidKey as $luckyUidKey)
        {
            $luckUid = $arrUid[$luckyUidKey];
            RewardUtil::reward3DtoCenter($luckUid, array($rewardData), RewardSource::OLYMPIC_LUCKY);
            MailTemplate::sendOlympic(MailTemplateID::OLYMP_LUCKY, $luckUid);
            UserOlympic::getInstance($luckUid)->update();
        }
        Logger::info('rewardLucky end');
    }
    
    public static function getTodayWeek()
    {
        $week = Util::getTodayWeek();
        if($week == 0)
        {
            $week = 7;
        }
        return $week;
    }

    public static function isOlympicSwitchOpen($uid)
    {
        if(EnSwitch::isSwitchOpen(SwitchDef::OLYMPIC) == false)
        {
            throw new FakeException('user:%d does not open the olympic', $uid);
        }
    }
    
    public static function getRetrieveInfo($uid)
    {
    	Logger::trace('OlympicLogic::getRetrieveInfo param[uid:%d] begin...', $uid);
    	
    	if(!EnSwitch::isSwitchOpen(SwitchDef::OLYMPIC))
    	{
    		Logger::trace('OlympicLogic::getRetrieveInfo olympic switch not open for uid[%d], can not retrieve, return', $uid);
    		return FALSE;
    	}
    	
    	$now = Util::getTime();
    	$todayStartTime = self::getPreOlympicStartTime();
    	$stageInfo = self::getStageInfo();
    	if ($stageInfo['stage'] == OlympicStage::AFTER_OLYMPIC
            && $stageInfo['status'] == OlympicStageStatus::END
            && ($now < $todayStartTime 				// 这是今天擂台赛开始前的情况
            	|| $now > $todayStartTime + 300)) 	// 这是今天擂台赛结束后的情况，向后偏移一些时间，以避免临界值的情况
    	{
    		// 判断是否在32强，在32强肯定不能资源追回
    		$rankInst = OlympicRank::getInstance();
    		$rankInfo = $rankInst->getInfoByUid($uid);
    		if (!empty($rankInfo))
    		{
    			Logger::trace('OlympicLogic::getRetrieveInfo uid[%d] is in 32 rankInfo[%s], can not retrieve, return', $uid, $rankInfo);
    			return FALSE;
    		}
    		
    		// 是否领取过助威奖，领过助威奖，不能资源追回
    		$reward = EnReward::getRewardByUidTime($uid, RewardSource::OLYMPIC_CHEER, ($now < $todayStartTime ? $todayStartTime - SECONDS_OF_DAY : $todayStartTime));
    		if (!empty($reward)) 
    		{
    			Logger::trace('OlympicLogic::getRetrieveInfo uid[%d] is recv cheer reward at time[%s], now[%s], today start time[%s], can not retrieve, return', $uid, 
    							strftime('%Y%m%d-%H%M%S', $reward[0][RewardDef::SQL_SEND_TIME]),
            					strftime('%Y%m%d-%H%M%S', $now),
            					strftime('%Y%m%d-%H%M%S', $todayStartTime));
    			return FALSE;
    		}
    		
    		// 是否领取过幸运奖，领过幸运奖，不能资源追回
    		$reward = EnReward::getRewardByUidTime($uid, RewardSource::OLYMPIC_LUCKY, ($now < $todayStartTime ? $todayStartTime - SECONDS_OF_DAY : $todayStartTime));
    		if (!empty($reward))
    		{
    			Logger::trace('OlympicLogic::getRetrieveInfo uid[%d] is recv luck reward at time[%s], now[%s], today start time[%s], can not retrieve, return', $uid, 
    							strftime('%Y%m%d-%H%M%S', $reward[0][RewardDef::SQL_SEND_TIME]),
            					strftime('%Y%m%d-%H%M%S', $now),
            					strftime('%Y%m%d-%H%M%S', $todayStartTime));
            	return FALSE;
    		}
    		
    		// 终于可以追回啦
    		$logInst = OlympicLog::getInstance(OlympicLogType::BATTLE_PROGRESS);
    		$stageInfo = $logInst->getCurStageInfo();
    		$lastEndTime = (isset($stageInfo[OlympicLogDef::VA_INFO_PROGRESS_UPDATETIME]) ? $stageInfo[OlympicLogDef::VA_INFO_PROGRESS_UPDATETIME] : 0);
    		$nextStartTime = ($now < $todayStartTime ? $todayStartTime : $todayStartTime + SECONDS_OF_DAY);
    		$ret = array($lastEndTime, $nextStartTime);
    			
    		Logger::trace('OlympicLogic::getRetrieveInfo uid[%d] can retrieve, return[ret:%s]', $uid, $ret);
    		return $ret;
    	}
    	else 
    	{
    		Logger::trace('OlympicLogic::getRetrieveInfo can not retrieve, now[%s] todayStartTime[%s] stage[%d] status[%d]', strftime('%Y%m%d%H%M%S', $now), strftime('%Y%m%d%H%M%S', $todayStartTime),  $stageInfo['stage'],  $stageInfo['status']);
    	}
    	
    	Logger::trace('OlympicLogic::getRetrieveInfo param[uid:%d] end...', $uid);
    	return FALSE;
    }
    
    public static function getTimeConfig()
    {
    	return array('begin_time' => self::getPreLiminaryMatchStartTime());
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */