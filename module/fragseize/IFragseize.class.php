<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IFragseize.class.php 207981 2015-11-09 03:55:12Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/fragseize/IFragseize.class.php $
 * @author $Author: ShijieHan $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-11-09 03:55:12 +0000 (Mon, 09 Nov 2015) $
 * @version $Revision: 207981 $
 * @brief 
 *  
 **/
interface IFragseize
{
	/**
	 * 获取用户所有碎片
	 * @return array
	 * {
	 * 		array{ 'frag_id' => $fragId, 'fragNum' => $fragNum },
	 * 		.
	 * 		.
	 * 		array{ 'white_end_time' => int },免战结束时间
	 * }
	 * 
	 */
	public function getSeizerInfo();
	
	/**
	 * 获取可抢夺用户信息
	 * 
	 * @param int $fragId 碎片Id
	 * @return array
	 * {
	 * 		$uid => array
	 * 				{
	 * 					'uid' 	=> int,
	 * 					'utid'	=> int,
	 * 					'uname' => string,
	 * 					'level' => int,
	 * 					'frag_num'	  => int, 用户该碎片的数量
	 * 					'squad' => array(),
	 * 					'npc'	=> int, 0为不是，1为是
	 * 				}
	 * }
	 * 
	 */
	public function getRecRicher( $fragId, $num );
	
	/**
	 * 抢夺
	 * @param int $uid
	 * @param int $fragId
	 * @param int $isNPC=0 ( 0为不是 or 1为是 )
	 * @param bool $guide=false ( 是否为新手引导 )
	 * @return array
	 * {
	 * 		'fightStr'	=> string,
	 * 		'reward'	=> array
	 * 						{
	 * 							'exp' => int,
	 * 							'silver' => int,
	 * 							'fragNum' => int,
	 * 						},
	 * 		'card'		=> array{
	 * 								@see below
	 * 							},
	 * 		'fightFrc'	=> int,
	 * 		'apraisal'	=> A - F
	 * }
	 * OR 'fail'
	 * OR 'white' 对方在这一刻已经免战了
	 * 
	 * below 翻牌结果
	 *  {
	 * 		'real':						四种之一
	 * 		{	
	 * 			'rob' => $num			掠夺，在抽中掠夺时表示银币数量，没抽中掠夺时数量为0
	 * 			'silver' => $num		银币，数量
	 * 			'item':
	 * 			{
	 * 				'id':int			物品id
	 * 				'num:int			数量
	 * 			}
	 * 			'hero':
	 * 			{
	 * 				'id':int			武将id
	 * 				'num:int			数量
	 * 			}
	 * 		} 				
	 * 		'show1':					同上
	 * 		'show2':					同上
	 * }
	 * 
	 */
	public function seizeRicher( $uid, $fragId, $isNPC );
	
	/**
	 * 合成
	 * @param int $treasureId 要合成的宝物id
	 * @return true OR false
	 */
	public function fuse( $treasureId, $num );
	
	/**
	 * 
	 * @param int $type 1:金币免战 2:物品免战
	 * @param array $itemArr
	 * {
	 * 		item_tplID => int,
	 * 		.
	 * 		.
	 * }
	 */
	public function whiteFlag( $type );
	
	public function quickSeize($uid,$fragId,$seizeTimes);

	/**
	 * 一键夺宝
	 * @param $treasureId int 宝物模板id
	 * @param $ifUse int 0|1 是否自动使用体力丹(0不适用,1使用)
	 * @return array
	 * {
	 * 	'res' => 本字段一定会返回
	 * 			(ok, enough-碎片够合成一个宝物, bagFull-背包满, noStamina--没体力,当没选择自动使用耐力丹,并且没体力时候,
	 * 			noMedicine-没有体力丹, fail-用户自检失败 没有任何一个该宝物对应的碎片)
	 * 	'detail' => [
	 *		'fragId' => int 抢到的碎片id，如果没有该字段, 就是本次没抢到
	 * 		'medicine' => int 消耗的耐力丹数量 如果没有该字段, 就是本次没吃药
	 *		]
	 * 	'card' => { 同翻卡结构
	 *		'rob' => int
	 * 		'silver' => int
	 * 		'gold' => int
	 * 		'soul' => int
	 * 		'item' => {$id => $num}
	 * 		'hero' => {$id => $num}
	 * 		'treasFrag' => {$id => $num}
	 * 	}
	 * 	'silver' => int 银币
	 * 	'exp' => int 经验
	 * }
	 */
	public function oneKeySeize($treasureId, $ifUse=0);
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
