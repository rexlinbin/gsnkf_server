<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CopyTeam.def.php 138461 2014-11-04 10:11:05Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/CopyTeam.def.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-11-04 10:11:05 +0000 (Tue, 04 Nov 2014) $
 * @version $Revision: 138461 $
 * @brief 
 *  
 **/
class CopyTeamDef
{
    const COPYTEAM_SQLFIELD_UID = 'uid';
    const COPYTEAM_SQLFIELD_CURGUILDCOPY = 'cur_guild_copy';
    const COPYTEAM_SQLFIELD_GUILDRFRTIME = 'guild_rfr_time';
    const COPYTEAM_SQLFIELD_GUILDATKNUM = 'guild_atk_num';
    const COPYTEAM_SQLFIELD_GUILDHELPNUM = 'guild_help_num';
    const COPYTEAM_SQLFIELD_VACOPYTEAM = 'va_copy_team';
    const COPYTEAM_SUBSQLFIELD_PASSGUILDCOPY = 'cur_passed_guild_copy';
    const COPYTEAM_SQLFIELD_BUY_ATKNUM = 'buy_atk_num';
    const COPYTEAM_SQLFIELD_INVITE_STATUS = 'invite_status';
    
    
    static $ALL_GUILD_TEAM_SQLFIELD = array(
            self::COPYTEAM_SQLFIELD_UID,
            self::COPYTEAM_SQLFIELD_CURGUILDCOPY,
            self::COPYTEAM_SQLFIELD_GUILDRFRTIME,
            self::COPYTEAM_SQLFIELD_GUILDATKNUM,
            self::COPYTEAM_SQLFIELD_GUILDHELPNUM,
            self::COPYTEAM_SQLFIELD_BUY_ATKNUM,
            self::COPYTEAM_SQLFIELD_INVITE_STATUS,
            self::COPYTEAM_SQLFIELD_VACOPYTEAM,
            );
    
    //TODO:与lcserver一致
    const COPYTEAM_TYPE_GUILDTEAM = 1;//副本组队类型之公会组队
    const COPYTEAM_TYPE_COMMONCOPY = 2;//副本组队类型之普通副本组队
    
    
    const INVITE_STATUS_ALL = 0;
    const INVITE_STATUS_GUILD = 1;
    /**
     * 
     */
    const JOIN_TEAM_LIMIT_TYPE_GUILD = 2;//公会
    const JOIN_TEAM_LIMIT_TYPE_NOLIMIT = 1;//无限制
    const JOIN_TEAM_LIMIT_TYPE_GROUP = 3;//阵营
    
    static $ALL_JOIN_LIMIT_TYPE = array(
            self::JOIN_TEAM_LIMIT_TYPE_GROUP,
            self::JOIN_TEAM_LIMIT_TYPE_GUILD,
            self::JOIN_TEAM_LIMIT_TYPE_NOLIMIT
            );
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */