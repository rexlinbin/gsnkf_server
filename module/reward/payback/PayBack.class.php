<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PayBack.class.php 137321 2014-10-23 08:08:22Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/payback/PayBack.class.php $
 * @author $Author: TiantianZhang $(yangwenhai@babeltime.com)
 * @date $Date: 2014-10-23 08:08:22 +0000 (Thu, 23 Oct 2014) $
 * @version $Revision: 137321 $
 * @brief 
 *  
 **/

class PayBack 
{
	
	/**
	 * @see ServerProxy::addPayBackInfo
	 */
	public function addPayBackInfo($startime, $endtime, $arrypayback)
	{
		if (empty($arrypayback))
		{
			return false;
		}
		if (!PaybackLogic::checkTimeValidate($startime,$endtime,"PayBack.addPayBackInfo"))
		{
			return false;
		}

	   return PaybackLogic::insertPayBackInfo($startime, $endtime, $arrypayback);
	}
	
	/**
	 * @see ServerProxy::modifyPayBackInfo
	 */
	public function modifyPayBackInfo($startime,$endtime,$arrypayback)
	{
		if (empty($arrypayback))
		{
			return false;
		}
		if (!PaybackLogic::checkTimeValidate($startime,$endtime,"PayBack.modifyPayBackInfo"))
		{
			return false;
		}
		return PaybackLogic::updatePayBackInfo($startime, $endtime, $arrypayback);
	}
	
	/**
	 * @see ServerProxy::getPayBackInfoByTime
	 */
	public function  getPayBackInfoByTime($timestart,$timeend)
	{
		$retAry=array();
		if (!PaybackLogic::checkTimeValidate($timestart,$timeend,"PayBack.getPayBackInfoByTime"))
		{
			return $retAry;
		}
		return PaybackLogic::getPayBackInfoByTime($timestart, $timeend);
	}
	
	public function getArrPayBackInfoByTime($timeStart,$timeEnd)
	{
	    $retAry=array();
	    if (!PaybackLogic::checkTimeValidate($timeStart,$timeEnd,"PayBack.getArrPayBackInfoByTime"))
	    {
	        return $retAry;
	    }
	    return PaybackLogic::getArrPayBackInfoByTime($timeStart, $timeEnd);
	}
	
	
	/**
	 * 开启某个补偿
	 * @param int $paybackid
	 * @return bool
	 */
	public function openPayBackInfo($paybackid)
	{
		//检查id
		if (Empty($paybackid)||$paybackid <= 0)
		{
			Logger::FATAL('PayBack.openPayBackInfo invalid paybackid:%d', $paybackid);
			return  false;
		}
		return PaybackLogic::setPayBackOpenStatus($paybackid, 1);
	}
	/**
	 * 关闭某个补偿
	 * @param int $paybackid
	 * @return bool
	 */
	public function closePayBackInfo($paybackid)
	{
		//检查id
		if (Empty($paybackid)||$paybackid <= 0)
		{
			Logger::FATAL('PayBack.closePayBackInfo invalid paybackid:%d', $paybackid);
			return  false;
		}
		return PaybackLogic::setPayBackOpenStatus($paybackid, 0);
	}
	
	/**
	 * 检查某个补偿是不是开启的
	 * @param int $paybackid
	 * @return bool
	 */
	public function isPayBackInfoOpen($paybackid)
	{
		//检查id
		if (Empty($paybackid)||$paybackid <= 0)
		{
			Logger::FATAL('PayBack.isPayBackInfoOpen invalid paybackid:%d', $paybackid);
			return  false;
		}
		$ret=PaybackLogic::getPayBackOpenStatus($paybackid);
		return  $ret[PayBackDef::PAYBACK_SQL_IS_OPEN] > 0?true:false;
	}
	

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */