<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CopyTeamLogic.class.php 169872 2015-04-28 03:09:25Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/copyteam/CopyTeamLogic.class.php $
 * @author $Author: MingTian $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-04-28 03:09:25 +0000 (Tue, 28 Apr 2015) $
 * @version $Revision: 169872 $
 * @brief 
 *  
 **/
class CopyTeamLogic
{
    public static function createTeam($copyId,$joinLimit,$isAutoStart,$uid)
    {
        self::canUserAtkTeamCopy($copyId, $uid);
        $userObj = EnUser::getUserObj($uid);
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_FIGHTFORCE, $userObj->getFightForce());
        RPCContext::getInstance()->createTeam($isAutoStart, $joinLimit, 'copyteam.startTeamAtk');
        RPCContext::getInstance()->getFramework()->resetCallback();
    }
    
    public static function joinTeam($teamId,$copyId,$uid)
    {
        self::canUserAtkTeamCopy($copyId, $uid);
        $userObj = EnUser::getUserObj($uid);
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_FIGHTFORCE, $userObj->getFightForce());
        RPCContext::getInstance()->joinTeam($teamId);
        RPCContext::getInstance()->getFramework()->resetCallback();
        return 'ok';
    }
    
    
    private static function canUserAtkTeamCopy($copyId,$uid)
    {
        $teamType = self::getTeamCopyType($copyId);
        $userTeamObj = self::getUserTeamObjByTeamType($teamType, $uid);
        if(empty($userTeamObj))
        {
            throw new FakeException('empty UserTeamObj.');
        }
        $canAtk = $userTeamObj->canAtk($copyId);
        if($canAtk != 'ok')
        {
            throw new FakeException('can not create team.params is copyid %d,teamtype %d,reason is %s.',$copyId,$teamType,$canAtk);
        }
        $needLevel = self::getTeamCopyNeedUserLevel($copyId);
        $userObj = EnUser::getUserObj($uid);
        if($userObj->getLevel() < $needLevel)
        {
            throw new FakeException('atk copy %d need user level %d.but user %d level is %d.',
                    $copyId,$needLevel,$uid,$userObj->getLevel());
        }
        $needExecution = btstore_get()->COPYTEAM[$copyId]['need_execution'];
        if($userObj->getCurExecution() < $needExecution)
        {
            throw new FakeException('atk copy %d need execution %d.but user %d has execution %d.',
                    $copyId,$needExecution,$uid,$userObj->getCurExecution());
        }
        if(CopyUtil::checkFightCdTime() == FALSE)
        {
            throw new FakeException('not cool down yet,can not enter team battle.');
        }
        if(BagManager::getInstance()->getBag($uid)->isFull())
        {
            throw new FakeException('bag is full');
        }
    }
    
    public static function startTeamAtk($copyId,$teamList)
    {
        $minNum = btstore_get()->COPYTEAM[$copyId]['min_member_num'];
        $maxNum = btstore_get()->COPYTEAM[$copyId]['max_member_num'];
        if(count($teamList) < $minNum || (count($teamList) > $maxNum))
        {
            throw new FakeException('copy %d min_member_num %d max_member_num %d.teamlist is %s.',
                    $copyId,$minNum,$maxNum,$teamList);
        }
        $baseId = btstore_get()->COPYTEAM[$copyId]['base_id'];
        $maxWin = btstore_get()->COPYTEAM[$copyId]['max_win_num'];
        $armyList = CopyUtil::getArmyInBase($baseId);
        //准备上场成员
        $userFmt = array();
        $enemyFmt = array();
        foreach($teamList as $uid)
        {
            $userFmt['members'][] = EnUser::getUserObj($uid)->getBattleFormation();
        }
        foreach($armyList as $index => $armyId)
        {
            $enemyFmt['members'][$index] = EnFormation::getMonsterBattleFormation($armyId,BaseLevel::NORMAL);
            $enemyFmt['members'][$index]['uid'] = $index+1;
        }
        $teamLeader = EnUser::getUserObj($teamList[0]);
        $userFmt['name'] = $teamLeader->getUname();
        $userFmt['level'] = $teamLeader->getLevel();
        $enemyFmt['name'] = $copyId;
        $enemyFmt['level'] = btstore_get()->COPYTEAM[$copyId]['level'];
        Logger::trace('before doMultiHero.userfmt %s.enemyfmt %s,maxwin %d',$teamList,$armyList,$maxWin);
        $arrExtra = array(
        					'arrNeedResult'=> array(
        					        'simpleRecord' => self::getRoundNumPerHit(),
        					        'saveSimpleRecord' => 0),
        					'mainType' => BattleType::COPY_TEAM,
                        );
        $atkRet = EnBattle::doMultiHero($userFmt, $enemyFmt,BattleConf::MAX_ARENA_COUNT, $maxWin,$arrExtra);
        $arrUidNotAlone = self::isArrUserInSameGuild($teamList);
        $atkRet['uids_same_guild'] = $arrUidNotAlone;
        self::calculateTeamAtkRet($copyId, $teamList, $atkRet);
    }
    
    private static function isArrUserInSameGuild($arrUser)
    {
        $arrUserGuild = GuildDao::getArrMember($arrUser, array(GuildDef::USER_ID,GuildDef::GUILD_ID), 0, count($arrUser));
        $arrUserGuild = Util::arrayIndex($arrUserGuild, GuildDef::USER_ID);
        $arrGuild = array();
        foreach($arrUserGuild as $uid => $userInfo)
        {
            $guild = $userInfo[GuildDef::GUILD_ID];
            $arrGuild[$guild][] = $uid;
        }
        $arrUidNotAlone = array();
        foreach($arrGuild as $guildId => $arrUid)
        {
            if(count($arrUid) < 2)
            {
                continue;
            }
            foreach($arrUid as $uid)
            {
                $arrUidNotAlone[] = $uid;
            }
        }
        return $arrUidNotAlone;
    }
    
    private static function calculateTeamAtkRet($copyId,$teamList,$atkRet)
    {
        $arrUidNotAlone = $atkRet['uids_same_guild'];
        unset($atkRet['uids_same_guild']);
        foreach($teamList as $index =>  $teamMember)
        {
            $atkRet['same_guild'] = FALSE;
            if(in_array($teamMember, $arrUidNotAlone))
            {
                $atkRet['same_guild'] = TRUE;
            }
            $isLeader = $index == 0 ? true : false;
            RPCContext::getInstance()->executeTask($teamMember,
                        'copyteam.doneTeamBattle',
                        array($teamMember, $copyId, $atkRet, $isLeader));
        }
    }
    
    
    public static function getSameGuildSilverAddition()
    {
        return btstore_get()->GUILD_COPY[GuildDef::GUILD_SILVER_ADDTION];
    }
    
    public static function getCityWarSilverAddition($uid)
    {
        $addition = EnCityWar::getCityEffect($uid, CityWarDef::COPYTEAM);
        Logger::info('EnCityWar::getCityEffect act. addition is %d',$addition);
        return $addition;
    }
    
    public static function doneTeamBattle($uid, $copyId, $atkRet, $isLeader)
    {
        $sessionUid = RPCContext::getInstance()->getSession('global.uid');
        if (empty($sessionUid))
        {
            RPCContext::getInstance()->setSession('global.uid', $uid);
        }
        $isWin = $atkRet['server']['result'];
        $guildReward = $atkRet['same_guild'];
        unset($atkRet['same_guild']);
        $reward = array();
        $userTeamInfo = array();
        $userObj = EnUser::getUserObj($uid);
        Logger::trace('sameguild is %d',$guildReward);
        if($isWin)
        {
            $teamType = self::getTeamCopyType($copyId);
            $userTeamObj = self::getUserTeamObjByTeamType($teamType, $uid);
            if(empty($userTeamObj))
            {
                throw new FakeException('empty UserTeamObj.');
            }
            $silverAddtion = 0;
            if($guildReward)
            {
                $silverAddtion += self::getSameGuildSilverAddition();
                Logger::trace('sameguild copyteam silver addition is %d',$silverAddtion);
            }
            $silverAddtion += self::getCityWarSilverAddition($uid);
            $reward = btstore_get()->COPYTEAM[$copyId]['reward']->toArray();
            if(isset($reward['silver']))
            {
                Logger::trace('reward silver %d addition is %d',$reward['silver'],$silverAddtion);
                $reward['silver'] = intval($reward['silver'] * (1+$silverAddtion/UNIT_BASE));
            }
            if($userTeamObj->getGuildAtkNum() > 0)
            {
                $userObj->addFightCd(CopyConf::$FIGHT_CD_TIME);
                $needExecution = btstore_get()->COPYTEAM[$copyId]['need_execution'];
                if($userObj->subExecution($needExecution) == FALSE)
                {
                    throw new FakeException('user sub execution failed.');
                }
                foreach($reward as $key => $value)
                {
                    switch($key)
                    {
                        case 'exp':
                            $userObj->addExp($value * $userObj->getLevel());
                            break;
                        case 'silver':
                            $userObj->addSilver($value);
                            break;
                        case 'soul':
                            $userObj->addSoul($value);
                            break;
                    }
                }
                $reward['item'] = self::getTeamCopyReward($copyId, $isWin, $uid);
                BagManager::getInstance()->getBag($uid)->update();
            }
            else
            {
                $userObj->addSilver($reward['silver']);
                $reward = array(
                        'silver'=>$reward['silver']
                        );
            }
            $userTeamInfo = $userTeamObj->doneTeamBattle($copyId, $atkRet, $isLeader, $isWin);
            $userObj->update();
            $userTeamObj->saveUserTeamInfo();
            EnWeal::addKaPoints(KaDef::GCOPY);
            EnAchieve::updateGuildCopy($uid, $copyId);
            EnActive::addTask(ActiveDef::GUILDCOPYTEAM);
        }
        $atkRet['reward'] = $reward;
        $atkRet['copyTeamInfo'] = $userTeamInfo;
        unset($atkRet['client']);
        RPCContext::getInstance()->sendMsg(array($uid), 
                PushInterfaceDef::COPY_TEAM_ATK_RESULT, $atkRet);
    }
    
    public static function getTeamCopyReward($copyId,$isWin,$uid)
    {
        $baseId = btstore_get()->COPYTEAM[$copyId]['base_id'];
        $lvName = CopyConf::$BASE_LEVEL_INDEX[BaseLevel::SIMPLE];
        $arrDropIds = btstore_get()->BASE[$baseId][$lvName][$lvName.'_droptbl_ids']->toArray();
        $itemReward = array();
        foreach($arrDropIds as $dropId)
        {
            $arrItemTmplId = Drop::dropItem($dropId);
            foreach ($arrItemTmplId as $itemTplId => $itemNum)
            {
                if(!isset($itemReward[$itemTplId]))
                {
                    $itemReward[$itemTplId] = 0;
                }
                $itemReward[$itemTplId] += $itemNum;
            }
        }
        if(BagManager::getInstance()->getBag($uid)->addItemsByTemplateID($itemReward,TRUE) == FALSE)
        {
            throw new FakeException('add item failed %s.',$itemReward);
        }
        return $itemReward;
    }
    
    public static function getCopyTeamInfo($teamType,$uid)
    {
        $userTeamObj = self::getUserTeamObjByTeamType($teamType, $uid);
        if(empty($userTeamObj))
        {
            throw new FakeException('empty UserTeamObj.');
        }
        $userTeamInfo = $userTeamObj->getUserTeamInfo();
        $userTeamObj->saveUserTeamInfo();
        return $userTeamInfo;
    }
    
    /**
     * 邀请全服的人参加工会组队战  拉取邀请列表
     * 1.首先拉取公会的成员
     * 2.拉取公会之外的成员（按等级拉取）
     * @param int $inviteNum    邀请的人数
     * @param int $arrUidInTeam    当前队伍已经有的人数
     */
    public static function getAllInviteInfo($uid,$teamCopyId,$teamList,$num)
    {
        if($num > 50)
        {
            throw new FakeException('max invite user num is 50.request num is %d',$num);
        }
        $guildId = GuildLogic::getGuildId($uid);
        $memberList = GuildDao::getMemberList($guildId, array(GuildDef::USER_ID), 0, CData::MAX_FETCH_SIZE);
        $memberList = Util::arrayIndex($memberList, GuildDef::USER_ID);
        //公会成员
        $arrUidGuild = array_diff(array_keys($memberList), $teamList);
        $needLevel = self::getTeamCopyNeedUserLevel($teamCopyId);
        $arrUserNoGuild = UserDao::getArrUserByWhereOrder(0, $num, array('uid'),
                array(array('status','=',UserDef::STATUS_ONLINE),array('level','>=',$needLevel),array('guild_id','>',0),array('uid',"NOT IN",$teamList)),
                array('level'=>FALSE,'uid'=>TRUE));
        $arrUserNoGuild = util::arrayIndex($arrUserNoGuild, 'uid');
        $arrUidNoGuild = array_keys($arrUserNoGuild);
        $arrAllUid = array_merge($arrUidGuild,$arrUidNoGuild);
        $arrUserInfo = self::filterInviteUser($arrAllUid, $teamCopyId, $guildId);
        if(count($arrUserInfo) < $num)
        {
            $arrUserNoGuild = UserDao::getArrUserByWhereOrder($num, $num*2, array('uid'),
                    array(array('status','=',UserDef::STATUS_ONLINE),array('level','>=',$needLevel),array('guild_id','>',0),array('uid',"NOT IN",$teamList)),
                    array('level'=>FALSE,'uid'=>TRUE));
            $arrUserNoGuild = util::arrayIndex($arrUserNoGuild, 'uid');
            $arrUidNoGuild = array_keys($arrUserNoGuild);
            $arrPartUserInfo = self::filterInviteUser($arrAllUid, $teamCopyId, $guildId);
            $arrUserInfo = $arrUserInfo + $arrPartUserInfo;
        }
        unset($arrUserInfo[$uid]);
        $arrUserInfo = array_merge($arrUserInfo);
        if(count($arrUserInfo) > $num)
        {
            $arrUserInfo = array_slice($arrUserInfo, 0, $num);
        }
        return $arrUserInfo;
    }
    
    private static function filterInviteUser($arrUid,$teamCopyId,$inviterGuildId)
    {
        $arrUser = Enuser::getArrUserBasicInfo($arrUid, array('uid','dress','fight_force','level','uname','status','htid', 'guild_id','vip'));
        //根据建筑等级判断
        $arrGuildId = array_keys(Util::arrayIndex($arrUser, 'guild_id'));
        $arrGuildInfo = EnGuild::getArrGuildInfo($arrGuildId);
        $arrGuildLevel = array();
        foreach($arrGuildInfo as $guildId => $guildInfo)
        {
            $type = GuildDef::COPY;
            $level = GuildConf::$GUILD_BUILD_DEFAULT[$type][GuildDef::LEVEL];
            if (isset($guildInfo[GuildDef::VA_INFO][$type]))
            {
                $level = $guildInfo[GuildDef::VA_INFO][$type][GuildDef::LEVEL];
            }
            $arrGuildLevel[$guildId] = $level;
        }
        //根据玩家等级  玩家的登录状态 公会id 第二次过滤（其实本次过滤没意义，因为拉取数据的时候已经过滤了）
        $needLevel = self::getTeamCopyNeedUserLevel($teamCopyId);
        $arrFilteredUser = array();
        foreach($arrUser as $uid => $userInfo)
        {
            //过滤不在线的   等级不够不够打副本的
            if($userInfo['level'] < $needLevel)
            {
                continue;
            }
            if($userInfo['status'] != UserDef::STATUS_ONLINE)
            {
                continue;
            }
            //没有工会的人需要被过滤掉
            if(empty($userInfo['guild_id']))
            {
            	continue;
            }
            $guildId = $userInfo['guild_id'];
            $userInfo[GuildDef::GUILD_NAME] = $arrGuildInfo[$guildId][GuildDef::GUILD_NAME];
            $needGuildLv = CopyTeamLogic::getGuildCopyNeedBuildLevel($teamCopyId);
            if(!isset($arrGuildLevel[$guildId]) || 
                    $arrGuildLevel[$guildId] < $needGuildLv)
            {
                continue;
            }
            unset($userInfo['status']);
            $arrFilteredUser[$uid] = $userInfo;
        }
        $arrUser = $arrFilteredUser;
        $arrFilteredUser = array();
        //根据玩家的公会组队副本进行第三次过滤  1.当前副本是否大于要攻击副本（当前副本等于要攻击的副本特殊判断）2.是否有攻击次数
        $arrCopyTeamInfo = CopyTeamDao::getArrUserCopyTeamInfo($arrUid,
                CopyTeamDef::$ALL_GUILD_TEAM_SQLFIELD);
        $arrCopyTeamInfo = Util::arrayIndex($arrCopyTeamInfo, CopyTeamDef::COPYTEAM_SQLFIELD_UID);
        $arrUidInDoubt = array();
        foreach($arrUser as $uid => $userInfo)
        {
            if(isset($arrCopyTeamInfo[$uid]) == FALSE)
            {
                continue;
            }
            $copyTeamInfo = $arrCopyTeamInfo[$uid];
            if($copyTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDATKNUM] < 1 &&
                    ($copyTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDHELPNUM] >= CopyTeamLogic::getGuildDailyHelpNum()))
            {
                continue;
            }
            $curCopyId = $copyTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_CURGUILDCOPY];
            if($curCopyId < $teamCopyId)//建筑等级不够
            {
                continue;
            }
            if(empty($userInfo['guild_id']))
            {
                continue;
            }
            if(!empty($userInfo['guild_id']) && 
                    ($userInfo['guild_id'] != $inviterGuildId) &&
                    $copyTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_INVITE_STATUS] == CopyTeamDef::INVITE_STATUS_GUILD)
            {
                continue;
            }
            //建筑等级 玩家等级  前置组队副本三个条件都满足了
            if($curCopyId == $teamCopyId)
            {
                $maxPassedCopy = $copyTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_VACOPYTEAM][CopyTeamDef::COPYTEAM_SUBSQLFIELD_PASSGUILDCOPY];
                if($maxPassedCopy < $curCopyId)//$maxPassedCopy >= $curCopyId 可以推断出前置普通副本已经通关了
                {
                    $arrUidInDoubt[] = $uid;
                }
            }
            $arrFilteredUser[$uid] = $userInfo;
        }
        //根据普通副本的通关情况进行第四次过滤
        $preNCopyId = CopyTeamLogic::getPreNCopy($teamCopyId);
        if(!empty($arrUidInDoubt))
        {
            $arrCopyInfo = NCopyDAO::getArrUserCopyInfo($arrUidInDoubt,
                    array('uid','va_copy_info'),$preNCopyId);
            $arrCopyInfo = Util::arrayIndex($arrCopyInfo, 'uid');
            $arrUser = $arrFilteredUser;
            $arrFilteredUser = array();
            foreach($arrUser as $uid => $userInfo)
            {
                if(in_array($uid, $arrUidInDoubt) == FALSE)
                {
                    $arrFilteredUser[$uid] = $userInfo;
                    continue;
                }
                if(isset($arrCopyInfo[$uid]) == FALSE)
                {
                    continue;
                }
                $copyInfo = $arrCopyInfo[$uid];
                $copyInfo['copy_id'] = $preNCopyId;
                if(CopyUtil::isNCopyPassed($copyInfo) == FALSE)
                {
                    continue;
                }
                $arrFilteredUser[$uid] = $userInfo;
            }
        }
        return $arrFilteredUser;
    }
    
    /**
     * 
     * @param int $teamType
     * @return UserTeamInfo
     */
    private static function getUserTeamObjByTeamType($teamType,$uid)
    {
        $userTeamObj = NULL;
        switch($teamType)
        {
            case CopyTeamDef::COPYTEAM_TYPE_GUILDTEAM:
                $userTeamObj = new UserGuildTeamInfo($uid);
                break;
        }
        return $userTeamObj;
    }
    
    public static function getNextTeamCopy($copyId)
    {
        if(!isset(btstore_get()->COPYTEAM[$copyId]['after_team_copy']))
        {
            return 0;
        }
        return btstore_get()->COPYTEAM[$copyId]['after_team_copy'];
    }
    
    public static function getTeamCopyType($copyId)
    {
        if(!isset(btstore_get()->COPYTEAM[$copyId]['team_type']))
        {
            return 0;
        }
        return btstore_get()->COPYTEAM[$copyId]['team_type'];
    }
    
    public static function getGuildCopyRfrGapTime()
    {
        return btstore_get()->GUILD_COPY[GuildDef::GUILD_REFRESH_CD];
    }
    
    public static function getTeamCopyNeedUserLevel($copyId)
    {
        return btstore_get()->COPYTEAM[$copyId]['need_level'];
    }
    
    public static function getGuildCopyNeedBuildLevel($copyId)
    {
        if(!isset(btstore_get()->GUILD_COPY[GuildDef::GUILD_COPY_ARR][$copyId]))
        {
            return PHP_INT_MAX;
        }
        return btstore_get()->GUILD_COPY[GuildDef::GUILD_COPY_ARR][$copyId];
    }
    
    public static function getMaxGuildCopyByGuildLv($uid)
    {
        $buildLv = EnGuild::getBuildLevel($uid);
        $maxCopyId = 0;
        $allGuildCopy = btstore_get()->GUILD_COPY[GuildDef::GUILD_COPY_ARR];
        foreach($allGuildCopy as $copyId => $needLv)
        {
            if($buildLv >= $needLv && ($copyId > $maxCopyId))
            {
                $maxCopyId = $copyId;
            }
        }
        return $maxCopyId;
    }
    
    public static function getPreNCopy($copyId)
    {
        return btstore_get()->COPYTEAM[$copyId]['pre_normal_copy'];
    }
    
    public static function getFirstGuildCopy()
    {
        $arrCopy = btstore_get()->GUILD_COPY['copyArr'];
        $openLevel = PHP_INT_MAX;
        $firstCopy = 0;
        foreach($arrCopy as $copyId => $level)
        {
            if($level <= $openLevel)
            {
                $openLevel = $level;
                $firstCopy = $copyId;
            }
        }
        return $firstCopy;
    }
    
    public static function getGuildDailyAddAtkNum()
    {
        return btstore_get()->GUILD_COPY[GuildDef::GUILD_COPY_ADD];
    }
    
    public static function getGuildAtkNumLimit()
    {
        return btstore_get()->GUILD_COPY[GuildDef::GUILD_COPY_LIMIT];
    }
    
    public static function getRoundNumPerHit()
    {
        return intval(btstore_get()->GUILD_COPY[GuildDef::GUILD_HIT_ROUNDS]);
    }
    
    public static function getGuildDailyHelpNum()
    {
        return intval(btstore_get()->GUILD_COPY[GuildDef::GUILD_HELP_NUM]);
    }
    
    public static function buyAtkNum($num,$uid)
    {
        $copyteamInst = new UserGuildTeamInfo($uid);
        $userObj = EnUser::getUserObj($uid);
        $buyNum = $copyteamInst->getBuyAtkNum();
        $buyNumLimit = btstore_get()->VIP[$userObj->getVip()]['teamcopyBuyNum'][0];
        if($buyNum+$num > $buyNumLimit)
        {
            throw new FakeException('can not buy.current buynum is %d limit is %d,want to buy %d',$buyNum,$buyNumLimit,$num);
        }
        $initGold = btstore_get()->VIP[$userObj->getVip()]['teamcopyBuyNum'][1];
        $incGold = btstore_get()->VIP[$userObj->getVip()]['teamcopyBuyNum'][2];
        $needGold = 0;
        for($i=0;$i<$num;$i++)
        {
            $needGold += ($initGold + ($buyNum + $i) * $incGold);
        }
        if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_BUY_TEAMCOPY_ATKNUM) == FALSE)
        {
            throw new FakeException('sub gold failed.');
        }
        $copyteamInst->addBuyAtkNum($num);
        $copyteamInst->addGuildAtkNum($num);
        $userObj->update();
        $copyteamInst->saveUserTeamInfo();
    }
    
    public static function setInviteStatus($uid,$status)
    {
        $copyteamInst = new UserGuildTeamInfo($uid);
        $copyteamInst->setInviteStatus($status);
        $copyteamInst->saveUserTeamInfo();
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */