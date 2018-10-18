<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Potence.def.php 84127 2014-01-01 09:11:39Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Potence.def.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-01-01 09:11:39 +0000 (Wed, 01 Jan 2014) $
 * @version $Revision: 84127 $
 * @brief 
 *  
 **/

class PotenceDef
{
	//常量
	const POTENCE_PERCENT_MODULUS						=				10000;

	const POTENCE_ID									= 				'potenceId';
	const POTENCE_LIST									=				'potenceList';
	const POTENCE_LIST_NUM								=				'potenceListNum';
	const POTENCE_TYPE_NUM								=				'potenceTypeNum';
	const POTENCE_TYPE_WEIGHT							=				'weight';
	const POTENCE_TYPE_NUM_LIST							=				'potenceTypeNumList';

	const POTENCE_ATTR_ID								=				'potenceAttrId';
	const POTENCE_ATTR_WEIGHT							= 				'weight';
	const POTENCE_ATTR_VALUE							=				'potenceAttrValue';
	const POTENCE_VALUE_ADJUST							=				'potenceValueAdjust';
	const POTENCE_VALUE_ADD								=				'potenceValueAdd';
	const POTENCE_VALUE_MODIFY							=				'potenceValueModify';
	const POTENCE_VALUE_COST							=				'potenceValueCost';
	const POTENCE_REFRESH_TYPE							=				'potenceRefreshType';
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */