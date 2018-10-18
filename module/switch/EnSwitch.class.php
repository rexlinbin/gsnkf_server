<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: EnSwitch.class.php 67286 2013-09-29 12:31:33Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/switch/EnSwitch.class.php $
 * @author $Author: TiantianZhang $(lanhongyu@babeltime.com)
 * @date $Date: 2013-09-29 12:31:33 +0000 (Sun, 29 Sep 2013) $
 * @version $Revision: 67286 $
 * @brief 
 *  
 **/

class EnSwitch
{
    
    
    /**
     * 保存switchObj
     * uid => SwitchObj
     * @var unknown_type
     */
    private static $arrSwitch=array();
    
    /**
     * @return SwitchObj
     */
    public static function getSwitchObj($uid = 0)
    {
        if ($uid == 0)
        {
            $uid = RPCContext::getInstance()->getUid();
            if ($uid == null)
            {
                throw new FakeException('uid and global.uid are 0');
            }
        }
        if (!isset(self::$arrSwitch[$uid]))
        {
            self::$arrSwitch[$uid] = new SwitchObj($uid);
        }
        return self::$arrSwitch[$uid];
    }
    
    /**
     * 每次用户登录系统     判断是否有新的功能接口开启
     */
	public static function checkSwitch()
	{
	    Logger::debug('EnSwitch.checkSwitch start.');
	    $conf    =    btstore_get()->SWITCH;
	    $uid    =    RPCContext::getInstance()->getUid();
	    if(empty($uid))
	    {
	        throw new FakeException('no use login');
	    }
	    $ret    =    SwitchLogic::checkOpenNewSwitch($uid);
	    self::getSwitchObj($uid)->save();
	    Logger::debug('EnSwitch.checkSwitch end.');
	    return $ret;
	}	
	/**
	 * user->update时update switch
	 */
	public static function checkSwitchOnLevelUp()
	{
	    Logger::debug('EnSwitch.checkSwitchOnLevelUp start.');
	    $uid = RPCContext::getInstance()->getUid();
	    $ret    =    SwitchLogic::checkOpenNewSwitchByLevel($uid);
	    Logger::debug('EnSwitch.checkSwitchOnLevelUp end.');
	    return $ret;
	}
	/**
	 * dobattle之后update switch
	 * @param unknown_type $baseId
	 */
	public static function checkSwitchOnDefeatBase($baseId)
	{
	    Logger::debug('EnSwitch.checkSwitchDefeatBase start.');
	    $uid = RPCContext::getInstance()->getUid();
	    $switchBases    =    btstore_get()->SWITCHBASE->toArray();
	    if(in_array($baseId, $switchBases) == FALSE)
	    {
	        return;
	    }
        $ret = SwitchLogic::checkOpenNewSwitchByBase($baseId,$uid);	 
        Logger::debug('EnSwitch.checkSwitchDefeatBase end.');
        return $ret;
	}
	
	public static function isSwitchOpen($module,$uid=0)
	{
	    $reflection     =    new ReflectionClass('SwitchDef');
	    $switches    =    $reflection->getConstants();
	    if(!in_array($module, $switches))
	    {
	        throw new FakeException('module %s is not in switch list.',$module);
	    }
	    if(self::getSwitchObj($uid)->isSwitchOpen($module) == TRUE)
	    {
	        return TRUE;
	    }
	    return FALSE;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */