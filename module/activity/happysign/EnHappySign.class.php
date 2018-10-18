<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnHappySign.class.php 232270 2016-03-11 08:49:59Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/happysign/EnHappySign.class.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2016-03-11 08:49:59 +0000 (Fri, 11 Mar 2016) $
 * @version $Revision: 232270 $
 * @brief 
 *  
 **/
class EnHappySign
{
	// 欢乐签到配置解析
	/**
	 * 解析后的格式:
	 * <code>
	 * array
	 * {
	 * 		rewardId => 
	 * 		array
	 * 		{
	 * 			'requireDayNum' => int,	活动期间内累计登陆天数
	 * 			'unSelectRewardArr'	=> 奖励如果是不可选类型,奖励字段是数组结构,key从0开始:array
	 * 								[	
	 * 									奖励三元组array(),
	 * 									……
	 * 								]
	 * 			'selectRewardArr' 	=> 奖励如果是可选类型,奖励字段是map结构,key从1开始:array
	 * 								{	
	 * 									selectId => 奖励三元组array(),
	 * 									……
	 * 								}
	 * 		}	
	 * }
	 * </code>
	 * 
	 * notice : 一个rewardId内'unSelectRewardArr'与'selectRewardArr'字段只有其中一个,如果2个都有则是解析错误
	 */
	const SELECT_TYPE = 2;
	public static function readHappySignCsv($arr)
	{
		$confList = array();
		$tmpRewardConf = array();
		$rewardConf = array();
		if (empty($arr) || empty($arr[0]) || empty($arr[0][0]))
		{
			return array();
		}
		$columns = count($arr[0]);
		foreach ($arr as $data)
		{
			if (empty($data) || empty($data[0]))
			{
				break;
			}
			$index = 0;
			$rewardId = intval($data[$index]);
			$index += 3;
			$confList[$rewardId][HappySignDef::REQ_DAY] = intval($data[$index]);
			$rewardArr = $data[++$index];
			$tmpRewardConf = Util::str2Array($rewardArr, ',');
			$confType = intval($data[++$index]);
			// 改版前的配置字段是6个,新版的是7个,此处做解析兼容,为了在版本过渡时测试
			if (7 == $columns)
			{
				$confList[$rewardId][HappySignDef::COST] = intval($data[++$index]);
			}
			$isSelect = false;
			foreach ($tmpRewardConf as $key => $val)
			{
				if (self::SELECT_TYPE == $confType)
				{
					$isSelect ? : $isSelect = true;
					$key = $key + 1;
				}
				$rewardConf[$key] = Util::array2Int(Util::str2Array( $val , '|'));
			}
			if ($isSelect)
			{
				$confList[$rewardId][HappySignDef::SELECT_REWARD] = $rewardConf;
			}
			else
			{
				$confList[$rewardId][HappySignDef::UNSELECT_REWARD] = $rewardConf;
			}
			// 新一轮循环把奖励数组置为空,以免上一次数组型的结构污染下一次map型的结构
			$rewardConf = array();
		}
		return $confList;
	}
	
	public static function updateSignTime($uid)
	{
		if (!EnActivity::isOpen(ActivityName::HAPPYSIGN))
		{
			return;
		}
		HappySignManager::getInstance($uid);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */