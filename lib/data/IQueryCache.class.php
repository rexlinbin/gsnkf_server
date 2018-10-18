<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IQueryCache.class.php 38034 2013-02-04 08:42:14Z HaopingBai $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/lib/data/IQueryCache.class.php $
 * @author $Author: HaopingBai $(hoping@babeltime.com)
 * @date $Date: 2013-02-04 16:42:14 +0800 (周一, 04 二月 2013) $
 * @version $Revision: 38034 $
 * @brief 本地查询缓存，用于简化逻辑代码，不用每个模块都维护自己的缓存
 *
 **/
interface IQueryCache
{

	/**
	 * 在查询之间先到查询缓存中进行查询
	 * @param array $arrData 要查询的请求
	 * @param bool $moreResult 是否还有结果需要查询
	 * @return array 从缓存中查出来的数据
	 */
	function beforeQuery(&$arrData, &$moreResult);

	/**
	 * 更新数据库后得到的结果来更新查询缓存
	 * @param array $arrData 要查询的请求
	 * @param array $arrRet 要更新的缓存数据
	 */
	function afterQuery($arrData, $arrRet);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */