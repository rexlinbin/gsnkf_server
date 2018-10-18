<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: INCopy.class.php 196499 2015-09-06 06:54:48Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ncopy/INCopy.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-09-06 06:54:48 +0000 (Sun, 06 Sep 2015) $
 * @version $Revision: 196499 $
 * @brief 
 *  
 **/
interface INCopy
{
	/**
	 * 获取玩家的所有普通副本
	 * @param $simple  是否进行登录优化  默认是false，不优化   
	 * @return array
	 * <code>
	 * [
	 *     copy_list:array
	 *     [
	 *         copyid:array
	 * 		    [
	 * 			    uid:int            玩家uid    没有此字段
	 * 			    copy_id:int        副本id      没有此字段
	 * 			    score:int          副本得分              如果达到了最高副本得分，没有此字段
	 * 			    prized_num:int     领取的副本奖励    如果领取了所有的箱子奖励 没有此字段
	 * 			    va_copy_info:      副本扩展信息   如果下面的字段都没有  没有此字段
	 * 			    [
	 * 				    progress:array    如果所有的据点都通过了所有难度  没有此字段
	 *                  [
	 *                      base_id=>base_status(base_status的取值：0可显示 1可攻击 2npc通关 3简单通关 4普通通关 5困难通关）
	 *                  ]   
	 *                  defeat_num:array       如果所有据点都没有打过    没有此字段
	 *                  [
	 *                      base_id=>can_defeat_num   据点可攻击次数
	 *                  ]   
	 *                  reset_num:array       如果所有据点都没有重置过  没有此字段
	 *                  [
	 *                      base_id=>reset_num   据点的重置次数（重置是设置攻击次数为0）
	 *                  ] 
	 * 			    ]
	 * 		    ]
	 *
	 *     ]
	 *     sweep_cd:int	 	
	 *     clear_sweep_num:int
	 * ]
	 * </code>
	 */
	function getCopyList($simple=FALSE);

	/**
	 * 判断是否可以进入某据点某难度级别进行攻击
	 * @param int $copyId 副本id
	 * @param int $baseId 据点id
	 * @param int $baseLv   据点难度级别     npc:0,简单难度:1,普通难度:2,困难难度:3
	 * @return string 'ok' 'execution'(没有体力了） 'bag'(背包满了)
	 */
	public function enterBaseLevel($copyId,$baseId,$baseLv);
	/**
	 * 领取副本奖励
	 * @param int $copyId 副本id
	 * @param int $caseID  取值是0,1,2
	 * @return array
	 * <code>
	 * [
	 * 		item:array					物品奖励
	 * 				[
	 * 					iteminfo:array
	 * 				]
	 * 		gold:int					金币奖励
	 * 		silver:int					银两奖励
	 * 		soul:int					将魂奖励
	 * 		bag:array					背包格子中物品的变化
	 * 				[
	 * 					gid:array(iteminfo)
	 * 				]
	 * ]
	 * </code>
	 */
	public function getPrize($copyId,$caseID);
	/**
	 * 战斗接口
	 * @param int $copy_id
	 * @param int $base_id
	 * @param int $level
	 * @param int $army_id
	 * @param array $fmt 当前玩家的阵型数据
	 * @param array $herolist
	 * @return array
	 * <code>
	 * [
	 * 		err:int					ok表示操作成功，execution表示行动力不足
	 * 		fightRet:array 			战斗过程以及结果
	 * 		reward:array			奖励信息
	 * 				[
	 * 					soul:int
	 * 					silver:int
	 * 					gold:int
	 * 					exp:int
	 * 					item:array
	 *                     [
	 *                         iteminfo:array
	 *                             [
	 *                                 item_id:int
     *                                 item_template_id:int
     *                                 item_num:int
	 *                             ]
	 *                     ]
	 * 					hero:array
	 *                     [
	 *                         dropHeroInfo:array
	 *                         [
	 *                             mstId:int    掉落武将的monsterId
     *                             htid:int     掉落的武将htid
	 *                         ]
	 *                        
	 *                     ]
	 * 				]
	 *      extra_reward:array      
	 *          [
	 *             item=>array
	 *             [
	 *                 ItemTmplId=>num
	 *             ]
	 *             hero=>array
	 *             [
	 *                 Htid=>num
	 *             ]
	 *             silver=>int
	 *             soul=>int
	 *             treasFrag=>array
	 *             [
	 *                 TreasFragTmplId=>num
	 *             ]
	 *          ]
	 * 		appraisal:int			战斗结果
	 * 		score:int				副本当前得分
	 * 		newcopyorbase:array		开启的新副本或者据点
	 *      mysmerchant:array       触发了神秘商人，如果为空  表示没有触发
	 *      cd:int                  战斗冷却时间
	 * ]
	 * </code>
	 */
	public function doBattle($copy_id,$base_id,$level,$army_id,$fmt=array(),$herolist=null);

	/**
	 * 离开据点某难度级别    应用场景：攻击成功或者失败后点击返回按钮
	 * @param int $copyId				副本id
	 * @param int $baseId				据点id
	 * @param int $baseLv				据点难度级别
	 * @return 'ok'
	 */
	public function leaveBaseLevel($copyId,$baseId,$baseLv);

	/**
	 * （暂时不用）重新攻击某据点难度级别  应用场景：攻击失败之后点击重新攻击按钮
	 * @param int $copyId			副本id
	 * @param int $baseId			据点id
	 * @param int $baseLv		据点难度级别  取值是0npc 1simple 2normal 3hard
	 * @return string ok
	 */
	public function reFight($copyId,$baseId,$baseLv);

	/**
	 * （暂时不用）返回据点攻击的攻略以及排名信息
	 * @param int $baseId				据点id
	 * @param int $base_level			据点难度级别 取值是0npc 1simple 2normal 3hard
	 * @return array
	 * [
	 * 		replay:array
	 * 		rank:array
	 * ]
	 */
	public function getBaseDefeatInfo($baseId,$base_level);
	/**
	 * （暂时不用）获取攻击据点的玩家排名信息（前十通关据点的玩家信息）
	 * @param int $copyId			副本id
	 * @return array
	 * <code>
	 * [
	 * 		uid:int				玩家uid
	 * 		level:int			玩家当时的级别
	 * 		copy_id:int			副本id
	 * 		rank:int			玩家的排名
	 * ]
	 * </code>
	 */
	public function getCopyRank($copyId);

	/**
	 * 更新在某个战斗中死亡的卡牌的血量为满血
	 * @param int $baseId				据点id
	 * @param int $baseLv			据点难度级别
	 * @param int $cardId				卡牌id
	 * @return 'ok' 'silver'
	 */
	public function reviveCard($baseId,$baseLv,$cardId);
	
	/**
	 * 非正常方式退出游戏之后  在进入游戏   返回给前端上次的进度 如果返回的是空数组 表示据点通关或者保存的信息过期了
	 * @return  array
	 * <code>
	 * [
	 * 	attackinfo:array
	 * 		[
	 * 			copy_id:int
	 * 			base_id:int
	 * 			level:int
	 * 			hp_modal:int
	 * 			card_info:array[hp:array[card_id:array[cur_hp:int max_hp:int]]]
	 * 			base_progress:array[base_id:int]
	 * 			revive_num:int 	
	 * 		]
	 * copylist:array
	 * 		[
	 * 			copyid:array
	 * 			[
	 * 				uid:int                玩家uid
	 * 				copy_id:int            副本id
	 * 				score:int              副本得分
	 * 				prized_num:int         领取的副本奖励
	 * 				va_copy_info:          副本扩展信息
	 * 				[
	 * 					mixed
	 * 				]
	 * 			]
	 * 		]
	 * ]
	 * </code>
	 */
	public function getAtkInfoOnEnterGame();
	/**
	 * (暂时不用)退出普通副本   清空服务器中session信息
	 * @param copyId    副本id
	 * @return string 'ok'
	 */
	public function leaveNCopy($copyId);
	
	/**
	 * 扫荡某个据点
	 * @param int $copyId
	 * @param int $baseId
	 * @param int $baseLv
	 * @param int $num
	 * @return array 
	 * <code>
	 * [
	 *     sweepcd:int
	 *     reward:array
	 *     [
	 *         index=>array
	 *         [
	 *         soul:int
	 *         exp:int
	 *         silver:int
	 *         item:array
	 *         hero:array
	 *         ]
	 *     ]
	 *     extra_reward:array
	 *     [
	 *             item=>array
	 *             [
	 *                 ItemTmplId=>num
	 *             ]
	 *             hero=>array
	 *             [
	 *                 Htid=>num
	 *             ]
	 *             silver=>int
	 *             soul=>int
	 *             treasFrag=>array
	 *             [
	 *                 TreasFragTmplId=>num
	 *             ]
	 *     ]
	 *     mysmerchant:array       触发了神秘商人，如果为空  表示没有触发
	 * ]
	 * </code>
	 */
	public function sweep($copyId,$baseId,$baseLv,$num);
	/**
	 * 重置某据点攻击次数
	 * @param int $baseId
	 * @param int $spendType 消费类型  1是金币 2是物品
	 * @return string 'ok'
	 */
	public function resetAtkNum($baseId,$spendType=CopyDef::RESET_BASE_SPEND_TYPE_GOLD);
	/**
	 * 根据副本得分获取副本排行榜
	 * @param $rankNum 排名数目
	 * @return array
	 * <code>
	 * [
	 *     rank_list:array
	 *     [
	 *         uid:int
	 *         uname:string
	 *         level:int
	 *         score:int
	 *         rank:int
	 *         htid:int
	 *         dress:array
	 *     ]
	 *     user_rank:array
	 *     [
	 *         rank:int
	 *         score:int
	 *     ]
	 * ]    
	 * </code>
	 */
	public function getUserRankByCopy($rankNum);
	/**
	 * @return string 'ok'
	 */
	public function clearSweepCd();
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */