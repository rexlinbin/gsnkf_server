<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: IItemInfo.class.php 250248 2016-07-06 09:32:12Z QingYao $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/itemInfo/IItemInfo.class.php $
 * @author $Author: QingYao $(jhd@babeltime.com)
 * @date $Date: 2016-07-06 09:32:12 +0000 (Wed, 06 Jul 2016) $
 * @version $Revision: 250248 $
 * @brief
 *
 **/

interface IItemInfo
{
	/**
	 * 获得装备图鉴
	 * 
	 * @param int $uid  用户id，默认为0是当前用户
	 * @return array
	 * <code>
	 * {
	 *     itemTplId:int	装备模板id
	 * }
	 * </code>
	 */
	public function getArmBook($uid = 0);

	/**
	 * 获得宝物图鉴
	 *
	 * @param int $uid  用户id，默认为0是当前用户
	 * @return array
	 * <code>
	 * {
	 *     itemTplId:int	宝物模板id
	 * }
	 * </code>
	 */
	public function getTreasBook($uid = 0);
	
	/**
	 * 获得神兵图鉴
	 *
	 * @param int $uid  用户id，默认为0是当前用户
	 * @return array
	 * <code>
	 * {
	 *     itemTplId:int	神兵模板id
	 * }
	 * </code>
	 */
	public function getGodWeaponBook($uid = 0);
	
	/**
	 * 获得兵符图鉴
	 *
	 * @param int $uid  用户id，默认为0是当前用户
	 * @return array
	 * <code>
	 * {
	 *     itemTplId:int	兵符模板id
	 * }
	 * </code>
	 */
	public function getTallyBook($uid = 0);
	
	/**
	 * 获得战车图鉴
	 *
	 * @param int $uid  用户id，默认为0是当前用户
	 * @return array
	 * <code>
	 * {
	 *     itemTplId:int	战车模板id
	 * }
	 * </code>
	 */
	public function getChariotBook($uid = 0);
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */