<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ILordwarMall.class.php 171553 2015-05-07 08:47:13Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/lordwarshop/ILordwarMall.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-05-07 08:47:13 +0000 (Thu, 07 May 2015) $
 * @version $Revision: 171553 $
 * @brief 
 *  
 **/
interface ILordwarShop
{
	/**
	 * array
	 * {
	 * 	goodId => num
	 * }
	 * 
	 */
	function getInfo();
	
	function buy( $goodId, $num );
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */