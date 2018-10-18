<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IChargeDart.class.php 241166 2016-05-05 10:35:37Z ShuoLiu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chargedart/IChargeDart.class.php $
 * @author $Author: ShuoLiu $(hoping@babeltime.com)
 * @date $Date: 2016-05-05 10:35:37 +0000 (Thu, 05 May 2016) $
 * @version $Revision: 241166 $
 * @brief 
 *  
 **/


/********************************************************************/
/* 木牛流马接口类
 * ↓↓↓↓↓↓↓画个小马，没有bug↓↓↓↓↓↓↓
 *                   .-' _..`.
                  /  .'_.'.'
                 | .' (.)`.
                 ;'   ,_   `.
 .—.__________.'    ;  `.;-'
|  ./               /
|  |               / 
`..'`-._  _____, ..'
     / | |     | |\ \
    / /| |     | | \ \
   / / | |     | |  \ \
  /_/  |_|     |_|   \_\
 |__\  |__\    |__\  |__\

 * 建立接口  2016年04月06日
 * 基本完成代码编写 2016年04月12日
 * 一大波修改 2016年04月21日
 * 5月份修改：
 * 1.接受邀请时，只能在车未出发的时候
 * 2.运送和刷新车优先使用未使用次数，次数不足时优先使用道具
 * 3.区域的刷新次数随车走，不随人
 */
/********************************************************************/


interface IChargeDart
{
    /**
     * 进入押镖页面，返回玩家基础信息和所在页的信息，没有所在页则返回高级区第一页
     * @return array
     * <code>
     * {
     *      'have_charge_dart'=>int  //当前是否有镖车
     *      'shipping_num'=>int     //已用的运送次数
     *      'rest_ship_num'=>int    //剩余的运送次数
     *      'rob_num'=>int   //已用的掠夺次数
     *      'rest_rob_num'=>int //剩余的掠夺次数
     *      'assistance_num'=>int   //已用的协助次数
     *      'rest_assistance_num'=>int   //剩余的协助次数
     *      'stage_id'=>int         
     *      'page_id'=>int 
     *      ----------------------------
     *      'page_info'=>array(
     *          'road_id'=>array(
     *              'uid'=>array(
     *                  'uname'
     *                  'begin_time'
     *                  'be_robbed_num' //被掠夺次数
     *                  //'has_rage' //狂怒
     *                  'guild_name'
     *                  ),
     *              ),
     *          ),
     *      ----------------------------
     *      'page_info'=>array(
     *          0=>array(
     *              'uname'
     *              'road_id'
     *              'begin_time'
     *              'be_robbed_num' //被掠夺次数
     *              'guild_name'
     *          ),
     *      ),
     * }
     * </code>
     */
    function enterChargeDart();
    
    
    /**
     * 离开场景
     */
    function leave();
    
    /**
     * 取得某一页的信息
     * @param int $stage_id 区域id
     * @param int $page_id 页id
     * @return array
     * <code>
     * {
     *      //'stage_id'=>int
     *      //'page_id'=>int,
     *      --------------------------
     *      'page_info'=>array(
     *          'road_id'=>array(
     *              'uid'=>array(
     *                  'uname'
     *                  'begin_time'
     *                  'be_robbed_num' //被掠夺次数
     *                  //'has_rage' //狂怒
     *                  'guild_name'
     *                  ),
     *              ),
     *          ),
     *      -------------------------
     *      2016年04月08改成了用下面这个
     *      'page_info'=>array(
     *          0=>array(
     *              'uname'
     *              'road_id'
     *              'begin_time'
     *              'be_robbed_num' //被掠夺次数
     *              'guild_name'
     *          ),
     * }
     * </code>
     */
    function getOnePageInfo($stageId,$pageId);
    
    /**
     * 取得单个镖车的信息
     * @param int $targetUid 镖车主人的uid
     * @return array
     * <code>
     * {
     *      'uid'=>int,
     *      'uname'=>int,
     *      'level'=>int,
     *      'guild_name'=>string
     *      'begin_time'=>int 
     *      'quality'=>int //品质
     *      'be_robbed_num'=>int //被掠夺次数
     *      'have_assistance'=>int //是否有协助者
     *      'have_look_success'=>int //是否已经瞭望过了
     *      'have_rob_success' => int //是否掠夺成功
     *      'stage_id'=>int //在哪一区域
     *      'page_id'=>int //在那一页
     *      'assistance_uid'=>int //协助人uid，判断查看的玩家是否是协助者时使用
     *      'assist_uname' => string //自己瞭望自己且有协助者的时候有这个字段
     * }
     * </code>
     */
    function getChargeDartInfo($targetUid);

    /**
     * 瞭望
     * @param int $targetUid
     * @return array
     * <code>
     * {
     *  array(
     *      0=>array(    //主人的信息
     *          'uid' => 
     *          'uname' => 
     *          'level' => 
     *          'fight_force' => 
     *          'have_rage' => 
     *          'utid' => 
     *          'htid' => 
     *          'guild_id' =>
     *          'guild_name' =>
     *      )
     *      1=>array(...内容同上) //协助者的信息，协助者为空则没有此字段
     *  )
     * }
     * </code>
     */
    function ChargeDartLook($targetUid);
    
    /**
     * 掠夺
     * @param int $targetUid 
     * @param int $rage 掠夺者是否要开始狂怒
     * @return array
     * <code>
     * {
     *  array(
     *      'success' => int 0失败，1成功
     *      'atkRet1' => array(
     *          'fightRet' => 战斗串
     *          'appraisal' => 评价
     *          ),
     *      'atkRet2' => array(
     *          'fightRet' => 战斗串
     *          'appraisal' => 评价
     *          ),
     *      'userInfo' => array( //结算字段，注意没有工会的不会返回guild_name字段
     *          1=>array( //协助者结算字段，如果没有协助者则只有resetHpPrecent字段
     *              'uname'
     *              'level'
     *              'htid'
     *              'guild_name'
     *              'resetHpPrecent' => 剩余血量百分比
     *              ),
     *          2=>array( //主人结算字段
     *              'uname'
     *              'level'
     *              'htid'
     *              'guild_name'
     *              'resetHpPrecent' => 剩余血量百分比
     *              ),
     *          ),
     *   )
     * }
     */
    function rob($rage,$targetUid);
    
    /**
     * 协助好友
     * @param int $targetUid
    function assistance($targetUid);*/
    
    /**
     * 进入运送界面（刷新区域，邀请协助的界面）
     * @return array
     * <code>
     * {
     *      'stage_id'=>int //当前刷新出的区域
     *      'refresh_num'=>int //已用刷新次数
     *      'has_invited'=>int //是否已经邀请人了
     *      
     *      //协助人信息,没有则全空
     *      'assistance_uid'=>int
     *      'assistance_uname'=>int
     *      'assistance_level'=>int
     *      'assistance_guildname'=>string
     * }
     * </code>
     */
    function enterShipPage();
    
    /**
     * 刷新区域
     * @return int $stage_id
     */
    function refreshStage();
    /**
     * 邀请好友
     * @param int $targetUid 被邀请的好友uid
     */
    function inviteFriend($targetUid);
    
    /**
     * 接受邀请
     * @param int $targetUid 邀请人的uid
     * @param int $flag 标记某个镖车的邀请的字段
     */
    function acceptInvite($targetUid, $flag);
    
    /**
     * 开始运送
     * @return array
     * <code>
     * {
     *      'stage_id'=>int 哪一个区域
     *      'page_id'=>int 哪一页
     * }
     * </code>
     */
    function beginShipping();
    
    /**
     * 开启狂怒
     * @param int $type 0为给自己，1为给协助者
     * @return bool
     */
    function openRage($type);
    
    /**
     * 疾行
     * @return bool
     */
    function finishByGold();
    
    /**
     * 购买掠夺次数
     * @return bool
     */
    function buyRobNum($num);
    
    /**
     * 购买运送次数
     */
    function buyShipNum($num);
    
    /**
     * 购买协助次数
     */
    function buyAssistanceNum($num);
    
    
    //我当前车的信息，两个区域的，我的所有的
    /*/**
     * 获得我的当前车的瞭望，被掠夺等信息
     *
    function getThisChargeDartInfo($targetUid);*/
    
    /**
     * 得到某个区域的掠夺成功的战报信息
     * @param unknown $stage_id
     * @return array
     * <code> 按照时间从最近到最早排好序的
     * {    0=>array(
     *          'uid'       攻击者
     *          'be_uid'    被攻击者
     *          'time'      发生时间
     *          'be_robbed_num' 被掠夺测试，到达终点那种情况里用于结算奖励
     *          'brid1'     战报1，攻击者打协助者的战报
     *          'brid2'     战报2，攻击者打镖车主人的战报
     *          'uname'     攻击者角色名
     *          'beUname'   被攻击者角色名
     *          'isDouble'  此次运送是否是双倍奖励，0否，1是
     *      )
     *      1=>array(...
     * }
     * </code>
     */
    function getStageInfo($stageId);
    
    /**
     * 得到我的所有的被抢记录，瞭望记录等
     * <code>  按照时间从最近到最早排好序的
     * {    0=>array(
     *          'stage_id'  区域id
     *          'uid'       type=1攻击者，type=2瞭望人，type=3镖车主人
     *          'be_uid'    type=1被攻击者，type=2被瞭望人，type=3协助者
     *          'type'      类型，1战斗，2瞭望，3镖车到达终点
     *          'time'      发生时间
     *          'success'   是否成功
     *          'be_robbed_num' 被掠夺测试，到达终点那种情况里用于结算奖励
     *          'brid1'     战报1，攻击者打协助者的战报
     *          'brid2'     战报2，攻击者打镖车主人的战报
     *          'uname'     攻击者角色名 同uid和be_uid
     *          'beUname'   被攻击者角色名  be_uid=0时没有此字段
     *          'isDouble'  此次运送是否是双倍奖励，0否，1是
     *      )
     *      1=>array(....
     * }
     * </code>
     */
    function getAllMyInfo();
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */