<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnBattle.class.php 203470 2015-10-20 11:51:52Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/battle/EnBattle.class.php $
 * @author $Author: BaoguoMeng $(jiangzhichao@babeltime.com)
 * @date $Date: 2015-10-20 11:51:52 +0000 (Tue, 20 Oct 2015) $
 * @version $Revision: 203470 $
 * @brief 
 *  
 **/
/**********************************************************************************************************************
 * Class       : EnBattle
 * Description : 战斗内部接口类
 * Inherit     :
 **********************************************************************************************************************/
class EnBattle
{
	
	
	/**
	 *
	 * @param array $arrFormation1  
	 * <code>结构如下：
	 * {
	 * 		uid:用户uid
	 * 		name:用户名称
	 * 		isPlayer:是否为玩家
	 * 		level:用户等级
	 * 		flag:海盗旗
	 * 		formation:阵形id
	 * 		arrHero:
	 * 		[
	 * 			{
	 * 				
	 * 				hid:英雄id
	 * 				position:英雄在阵形中的位置0-8
	 * 				level:等级
	 * 				maxHp:最大血量
	 * 				currHp:当前血量，可选，如果没有则默认为最大血量
	 * 				currRage:当前怒气值，可选，如果没有则默认为0
	 * 				rageBase:怒气基础
	 * 				rageRatio:怒气倍率
	 * 				rageAmend:怒气修正
	 * 				dodge:闪避值
	 * 				fatal:致使值
	 * 				parry:招架值
	 * 				hit:命中值
	 * 				strength:力量值
	 * 				intelligence:智力值
	 * 				reign:统帅值
	 * 				parrySkill:招架技能
	 * 				charmSkill:魅惑技能
	 * 				chaosSkill:混乱技能
	 * 				attackSkill:普通攻击技能
	 * 				arrSkill:[所有装备技能id],
	 * 				arrImmunedEffect:[免疫的效果id]
	 * 				physicalAttackAddition:物理攻击百分比
	 * 				physicalAttackBase:物理攻击基础
	 * 				physicalAttackRatio:物理攻击倍率
	 * 				physicalDefendAddition:物理防御百分比
	 * 				physicalDefendBase:物理防御基础
	 * 				physicalDamageIgnoreRatio:物理免伤倍率
	 * 				magicAttackAddition:魔法攻击百分比
	 * 				magicAttackBase:魔法攻击基础
	 * 				magicAttackRatio:魔法攻击倍率
	 * 				magicDefendAddition:魔法防御百分比
	 * 				magicDefendBase:魔法防御基础
	 * 				magicDamageIgnoreRatio:魔法免伤倍率
	 * 				killAttackAddition:必杀攻击百分比
	 * 				killAttackBase:必杀攻击基础
	 * 				killAttackRatio:必杀攻击倍率
	 * 				killDefendAddition:必杀防御百分比
	 * 				killDefendBase:必杀防御基础
	 * 				killDamageIgnoreRatio:必杀免伤倍率
	 * 				windAttackAddition:风属性攻击百分比
	 * 				windAttackBase:风属性攻击基础
	 * 				windDefendBase:风属性防御基础
	 * 				thunderAttackAddition:雷属性攻击百分比
	 * 				thunderAttackBase:雷属性攻击基础
	 * 				thunderDefendBase:雷属性防御基础
	 * 				waterAttackAddition:水属性攻击百分比
	 * 				waterAttackBase:水属性攻击基础
	 * 				waterDefendBase:水属性防御基础
	 * 				fireAttackAddition:火属性攻击百分比
	 * 				fireAttackBase:火属性攻击基础
	 * 				fireDefendBase:火属性防御基础
	 * 				equipInfo:[装备id]
	 * 				daimonApple:[同物品基础信息]
	 
	 * 			}
	 * 		]
	 * 	}
	 * </code>
	 * @param array $arrFormation2 同$arrFormation1
	 * @param int $type 0表示普通回合，1表示强制回合：team1先出手  2普通回合，3强制回合：tame2先出手
	 * @param callback $callback 一个形如function a(server结构)的回调函数，返回结果为用户获得的奖励,参考下面reward描述
	 * @param array $arrEndCondition 结束条件结构如下：
	 * <code>
	 * {
	 * 		attackRound:攻击回合数
	 * 		defendRound:防守回合数
	 * 		team1:[[uid, hp百分比]]
	 * 		team2:[[uid, hp百分比]]
	 * }
	 * </code>
	 * @param array $arrExtra 其他参数
	 * <code>
	 * {
	 * 		bgid:背景id
	 * 		musicId:音乐id
	 * 		type:结算类型
	 * 		damageIncreConf => array 伤害随着回合数增加的配置，支持多段，多段之间的开始回合和结束回合不能重叠
	 * 		{
	 * 			array(type,begin,end,coef)  
	 * 				第一个元素代表伤害的增加类型  @see BattleDamageIncreType
	 * 				第二个元素代表开始的回合数
	 * 				第三个元素代表结束的回合数
	 * 				第四个元素代表伤害增加的系数，以10000为基数
	 * 		}
	 * 		isPvp：是否强制指定是否是Pvp类型，默认根据战斗数据中的"isPlayer指定"
	 * 		isMotifyId 	设置了这个值，且这个值为TRUE的话，会给hid做偏移
	 * }
	 * </code>
	 * @return array 
	 * <code>结构如下:
	 * {
	 * 		server:用于服务器端计算结果用，结构如下：
	 * 		{
	 * 			brid:战斗记录id
	 * 			round:持续回合数
	 * 			appraisal:评价
	 * 			team1:
	 * 			[
	 * 				{
	 * 					hid:英雄id
	 * 					hp:剩余血量
	 * 					costHp:消耗血量
	 * 					damage:受到的总伤害
	 * 					rage:剩余怒气
	 * 				}
	 * 			]
	 * 			team2:同team1
	 * 			uid1:队伍1的id
	 * 			uid2:队伍2的id
	 * 		}
	 * 		client:用于客户端显示动画用，结构如下：
	 * 		{
	 * 			brid:战斗记录
	 *			url_brid:用于组成url的记录id
	 *			bgId:背景id
	 *			musicId:音乐id
	 *			appraisal:评价
	 *			type:结算类型，@see BattleType
	 *			reward:
	 *			{
	 *				//此处仅为示例，不同type的战斗的奖励数据结构是不同的
	 *				arrHero:
	 *				[
	 *					{
	 *						hid:英雄id
	 *						htid:形象id
	 *						initial_level:等级初量
	 *						current_level:等级终量
	 *						current_exp:经验终量
	 *						add_exp:经验增量
	 *					}
	 *				]
	 *				prestige:获得的威望
	 *				exp:经验
	 *				expericne:阅历
	 *				belly:游戏币
	 *				contribute:工会贡献
	 *				equipInfo:[{}]
	 *				daimonApple:[同物品基础信息]
	 *			}
	 *			team1:
	 *			{
	 *				totalHpCost:总的hp消耗
	 *				totalDamage:受到的总伤害
	 *				name:用户名
	 *				level:等级
	 *				flag:海盗旗
	 *				formation:阵形id
	 *				uid:队伍id
	 *				isPlayer:是否为玩家
	 *				attackLevel:攻击等级
	 *				defendLevel:防守等级
	 *				flags:[获取得的旗子]
	 *				singleCount:单挑胜利次数
	 *				arrHero:
	 *				[
	 *					{
	 *						hid:英雄id
	 *						htid:形象id
	 *						maxHp:最大血量
	 *						currHp:初始血量
	 *						currRage:初始怒气值
	 *						equipInfo:[{装备信息}]
	 *						position:位置
	 *						arrSkill:普通技能
	 *						rageSkill:怒气技能
	 *						attackSkill:普通攻击技能
	 *						level:等级
	 *					}
	 *				]
	 *			}
	 *			team2:同team1数组
	 *			battle: 具体的战斗过程
	 *			[
	 *				{
	 *					attacker:攻击者
	 *					defender:本次攻击的基准目标
	 *					action:本次攻击所采用的技能id
	 *					round:本次攻击所在的回合，显示用
	 *					enBuffer:[增加的bufferId]
	 *					deBuffer:[消失的bufferId]
	 *					imBuffer:[免疫的bufferId]
	 *					buffer:
	 *					[
	 *						{
	 *							bufferId:生效的bufferId
	 *							type:生效的类型，9表示生命,28表示怒气
	 *							data:改变的数据
	 *						}
	 *					]
	 *					arrReaction:
	 *					[
	 *						{
	 *							defender:防守者
	 *							reaction:防守的动作
	 *							arrDamage:
	 *							[
	 *								{
	 *									damageType:伤害类型
	 *									damageValue:伤害值
	 *								}
	 *							]
	 *							fatal:是否暴击
	 *							enBuffer:[增加的bufferId]
	 *							deBuffer:[消失的bufferId]
	 *							imBuffer:[免疫的bufferId]
	 *							buffer:
	 *							[
	 *								{
	 *									bufferId:生效的bufferId
	 *									type:生效的类型，9表示生命，28表示怒气
	 *									data:改变的数据
	 *								}
	 *							]
	 *						}		
	 *					]
	 *					arrChild:同battle里的数组
	 * 				}
	 * 			]
	 * 		}
	 * }
	 * @param $db 指定db, 跨服战需要
	 * </code>
	 */
	public static function doHero($arrFormation1, $arrFormation2, $type = 0, $callback = null,
			$arrEndCondition = null, $arrExtra = null, $db = null)
	{
		return BattleLogic::doHero ( $arrFormation1, $arrFormation2, $type, $callback,
				$arrEndCondition, $arrExtra, $db );
	}
	/**
	 * 如果战斗胜利了，并且击败了某个monster   则有可能掉落卡牌
	 * @param array $atkRet
	 * <code>
	 * [
	 *     mstId:array
	 *         {
	 *             htid
	 *             level
	 *         }
	 * ]
	 * </code>
	 */
	public static function dropHeroOnDoneBt($atkRet)
	{
	    $dropHtids    =    array();
	    //输了
	    if(BattleDef::$APPRAISAL[$atkRet['appraisal']] > BattleDef::$APPRAISAL['D'])
	    {
	        return $dropHtids;
	    }
	    $team2    =    $atkRet['team2'];  
	    foreach($team2 as $mst)
	    {
	        if($mst['hp'] == 0 && ($mst['costHp'] > 0))
	        {
	            $dropHtidInfo    =    self::dropHero($mst['hid']);
	            if(!empty($dropHtidInfo))
	            {
	                $dropHtidInfo['mstId'] = $mst['hid'];
	                $dropHtids[]    =    $dropHtidInfo;
	            }
	        }
	        else 
	        {
	            continue;    
	        }
	    }
	    return $dropHtids;
	}
	
	public static function dropHero($mstId)
	{
	    $mstConf    =    btstore_get()->MONSTERS[$mstId];
	    if(!isset($mstConf[CreatureAttr::DROP_HERO_PRB])||
	            (count($mstConf[CreatureAttr::DROP_HERO_PRB])<2))
	    {
	        return FALSE;
	    }
	    $randnum    =    rand(0, 10000);
	    if($randnum<intval($mstConf[CreatureAttr::DROP_HERO_PRB][1]))
	    {
	        $level    =    1;
	        $htid    =   intval($mstConf[CreatureAttr::DROP_HERO_PRB][0]); 
	        if(isset($mstConf[CreatureAttr::DROP_HERO_PRB][2]))
	        {
	            //郑琛说掉落卡牌等级就是怪物等级
	            $level    =    intval($mstConf[CreatureAttr::LEVEL]);
	            $maxLevel =    HeroUtil::getMaxLevel($htid);
	            if($level > $maxLevel)
	            {
	                $level    =    $maxLevel;
	            }
	        }
	        return array('htid'=>$htid,'level'=>$level);
	    }
	    return FALSE;
	}
	
	/**
	 * 多人战斗
	 * @param array $arrFormationList1 战斗部队列表1，格式如下
	 * <code>
	 * 	{
	 * 		name:名称
	 * 		level:等级
	 * 		members:[参与doHero中参数arrFormationList]
	 * 	}
	 * </code>
	 * @param array $arrFormationList2 战斗部队列表2，同$arrFormationList1
	 * @param int $arenaCount 擂台个数，也即同时可以进行的战斗次数
	 * @param array $arrExtra 额外的参数
	 * <code>
	 * 	{
	 * 		arrEndCondition:结束条件
	 * 		mainBgid:主背景id
	 * 		subBgid:子背景id
	 * 		mainMusicId:主音乐id
	 * 		subMusicId:子音乐id
	 * 		mainCallback:主战斗的 callback
	 * 		subCallback:子战斗的callback
	 * 		mainType:主战斗的结算类型
	 * 		subType:子战斗的结算类型
	 * 		db:指定db
	 * 		isGuildWar：是否是跨服军团战
	 * 		mapUidInitWin:  用户初始胜利次数
	 * 		stopWhenBattleFailed：如果战斗报错，是否throw 
	 * 		damageIncreConf => array 伤害随着回合数增加的配置，支持多段，多段之间的开始回合和结束回合不能重叠
	 * 		{
	 * 			array(type,begin,end,coef)  
	 * 				第一个元素代表伤害的增加类型  @see BattleDamageIncreType
	 * 				第二个元素代表开始的回合数
	 * 				第三个元素代表结束的回合数
	 * 				第四个元素代表伤害增加的系数，以10000为基数
	 * 		}
	 * 		isPvp：是否强制指定是否是Pvp类型，默认根据战斗数据中的"isPlayer指定"
	 * 	}
	 * </code>
	 * @return array 战斗结果
	 * 	{
	 * 		client:
	 * 		{
	 *			team1:
	 *			{
	 *				memberCount:成员数量
	 *				name:名称
	 *				level:等级
	 * 			}
	 * 			team2:同team1
	 *			index:
	 *			[
	 *				{
	 * 					uid:用户uid
	 * 					records:[用户相关的战报id]
	 * 				}
	 * 			]
	 *			maxWin:
	 *			{
	 * 				uid:最大连胜场次
	 * 			}
	 * 			arena:
	 * 			[
	 * 				[	
	 * 					{
	 * 						brid:战报记录id
	 * 						attacker:1表示attacker是team1,2表示attacker是team2
	 * 						lastBrid:防守方所依赖的brid，也即必须结束的录相
	 * 					}
	 * 				]
	 * 			]
	 * 			lastTeam:参考doHero里的team1参数
	 * 			brid:战报id
	 * 			url_brid:用于组成url的记录id
	 * 			musicId:音乐id
	 * 			type:类型
	 * 			bgid:背景id
	 * 			result:结果
	 * 		}
	 * 		server:
	 * 		{
	 * 			result:true表示胜利
	 * 			brid:当前的战报id
	 * 			record:同client里
	 *          maxWin:最大连胜次数
	 *          arrProcess:array
	 *          [
	 *               index=>array
	 *               [
	 *                  'brid' => int,
     *                  'attacker' => int,
     *                  'defender' => int,
     *                  'appraise' => string,
     *                  'simpleRecord'=>array
     *              ]
	 *          ]
	 * 			team1:array
	 *          [
	 *              index=>array
	 *              [
	 *                  index=>array
	 *                  [
	 *                     'dress' => array
     *                     'htid' => int,
     *                     'maxHp' => int,
     *                     'uid' => int,
     *                     'name' => string,
     *                     'maxWin' => int,
	 *                  ]
	 *              ]
	 *          ]
	 *          team2:array
	 *          [
	 *              结构同team1
	 *          ]   
	 * 		}
	 * }
	 * @see IBattle.doHero()
	 */
	public static function  doMultiHero($arrFormationList1, $arrFormationList2, 
			$arenaCount = BattleConf::MAX_ARENA_COUNT, $maxWin = 1, $arrExtra = null)
	{
		return BattleLogic::doMultiHero($arrFormationList1, $arrFormationList2, $arenaCount, $maxWin, $arrExtra);
	}
   	
	/**
	 * 记录战报
	 * 
	 * @param  $brid					战报id
	 * @param  $recordData				战报数据
	 * @return NULL
	 */
	public static function addRecord($brid, $recordData)
	{
		BattleLogic::addRecord($brid, $recordData);
		return;
	}
	
	/**
	 * 获取战报
	 * 
	 * @param  $brid					战报id
	 * @return string					战报数据 
	 */
	public static function getRecord($brid)
	{
		return BattleLogic::getRecord($brid);
	}
	
	public static function getArrRecord($arrBrid)
	{
		return BattleLogic::getArrRecordRaw($arrBrid);
	}
	
	/**
	 * 设置左右双方谁先出手
	 * @param $type 战斗类型，doHero的第三个参数
	 * @param $leftFirst 是否左边先出手
	 */
	public static function setFirstAtk($type, $leftFirst)
	{
		$type = intval($type);
		if($leftFirst)
		{
			$type &= 0xFD;
		}
		else
		{
			$type |= 0x2;
		}
		
		return $type;
	}
	
	
	public static function isLeftFirstAtk($type)
	{
		if( ( $type & 0x2 ) == 0)
		{
			return true;
		}
		return false;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */