<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IFestival.class.php 157285 2015-02-05 11:04:59Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/festival/IFestival.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-02-05 11:04:59 +0000 (Thu, 05 Feb 2015) $
 * @version $Revision: 157285 $
 * @brief 
 *  
 **/
interface IFestival
{
	/**
	 * 获得合成信息
	 *
	 * @return array
	 * 			[
	 * 				fNumber => num   : 公式号对应的已用合成次数
	 * 			]
	 */
	public function getFestivalInfo();

	/**
	 * 合成
	 *
	 * @param $fNumber int  公式号
	 * @param $num     int  合成几次（不传默认为1）
	 * @return 'ok'
	*/
	public function compose($fNumber, $num);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */