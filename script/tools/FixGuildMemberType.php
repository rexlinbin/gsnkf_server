<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FixGuildMemberType.php 223095 2016-01-18 09:22:39Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/FixGuildMemberType.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-01-18 09:22:39 +0000 (Mon, 18 Jan 2016) $
 * @version $Revision: 223095 $
 * @brief 
 *  
 **/
class FixGuildMemberType extends BaseScript
{
    protected function executeScript ($arrOption)
    {
        $fix = false;
        if(isset($arrOption[0]) &&  $arrOption[0] == 'fix')
        {
            $fix = true;
        }

        $guildId = intval($arrOption[1]);
        $uid = intval($arrOption[2]);
        $type = intval($arrOption[3]);
        Util::kickOffUser($uid);
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);

        try
        {
            $guild = GuildObj::getInstance($guildId);
        }
        catch(Exception $e)
        {
            echo "guild is not exist.\n";
            return ;
        }

        $arrCond = array(
                array(GuildDef::GUILD_ID, '=', $guildId),
                array(GuildDef::MEMBER_TYPE, '=', GuildMemberType::PRESIDENT)
        );
        $arrRet = GuildDao::getMember($arrCond, array(GuildDef::USER_ID));
        if (!empty($arrRet[0][GuildDef::USER_ID]))
        {
            echo "guild has president.\n";
            return;
        }

        $member = GuildMemberObj::getInstance($uid);
        if ($guildId != $member->getGuildId())
        {
            echo "guild do not have uid.\n";
            return;
        }
        echo "guild has no president.\n";
        $member->setMemberType(GuildMemberType::PRESIDENT);

        if ($fix)
        {
             $member->update();
        }

        return "ok\n";
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */