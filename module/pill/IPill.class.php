<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IPill.class.php 245032 2016-06-01 10:47:36Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pill/IPill.class.php $
 * @author $Author: QingYao $(yaoqing@babeltime.com)
 * @date $Date: 2016-06-01 10:47:36 +0000 (Wed, 01 Jun 2016) $
 * @version $Revision: 245032 $
 * @brief 
 *  
 **/
interface IPill
{
	/**
	 * 合成丹药
	 * @param int $index      //合成的index,对应的是normall_config表里的物品的index
	 * @param int $isAll       //是否是全部合成
	 * @return 'err';//物品不足
	 * @return array(
	 *				'itemTmpId'=>60129,
	 *				'itemNum'=>1;
	 *				)
	 */
	public function fuse($index,$isAll);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */