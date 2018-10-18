<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UserOlympic.class.php 123443 2014-07-28 16:18:54Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/olympic/UserOlympic.class.php $
 * @author $Author: wuqilin $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-07-28 16:18:54 +0000 (Mon, 28 Jul 2014) $
 * @version $Revision: 123443 $
 * @brief 
 *  
 **/
class UserOlympic
{
    private $uid = NULL;
    private $userOlympicInfo = NULL;
    private $buffer = NULL;
    private $isInit = false;
    /**
     * 积分记录
     * @var array
     */
    private $arrIntegralRecord = array();
    private static $arrInstance = NULL;
    
    public function __construct($uid,$info)
    {
        if(empty($uid))
        {
            throw new FakeException('empty uid.please provide valid uid');
        }
        if(empty($info))
        {
            $info = OlympicDao::getUserOlympicInfo($uid, UserOlympicDef::$ALL_FIELD);
        }
        $this->uid = $uid;
        if(empty($info))
        {
            $info = $this->getInitInfo();
            $this->isInit = true;
        }
        $this->userOlympicInfo = $info;
        $this->buffer = $info;
        $this->rfrInfo();
    }
    
    /**
     * 
     * @param int $uid
     * @param array $userInfo
     * @return UserOlympic
     */
    public static function getInstance($uid,$userInfo=array())
    {
        if(!isset(self::$arrInstance[$uid]))
        {
            self::$arrInstance[$uid] = new UserOlympic($uid,$userInfo);
        }
        return self::$arrInstance[$uid];
    }
    
    public static function release()
    {
        self::$arrInstance = NULL;
    }
    
    /**
     * 根据cheer_time清除cheer_uid
     * 根据weekly_rfr_time进行每周重置，重置cheer_num、cheer_valid_num、win_accum_num、va中的积分字段
     */
    public function rfrInfo()
    {
        if(OlympicLogic::canDailyRfrCheer(self::getCheerTime()))
        {
            $this->userOlympicInfo[UserOlympicDef::FIELD_CHEER_TIME] = Util::getTime();
            $this->userOlympicInfo[UserOlympicDef::FIELD_CHEERUID] = 0;
            $this->userOlympicInfo[UserOlympicDef::FIELD_BE_CHEER_NUM] = 0;
        }
        
        if(OlympicLogic::canWeeklyRfrData(self::getWeeklyRfrTime()))
        {
            $this->userOlympicInfo[UserOlympicDef::FIELD_CHEER_NUM] = 0;
            $this->userOlympicInfo[UserOlympicDef::FIELD_BE_CHEER_NUM] = 0;
            $this->userOlympicInfo[UserOlympicDef::FIELD_CHEER_VALID_NUM] = 0;
            $this->userOlympicInfo[UserOlympicDef::FIELD_WIN_ACCUMNUM] = 0;
            $this->userOlympicInfo[UserOlympicDef::FIELD_VA_OLYMPIC]
                    [UserOlympicDef::SUBFIELD_INTEGRALRECORD] = array();
        }
        
    }
    
    public function getInitInfo()
    {
        $userOlympicInfo = array(
                UserOlympicDef::FIELD_UID => $this->uid,
                UserOlympicDef::FIELD_CHEER_NUM => 0,
                UserOlympicDef::FIELD_BE_CHEER_NUM => 0,
                UserOlympicDef::FIELD_CHEER_TIME => 0,
                UserOlympicDef::FIELD_CHEER_VALID_NUM => 0,//助威成功次数
                UserOlympicDef::FIELD_CHEERUID => 0,
                UserOlympicDef::FIELD_INTEGRAL => 0,//积分
                UserOlympicDef::FIELD_WEEKLY_RFR_TIME => Util::getTime(),
                UserOlympicDef::FIELD_CHALLENGE_TIME => 0,
                UserOlympicDef::FIELD_CHALLENGE_CDTIME => 0,
                UserOlympicDef::FIELD_WIN_ACCUMNUM => 0,//连胜次数
                UserOlympicDef::FIELD_VA_OLYMPIC => 0,
                UserOlympicDef::FIELD_STATUS => DataDef::NORMAL,
                );
        return $userOlympicInfo;
    }
    
    public function getChallengeTime()
    {
        return $this->userOlympicInfo[UserOlympicDef::FIELD_CHALLENGE_TIME];
    }
    
    public function getChallengeCdTime()
    {
        return $this->userOlympicInfo[UserOlympicDef::FIELD_CHALLENGE_CDTIME];
    }
    
    public function getCheerTime()
    {
        return $this->userOlympicInfo[UserOlympicDef::FIELD_CHEER_TIME];
    }
    
    public function getWeeklyRfrTime()
    {
        return $this->userOlympicInfo[UserOlympicDef::FIELD_WEEKLY_RFR_TIME];
    }
    
    public function getCheerUid()
    {
        return $this->userOlympicInfo[UserOlympicDef::FIELD_CHEERUID];
    }
    
    public function cheer($cheerUid)
    {
        $this->userOlympicInfo[UserOlympicDef::FIELD_CHEERUID] = $cheerUid;
        $this->userOlympicInfo[UserOlympicDef::FIELD_CHEER_TIME] = Util::getTime();
        $this->userOlympicInfo[UserOlympicDef::FIELD_CHEER_NUM]++;
    }

    public function getBeCheer()
    {
        return $this->userOlympicInfo[UserOlympicDef::FIELD_BE_CHEER_NUM];
    }

    public function beCheer()
    {
        $this->userOlympicInfo[UserOlympicDef::FIELD_BE_CHEER_NUM]++;
    }
    
    public function challenge()
    {
        $this->userOlympicInfo[UserOlympicDef::FIELD_CHALLENGE_TIME] = Util::getTime();
        $this->userOlympicInfo[UserOlympicDef::FIELD_CHALLENGE_CDTIME] = OlympicLogic::getChallengeCdTime();
    }
    
    public function clearCd()
    {
        $this->userOlympicInfo[UserOlympicDef::FIELD_CHALLENGE_CDTIME] = 0;
    }
    
    public function addIntegral($getType,$integral)
    {
        if(in_array($getType, OlympicIntegralGetType::$ALL_TYPE) == FALSE)
        {
            throw new FakeException('gettype %d alltype %s',$getType,OlympicIntegralGetType::$ALL_TYPE);
        }
        $this->userOlympicInfo[UserOlympicDef::FIELD_INTEGRAL] += $integral;
        $dayBreak = OlympicLogic::getDayBreak(Util::getTime());
        $this->arrIntegralRecord[$dayBreak][$getType] = $integral;
    }
    
    public function addCheerValidNum($num)
    {
        $this->userOlympicInfo[UserOlympicDef::FIELD_CHEER_VALID_NUM] += $num;
    }
    
    public function addWinNum($num)
    {
        $this->userOlympicInfo[UserOlympicDef::FIELD_WIN_ACCUMNUM] += $num;
    }

    public function addBeCheerNum($num)
    {
        $this->userOlympicInfo[UserOlympicDef::FIELD_BE_CHEER_NUM] += $num;
    }

    public function signUp()
    {

    }
    
    public function update()
    {

        $initInfo = $this->getInitInfo();
        if( ($this->userOlympicInfo == $this->buffer) &&
                empty($this->arrIntegralRecord))
        {
            return;
        }
        
        $guid = RPCContext::getInstance()->getUid();
        if($guid != $this->uid)
        {
            $buffer = $this->buffer;
            if($this->buffer == NULL)
            {
                $buffer = $initInfo;
            }
            $modifyInfo = array();
            foreach($this->userOlympicInfo as $key => $value)
            {
                if($buffer[$key] != $value)
                {
                    if(in_array($key, UserOlympicDef::$ARRFIELD_OTHER_UPDATE_IGNORE))
                    {
                        continue;
                    }
                    if(in_array($key, UserOlympicDef::$ARRFIELD_OTHER_UPDATE_SET))
                    {

                    }
                    if(in_array($key, UserOlympicDef::$ARRFIELD_MODIFYBYOTHER_DELT))
                    {
                        $modifyInfo[$key] = $value - $buffer[$key];
                    }
                    else
                    {
                        throw new InterException('userolympic cant update field:%s in otherobj. org:%s, cur:%d',
                            $key, $value, $this->userOlympicInfo[$key]);
                    }
                }
            }
            RPCContext::getInstance()->executeTask($this->uid, 'olympic.modifyUserInfoByOther',
                     array($this->uid,$modifyInfo,$this->arrIntegralRecord));
        }
        else
        {
            $userOlympicInfo = $this->userOlympicInfo;
            if((!empty($this->arrIntegralRecord)))
            {
                foreach($this->arrIntegralRecord as $time => $arrTimeRecord)
                {
                    foreach($arrTimeRecord as $type => $integral)
                    {
                        $userOlympicInfo[UserOlympicDef::FIELD_VA_OLYMPIC]
                            [UserOlympicDef::SUBFIELD_INTEGRALRECORD][$time][$type] = $integral;
                    }
                }
            }
            if( $this->isInit )
            {
            	OlympicDao::insertUserOlympicInfo($userOlympicInfo);
            	$this->isInit = false;
            }
            else
            {
           		OlympicDao::saveUserOlympicInfo($this->uid,$userOlympicInfo);
            }
        }
        $this->buffer = $this->userOlympicInfo;
        $this->arrIntegralRecord = array();
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */