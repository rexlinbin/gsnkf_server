<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TravelShopObj.class.php 198215 2015-09-11 12:12:26Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/travelshop/TravelShopObj.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-09-11 12:12:26 +0000 (Fri, 11 Sep 2015) $
 * @version $Revision: 198215 $
 * @brief 
 *  
 **/
class TravelShopObj
{
	private $info = NULL;							// 修改数据
	private $infoBak = NULL; 						// 原始数据
	private static $instance = Null;				// 单例
	
	/**
	 * 获取本类的实例
	 *
	 * @return TravelShopObj
	*/
	public static function getInstance()
	{
		if (empty(self::$instance))
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	public static function release()
	{
		self::$instance = NULL;
	}
	
	private function __construct()
	{
		$info = TravelShopDao::select();
		if (empty($info))
		{
			$info = $this->init();
		}
		$this->info = $info;
		$this->infoBak = $info;
		$this->refresh();
	}
	
	public function init()
	{
		$arrField = array(
				TravelShopDef::FIELD_ID => 1,
				TravelShopDef::FIELD_SUM => 0,
				TravelShopDef::FIELD_REFRESH_TIME => Util::getTime(),
		);
		
		TravelShopDao::insertOrUpdate($arrField);
	
		return $arrField;
	}
	
	public function refresh()
	{
		$refreshTime = $this->getRefreshTime();
		if (!TravelShopLogic::isInCurRound($refreshTime)) 
		{
			$this->info = $this->init();
		}
	}
	
	public function getSum()
	{
		return $this->info[TravelShopDef::FIELD_SUM];
	}
	
	public function setSum($num)
	{
		$this->info[TravelShopDef::FIELD_SUM] = $num;
	}
	
	public static function addSum($num)
	{
		$opSum = new IncOperator($num);
		$arrField = array(
				TravelShopDef::FIELD_SUM => $opSum,
		);
	
		TravelShopDao::update($arrField);
	}
	
	public function getRefreshTime()
	{
		return $this->info[TravelShopDef::FIELD_REFRESH_TIME];
	}
	
	public function update()
	{
		if ($this->info != $this->infoBak)
		{
			TravelShopDao::update($this->info);
			$this->infoBak = $this->info;
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */