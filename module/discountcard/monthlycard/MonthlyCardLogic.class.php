<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MonthlyCardLogic.class.php 259698 2016-08-31 08:07:55Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/discountcard/monthlycard/MonthlyCardLogic.class.php $
 * @author $Author: BaoguoMeng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-31 08:07:55 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259698 $
 * @brief 
 *  
 **/
class MonthlyCardLogic
{
    public static function getCardInfo ($uid)
    {
    	$ret = array();
    	$giftStatus = MONTHCARD_GIFTSTATUS::NOGIFT;
    	foreach (DiscountCardDef::$VAILD_MONTHLYCATD_IDS as $cardId)
    	{
    		$inst = MonthlyCardObj::getInstance($uid, $cardId);
    		$ret[$cardId] = $inst->getCardInfo();
    		$inst->save();
    		$ret[$cardId]['charge_gold'] = self::getChargeGoldNumForCard($uid, $cardId);
    		if ($giftStatus < $inst->getGiftStatus()) 
    		{
    			$giftStatus = $inst->getGiftStatus();
    		}
    	}
    	$ret[$cardId+1][DiscountCardDef::TBL_SQLFIELD_MONTH_GIFTSTATUS] = $giftStatus;
        
        return $ret;
    }
    
    public static function buyCard ($uid, $orderId, $type, $itemTplId, $itemNum, $goldNum)
    {
        $cardId = DiscountCardDef::MONTHLYCATD_ID;
        $inst = MonthlyCardObj::getInstance($uid, $cardId);
        if($inst->buyCard() == FALSE)
        {
            throw new FakeException('buy card failed.');
        }
        $batchData = new BatchData();
        $cardData = $batchData->newData();
        $cardInfo = $inst->getCardInfo();
        $cardData->insertOrUpdate(DiscountCardDef::DISCOUNTCARD_TBLNAME)
                 ->values($cardInfo)
                 ->query();
        
        $orderData = $batchData->newData();
        $orderInfo = array(
                'uid'=>$uid,
                'order_id'=>$orderId,
                'item_type'=>$type,
                'item_tpl_id'=>$cardId,
                'item_num'=>$itemNum,
                'gold_num' => $goldNum,
                'mtime' =>Util::getTime(),
                'status'=>1
                );
        $orderData->insertInto(User4BBpayDao::tblBBpayItem)
                  ->values($orderInfo)
                  ->query();
        
        $userData = $batchData->newData(); 
        $goldBack = $goldNum;
        if ($goldBack>0)
        {
            $opGold = new IncOperator($goldBack);
        }
        else
        {
            $opGold = new DecOperator($goldBack);
        }
        
        $sumGold = User4BBpayDao::getSumGoldByUid($uid);
        $sumGold += $goldNum;
        $userObj = EnUser::getUserObj($uid);
        $oldVip = $userObj->getVip();
        $newVip = 0;
        foreach (btstore_get()->VIP as $vipInfo)
        {
            if ($vipInfo['totalRecharge'] > $sumGold)
            {
                break;
            }
            else
            {
                $newVip = $vipInfo['vipLevel'];
            }
        }
        //给用户加金币, 设置vip等级
        $userData->update(User4BBpayDao::tblUser)
                 ->set(array('gold_num'=>$opGold, 'vip'=>$newVip))
                 ->where('uid', '=', $uid)
                 ->query();
        
        $batchData->query();
        Statistics::gold(StatisticsDef::ST_FUNCKEY_MONTHCARD_GOLD, $goldBack, 
                $userObj->getGold(), $userObj->getPid());
        $guid = RPCContext::getInstance ()->getUid();
        if ($guid != null && $userObj->isOnline ())
        {
            $userObj->modifyFields (
                    array ('gold_num' => $goldBack, 'vip' => $newVip - $oldVip ) );
            $userObj->updateSession();
            RPCContext::getInstance()->sendMsg( array($uid),
                    PushInterfaceDef::USER_UPDATE_USER_INFO,
                    array(
                            'gold_num'=>$userObj->getGold (),
                            'vip'=>$userObj->getVip ())
                    );
            $msg    =    array(
                    'gold_num' => $userObj->getGold (), //当前的金币数目
                    'vip' => $userObj->getVip (),//当前的VIP等级
                    'charge_gold_sum' => $sumGold,//当前充值金额
                    'charge_gold' => $goldNum,//此次充值金额
                    'pay_back' => $goldBack, //充值返还（平台返还+配置返还）
                    'first_pay' => FALSE,//是否是首充
                    'charge_type' => CHARGE_TYPE::CHARGE_BUYMONTYLYCARD,
            );
            RPCContext::getInstance ()->sendMsg ( array ($uid ),
                    PushInterfaceDef::CHARGE_GOLD_UPDATE_USER,$msg);
            RPCContext::getInstance()->sendMsg(array($uid),
                    PushInterfaceDef::MONTHLY_CARD_UPDATE, array($cardInfo));
            RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_VIP, $newVip);
        }
        else
        {
            Logger::trace('user %s is not onlie.',$uid);
        }
        
        if (($newVip - $oldVip) > 0)
        {
            ChatTemplate::sendSysVipLevelUp1($userObj->getTemplateUserInfo(), $newVip);
            MailTemplate::sendVip($userObj->getUid(), $newVip);
            ChatTemplate::sendBroadcastVipLevelUp2($userObj->getTemplateUserInfo(), $newVip);
        }
    }
    
    public static function buyMonthlyCard($uid, $cardId)
    {
        self::canUserBuyMonthlyCard($uid, $cardId);
        $needGold = btstore_get()->MONTHLYCARD['card'][$cardId]['needMoney'];
        $userObj = EnUser::getUserObj($uid);
        if($userObj->subGold($needGold, DiscountCardDef::$MONTHLYCATD_TO_COST[$cardId]) == FALSE)
        {
            throw new FakeException('sub gold failed');
        }
        $lastBuyTime = 0;
        foreach (DiscountCardDef::$VAILD_MONTHLYCATD_IDS as $value)
        {
        	$inst = MonthlyCardObj::getInstance($uid, $value);
        	if ($lastBuyTime < $inst->getBuyTime()) 
        	{
        		$lastBuyTime = $inst->getBuyTime();
        	}
        }
        $monthlyCardObj = MonthlyCardObj::getInstance($uid, $cardId);
        if($monthlyCardObj->buyCard($lastBuyTime) == FALSE)
        {
            throw new FakeException('buy card failed.');
        }
        $userObj->update();
        $monthlyCardObj->save();
        
        //改变另一个月卡大礼包的状态
        if ($monthlyCardObj->getGiftStatus() == MONTHCARD_GIFTSTATUS::HASGIFT) 
        {
	        foreach (DiscountCardDef::$VAILD_MONTHLYCATD_IDS as $value)
	        {
	        	$inst = MonthlyCardObj::getInstance($uid, $value);
		        if ($value != $cardId && ($inst->getGiftStatus() != NULL) ) 
	        	{
	        		$inst->setGiftStatus($monthlyCardObj->getGiftStatus());
	        		$inst->save();
	        	}
	        }
        }
    	
        return $monthlyCardObj->getCardInfo();
    }
    
    public static function canUserBuyMonthlyCard($uid, $cardId)
    {
        $monthlyCardObj = MonthlyCardObj::getInstance($uid, $cardId);
        if($monthlyCardObj->canBuyCard() == FALSE)
        {
            throw new FakeException('can not buy monthlycard');
        }
        $chargeGold = self::getChargeGoldNumForCard($uid, $cardId);
        $needCharge = btstore_get()->MONTHLYCARD['card'][$cardId]['needChargeGold'];
        if($chargeGold < $needCharge)
        {
            throw new FakeException('chargegold %d need %d can not buy monthlycard.',$chargeGold,$needCharge);
        }
    }
    
    public static function getChargeGoldNumForCard($uid, $cardId)
    {
        $monthlyCardObj = MonthlyCardObj::getInstance($uid, $cardId);
        $cardInfo = $monthlyCardObj->getCardInfo();
        if(empty($cardInfo))
        {
            $startTime = 0;
        }
        else
        {
            $startTime = $monthlyCardObj->getDueTime();
        }
        if ( !defined( 'PlatformConfig::NEW_MONTHLYCARD_TIME' ) || !defined( 'PlatformConfig::NEW_MONTHLYCARD_TIME2' ))
        {
            throw new InterException('PlatformConfig::NEW_MONTHLYCARD_TIME is not set');
        }
        $rfrTime = strtotime(DiscountCardDef::$MONTHLYCATD_TO_STARTTIME[$cardId]);
        if($startTime < $rfrTime)
        {
            $startTime = $rfrTime;
        }
        $endTime = Util::getTime();
        $chargeGoldNum = User4BBpayDao::getSumGoldByTime($startTime, $endTime, $uid);
        return $chargeGoldNum;
    }
    
    public static function getDailyReward ($uid,$cardId)
    {
        $inst = MonthlyCardObj::getInstance($uid, $cardId);
        $cardInfo = $inst->getCardInfo();
        if(empty($cardInfo))
        {
            throw new FakeException('user %d has not monthlycard.',$uid);
        }
        //卡过期了
        if($inst->getDueTime() <= Util::getTime())
        {
            throw new FakeException('monthlycard duetime is %d now is %d.card is due.',$inst->getDueTime(),Util::getTime());
        }
        //今天的奖励领取了
        if(Util::isSameDay($inst->getRewardTime()))
        {
            throw new FakeException('monthlycard dailyreward has got.rewarttime is %d',$inst->getRewardTime());
        }
        $reward = btstore_get()->MONTHLYCARD['card'][$cardId]['originalReward']->toArray();
        $userObj = EnUser::getUserObj($uid);
        $bag = BagManager::getInstance()->getBag($uid);
        $statisType = RewardDef::$GOLD_STATISTICS_TYPE[DiscountCardDef::$MONTHLYCATD_TO_REWARD[$cardId]];
        RewardUtil::reward($uid, $reward, $statisType, TRUE);
        if($inst->setRewardTime(Util::getTime()) == FALSE)
        {
            throw new FakeException('set reward time failed.');
        }
        $inst->save();
        $userObj->update();
        $bag->update();
        return 'ok';
    }
    
    public static function rewardTransfrom($reward)
    {
        $ret = array();
        foreach($reward as $type => $value)
        {
            $ret[] = array(
                    'type'=>$type,
                    'val'=>$value
                    );
        }
        return $ret;
    }
    
    
    public static function sendRewardByCard($cardId,$uid,$rewardTime)
    {
        $userObj = EnUser::getUserObj($uid);
        $userLv = $userObj->getLevel();
        $rewardConf = btstore_get()->MONTHLYCARD['card'][$cardId]['reward']->toArray();
        $reward = array();
        foreach($rewardConf as $confType => $rewardInfo)
        {
            switch($confType)
            {
                case RewardConfType::SILVER_MUL_LEVEL:
                case RewardConfType::SOUL_MUL_LEVEL:
                case RewardConfType::EXP_MUL_LEVEL:
                    $rewardInfo = intval($rewardInfo) * $userLv;
                    break;
            }
            $type = RewardConfType::$rewardUtil2Center[$confType];
            $reward[$type] = $rewardInfo;
        }
        $reward[RewardDef::EXT_DATA][RewardDef::SQL_SEND_TIME] = $rewardTime;
        Logger::info('sendRewardByCard %s rewardtime is %d',$reward,$rewardTime);
        EnReward::sendReward($uid, DiscountCardDef::$MONTHLYCATD_TO_REWARD[$cardId], $reward);
        return $reward;
    }
    
    public static function getGift ($uid)
    {
    	$giftStatus = MONTHCARD_GIFTSTATUS::NOGIFT;
    	foreach (DiscountCardDef::$VAILD_MONTHLYCATD_IDS as $cardId)
    	{
    		$inst = MonthlyCardObj::getInstance($uid, $cardId);
    		if ($giftStatus < $inst->getGiftStatus())
    		{
    			$giftStatus = $inst->getGiftStatus();
    		}
    	}
    	
    	//没有礼包可以领取
    	if($giftStatus != MONTHCARD_GIFTSTATUS::HASGIFT)
    	{
    	    throw new FakeException('getGift failed.status is %d',$giftStatus);
    	}
    	
    	//找到对应的月卡
    	foreach (DiscountCardDef::$VAILD_MONTHLYCATD_IDS as $cardId)
    	{
    		$inst = MonthlyCardObj::getInstance($uid, $cardId);
    		if ($inst->getGiftStatus() == MONTHCARD_GIFTSTATUS::HASGIFT)
    		{
    			break;
    		}
    	}
    	
    	$inst->setGiftStatus(MONTHCARD_GIFTSTATUS::GOTGIFT);
    	$gift = btstore_get()->MONTHLYCARD['card'][$cardId]['originalgift']->toArray();
    	RewardUtil::reward($uid, $gift, StatisticsDef::ST_FUNCKEY_MONTHLYCARD_GIFT, TRUE);
    	$inst->save();
        Enuser::getUserObj($uid)->update();
        BagManager::getInstance()->getBag($uid)->update();
        
        //改变另一个月卡大礼包的状态
        foreach (DiscountCardDef::$VAILD_MONTHLYCATD_IDS as $value)
    	{
    		$inst = MonthlyCardObj::getInstance($uid, $value);
        	if ($value != $cardId && ($inst->getGiftStatus() != NULL) )  
        	{
        		$inst->setGiftStatus(MONTHCARD_GIFTSTATUS::GOTGIFT);
        		$inst->save();
        	}
        }
        
        return 'ok';
    }
    
    /**
     * 当前是否有大礼包
     * 开服前七天有大礼包
     */
    public static function inGiftTime()
    {
        $curTime = Util::getTime();
        $openDate = strtotime(GameConf::SERVER_OPEN_YMD.' '.GameConf::SERVER_OPEN_TIME);
        Logger::trace('curtime is %d opendate is %d 7days after opendate is %d',
                Util::getTime(),$openDate, $openDate + SECONDS_OF_DAY * DiscountCardDef::NEW_GROUP_GIFT_DAY);
        if($curTime >= $openDate && ($curTime < $openDate + SECONDS_OF_DAY * DiscountCardDef::NEW_GROUP_GIFT_DAY)
                 || (EnActivity::isOpen(ActivityName::MONTHLYCARDGIFT)))
        {
            return TRUE;
        }
        return FALSE;
    }
    /**
     * 获取月卡持续时间
     * @param int $cardId
     * @return number
     */
    public static function getDuration($cardId)
    {
        return btstore_get()->MONTHLYCARD['card'][$cardId]['duration'];
    }
    
    public static function getLimitTime($cardId)
    {
        return btstore_get()->MONTHLYCARD['card'][$cardId]['limitTime'];
    }
    
    /**
     * 获取凌晨时间戳
     * @param int $time
     */
    public static function getDayBreak($time)
    {
        return strtotime(date("Y-m-d", $time));
    }
    
    public static function canBuyMonthlyCard($uid)
    {
        $inst = MonthlyCardObj::getInstance($uid, DiscountCardDef::MONTHLYCATD_ID);
        $cardInfo = $inst->getCardInfo();
        return $inst->canBuyCard();
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */