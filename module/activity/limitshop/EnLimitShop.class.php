<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnLimitShop.class.php 147617 2014-12-20 06:30:07Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/limitshop/EnLimitShop.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2014-12-20 06:30:07 +0000 (Sat, 20 Dec 2014) $
 * @version $Revision: 147617 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Mall.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/LimitShop.def.php";

class EnLimitShop
{
	//限时商店解析函数
	public static function readLimitShopCSV($arrData)
	{
		$ZERO = 0;
		$arrConfKey = array(
				'goodsId' => $ZERO,
				LimitShopDef::REFRESH_DAY => ++$ZERO,
				LimitShopDef::ITEMS => ++$ZERO,
				LimitShopDef::NOW_COST => ($ZERO += 5),
				LimitShopDef::LIMIT_VIP => ++$ZERO,
				LimitShopDef::LIMIT_NUM => ++$ZERO,
				
		);
		
		$confList = array();
		
		foreach ($arrData as $data)
		{
			if (empty($data) || empty($data[0]))
			{
				break;
			}
			
			$conf = array();
			
			foreach ($arrConfKey as $key => $index)
			{
				switch ($key)
				{
					case LimitShopDef::ITEMS:
						$conf[$key] = array();
						$conf[$key] = array_map('intval', Util::str2Array($data[$index],'|'));
						break;
					case LimitShopDef::REFRESH_DAY:
						$conf[$key] = array();
						$conf[$key] = Util::str2Array($data[$index], ',');
						break;
					default:
						$conf[$key] = intval($data[$index]);
				}
			}
			
			$goodsId = $conf['goodsId'];
			unset($conf['goodsId']);
			$confList[$goodsId] = $conf;
		}
		
		$newConfList  = array();
		
		foreach ($confList as $goodsId => $index)
		{
			$temp = $index;
			unset($temp[LimitShopDef::REFRESH_DAY]);
			
			$day = $index[LimitShopDef::REFRESH_DAY];
			foreach ( $day as $value )
			{
				$newConfList[LimitShopDef::LIMITSHOP_DAY_INFO][$value][$goodsId] = $temp;
			}
		}
		
		return $newConfList;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */