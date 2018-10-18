<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnRechargeGift.class.php 207298 2015-11-04 11:29:34Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/rechargegift/EnRechargeGift.class.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2015-11-04 11:29:34 +0000 (Wed, 04 Nov 2015) $
 * @version $Revision: 207298 $
 * @brief 
 *  
 **/
class EnRechargeGift
{
	// 充值送礼配置解析
	/**
	 * 解析后的格式:
	 * <code>
	 * array
	 * {
	 * 		rewardId => 
	 * 		array
	 * 		{
	 * 			'expenseGold' 	=> int,	累计充值金币额度
	 * 			'unSelectRewardArr'	=> 奖励如果是不可选类型,奖励字段是数组结构,key从0开始:array
	 * 								[	
	 * 									奖励三元组array(),
	 * 									……
	 * 								]
	 * 			'selectRewardArr'	=> 奖励如果是可选类型,奖励字段是map结构,key从1开始:array
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
	public static function readRechargeGiftCsv($arr)
	{
		$confList = array();
		$tmpRewardConf = array();
		$rewardConf = array();
		foreach ($arr as $data)
		{
			if (empty($data) || empty($data[0]))
			{
				break;
			}
			$index = 0;
			$rewardId = intval($data[$index++]);
			$confList[$rewardId][RechargeGiftDef::REQ_GOLD] = intval($data[$index++]);
			$rewardArr = $data[$index++];
			$tmpRewardConf = Util::str2Array($rewardArr, ',');
			$confType = intval($data[$index]);
			$isSelect = false;
			foreach ($tmpRewardConf as $key => $val)
			{
				if (self::SELECT_TYPE == $confType)
				{
					$key = $key + 1;
					$isSelect ? : $isSelect = true;
				}
				$rewardConf[$key] = Util::array2Int(Util::str2Array( $val , '|'));
			}
			if ($isSelect)
			{
				$confList[$rewardId][RechargeGiftDef::SELECT_REWARD] = $rewardConf;
			}
			else 
			{
				$confList[$rewardId][RechargeGiftDef::UNSELECT_REWARD] = $rewardConf;
			}
			// 新一轮循环把奖励数组置为空,以免上一次数组型的结构污染下一次map型的结构
			$rewardConf = array();
		}
		return $confList;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */