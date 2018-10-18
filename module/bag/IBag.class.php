<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: IBag.class.php 250248 2016-07-06 09:32:12Z QingYao $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/bag/IBag.class.php $
 * @author $Author: QingYao $(jhd@babeltime.com)
 * @date $Date: 2016-07-06 09:32:12 +0000 (Wed, 06 Jul 2016) $
 * @version $Revision: 250248 $
 * @brief
 *
 **/

interface IBag
{
	/**
	 *
	 * 背包数据
	 *
	 * @return	mixed									用户背包的数据
	 * <code>
	 * 	{
	 * 		arm:										装备背包
	 * 		{
	 * 			gid:
	 * 				{
	 * 					item_id:int						物品ID
	 * 					item_template_id:int			物品模板ID
	 * 					item_num:int					物品数量
	 * 					item_time:int					物品产生时间
	 * 					va_item_text:					物品扩展信息
	 * 					{
	 * 						mixed
	 * 					}
	 * 				}
	 * 		}
	 *		props:										道具背包，同arm
	 *		heroFrag:									武将碎片背包，同arm
	 *		treas:										宝物背包，同arm
	 *		armFrag:									装备碎片背包，同arm
	 *		dress:									 	时装背包，同arm
	 *		fightSoul:									战魂背包，同arm
	 *		petFrag:									宠物碎片背包，同arm
	 *		godWeapon:									神兵背包，同arm
	 *		godWeaponFrag:								神兵碎片背包，同arm
	 *		pocket:										锦囊背包，同arm
	 *		tally:										兵符背包，同arm
	 *		tallyFrag:									兵符碎片背包，同arm
	 *     chariot:                                 战车背包，同arm
	 * 		gridStart									每个背包的起始格子
	 * 		{
	 * 			arm : int
	 * 			props : int
	 * 			heroFrag : int
	 * 			treas : int
	 * 			armFrag : int
	 * 			dress : int
	 * 			fightSoul : int
	 * 			petFrag : int 
	 * 			godWeapon ： int
	 * 			godWeaponFrag ： int
	 * 			rune:int
	 * 			runeFrag:int
	 * 			pocket:int
	 * 			tally:int
	 * 			tallyFrag:int
	 * 		}
	 * 		gridMaxNum									每个背包的最大格子数
	 * 		{
	 * 			arm : int
	 * 			props : int
	 * 			heroFrag : int
	 * 			treas : int
	 * 			armFrag : int
	 * 			dress : int
	 * 			fightSoul : int
	 * 			petFrag : int 
	 * 			godWeapon ： int
	 * 			godWeaponFrag ： int
	 * 			rune:int
	 * 			runeFrag:int
	 * 			pocket:int
	 * 			tally:int
	 * 			tallyFrag:int
	 * 		}
	 *  }
	 * </code>
	 */
	public function bagInfo();

	/**
	 *
	 * 格子数据
	 *
	 * @param int $gid
	 *
	 * @return array									物品信息
	 * <code>
	 * 	{
	 * 		item_id:int									物品ID
	 * 		item_template_id:int						物品模板ID
	 * 		item_num:int								物品数量
	 * 		item_time:int								物品产生时间
	 * 		va_item_text:								物品扩展信息
	 * 		{
	 * 			mixed
	 * 		}
	 * 	}
	 * </code>
	 */
	public function gridInfo($gid);

	/**
	 *
	 * 格子数据
	 *
	 * @param array(int) $gid
	 *
	 * @return array									物品信息
	 * <code>
	 * 	{
	 * 		gid:{
	 * 			item_id:int								物品ID
	 * 			item_template_id:int					物品模板ID
	 * 			item_num:int							物品数量
	 * 			item_time:int							物品产生时间
	 * 			va_item_text:							物品扩展信息
	 * 			{
	 * 				mixed
	 * 			}
	 * 		}
	 *  }
	 * </code>
	 */
	public function gridInfos($gid);

	/**
	 *
	 * 使用物品
	 *
	 * @param int $gid									格子ID
	 * @param int $itemId								物品ID
	 * @param int $itemNum								使用物品数量
	 * @param int $check								是否检查背包满，0不检查1检查，默认0
	 * @param int $merge								对于可叠加的物品，使用需要消耗多个物品时候，是否根据堆叠上限合并整理，0不合并，1合并，默认0
	 *
	 * @return array									
	 * <code>
	 * 	{
	 * 		'ret':string
	 *     		'ok'									成功
	 *     		'bagfull'								背包满了
	 *     		'herofull'								武将背包满了
	 *      'pet':array									宠物信息
	 * 		'drop':array								掉落信息
	 * 		{
	 * 			'item':array							物品
	 * 			{
	 * 				itemTemplateId => itemNum			物品模板id和数量
	 * 			}
	 * 			'hero':array							武将
	 * 			{	
	 * 				heroTid => heroNum					武将模板id和数量
	 * 			}
	 * 			'treasFrag':array						宝物碎片
	 * 			{
	 * 				itemTemplateId => itemNum			物品模板id和数量
	 *			}
	 * 			'silver':array							银币数量
	 * 			{
	 * 				index => $num
	 * 			}
	 * 			'soul':array							将魂数量	
	 * 			{
	 * 				index => $num
	 * 			}
	 * 		}
	 *  }
	 * </code>
	 */
	public function useItem($gid, $itemId, $itemNum, $check = 0, $merge = 0);
	
	/**
	 * 使用礼物物品
	 * 
	 * @param int $gid									格子ID
	 * @param int $itemId								物品ID
	 * @param int $optionId								选项ID
	 * @param int $itemNum								物品数量
	 * @param int $check								是否检查背包满，0不检查1检查，默认0
	 * @return ok
	 */
	public function useGift($gid, $itemId, $optionId, $itemNum, $check = 0);
	
	/**
	 * 批量卖出物品
	 * 
	 * @param array $gids
	 * <code>
	 * {
	 * 		array
	 * 		{
	 * 			$gid									格子ID
	 * 			$itemId									物品ID
	 * 			$itenNum								物品数量
	 * 		}
	 * }
	 * </code>
	 * @return ok
	 */
	public function sellItems($gids);

	/**
	 *
	 * 摧毁物品
	 *
	 * @param int $gid									格子ID
	 * @param int $itemId								物品ID
	 *
	 * @return ok
	 */
	public function destoryItem($gid, $itemId);

	/**
	 *
	 * 开启格子
	 * 每次开5个
	 *
	 * @param int $gridNum								格子数目, 只接受5
	 * @param int $bagType								背包类型,1装备2道具3宝物4装备碎片5时装6神兵7神兵碎片8符印9符印碎片10锦囊11兵符12兵符碎片13战车背包
	 *
	 * @return ok
	 */
	public function openGridByGold($gridNum, $bagType);

	/**
	 * 开启格子
	 *
	 * @param int $gridNum								格子数目, 只接受5
	 * @param int $bagType								背包类型, 1装备2道具3宝物4装备碎片5时装6神兵7神兵碎片8符印9符印碎片10锦囊11兵符12兵符碎片13战车背包
	 *
	 * @return ok
	 */
	public function openGridByItem($gridNum, $bagType);
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */