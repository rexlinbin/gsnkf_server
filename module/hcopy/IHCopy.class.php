<?php
/***************************************************************************
 *
 * Copyright (c) 2014 babeltime.com, Inc. All Rights Reserved
 * $Id: IHCopy.class.php 110447 2014-05-23 07:58:19Z QiangHuang $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hcopy/IHCopy.class.php $
 * @author $Author: QiangHuang $(huangqiang@babeltime.com)
 * @date $Date: 2014-05-23 07:58:19 +0000 (Fri, 23 May 2014) $
 * @version $Revision: 110447 $
 * @brief
 *
 **/

interface IHCopy
{
	/**
	 * @param none 
	 * @return 
	 * {  
	 *    $htid => array(  武将模板id
	 *       $copyid => array(    副本, 难度 ， 完成次数
	 *         level => $finish_num 
	 *      ),  
	 *    ),
	 *  }
	 */
	function getAllCopyInfos();
	/**
	 *  取某列传副本信息
	 *  @param: copyId 副本Id
	 *  @param: level 难度
	 *  @return  array
	 *  <code>
	 *  [
	 *  	copyid : int    副本id
	 *   	finish_num => int 已通关次数
	 * 		va_copy_info =>          副本扩展信息
	 * 			[
	 * 			    progress:array
	 *              [
	 *                 base_id=>base_status(base_status的取值：0可显示 1可攻击 2npc通关 3简单通关 4普通通关 5困难通关）
	 *              ]
	 * 			]
	 *  ]
	 *  </code>
	 */
	public function getCopyInfo($copyid, $level);

	/**
	 * 判断是否可以进入某据点某难度级别进行攻击
	 * @param int $copyId 副本id
	 * @param int $baseId 据点id
	 * @param int $baseLv   据点难度级别     npc:0,简单难度:1,普通难度:2,困难难度:3
	 * @return string 'ok' 'execution'(没有体力了） 'bag'(背包满了) 'formation'(武将不在阵型中) 'maxpassnum'(达到最大通关次数)
	 */
	public function enterBaseLevel($copyId,$baseId,$baseLv);

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
	 *
	 *      newcopyorbase : array
	 *       	[
	 *         		hero_copy : array  如果此副本有变化，如开启新据点，会返回副本最新数据，与getCopyInfo($copyid)相同。如果未通关，没有此字段。
	 *       			[
	 *  					copyid : int    副本id
	 *   					finish_num => int 已通关次数
	 * 						va_copy_info =>          副本扩展信息
	 * 							[
	 * 			    				progress:array
	 *              					[
	 *                 					base_id=>base_status(base_status的取值：0可显示 1可攻击 2npc通关 3简单通关 4普通通关 5困难通关）
	 *              					]
	 * 							]
	 *       			]
	 *         		pass_hero_copy : array 如果副本通关， 返回此数据。未通关，则没有此数据。
	 *       			[
	 *       				copyid => pass_num   副本id => 通关次数
	 *          		]
	 *        	]
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
	 * 更新在某个战斗中死亡的卡牌的血量为满血
	 * @param int $baseId				据点id
	 * @param int $baseLv			据点难度级别
	 * @param int $cardId				卡牌id
	 * @return 'ok' 'silver'
	 */
	public function reviveCard($baseId,$baseLv,$cardId);

}


