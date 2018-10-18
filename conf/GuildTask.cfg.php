<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildTask.cfg.php 115060 2014-06-17 09:55:52Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/GuildTask.cfg.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-06-17 09:55:52 +0000 (Tue, 17 Jun 2014) $
 * @version $Revision: 115060 $
 * @brief 
 *  
 **/
class GuildTaskConf
{
	//const TAR_TASK_NUM =3;
	const TASK_ACCEPT_NUM = 1;
}

class GuildTaskType
{
	//前端贡献的种类
	const HANDIN_ARM = 1;
	const HANDIN_TREASURE = 2;
	const HANDIN_PROP = 3;
	
	//后端调用模块
	const BASE = 4;//攻打某个副本$detailId 为据点id（策划说现在只有普通副本的）
	const RUIN_CITY = 5;//破坏城防$detailId 为破坏城防的等级
	const MEND_CITY = 6;//修复城防$detailId 为修复城防的等级
	
	
	//前端因为要跳转，需要分开，我希望合起来，自己合吧
	const HANDIN_ITEM = 101;
	static $handItem = array(
			self::HANDIN_ARM,
			self::HANDIN_TREASURE,
			self::HANDIN_PROP
	);
	
	static $typeBagArr = array(
			self::HANDIN_ARM => BagDef::BAG_ARM,
			self::HANDIN_TREASURE => BagDef::BAG_TREAS,
			self::HANDIN_PROP => BagDef::BAG_PROPS,
	);
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */