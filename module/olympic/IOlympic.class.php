<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IOlympic.class.php 122265 2014-07-23 03:00:43Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/olympic/IOlympic.class.php $
 * @author $Author: ShijieHan $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-07-23 03:00:43 +0000 (Wed, 23 Jul 2014) $
 * @version $Revision: 122265 $
 * @brief 
 *  
 **/
/**
 * 
 * @author dell
 * 
 */
interface IOlympic
{
    /**
     * 进入擂台赛界面
     */
    function enterOlympic();
    
    /**
     * 离开擂台赛界面
     */
    function leave();
    /**
     * 不同阶段返回不同的信息，每个阶段都返回的信息：当前进行到哪个阶段、此阶段的结束时间
     * 三个不同的阶段返回的信息
     * 1.比赛前阶段：奖池信息、上一届冠军
     * 2.预选赛阶段：奖池信息、报名的32名玩家的信息、预选赛的战报信息
     * 3.进16强赛-进4强赛阶段：奖池信息、报名的32名玩家的信息、进16强赛到进4强赛的战报数据
     * 4.助威阶段到半决赛到决赛阶段到比赛后阶段：奖池信息、报名的32名玩家的信息、半决赛到决赛阶段的战报数据
     * 
     * @return array
     * <code>
     * [
     *     stage:int                当前的阶段
     *     status:int               0是准备 1是开始了  2是超时  3是出现错误  4是结束了
     *     stage_end_time:int        当前阶段的结束时间
     *     silver_pool:int            奖池总的银币数量
     *     cheer_uid:int              今天助威的玩家
     *     last_champion:array        上一届冠军信息
     *     challenge_cd:int           CD时间
     *     [
     *         uid:int
     *         uname:int
     *         dress:array
     *         htid:int
     *     ]
     *     rank_list:array       报名的32个玩家的信息
     *     [
     *         uid=>array
     *         [
     *             sign_up_index:int    报名位置
     *             olympic_index:int    比赛位置
     *             final_rank:int        排名
     *             uid:int
     *             uname:string
     *             dress:array
     *             htid:int
     *             vip:int
     *             level:int
     *             fight_force:int
     *             be_cheer_num:int
     *         ]
     *     ]
     *     fight_info:array        战斗数据
     *     [
     *         log_type=>array        log_type的取值 1.预选赛战报  2.16强战报  3.8强战报 4.4强战报  5.2强战报  6.冠军赛战报
     *         [
     *             array
     *             [
     *                 attacker:int
     *                 defender:int
     *                 brid:int
     *                 result:string
     *             ]
     *         ]
     *     ]
     * ]
     * </code> 
     */
    function getInfo();
    
    /**
     * 获得整体战报
     * 返回决赛的所有战报
     * @return array
     * <code>
     * [
     *     rank_list:array       报名的32个玩家的信息
     *     [
     *         uid=>array
     *         [
     *             sign_up_index:int    报名位置
     *             olympic_index:int    比赛位置
     *             final_rank:int        排名
     *             uid:int
     *             uname:int
     *             dress:array
     *             htid:int
     *         ]
     *     ]
     *     fight_info:array        战斗数据
     *     [
     *         stage=>array        log_type的取值 1.预选赛战报  2.16强战报  3.8强战报 4.4强战报  5.2强战报  6.冠军赛战报
     *         [
     *             array
     *             [
     *                 attacker:int
     *                 defender:int
     *                 brid:int
     *                 result:string
     *             ]
     *         ]
     *     ]
     * ]
     * </code>
     */
    function getFightInfo();

    /**
     * 报名
     * @param $index
     * @return string 'ok'
     */
    function signUp($index);
    /**
     * 决赛名额不满32时，能不能挑战
     * @param $signUpIndex
     * @return array
     * [
     *     res:string 战斗结果
     *     fight_ret:string 战报
     *     userInfo:array 用户的信息
     * ]
     */
    function challenge($signUpIndex);
    /**
     * 清除挑战Cd
     * @return array
     * <code>
     * [
     *  'gold' => int 扣除金币
     * ]
     * </code>
     */
    function clearChallengeCd();
    /**
     * 所有成功进入决赛的玩家不可点击助威. 失败了也不行吗？  	进入32强的玩家无法助威
     * 每个玩家只允许助威1个玩家,助威后不可撤销
     * @return string 'ok'
     */
    function cheer($cheerUid);
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */