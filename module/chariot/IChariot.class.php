<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $itemId$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chariot/IChariot.class.php $
 * @author $Author: QingYao $(yaoqing@babeltime.com)
 * @date $Date: 2016-07-12 11:16:32 +0000 (Tue, 12 Jul 2016) $
 * @version $Revision: 251288 $
 * @brief 
 *  
 **/
interface IChariot
{
	/**
	 * 装备战车，若有同位置的已装备的别的车则直接将原来的卸下，装上这个
	 * @param unknown $pos位置$itemId战车物品ID
	 * return ok
	 */
	function equip($pos,$itemId);
	
	function unequip($pos,$itemId);

	/**
	 * 强化战车，参数$itemId是对应的物品ID
	 * @param unknown $itemId
	 * return array
	* 	  					{
	* 							item_id:int			物品ID
	* 							item_template_id:int		物品模板ID
	 *							item_num:int			物品数量
	* 							item_time:int			物品产生时间
	* 							va_item_text:			物品扩展信息
	* 							{
	* 											chariotEnforce=>1      //强化等级
	* 											chariotDevelop=>1       //进阶等级
	* 							}
	 *	  					}
	 */
	function enforce($itemId,$addLv=1);
	
	/**
	 * 分解战车，参数$itemArr是对应的物品ID数组
	 * @param unknown $itemArr
	 * return array(         //返回分解获得的物品信息
	 * 				'item'=>array(itemId=>itemNum,)
	 * )
	 */
	function resolve($itemArr);
	function previewResolve($itemArr);
	
	/**
	 * 重生战车，策划定义的重生其实就是重置。。。参数$itemId是对应的物品ID
	 * @param unknown $itemId
	 * return okreturn array(
	 * 								'silver'=>$getSilver,	
	 * 								'item'=>$getItem,
	 *		);
	 */
	function reborn($itemId);
	function previewReborn($itemId);
	/**
	 * 战车进阶，参数$itemId是对应的物品ID
	 * @param unknown $itemId
	 */
	function develop($itemId);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */