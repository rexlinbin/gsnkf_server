<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MonthlyCardObj.class.php 240763 2016-04-29 10:58:56Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/discountcard/monthlycard/MonthlyCardObj.class.php $
 * @author $Author: MingTian $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-04-29 10:58:56 +0000 (Fri, 29 Apr 2016) $
 * @version $Revision: 240763 $
 * @brief 
 *  
 **/
class MonthlyCardObj extends DiscountCardObj
{
    /**
     * 
     * @var MonthlyCardObj
     */
    private static $_instance = NULL;
    private $rewardInfo = array();
    public static function getInstance($uid,$cardId)
    {
        if(self::$_instance !=NULL  && 
                (self::$_instance->getUid() == $uid) &&
                (self::$_instance->getCardId() == $cardId))
        {
            return self::$_instance;
        }
        self::$_instance = new self($uid,$cardId);
        self::$_instance->getUnclaimedReward();
        return self::$_instance;
    }
    
    public static function release()
    {
        self::$_instance = NULL;
    }
    
    /**
     * 领取今天之前未领取的奖励
     */
    public function getUnclaimedReward()
    {
        Logger::trace('getUnclaimedReward %s',$this->cardInfo);
        if(empty($this->cardInfo))
        {
            return;
        }
        $rewardTime = $this->getRewardTime();
        if($rewardTime < ($this->getBuyTime() - SECONDS_OF_DAY))
        {
            $rewardTime = $this->getBuyTime() - SECONDS_OF_DAY;
        }
        $dayBreak = strtotime(date( "Y-m-d",Util::getTime()));//今天凌晨
        $unGotNum = 0;
        $time = $this->getBuyTime();
        //将今天之前并且是在月卡到期之前没有领取的奖励发到奖励中心
        while($rewardTime+SECONDS_OF_DAY < $dayBreak && ($rewardTime < strtotime(date( "Y-m-d",$this->getDueTime()))))
        {
            $unGotNum++;
            $rewardTime = $rewardTime + SECONDS_OF_DAY;
            $this->rewardInfo[] = $rewardTime;
        }
        $this->setRewardTime($rewardTime);  
    }
    
    public function canBuyCard()
    {
        Logger::trace('buycard cardinfo is %s',$this->cardInfo);
        if(empty($this->cardInfo))
        {
            return TRUE;
        }
        $lastDueTime = $this->getDueTime();
        if($lastDueTime >= Util::getTime())
        {
            if($lastDueTime - Util::getTime() > MonthlyCardLogic::getLimitTime($this->cardId))
            {
                Logger::info('lastduetime %d cur is %d limit is %d.can not buy.',$lastDueTime,Util::getTime(),MonthlyCardLogic::getLimitTime($this->cardId));
                return FALSE;
            }
        }
        return TRUE;
    }
    
    public function buyCard($lastBuyTime = 0)
    {
        Logger::trace('buycard cardinfo is %s',$this->cardInfo);
        if(empty($this->cardInfo))
        {
            $this->cardInfo = $this->getInitInfo($lastBuyTime);
            return TRUE;
        }
        $lastDueTime = $this->getDueTime();
        $duration = MonthlyCardLogic::getDuration($this->cardId);
        if($lastDueTime < Util::getTime())
        {
            $newDueTime = MonthlyCardLogic::getDayBreak(Util::getTime())  + $duration * SECONDS_OF_DAY - 1;
            $this->setRewardTime(strtotime('yesterday',Util::getTime()));
        }
        else
        {
            if($lastDueTime - Util::getTime() > MonthlyCardLogic::getLimitTime($this->cardId))
            {
                Logger::warning('lastduetime %d cur is %d limit is %d.can not buy.',$lastDueTime,Util::getTime(),MonthlyCardLogic::getLimitTime($this->cardId));
                return FALSE;
            }
            $newDueTime = $lastDueTime + $duration * SECONDS_OF_DAY;
        }
        if(MonthlyCardLogic::inGiftTime() && 
                $this->getGiftStatus() == MONTHCARD_GIFTSTATUS::NOGIFT)
        {
            $this->setGiftStatus(MONTHCARD_GIFTSTATUS::HASGIFT);
        }
        else if(!empty($lastBuyTime) && !EnMergeServer::isMonthCardEffect($lastBuyTime) 
        		&& EnMergeServer::isMonthCardEffect()
    	|| empty($lastBuyTime) && EnMergeServer::isMonthCardEffect())
        {
            $this->setGiftStatus(MONTHCARD_GIFTSTATUS::HASGIFT);
        }
        
        $this->setDueTime($newDueTime);
        $this->setBuyTime(Util::getTime());
        return TRUE;
    }
    
    public function getGiftStatus()
    {
        if(empty($this->cardInfo))
        {
            return NULL;
        }  
        return $this->cardInfo[DiscountCardDef::TBL_SQLFIELD_VAINFO]
            [DiscountCardDef::TBL_SQLFIELD_SUBVA_MONTH]
            [DiscountCardDef::TBL_SQLFIELD_MONTH_GIFTSTATUS]; 
    }
    
    public function setGiftStatus($status)
    {
        if(empty($this->cardInfo))
        {
            return NULL;
        }
        $this->cardInfo[DiscountCardDef::TBL_SQLFIELD_VAINFO]
            [DiscountCardDef::TBL_SQLFIELD_SUBVA_MONTH]
            [DiscountCardDef::TBL_SQLFIELD_MONTH_GIFTSTATUS] = $status;
    }
    
    public function getRewardTime()
    {
        if(empty($this->cardInfo))
        {
            return NULL;
        }
        return $this->cardInfo[DiscountCardDef::TBL_SQLFIELD_VAINFO]
            [DiscountCardDef::TBL_SQLFIELD_SUBVA_MONTH]
            [DiscountCardDef::TBL_SQLFIELD_MONTH_REWARDTIME];
    }
    
    public function setRewardTime($time)
    {
        if(empty($this->cardInfo))
        {
            return FALSE;
        }
        $this->cardInfo[DiscountCardDef::TBL_SQLFIELD_VAINFO]
            [DiscountCardDef::TBL_SQLFIELD_SUBVA_MONTH]
            [DiscountCardDef::TBL_SQLFIELD_MONTH_REWARDTIME] = $time;
        return TRUE;
    }
    
    public function getDueTime()
    {
        if(empty($this->cardInfo))
        {
            return NULL;
        }
        return $this->cardInfo[DiscountCardDef::TBL_SQLFIELD_DUETIME];
    }
    
    public function setDueTime($time)
    {
        if(empty($this->cardInfo))
        {
            return NULL;
        }
        $this->cardInfo[DiscountCardDef::TBL_SQLFIELD_DUETIME] = $time;
    }
    
    /**
     * 只有买卡时才会调用此接口
     */
    public function getInitInfo($lastBuyTime = 0)
    {
        $inGiftTime = MonthlyCardLogic::inGiftTime();
        $duringMergeServer = empty($lastBuyTime) && EnMergeServer::isMonthCardEffect() ||
        !empty($lastBuyTime) && !EnMergeServer::isMonthCardEffect($lastBuyTime) && EnMergeServer::isMonthCardEffect();
        $inGiftTime = $inGiftTime || ($duringMergeServer);
        $dueTime = MonthlyCardLogic::getDayBreak(Util::getTime()) + SECONDS_OF_DAY * (MonthlyCardLogic::getDuration($this->cardId)) - 1;
        $cardInfo = array(
                DiscountCardDef::TBL_SQLFIELD_UID => $this->uid,
                DiscountCardDef::TBL_SQLFIELD_CARDID => $this->cardId,
                DiscountCardDef::TBL_SQLFIELD_BUYTIME => Util::getTime(),
                DiscountCardDef::TBL_SQLFIELD_DUETIME => $dueTime,
                DiscountCardDef::TBL_SQLFIELD_VAINFO => array(
                        DiscountCardDef::TBL_SQLFIELD_SUBVA_MONTH => array(
                                DiscountCardDef::TBL_SQLFIELD_MONTH_GIFTSTATUS => $inGiftTime?MONTHCARD_GIFTSTATUS::HASGIFT:MONTHCARD_GIFTSTATUS::NOGIFT,
                                DiscountCardDef::TBL_SQLFIELD_MONTH_REWARDTIME => strtotime('yesterday',Util::getTime()),
                                )
                        )
                );
        return $cardInfo;
    }
    
    public function save()
    {
        $guid = RPCContext::getInstance()->getUid();
        if($this->uid != $guid)
        {
            Logger::warning('update.but current user %d is not guid %d',$this->uid,$guid);
            return;
        }
        $buffer = $this->buffer;
        parent::save();
        //发放未领取的每日奖励
        foreach($this->rewardInfo as $rewardTime)
        {
            MonthlyCardLogic::sendRewardByCard($this->cardId, $this->uid, $rewardTime);
        }
        $rewardNum = count($this->rewardInfo);
        if($rewardNum > 0)
        {
            $lastRewardTime = empty($buffer)?0:$buffer[DiscountCardDef::TBL_SQLFIELD_VAINFO][DiscountCardDef::TBL_SQLFIELD_SUBVA_MONTH][DiscountCardDef::TBL_SQLFIELD_MONTH_REWARDTIME];
            Logger::info('reissue monthlycard daily reward.lastrewardtime is %d rewardnum is %d',
                    $lastRewardTime,$rewardNum);
        }
        $this->rewardInfo = array();
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */