<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Fragseize.def.php 209995 2015-11-17 02:27:12Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Fragseize.def.php $
 * @author $Author: ShijieHan $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-11-17 02:27:12 +0000 (Tue, 17 Nov 2015) $
 * @version $Revision: 209995 $
 * @brief 
 *  
 **/
class FragseizeDef
{
	const FRAG_ID = 'frag_id';
	const FRAG_NUM = 'frag_num';
	const SEIZE_NUM = 'seize_num';
	
	const FRAG_LOKER_PRE = 'frag_locker_';
	
	const WHITE_END_TIME = 'white_flag_time';
	
	const FIRST_TIME = 'first_time';
	
	const WHITE_BYGOLD = 1;
	const WHITE_BYITEM = 2;

	const STAMINA_ITEM_TEMPLATE_ID = 10042; //耐力丹
	const MAX_SEIZE_NUM_ONCE = 35;	//单次一键夺宝最大抢夺次数
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */