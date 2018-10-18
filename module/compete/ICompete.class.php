<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ICompete.class.php 241875 2016-05-10 08:09:25Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/compete/ICompete.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-05-10 08:09:25 +0000 (Tue, 10 May 2016) $
 * @version $Revision: 241875 $
 * @brief 
 *  
 **/
/**********************************************************************************************************************
 * Class       : ICompete
 * Description : 比武系统对外接口类
 * Inherit     :
 **********************************************************************************************************************/
/**
 * 比武奖励发放失败后的补发脚本如下：
 * 1.script/tools/CompeteReward.php
 * 使用条件：全服未发奖且积分已重置, 利用脚本抓取用户积分数据，排序后作为输入。
 * 2.script/tools/FixCompete.php
 * 使用条件：全服积分未重置，用当前积分减去上一轮积分，加上1000，就是当前的积分。
 * 3.test/ResendCompeteReward.php
 * 使用条件：全服部分奖励未发且积分未重置，直接重发并重置积分。
 * 4.test/SendCompeteBC.php
 * 使用条件：全服部分奖励未发且积分未重置（时间久），补发全服补偿。
 * 5.test/SendCompeteReward.php
 * 使用条件：全服奖励未发完且积分已重置，直接补发最后一档奖励。
 * 6.test/SendComRewardByUser.php
 * 使用条件：全服N个人奖励未发且积分未重置，需要知道用户的排名，直接补发相应奖励。
 */
interface ICompete
{
	/**
	 * 获取用户的比武信息
	 *
	 * @return array
	 * <code>
	 * {
	 * 		'round':int						第几轮
	 * 		'state':int						状态：0没有开始，1比武，2休息，3发奖
	 * 		'num':int						比武次数
	 * 		'buy':int 						购买次数
	 * 		'honor':int						荣誉值
	 * 		'point':int						积分
	 * 		'rank':int						排名
	 * 		'refresh':int					刷新冷却时间
	 * 		'rivalList':array				比武时间内返回对手列表
	 * 		{
	 * 			{
	 * 				'uid':int				用户id
	 * 				'utid':int				用户模板id
	 * 				'uname':int        		用户名称
	 *    			'level':int             用户等级
	 *    			'vip':int				vip等级
	 *    			'fight_force':int 		用户战斗力
	 *    			'squad':array			阵容
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
	 *    			'point':int				积分
	 *    			'guild_name':string		军团名字
	 *    			'title':int				称号
	 *    		}
	 * 		}
	 * 		'rankList':array				比武结束后返回排行榜前三
	 * 		{
	 * 			{
	 * 				'uid':int				用户id
	 * 				'uname':int        		用户名称
	 *    			'level':int             用户等级
	 *    			'vip':int				vip等级
	 *    			'title':int				称号
	 *    			'squad':array			阵容
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
	 *  			'point':int				积分
	 *  			'rank':int				排名
	 *  			'guild_name':string		军团名字
	 *  		}
	 *    	}
	 * 		'foeList':array					仇人列表
	 * 		{
	 * 			{
	 * 				'uid':int				用户id
	 * 				'utid':int				用户模板id
	 * 				'uname':int        		用户名称
	 *    			'level':int             用户等级
	 *    			'vip':int				vip等级
	 *    			'fight_force':int 		用户战斗力
	 *    			'squad':array			阵容
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
	 *  			'point':int				积分
	 *  			'guild_name':string		军团名字
	 *    		}
	 * 		}
	 * }
	 * </code>
	 */
	public function getCompeteInfo();
	
	/**
	 * 刷新用户的对手列表
	 * 非比武时间不能刷新
	 * 
	 * @return array
	 * <code>
	 * {
 	 * 		{
 	 * 			'uid':int					用户id
 	 * 			'utid':int					用户模板id
 	 * 			'uname':int        			用户名称
 	 *    		'level':int                 用户等级
 	 *    		'fight_force':int 			用户战斗力
 	 *    		'squad':array				阵容
	 *  		{
	 *  			index => 
	 *  			{
	 *  				'htid'				阵容的位置对应武将模板id
	 *  				'dress'				时装信息
	 *  				{
	 *      				$posId => $dressTplId 位置id对应时装模板id
	 * 					}
	 *  			}
	 *  		}
	 *  		'point':int					积分
	 *  		'guild_name':string			军团名字
	 *  		'title':int				称号
 	 *    	}
 	 * }
 	 * </code>
	 */
	public function refreshRivalList();
	
	/**
	 * 比武或复仇
	 * 比武成功就刷新对手列表，额外返回rivalList
	 * 
	 * @param int $atkedUid					被攻击用户uid
	 * @param int $type						类型：0比武，1复仇, 默认为0
	 * @return array
	 * <code>
	 * {
	 * 		'atk':							战斗结果
	 * 		{								
	 * 			'fightRet' 					战斗字符串
	 * 			'appraisal'					评价
	 * 		}
	 * 		'flop':							1真2假，翻牌结果包含: 掠夺, 银币, 金币, 将魂, 物品, 武将, 宝物碎片
	 * 		{					
	 * 			'real':						7种之一
	 * 			{	
	 * 				'rob' => $num			掠夺，在抽中掠夺时表示银币数量，没抽中掠夺时数量为0
	 * 				'silver' => $num		银币，数量
	 * 				'gold' => $num			金币，数量
	 * 				'soul' => $num			将魂，数量
	 * 				'item':					物品
	 * 				{
	 * 					'id':int			物品id
	 * 					'num:int			数量
	 * 				}
	 * 				'hero':					武将
	 * 				{
	 * 					'id':int			武将id
	 * 					'num:int			数量
	 * 				}
	 * 				'treasFrag':			宝物碎片
	 * 				{
	 * 					'id':int			物品id
	 * 					'num:int			数量
	 * 				}
	 * 			} 				
	 * 			'show1':					同上
	 * 			'show2':					同上
	 * 		}
	 * 		'suc_point':int					胜利积分
	 * 		'point':int						积分
	 * 		'rank':int						排名				
	 * 		'rivalList':array				对手列表
	 * 		{
	 * 			{
	 * 				'uid':int				用户id
	 * 				'utid':int				用户模板id
	 * 				'uname':int        		用户名称
	 *    			'level':int             用户等级
	 *    			'fight_force':int 		用户战斗力
	 *    			'squad':array			阵容
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
	 *  			'point':int				积分
	 *  			'guild_name':string		军团名字
	 *  			'title':int				称号
	 *    		}
	 * 		}
	 * }
	 * </code>
	 */
	public function contest($atkedUid, $type = 0);
	
	/**
	 * 获取积分排行榜
	 * @return array
	 * <code>
	 * {
	 * 		{
	 * 			'uid':int				用户id
	 * 			'uname':int        		用户名称
	 *    		'level':int             用户等级
	 *    		'vip':int				vip等级
	 *    		'squad':array			阵容
	 *  		{
	 *  			index => 
	 *  			{
	 *  				'htid'			阵容的位置对应武将模板id
	 *  				'dress'			时装信息
	 *  				{
	 *      				$posId => $dressTplId 位置id对应时装模板id
	 * 					}
	 *  			}
	 *  		}
	 *  		'point':int				积分
	 *  		'rank':int				排名
	 *  		'fight_force':int		战斗力
	 *  		'guild_name':string		军团名字
	 *    	}
	 * }
	 * </code>	 
	 */
	public function getRankList();
	
	/**
	 * 购买比武次数
	 * 
	 * @param int $num
	 * @return string 'ok'
	 */
	public function buyCompeteNum($num);
	
	/**
	 * 获取商店信息
	 *
	 * @return array
	 * <code>
	 * {
	 * 		$goodsId					商品id
	 * 		{
	 * 			'num'					购买次数
	 * 			'time'					购买时间
	 * 		}
	 * }
	 * </code>
	 */
	public function getShopInfo();
	
	/**
	 * 商店兑换商品
	 *
	 * @param int $goodsId				商品id
	 * @param int $num					数量
	 * @param string 'ok'
	 */
	public function buy($goodsId, $num);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */