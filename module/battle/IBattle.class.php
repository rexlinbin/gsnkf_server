<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IBattle.class.php 103847 2014-04-24 14:36:54Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/battle/IBattle.class.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2014-04-24 14:36:54 +0000 (Thu, 24 Apr 2014) $
 * @version $Revision: 103847 $
 * @brief 战斗模块
 *
 *
 **/
interface IBattle
{

	/**
	 * 普通pvp战斗
	 * @param array $arrFormation1
	 * @param array $arrFormation2
	 * @param array $arrExtra 额外参数
	 * <code>
	 * {
	 * 		teamName1:名称1
	 *		teamName2:名称2
	 * 		teamLevel1:等级1
	 * 		teamLevel2:等级2
	 * 		dlgId:对话id
	 * 		dlgRound:第几回出对话
	 * 		bgid:背景id
	 * 		musicId:音乐id
	 * 		type:类型,参考BattleType
	 * }
	 * </code>
	 * @see BattleType
	 * @see IBattle::pvp()
	 */
	function test($arrFormation1, $arrFormation2, $arrExtra = array());

	
	
	
	/**
	 * 根据战斗记录签名获取战斗录相
	 * @param int $brid
	 * @return string 战斗录相  返回值被base64encode过
	 */
	function getRecord($brid);
	
	
	/**
	 * 根据战斗记录签名获取战斗录相
	 * @param int $brid
	 * @return string 战斗录相 返回值没有base64encode，是直接的amf编码串
	 */
	function getRecordRaw($brid);
	
	/**
	 * 获取组队战战报
	 * @param int $brid
	 */
	public function getMultiRecord($brid);

	/**
	 * 战报录相，如果访问一次会将这个战报标记为永久
	 * @param int $brid
	 */
	function getRecordForWeb($brid);

	/**
	 * 获取录相的url
	 * @param int $brid
	 * @return string
	 */
	function getRecordUrl($brid);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */