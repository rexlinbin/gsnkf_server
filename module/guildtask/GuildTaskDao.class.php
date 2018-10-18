<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildTaskDao.class.php 114263 2014-06-13 11:29:14Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildtask/GuildTaskDao.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-06-13 11:29:14 +0000 (Fri, 13 Jun 2014) $
 * @version $Revision: 114263 $
 * @brief 
 *  
 **/
class GuildTaskDao
{
	static $guildTaskTable = 't_guildtask';
	public static function getTaskInfo($uid)
	{
		$arrFields = array(
				'uid',
				GuildTaskDef::RESET_TIME,
				GuildTaskDef::REF_NUM,
				GuildTaskDef::TASK_NUM,
				GuildTaskDef::FORGIVE_TIME,
				GuildTaskDef::VA_GUILDTASK,
		);
		$data = new CData();
		$ret = $data->select($arrFields)->from(self::$guildTaskTable)
		-> where( array( 'uid','=',$uid ) )
		->query();
		
		if (empty( $ret ))
		{
			return array();
		}
		return $ret[0];
	}
	
	public static function insertOrUpdate($uid, $valArr)
	{
		$data = new CData();
		$ret = $data->insertOrUpdate( self::$guildTaskTable )->values( $valArr )
		->where('uid','=',$uid)-> query();
		
		if ( $ret[DataDef::AFFECTED_ROWS] <= 0 )
		{
			throw new FakeException( 'nothing change' );
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */