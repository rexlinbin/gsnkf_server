<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IGuildCopy.class.php 232256 2016-03-11 07:50:02Z DuoLi $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildcopy/IGuildCopy.class.php $
 * @author $Author: DuoLi $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-03-11 07:50:02 +0000 (Fri, 11 Mar 2016) $
 * @version $Revision: 232256 $
 * @brief 
 *  
 **/

/*******************************接口修改记录*******************************
 * 接口去掉了enter和leave 									20150324
 * getUserInfo增加pass_time,total_hp,curr_hp，去掉progress	20150324	
 * getCopyInfo增加max_damager的注释							20150324				
 * getUserInfo去掉小丰收，增加refresh_time代表玩家自己的刷新时间		20150325
 * refresh接口去掉type参数									20150325
 * refresh接口返回值增加lack									20150325
 * attack接口增加ret字段									20150326
 * getCopyInfo接口hp中index改为hid，用hid索引					20150326
 * addAtkNum增加一种返回值already_pass代表买的时候刚好已经通关啦		20150326
 * refresh增加一种返回值already_pass代表军团成员全团突击时候，已经通关  	20150326
 * 推送接口增加uname字段，代表是谁购买了“全团突击”功能，前端跑马灯显示		20150326
 * 修改了getUserInfo的字段的一些中文描述							20150326
 * refresh和openBox接口添加返回值的中文描述						20150327
 * getBoxInfo和openBox也要增加htid							20150409
 * 增加推送，当军团副本通关时候向军团成员推送							20150409
 * getUserInfo增加字段refresher，代表使用过突击的军团成员姓名		20150409
 * attack接口返回据点怪血量信息									20150410
 * getRankList接口增加uid字段和fight_force					20150410
 * getRankList增加军团排行数据								20150420
 * 增加getLastBoxInfo接口，用于获取昨天的宝箱信息					20150424
 * getLastBoxInfo接口增加昨天的副本id							20150427
 * 修改了下注释												20150506
 * 
 * @author Administrator
 *
 */
 
interface IGuildCopy
{
	/**
	 * 获得玩家军团副本的基本信息，如果玩家不在任何军团，则返回空数组
	 * 
	 * @return
	 * {
	 * 		curr        	   						今天攻击目标副本Id
	 * 		next    								明天攻击目标副本Id
	 *      max_pass_copy							通关的最大军团副本id
	 *      refresh_num								军团今天使用“全团突击”的次数，为军团全体成员加n次攻击次数
	 *      refresh_time							玩家自己今天点击“全团突击”的时间，如果为0，代表今天还没有点击过
	 *      pass_time								当前副本通关时间，如果为0代表当前副本没有通关
	 * 		atk_damage								玩家今天造成的总伤害
	 * 		atk_num      							玩家今天总的可以攻击的次数（包括系统默认的，“全团突击”的，自己买的）
	 * 		buy_num 	   							已经购买的次数
	 * 		recv_pass_reward_time					通关后，领取阳光普照奖的时间，如果为0，代表今天未领取
	 * 		recv_box_reward_time					通关后，领取宝箱奖励的时间，如果为0，代表今天未领取
	 * 		total_hp								当前副本拥有的总血量
	 * 		curr_hp									当前副本剩余的总血量
	 * 		refresher(uname1,uname2...)				！！肖总，这是array，可不是map!!存储今天所属军团使用过全团突击的军团成员姓名
	 * 
	 * 		‘boss_info' => array { @see self::BossInfo } Boss数据
	 * }
	 */
	public function getUserInfo();
	
	/**
	 * 获取副本信息
	 * 
	 * @param int $copyId
	 * @return 
	 * [
	 * 		base_id => array                 	据点信息
     *      {
     *    	     hp => array                  	据点血量信息，如果没有被攻打过，没有hp
     *           [
     *               hid => array(total,curr) 	武将血量信息
     *           ]
     *           type => array()               	据点类型（魏蜀吴群,1234）
     *           max_damager => array           造成最大伤害的玩家信息，如果没有被攻打过，就没有max_damager
     *           {
     *               uid
     *               htid							
     *               uname						
     *               damage
     *           }
     *      }
	 * ]
	 */
	public function getCopyInfo($copyId);
	
	/**
	 * 设置攻打目标副本
	 * 
	 * @param int $copyId
	 * 
	 * @throws
	 * @return 'ok'
	 */
	public function setTarget($copyId);
	
	/**
	 * 攻击据点
	 * 
	 * @param int $copyId
	 * @param int $baseIndex
	 * 
	 * @return
	 * {
	 * 		ret => 'ok'|'dead'				以下字段只有在ok的情况下才有效,dead代表这个据点已经被击破啦
	 * 		fight_ret => array()			战斗战斗串
	 * 		damage => int 					伤害
	 * 		kill => int 					是否击杀0|1
	 * 		hp => array                  	据点血量信息
     *      [
     *          hid => array(total,curr) 	
     *      ]
	 * } 
	 */
	public function attack($copyId, $baseIndex);
	
	/**
	 * Boss信息
	 * 
	 * @param void
	 * @return array  
	 * { 
	 *  hp : int ,							当前boss血量
	 *  max_hp : int,						boss最高血量
	 *  cd : int , 							下一次boss刷新时间	
	 * 	atk_boss_num : int ,				当前进攻的次数
	 *  buy_boss_num : int ,    			购买的次数
 	 * }
	 * */
	public function bossInfo();
	
	/**
	 * 购买BOSS攻击次数
	 * @param $count int 购买的次数
	 * 
	 * @return 'ok'
	 * */
	public function buyBoss($count);
	
	/**
	 * 攻击BOSS
	 * @param void
	 * 
	 * @return 
	 * [
	 * 		ret => 'ok'|'conflict'|'cd'			以下字段只有在ok的情况下才有效,conflict代表别人同时在请求，cd代表BOSS正在CD中
	 * 		fight_ret => array()				战斗战斗串
	 * 		kill => int 						是否击杀0|1
	 * 		boss_info => array ( 'hp'=> int , 'cd' => int, 'max_hp' => int )
	 * ]
	 * */
	public function attackBoss();
	
	/**
	 * 获得排行榜信息，包含全服排行和军团排行
	 * 
	 * @return
	 * {
	 * 		all => array
	 * 		[
	 * 			{
	 * 				rank
	 * 				uid
	 * 				fight_force
	 * 				htid
	 * 				vip
	 *              level
	 *              fight_force
	 *              dress => array()
	 * 				uname
	 * 				guild_name
	 * 				damage
	 * 			}
	 * 		]
	 * 		guild => array
	 * 		[
	 * 			{
	 * 				rank
	 * 				uid
	 * 				fight_force
	 * 				htid
	 * 				vip
	 *              level
	 *              fight_force
	 *              dress => array()
	 * 				uname
	 * 				damage
	 * 			}
	 * 		]
	 * 		guild_copy => array
	 * 		[
	 * 			{
	 * 				rank
	 * 				guild_id
	 * 				guild_name
	 * 				guild_level
	 * 				fight_force
	 * 				max_pass_copy
	 * 				pass_time				这里其实是表里面的max_pass_time
	 * 			}
	 * 		]
	 * }
	 * 
	 */
	public function getRankList();
	
	/**
	 * 玩家自己通过购买增加攻击次数
	 * 
	 * @return 'ok'/'already_pass'
	 */
	public function addAtkNum();
	
	/**
	 * 刷新全体军团成员攻击次数
	 * 
	 * @return 'ok'/'already_pass'/'lack'  分别代表  ‘没问题’/‘军团已经通关’/‘军团今天总的全团突击次数用光’
	 */
	public function refresh();
	
	/**
	 * 通过副本后，军团成员领取"阳光普照奖"
	 * 
	 * @return 'ok'/'after_pass'
	 */
	public function recvPassReward();
	
	/**
	 * 获得宝箱的信息，为空数组，代表没有人领取了任何一个宝箱
	 * 
	 * @return
	 * [
	 * 		id => array
	 * 		{
	 * 			uid			领取这个宝箱的uid
	 * 			htid		领取这个宝箱的htid
	 * 			uname		领取这个宝箱的uname
	 * 			reward		这个宝箱中的奖励Id
	 * 		}
	 * ]
	 */
	public function getBoxInfo();
	
	/**
	 * 获得昨天宝箱的信息，为空数组，代表没有人领取了任何一个宝箱
	 *
	 * @return
	 * {
	 * 		last => int			昨天攻打的目标副本Id	
	 * 		box => array
	 * 		[
	 * 			id => array
	 * 			{
	 * 				uid			领取这个宝箱的uid
	 * 				htid		领取这个宝箱的htid
	 * 				uname		领取这个宝箱的uname
	 * 				reward		这个宝箱中的奖励Id
	 * 			}
	 * 		]
	 * }
	 */
	public function getLastBoxInfo();
	
	/**
	 * 通关副本后，军团成员抽取"宝箱奖励"
	 * 
	 * @param int $boxId
	 * @return
	 * {
	 * 		ret：'ok'/'already'/'after_pass'，分别代表“没问题”/“这个宝箱已经被别人领走啦”/“通过后才加入的军团”
	 * 		extra: 如果是ok，这里是奖励Id
	 * 			         如果是already，这个是领取者的数组
	 * 				{
	 * 					uid => int
	 * 					htid => int
	 * 					uname => string
	 * 					reward => 奖励Id
	 * 				}
	 * }
	 */
	public function openBox($boxId);
	
	/**
	 * 获取商店信息
	 *
	 * @return array
	 * [
	 * 		$goodsId => array			商品id
	 * 		{
	 * 			'num' => int			购买次数
	 * 			'time' => int			购买时间
	 * 		}
	 * ]
	 */
	public function getShopInfo();
	
	/**
	 * 兑换商品
	 *
	 * @param int $goodsId				商品id
	 * @param int $num					数量
	 * 
	 * @return string 'ok'
	*/
	public function buy($goodsId, $num);
}

/**
 *push.guildcopy.update_refresh_num
 *{
 *		total => int
 *		uname => string
 *}
 *push.guildcopy.curr_copy_pass
 *{
 *		uname => string
 *}
 */

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */