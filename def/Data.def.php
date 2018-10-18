<?php

/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Data.def.php 45099 2013-04-27 10:59:23Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Data.def.php $
 * @author $Author: wuqilin $(jhd@babeltime.com)
 * @date $Date: 2013-04-27 10:59:23 +0000 (Sat, 27 Apr 2013) $
 * @version $Revision: 45099 $
 * @brief
 *
 **/

define ( 'MYSQLI_OPT_READ_TIMEOUT', 11 );
define ( 'MYSQLI_OPT_WRITE_TIMEOUT', 12 );

class DataDef
{
	const DELETED = 0;

	const NORMAL = 1;

	const MAX_FETCH = 100;

	const AFFECTED_ROWS = "affected_rows";

	const INSERT_ID = "last_insert_id";

	const COUNT = "count";
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
