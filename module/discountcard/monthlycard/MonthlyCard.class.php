<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MonthlyCard.class.php 238955 2016-04-19 02:18:53Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/discountcard/monthlycard/MonthlyCard.class.php $
 * @author $Author: MingTian $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-04-19 02:18:53 +0000 (Tue, 19 Apr 2016) $
 * @version $Revision: 238955 $
 * @brief 
 *  
 **/
class MonthlyCard implements IMonthlyCard
{
    private $uid = NULL;

    public function __construct()
    {
        $this->uid = RPCContext::getInstance()->getUid();
    }
	/* (non-PHPdoc)
     * @see IMonthlyCard::getCardInfo()
     */
    public function getCardInfo ()
    {
        // TODO Auto-generated method stub
        Logger::trace('MonthlyCard.getCardInfo start');
        $ret = MonthlyCardLogic::getCardInfo($this->uid);
        Logger::trace('MonthlyCard.getCardInfo end.ret %s',$ret);
        return $ret;
    }

	/* (non-PHPdoc)
     * @see IMonthlyCard::buyCard()
     */
    public function buyCard ($uid, $orderId, $type, $itemTplId, $itemNum, $goldNum)
    {
        // TODO Auto-generated method stub
        MonthlyCardLogic::buyCard($uid, $orderId, $type, $itemTplId, $itemNum, $goldNum);
        Logger::trace('MonthlyCard.buyCard end');
    }
    
    public function buyMonthlyCard($cardId)
    {
    	if (!in_array($cardId, DiscountCardDef::$VAILD_MONTHLYCATD_IDS)) 
    	{
    		throw new FakeException('Err para, cardId:%d', $cardId);
    	}
        $ret = MonthlyCardLogic::buyMonthlyCard($this->uid, $cardId);
        return $ret;
    }

	/* (non-PHPdoc)
     * @see IMonthlyCard::getDailyReward()
     */
    public function getDailyReward ($cardId)
    {
        // TODO Auto-generated method stub
        Logger::trace('MonthlyCard.getDailyReward start');
        if (!in_array($cardId, DiscountCardDef::$VAILD_MONTHLYCATD_IDS))
        {
        	throw new FakeException('Err para, cardId:%d', $cardId);
        }
        $ret = MonthlyCardLogic::getDailyReward($this->uid, $cardId);
        Logger::trace('MonthlyCard.getDailyReward end.ret %s',$ret);
        return $ret;
    }

	/* (non-PHPdoc)
     * @see IMonthlyCard::getGift()
     */
    public function getGift ()
    {
        // TODO Auto-generated method stub
        Logger::trace('MonthlyCard.getGift start');
        $ret = MonthlyCardLogic::getGift($this->uid);
        Logger::trace('MonthlyCard.getGift end.ret %s',$ret);
        return $ret;
    }
    
    public function sendRewardToCenter($uid)
    {
        $guid = RPCContext::getInstance()->getUid();
        if($uid != $guid)
        {
            Logger::info('uid %d is not online.do not send reward to center.guid is %d',$uid,$guid);
            return;
        }
        Logger::info('user %d is online.send monthlycard reward to center.',$uid);
        $this->getCardInfo();
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */