<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Formation.def.php 202860 2015-10-16 12:53:44Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Formation.def.php $
 * @author $Author: BaoguoMeng $(lanhongyu@babeltime.com)
 * @date $Date: 2015-10-16 12:53:44 +0000 (Fri, 16 Oct 2015) $
 * @version $Revision: 202860 $
 * @brief 
 *  
 **/

class FormationDef
{

	const SESSION_KEY_FORMATION = 'formation.formation';
	
	
	const FORMATION_SIZD = 6;
	const EXTRA_SIZD = 10;
	const ATTR_EXTRA_SIZE = 3;
	
	const HERO_TYPE_FORMATION      = 1;   // 阵上的武将
	const HERO_TYPE_ATTR_FRIEND	   = 2;	  // 助战军
	const HERO_TYPE_LITTLE_FRIEND  = 3;	  // 小伙伴
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */