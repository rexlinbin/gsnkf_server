<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IACopy.class.php 245319 2016-06-02 11:39:26Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/acopy/IACopy.class.php $
 * @author $Author: GuohaoZheng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-06-02 11:39:26 +0000 (Thu, 02 Jun 2016) $
 * @version $Revision: 245319 $
 * @brief 
 *  
 **/
interface IACopy
{

	/**
	 * 获取所有的副本类活动
	 * @return array
	 * <code>
	 * [
	 * 		copyid:array
	 * 				[
	 * 					uid:int
	 * 					copy_id:int
	 * 					last_defeat_time:int
	 * 					can_defeat_num:int
	 *                  buy_atk_num:int
	 * 					va_copy_info:array
	 *                  [
	 *                      gold_atk_time:int 
	 *                      gold_atk_num:int
	 *                      gold_tree_exp:int    这个字段有可能没有 默认值是0
	 *                      gold_tree_level:int    这个字段有可能没有  默认值是1
	 *                      fmt_valid:bool            是否使用摇钱树阵型   如果没有此字段表示不使用摇钱树阵型  true表示使用  false表示不使用
	 *                      base_id: int         主角经验副本的据点id，默认是0
	 *                      battle_info:array
	 *                      [
	 *                          arrHero:array
	 *                          [
	 *                              pos=>array
	 *                              [
	 *                                  hid:int
	 *                                  htid:int
	 *                                  level:int
	 *                                  evolve_level:int
	 *                              ]
	 *                          ]
	 *                      ]
	 *                  ]
	 * 				]
	 * ]
	 * </code>
	 */
	function getCopyList();
	/**
	 * 获取某个副本的信息
	 * @param int $copyId
	 * @return array
	 * <code>
	 * 			[
	 * 				uid:int
	 * 				copy_id:int
	 * 				last_defeat_time:int
	 * 				can_defeat_num:int
	 * 				va_copy_info:array
	 * 			]
	 * </code>
	 */
	function getCopyInfo($copyId);
	/**
	 * 进入某个据点的难度级别进行攻击(活动类别：活动据点)
	 * @param int $copyId
	 * @param int $baseLv
	 * @return string 'ok'
	 */
	function enterBaseLevel($copyId,$baseId,$baseLv=BaseLevel::SIMPLE);
	/**
	 * 活动副本中的活动据点的战斗接口
	 * @param int $copyId
	 * @param int $baseLv
	 * @param int $armyId
	 * @param array $fmt		玩家的当前阵型
	 * @return array
	 * <code>
	 * [
	 *      err:string (nodefeatnum  execution ok三个值）
	 * 		fightRet:array
	 * 		curHp:array
	 * 				[
	 * 					array[hid:int   hp:int  costHp:int]
	 * 				]
	 * 		cd:int							战斗冷却时间
	 * 		reward:array
	 * 				[
	 * 					silver:int			银两奖励
	 * 					exp:int				经验奖励
	 * 					gold:int			金币奖励
	 * 					soul:int			将魂奖励
	 * 					item:array			物品奖励
	 * 					bag:array			背包中格子的变化
	 * 					card:array			掉落的卡牌
	 * 				]
	 *      extra_reward:array    格式同reward字段
	 * 		appraisal:int
	 *      hurt:int
	 * ]
	 * </code>
	 */
	function atkActBase($copyId,$baseLv,$armyId,$fmt=array());
	/**
	 * 攻击摇钱树
	 * @param int $copyId
	 * @param int $byItem 0表示使用攻击次数   1表示使用物品
	 * @param array $fmt
	 * @return array
	 * <code>
	 * [
	 *      err:string (nodefeatnum  execution ok三个值）
	 * 		fightRet:array
	 * 		curHp:array
	 * 				[
	 * 					array[hid:int   hp:int  costHp:int]
	 * 				]
	 * 		cd:int							战斗冷却时间
	 * 		reward:array
	 * 				[
	 * 					silver:int			银两奖励
	 * 					exp:int				经验奖励
	 * 					gold:int			金币奖励
	 * 					soul:int			将魂奖励
	 * 					item:array			物品奖励
	 * 					bag:array			背包中格子的变化
	 * 					card:array			掉落的卡牌
	 * 				]
	 *      extra_reward:array    格式同reward字段
	 * 		appraisal:int
	 *      hurt:int
	 * ]
	 * </code>
	 */
	function atkGoldTree($copyId,$byItem=0,$fmt=array());
	/**
	 * 攻击摇钱树
	 * @param int $copyId
	 * @param int $armyId
	 * @param array $fmt
	 * @return array
	 * <code>
	 * [
	 *      err:string (nodefeatnum  execution ok三个值）
	 * 		fightRet:array
	 * 		curHp:array
	 * 				[
	 * 					array[hid:int   hp:int  costHp:int]
	 * 				]
	 * 		cd:int							战斗冷却时间
	 * 		reward:array
	 * 				[
	 * 					silver:int			银两奖励
	 * 					exp:int				经验奖励
	 * 					gold:int			金币奖励
	 * 					soul:int			将魂奖励
	 * 					item:array			物品奖励
	 * 					bag:array			背包中格子的变化
	 * 					card:array			掉落的卡牌
	 * 				]
	 *      extra_reward:array    格式同reward字段
	 * 		appraisal:int
	 * ]
	 * </code>
	 */
	function doBattle($copyId,$baseId,$armyId,$fmt=array());
	/**
	 * 金币攻击摇钱树
	 * @param int $copyId
	 * @param array $fmt
	 * @return array
	 * <code>
	 * [
	 *      err:string (nodefeatnum  execution ok三个值）
	 * 		fightRet:array
	 * 		curHp:array
	 * 				[
	 * 					array[hid:int   hp:int  costHp:int]
	 * 				]
	 * 		cd:int							战斗冷却时间
	 * 		reward:array
	 * 				[
	 * 					silver:int			银两奖励
	 * 					exp:int				经验奖励
	 * 					gold:int			金币奖励
	 * 					soul:int			将魂奖励
	 * 					item:array			物品奖励
	 * 					bag:array			背包中格子的变化
	 * 					card:array			掉落的卡牌
	 * 				]
	 *      extra_reward:array    格式同reward字段
	 * 		appraisal:int
	 *      hurt:int
	 * ]
	 * </code>
	 */
	function atkGoldTreeByGold($copyId,$fmt=array());
	/**
	 * 复活死亡的卡牌
	 * @param int $copyId
	 * @param int $baseLv
	 * @param int $cardId
	 * @return string 'ok'
	 */
	function reviveCard($copyId,$baseLv,$cardId);
	
	/**
	 * 重新攻击某据点难度级别  应用场景：攻击失败之后点击重新攻击按钮
	 * @param int $copyId
	 * @param int $baseLv
	 * @return string 'ok'
	 */
	public function reFight($copyId,$baseLv);
	/**
	 * 离开某个副本的据点难度级别(活动类型：活动据点）
	 * @param int $copyId
	 * @param int $baseLv
	 * @return string 'ok'
	 */
	public function leaveBaseLevel($copyId,$baseLv);
	
	/**
	 * 购买摇钱树攻击次数
	 * @param int $num
	 * @return string 'ok'
	 */
	public function buyGoldTreeAtkNum($num);
	
	/**
	 * 购买经验宝物攻击次数
	 * @param int $num
	 * @return string 'ok'
	 */
	public function buyExpTreasAtkNum($num);
	
	/**
	 * 刷新摇钱树的战斗数据
	 * @return string 'ok'
	 */
	public function refreshBattleInfo();
	
	/**
	 * 设置是否使用摇钱树阵型
	 * @param bool $isValid
	 * @return string 'ok'
	 */
	public function setBattleInfoValid($isValid);
	
	/**
	 * 购买主角经验副本攻击次数
	 * @param int $num
	 * @return string 'ok'
	 */
	public function buyExpUserAtkNum($num);
	
	/**
	 * 购买天命副本攻击次数
	 * @param int $num
	 * @return 'ok'
	 */
	public function buyDestinyAtkNum($num);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */