<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: IBoss.class.php 160199 2015-03-05 09:22:21Z ShiyuZhang $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/boss/IBoss.class.php $
 * @author $Author: ShiyuZhang $(jhd@babeltime.com)
 * @date $Date: 2015-03-05 09:22:21 +0000 (Thu, 05 Mar 2015) $
 * @version $Revision: 160199 $
 * @brief
 *
 **/

interface IBoss
{
	/**
	 * Boss的时间偏移
	 * @return int $offset(有符号int,单位s)
	 */
	public function getBossOffset();

	/**
	 * 进入boss（获取boss战信息）
	 *
	 * @param int $boss_id
	 * @return array
	 * <code>
	 * {
	 * 	 boss_time			=> 是否是boss时间 1是 0非
     *   boss_id 			=> bossId
     *   hp 				=> boss当前血量
     *   level 				=> boss当前等级
     *   start_time 		=> boss开始时间
     *   refresh_time 		=> 超级英雄刷新时间
     *   boss_maxhp 		=> boss的最大血量
     *   
     *   uid 				=> 用户uid
     *   uname 				=> 用户uname
     *   last_attack_time 	=> 用户上次攻击时间
     *   inspire_time_silver=> 用户上次银币鼓舞时间
     *   inspire_time_gold 	=> 用户上次金币鼓舞时间
     *   attack_hp			=> 用户给boss造成的总伤害
     *   attack_num			=> 用户总的攻击次数
     *   inspire 			=> 用户的鼓舞次数
     *   revive 			=> 用户的复活次数
     *   flags 				=> 用户是否处于复活
     *   atk_rank 			=> 用户的攻击血量排名
     *   formation_switch	=> 阵型开关
     *   va_boss_atk		=> array(
     *   						'formation' => array(
     *   											bossId => array(
     *   															'hid',
     *   															'htid',
     *   															....	
     *   																)));
     *   
	 * }
	 * Or
	 * {
	 * 		'boss_time':int				是否boss时间 1是 0非
	 * 		'level': int				boss当前等级
	 * }
	 * </code>
	 */
	public function enterBoss($boss_id);
	
	/**
	 * 攻击
	 * @param int $boss_id
	 * @return array					
	 * <code>
	 * {
	 * 		success 	boolean			是否成功,FALSE表示boss已经被击败并且下列数据无效
	 * 		hp  		boss当前血量
	 *		uname  		攻击者名字
	 *		bossAtkHp  	攻击者攻击的血量(这次攻击的血量)
	 *
	 *		attack_hp  	我的总攻击血量
	 *		fight_ret  	战斗串
	 *		rank  		我的排名
	 * }
	 * </code>
	 */
	public function attack();


	/**
	 *
	 * 立即复活
	 *
	 * @param int $boss_id		boss id
	 *
	 * @return boolean			TRUE表示成功
	 */
	public function revive();

	/**
	 *
	 * boss结束
	 *
	 * @return array
	 * <code>
	 * [
	 *  	is_expired 		请求是否过期
  	 *		boss_id 		bossid
  	 *		is_killer 		是否是自己杀死boss
  	 *		attack_hp 		攻击的总血量
  	 *		reward_kill 	杀死boss的奖励
  	 *		rank 			排名
  	 *		reward_rank 	排名奖励
	 * ]
	 * </code>
	 */
	public function over();

	/**
	 * 离开boss副本
	 * @return
	 */
	public function leaveBoss();

	/**
	 * 银币鼓舞
	 * @return	bool 鼓舞是否成功		TRUE表示成功,FALSE表示失败
	 */
	public function inspireBySilver();

	/**
	 * 金币鼓舞
	 * @return	bool 鼓舞是否成功		TRUE表示成功,FALSE表示失败
	 */
	public function inspireByGold();
	
	/**
	 * 获取伤害阵容
	 * @param int $bossId
	 * <code>
	 * [
	 *  	good 		=>array(htid,htid)
  	 *		better 		=>array(htid,htid)
  	 *		best 		=>array(htid,htid)
	 * ]
	 * 
	 */
	public function getSuperHero($bossId);
	
	/**
	 * 获取boss的攻击血量排行
	 * @param int $bossId
	 */
	public function getAtkerRank( $bossId );
	
	public function getMyRank();
	
	/**
	 * 设置boss战斗阵型
	 * @param unknown $bossId
	 */
	public function setBossFormation( $bossId );
	
	/**
	 * 开启或关闭boss阵型
	 * @param unknown $bossId
	 * @param unknown $switch ( 0关 1开 )
	 */
	public function setFormationSwitch( $bossId, $switch );
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */