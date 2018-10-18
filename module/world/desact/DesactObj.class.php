<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DesactObj.class.php 203457 2015-10-20 11:31:03Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/desact/DesactObj.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-10-20 11:31:03 +0000 (Tue, 20 Oct 2015) $
 * @version $Revision: 203457 $
 * @brief 
 *  
 **/
class DesactObj
{
    private static $_instance = NULL;
    
    private $data = NULL;
    private $dataModify = NULL;
    
    private $uid = NULL;
    private $DesactConfObj = NULL;
    
    public function __construct($uid)
    {
        $guid = RPCContext::getInstance()->getUid();
        
        if ( empty($guid) || $uid != $guid)
        {
            throw new InterException('invalid uid:%s, guid:%s.',$uid,$guid);
        }
        
        $this->uid = $uid;
        
        $this->dataModify = RPCContext::getInstance()->getSession(DesactDef::SESS_KEY_DESACT_INFO);
        
        if (empty($this->dataModify))
        {
            $this->dataModify = DesactDao::getDesactUser($this->uid, DesactDef::$ARR_INNER_DESACT_FIELDS);
            
            if (empty($this->dataModify))
            {
                $this->dataModify = $this->init();
            }
            
            RPCContext::getInstance()->setSession(DesactDef::SESS_KEY_DESACT_INFO, $this->dataModify);
        }
        
        $this->data = $this->dataModify;
        $this->refresh();
    }
    
    public static function getInstance($uid)
    {
        if (!isset(self::$_instance[$uid]))
        {
            self::$_instance[$uid] = new self($uid);
        }
        
        return self::$_instance[$uid];
    }
    
    public static function releaseInstance($uid)
    {
        if (isset(self::$_instance[$uid]))
        {
            unset(self::$_instance[$uid]);
        }
    }
    
    public function init()
    {
        $initInfo = array(
            DesactDef::SQL_UID => $this->uid,
            DesactDef::SQL_UPDATE_TIME => Util::getTime(),
            DesactDef::SQL_VA_DATA => array('taskInfo' => array()),
        );
        
        DesactDao::insertDesactInfo($initInfo);
        
        return $initInfo;
    }
    
    public function refresh()
    {
        $curTidAndUdtTime = DesactLogic::getCurTidAndUdtTime();
        $confUpdateTime = $curTidAndUdtTime['update_time'];
        
        if ($this->dataModify['update_time'] < $confUpdateTime)
        {
            $this->dataModify['update_time'] = Util::getTime();
            $this->dataModify['va_data'] = array('taskInfo' => array());
        }
    }
    
    public function getUid()
    {
        return $this->uid;
    }
    
    public function getInfo()
    {
        return $this->dataModify[DesactDef::SQL_VA_DATA]['taskInfo'];
    }
    
    public function doTask($tid, $num)
    {
        if (empty($this->dataModify[DesactDef::SQL_VA_DATA]['taskInfo'][$tid]['num']))
        {
            $this->dataModify[DesactDef::SQL_VA_DATA]['taskInfo'][$tid]['num'] = 0;
        }
        
        $this->dataModify[DesactDef::SQL_VA_DATA]['taskInfo'][$tid]['num'] += $num;
    }
    
    public function gainReward($tid, $rid)
    {
        if (empty($this->dataModify[DesactDef::SQL_VA_DATA]['taskInfo'][$tid]['rewarded']))
        {
            $this->dataModify[DesactDef::SQL_VA_DATA]['taskInfo'][$tid]['rewarded'] = array();
        }
        
        $this->dataModify[DesactDef::SQL_VA_DATA]['taskInfo'][$tid]['rewarded'][$rid] = Util::getTime();
    }
    
    public function update()
    {
        if ($this->data == $this->dataModify)
        {
            Logger::warning('nothing change.');
            return ;
        }
    
        $updateArr = array();
    
        foreach ($this->dataModify as $key => $value)
        {
            if ($value != $this->data[$key])
            {
                $updateArr[$key] = $value;
            }
        }
    
        if (empty($updateArr))
        {
            Logger::warning('nothing change.what is wrong? data:%s.dataModify:%s.',$this->data,$this->dataModify);
            return ;
        }
    
        DesactDao::updateDesact($this->uid, $updateArr);
        RPCContext::getInstance()->setSession(DesactDef::SESS_KEY_DESACT_INFO, $this->dataModify);
        $this->data = $this->dataModify;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */