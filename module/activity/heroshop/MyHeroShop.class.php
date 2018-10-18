<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MyHeroShop.class.php 100498 2014-04-16 02:24:42Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/heroshop/MyHeroShop.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-04-16 02:24:42 +0000 (Wed, 16 Apr 2014) $
 * @version $Revision: 100498 $
 * @brief 
 *  
 **/
class MyHeroShop
{
    private $uid = NULL;
    private $buffer = NULL;
    private $shopInfo = NULL;
    /**
     * 
     * @var MyHeroShop
     */
    private static $_instance = NULL;
    
    
    private function __construct($uid=0)
    {
        if(empty($uid))
        {
            $uid = RPCContext::getInstance()->getUid();
        }
        $this->uid = $uid;
        $allFiled = array(
                HeroShopDef::SQL_FIELD_UID,
                HeroShopDef::SQL_FIELD_SCORE,
                HeroShopDef::SQL_FIELD_SCORE_TIME,
                HeroShopDef::SQL_FIELD_FREE_CD,
                HeroShopDef::SQL_FIELD_FREE_NUM,
                HeroShopDef::SQL_FIELD_GOLD_BUY_NUM,
                HeroShopDef::SQL_FIELD_SPECIAL_BUY_NUM,
                HeroShopDef::SQL_FIELD_REWARD_TIME,
                );
        $shopInfo = HeroShopDao::getShopInfoByUid($uid, $allFiled);
        $this->shopInfo = $shopInfo;
        $this->buffer = $shopInfo;
        if(empty($this->shopInfo))
        {
            $this->shopInfo = $this->initShopInfo();
        }
        $this->shopInfo = $this->resetShopInfo($this->shopInfo);
        Logger::trace('shopinfo %s.',$this->shopInfo);
    }
    
    public function getUid()
    {
        return $this->uid;
    }
    
    /**
     * 
     * @param int $uid
     * @return MyHeroShop
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
    
    private function getInitInfo()
    {
        $shopInfo = array(
                HeroShopDef::SQL_FIELD_UID=>$this->uid,
                HeroShopDef::SQL_FIELD_SCORE=>0,
                HeroShopDef::SQL_FIELD_SCORE_TIME=>Util::getTime(),
                HeroShopDef::SQL_FIELD_FREE_CD=>0,
                HeroShopDef::SQL_FIELD_FREE_NUM=>HeroShopDef::INIT_FREE_BUY_NUM,
                HeroShopDef::SQL_FIELD_GOLD_BUY_NUM=>0,
                HeroShopDef::SQL_FIELD_SPECIAL_BUY_NUM=>0,
                HeroShopDef::SQL_FIELD_REWARD_TIME => 0
        );
        return $shopInfo;
    }
    
    private function resetShopInfo($shopInfo)
    {
        $startTime = HeroShopLogic::getActStartTime();
        if($shopInfo[HeroShopDef::SQL_FIELD_SCORE_TIME] < $startTime)
        {
            $shopInfo = $this->getInitInfo();
        }
        return $shopInfo;
    }
    
    private function initShopInfo()
    {
        $shopInfo = $this->getInitInfo();
        return $shopInfo;
    }
    
    public function getScore()
    {
        return $this->shopInfo[HeroShopDef::SQL_FIELD_SCORE];
    }
    
    public function addScore($num)
    {
        $this->shopInfo[HeroShopDef::SQL_FIELD_SCORE] += $num;
        $this->shopInfo[HeroShopDef::SQL_FIELD_SCORE_TIME] = Util::getTime();
    }
    
    public function save()
    {
        if($this->shopInfo != $this->buffer)
        {
            HeroShopDao::updateShopInfo($this->shopInfo);
            $this->buffer = $this->shopInfo;
            return TRUE;
        }
        return FALSE;
    }
    
    public function getShopInfo()
    {
        return $this->shopInfo;
    }
    
    public function getFreeNum()
    {
        return $this->shopInfo[HeroShopDef::SQL_FIELD_FREE_NUM];
    }
    
    public function getFreeCd()
    {
        return $this->shopInfo[HeroShopDef::SQL_FIELD_FREE_CD];
    }
    
    public function subFreeNum()
    {
        if($this->shopInfo[HeroShopDef::SQL_FIELD_FREE_NUM] < 1)
        {
            return FALSE;
        }
        $this->shopInfo[HeroShopDef::SQL_FIELD_FREE_NUM] -= 1;
        return TRUE;
    }
    
    public function resetFreeCd()
    {
        //修改free_cd
        $this->shopInfo[HeroShopDef::SQL_FIELD_FREE_CD] =
        Util::getTime()+HeroShopLogic::getConfFreeCd();
    }
    
    public function addFreeNum()
    {
        $this->shopInfo[HeroShopDef::SQL_FIELD_FREE_NUM] += 1;
    }
    
    public function getBuyNum()
    {
        return $this->shopInfo[HeroShopDef::SQL_FIELD_GOLD_BUY_NUM];
    }
    
    public function getSpecailBuyNum()
    {
        return $this->shopInfo[HeroShopDef::SQL_FIELD_SPECIAL_BUY_NUM];
    }
    
    public function addGoldBuyNum($num)
    {
        $this->shopInfo[HeroShopDef::SQL_FIELD_GOLD_BUY_NUM] += $num;
    }
    
    public function addSpecialBuyNum($num)
    {
        $this->shopInfo[HeroShopDef::SQL_FIELD_SPECIAL_BUY_NUM] += $num;
    }
    
    //console使用
    public function setFreeCd($time)
    {
        $this->shopInfo[HeroShopDef::SQL_FIELD_FREE_CD] = $time;
    }
    
    public function getReward()
    {
        $this->shopInfo[HeroShopDef::SQL_FIELD_REWARD_TIME] = Util::getTime();
    }
    
    public function getRewardTime()
    {
        return $this->shopInfo[HeroShopDef::SQL_FIELD_REWARD_TIME];
    }
    
    public function getScoreTime()
    {
        return $this->shopInfo[HeroShopDef::SQL_FIELD_SCORE_TIME];
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */