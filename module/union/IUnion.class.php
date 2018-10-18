<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IUnion.class.php 241839 2016-05-10 07:35:50Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/union/IUnion.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-05-10 07:35:50 +0000 (Tue, 10 May 2016) $
 * @version $Revision: 241839 $
 * @brief 
 *  
 **/
interface IUnion
{
	/**
	 * 登录时获取信息
	 * 
	 * @return array 
	 * <code>
	 * {
	 * 		'union':array
	 * 		{
	 * 			$unionId
	 * 		}
	 * 		'attr':array
	 * 		{
	 * 			$attrId => $attrValue
	 * 		}
	 * 		'func':array
	 * 		{
	 * 			$type => 
	 * 			{
	 * 				$id
	 * 			}
	 * 		}
	 * }
	 * </code>
	 */
	public function getInfoByLogin();
	
	/**
	 * 获取详细信息
	 * 
	 * @return array 
	 * <code>
	 * {
	 * 		'uid':int
	 * 		'va_fate':array
	 * 		{
	 * 			'lists':array
	 * 			{
	 * 				$id:array
	 * 				{
	 * 					$htid
	 * 					$itemTplId
	 * 				}
	 * 			}
	 * 		}
	 * 		'va_loyal':array
	 * 		{
	 * 			'lists':array
	 * 			{
	 * 				$id:array
	 * 				{
	 * 					$htid
	 * 				}
	 * 			}
	 * 		}
	 * 		'va_martial':array
	 * 		{
	 * 			'lists':array
	 * 			{
	 * 				$id:array
	 * 				{
	 * 					$htid
	 * 					$itemTplId
	 * 				}
	 * 			}
	 * 		}
	 * }
	 * </code>
	 */
	public function getInfo();
	
	/**
	 * 镶嵌卡牌
	 * 
	 * @param int $id 对应CSV的id
	 * @param int $aimId 武将id或物品id
	 * @param int $isHero 0物品1武将,默认1
	 * @param int $type 0缘分堂1忠义堂2演武堂,默认0
	 * @return string 'ok'
	 */
	public function fill($id, $aimId, $isHero = 1, $type = 0);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */