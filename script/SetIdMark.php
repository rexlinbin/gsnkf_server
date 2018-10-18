<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(wuqilin@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  合服时会从数据中获取某个id(uid,hid,item_id等)的最大值，最小值。
 *  然后根据这些最大值最小值计算偏移，生成新的id文件。
 *  但是像hid这样的id，有部分是放在va中的，所以通过简单sql得到的最大值偏小，最小值偏大
 *  目前最小值偏大的问题可以不处理，但是最大值偏小的问题会导致
 *  1）部分玩家的hid重复
 *  2）合服后生成的新hid偏小，开服后涉及新产生武将的请求可能失败，或者再次导致部分玩家hid重复
 *  
 **/


class SetIdMark extends BaseScript
{

	protected function executeScript($arrOption)
	{
		$hid = IdGenerator::nextId('hid');
		$arrField = array(
			'delete_time' => time(),
		);
		HeroLogic::addNewHero(0, $hid, 20001, $arrField);
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */