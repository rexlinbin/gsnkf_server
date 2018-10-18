<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SwitchObj.class.php 122918 2014-07-25 06:22:05Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/switch/SwitchObj.class.php $
 * @author $Author: wuqilin $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-07-25 06:22:05 +0000 (Fri, 25 Jul 2014) $
 * @version $Revision: 122918 $
 * @brief 
 *  
 **/
class SwitchObj
{
    const WIDTH = 25;
    private $switch = array();
    private $modifySwitch = array();
    private $uid;
    private $arrNew = array();
    
    public function __construct ($uid)
    {
        //如果玩家自己的连接里，尝试从session里面获取数据
        if ($uid == RPCContext::getInstance()->getSession(UserDef::SESSION_KEY_UID))
        {
            $this->switch = RPCContext::getInstance ()->getSession(SwitchSession::SWITCHSESSION);
            Logger::trace('new SwitchObj from session:%s', $this->switch);
        }
        if ( empty($this->switch) )
        {
            $switchInfo = SwitchDao::get($uid);
            $this->switch = $switchInfo;
            if ($uid == RPCContext::getInstance()->getUid())
            {
                RPCContext::getInstance ()->setSession(SwitchSession::SWITCHSESSION, $switchInfo);
            }
        }
        $this->modifySwitch = $this->switch;
        if(empty($this->modifySwitch))
        {
            $this->modifySwitch = array(
                    TblSwitchField::UID=>$uid,
                    TblSwitchField::GROUP0=>0,
                    TblSwitchField::GROUP1=>0,
                    TblSwitchField::GROUP2=>0
                    );
        }
        $this->uid = $uid;
    }
    
    public function getSwitchInfo()
    {
        return $this->modifySwitch;
    }
    
    public function addNewSwitch($moduleIndex)
    {
    	if( $this->isSwitchOpen($moduleIndex)   )
    	{
    		Logger::debug('%d is opened, ignore', $moduleIndex);
    		return;
    	}
        $group    =    self::getModuleGroup($moduleIndex);
        $index    =    self::getModuleIndexInGroup($moduleIndex);
        $this->modifySwitch['group'.$group]+=(1<<$index);
        if(in_array($moduleIndex, $this->arrNew))
        {
            return;
        }
        $this->arrNew[] = $moduleIndex;
    }
    
    public function save()
    {
        if($this->modifySwitch == $this->switch)
        {
            Logger::trace('no change');
            return;
        }
        $this->switch = $this->modifySwitch;
        SwitchDao::update($this->uid, $this->modifySwitch);
        if ($this->uid == RPCContext::getInstance()->getUid())
        {
            RPCContext::getInstance()->setSession(SwitchSession::SWITCHSESSION, $this->modifySwitch);
            if(!empty($this->arrNew))
            {
                foreach($this->arrNew as $index => $switchId)
                {
                    RPCContext::getInstance()->sendMsg(array($this->uid),
                            PushInterfaceDef::SWITCH_ADD_NEW_SWITCH,
                            array('newSwitchId'=>$switchId));
                }
            }
        }
        
    }
    

    public function isSwitchOpen($module)
    {
        $switchInfo =  $this->getSwitchInfo();
        $group    =    self::getModuleGroup($module);
        $index    =    self::getModuleIndexInGroup($module);
        $data    =    $switchInfo['group'.$group];
        $cmp    =    (1<<$index);
        if(($data & $cmp) == $cmp)
        {
            return TRUE;
        }
        return FALSE;
    }
    
    /**
     *
     * @param int $module
     * @return int 0,1   (1-30返回0,31---返回1)
     */
    public static function getModuleGroup($module)
    {
        return intval(($module-1)/self::WIDTH);
    }
    /**
     *
     * @param int $module
     * @return number   0-29
     */
    public static function getModuleIndexInGroup($module)
    {
        return intval(($module-1)%self::WIDTH);
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */