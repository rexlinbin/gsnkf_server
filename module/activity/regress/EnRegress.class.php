<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnRegress.class.php 132247 2014-09-15 11:31:56Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/regress/EnRegress.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-09-15 11:31:56 +0000 (Mon, 15 Sep 2014) $
 * @version $Revision: 132247 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

class EnRegress
{
	public static function readRegressCSV($arrData)
	{
		$confList = array();
		foreach ($arrData as $key => $data)
		{
			if (empty($data) || empty($data[0]))
			{
				break;
			}
			
			$index = 0;
			$id = intval($data[$index++]);
			$confList[$id]['createtime'] = intval($data[$index++]);
			$confList[$id]['offline'] = intval($data[$index++]);
			$confList[$id]['offreward'] = array();
			$arr = str2array($data[$index++]);
			foreach($arr as $value)
			{
				$confList[$id]['offreward'][] = array2Int(str2Array($value, '|'));
			}
			$confList[$id]['reward'] = array();
			$arr = str2array($data[$index++]);
			foreach($arr as $value)
			{
				$confList[$id]['reward'][] = array2Int(str2Array($value, '|'));
			}
		}
		
		return $confList;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */