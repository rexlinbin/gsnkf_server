<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IReward.class.php 214191 2015-12-07 03:30:58Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/IReward.class.php $
 * @author $Author: BaoguoMeng $(wuqilin@babeltime.com)
 * @date $Date: 2015-12-07 03:30:58 +0000 (Mon, 07 Dec 2015) $
 * @version $Revision: 214191 $
 * @brief 
 *  
 **/

interface IReward
{
	
	/**
	 * 获取玩家未领取的奖励列表
	 * @param int $offset   offset=0时，会附带上系统补偿的数据，次数返回的数据可能大于$limit
	 * @param int $limit  limit=-1时，表示拉取全部
	 * 
	 * @return array
	 * <code>
	 * {
	 * 		[
	 * 			rid int:奖励的唯一ID（ 其中id < 1000000 的为补偿 ）
	 * 			source int:类型，系统补偿、首充奖励等...(前后端预先约定好)
	 * 			send_time int:发奖时间
	 * 			expire_time int 到期时间
	 * 			va_reward: 具体的奖励。
	 * 			{
	 * 				item:	奖励物品
	 * 				[
	 * 					{
	 * 						tplId int: 物品模板ID
	 * 						num int: 物品个数
	 * 					}
	 * 				]
	 * 				gold:  金币
	 * 				silver： 银币
	 * 				soul: 将魂
	 * 				jewel: 魂玉
	 * 				execution: 行动力
	 * 				stamina: 耐力
	 * 				prestige: 声望
	 * 				fs_exp:战魂经验
	 * 				jh:武将精华
	 * 				arrHeroTpl: 英雄卡
	 * 				[
	 * 					{
	 * 						tplId int: 英雄模板ID
	 * 						num int: 个数
	 * 					}
	 * 				]
	 * 				extra:
	 * 				{
	 * 					//不同的奖励不同。如：竞技场中有rank(排名)
	 * 				}
	 * 			}
	 *
	 *		]
	 * }
	 * </code>
	 */
	public function getRewardList($offset, $limit);
	
	
	/**
	 * 领取奖励
	 * @param int $rid 要领取得奖励id
	 * @return ok  
	 * 			bag_full 背包满
	 */
	public function receiveReward($rid);
	
	/**
	 * 批量领取奖励
	 * @param array $ridArr 要领取的rid数组
	 * @return ok  
	 * 			bag_full 背包满
	 */
	public function receiveByRidArr( $ridArr );
	
	
	/**
	 * 礼包卡换礼品
	 * 
	 * @param string $code 礼品卡
	 * @return array
	 * <code>
	 * {
	 * ret:
	 * 		ok 
	 * 		0: 未知错误
	 *		1: 已使用，不可重复使用
	 *		2: 系统繁忙，请重试
	 * 		3: 此卡不可使用
	 * 		4 ：卡不存在
	 *      5: 领取失败，同一类型礼券只能使用一次
	 * reward:  
	 * 		{
	 * 			'silver' => $value			银币
	 * 			'soul' => $value			将魂
	 * 			'gold' => $value			金币
	 * 			'execution' => $value		体力
	 * 			'stamina' => $value			耐力
	 * 			'hero' => hid				武将	
	 * 			'item' => array				物品
	 * 			{
	 * 				item_template_id=> item_num
	 * 			}
	 * 		}
	 * info : 礼包名字
	 * }
	 * </code>
	 */
	function getGiftByCode($code);
	
	/**
	 * 获取玩家已领取的奖励列表
	 * @return array
	 * <code>
	 * {
	 * 		[
	 * 			rid int:奖励的唯一ID（ 其中id < 1000000 的为补偿 ）
	 * 			source int:类型，系统补偿、首充奖励等...(前后端预先约定好)
	 * 			recv_time int:领奖时间
	 * 			va_reward: 具体的奖励。
	 * 			{
	 * 				item:	奖励物品
	 * 				[
	 * 					{
	 * 						tplId int: 物品模板ID
	 * 						num int: 物品个数
	 * 					}
	 * 				]
	 * 				gold:  金币
	 * 				silver： 银币
	 * 				soul: 将魂
	 * 				jewel: 魂玉
	 * 				execution: 行动力
	 * 				stamina: 耐力
	 * 				prestige: 声望
	 * 				arrHeroTpl: 英雄卡
	 * 				[
	 * 					{
	 * 						tplId int: 英雄模板ID
	 * 						num int: 个数
	 * 					}
	 * 				]
	 * 				extra:
	 * 				{
	 * 					//不同的奖励不同。如：竞技场中有rank(排名)
	 * 				}
	 * 			}
	 *		]
	 * }
	 * </code>
	 */
	public function getReceivedList();
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */