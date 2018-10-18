<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IForge.class.php 258136 2016-08-24 07:18:02Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/forge/IForge.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-08-24 07:18:02 +0000 (Wed, 24 Aug 2016) $
 * @version $Revision: 258136 $
 * @brief 
 *  
 **/

interface IForge
{
	/**
	 *
	 * 强化装备
	 *
	 * @param int $itemId			物品ID
	 * @param int $level			等级
	 * @return array 
	 * <code>
	 * {
	 * 		'cost_num':int			花费银币
	 * 		'fatal_num':int			暴击次数
	 *		'level_num':int			强化级数
	 * }
	 * </code>
	 */
	public function reinforce($itemId, $level = 1);
	
	/**
	 * 自动强化装备
	 * 
	 * @param int $itemId 		 物品id
	 * @return array 
	 * <code>
	 * {
	 * 		{
	 * 			'cost_num':int			花费银币
	 * 			'fatal_num':int			暴击次数
	 *			'level_num':int			强化级数
	 *		}
	 * }
	 * </code>
	 */
	public function autoReinforce($itemId);
	
	/**
	 * 进阶装备
	 * 品质变化，模板id不变
	 *
	 * @param int $itemId			物品id
	 * @param int $itemIds			消耗的物品id组
	 * @return array
	 * <code>
	 * {
	 * 		'armReinforceLevel':强化等级
 	 * 		'armReinforceCost':强化费用
 	 * 		'armDevelop':装备进阶等级
 	 * 		'armPotence':潜能属性数组
 	 * 		{
 	 * 			$attrId => $attrValue
 	 * 		}
 	 * 		'armFixedPotence':洗练属性数组
 	 * 		{
 	 * 			$attrId => $attrValue
 	 * 		}
	 * }
	 * </code>
	 */
	public function developArm($itemId, $itemIds);
	
	/**
	 * 升级宝物
	 * 
	 * @param int $itemId			物品id
	 * @param array $itemIds		消耗的物品id组
	 * @param array $arrNum			物品对应的数量
	 * @return array
	 * <code>
	 * {
	 * 		item_id:int				物品ID
	 * 		item_template_id:int	物品模板ID
	 * 		item_num:int			物品数量
	 * 		item_time:int			物品产生时间
	 * 		va_item_text:			物品扩展信息
	 * 		{
	 * 			treasureLevel:int	当前等级
	 * 			treasureExp:int		总经验值
	 * 			treasureEvolve:int	进阶等级
	 * 		}
	 * }
	 * </code>
	 */
	public function upgrade($itemId, $itemIds, $arrNum = array());
	
	/**
	 * 精炼宝物
	 *
	 * @param int $itemId			物品id
	 * @param int $itemIds			消耗的物品id组
	 * @return array
	 * <code>
	 * {
	 * 		item_id:int				物品ID
	 * 		item_template_id:int	物品模板ID
	 * 		item_num:int			物品数量
	 * 		item_time:int			物品产生时间
	 * 		va_item_text:			物品扩展信息
	 * 		{
	 * 			treasureLevel:int	当前等级
	 * 			treasureExp:int		总经验值
	 * 			treasureEvolve:int	精炼等级
	 * 		}
	 * }
	 * </code>
	 */
	public function evolve($itemId, $itemIds);
	
	/**
	 * 进阶宝物 
	 * 品质变化，模板id不变
	 *
	 * @param int $itemId			物品id
	 * @param int $itemIds			消耗的物品id组
	 * @return array
	 * <code>
	 * {
	 * 		item_id:int				物品ID
	 * 		item_template_id:int	物品模板ID
	 * 		item_num:int			物品数量
	 * 		item_time:int			物品产生时间
	 * 		va_item_text:			物品扩展信息
	 * 		{
	 * 			treasureLevel:int	当前等级
	 * 			treasureExp:int		总经验值
	 * 			treasureEvolve:int	精炼等级
	 * 			treasureDevelop:int	进阶等级
	 * 		}
	 * }
	 * </code>
	 */
	public function develop($itemId, $itemIds);
	
	/**
	 * 道具升级战魂
	 *
	 * @param int $itemId			物品id
	 * @param int $itemIds			消耗的物品id组
	 * @return array
	 * <code>
	 * {
	 * 		item_id:int				物品ID
	 * 		item_template_id:int	物品模板ID
	 * 		item_num:int			物品数量
	 * 		item_time:int			物品产生时间
	 * 		va_item_text:			物品扩展信息
	 * 		{
	 * 			fsLevel:int	当前等级
	 * 			fsExp:int	总经验值
	 * 		}
	 * }
	 */
	public function promote($itemId, $itemIds);
	
	/**
	 * 经验升级战魂
	 * 
	 * @param int $itemId 			物品id
	 * @param int $addLevel			增加等级
	 * @return array
	 * <code>
	 * {
	 * 		'fs_exp':int 			吃掉的战魂经验
	 * 		item_id:int				物品ID
	 * 		item_template_id:int	物品模板ID
	 * 		item_num:int			物品数量
	 * 		item_time:int			物品产生时间
	 * 		va_item_text:			物品扩展信息
	 * 		{
	 * 			fsLevel:int	当前等级
	 * 			fsExp:int	总经验值
	 * 		}
	 * }
	 */
	public function promoteByExp($itemId, $addLevel);
	
	/**
	 * 进阶战魂
	 * 模板id变化
	 *
	 * @param int $itemId			物品id
	 * @param int $itemIds			消耗的物品id组
	 * @return array
	 * <code>
	 * {
	 * 		item_id:int				物品ID
	 * 		item_template_id:int	物品模板ID
	 * 		item_num:int			物品数量
	 * 		item_time:int			物品产生时间
	 * 		va_item_text:			物品扩展信息
	 * 		{
	 * 			fsLevel:int	当前等级
	 * 			fsExp:int	总经验值
	 * 		}
	 * }
	 * </code>
	 */
	public function fightSoulDevelop($itemId, $itemIds);
	
	/**
	 * 精炼战魂
	 *
	 * @param int $itemId			物品id
	 * @param int $itemIds			消耗的物品id组
	 * @return array
	 * <code>
	 * {
	 * 		item_id:int				物品ID
	 * 		item_template_id:int	物品模板ID
	 * 		item_num:int			物品数量
	 * 		item_time:int			物品产生时间
	 * 		va_item_text:			物品扩展信息
	 * 		{
	 * 			fsLevel:int	当前等级
	 * 			fsExp:int	总经验值
	 * 			fsEvolve:int 精炼等级 （不能精炼的战魂没有这个字段）
	 * 		}
	 * }
	 * </code>
	 */
	public function fightSoulEvolve($itemId, $itemIds);
	
	/**
	 * 升级时装
	 *
	 * @param int $itemId			物品id
	 * @return array
	 * <code>
	 * {
	 * 		item_id:int				物品ID
	 * 		item_template_id:int	物品模板ID
	 * 		item_num:int			物品数量
	 * 		item_time:int			物品产生时间
	 * 		va_item_text:			物品扩展信息
	 * 		{
	 * 			dressLevel:int		当前等级
	 * 		}
	 * }
	 */
	public function upgradeDress($itemId);
	
	/**
	 * 升级锦囊
	 * 
	 * @param int $itemId
	 * @param array $itemIds
	 * @return array
	 * <code>
	 * {
	 * 		item_id:int				物品ID
	 * 		item_template_id:int	物品模板ID
	 * 		item_num:int			物品数量
	 * 		item_time:int			物品产生时间
	 * 		va_item_text:			物品扩展信息
	 * 		{
	 * 			pocketLevel:int		当前等级
	 * 			pocketExp:int		总经验值
	 * 		}
	 * }
	 */
	public function upgradePocket($itemId, $itemIds);
	
	/**
	 * 升级兵符
	 *
	 * @param int $itemId
	 * @param array $itemIds
	 * @param array $arrNum			物品对应的数量
	 * @return array
	 * <code>
	 * {
	 * 		silver:int				消耗的银币
	 * 		item_id:int				物品ID
	 * 		item_template_id:int	物品模板ID
	 * 		item_num:int			物品数量
	 * 		item_time:int			物品产生时间
	 * 		va_item_text:			物品扩展信息
	 * 		{
	 * 			tallyLevel:等级
 	 * 			tallyExp:总经验值
     * 			tallyEvolve:精炼等级
 	 * 			tallyDevelop:进阶等级
	 * 		}
	 * }
	 */
	public function upgradeTally($itemId, $itemIds, $arrNum = array());
	
	/**
	 * 进阶兵符
	 *
	 * @param int $itemId
	 * @param array $itemIds
	 * @return array
	 * <code>
	 * {
	 * 		item_id:int				物品ID
	 * 		item_template_id:int	物品模板ID
	 * 		item_num:int			物品数量
	 * 		item_time:int			物品产生时间
	 * 		va_item_text:			物品扩展信息
	 * 		{
	 * 			tallyLevel:等级
	 * 			tallyExp:总经验值
	 * 			tallyEvolve:精炼等级
	 * 			tallyDevelop:进阶等级
	 * 		}
	 * }
	 */
	public function developTally($itemId, $itemIds);
	
	/**
	 * 精炼兵符
	 *
	 * @param int $itemId
	 * @param array $itemIds
	 * @return array
	 * <code>
	 * {
	 * 		item_id:int				物品ID
	 * 		item_template_id:int	物品模板ID
	 * 		item_num:int			物品数量
	 * 		item_time:int			物品产生时间
	 * 		va_item_text:			物品扩展信息
	 * 		{
	 * 			tallyLevel:等级
	 * 			tallyExp:总经验值
	 * 			tallyEvolve:精炼等级
	 * 			tallyDevelop:进阶等级
	 * 		}
	 * }
	 */
	public function evolveTally($itemId, $itemIds);
	
	/**
	 *
	 * 随机洗练：银币洗练、金币洗练
	 *
	 * @param int $itemId			物品ID
	 * @param boolean $special		是否使用金币
	 * @return array
	 * <code>
	 * {
	 * 		'ret':boolean			是否成功
	 *		'potence':array			刷新出的数值
	 * }
	 * </code>
	 */
	public function randRefresh($itemId, $special);
	
	/**
	 *
	 * 随机洗练确认
	 *
	 * @param int $itemId			物品ID
	 * @return boolean 
	 */
	public function randRefreshAffirm($itemId);
	
	/**
	 * 固定洗练：
	 * 
	 * @param int $itemId			物品ID
	 * @param int $type				洗练方式,1,2,3,,,
	 * @param int $num				洗练次数，默认是1
	 * @return array 
	 * <code>
	 * {
	 * 		'ret':boolean			是否成功
	 *		'potence':array			刷新出的数值
	 * }
	 * </code>
	 */
	public function fixedRefresh($itemId, $type, $num = 1);
	
	/**
	 *
	 * 固定洗练确认
	 *
	 * @param int $itemId			物品ID
	 * @return boolean 
	 */
	public function fixedRefreshAffirm($itemId);
	
	/**
	 * 获得潜能转移的信息
	 * 
	 * @return array
	 * <code>
	 * {
	 * 		'transfer_num':int		当前已经使用的免费次数
	 *		'transfer_time':int		刷新时间
	 * }
	 * </code>
	 */
	public function getPotenceTransferInfo();
	
	/**
	 * 潜能转移
	 * 
	 * @param int $srcItemId		源物品id
	 * @param int $desItemId		目标物品id
	 * @param int $type				转移方式
	 * @return array
	 * <code>
	 * {
	 * 		'ret':string			转移成功或失败
	 *		'items':array			源物品和目标物品信息
	 *		{
	 *			$srcItemId => $itemInfo
	 *			$desItemId => $itemInfo
	 *		}
	 * }
	 * </code>
	 */
	public function potenceTransfer($srcItemId, $desItemId, $type);
	
	/**
	 * 紫装合成橙装
	 * 
	 * @param int $method	方法id
	 * @param int $itemId	紫装物品id
	 * @return string $ret 结果:'ok'成功,'err'失败
	 */
	public function compose($method, $itemId);
	
	/**
	 * 符印合成
	 * 
	 * @param int $method id
	 * @param array $arrItemId 消耗的物品id
	 * @return string $ret 结果:'ok'成功
	 */
	public function composeRune($method, $arrItemId);
	
	/**
	 * 宝物镶嵌符印
	 * 
	 * @param int $treasItemId	宝物id
	 * @param int $runeItemId	符印id
	 * @param int $index	第几孔,1,2,3,4
	 * @param int $resItemId 	原始宝物id
	 * @return string $ret 结果:'ok'成功,'err'失败
	 */
	public function inlay($treasItemId, $runeItemId, $index, $resItemId = 0);
	
	/**
	 * 宝物卸下符印
	 * 
	 * @param int $itemId
	 * @param int $index
	 * @return string $ret 结果:'ok'成功,'err'失败
	 */
	public function outlay($itemId, $index);
	
	/**
	 * 锁定装备或锦囊
	 * @param int $itemId
	 * @return string $ret 结果:'ok'成功,'err'失败
	 */
	public function lock($itemId);
	
	/**
	 * 解锁装备或锦囊
	 * @param int $itemId
	 * @return string $ret 结果:'ok'成功,'err'失败
	 */
	public function unlock($itemId);
	
	/**
	 * 宝物转换
	 * @param int $itemId 转换前的宝物id
	 * @param int $itemTplId 待转换的宝物模板id
	 * @return int $itemId 转换后的宝物id
	 */
	public function transferTreasure($itemId, $itemTplId);
	
	/**
	 * 兵符转换
	 * 
	 * @param int $itemId 转换前的兵符id
	 * @param int $itemTplId 待转换的兵符模板id
	 * @return int $itemId 转换后的兵符id
	 */
	public function transferTally($itemId, $itemTplId);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */