<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CopyTeam.class.php 138626 2014-11-05 10:13:04Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/copyteam/CopyTeam.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-11-05 10:13:04 +0000 (Wed, 05 Nov 2014) $
 * @version $Revision: 138626 $
 * @brief 
 *  
 **/
class CopyTeam implements ICopyTeam
{
    private $uid = NULL;
    
    public function __construct()
    {
        $this->uid = RPCContext::getInstance()->getUid();
    }
    
    public function getCopyTeamInfo($teamType)
    {
        Logger::debug('copyteam.getCopyTeamInfo start.param %d.',$teamType);
        list($teamType) = Util::checkParam(__METHOD__, func_get_args());
        $ret = CopyTeamLogic::getCopyTeamInfo($teamType, $this->uid);
        return $ret;
    }
    
    public function createTeam($copyId,$joinLimit,$isAutoStart=FALSE)
    {
        Logger::debug('copyteam.createTeam params:%d.%d.',$copyId,$joinLimit);
        list($copyId,$joinLimit) = Util::checkParam(__METHOD__, func_get_args());
        if(!in_array($joinLimit, CopyTeamDef::$ALL_JOIN_LIMIT_TYPE))
        {
            throw new FakeException('joinlimit is %d.error',$joinLimit);
        }
        CopyTeamLogic::createTeam($copyId, $joinLimit,$isAutoStart, $this->uid);
    }
    
    public function joinTeam($teamId,$copyId)
    {
        Logger::debug('copyteam.joinTeam.params %d.%d.',$teamId,$copyId);
        list($teamId,$copyId) = Util::checkParam(__METHOD__, func_get_args());
        CopyTeamLogic::joinTeam($teamId, $copyId, $this->uid);
    }
    
    public function startTeamAtk($teamList,$copyId)
    {
        Logger::debug('copyteam.startTeamAtk.params %s.%d.',$teamList,$copyId);
        list($teamList,$copyId) = Util::checkParam(__METHOD__, func_get_args());
        CopyTeamLogic::startTeamAtk($copyId, $teamList);
    }
    
    public function doneTeamBattle($teamMember, $copyId, $atkRet, $isLeader)
    {
        Logger::trace('copyteam.doneTeamBattle start.teammember %d,copyid %d.',$teamMember,$copyId);
        CopyTeamLogic::doneTeamBattle($teamMember, $copyId, $atkRet, $isLeader);
    }
    
    public function inviteGuildMem($inviteUid,$teamCopyId,$teamId)
    {
        //发送消息给其他公会成员    sendMsg给玩家(邀请人、teamId)
        //首先判断被邀请人是否在线
        $inviteUser = EnUser::getUserObj($inviteUid);
        if ($inviteUser->isOnline() == FALSE)
        {
            return 'fail';
        }
        $arrUser = EnUser::getArrUserBasicInfo(array($this->uid,$inviteUid), 
                array('uid','uname','dress','htid','fight_force','level','guild_id','vip'));
        $userInfo = $arrUser[$this->uid];
        $guildId = $userInfo['guild_id'];
        $copyTeamObj = new UserGuildTeamInfo($inviteUid);
        $copyTeamInfo = $copyTeamObj->getUserTeamInfo();
        if ($copyTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_INVITE_STATUS] == CopyTeamDef::INVITE_STATUS_GUILD 
                && ($guildId != $arrUser[$inviteUid]['guild_id']))
        {
            return 'fail';
        }
        $arrGuildInfo = EnGuild::getArrGuildInfo(array($guildId));
        $userInfo[GuildDef::GUILD_NAME] = $arrGuildInfo[$guildId][GuildDef::GUILD_NAME];
        RPCContext::getInstance()->sendMsg(array($inviteUid),
                 PushInterfaceDef::COPY_TEAM_INVITE_GUILD_MEM, 
                array($userInfo,$teamCopyId,$teamId));
        return 'ok';
    }
    
    public function getAllInviteInfo($teamCopyId,$teamList,$num)
    {
        Logger::trace('CopyTeam.getAllInviteInfo start.params teamcopyid %d.teamlist %s.',$teamCopyId,$teamList);
        list($TeamCopyId,$teamList) = Util::checkParam(__METHOD__, func_get_args());
        $ret = CopyTeamLogic::getAllInviteInfo($this->uid, $teamCopyId,$teamList, $num);
        return $ret;
    }
    
    public function buyAtkNum($num)
    {
        list($num) = Util::checkParam(__METHOD__, func_get_args());
        CopyTeamLogic::buyAtkNum($num, $this->uid);
        return 'ok';
    }
    
    public function setInviteStatus($status)
    {
        list($status) = Util::checkParam(__METHOD__, func_get_args());
        CopyTeamLogic::setInviteStatus($this->uid, $status);
        return 'ok';
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */