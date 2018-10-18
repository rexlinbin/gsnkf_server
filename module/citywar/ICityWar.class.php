<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ICityWar.class.php 138206 2014-10-31 07:41:45Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/citywar/ICityWar.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-10-31 07:41:45 +0000 (Fri, 31 Oct 2014) $
 * @version $Revision: 138206 $
 * @brief 
 *  
 **/
interface ICityWar
{
	/**
	 * 获得军团所有报名的城池信息
	 * 
	 * @param int $guildId
	 * @return array 
	 * <code>
	 * {
	 *      'timeConf':
	 *      {
	 *      	'signupStart':int  	报名开始时间
	 *      	'signupEnd':int		报名结束时间
	 *      	'prepare':int  		准备时间，在战斗开始之前为准备时间，即可以鼓舞，增加连战次数的阶段
	 *      	'arrAttack':array 
	 *      	{
	 *      		{13400000,13400001} 每轮战斗一个，两个元素，第一个为战斗开始时间，第二个为战斗结束时间
	 *      	}
	 *      	'reward':array
	 *      	{13400000,13400001} 发奖开始时间和发奖结束时间
	 *      }
	 *      'occupy':array
	 *      {
	 *      	$cityId:array
	 *      	{
	 *      		'guild_id':int
	 *      		'guild_name':string
	 *      	}
	 *      }
	 *      'sign':array
	 *      {
	 *      	$cityId
	 *      }
	 *      'suc':array
	 *      {
	 *      	$cityId
	 *      }
	 *      'attack':array
	 *      {
	 *      	$cityId
	 *      }
	 *      'reward':int 城池id
	 *      'offline':array
	 * 		{
	 * 			1 => $cityId 第一场
	 * 			2 => $cityId 第二场
	 * 		}
	 *      'contri_week':int 周贡献
	 * }
	 * </code>
	 */
	public function getGuildSignupList($guildId);
	
	/**
	 * 获得城池报名前10的军团信息
	 * 
	 * @param int $cityId
	 * @return array 
	 * <code>
	 * {
	 * 		'list' => array
	 * 		{
	 * 			$rank => array
	 * 			{
	 * 				'guild_id':int		军团id
	 * 				'guild_name':string	军团名称
	 * 				'guild_level':int	军团等级
	 * 				'fight_force':int	军团战斗力
	 * 				'contri_week':int	军团周贡献
	 * 			}
	 * 		}
	 * 		'self' => array
	 * 		{
	 * 			$rank => array
	 * 			{
	 * 				'guild_id':int		军团id
	 * 				'guild_name':string	军团名称
	 * 				'guild_level':int	军团等级
	 * 				'fight_force':int	军团战斗力
	 * 				'contri_week':int	军团周贡献
	 * 			}
	 * 		}
	 * }
	 * </code>
	 */
	public function getCitySignupList($cityId, $guildId);
	
	/**
	 * 获得城池的战报
	 * 
	 * @param int $cityId
	 * @return array 
	 * <code>
	 * {
	 * 		$id	第几场		
	 * 		{
	 * 			'attack':array
	 * 			{
	 * 				'guild_id':int	军团id
	 * 				'guild_name':int  军团名称
	 * 			}
	 * 			'defend':array
	 * 			{
	 * 				'guild_id':int	军团id
	 * 				'guild_name':int  军团名称
	 * 			}
	 * 			'result':int	0输1赢
	 * 			'replay':int	战报id
	 * 		}
	 * }
	 * </code>
	 */
	public function getCityAttackList($cityId);
	
	/**
	 * 获得城池信息
	 * 
	 * @param int $cityId
	 * @return array 
	 * <code>
	 * {
	 * 		'guild_id':int	军团id
	 * 		'guild_name':string	军团名称
	 * 		'guild_level':int	军团等级
	 * 		'fight_force':int	军团战斗力
	 * 		'city_defence':int	城防
	 * 		'city_force':int	城池战斗力
	 * 		'mend_time':int		修复城防时间
	 * 		'ruin_time':int		破坏城防时间
	 * }
	 * </code>
	 */
	public function getCityInfo($cityId);
	
	/**
	 * 战斗准备阶段：获得用户当前所在城池id
	 * 
	 * @return int $cityId  不在任何城池默认0
	 */
	public function getCityId();
	
	/**
	 * 离线入场
	 * 
	 * @param int $cityId
	 * @param int $roundId 战斗场次，0或1
	 * @return string $ret 结果:'ok'成功,'err'失败,'limit'达到上限,'nobattle'没有战斗
	 */
	public function offlineEnter($cityId, $roundId);
	
	/**
	 * 取消离线入场
	 * 
	 * @param int $cityId
	 * @param int $roundId 战斗场次，0或1
	 * @return string $ret 结果:'ok'成功,'err'失败,'nobattle'没有战斗
	 */
	public function cancelOfflineEnter($cityId, $roundId);
	
	/**
	 * 进入战场
	 * 
	 * @param int $cityId
	 * @return array 结果
	 * <code>
	 * {
	 * 		'ret':string 'ok'成功,'err'失败,'limit'达到上限,'nobattle'没有战斗
	 * 		'user':array
	 * 		{
	 * 			'max_win':int
	 * 			'buy_num':int
	 * 			'inspire_cd':int
	 * 			'attack_level':int
	 * 			'defend_level':int
	 * 		}
	 * 		'attacker':array
	 * 		{
	 * 			'guild_id':int
	 * 			'guild_name':string
	 * 			'list':array
	 * 			{
	 * 				$id:array
	 * 				{
	 * 					'uid':int
	 * 					'utid':int
	 * 					'uname':string
	 * 					'htid':int
	 *					'dress':array
	 * 				}
	 * 			}
	 * 			'offline':array
	 * 			{
	 * 				$id:array
	 * 				{
	 * 					'uid':int
	 * 					'utid':int
	 * 					'uname':string
	 * 					'htid':int
	 *					'dress':array
	 * 				}
	 * 			}
	 * 		}
	 * 		'defender':array
	 * 		{
	 * 			'guild_id':int
	 * 			'guild_name':string
	 * 			'list':array
	 * 			{
	 * 				$id:array
	 * 				{
	 * 					'uid':int
	 * 					'utid':int
	 * 					'uname':string
	 * 					'htid':int
	 *					'dress':array
	 * 				}
	 * 			}
	 * 			'offline':array
	 * 			{
	 * 				$id:array
	 * 				{
	 * 					'uid':int
	 * 					'utid':int
	 * 					'uname':string
	 * 					'htid':int
	 *					'dress':array
	 * 				}
	 * 			}
	 * 		}
	 * }
	 * </code>
	 */
	public function enter($cityId);
	
	/**
	 * 离开战场
	 *
	 * @param int $cityId
	 * @return string $ret 结果:'ok'成功,'err'失败
	 */
	public function leave($cityId);
	
	/**
	 * 鼓舞
	 * 
	 * @param int $cityId
	 * @param int $type 鼓舞类型：0银币，1金币
	 * @return array 用户攻防等级
	 * <code>
	 * {
	 * 		'ret':string ok/err
	 * 		'suc':bool true/false
	 * 		'attack_level':int
	 * 		'defend_level':int
	 * }
	 * </code>
	 */
	public function inspire($cityId, $type = 0);
	
	/**
	 * 购买连胜次数
	 * 
	 * @param int $cityId
	 * @return array 用户攻防等级
	 * <code>
	 * {
	 * 		'ret':string ok/err
	 * 		'max_win':int
	 * 		'buy_num':int
	 * }
	 * </code>
	 */
	public function buyWin($cityId);
	
	/**
	 * 领奖
	 * 
	 * @param int $cityId
	 * @return array 
	 * <code>
	 * {
	 * 		'ret':string ok/err
	 * 		'member_type':int 成员类型,0表示平民,1表示会长,2表示副会长
	 * }
	 * </code>
	 */
	public function getReward($cityId);
	
	/**
	 * 报名
	 *
	 * @param int $cityId 城池id
	 * @return string $ret 结果:'ok'成功,'err'失败
	 */
	public function signup($cityId);
	
	/**
	 * 破坏城防
	 * 
	 * @param int $cityId
	 * @return array
	 * <code>
	 * {
	 * 		'ret':string
	 * 			'ok'
	 * 			'failed'
	 * 		'atk':
	 * 		{								战斗模块返回的数据
	 * 			'uid':int 					用户id，默认0为NPC
	 * 			'uname':string				用户名字
	 * 			'fightRet' 					战斗字符串
	 * 			'appraisal'					评价
	 * 		}
	 * 		'defence':int					城防
	 * 		'force':int						战斗力
	 * 		'subdefence':int				减少城防
	 * 		'subforce':int					减少战斗力			
	 * }
	 * </code>
	 */
	public function ruinCity($cityId);
	
	/**
	 * 修复城防
	 * 
	 * @param int $cityId
	 * @return array
	 * <code>
	 * {
	 * 		'ret':string
	 * 			'ok'
	 * 			'failed'
	 * 		'atk':
	 * 		{								战斗模块返回的数据
	 * 			'uid':int 					用户id,默认0为NPC
	 * 			'uname':string				用户名字
	 * 			'fightRet' 					战斗字符串
	 * 			'appraisal'					评价
	 * 		}
	 * 		'defence':int					城防
	 * 		'force':int						战斗力
	 * 		'adddefence':int				增加城防
	 * 		'addforce':int					增加战斗力	
	 * }
	 * </code>
	 */
	public function mendCity($cityId);
	
	/**
	 * 清除cd
	 * 
	 * @param int $type	类型,0修复1破坏,默认0
	 * @return string $ret 结果:'ok'成功,'failed'失败
	 */
	public function clearCd($type = 0);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */