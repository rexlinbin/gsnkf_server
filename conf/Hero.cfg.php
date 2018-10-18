<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: Hero.cfg.php 190753 2015-08-13 03:10:08Z BaoguoMeng $$
 * 
 **************************************************************************/

/**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Hero.cfg.php $$
 * @author $$Author: BaoguoMeng $$(lanhongyu@babeltime.com)
 * @date $$Date: 2015-08-13 03:10:08 +0000 (Thu, 13 Aug 2015) $$
 * @version $$Revision: 190753 $$
 * @brief 
 *  
 **/
class HeroConf
{
	
	/**
	 * 武将最大个数
	 */
	const INC_RATIO = 100;
	
	const FIGHT_FORCE_PLUS_SW    =    15000;
	
	const SANWEI_RATIO = 100;
	
	const MAX_HERO_NUM = 2000;
	
	const MIN_FIGHT_FORCE = 5;//最小战斗力数值  如果计算出来的战斗力<5  赋值为5
	//只有四五星武将可以被分解
	public static $RESOLVED_HERO_STARLV = array(4,5);
	
	/**
	 * 修正武将战斗力的一个系数：pvp系数
	 */
	const PVP_REFER_COEF = 5099;
}    


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
