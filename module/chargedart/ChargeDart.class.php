<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChargeDart.class.php 239268 2016-04-20 07:50:05Z ShuoLiu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chargedart/ChargeDart.class.php $
 * @author $Author: ShuoLiu $(hoping@babeltime.com)
 * @date $Date: 2016-04-20 07:50:05 +0000 (Wed, 20 Apr 2016) $
 * @version $Revision: 239268 $
 * @brief 
 *  
 **/

class ChargeDart implements IChargeDart
{
    
    private $uid = null;
    
    public function __construct()
    {
        $this->uid = RPCContext::getInstance()->getUid();
        //TODO 功能节点检查
        if(!EnSwitch::isSwitchOpen(SwitchDef::CHARGEDART))
        {
            throw new FakeException("This switch is not open!");
        }
        
        //等级检查
        $need_level = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_LEVEL];
        $user = EnUser::getUserObj($this->uid);
        $user_level = $user->getLevel();
        if($user_level < $need_level)
        {
            throw new FakeException("The user's level is not enough!");
        }
    }
    
    /**
     * 进入押镖页面，返回玩家基础信息和所在页的信息，没有所在页则返回高级区第一页
     * @return array
     * <code>
     * {
     *      'have_charge_dart'=>int  //当前是否有镖车
     *      'shipping_num'=>int     //已用的运送次数
     *      'plundering_num'=>int   //已用的掠夺次数
     *      'assistance_num'=>int   //已用的协助次数
     *      'stage_id'=>int
     *      'page_id'=>int,
     *      'page_info'=>array(
     *          'road_id'=>array(
     *              'uid'=>array(
     *                  'uname'
     *                  'begin_time'
     *                  'be_plundered_num' //被掠夺次数
     *                  //'has_rage' //狂怒
     *                  'guild_name'
     *                  ),
     *              ),
     *          ),
     * }
     * </code>
     */
    public function enterChargeDart()
    {
        //标记进入场景
        RPCContext::getInstance()->setSession(SPECIAL_ARENA_ID::SESSION_KEY, SPECIAL_ARENA_ID::CHARGEDART);
        
        return ChargeDartLogic::enterChargeDart($this->uid);
    }
    
    
    /**
     * 离开场景
    */
    public function leave()
    {
        RPCContext::getInstance()->unsetSession(SPECIAL_ARENA_ID::SESSION_KEY);
    }
    
    /**
     * 取得某一页的信息
     * @param int $stage_id 区域id
     * @param int $page_id 页id
     * @return array
     * <code>
     * {
     *      'stage_id'=>int
     *      'page_id'=>int,
     *      'page_info'=>array(
     *          'road_id'=>array(
     *              'uid'=>array(
     *                  'uname'
     *                  'begin_time'
     *                  'be_plundered_num' //被掠夺次数
     *                  //'has_rage' //狂怒
     *                  'guild_name'
     *                  ),
     *              ),
     *          ),
     * }
     * </code>
    */
    public function getOnePageInfo($stageId,$pageId)
    {
        return ChargeDartLogic::getOnePageInfo($stageId, $pageId);
    }
    
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
     *      'be_plundered_num'=>int //被掠夺次数
     *      'has_assitance'=>int //是否有协助者
     *      'has_look_success'=>int //是否已经瞭望过了
     *      'stage_id'=>int //在哪一区域
     *      'page_id'=>int //在那一页
     * }
     * </code>
    */
    public function getChargeDartInfo($targetUid)
    {
        return ChargeDartLogic::getChargeDartInfo($this->uid, $targetUid);
    }
    
    /**
     * 瞭望
     * @param int $targetUid
     * @return array
     * <code>
     * {
     *      'assistance_uid'=>int
     *      'assistance_uname'=>int
     *      'assistance_level'=>int
     *      'assistance_guildname'=>string
     *      'user_fight_force'=>int
     *      'assistance_fight_force'=>int
     *      'user_have_rage'=>int //主人是否开启狂怒
     *      'assistance_have_rage'=>int //协助者是否开启狂怒
     *      'user_htid'=>int //显示头像使用
     *      'assistance_htid'=>int //显示头像使用
     * }
     * </code>
    */
    public function ChargeDartLook($targetUid)
    {
        return ChargeDartLogic::ChargeDartLook($this->uid, $targetUid);
    }
    
    /**
     * 掠夺
     * @param int $targetUid
     * @param int $rage 掠夺者是否要开始狂怒
    */
    public function rob($rage,$targetUid)
    {
        return ChargeDartLogic::rob($this->uid, $rage, $targetUid);
    }
    
    /**
     * 协助好友
     * @param int $targetUid
    public function assistance($targetUid);*/
    
    /**
     * 进入运送界面
     * @return array
     * <code>
     * {
     *      'stage_id'=>int //当前刷新出的区域
     *      'shipping_num'=>int //已用的运送次数
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
    public function enterShipPage()
    {
        return ChargeDartLogic::enterShipPage($this->uid);
    }
    
    /**
     * 刷新区域
     * @return int $stage_id
    */
    public function refreshStage()
    {
        return ChargeDartLogic::refreshStage($this->uid);
    }
    
    /**
     * 邀请好友
     * @param int $targetUid 被邀请的好友uid
    */
    public function inviteFriend($targetUid)
    {
        return ChargeDartLogic::inviteFriend($this->uid, $targetUid);
    }
    
    /**
     * 接受邀请
     * @param int $targetUid 邀请人的uid
    */
    public function acceptInvite($targetUid, $flag)
    {
        return ChargeDartLogic::acceptInvite($this->uid, $targetUid, $flag);
    }
    
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
    public function beginShipping()
    {
        return ChargeDartLogic::beginShipping($this->uid);
    }
    
    /**
     * 开启狂怒
     * @param int $type 0为给自己，1为给协助者
     * @return bool
    */
    public function openRage($type)
    {
        return ChargeDartLogic::openRage($this->uid, $type);
    }
    
    /**
     * 疾行
     * @return bool
    */
    public function finishByGold()
    {
        return ChargeDartLogic::finishByGold($this->uid);
    }
    
    /**
     * 购买掠夺次数
     * @return bool
    */
    public function buyRobNum($num)
    {
        return ChargeDartLogic::buyRobNum($this->uid, $num);
    }
    
    /**
     * 购买运送次数
    */
    public function buyShipNum($num)
    {
        return ChargeDartLogic::buyShipNum($this->uid, $num);
    }
    
    /**
     * 购买协助次数
    */
    public function buyAssistanceNum($num)
    {
        return ChargeDartLogic::buyAssistanceNum($this->uid, $num);
    }
    
    
    //我当前车的信息，两个区域的，我的所有的
    /**
     * 获得我的当前车的瞭望，被掠夺等信息
    *
    public function getThisChargeDartInfo($targetUid)
    {
        return ChargeDartLogic::getThisChargeDartInfo($targetUid);
    }*/
    
    /**
     * 得到某个区域的掠夺成功的战报信息
     * @param unknown $stage_id
    */
    public function getStageInfo($stageId)
    {
        return ChargeDartLogic::getStageInfo($stageId);
    }
    
    /**
     * 得到我的所有的被抢记录，瞭望记录等
    */
    public function getAllMyInfo()
    {
        return ChargeDartLogic::getAllMyInfo($this->uid);
    }
    
    public function __sendReward($uid,$beginTime)
    {
        return ChargeDartLogic::__sendReward($uid,$beginTime);
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */