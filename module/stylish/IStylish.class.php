<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IStylish.class.php 242914 2016-05-16 08:08:39Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/stylish/IStylish.class.php $
 * @author $Author: MingTian $(pengnana@babeltime.com)
 * @date $Date: 2016-05-16 08:08:39 +0000 (Mon, 16 May 2016) $
 * @version $Revision: 242914 $
 * @brief 
 *  
 **/
interface IStylish
{
	/**
	 * 获取称号信息
	 * 
	 * @return array
	 * <code>
	 * {
	 * 		'title'
	 * 		{
	 * 			$id => $deadline 激活的称号id=>截止时间，截止时间为0表示非限时称号
	 * 		}
	 * }
	 * </code>
	 */
	public function getStylishInfo();
	
	/**
	 * 激活称号
	 * @param int $id 称号id
	 * @param int $itemId 消耗物品id
	 * @param int $itemNum 消耗的物品数量
	 * @return string 'ok'
	 */
	public function activeTitle($id, $itemId, $itemNum);
	
	/**
	 * 设置称号
	 * @param int $id 称号id
	 * @return string 'ok'
	 */
	public function setTitle($id);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */