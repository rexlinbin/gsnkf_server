<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: OlympicRank.class.php 121587 2014-07-18 13:44:54Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/olympic/OlympicRank.class.php $
 * @author $Author: wuqilin $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-07-18 13:44:54 +0000 (Fri, 18 Jul 2014) $
 * @version $Revision: 121587 $
 * @brief 
 *  
 **/
class OlympicRank
{
    private $arrRankInfo = array();
    private $arrBuffer = array();
    private static $fetchAll = FALSE;
    /**
     * 
     * @var OlympicRank
     */
    private static $_instance = NULL;
    
    /**
     * @return OlympicRank
     */
    public static function getInstance()
    {
        if(self::$_instance == NULL)
        {
            self::$_instance = new OlympicRank();
        }
        return self::$_instance;
    }
    
    public static function release()
    {
        self::$fetchAll = FALSE;
        self::$_instance = NULL;
    }
    
    public function getInitInfo($signUpIndex)
    {
        $initInfo = array(
                OlympicRankDef::FIELD_FINAL_RANK => 0,
                OlympicRankDef::FIELD_OLYMPICINDEX => 0,
                OlympicRankDef::FIELD_SIGNUP_INDEX => $signUpIndex,
                OlympicRankDef::FIELD_UID => 0
                );
        return $initInfo;
    }
    
    /**
     * 报名的时候用
     * @param int $index
     */
    public function getInfoBySignUpIndex($signUpIndex)
    {
        if(isset($this->arrRankInfo[$signUpIndex])
                 && (!empty($this->arrRankInfo[$signUpIndex])))
        {
            return $this->arrRankInfo[$signUpIndex];
        }
        $info = OlympicDao::getOlympicRank($signUpIndex);
        if(empty($info))
        {
            $info = $this->getInitInfo($signUpIndex);
            OlympicDao::insertOlympicRank($info);
        }
        $this->fetchDbRfrBuffer(array($signUpIndex=>$info));
        return $info;
    }
    
    public function getInfoByUid($uid)
    {
        $info = OlympicDao::getRankInfoByUid($uid);
        if(empty($info))
        {
            return array();
        }
        $signUpIndex = $info[OlympicRankDef::FIELD_SIGNUP_INDEX];
        $this->fetchDbRfrBuffer(array($signUpIndex=>$info));
        return $info;
    }
    
    private function fetchDbRfrBuffer($arrRankInfo)
    {
        $this->arrRankInfo = $this->arrRankInfo + $arrRankInfo;
        $this->arrBuffer = $this->arrBuffer + $arrRankInfo;
    }
    
    /**
     * 报名结束之后拉取所有的玩家
     */
    public function getAllSignUpUser()
    {
        if(self::$fetchAll)
        {
            return $this->arrRankInfo;
        }
        $arrSignUpInfo = OlympicDao::getAllSignUpUser();
        $arrSignUpInfo = Util::arrayIndex($arrSignUpInfo, OlympicRankDef::FIELD_SIGNUP_INDEX);
        $this->fetchDbRfrBuffer($arrSignUpInfo);
        self::$fetchAll = TRUE;
        return $arrSignUpInfo;
    }
    
    /**
     * 给所有报名的玩家分组
     */
    public function reorderAllSignUpUser()
    {
        $arrSignUpInfo = $this->getAllSignUpUser();
        $arrTmpIndex = array();
        for($i=OlympicDef::MIN_SIGNUP_INDEX;$i<=OlympicDef::MAX_SIGNUP_INDEX;$i++)
        {
            $arrTmpIndex[] = $i;
        }
        shuffle($arrTmpIndex);
        foreach($arrTmpIndex as $index => $signUpIndex)
        {
            if(isset($arrSignUpInfo[$signUpIndex]))
            {
                $this->arrRankInfo[$signUpIndex][OlympicRankDef::FIELD_OLYMPICINDEX] = $index;
            }
        }
    }
    
    public function setRank($uid,$rank)
    {
        $find = FALSE;
        foreach($this->arrRankInfo as $signUpIndex => $rankInfo)
        {
            $rankUid = $rankInfo[OlympicRankDef::FIELD_UID];
            if($uid != $rankUid)
            {
                continue;
            }
            $find = TRUE;
            $this->arrRankInfo[$signUpIndex][OlympicRankDef::FIELD_FINAL_RANK] = $rank;
        }
        return $find;
    }
    
    public function signUp($uid,$index)
    {
        $this->getInfoBySignUpIndex($index);
        $this->arrRankInfo[$index][OlympicRankDef::FIELD_UID] = $uid;
        $this->arrRankInfo[$index][OlympicRankDef::FIELD_FINAL_RANK] = OlympicDef::RANK_32;
    }
    
    public function dailyReset($signUpIndex)
    {
        $this->arrRankInfo[$signUpIndex][OlympicRankDef::FIELD_UID] = 0;
        $this->arrRankInfo[$signUpIndex][OlympicRankDef::FIELD_FINAL_RANK] = 0;
        $this->arrRankInfo[$signUpIndex][OlympicRankDef::FIELD_OLYMPICINDEX] = 0;
    }
    
    public function save()
    {
        if($this->arrRankInfo == $this->arrBuffer)
        {
            return;
        }
        foreach($this->arrRankInfo as $signUpIndex => $rankInfo)
        {
            if(!isset($this->arrBuffer[$signUpIndex]))
            {
                throw new FakeException('buffer %s rankinfo %s',$this->arrBuffer,$this->arrRankInfo);
            }
            if($this->arrBuffer[$signUpIndex] == $rankInfo)
            {
                continue;
            }
            OlympicDao::saveRankInfo($rankInfo, $signUpIndex);
        }
        $this->arrBuffer = $this->arrRankInfo;
        self::$fetchAll = FALSE;
    }
     
    public static function resetDate()
    {
    	$arrValue = array(
    		OlympicRankDef::FIELD_UID => 0,
    		OlympicRankDef::FIELD_FINAL_RANK => 0,
    		OlympicRankDef::FIELD_OLYMPICINDEX => 0,
    	);
    	OlympicDao::setAllRank($arrValue);
    	self::release();
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */