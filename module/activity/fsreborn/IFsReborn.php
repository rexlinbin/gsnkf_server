<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IFsReborn.php 200753 2015-09-28 06:20:54Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/fsreborn/IFsReborn.php $
 * @author $Author: MingTian $(zhengguohao@babeltime.com)
 * @date $Date: 2015-09-28 06:20:54 +0000 (Mon, 28 Sep 2015) $
 * @version $Revision: 200753 $
 * @brief 
 *  
 **/
interface IFsReborn
{
	/**
	 * 获得信息
	 * 
	 * @return array
	 * <code>
	 * {
	 * 		'num':int 重生次数
	 * }
	 * </code>
	 */
	public function getInfo();
	
	/**
	 * 重生战魂
	 * 
	 * @param int $itemId 物品id
	 * @return array
	 * <code>
	 * {
	 * 		'silver':int 重生次数
	 * 		'exp':int 经验
	 * 		'item':array 战魂数组
	 * 		{
	 * 			$itemId => $itemTplId
	 * 		}
	 * }
	 * </code>
	 */
	public function reborn($itemId);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */