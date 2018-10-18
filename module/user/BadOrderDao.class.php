<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BadOrderDao.class.php 157009 2015-02-04 13:12:00Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/BadOrderDao.class.php $
 * @author $Author: wuqilin $(wuqilin@babeltime.com)
 * @date $Date: 2015-02-04 13:12:00 +0000 (Wed, 04 Feb 2015) $
 * @version $Revision: 157009 $
 * @brief 
 *  
 **/

class BadOrderDao
{
	const tblBadOrder = 't_bad_order';
	
	
	public static function getSumNeedSubNumOfUid($uid)
	{
		$data = new CData();
		$ret = $data->select(array('sum(sub_num)'))
					->from(self::tblBadOrder)
					->where('uid', '=', $uid)
					->where('status', '=', 1)
					->query();
		
		$sum = 0;
		if (!empty($ret[0]['sum(sub_num)']))
		{
			$sum = $ret[0]['sum(sub_num)'];
		}
		return $sum;
	}
	
	public static function getArrBadOrderByUid($uid, $arrField)
	{
		$data = new CData();
		$ret = $data->select($arrField)
					->from(self::tblBadOrder)
					->where('uid', '=', $uid)
					->where('status', '=', 1)
					->query();
		return $ret;
	}
	
	public static function insertBadOrder($uid, $orderId, $goldNum, $needSubGoldNum)
	{
		$arrField = array(
					'order_id' => $orderId, 
					'uid' => $uid,
					'gold_num'=> $goldNum, 
					'sub_num'=> $needSubGoldNum, 
					'set_time' => Util::getTime(),
					'status'=> 1
		);

		$data = new CData();
		$data->insertInto(self::tblBadOrder)
				->values($arrField)
				->query();
	}
	

	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */