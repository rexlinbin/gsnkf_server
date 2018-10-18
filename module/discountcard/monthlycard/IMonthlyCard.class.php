<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IMonthlyCard.class.php 238955 2016-04-19 02:18:53Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/discountcard/monthlycard/IMonthlyCard.class.php $
 * @author $Author: MingTian $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-04-19 02:18:53 +0000 (Tue, 19 Apr 2016) $
 * @version $Revision: 238955 $
 * @brief 
 *  
 **/
/**
 * 月卡
 * @author dell
 * 1.月卡随时都可以买  不是活动
 * 2.月卡的奖励会变化   如果奖励变化时，玩家月卡未到期  那么玩家在变化前和变化后领取的奖励不一样
 * 3.月卡奖励每天连续发放    如果某天没有领取奖励  那么登陆的时候一并发到奖励中心
 * 4.一个账号只有在第一次买月卡时才有月卡大礼包
 * 5.大礼包如果不领取，是一直可以领取的
 * 6.如果月卡不到期，购买下一个是有限制的（如离月卡到期时间小于等于5天才能购买下一个月卡）
 *
 */
interface IMonthlyCard
{
    /**
     * @return array  如果玩家没有买过月卡  返回空array
     * <code>
     * [
     * 		1=>array	月卡1的信息
     * 		[
     *     		uid:Int
     *     		card_id:int
     *     		buy_time:int
     *     		due_time:int
     *     		va_card_info:array
     *     		[
     *         		monthly_card:array
     *         		[
     *             		reward_time:int   //领取每天奖励的时间
     *         		]
     *     		]
     *     		charge_gold:int
     *		] 
     *		2=>array	月卡2的信息
     * 		[
     *     		uid:Int
     *     		card_id:int
     *     		buy_time:int
     *     		due_time:int
     *     		va_card_info:array
     *     		[
     *         		monthly_card:array
     *         		[
     *             		reward_time:int   //领取每天奖励的时间
     *         		]
     *     		]
     *     		charge_gold:int
     *		]
     *		3=>array
     *		{
     *			gift_status:int   //大礼包状态  1:没有大礼包  2:有大礼包，并且没有领取  3:已经领取了大礼包  
     *		}
     * ]
     * </code>
     */
    public function getCardInfo();
    
    /**
     * unused
     */
    public function buyCard($uid, $orderId, $type, $itemTplId, $itemNum, $goldNum);
    
    /**
     * 领取每日奖励
     * 
     * @param int $cardId 月卡id
     * @return string 'ok'
     */
    public function getDailyReward($cardId);
    
    /**
     * 领取大礼包
     * 
     * @return string 'ok'
     */
    public function getGift();
    
    /**
     * 购买月卡
     * 
     * @param int $cardId 月卡id
     * @return array
     * <code>
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
     *             gift_status:int   //大礼包状态  1:没有大礼包  2:有大礼包，并且没有领取  3:已经领取了大礼包
     *         ]
     *     ]  
     * ]
     * </code>
     */
    public function buyMonthlyCard($cardId);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */