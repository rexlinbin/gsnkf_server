<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DiscountCardObj.class.php 239220 2016-04-20 04:34:53Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/discountcard/DiscountCardObj.class.php $
 * @author $Author: MingTian $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-04-20 04:34:53 +0000 (Wed, 20 Apr 2016) $
 * @version $Revision: 239220 $
 * @brief 
 *  
 **/
class DiscountCardObj
{
    protected $uid = NULL;
    protected $cardId = NULL;
    /**
     * 
     * @var array
     * [
     *     uid:Int
     *     card_id:int
     *     buy_time:int
     *     due_time:int
     *     va_card_info:array
     *     [
     *         monthly_card:array
     *         [
     *             reward_time:int   //领取每天奖励的时间
     *             gift_status:int   //大礼包状态
     *         ]
     *     ]
     * ]
     */
    protected $cardInfo = NULL;
    protected $buffer = NULL;
    
    protected function __construct($uid,$cardId)
    {
        if(empty($uid) || empty($cardId))
        {
            throw new FakeException('invalid uid %d or cardid %d',$uid,$cardId);
        }
        $this->uid = $uid;
        $this->cardId = $cardId;
        $cardInfo = DiscountCardDao::getCardInfo($uid, $cardId);
        $this->cardInfo = $cardInfo;
        $this->buffer = $cardInfo;
    }
    
    public function getCardInfo()
    {
        return $this->cardInfo;
    }
    
    public function getBuyTime()
    {
        if(!empty($this->cardInfo))
        {
             return $this->cardInfo[DiscountCardDef::TBL_SQLFIELD_BUYTIME];   
        }
        return NULL;
    }
    
    public function setBuyTime($time)
    {
        if(!empty($this->cardInfo))
        {
            $this->cardInfo[DiscountCardDef::TBL_SQLFIELD_BUYTIME] = $time;
        }
        return NULL;
    }
    
    public function getDueTime()
    {
        if(!empty($this->cardInfo))
        {
            return $this->cardInfo[DiscountCardDef::TBL_SQLFIELD_DUETIME];
        }
        return NULL;
    }
    
    public function getUid()
    {
        return $this->uid;
    }
    
    public function getCardId()
    {
        return $this->cardId;
    }
    
    public function save()
    {
    	//目前只能在自己的连接中改自己的数据
    	if($this->uid != RPCContext::getInstance()->getUid())
    	{
    		throw new InterException('Not in the uid:%d session', $this->uid);
    	}
        if($this->cardInfo != $this->buffer)
        {
            DiscountCardDao::saveCardInfo($this->cardInfo);
            $this->buffer = $this->cardInfo;
        }
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */