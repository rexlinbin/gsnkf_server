<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MineralLogic.class.php 258616 2016-08-26 09:09:00Z QingYao $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mineral/MineralLogic.class.php $
 * @author $Author: QingYao $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-26 09:09:00 +0000 (Fri, 26 Aug 2016) $
 * @version $Revision: 258616 $
 * @brief 
 *  
 **/
class MineralLogic
{
    public static $grabbedGoldPit = array();
    public static function capturePitNotOccupied($uid,$domainId,$pitId)
    {
        $ret = self::capturePit($uid,$domainId, $pitId, MineralDef::CAPTURE_TYPE_CAPTURE_FREE);
        return $ret;
    }
    
    public static function grabPitByGold($uid,$domainId,$pitId)
    {
        if(self::isDuringCaptureTime($domainId) == TRUE)
        {
            throw new FakeException('can not capture this pit by gold.now is during capture time');
        }
        if(Enuser::getUserObj($uid)->subGold(MineralConf::$GRAB_PIT_BY_GOLD_NUM,
                StatisticsDef::ST_FUNKEY_GRAB_MINERAL) == FALSE)
        {
            throw new FakeException('sub gold failed;');
        }
        $ret = self::capturePit($uid,$domainId, $pitId,MineralDef::CAPTURE_TYPE_GRAB_BY_GOLD);
        $ret['gold'] = MineralConf::$GRAB_PIT_BY_GOLD_NUM + $ret['gold'];
        return $ret;
    }
    
    public static function grabPit($uid,$domainId,$pitId)
    {
        if(self::isDuringCaptureTime($domainId) == FALSE)
        {
            throw new FakeException('can not capture this pit freely.now is not during capture time');
        }
        $ret = self::capturePit($uid,$domainId, $pitId,MineralDef::CAPTURE_TYPE_GRAB);
        return $ret;
    }
    
    public static function captureGoldPit($uid,$domainId,$pitId)
    {
        $userObj = Enuser::getUserObj($uid);
        $needGold = self::goldPitNeedGold($domainId, $pitId);
        if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_CAPTURE_GOLDPIT) == FALSE)
        {
            throw new FakeException('sub gold failed.');
        }
        $ret = self::capturePit($uid,$domainId, $pitId, MineralDef::CAPTURE_TYPE_CAPTURE_FREE);
        $ret['gold'] += $needGold;
        return $ret;
    }
    
    public static function grabGoldPit($uid,$domainId,$pitId)
    {
        if(self::isDuringCaptureTime($domainId) == FALSE)
        {
            throw new FakeException('can not capture this pit freely.now is not during capture time');
        }
        $userObj = Enuser::getUserObj($uid);
        $needGold = self::goldPitNeedGold($domainId, $pitId);
        if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_CAPTURE_GOLDPIT) == FALSE)
        {
            throw new FakeException('sub gold failed.');
        }
        self::$grabbedGoldPit = array($domainId,$pitId);
        $ret = self::capturePit($uid,$domainId, $pitId, MineralDef::CAPTURE_TYPE_GRAB);
        $ret['gold'] += $needGold;
        self::$grabbedGoldPit = array();
        return $ret;
    }
    
    private static function isDuringCaptureTime($domianId)
    {
        //普通矿区 任何时间抢矿不花金币
        if(!MineralLogic::isGoldDomain($domianId))
        {
            return TRUE;
        }
        $now = Util::getTime();
        $date = date("Y-m-d",$now);
        $openTime = strtotime($date." ".MineralConf::$CAPTURE_PIT_START_TIME.":00:00");
        $endTime = strtotime($date." ".MineralConf::$CAPTURE_PIT_END_TIME.":00:00");
        if($now > $openTime &&  ($now  < $endTime))
        {
            return TRUE;
        }
        return FALSE;
    }
    
    private static function goldPitNeedGold($domainId,$pitId)
    {
        return btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_GOLDPIT_NEED_GOLD];
    }
    
    public static function grabGoldPitByGold($uid,$domainId,$pitId)
    {
        if(self::isDuringCaptureTime($domainId) == TRUE)
        {
            throw new FakeException('can not capture this pit by gold.now is during capture time');
        }
        $userObj = Enuser::getUserObj($uid);
        $needGold = self::goldPitNeedGold($domainId, $pitId);
        if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_CAPTURE_GOLDPIT) == FALSE)
        {
            throw new FakeException('sub gold failed.');
        }
        if($userObj->subGold(MineralConf::$GRAB_PIT_BY_GOLD_NUM,
                StatisticsDef::ST_FUNKEY_GRAB_MINERAL) == FALSE)
        {
            throw new FakeException('sub gold failed');
        }
        self::$grabbedGoldPit = array($domainId,$pitId);
        $ret = self::capturePit($uid,$domainId, $pitId, MineralDef::CAPTURE_TYPE_GRAB_BY_GOLD);
        $ret['gold'] += $needGold + MineralConf::$GRAB_PIT_BY_GOLD_NUM;
        self::$grabbedGoldPit = array();
        return $ret;
    }
    
    private static function getLockerName($domainId,$pitId)
    {
        return MineralDef::MINERAL_PIT_LOCKER_PRE
            .	$domainId	.	'_'	.$pitId;
    }
    
    public static function capturePit($uid, $domainId, $pitId, $type)
    {
        Logger::trace('capturePit %s %s %s.',$domainId,$pitId,$type);
        
        //检查是否能抢矿
        self::canCapturePit($uid,$domainId, $pitId);
        
        //加锁
        $subGold = 0;
        $locker	= new Locker();
        $locker->lock(self::getLockerName($domainId, $pitId));
        try
        {
            $pitObj = PitManager::getInstance()->getPitObj($domainId, $pitId);
            $captureUid = $pitObj->getCapture();
            
            //判断是否能占矿或者抢矿
            if($type == MineralDef::CAPTURE_TYPE_CAPTURE_FREE)
            {
                if(!empty($captureUid))
                {
                    throw new FakeException('this pit with domain %s pit %s is occupied.',$domainId,$pitId);
                }
            }
            else if($type == MineralDef::CAPTURE_TYPE_GRAB || $type == MineralDef::CAPTURE_TYPE_GRAB_BY_GOLD)
            {
                if(empty($captureUid))
                {
                    throw new FakeException('this pit with domain %s pit %s is not occupied.',$domainId,$pitId);
                }
            }
            else
            {
                throw new FakeException('no such cpature type %d',$type);
            }
            if($pitObj->isInProtectTime() == TRUE)
            {
                throw new FakeException('domain %d pit %d can not be captured.it is in protect time',$domainId,$pitId);
            }
            //是否是抢夺  从别人那里占领矿坑
            if(!empty($captureUid) && ($captureUid == $uid))
            {
                throw new FakeException('this pit(domain %s pit %s) is belong to yourself.',$domainId,$pitId);
            }
            //获取矿坑占有者对象
            $user = EnUser::getUserObj($uid);
            $btFmt = $user->getBattleFormation();
            $atkRet = self::fightForPit($uid, $btFmt, $domainId, $pitId,$captureUid);
            $isSuccess = (BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'])?TRUE:FALSE;
            $capAcquire = array();
            Logger::trace('capturePit isSuccess %d',$isSuccess);
            if($isSuccess)
            {
                //给协助军发邮件
                $arrGuard = $pitObj->getArrGuard();
                foreach($arrGuard as $guardUid => $guardInfo)
                {
                    $acquire = $pitObj->getGuardAcquireToNow($guardUid);
                    MailTemplate::sendMineralHelper(MailTemplateID::MINERAL_HELPER_BEOCCUPIED,
                            $guardUid, $acquire['time'], $acquire['silver'], $uid);
                }
                //计算原矿主的收益
                $capAcquire = $pitObj->getCaptureAcquireToNow();                
                $pitObj->changeCapture($uid);
                $pitObj->doWhenChangeOwner($uid);
                EnActive::addTask(ActiveDef::MINERAL);
                //新服活动调用的需求2016-5-12 18:01:48
                EnNewServerActivity::updateMineral($uid);
                //老玩家回归占领或协助资源矿num次
                EnWelcomeback::updateTask(WelcomebackDef::TASK_TYPE_MINERAL, 1);
            }
            //给矿主和抢矿的人发邮件
            $domainType = btstore_get()->MINERAL[$domainId]['domain_type'];
            self::sendMailAfterFight($uid,$isSuccess, $atkRet['server']['brid'],
                    $domainType,$captureUid,$capAcquire,$domainId);
            $user->addFightCd(CopyConf::$FIGHT_CD_TIME);
            $user->update();
            PitManager::getInstance()->save();
            if(is_bool($isSuccess))
            {
                Logger::trace('capturePit isSuccess is bool');
            }
            else 
            {
                Logger::trace('capturePit isSuccess is not bool');
            }
            if($isSuccess)
            {
                Logger::trace('capturePit isSuccess is true');
            }
            else 
            {
                Logger::trace('capturePit isSuccess is false');
            }
            Logger::trace('capturePit isSuccess %d',$isSuccess);
        }
        catch ( Exception $e )
        {
            $locker->unlock(self::getLockerName($domainId, $pitId));
            throw $e;
        }
        $locker->unlock(self::getLockerName($domainId, $pitId));
        if((($type === MineralDef::CAPTURE_TYPE_GRAB) ||
                ($type === MineralDef::CAPTURE_TYPE_GRAB_BY_GOLD)) &&
                ($isSuccess))
        {
            $capture = EnUser::getUserObj($captureUid);
            RPCContext::getInstance()->sendMsg(
                    array(0),
                    PushInterfaceDef::MINERAL_ROB_BROADCAST,
                    array(
                            'domain_id'=>$domainId,
                            'pit_id'=>$pitId,
                            'pre_capture'=>$capture->getUname(),
                            'now_capture'=>$user->getUname(),
                            'rob_time'=>Util::getTime()
                    )
            );
            $robInfo = array(
                    'rob_time'=>Util::getTime(),
                    'domain_id'=>$domainId,
                    'pit_id'=>$pitId,
                    'pre_capture'=>$captureUid,
                    'now_capture'=>$uid
            );
            RPCContext::getInstance()->executeTask(SPECIAL_UID::MINERAL_ROB_LOG_UPDATE,
                     'mineral.updateRobLog', array($robInfo));
        }
        Logger::trace("EnMission:doMission uid:%d", $uid);
        EnMission::doMission($uid, MissionType::MINERAL, 1);
        return array(
                MineralDef::CAPTURE_PIT_ERROR_CODE=>MineralDef::CAPTURE_PIT_OK,
                'fight_ret'=>$atkRet['client'],
                'fight_cd'=>$user->getFightCdTime(),
                'appraisal' => $atkRet['server']['appraisal'],
                'gold'=>$subGold,
                'pitInfo'=>$pitObj->getPitInfo()
        );
    }
    
    public static function updateRobLog($robInfo)
    {
        $arrRobLog = MineralDAO::getRobLog();
        while(count($arrRobLog) >= MineralDef::MAX_ROBLOG_NUM)
        {
            unset($arrRobLog[0]);
            $arrRobLog = array_merge($arrRobLog);
        }
        $arrRobLog = array_merge($arrRobLog,array($robInfo));
        MineralDAO::updateRobLog($arrRobLog);
    }
    
    private static function canCapturePit($uid,$domainId, $pitId)
    {
        $user =	EnUser::getUserObj($uid);
        if($user->getCurExecution() < MineralConf::$CAPTURE_PIT_NEED_EXECUTION)
        {
            throw new FakeException('execution is not enough.can not capturepit');
        }
        if($user->getFightCdTime()	>	Util::getTime())
        {
            throw new FakeException('fight is not cool down.can not capturepit');
        }
        $canGetNum = MineralDef::OCCUPY_PIT_LIMIT;
        if(self::isGoldDomain($domainId))
        {
            $occupyNum = MineralDAO::getGoldPitNumByUid($uid);
            $guardInfo = array();
        }
        else
        {
            $occupyNum = MineralDAO::getPitNumByUid($uid);
            $guardInfo = MineralDAO::getGuardInfoByUid($uid);
        }
        $guardNum = empty($guardInfo)?0:1;
        if($occupyNum+$guardNum >= $canGetNum)
        {
            throw new FakeException('can not capture pit.user has occupynum %d.guardnum %d',$occupyNum,$guardNum);
        }
    }
    
    
    private static function fightForPit($uid, $btFmt,$domainId,$pitId,$capturerUid)
    {
    
        $armyBtFmt = array();
        $armyId = 0;
        $callback = array();
        $btType = 0;
        $winCon     = array();
        $extraInfo  = array();
        if(empty($capturerUid))
        {
            $armyId	= btstore_get()->MINERAL[$domainId]['pits'][$pitId][PitArr::GUARDARMY];
            $armyBtFmt = EnFormation::getMonsterBattleFormation($armyId);
        }
        else
        {
            $capture = EnUser::getUserObj($capturerUid);
            $armyBtFmt	=	$capture->getBattleFormation();
        }
        if(!empty($armyId))//打土著部队
        {
            $btType = btstore_get()->ARMY[$armyId]['fight_type'];
            $winCon = CopyUtil::getVictoryConditions($armyId);
            $extraInfo = CopyUtil::getExtraBtInfo($armyId, BattleType::MINERAL);
            $atkRet = EnBattle::doHero($btFmt, $armyBtFmt, $btType, $callback, $winCon, $extraInfo);
        }
        else
        {
            $player = EnUser::getUserObj($btFmt['uid']);
            $capture = EnUser::getUserObj($armyBtFmt['uid']);
            $type = EnBattle::setFirstAtk(0, $player->getFightForce() >= $capture->getFightForce());
            $atkRet = EnBattle::doHero($btFmt, $armyBtFmt, $type, $callback, $winCon, $extraInfo);
        }
        $userObj = EnUser::getUserObj($uid);
        if($userObj->subExecution(MineralConf::$CAPTURE_PIT_NEED_EXECUTION) == FALSE)
        {
            throw new FakeException('lack execution');
        }
        return $atkRet;
    }
    
    
    private static function sendMailAfterFight($robUid,$isSuccess,$brid,
            $domainType,$captureUid,$grab,$domainId)
    {
        $user =	EnUser::getUserObj($robUid);
        if(empty($captureUid))
        {
            return;
        }
        $capturer = EnUser::getUserObj($captureUid);
        $capturerInfo = $capturer->getTemplateUserInfo();
        $userInfo =$user->getTemplateUserInfo();
        //免费抢矿时间
        if(MineralLogic::isDuringCaptureTime($domainId) == TRUE)
        {
            //给攻击者发信息
            MailTemplate::sendMineralAttack($user->getUid(), $capturerInfo, $brid, $isSuccess);
            if(!empty($grab))
            {
                if(!empty(self::$grabbedGoldPit))
                {
                    MailTemplate::sendMineralOneHour($capturer->getUid(), $userInfo, $grab['time'],$grab['silver'],$grab['guildSilver'],$domainType,$brid,$grab['iron']);
                }
                else
                {
                    MailTemplate::sendMineralDefend($capturer->getUid(), $userInfo, $brid, !$isSuccess,$domainType,$grab['time'],$grab['silver'],$grab['guildSilver'],$grab['iron']);
                }
            }
            else
            {
                MailTemplate::sendMineralDefend($capturer->getUid(), $userInfo, $brid, !$isSuccess,$domainType);
            }
        }
        else
        {
            MailTemplate::sendMineralOccupyForceAtk($user->getUid(), $capturerInfo, $isSuccess);
            if(!empty($grab))
            {
                if(!empty(self::$grabbedGoldPit))
                {
                    MailTemplate::sendMineralOneHour($capturer->getUid(), $userInfo, $grab['time'],$grab['silver'],$grab['guildSilver'],$domainType,$brid,$grab['iron']);
                }
                else
                {
                    MailTemplate::sendMineralOccupyForce($capturer->getUid(),$userInfo,$brid,!$isSuccess,$domainType,$grab['time'],$grab['silver'],$grab['guildSilver'],$grab['iron']);
            
                }
            }
            else
            {
                MailTemplate::sendMineralOccupyForce($capturer->getUid(),$userInfo,$brid,!$isSuccess,$domainType);
            }
        }
    }
    
    
    public static function giveUpPit($uid,$domainId,$pitId)
    {
        $locker = new Locker();
        $locker->lock(self::getLockerName($domainId, $pitId));
        try{
            $pitObj = PitManager::getInstance()->getPitObj($domainId, $pitId);
            $captureUid = $pitObj->getCapture();
            if($captureUid != $uid)
            {
                throw new FakeException('this pit is not belong to you.your id is %s,the owner is %s.',$uid,$captureUid);
            }
            $acquire = $pitObj->getCaptureAcquireToNow();  //获取资源矿收获
            $silver = $acquire['silver'];
            //给矿主邮件
            MailTemplate::sendMineralDue($uid, $silver,$acquire['time'], $acquire['guildSilver'],$acquire['iron']);
            //给协助军发邮件 
            $arrGuard = $pitObj->getArrGuard();
            foreach($arrGuard as $guardUid => $guardInfo)
            {
                $acquire = $pitObj->getGuardAcquireToNow($guardUid);
                MailTemplate::sendMineralHelper(MailTemplateID::MINERAL_GIVEUP_BYOWNER,
                        $guardUid, $acquire['time'], $acquire['silver'], $uid);
            }
            $pitObj->doWhenGiveUpOrDue();
            $pitObj->changeCapture(0);
            PitManager::getInstance()->save();
            Enuser::getUserObj()->update();
        }catch(Exception $e)
        {
            $locker->unlock(self::getLockerName($domainId, $pitId));
            throw $e;
        }
        $locker->unlock(self::getLockerName($domainId, $pitId));
        return array(
                MineralDef::CAPTURE_PIT_ERROR_CODE=>'ok',
                'silver'=>$silver
        );
    }
    
    
    /**
     * 获取某页的资源矿信息   只返回非空矿  其他空矿信息由前端补全
     * @param int $domainID
     */
    public static function getPitByDomain($domainId)
    {
        if(!isset(btstore_get()->MINERAL[$domainId]))
        {
            throw new ConfigException('no such pit domain with domainID %s.',$domainId);
        }
        $arrPitInfo = PitManager::getInstance()->getArrPitByPitId($domainId);
        return array_merge($arrPitInfo[$domainId],array());
    }
    
    public static function getSelfPitsInfo($uid)
    {
        $arrDbPit = MineralDAO::getArrPitByUid($uid);
        $guardInfo = MineralDAO::getGuardInfoByUid($uid);
        $arrPit = array();
        foreach($arrDbPit as $pitInfo)
        {
            $pitId = $pitInfo[TblMineralField::PITID];
            $domainId = $pitInfo[TblMineralField::DOMAINID];
            $arrPit[$domainId][] = $pitId;
        }
        $guardStartTime = 0;
        if(!empty($guardInfo))
        {
            $pitId = $guardInfo[TblMineralGuards::PITID];
            $domainId = $guardInfo[TblMineralGuards::DOMAINID];
            $arrPit[$domainId][] = $pitId;
            $guardStartTime = $guardInfo[TblMineralGuards::GUARDTIME];
        }
        $arrPitInfo = array();
        foreach($arrPit as $domainId => $arrPitId)
        {
            $arrPitId = array_unique($arrPitId);
            $retTmp = PitManager::getInstance()->getArrPitByPitId($domainId,$arrPitId);
            $arrPitInfo = array_merge($arrPitInfo,$retTmp[$domainId]);
        }
        PitManager::getInstance()->save();
        return array('pits'=>$arrPitInfo,'guard_start_time'=>$guardStartTime);
    }
    
    /**
     * 探索空旷（一键探索）  找出没有空旷的矿页  返回此页的矿信息
     */
    public static function explorePit($pitType)
    {
        $curDomain    =    RPCContext::getInstance()->getSession(MINERAL_SESSION_NAME::DOMAINID);
        if(empty($curDomain))
        {
            $domainType = MineralType::SENIOR;
        }
        else
        {
            $domainType = intval(btstore_get()->MINERAL[$curDomain]['domain_type']);
        }
        $ret = MineralDAO::explorePit($domainType, $pitType);
        if(empty($ret))
        {
            return array();
        }
        $domainId = $ret[TblMineralField::DOMAINID];
        $pits = self::getPitByDomain($domainId);
        return $pits;
    }
    
    public static function duePit($uid,$domainId,$pitId)
    {
        $locker = new Locker();
        $locker->lock(self::getLockerName($domainId, $pitId));
        $acquire = array();
        try
        {
            $pitObj = PitManager::getInstance()->getPitObj($domainId, $pitId);
            $captureUid = $pitObj->getCapture();
            if($captureUid != $uid)
            {
                Logger::warning('duePit domain %d pit %d the capturer %s is not user %s.',$domainId,$pitId,$captureUid,$uid);
                $locker->unlock(self::getLockerName($domainId, $pitId));
                return $acquire;
            }
            $acquire = $pitObj->getCaptureAcquireToNow();
            MailTemplate::sendMineralDue($uid, $acquire['silver'],$acquire['time'],$acquire['guildSilver'],$acquire['iron']);
            $arrGuard = $pitObj->getArrGuard();
            foreach($arrGuard as $guardUid => $guradInfo)
            {
                $acquire = $pitObj->getGuardAcquireToNow($guardUid);
                MailTemplate::sendMineralHelper(MailTemplateID::MINERAL_HELPER_OCCUPY_TIMEUP,
                        $guardUid, $acquire['time'], $acquire['silver']);
            }
            $pitObj->due();
            $pitObj->doWhenGiveUpOrDue();
            PitManager::getInstance()->save();
            Enuser::getUserObj($uid)->update();
        }
        catch ( Exception $e )
        {
            $locker->unlock(self::getLockerName($domainId, $pitId));
            throw $e;
        }
        $locker->unlock(self::getLockerName($domainId, $pitId));
        return $acquire;
    }
    
    
    public static function occupyPit($uid,$domainId, $pitId)
    {
        Logger::trace('MineralLogic::occupyPit Start. domainId:%d, pidId:%d', $domainId, $pitId);
    
        //改变矿坑守卫军要加锁
        $locker = new Locker();
        $locker->lock(self::getLockerName($domainId, $pitId));
        try
        {
            $occupyNum = MineralDAO::getPitNumByUid($uid);
            $guardInfo = MineralDAO::getGuardInfoByUid($uid);
            $guardNum = empty($guardInfo)?0:1;
            if($occupyNum >= 1 || ($guardNum >= 1))
            {
                throw new FakeException('user %s has capture or guard pit.can not be guarder of other pit',$uid);
            }
            $pitObj = PitManager::getInstance()->getPitObj($domainId, $pitId);
            //没有矿主的矿不能被守卫
            if($pitObj->getCapture() == 0)
            {
                throw new FakeException('domainid:%s,pitid:%s is not occupies,can not be guared.',$domainId,$pitId);
            }
            //检查你是否是该矿的矿主
            if($pitObj->getCapture() == $uid)
            {
                throw new FakeException('this pit(domain %s pit %s) is belong to yourself.',$domainId,$pitId);
            }
            $guardNumLimit = self::getGuardNumLimit($domainId, $pitId);
            if($pitObj->getGuardCount() >= $guardNumLimit)
            {
                throw new FakeException('this mineral guards has reached its limit num:%d',
                         $guardNumLimit);
            }
            if($pitObj->addGuard($uid) == FALSE)
            {
                throw new FakeException('addguard %d for domain %d pit %d failed',
                        $uid,$pitObj->getDomainId(),$pitObj->getPitId());
            }
            EnActive::addTask(ActiveDef::MINERAL);
            //老玩家回归占领或协助资源矿num次
            EnWelcomeback::updateTask(WelcomebackDef::TASK_TYPE_MINERAL, 1);
            PitManager::getInstance()->save();
        }
        catch(Exception $e)
        {
            $locker->unlock(self::getLockerName($domainId, $pitId));
            throw $e;
        }
        //向前端发广播
        $locker->unlock(self::getLockerName($domainId, $pitId));
        Logger::trace("EnMission:doMission uid:%d", $uid);
        EnMission::doMission($uid, MissionType::MINERAL, 1);
        return array('errcode'=>0);
    }
    
    /**
     * 抢守卫军
     * @param int $domainId1  要枪守卫军的矿区id
     * @param int $pitId1 要枪守卫军的矿id
     * @param int $tuid 守卫军uid
     * @throws FakeException
     * @throws Exception
     */
    public static function robGuards($uid, $domainId1, $pitId1, $tuid)
    {
        Logger::trace('MineralLogic::robGuards Start. domianId1:%d, pitId1:%d, tuid:%d', $domainId1, $pitId1, $tuid);
    
        $locker = new Locker();
        $locker->lock(self::getLockerName($domainId1, $pitId1));
        try
        {
            $pits = MineralDAO::getPitsByUid($uid);//TODO:此处获取uid的所有矿，下面又通过PitManager获取每个pit，有重复db，可以优化
            if(empty($pits))
            {
                //throw new FakeException('you donot have pits:%s.', $pits);
            	Logger::warning('you donot have pits:%s.', $pits);
            	$locker->unlock(self::getLockerName($domainId1, $pitId1));
            	return array('errcode'=>1); //进攻方不是矿主
            }
            $robDomainId = 0; //实际抢矿资源区id
            $robPitId = 0;  //实际抢矿矿坑id
            $canRob = false;
            for($i = 0; $i < count($pits); $i++)
            {
                $robDomainId = $pits[$i][TblMineralField::DOMAINID];
                $robPitId = $pits[$i][TblMineralField::PITID];
                $robPitObj = PitManager::getInstance()->getPitObj($robDomainId, $robPitId);
                if($robPitObj->getGuardCount() >= self::getGuardNumLimit($robDomainId, $robPitId))
                {
                    continue;
                }
                if(self::isGoldDomain($robDomainId))
                {
                    continue;
                }
                $canRob = true;
                break;
            }
            if(!$canRob)
            {
                Logger::warning('guards is full.');
                $locker->unlock(self::getLockerName($domainId1, $pitId1));
                return array('errcode'=>2); //协助军已满
            }
            $locker->lock(self::getLockerName($robDomainId, $robPitId));
            $robPitObj = PitManager::getInstance()->getPitObj($robDomainId, $robPitId);
            $pitObj = PitManager::getInstance()->getPitObj($domainId1, $pitId1);
            if($pitObj->getCapture() == 0)
            {
                //throw new FakeException('robbed capture has no owner.domaind %d pit %d',$domainId1,$pitId1);
            	Logger::warning('this pit(domain %s pit %s) has no owner.',$robDomainId,$robPitId);
            	$locker->unlock(self::getLockerName($robDomainId, $robPitId));
            	$locker->unlock(self::getLockerName($domainId1, $pitId1));
            	return array('errcode'=>2); //防守方是空旷
            }
            if($pitObj->getCapture() == $uid)
            {
                //throw new FakeException('robbed capture owner is yourself.domaind %d pit %d',$domainId1,$pitId1);
            	Logger::warning('this pit(domain %s pit %s) is belong to yourself.',$robDomainId,$robPitId);
            	$locker->unlock(self::getLockerName($robDomainId, $robPitId));
            	$locker->unlock(self::getLockerName($domainId1, $pitId1));
            	return array('errcode'=>3); //不能抢自己矿的小弟
            }
            $arrGuard = $pitObj->getArrGuard();
            if(isset($arrGuard[$tuid]) == FALSE)
            {
                //throw new FakeException('user %d is not guard of domain %d pit %d',$tuid,$domainId1,$pitId1);
            	Logger::warning('is not guard for the pit, gurad uid:%d', $tuid);
            	$locker->unlock(self::getLockerName($robDomainId, $robPitId));
            	$locker->unlock(self::getLockerName($domainId1, $pitId1));
            	return array('errcode'=>5);
            }
            //获取进攻方的阵型 战斗
            $user = EnUser::getUserObj($uid);
            $btFmt = $user->getBattleFormation();
            $capturer = $pitObj->getCapture();
            $capturerObj = EnUser::getUserObj($capturer);
            $userFF = $user->getFightForce();
            $capturerFF = $capturerObj->getFightForce();
            $atkType = EnBattle::setFirstAtk(0, $userFF >= $capturerFF);
            $atkRet = EnBattle::doHero($btFmt, $capturerObj->getBattleFormation(), $atkType);
            //不管成功或失败 进攻方 都扣行动力 Execution行动力
            $execution = self::getRobGuardNeedExec();
            if($user->subExecution($execution) == FALSE)
            {
                throw new FakeException('not enough execution to capture.need %s now %s.',MineralConf::$CAPTURE_PIT_NEED_EXECUTION,$user->getCurExecution());
            }
            $isSuc = BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'];
    
            if($isSuc)
            {
                $acquire = $pitObj->getGuardAcquireToNow($tuid);
                MailTemplate::sendMineralHelper(MailTemplateID::MINERAL_HELPER_BESEIZED,
                            $tuid, $acquire['time'], $acquire['silver'], $uid);
                //给被抢矿主邮件
                MailTemplate::sendMineralOwner($pitObj->getCapture(), $uid, $acquire['time']);
                if($pitObj->robGuardByOther($tuid) == FALSE)
                {
                    throw new FakeException('robguardbyother %d for domain %d pit %d failed.',
                            $tuid,$pitObj->getDomainId(),$pitObj->getPitId());
                }
                if($robPitObj->addGuard($tuid) == FALSE)
                {
                    throw new FakeException('addguard %d for domain %d pit %d failed',
                            $tuid,$pitObj->getDomainId(),$pitObj->getPitId());
                }
                PitManager::getInstance()->save();   
                EnActive::addTask(ActiveDef::MINERAL); 
            }
        }
        catch(Exception $e)
        {
            $locker->unlock(self::getLockerName($robDomainId, $robPitId));
            $locker->unlock(self::getLockerName($domainId1, $pitId1));
            throw $e;
        }
        EnUser::getUserObj($uid)->update();
        $locker->unlock(self::getLockerName($robDomainId, $robPitId));
        $locker->unlock(self::getLockerName($domainId1, $pitId1));
        Logger::trace('MineralLogic::robGuards End. domianId1:%d, pitId1:%d, tuid:%d', $domainId1, $pitId1, $tuid);
        return array(
            'errcode' => 0,
            'battleRes' => $atkRet
            );
    }
    
    
    public static function delayPitDueTime($domainId, $pitId, $uid)
    {
        $locker	=	new Locker();
        $locker->lock(self::getLockerName($domainId, $pitId));
        try
        {
            $pitObj = PitManager::getInstance()->getPitObj($domainId, $pitId);
            //延期次数
            $delayTimes = $pitObj->getDelayTimes();
            if($delayTimes >= self::getDelayTimeLimit())
            {
                throw new FakeException('your delay times has reached limit');
            }
            if($pitObj->getCapture() != $uid)
            {
                throw new FakeException('this pit(domain %s pit %s) is not belong to yourself.',$domainId,$pitId);
            }
            //扣除金币和体力
            $user = EnUser::getUserObj();
            if($user->subExecution(btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_RES_DELAY_DATA][$delayTimes][2]) == FALSE)  //扣除体力
            {
                throw new FakeException('not enough execution to delay pit due time.need %s now %s.',btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_RES_DELAY_DATA][$delayTimes][2],$user->getCurExecution());
            }
            if($user->subGold(btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_RES_DELAY_DATA][$delayTimes][1], StatisticsDef::ST_FUNKEY_DELAY_PIT) == FALSE)
            {
                throw new FakeException('not enough gold to delay pit due time.need %s now %s.',btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_RES_DELAY_DATA][$delayTimes][1],$user->getGold());
            }
            $pitObj->delay();
            PitManager::getInstance()->save();
            $user->update();
            EnActive::addTask(ActiveDef::MINERAL);
        }
        catch(Exception $e)
        {
            $locker->unlock(self::getLockerName($domainId, $pitId));
            throw $e;
        }
        $locker->unlock(self::getLockerName($domainId, $pitId));
        return array(
                MineralDef::CAPTURE_PIT_ERROR_CODE => MineralDef::CAPTURE_PIT_OK
                );
    }
    
    public static function abandonPit($uid, $domainId, $pitId)
    {
        Logger::trace('MineralLogic::abandomPit End. domainId:%d, pidId:%d', $domainId, $pitId);
        return self::endPitGuard($uid, $domainId, $pitId,'abandon');
    }
    
    public static function endPitGuard($uid,$domainId,$pitId,$type='due')
    {
        //改变矿坑守卫军要加锁
        $locker = new Locker();
        $locker->lock(self::getLockerName($domainId, $pitId));
        try
        {
            $pitObj = PitManager::getInstance()->getPitObj($domainId, $pitId);
            if(empty($pitObj))
            {
                throw new FakeException('no such pit with domainid:%s,pitid:%s.',$domainId,$pitId);
            }
            $arrGuard = $pitObj->getArrGuard();
            if(!isset($arrGuard[$uid]))
            {
                throw new FakeException('user:%d is not a guard of this pit! domainId:%d, pitId:%d!guardInfo %s', 
                        $uid, $domainId, $pitId,$arrGuard);
            }
            $guardTime = $pitObj->getGuardTime($uid);
            if( Util::getTime() - $guardTime > 259200  )
            {
            	Logger::fatal('invalid guard time. uid:%d, domainId:%d, pitId:%d, time:%d', 
            		$uid, $domainId, $pitId, Util::getTime() - $guardTime );
            }
            else
            {
            	$pitObj->addTotalGuardTime(Util::getTime() - $guardTime);
            }
            
            $acquire = $pitObj->getGuardAcquireToNow($uid);
            if($type == 'abandon')
            {
                MailTemplate::sendMineralHelper(MailTemplateID::MINERAL_HELPER_GIVEUP,
                        $uid, $acquire['time'], $acquire['silver']);
                if($pitObj->giveUpGuard($uid) == FALSE)
                {
                    throw new FakeException('giveupguard %d for domain %d pit %d failed',
                            $uid,$domainId,$pitId);
                }
            }
            else if($type == 'due')
            {
                MailTemplate::sendMineralHelper(MailTemplateID::MINERAL_HELPER_TIMEUP,
                        $uid, $acquire['time'], $acquire['silver']);
                if($pitObj->dueGuard($uid) == FALSE)
                {
                    throw new FakeException('dueguard %d for domainid %d pitid %d failed.',
                            $uid,$domainId,$pitId);        
                }
            }
            PitManager::getInstance()->save();
        }
        catch(Exception $e)
        {
            $locker->unlock(self::getLockerName($domainId, $pitId));
            throw $e;
        }
        $locker->unlock(self::getLockerName($domainId, $pitId));
        return array('errcode'=>0);
    }
    
    
    
    
    private static function getDelayTimeLimit()
    {
        $conf = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_RES_DELAY_DATA]->toArray();
        return count($conf);
    }
    
    public static function getRobGuardNeedExec()
    {
        return btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_LOOTHELPARMY_COSTEXCETION];
    }
    
    
    public static function getGuardNumLimit($domainId,$pitId)
    {
        return btstore_get()->MINERAL[$domainId]['pits'][$pitId][PitArr::GUARDLIMITNUM];
    }
    
    public static function getPitHarvestTime($domainId,$pitId,$capture)
    {
        return btstore_get()->MINERAL[$domainId]['pits'][$pitId][PitArr::HARVESTTIME];
    }
    
    public static function getPitGuardTime()
    {
        return btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_PIT_HELPER_TIMELIMIT];
    }
    
    public static function getArrPitIdByDomainId($domainId)
    {
    	return  array_keys(btstore_get()->MINERAL[$domainId]['pits']->toArray());
    }
    
    /**
     * 
     * @param unknown_type $pitInfo
     * @param unknown_type $arrGuardInfo
     * 占领者资源矿游戏币收益=
     * int【 资源矿游戏币基础值*（占领时间+协助军协助时间总和*单个协助军收入增益/100）*资源矿系数*(玩家等级+资源矿玩家等级修正)】
                            资源矿系数 =5*10^-5
                            玩家等级=max（30，玩家实际等级）
                            int【】内向上取整
                            玩家等级以资源矿收获的时候的等级为准
     *    上述计算之后   城池加成   福利活动加成
     */
    public static function getCaptureAcquire($pitInfo,$arrGuardInfo)
    {
        $captureTime =	Util::getTime() - $pitInfo[TblMineralField::OCCUPYTIME];
        $actualCaptureTime = $captureTime;
        $uid = $pitInfo[TblMineralField::UID];  //占矿者uid
        if(empty($uid))
        {
            return array('time'=>0,'silver'=>0,'guildSilver'=>0,'iron'=>0);
        }
        $pitId = $pitInfo[TblMineralField::PITID];
        $domainId = $pitInfo[TblMineralField::DOMAINID];
        if(!empty(self::$grabbedGoldPit)
                && self::$grabbedGoldPit[0] == $domainId 
                && self::$grabbedGoldPit[1] == $pitId)
        {
            $minCaptureTime = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_ROB_GOLDPIT_MINCAPTURE];
            if($captureTime < $minCaptureTime)
            {
                Logger::info('capturetime %d mincapturetime %d',
                        $captureTime,$minCaptureTime);
                $captureTime = $minCaptureTime;
            }
        }
        $delayTimes = $pitInfo[TblMineralField::DELAYTIMES];
        $delayTime = 0;
        for($i=0;$i<$delayTimes;$i++)
        {
            $delayTime += self::getDelayTimeOnce($i);
        }
        $maxCaptureTime = self::getPitHarvestTime($domainId, $pitId, $uid) + $delayTime;
        if($captureTime > $maxCaptureTime)
        {
            $captureTime = $maxCaptureTime;
            if($captureTime > $maxCaptureTime + 60 )//TODO
            {
                Logger::warning('capturetime %d is max than maxcapturetime.delaytime %d',$captureTime,$delayTimes);
            }
        }
        $oneHelpArmyEnhance = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_ONEHELPARMY_ENHANCE];    //单个协助军收入增益
        $resPlayerLv = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_RESPLAYER_LV];  //玩家等级修正
        $level = EnUser::getUserObj($uid)->getLevel();
        $level = max(array(30, $level)) + $resPlayerLv;
        $silverBase =  self::getSilverBaseOfPit($domainId,$pitId);  //资源矿游戏币基础值
        $totalGuardTime = $pitInfo[TblMineralField::TOTALGUARDSTIME];  //协助军协助时间总和
        foreach($arrGuardInfo as $guardInfo)
        {
            $guardTime = Util::getTime()-$guardInfo[TblMineralGuards::GUARDTIME];
            if($guardTime > self::getPitGuardTime())
            {
                $guardTime = self::getPitGuardTime();
            }
            $totalGuardTime += $guardTime;
        }
        if( $totalGuardTime > SECONDS_OF_DAY * 4 )
        {
        	Logger::fatal('invalid totalGuardTime:%d, uid:%d, domainId:%d, pitId:%d', 
        				$totalGuardTime, $uid, $domainId, $pitId);
        	$totalGuardTime = 0;
        }
        $silver = ($captureTime + $totalGuardTime * $oneHelpArmyEnhance / 100) *
                MineralDef::MINERAL_OUTPUT_RATIO * $level * $silverBase;
        $silver = ceil($silver);
        $cityAddition = EnCityWar::getCityEffect($uid, CityWarDef::MINERAL)/UNIT_BASE;
        Logger::info('EnCityWar::getCityEffect for mineral capture is %d.uid %d',$cityAddition*UNIT_BASE,$uid);
        $silverGot = intval($silver * (1 + $cityAddition));
        $silverAddition = MineralLogic::getSilverAddition();
        Logger::info('getSilverAddition for mineral capture %d,uid %d',$silverAddition*UNIT_BASE,$uid);
        $silverGot = intval($silverGot * $silverAddition);
        //聚义厅加成
        $unionAddition = EnUnion::getAddFuncByUnion($uid, UnionDef::TYPE_MINERAL_PIT_ADDRATE)/UNIT_BASE;
        Logger::info('EnUnion::getAddFuncByUnion getSilverAddition for mineral capture is %d uid %d', $unionAddition*UNIT_BASE, $uid);
        $silverGot = intval($silverGot * (1 + $unionAddition));
        if($silverGot < MineralDef::MINERAL_ACQUIRE_MIN_SILVER)
        {
            $silverGot = MineralDef::MINERAL_ACQUIRE_MIN_SILVER;
        }
        //军团加成
        //普通、高级、金币矿区前二十页才有
        $allguildSilver=0;
        $guildId=$pitInfo[TblMineralField::GUILDID];
        if(MineralDef::IF_GUILD_ADD_OPEN==1&&$guildId!=0
        		&&$domainId%10000<=20)
        {
        	//找同一页的所有矿坑信息
        	$arrPitId=self::getArrPitIdByDomainId($domainId);
        	$pitManager=PitManager::getInstance();
        	$arrPitObj=$pitManager->getArrPitByPitId($domainId);
        	$selfPitObj=$pitManager->getPitObj($domainId, $pitId);
        	$occupyTime=$pitInfo[TblMineralField::OCCUPYTIME];
        	
        	 /**
             * 自己的矿与其他矿的时间交集的时间节点
             */
            $arrTimeNodeOfMine = array();
        	foreach ($arrPitId as $id)
        	{
        		$eachPitObj=$pitManager->getPitObj($domainId, $id);
        		$eachGuildInfo=$eachPitObj->getGuildInfo($guildId,$pitId);//加了个pitId，为了刷新va的时候只刷新自己的矿
        		if (empty($eachGuildInfo))
        		{
        			continue;
        		}
        		foreach ($eachGuildInfo as $eachTimePeriod)
        		{
        			foreach ($eachTimePeriod as $timeNode)
        			{
        				if ($timeNode>=$occupyTime&&$timeNode<Util::getTime())
        				{
        					$arrTimeNodeOfMine[]=$timeNode;
        				}
        			}
        		}
        	}
        	//再加个结束时间？
        	$arrTimeNodeOfMine[]=Util::getTime();
        	sort($arrTimeNodeOfMine);
        	$totalNum = 0;
        	for($i=0;$i<count($arrTimeNodeOfMine)-1;++$i)
        	{
        		if ($arrTimeNodeOfMine[$i+1]-$arrTimeNodeOfMine[$i]>SECONDS_OF_DAY*1)
        		{
        			continue;
        		}
        		$totalNum = 0;
        		foreach ($arrPitId as $id)
        		{
        			$eachPitObj=$pitManager->getPitObj($domainId, $id);
        			$eachGuildInfo=$eachPitObj->getGuildInfo($guildId);
        			if (empty($eachGuildInfo))
        			{
        				continue;
        			}
        			foreach ($eachGuildInfo as $eachTimePeriod)
        			{
        				if (isset($eachTimePeriod[MineralDef::GUILD_ENDTIME]))
        				{
        					$oneGuildEndTime=$eachTimePeriod[MineralDef::GUILD_ENDTIME];
        				}else {
        					$oneGuildEndTime=Util::getTime();
        				}
        				if ($arrTimeNodeOfMine[$i]>=$eachTimePeriod[MineralDef::GUILD_STARTTIME]
        						&&$arrTimeNodeOfMine[$i+1]<=$oneGuildEndTime)
        				{
        					$totalNum++;
        				}
        			}
        		}
        		$norConf = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_MINERAL_GUILD_EXTRA_RES];
        		if (isset($norConf[$totalNum]))
        		{
        			$guildSilverBase=$norConf[$totalNum];
        			/**
        			 * 计算军团加成
        			 * (n人时间*n人系数)*资源矿系数*(玩家等级+资源矿玩家等级修正)*(1+城池资源矿增益/10000)
        			 */
        			$guildIdCapturePeriodTime=$arrTimeNodeOfMine[$i+1]-$arrTimeNodeOfMine[$i];
        			//$silverBase $level  MineralDef::MINERAL_OUTPUT_RATIO * $level * $silverBase;
        			$guildSilver=(($guildIdCapturePeriodTime*$guildSilverBase))*MineralDef::MINERAL_OUTPUT_RATIO * $level;
        			$guildSilver=ceil($guildSilver);
        			
        			$guildSilverGot = intval($guildSilver * (1 + $cityAddition));
        			$guildSilverGot = intval($guildSilverGot * $silverAddition);
        			//聚义厅加成
        			$guildSilverGot = intval($guildSilverGot * (1 + $unionAddition));
        			
        			Logger::info("getCaptureGuildAcquire:%d periodstart:%d,periodend:%d guildsilverbase:%d level:%d "
        					,$guildSilverGot,$arrTimeNodeOfMine[$i],$arrTimeNodeOfMine[$i+1],$guildSilverBase,$level);
        			
        			$allguildSilver+=$guildSilverGot;
        		}
        	}
        }
        if ($allguildSilver>MineralDef::MAX_GUILD_ADD_SILVER)
        {
        	$allguildSilver=MineralDef::MAX_GUILD_ADD_SILVER;
        }
        $silverGot+=$allguildSilver;
        //新加一个资源矿获得物品2016年7月6日16:57:04战车开启才有这个加成
        $ironGot=0;
        if (ChariotUtil::isChariotOpen($uid))
        {
        	//获得的精铁=精铁基础值（读表）*占领时间*资源矿系数（写死）
        	$baseIron=self::getIronBaseOfPit($domainId, $pitId);
        	$ironTime=$actualCaptureTime<$captureTime?$actualCaptureTime:$captureTime;//精铁时间没有保底
        	$ironGot=$baseIron*$ironTime*MineralDef::MINERAL_OUTPUT_IRON_RATIO;
        }
        $ironGot=floor($ironGot);
        if ($ironGot >MineralDef::MAX_IRON_GOT)
        {
        	$ironGot=MineralDef::MAX_IRON_GOT;
        }
        Logger::info('getCaptureAcquire uid %d pit %d domain %d capturetime %d totalguardtime %d silver %d guildAddSilver:%d iron:%d',
                $uid, $pitId,$domainId,$actualCaptureTime,$totalGuardTime,$silverGot,$allguildSilver,$ironGot);
        return array(
                'time'=>$actualCaptureTime,
                'silver'=>$silverGot,
        		'guildSilver'=>$allguildSilver,
        		'iron'=>$ironGot,
        );
    }
    
    public static function getSilverBaseOfPit($domainId,$pitId)
    {
        return btstore_get()->MINERAL[$domainId]['pits'][$pitId][PitArr::OUTPUT];
    }
    
    public static function getIronBaseOfPit($domainId,$pitId)
    {
    	return btstore_get()->MINERAL[$domainId]['iron_num'][$pitId-1];
    }
    
    public static function getWealSilverAddition()
    {
        $addition = EnWeal::getWeal(WealDef::MINERAL_PRODUCE);
        if(empty($addition))
        {
            return 1;
        }
        return $addition/UNIT_BASE;
    }
    
    public static function getSilverAddition()
    {
    	$wealAddition = MineralLogic::getWealSilverAddition();
    	$mergeAddition = EnMergeServer::getMineralRate();
    	return max(array($wealAddition, $mergeAddition, 1.0));
    }
    
    public static function getDelayTimeOnce($i)
    {
        return btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_RES_DELAY_DATA][$i][0];
    }
    
    
    /**
     * 协助军收入=int【 (资源矿游戏币基础值*协助时间*资源矿系数*(玩家等级+资源矿玩家等级修正)*
     *         协助军收入系数/100)】
                             协助军收入系数，单个协助军收入增益，抢夺协助军消耗体力，资源矿玩家等级修正这几个参数配置到normal_config（通用配置）中
     * @param unknown_type $guardInfo
     * @param unknown_type $uid
     * @throws FakeException
     * @return multitype:number
     */
    public static function getGuardAcquire($guardInfo)
    {
        $uid = $guardInfo[TblMineralGuards::UID];
        if(empty($uid))
        {
            throw new FakeException('user %d is not guard of any pit',$uid);
        }
        $domainId = $guardInfo[TblMineralGuards::DOMAINID];
        $pitId = $guardInfo[TblMineralGuards::PITID];
        $silverBase =  self::getSilverBaseOfPit($domainId, $pitId);
        $guardTime = Util::getTime() - $guardInfo['guard_time'];    //担任协助军的时间长度
        $maxGuardTime = self::getPitGuardTime();
        if($guardTime > $maxGuardTime)
        {
            //放宽一下限制   lcserver执行timer可能有延迟   资源矿加锁也可能造成延迟
            if($guardTime - 60 > $maxGuardTime)//TODO
            {
                Logger::warning('guardtime max than maxguardtime.guardinfo is %s',$guardInfo);
            }
            $guardTime = $maxGuardTime;
        }
        $resPlayerLv = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_RESPLAYER_LV];  //玩家等级修正
        $oneHelpArmyEnhance = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_HELPARMYINCOME_RATIO];    //单个协助军收入增益
        $level = EnUser::getUserObj($uid)->getLevel();
        $level = max(array(30, $level)) + $resPlayerLv;
        $silver = $silverBase * $guardTime * MineralDef::MINERAL_OUTPUT_RATIO *
                $level * $oneHelpArmyEnhance / 100;
        $silver = ceil($silver);
        $addition = EnCityWar::getCityEffect($uid, CityWarDef::MINERAL)/UNIT_BASE;
        Logger::info('EnCityWar::getCityEffect for mineral guard is %d uid %d',$addition*UNIT_BASE,$uid);
        $silverGot = intval($silver * (1 + $addition));
        $addition = MineralLogic::getSilverAddition();
        Logger::info('getSilverAddition for mineral guard %d,uid %d',$addition*UNIT_BASE,$uid);
        $silverGot = intval($silverGot * $addition);
        //聚义厅加成
        $addition = EnUnion::getAddFuncByUnion($uid, UnionDef::TYPE_MINERAL_PIT_ADDRATE)/UNIT_BASE;
        Logger::info('EnUnion::getAddFuncByUnion getSilverAddition for mineral guard is %d uid %d', $addition*UNIT_BASE, $uid);
        $silverGot = intval($silverGot * (1+ $addition));
        if($silverGot < MineralDef::MINERAL_ACQUIRE_MIN_SILVER)
        {
            $silverGot = MineralDef::MINERAL_ACQUIRE_MIN_SILVER;
        }
        Logger::info('getGuardAcquire uid %d pit %d domainid %d guradtime %d siver %d',
                $uid, $pitId,$domainId,$guardTime,$silverGot);
        return array(
                'time'=>$guardTime,
                'silver'=>$silverGot
        );
    }
    
    public static function sendCaptureRewardToCenter($uid,$silver,$iron)
    {
        $reward[RewardType::SILVER] = $silver;
        if(!empty($iron))
        {
        	$reward[RewardType::ARR_ITEM_TPL]=array(MineralDef::MINERAL_IRON_TPL_ID=>$iron);//物品ID写死
        }
        EnReward::sendReward($uid, RewardSource::MINERAL, $reward);
    }
    
    public static function sendGuardRewardToCenter($uid,$silver)
    {
        $reward[RewardType::SILVER] = $silver;
        EnReward::sendReward($uid, RewardSource::MINERAL, $reward);
    }
    
    public static function getCaptureDueTime($pitInfo)
    {
        $dueTime = 0;
        if(empty($pitInfo[TblMineralField::UID]))
        {
            return $dueTime;
        }
        $domainId = $pitInfo[TblMineralField::DOMAINID];
        $pitId = $pitInfo[TblMineralField::PITID];
        $harvestTime = self::getPitHarvestTime($domainId, $pitId, $pitInfo[TblMineralField::UID]);
        $dueTime = $pitInfo[TblMineralField::OCCUPYTIME] + $harvestTime-Util::getTime();
        $delayTimes = $pitInfo[TblMineralField::DELAYTIMES];
        $delayTimeTmp = 0;
        while($delayTimeTmp < $delayTimes)
        {
            $dueTime += self::getDelayTimeOnce($delayTimeTmp);
            $delayTimeTmp++;
        }
        return $dueTime;
    }
    
    private static function getProtectDueTime($pitInfo)
    {
        $domainId = $pitInfo[TblMineralField::DOMAINID];
        $pitId = $pitInfo[TblMineralField::PITID];
        $protectTime = btstore_get()->MINERAL[$domainId]['pits'][$pitId][PitArr::PROTECTTIME];
        if(Util::getTime() >= ($pitInfo[TblMineralField::OCCUPYTIME] + $protectTime))
        {
            return 0;
        }
        $due = $pitInfo[TblMineralField::OCCUPYTIME] + $protectTime - Util::getTime();
        return $due;
    }
    
    /**
     * 
     * @param array $arrPitInfo
     * [
     *     domain_id=>array
     *     [
     *         pit_id=>array
     *         [
     *             domain_id:int
     *             pit_id:int
     *             uid:int
     *             capture_time:int
     *             total_guard_time:int
     *             delay_times:int
     *             union_addition:int
     *             guards:array
     *             [
     *                 uid=>array 守卫信息
     *                 [
     *                     union_addition:int
     *                 ]
     *             ]
     *         ]
     *     ]
     * ]
     */
    public static function resetArrPitInfo($arrPitInfo)
    {
        Logger::trace('resetArrPitInfo %s',$arrPitInfo);
        $arrTmpPit = array();
        $arrCaptureUid = array();
        $arrGuardUid = array();
        foreach($arrPitInfo as $domainId => $arrPit)
        {
            foreach($arrPit as $pitId => $pitInfo)
            {
                $arrGuardInfo = $pitInfo['guards'];
                $newPitInfo = array();
                //处理矿的基本信息
                $domainId = $pitInfo[TblMineralField::DOMAINID];
                $pitId = $pitInfo[TblMineralField::PITID];
                $uid = $pitInfo[TblMineralField::UID];
                $protect_time = 0;
                $dueTime = 0;
                $union_addition = 0;
                if(!empty($uid))
                {
                    $arrCaptureUid[] = $uid;
                    $dueTime = self::getCaptureDueTime($pitInfo);
                    if($dueTime <= 0)
                    {
                        $dueTime = 0;
                    }
                    else
                    {
                        $protect_time = self::getProtectDueTime($pitInfo);
                    }
                    $union_addition = EnUnion::getAddFuncByUnion($uid, UnionDef::TYPE_MINERAL_PIT_ADDRATE);
                }
                $newPitInfo = $pitInfo;
                unset($newPitInfo[TblMineralField::DUETIMER]);
                $newPitInfo['due_time'] = $dueTime;
                $newPitInfo['protect_time'] = $protect_time;
                //聚义厅加成
                $newPitInfo['union_addition'] = $union_addition;
                //处理守卫信息
                $newPitInfo['guards'] = array();
                foreach($arrGuardInfo as $uid => $guardInfo)
                {
                    if($guardInfo[TblMineralGuards::GUARDTIME] == 0)
                    {
                        continue;
                    }
                    $newPitInfo['guards'][$uid] = array(
                            TblMineralGuards::UID => $uid,
                            TblMineralGuards::GUARDTIME => $guardInfo[TblMineralGuards::GUARDTIME],
                            );
                    $arrGuardUid[] = $uid;
                }
                $arrTmpPit[$domainId][$pitId] = $newPitInfo;
            }
        }
        $arrUserInfo = EnUser::getArrUserBasicInfo(array_unique(array_merge($arrCaptureUid,$arrGuardUid)), 
                array('uid','uname','level','dress','htid','guild_id'));
        $arrGuildId = array();
        foreach($arrCaptureUid as $uid)
        {
            $arrGuildId[] = $arrUserInfo[$uid]['guild_id'];
        }
        $dbRet = GuildDao::getArrGuild($arrGuildId, array(GuildDef::GUILD_NAME,GuildDef::GUILD_ID));
        $arrGuildInfo = Util::arrayIndex($dbRet, GuildDef::GUILD_ID);
        $ret = array();
        foreach($arrTmpPit as $domainId => $arrPit)
        {
            foreach($arrPit as $pitId => $pitInfo)
            {
                $capture = $pitInfo[TblMineralField::UID];
                $ret[$domainId][$pitId] = $pitInfo;
                if(empty($capture))
                {
                    continue;
                }
                $pitInfo['uname'] = $arrUserInfo[$capture]['uname'];
                $pitInfo['level'] = $arrUserInfo[$capture]['level'];
                $guildId = $arrUserInfo[$capture]['guild_id'];
                if(!empty($guildId) && (isset($arrGuildInfo[$guildId])))
                {
                    $pitInfo['guild_name'] = $arrGuildInfo[$guildId][GuildDef::GUILD_NAME];
                }
                foreach($pitInfo['guards'] as $guard => $guardInfo)
                {
                    $pitInfo['guards'][$guard]['uname'] = $arrUserInfo[$guard]['uname'];
                    $pitInfo['guards'][$guard]['level'] = $arrUserInfo[$guard]['level'];
                    $pitInfo['guards'][$guard]['htid'] = $arrUserInfo[$guard]['htid'];
                    $pitInfo['guards'][$guard]['dress'] = $arrUserInfo[$guard]['dress'];
                    //聚义厅加成-守卫
                    $pitInfo['guards'][$guard]['union_addition'] = EnUnion::getAddFuncByUnion($guard, UnionDef::TYPE_MINERAL_PIT_ADDRATE);
                }
                $pitInfo['guards'] = array_merge(array(),$pitInfo['guards']);
                $ret[$domainId][$pitId] = $pitInfo;
            }
        }
        Logger::trace('resetArrPitInfo %s',$arrPitInfo);
        return $ret;
    }
    
    public static function isGoldDomain($domainId)
    {
        $domainType = btstore_get()->MINERAL[$domainId]['domain_type'];
        if($domainType == MineralType::GOLD)
        {
            return TRUE;
        }
        return FALSE;
    }
    
    public static function getRobLog()
    {
        $arrRobLog = MineralDAO::getRobLog();
        $arrUid = array();
        foreach($arrRobLog as $robInfo)
        {
            $arrUid[] = $robInfo['pre_capture'];
            $arrUid[] = $robInfo['now_capture'];
        }
        $arrUserInfo = EnUser::getArrUserBasicInfo($arrUid, array('uid','uname'));
        $ret = array();
        foreach($arrRobLog as $robInfo)
        {
            $capture = $robInfo['pre_capture'];
            $robber = $robInfo['now_capture'];
            $robInfo['pre_capture'] = $arrUserInfo[$capture]['uname'];
            $robInfo['now_capture'] = $arrUserInfo[$robber]['uname'];
            $ret[] = $robInfo;
        }
        return $ret;
    }
    
   
    public static function doChangeGuild($uid,$newGuildId)
    {
    	$arrDbPit = MineralDAO::getArrPitByUid($uid);
    	$pitManager=PitManager::getInstance();
    	foreach($arrDbPit as $pitInfo)
    	{
    		$pitId = $pitInfo[TblMineralField::PITID];
    		$domainId = $pitInfo[TblMineralField::DOMAINID];
    		$eachPitObj=$pitManager->getPitObj($domainId, $pitId);
    		$eachPitObj->doWhenChangeGuild($newGuildId);
    		Logger::info("user change guild and mineral domainid:%d,pitid:%d info change:%s",$domainId,$pitId,$eachPitObj->getGuildInfo($newGuildId));
    	}
    	PitManager::getInstance()->save();
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */