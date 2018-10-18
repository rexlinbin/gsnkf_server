<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ActPayBackObj.class.php 233022 2016-03-16 09:32:17Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/actpayback/ActPayBackObj.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2016-03-16 09:32:17 +0000 (Wed, 16 Mar 2016) $
 * @version $Revision: 233022 $
 * @brief 
 *  
 **/
class ActPayBackObj
{
    private $uid = NULL;
    private $data = NULL;
    private $dataModify = NULL;
    public static $_instance = NULL;
    
    public function __construct($uid = 0)
    {
        if ( empty( $uid ) )
        {
            $uid = RPCContext::getInstance()->getUid();
            
            if ( empty($uid) )
            {
                throw new FakeException('uid in session is null.');
            }
        }
        
        $this->uid = $uid;
        $this->dataModify = ActPayBackDao::getInfo($this->uid, ActPayBackDef::$ALL_SQL_FIELD);
        
        if ( empty($this->dataModify) )
        {
            $this->init();
        }
        
        $this->data = $this->dataModify;
        
        $this->refresh();
    }
    
    public static function getInstance($uid)
    {
        if ( empty($uid) )
        {
            $uid = RPCContext::getInstance()->getUid();
        }
        
        if ( !isset(self::$_instance[$uid]) )
        {
            self::$_instance[$uid] = new self($uid);
        }
        
        return self::$_instance[$uid];
    }
    
    public function init()
    {
        $this->dataModify = array(
            ActPayBackDef::SQL_UID => $this->uid,
            ActPayBackDef::SQL_REFRESH_TIME => Util::getTime(),
            ActPayBackDef::SQL_VA_DATA => array(
                'rewarded' => array(),
            ),
        );
        
        ActPayBackDao::insert($this->uid, $this->dataModify);
    }
    
    public function refresh()
    {
        $rfrtime = $this->dataModify[ActPayBackDef::SQL_REFRESH_TIME];
        
        $actStartTime = ActPayBackLogic::getActStartTime();
        if ($rfrtime < $actStartTime)
        {
            $this->dataModify[ActPayBackDef::SQL_REFRESH_TIME] = Util::getTime();
            $this->dataModify[ActPayBackDef::SQL_VA_DATA] = array( 'rewarded'=>array() );
        }
    }
    
    public function getUid()
    {
        return $this->uid;
    }
    
    public function getRfrTime()
    {
        return $this->dataModify[ActPayBackDef::SQL_REFRESH_TIME];
    }
    
    public function getRewarded()
    {
        return $this->dataModify[ActPayBackDef::SQL_VA_DATA]['rewarded'];
    }
    
    public function receiveRewards($arrRid)
    {
        //目前领奖记录这里只记录了奖励id以及对应的领奖时间，如果需要查看具体奖励内容依赖领奖时间去数据库取配置（不安全）
        if ( FALSE == EnActivity::isOpen(ActivityName::ACTPAYBACK) )
        {
            Logger::warning("wanna receive actpayback reward, but act is not open.");
            return ;
        }
        
        $actStartTime = ActPayBackLogic::getActStartTime();
        
        foreach ($arrRid as $rid)
        {
            if ( isset($this->dataModify[ActPayBackDef::SQL_VA_DATA]['reward'][$rid])
                && $this->dataModify[ActPayBackDef::SQL_VA_DATA]['reward'][$rid] >= $actStartTime)
            {
                throw new FakeException('rid:%d has been rewarded, arrRid:%s, actStart:%d, data:%s.',$rid,$arrRid, $actStartTime, $this->dataModify);
            }
            
            $this->dataModify[ActPayBackDef::SQL_VA_DATA]['rewarded'][$rid] = Util::getTime();
        }
    }
    
    public function update()
    {
        $arrNeedUpdate = array();
        
        foreach ($this->dataModify as $key => $value)
        {
            if ($this->data[$key] != $value)
            {
                $arrNeedUpdate[$key] = $value;
            }
        }
        
        if ( empty( $arrNeedUpdate) )
        {
            Logger::debug('act pay back. nothing need update.');
            return ;
        }
        
        ActPayBackDao::update($this->uid, $arrNeedUpdate);
        
        $this->data = $this->dataModify;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */