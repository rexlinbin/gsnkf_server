<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ICopyTeam.class.php 138620 2014-11-05 09:56:23Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/copyteam/ICopyTeam.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-11-05 09:56:23 +0000 (Wed, 05 Nov 2014) $
 * @version $Revision: 138620 $
 * @brief 
 *  
 **/
interface ICopyTeam
{
    /**
     * 
     * @param int $teamType        副本组队类型暂时只有一种组队类型  1.公会副本组队
     * @return array
     * [
     *     uid:int
     *     cur_guild_copy:int
     *     guild_atk_num:int            此玩家的剩余公会副本组队次数
     *     guild_rfr_time:int
     *     guild_help_num:int
     *     buy_atk_num:int
     *     invite_status:int            邀请状态  0是可以被所有人邀请  1是只能被本公会的人邀请
     *     va_copy_team:array
     *     [
     *         cur_passed_guild_copy:int
     *     ]
     * ]
     */
    public function getCopyTeamInfo($teamType);
    /**
     * 创建队伍
     * @param int $copyId
     * @param int $joinLimit        组队成员限制  1.没限制  3.成员必须属于同一公会  2.成员必须属于同一阵营
     */
    public function createTeam($copyId,$joinLimit,$isAutoStart=FALSE);
    /**
     * 加入某个队伍
     * @param int $teamId
     * @param int $copyId
     */
    public function joinTeam($teamId,$copyId);
    /**
     * 开始组队战斗
     * @param array $teamList
     * @param int $copyId
     */
    public function startTeamAtk($teamList,$copyId);
    
    /**
     * 邀请全服的人参加工会组队战  拉取邀请列表
     * 1.首先拉取公会的成员
     * 2.拉取公会之外的成员（按等级拉取）
     * @param int $TeamCopyId
     * @param array $teamList
     * @return array
     * <code>
     * uid=>array
     *      [
     *          uid:int
     *          uname:string
     *          level:int
     *          fight_force:int
     *          dress:array
     *      ]
     * </code>
     */
    public function getAllInviteInfo($TeamCopyId,$teamList,$num);
    
    /**
     * 
     * @param int $uid
     * @param int $teamCopyId
     * @param int $teamId
     * @return string 'ok' 'fail'
     */
    public function inviteGuildMem($uid,$teamCopyId,$teamId);
    
    /**
     * 购买组队次数
     * @param int $num
     * @return string 'ok'
     */
    public function buyAtkNum($num);
    
    /**
     * 设置邀请状态  0是可以被所有人邀请  1是只能被本公会的人邀请
     * @param int $status
     * @return string 'ok'
     */
    public function setInviteStatus($status);
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */