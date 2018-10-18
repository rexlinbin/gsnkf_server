<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Framework.def.php 80342 2013-12-11 10:41:23Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Framework.def.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-12-11 10:41:23 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80342 $
 * @brief
 *
 **/

class FrameworkDef
{

	const HEAD_SIZE = 8;

	const AMF_AMF3 = PHP_AMF3_PREFIX;
}

/**
 * 请求类型
 * @author hoping
 *
 */
class RequestType
{

	/**
	 *
	 * @var unknown_type
	 */
	const RELEASE = 1;

	const DEBUG = 2;
}

/**
 * 请求类型（另外一种分类方式）
 * @author wuqilin
 */
class RequestMethodType
{
	/**
	 * 前端发过来的公共请求
	 */
	const E_PUBLIC = 1;

	/**
	 * 私有请求
	 */
	const E_PRIVATE = 2;

	/**
	 * 通过lcserver中team.excute串化的请求
	 */
	const E_SERIALIZE = 3;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
