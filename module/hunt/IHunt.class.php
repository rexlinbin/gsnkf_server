<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IHunt.class.php 218124 2015-12-28 07:59:38Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hunt/IHunt.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-12-28 07:59:38 +0000 (Mon, 28 Dec 2015) $
 * @version $Revision: 218124 $
 * @brief 
 *  
 **/
interface IHunt
{
	/**
	 * 获得用户的猎魂信息
	 * 
	 * @return int $place 场景id
	 */
	public function getHuntInfo();
	
	/**
	 * 直接跳转到特定场景
	 * 
	 * @param int $type 类型:0物品,1金币,默认值0
	 * @return array
	 * <code>
	 * {
	 * 		'item':战魂数组
	 * 		{
	 * 			$itemId => $itemTplId
	 * 		}
	 * 		'extra':额外掉落
	 * 		{
	 * 			$itemTplId => $num
	 * 		}
	 * 		'place':下一个场景id
	 * }
	 * </code>
	 */
	public function skip($type = 0);
	
	/**
	 * 跳转猎魂
	 * 
	 * @param int $num 次数：默认值10
	 * @return array
	 * <code>
	 * {
	 * 		'item':战魂数组
	 * 		{
	 * 			$id 第N次
	 * 			{
	 * 				$itemId => $itemTplId
	 * 			}
	 * 		}
	 * 		'material':材料
	 * 		{
	 * 			$itemTplId => $num
	 * 		}
	 * 		'extra':额外掉落
	 * 		{
	 * 			$itemTplId => $num
	 * 		}
	 * 		'place':下一个场景id
	 * 		'silver':花费银币
	 * }
	 * </code>
	 */
	public function skipHunt($num = 10);
	
	/**
	 * 猎魂
	 * 
	 * @param int $num 次数：默认值1
	 * @return array
	 * <code>
	 * {
	 * 		'item':战魂数组
	 * 		{
	 * 			$itemId => $itemTplId
	 * 		}
	 * 		'material':材料
	 * 		{
	 * 			$itemTplId => $num
	 * 		}
	 * 		'place':下一个场景id
	 * 		'silver':花费银币
	 * 		'white':白色战魂个数
	 * 		'green':绿色战魂个数
	 * 		'blue':蓝色战魂个数
	 * 		'purple':紫色战魂个数
	 * 		'exp':经验
	 * }
	 * </code>
	 */
	public function huntSoul($num = 1);
	
	/**
	 * 极速猎魂
	 * 
	 * @param int $type 类型1,2,3
	 * @param array $arrQuality 保留的品质，默认空不保留
	 * @return array
	 * <code>
	 * {
	 * 		'item':战魂数组
	 * 		{
	 * 			$itemId => $itemTplId
	 * 		}
	 * 		'material':材料
	 * 		{
	 * 			$itemTplId => $num
	 * 		}
	 * 		'place':下一个场景id
	 * 		'silver':花费银币
	 * 		'fs_exp':战魂经验
	 * }
	 * </code>
	 */
	public function rapidHunt($type, $arrQuality = array());
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */