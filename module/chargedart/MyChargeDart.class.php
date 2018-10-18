<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MyChargeDart.class.php 241166 2016-05-05 10:35:37Z ShuoLiu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chargedart/MyChargeDart.class.php $
 * @author $Author: ShuoLiu $(hoping@babeltime.com)
 * @date $Date: 2016-05-05 10:35:37 +0000 (Thu, 05 May 2016) $
 * @version $Revision: 241166 $
 * @brief 
 *  
 **/

class MyChargeDart
{
    protected $uid = 0;
    protected $userData = array();
    protected $userModify = array();
    
    private static $sArrInstance = array();
    
    /**
     * getInstance 获取用户实例
     *
     * @param int $uid 用户id
     * @static
     * @access public
     * @return MyChargeDart
     */
    public static function getInstance($uid = 0)
    {
        if ($uid == 0)
        {
            $uid = RPCContext::getInstance()->getUid();
            if ($uid == null)
            {
                throw new FakeException('uid and global.uid are 0');
            }
        }
    
        if (!isset(self::$sArrInstance[$uid]))
        {
            self::$sArrInstance[$uid] = new MyChargeDart($uid);
        }
    
        return self::$sArrInstance[$uid];
    }
    
    /**
     * 释放实例
     *
     * @param int $uid
     * @throws FakeException
     */
    public static function releaseInstance($uid)
    {
        if ($uid == 0)
        {
            $uid = RPCContext::getInstance()->getUid();
            if ($uid == null)
            {
                throw new FakeException('uid and global.uid are 0');
            }
        }
    
        if (isset(self::$sArrInstance[$uid]))
        {
            unset(self::$sArrInstance[$uid]);
        }
    }
    
    
    public function __construct($uid = 0)
    {
        if(empty($uid))
        {
            $uid = RPCContext::getInstance()->getUid();
        }
        
        $this->uid = $uid;
        $this->loadUserInfo($uid);

        //隔天检查
        $cmp_time = $this->userModify[ChargeDartDef::SQL_CMP_TIME];
        if ($cmp_time > 0 && !Util::isSameDay($cmp_time))
        {
            $this->changeByCrossDay();
            $this->save();
        }
        
        Logger::debug("userinfo is %s.",$this->userModify);
    }
    
    public function loadUserInfo($uid)
    {
        if ( $this->userData != null)
        {
            Logger::trace('aready loaduserinfo');
            return;
        }
        
        $userData = self::getUserInfo($uid);
        
        if (empty($userData))
        {
            $this->userModify = self::getInitUserInfo($uid);
            $arrFeild = $this->userModify;
            $arrFeild[ChargeDartDef::SQL_UID] = $this->uid;
            ChargeDartDao::insertOrchangeUserInfo($arrFeild);
        }
        else{
            $this->userModify = $userData;
        }
        
        $this->userData = $this->userModify;
    }
    
    public function getCmpTime()
    {
        return $this->userModify[ChargeDartDef::SQL_CMP_TIME];
    }
    
    public function getAllInfo()
    {
        return $this->userModify;
    }
    
    public function addShipNum()
    {
        $this->userModify[ChargeDartDef::SQL_SHIPPING_NUM]++;
    }
    
    public function addBuyShipNum($num)
    {
        $this->userModify[ChargeDartDef::SQL_BUY_SHIPPING_NUM] += $num;
    }
    
    public function addRobNum()
    {
        $this->userModify[ChargeDartDef::SQL_ROB_NUM]++;
    }
    
    public function addBuyRobNum($num)
    {
        $this->userModify[ChargeDartDef::SQL_BUY_ROB_NUM] += $num;
    }
    
    public function addAssistNum()
    {
        $this->userModify[ChargeDartDef::SQL_ASSISTANCE_NUM]++;
    }
    
    public function addBuyAssistNum($num)
    {
        $this->userModify[ChargeDartDef::SQL_BUY_ASSISTANCE_NUM] += $num;
    }
    
    public function addRereshNum()
    {
        $this->userModify[ChargeDartDef::SQL_REFRESH_NUM]++;
    }
    
    public function changeStage($stage_id)
    {
        $this->userModify[ChargeDartDef::SQL_STAGE_ID] = $stage_id;
    }
    
    public function addDarkCheck()
    {
        $this->userModify[ChargeDartDef::SQL_STAGE_REFRESH_NUM] ++;
    }
    
    public function clearDarkCheck()
    {
        $this->userModify[ChargeDartDef::SQL_STAGE_REFRESH_NUM] = 0;
    }
    
    public function inviteSomeOne($flag)
    {
        $this->userModify[ChargeDartDef::SQL_HAS_INVITED] = $flag;
    }
    
    public function getInviteSomeOneFlag()
    {
        return $this->userModify[ChargeDartDef::SQL_HAS_INVITED];
    }
    
    public function setAssistUid($uid)
    {
        $this->userModify[ChargeDartDef::SQL_ASSISTANCE_UID] = $uid;
    }
    
    public function beginChargeDart($pageId, $roadId, $tid, $time = 0)
    {
        $this->userModify[ChargeDartDef::SQL_STAGE_ID] = 
            ($this->userModify[ChargeDartDef::SQL_STAGE_ID]==0)?1:$this->userModify[ChargeDartDef::SQL_STAGE_ID];
        $this->userModify[ChargeDartDef::SQL_BEGIN_TIME] = ($time == 0)?Util::getTime():$time;
        $this->userModify[ChargeDartDef::SQL_PAGE_ID] = $pageId;
        $this->userModify[ChargeDartDef::SQL_ROAD_ID] = $roadId;
        $this->userModify[ChargeDartDef::SQL_BE_ROBBED_NUM] = 0;
        //$this->userModify[ChargeDartDef::SQL_USER_HAVE_RAGE] = 0;
        //$this->userModify[ChargeDartDef::SQL_ASSISTANCE_HAVE_RAGE] = 0;
        $this->userModify[ChargeDartDef::SQL_TID] = $tid;
    }
    
    public function clearChargeDartInfo()
    {
        $this->userModify[ChargeDartDef::SQL_BEGIN_TIME] = 0;
        $this->userModify[ChargeDartDef::SQL_PAGE_ID] = 0;
        $this->userModify[ChargeDartDef::SQL_ROAD_ID] = 0;
        $this->userModify[ChargeDartDef::SQL_BE_ROBBED_NUM] = 0;
        $this->userModify[ChargeDartDef::SQL_USER_HAVE_RAGE] = 0;
        $this->userModify[ChargeDartDef::SQL_ASSISTANCE_HAVE_RAGE] = 0;
        $this->userModify[ChargeDartDef::SQL_TID] = 0;
        $this->userModify[ChargedartDef::SQL_STAGE_REFRESH_NUM] = 0;
        
        $this->userModify[ChargeDartDef::SQL_ASSISTANCE_UID] = 0;
        $this->userModify[ChargeDartDef::SQL_STAGE_ID] = 0;
        $this->userModify[ChargeDartDef::SQL_HAS_INVITED] = 0;
        
        $this->userModify[ChargeDartDef::SQL_REFRESH_NUM] = 0;
    }
    
    public function addBeRobbedNum()
    {
        $this->userModify[ChargeDartDef::SQL_BE_ROBBED_NUM]++;
    }
    
    public function openUserRage()
    {
        $this->userModify[ChargeDartDef::SQL_USER_HAVE_RAGE] = 1;
    }
    
    public function openAssistRage()
    {
        $this->userModify[ChargeDartDef::SQL_ASSISTANCE_HAVE_RAGE] = 1;
    }
    
    public function userHaveRage()
    {
        return $this->userModify[ChargeDartDef::SQL_USER_HAVE_RAGE];
    }
    
    public function assistHaveRage()
    {
        return $this->userModify[ChargeDartDef::SQL_ASSISTANCE_HAVE_RAGE];
    }
    
    public function getChargeDartInfo($time = 0)
    {
        if($time == 0)
        {
            $time = Util::getTime();
        }
        
        $last_time = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_LAST_TIME];
        
        $ret = array();
        
        if($this->userModify[ChargeDartDef::SQL_BEGIN_TIME] + $last_time < $time)
        {
            return $ret;
        }
        
        return array(
            ChargeDartDef::SQL_STAGE_ID=>$this->userModify[ChargeDartDef::SQL_STAGE_ID],
            ChargeDartDef::SQL_PAGE_ID=>$this->userModify[ChargeDartDef::SQL_PAGE_ID],
            ChargeDartDef::SQL_BEGIN_TIME=>$this->userModify[ChargeDartDef::SQL_BEGIN_TIME],
            ChargeDartDef::SQL_BE_ROBBED_NUM=>$this->userModify[ChargeDartDef::SQL_BE_ROBBED_NUM],
            ChargeDartDef::SQL_ASSISTANCE_UID=>$this->userModify[ChargeDartDef::SQL_ASSISTANCE_UID],
        );
    }
    
    
    public function save()
    {
        //只更新变化的字段
        $arrFeild = array();
        foreach ($this->userData as $key => $value)
        {
            if($value != $this->userModify[$key])
            {
                $arrFeild[$key] = $this->userModify[$key];
            }
        }
        
        if (!empty($arrFeild))
        {
            $arrRet = ChargeDartDao::changeUserInfo($this->uid, $arrFeild);
            $this->userData = $this->userModify;
        }
    }
    
    private function getUserInfo($uid)
    {
        $selectFeild = array(
            ChargeDartDef::SQL_CMP_TIME,
            ChargeDartDef::SQL_SHIPPING_NUM,
            ChargeDartDef::SQL_BUY_SHIPPING_NUM,
            ChargeDartDef::SQL_ROB_NUM,
            ChargeDartDef::SQL_BUY_ROB_NUM,
            ChargeDartDef::SQL_ASSISTANCE_NUM,
            ChargeDartDef::SQL_BUY_ASSISTANCE_NUM,
            ChargeDartDef::SQL_REFRESH_NUM,
            ChargeDartDef::SQL_STAGE_ID,
            ChargeDartDef::SQL_STAGE_REFRESH_NUM,
            ChargeDartDef::SQL_HAS_INVITED,
            ChargeDartDef::SQL_ASSISTANCE_UID,
            ChargeDartDef::SQL_BEGIN_TIME,
            ChargeDartDef::SQL_PAGE_ID,
            ChargeDartDef::SQL_ROAD_ID,
            ChargeDartDef::SQL_BE_ROBBED_NUM,
            ChargeDartDef::SQL_USER_HAVE_RAGE,
            ChargeDartDef::SQL_ASSISTANCE_HAVE_RAGE,
            ChargeDartDef::SQL_TID,
        );
        
        $userinfo = ChargeDartDao::getUserInfoByUid($uid, $selectFeild);
        return $userinfo;
    }
    
    private function getInitUserInfo($uid)
    {
        $arrRet = array(
            ChargeDartDef::SQL_CMP_TIME => Util::getTime(),
            ChargeDartDef::SQL_SHIPPING_NUM => 0,
            ChargeDartDef::SQL_BUY_SHIPPING_NUM => 0,
            ChargeDartDef::SQL_ROB_NUM => 0,
            ChargeDartDef::SQL_BUY_ROB_NUM => 0,
            ChargeDartDef::SQL_ASSISTANCE_NUM => 0,
            ChargeDartDef::SQL_BUY_ASSISTANCE_NUM => 0,
            ChargeDartDef::SQL_REFRESH_NUM => 0,
            ChargeDartDef::SQL_STAGE_ID => 0,
            ChargeDartDef::SQL_STAGE_REFRESH_NUM => 0,
            ChargeDartDef::SQL_HAS_INVITED => 0,
            ChargeDartDef::SQL_ASSISTANCE_UID => 0,
            ChargeDartDef::SQL_BEGIN_TIME => 0,
            ChargeDartDef::SQL_PAGE_ID => 0,
            ChargeDartDef::SQL_ROAD_ID => 0,
            ChargeDartDef::SQL_BE_ROBBED_NUM => 0,
            ChargeDartDef::SQL_USER_HAVE_RAGE => 0,
            ChargeDartDef::SQL_ASSISTANCE_HAVE_RAGE => 0,
            ChargeDartDef::SQL_TID => 0,
        );
        
        return $arrRet;
    }
    
    public function changeByCrossDay()
    {
        $this->userModify[ChargeDartDef::SQL_CMP_TIME] = Util::getTime();
        $this->userModify[ChargeDartDef::SQL_SHIPPING_NUM] = 0;
        $this->userModify[ChargeDartDef::SQL_BUY_SHIPPING_NUM] = 0;
        $this->userModify[ChargeDartDef::SQL_ROB_NUM] = 0;
        $this->userModify[ChargeDartDef::SQL_BUY_ROB_NUM] = 0;
        $this->userModify[ChargeDartDef::SQL_ASSISTANCE_NUM] = 0;
        $this->userModify[ChargeDartDef::SQL_BUY_ASSISTANCE_NUM] = 0;
        //$this->userModify[ChargeDartDef::SQL_REFRESH_NUM] = 0;
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */