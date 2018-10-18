<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: IArena.class.php 202039 2015-10-14 03:49:45Z MingTian $
 * 
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/arena/IArena.class.php $
 * @author $Author: MingTian $(lanhongyu@babeltime.com)
 * @date $Date: 2015-10-14 03:49:45 +0000 (Wed, 14 Oct 2015) $
 * @version $Revision: 202039 $
 * @brief 
 * 
 **/

/**********************************************************************************************************************
 * Class       : IArena
 * Description : 竞技场系统对外接口类
 * Inherit     :
 **********************************************************************************************************************/

interface IArena
{
	/**
	 * 获得竞技场信息
	 * 
	 * @return array
	 * <code>
	 * {
	 * 		'ret':string	
	 * 			'ok'  
	 * 			'lock' 						后端错误，表示竞技场业务忙。
	 * 		'res':
	 * 		{
	 * 			'uid':int					用户id
	 * 			'position':int				排名    		  
	 * 			'challenge_num':int     	当天剩余的挑战次数
	 * 			'challenge_time':int		上次挑战时间
	 * 			'cur_suc':int 				当前连胜次数
	 * 			'max_suc':int 				历史最大连胜次数
	 * 			'min_position':int			历史最好排名
	 * 			'upgrade_continue':int		连续上升名次
	 * 			'reward_time':int			下次发奖时间
	 * 			'opponents':
	 * 			{
	 * 				position =>
	 *  			{
	 *  				'uid':int			用户id
	 *  				'utid':int			用户模板id
	 *  				'uname':string		用户名
	 *  				'level':int 		用户等级 
	 *  	 			'position':int		用户排名
	 *  				'squad':array		阵容
	 *  				{
	 *  					index => 
	 *  					{
	 *  						'htid'		阵容的位置对应武将模板id
	 *  						'dress'		时装信息
	 *  						{
	 *      						$posId => $dressTplId 位置id对应时装模板id
	 * 							}
	 *  					}
	 *  				}
	 *  				'armyId':int		普通用户这个字段是0，NPC这个字段非0
	 *  				'guild_name':string 军团名字
	 * 				}
	 * 			}
	 * 			'goods'
	 * 			{
	 * 				$goodsId				商品id
	 * 				{
	 * 					'num'				购买次数 
	 * 					'time'				购买时间
	 * 				}
	 * 			}
	 * 			'activity_begin_time':int	 活动开始时间
	 * 			'activity_end_time':int 	 活动结束时间
	 * 			'active_rate':int 			 奖励系数
	 * 		}
	 * }
	 * </code>
	 */
	public function getArenaInfo();
	
	/**
	 * 挑战某个排名的用户
	 * 
	 * @param uint $position 				排名
	 * @param uint $atkedUid 				排名对应的用户uid,
	 * @return array
	 * <code>
	 * {
	 * 		'ret':string
	 * 			'ok'
	 * 			'position_err' 				攻击位置错误，可能是当前用户被其他用户挑战打败，不能挑战此位置。对手信息更新了，但是前端还没有收到同步的数据
	 * 			'opponents_err'  			位置跟用户不一致
	 * 			'lock'  					竞技场业务忙
	 * 		'atk':							战斗模块返回的数据
	 * 		{		
	 * 			{	
	 * 				'fightRet' 				战斗字符串，战10次没有这个字段	
	 * 				'appraisal'				评价
	 * 				'force'					对方战斗力
	 * 			}
	 * 		}
	 * 		'opponents':					opponents_err时返回对手信息
	 * 		{
	 * 			position =>
	 *  		{
	 *  			'uid':int				用户id
	 *  			'utid':int				用户模板id
	 *  			'uname':string			用户名
	 *  			'level':int 			用户等级 
	 *  	 		'position':int			用户排名
	 *  			'squad':array			阵容
	 *  			{
	 *  				index => 
	 *  				{
	 *  					'htid'			阵容的位置对应武将模板id
	 *  					'dress'			时装信息
	 *  					{
	 *      					$posId => $dressTplId 位置id对应时装模板id
	 * 						}
	 *  				}
	 *  			}
	 *  			'armyId':int			普通用户这个字段是0，NPC这个字段非0
	 *  			'guild_name':string 军团名字
	 * 			}
	 * 		}
	 * 		'flop':							1真2假，翻牌结果包含: 掠夺, 银币, 金币, 将魂, 物品, 武将, 宝物碎片
	 * 		{	
	 * 			{				
	 * 				'real':						7种之一
	 * 				{	
	 * 					'rob' => $num			掠夺，在抽中掠夺时表示银币数量，没抽中掠夺时数量为0
	 * 					'silver' => $num		银币，数量
	 * 					'gold' => $num			金币，数量
	 * 					'soul' => $num			将魂，数量
	 * 					'item':					物品
	 * 					{
	 * 						'id':int			物品id
	 * 						'num:int			数量
	 * 					}
	 * 					'hero':					武将
	 * 					{
	 * 						'id':int			武将id
	 * 						'num:int			数量
	 * 					}
	 * 					'treasFrag':			宝物碎片
	 * 					{
	 * 						'id':int			物品id
	 * 						'num:int			数量
	 * 					}
	 * 				} 				
	 * 				'show1':					同上，战10次没有
	 * 				'show2':					同上，战10次没有
	 * 			}
	 * 		}
	 * }
	 * </code>
	 */
	public function challenge($position, $atkedUid, $num = 1);
	
	/**
	 * 获取竞技排行榜（前十）
	 * 
	 * @return array
	 * <code>
	 * {
	 * 		position =>
	 * 		{
	 * 			'uid':int			用户id
	 *  		'utid':int			用户模板id
	 *  		'uname':string		用户名
	 *  		'level':int 		用户等级 
	 *  		'vip':int			vip等级
	 *  	 	'position':int		用户排名
	 *  		'squad':array		阵容
	 *  		{
	 *  			index => 
	 *  			{
	 *  				'htid'		阵容的位置对应武将模板id
	 *  				'dress'		时装信息
	 *  				{
	 *      				$posId => $dressTplId 位置id对应时装模板id
	 * 					}
	 *  			}
	 *  		}
	 *  		'armyId':int		普通用户这个字段是0，NPC这个字段非0
	 *  		'fight_force':int	战斗力
	 *  		'guild_name':string 军团名字
	 * 		}
	 * }
	 * </code>
	 */
	public function getRankList();
	
	/**
	 * 获取幸运排名
	 * 
	 * @return array
	 * <code>
	 * {
	 * 		'last':array
	 * 		{
	 * 			{
	 * 				'position'
	 * 				'uid'
	 * 				'utid'
	 * 				'uname'
	 * 				'gold'
	 *			}
	 * 		}
	 * 		'current':array
	 * 		{
	 * 			{
	 * 				'position'
	 * 				'gold'
	 * 			}
	 * 		}
	 * }
	 * </code>
	 */
	public function getLuckyList();
	
	/**
	 * 商店兑换商品
	 *
	 * @param int $goodsId				商品id
	 * @param int $num					数量
	 * @param string 'ok'
	 */
	public function buy($goodsId, $num);
	
	/**
	 * 发放竞技场排名奖励
	 *
	 * @return string 'ok'
	 */
	public function sendRankReward();
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */