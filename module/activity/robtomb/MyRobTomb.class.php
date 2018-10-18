<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MyRobTomb.class.php 202938 2015-10-17 10:46:51Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/robtomb/MyRobTomb.class.php $
 * @author $Author: wuqilin $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-10-17 10:46:51 +0000 (Sat, 17 Oct 2015) $
 * @version $Revision: 202938 $
 * @brief 
 *  
 **/
class MyRobTomb
{
    private $uid = NULL;
    private $buffer = NULL;
    private $robInfo = NULL;
    /**
     *
     * @var MyRobTomb
     */
    private static $_instance = NULL;
    
    
    private function __construct($uid=0)
    {
        if(empty($uid))
        {
            $uid = RPCContext::getInstance()->getUid();
            if(empty($uid))
            {
                throw new FakeException('uid in session is null');
            }
        }
        $this->uid = $uid;
        $robInfo = RobTombDao::getRobInfo($uid, RobTombDef::$ALL_TBL_FIELD);
        $this->robInfo = $robInfo;
        $this->buffer = $robInfo;
        $this->refreshRobInfo();
    }
    
    public function getUid()
    {
        return $this->uid;
    }
    
    /**
     *
     * @param int $uid
     * @return MyRobTomb
     */
    public static function getInstance($uid=0)
    {
        if(empty($uid))
        {
            $uid = RPCContext::getInstance()->getUid();
        }
        if(self::$_instance == NULL || (self::$_instance->getUid() != $uid))
        {
            self::$_instance = new self($uid);
        }
        return self::$_instance;
    }
    
    public static function release()
    {
        if(self::$_instance != NULL)
        {
            self::$_instance = NULL;
        }
    }
    
    public function getRobInfo()
    {
        return $this->robInfo;
    }
    
    private function refreshRobInfo()
    {
        if(empty($this->robInfo) || 
                ($this->robInfo[RobTombDef::SQL_LAST_RFR_TIME] < RobTombLogic::getActStartTime()))
        {
            if(empty($this->robInfo))
            {
                Logger::info('this user %d enter this act firstly.init the robinfo.',$this->uid);
                $init = TRUE;
            }
            else
            {
                $init = FALSE;
                Logger::info('act open.start to reset robinfo of user %d.',$this->uid);
            }
            $this->robInfo[RobTombDef::SQL_FIELD_UID] = $this->getUid();
            $this->robInfo[RobTombDef::SQL_ACCUM_FREE_NUM] = 0;
            $this->robInfo[RobTombDef::SQL_ACCUM_GOLD_NUM] = 0;
            $this->robInfo[RobTombDef::SQL_LAST_RFR_TIME] = Util::getTime();
            $this->robInfo[RobTombDef::SQL_TODAY_FREE_NUM] = 0;
            $this->robInfo[RobTombDef::SQL_TODAY_GOLD_NUM] = 0;
            $this->robInfo[RobTombDef::SQL_VA_ROB_TOMB] = array();
            if($init)
            {
                RobTombDao::insertRobInfo($this->robInfo);
                $this->buffer = $this->robInfo;
            }
            return;
        }
        if(Util::isSameDay($this->robInfo[RobTombDef::SQL_LAST_RFR_TIME]) == FALSE)
        {
            Logger::trace('this user %d firstly enter this act.reset robinfo.',$this->uid);
            $this->robInfo[RobTombDef::SQL_LAST_RFR_TIME] = Util::getTime();
            $this->robInfo[RobTombDef::SQL_TODAY_FREE_NUM] = 0;
        }
    }
    
    public function robFree($num)
    {
        $this->robInfo[RobTombDef::SQL_ACCUM_FREE_NUM] += 1;
        $this->robInfo[RobTombDef::SQL_TODAY_FREE_NUM] += 1;
    }
    
    public function robGold($num)
    {
        $this->robInfo[RobTombDef::SQL_ACCUM_GOLD_NUM] += 1;
        $this->robInfo[RobTombDef::SQL_TODAY_GOLD_NUM] += 1;
    }
    
    public function getFreeRobNum()
    {
        return $this->robInfo[RobTombDef::SQL_TODAY_FREE_NUM];
    }
    
    public function getGoldRobNum()
    {
        return $this->robInfo[RobTombDef::SQL_TODAY_GOLD_NUM];
    }
    
    public function getAccumGoldRobNum()
    {
        return $this->robInfo[RobTombDef::SQL_ACCUM_GOLD_NUM];
    }
    
    public function addDropToBlackList($dropId,$num)
    {
        if(!isset($this->robInfo[RobTombDef::SQL_VA_ROB_TOMB][RobTombDef::SQL_VA_ROB_BLACKLIST][$dropId]))
        {
            $this->robInfo[RobTombDef::SQL_VA_ROB_TOMB][RobTombDef::SQL_VA_ROB_BLACKLIST][$dropId] = 0;
        }
        $this->robInfo[RobTombDef::SQL_VA_ROB_TOMB][RobTombDef::SQL_VA_ROB_BLACKLIST][$dropId] += $num;
    }
    
    public function getBlackList()
    {
        if(isset($this->robInfo[RobTombDef::SQL_VA_ROB_TOMB][RobTombDef::SQL_VA_ROB_BLACKLIST]))
        {
            return $this->robInfo[RobTombDef::SQL_VA_ROB_TOMB][RobTombDef::SQL_VA_ROB_BLACKLIST];
        }
        return array();
    }
    
    public function save()
    {
        if(!empty($this->robInfo) && ($this->robInfo != $this->buffer))
        {
            RobTombDao::updateRobInfo($this->uid, $this->robInfo);
            $this->buffer = $this->robInfo;
        }
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */