<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UserGuildTeamInfo.class.php 156136 2015-01-30 05:55:59Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/copyteam/UserGuildTeamInfo.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-01-30 05:55:59 +0000 (Fri, 30 Jan 2015) $
 * @version $Revision: 156136 $
 * @brief 
 *  
 **/
class UserGuildTeamInfo extends UserTeamInfo
{
    public function __construct($uid)
    {
        if(empty($uid))
        {
            $uid = RPCContext::getInstance()->getUid();
        }
        parent::__construct($uid);
        $userTeamInfo = CopyTeamDao::getCopyTeamInfo($uid, CopyTeamDef::$ALL_GUILD_TEAM_SQLFIELD);
        if(empty($userTeamInfo))
        {
            $userTeamInfo = $this->initUserTeamInfo();
        }
        $this->userTeamInfo = $userTeamInfo;
        $this->buffer = $userTeamInfo;
        self::rfrGuildInfo();
    }
    
    
    
    public function initUserTeamInfo()
    {
        $yesterday = Util::getTime() - SECONDS_OF_DAY;
        $userTeamInfo = array(
                CopyTeamDef::COPYTEAM_SQLFIELD_UID => $this->uid,
                CopyTeamDef::COPYTEAM_SQLFIELD_CURGUILDCOPY => CopyTeamLogic::getFirstGuildCopy(),
                CopyTeamDef::COPYTEAM_SQLFIELD_GUILDATKNUM => 0,
                CopyTeamDef::COPYTEAM_SQLFIELD_GUILDHELPNUM => 0,
                CopyTeamDef::COPYTEAM_SQLFIELD_GUILDRFRTIME => $yesterday,
                CopyTeamDef::COPYTEAM_SQLFIELD_INVITE_STATUS => CopyTeamDef::INVITE_STATUS_ALL,
                CopyTeamDef::COPYTEAM_SQLFIELD_BUY_ATKNUM => 0,
                CopyTeamDef::COPYTEAM_SQLFIELD_VACOPYTEAM => array(
                        CopyTeamDef::COPYTEAM_SUBSQLFIELD_PASSGUILDCOPY => 0,
                ),
        );
        CopyTeamDao::initCopyTeamInfo($userTeamInfo);
        return $userTeamInfo;
    }
    
    private function rfrGuildInfo()
    {
        $lastRfrTime = $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDRFRTIME];
        if(Util::isSameDay($lastRfrTime) == FALSE)
        {
            $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_BUY_ATKNUM] = 0;
            $diffDay = Util::getDaysBetween($lastRfrTime);
            $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDRFRTIME] = Util::getTime();
            $dailyAddNum = CopyTeamLogic::getGuildDailyAddAtkNum();
            $wealConf = EnWeal::getWeal(WealDef::COPY_TEAM_GUILD);
            if(!empty($wealConf))
            {
                if($wealConf/UNIT_BASE > 10 || ($wealConf/UNIT_BASE < 1))
                {
                    throw new FakeException('weal conf for guild copy team is wrong.conf is'.$wealConf);
                }
                $dailyAddNum = intval($dailyAddNum * ($wealConf/UNIT_BASE));
            }
            $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDATKNUM] += $diffDay * $dailyAddNum;
            if($this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDATKNUM] > CopyTeamLogic::getGuildAtkNumLimit())
            {
                $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDATKNUM] = CopyTeamLogic::getGuildAtkNumLimit();
            }
            $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDHELPNUM] = 0;
        }
        $maxGuildCopy = CopyTeamLogic::getMaxGuildCopyByGuildLv($this->uid);
        if($maxGuildCopy <= $this->getCurPassedGuildCopy())
        {
            if(empty($maxGuildCopy))
            {
                $this->setCurGuildCopy(CopyTeamLogic::getFirstGuildCopy());
            }
            else
            {
                $nextCopy = CopyTeamLogic::getNextTeamCopy($maxGuildCopy);
                if(!empty($nextCopy))
                {
                    $this->setCurGuildCopy($nextCopy);
                }
                else
                {
                    $this->setCurGuildCopy($maxGuildCopy);
                }
            }
        }
        else
        {
            $curPassedCopy = $this->getCurPassedGuildCopy();
            if(empty($curPassedCopy))
            {
                $this->setCurGuildCopy(CopyTeamLogic::getFirstGuildCopy());
            }
            else
            {
                $nextCopy = CopyTeamLogic::getNextTeamCopy($curPassedCopy);
                if(!empty($nextCopy))
                {
                    $this->setCurGuildCopy($nextCopy);
                }
                else
                {
                    $this->setCurGuildCopy($curPassedCopy);
                }
            }
        }
    }
    
    public function getGuildAtkNum()
    {
        return $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDATKNUM];
    }
    
    public function addGuildAtkNum($num)
    {
        $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDATKNUM] += $num;
    }
    
    public function getGuildHelpNum()
    {
        return $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDHELPNUM];
    }
    
    
    public function subGuildAtkNum()
    {
        if($this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDATKNUM] < 1)
        {
            throw new FakeException('no guild atk num.sub failed.');
        }
        $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDATKNUM]--;
    }
    
    public function addGuildHelpAtkNum()
    {
        if($this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDHELPNUM] >= CopyTeamLogic::getGuildDailyHelpNum())
        {
            throw new FakeException('no guild help atk num.add failed.');
        }
        $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDHELPNUM]++;
    }
    
    public function getCurGuildCopy()
    {
        return $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_CURGUILDCOPY];
    }
    
    public function getBuyAtkNum()
    {
        return $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_BUY_ATKNUM];
    }
    
    public function addBuyAtkNum($num)
    {
        $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_BUY_ATKNUM] += $num;
    }
    
    public function setGuildCanAtkNum($num)
    {
        $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDATKNUM] = $num;
    }
    
    public function canAtk($copyId)
    {
        if($this->getCurGuildCopy() > $copyId)
        {
            return 'ok';
        }
        if($this->getCurGuildCopy() < $copyId)
        {
            return 'cur_copy_is_'.$this->getCurGuildCopy();
        }
        //建筑等级        
        $buildLv = EnGuild::getBuildLevel($this->uid);
        if($buildLv === -1)
        {
            return 'not_in_any_guild';
        }
        $needLv = CopyTeamLogic::getGuildCopyNeedBuildLevel($copyId);
        if($needLv > $buildLv)
        {
            return 'not_enough_build_level';
        }
        //相应普通副本是否通关
        $preNCopy = CopyTeamLogic::getPreNCopy($copyId);
        if(!empty($preNCopy) && (MyNCopy::getInstance($this->uid)->isCopyPassed($preNCopy) == FALSE))
        {
            return 'pre_normal_copy_'.$preNCopy.'_is_not_passed';
        }
        //军团组队次数
        if($this->getGuildAtkNum() <= 0 && ($this->getGuildHelpNum() >= CopyTeamLogic::getGuildDailyHelpNum()))
        {
            return 'not_enough_team_atk_num';
        }
        return 'ok';
    }
    
    public function doneTeamBattle($copyId, $atkRet, $isLeader)
    {
        if($atkRet['server']['result'] == FALSE)
        {
            return array();
        }
        if($this->getGuildAtkNum() > 0)
        {
            $this->subGuildAtkNum();
        }
        else
        {
            $this->addGuildHelpAtkNum();
        }
        if($copyId >= $this->getCurGuildCopy())
        {
            $nextCopy = CopyTeamLogic::getNextTeamCopy($copyId);
            if(!empty($nextCopy))
            {
                if(CopyTeamLogic::getTeamCopyType($nextCopy) != CopyTeamDef::COPYTEAM_TYPE_GUILDTEAM)
                {
                    throw new ConfigException('copy %d next copy %d is not guild team copy.',$copyId,$nextCopy);
                }
                $this->setCurGuildCopy($nextCopy);
            }
            $this->setCurPassedGuildCopy($copyId);
        }
        return $this->getUserTeamInfo();
    }
    
    public function setCurGuildCopy($copyId)
    {
        if(empty($copyId))
        {
            return;
        }
        $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_CURGUILDCOPY] = $copyId;
    }
    
    private function getCurPassedGuildCopy()
    {
        if(!isset($this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_VACOPYTEAM][CopyTeamDef::COPYTEAM_SUBSQLFIELD_PASSGUILDCOPY]))
        {
            $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_VACOPYTEAM][CopyTeamDef::COPYTEAM_SUBSQLFIELD_PASSGUILDCOPY] = 0;
        }
        return $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_VACOPYTEAM][CopyTeamDef::COPYTEAM_SUBSQLFIELD_PASSGUILDCOPY];
    }
    
    public function setCurPassedGuildCopy($copyId)
    {
        if(empty($copyId))
        {
            return;
        }
        if($copyId > $this->getCurPassedGuildCopy())
        {
            $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_VACOPYTEAM][CopyTeamDef::COPYTEAM_SUBSQLFIELD_PASSGUILDCOPY] = $copyId;
        }
    }
    
    public function setInviteStatus($status)
    {
        $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_INVITE_STATUS] = $status;
    }
    
    public function getInviteStatus()
    {
        return  $this->userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_INVITE_STATUS];
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */