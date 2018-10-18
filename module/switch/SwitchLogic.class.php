<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: SwitchLogic.class.php 67349 2013-09-30 06:42:51Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/switch/SwitchLogic.class.php $
 * @author $Author: TiantianZhang $(lanhongyu@babeltime.com)
 * @date $Date: 2013-09-30 06:42:51 +0000 (Mon, 30 Sep 2013) $
 * @version $Revision: 67349 $
 * @brief 
 *  
 **/
class SwitchLogic
{
	private static function getSwitchInfo($uid = 0)
	{
	    if(empty($uid))
	    {
	        RPCContext::getInstance()->getUid();
	    }
	    $switchObj = EnSwitch::getSwitchObj($uid);
	    return $switchObj->getSwitchInfo();
	}
		
	
	public static function getSwitchArray($uid = 0)
	{
	    if(empty($uid))
	    {
	        RPCContext::getInstance()->getUid();
	    }
	    $reflection     =    new ReflectionClass('SwitchDef');
	    $switches    =    $reflection->getConstants();
	    $ret    =    array();
	    foreach($switches as $switchName => $switchId)
	    {
	        if(EnSwitch::getSwitchObj($uid)->isSwitchOpen($switchId) == TRUE)
	        {
	            $ret[] = $switchId;
	        }
	    }
	    return $ret;
	}
	/**
	 * 约定：switch.csv表中定义的功能节点是顺序开启的
	 */
	public static function checkOpenNewSwitch($uid=0)
	{
	    Logger::trace('checkOpenNewSwitch start');
	    if(empty($uid))
	    {
	        $uid = RPCContext::getInstance()->getUid();
	    }
	    if($uid != RPCContext::getInstance()->getUid())
	    {
	        return self::getSwitchInfo($uid);
	    }
	    $conf    =    btstore_get()->SWITCH->toArray();
	    $level    =    Enuser::getUserObj()->getLevel();
	    $baseInfo    =    self::getBasePassedInfo();
	    foreach($conf as $switchId=>$switchConf)
	    {
	        if(EnSwitch::getSwitchObj($uid)->isSwitchOpen($switchId) == true)
	        {
	            continue;
	        }
	        $needLv    =    $switchConf[BtTblSwitchField::OPENLV];
	        if($level < $needLv)
	        {
	            continue;
	        }
	        $needBase    =    $switchConf[BtTblSwitchField::OPEN_NEED_BASE];
	        //不能开启新的功能节点
	        if(!empty($needBase) && 
	                (!isset($baseInfo[$needBase]) || ( $baseInfo[$needBase] === 0)))
	        {
	            continue;
	        }
	        //开启新的功能节点
	        self::addNewSwitch($switchId);
	    }
	    return self::getSwitchInfo($uid);
	}
	
	private static function getBasePassedInfo()
	{
	    $switchInfo    =    self::getSwitchInfo();
	    $conf    =    btstore_get()->SWITCH->toArray();
	    $baseInfo    =    array();
	    $baseNotPassed    =    array();
	    $relatedCopies    =    array();
	    foreach($conf as $switchId=>$switchConf)
	    {
	        $baseId    =    $switchConf[BtTblSwitchField::OPEN_NEED_BASE];
	        if(empty($baseId))
	        {
	            continue;
	        }
	        $baseNotPassed[] = $baseId;
	        if(EnSwitch::getSwitchObj()->isSwitchOpen($switchId) == TRUE)
	        {
	            $baseInfo[$baseId]    =    1;
	        }
	    }
	    $basePassed    =    CopyUtil::isArrBasePassed($baseNotPassed);
	    $basePassed    =    $basePassed + $baseInfo;
	    return $basePassed;
	}

	public static function checkOpenNewSwitchByLevel($uid)
	{
	    if($uid != RPCContext::getInstance()->getUid())
	    {
	        return self::getSwitchInfo($uid);
	    }
        self::checkOpenNewSwitch($uid);	
        return self::getSwitchInfo($uid);
	}
	
	public static function checkOpenNewSwitchByBase($baseId,$uid)
	{
	    if($uid != RPCContext::getInstance()->getUid())
	    {
	        return self::getSwitchInfo($uid);
	    }
	    $user    =    Enuser::getUserObj();
        $conf    =    btstore_get()->SWITCH->toArray();
        $newSwitch    =    array();
        foreach($conf as $switchId => $switchConf)
        {
            if($switchConf[BtTblSwitchField::OPEN_NEED_BASE] == $baseId)
            {
                $module    =    $switchId;
                if(EnSwitch::getSwitchObj()->isSwitchOpen($module) == TRUE)
                {
                    continue;
                }
                $needLv    =    $switchConf[BtTblSwitchField::OPENLV];
                if($user->getLevel() < $needLv)
                {
                    continue;
                }
                $newSwitch[]    =    $module;
            }
        }
        foreach($newSwitch as $moduleIndex)
        {
            self::addNewSwitch($moduleIndex);
        }
        return EnSwitch::getSwitchObj()->getSwitchInfo();
	}
	
	private static function addNewSwitch($moduleIndex)
	{
	    $reflection     =    new ReflectionClass('SwitchDef');
	    $switches    =    $reflection->getConstants();
	    if(!in_array($moduleIndex, $switches))
	    {
	        throw new InterException('module %s is not in switch list.',$moduleIndex);
	    }
	    if(EnSwitch::getSwitchObj()->isSwitchOpen($moduleIndex) == TRUE)
	    {
	        return;
	    }
	    //开启新的功能
	    EnSwitch::getSwitchObj()->addNewSwitch($moduleIndex);
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */