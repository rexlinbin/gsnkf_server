<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(pengnana@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/
interface IBlackshop
{
	/**
	 * 获取玩家已兑换次数
	 * @return 
	 *  [id] => [num] => int		.
	 * */
	public function getBlackshopInfo();
	/**
	 * 兑换物品
	 * @param  id  int 兑换的配置id
	 *         num int  兑换个数
 	 * @return   'ok'   
	 * */
	public function exchangeBlackshop($id,$num);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */