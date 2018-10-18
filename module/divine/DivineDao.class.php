<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DivineDao.class.php 60744 2013-08-22 06:11:46Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/divine/DivineDao.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-08-22 06:11:46 +0000 (Thu, 22 Aug 2013) $
 * @version $Revision: 60744 $
 * @brief 
 *  
 **/
class DivineDao
{
	
	
	static function getDiviInfo($uid)
	{
		$where = array("uid", "=", $uid);
		$fields = DivineDef::$DIVI_FIELDS;
		$data = new CData();
		$arrRet = $data->select($fields)
			               ->from(DivineDef::$TBL)
						   ->where($where)->query();
			if (isset($arrRet[0]))
			{
				return $arrRet[0];
			}
			return array();	
		}
		
		
		static function addNewDivine( $valueArr )
		{	
			$data = new CData();
			$data->insertOrUpdate(DivineDef::$TBL)->values($valueArr)->query();
		}
		
		
		static function updateDiviInfo($uid, $updateArr)
		{
			$where = array('uid', '=', $uid);
			$data = new CData();
			$arrRet = $data->update(DivineDef::$TBL)
			               ->set($updateArr)
			               ->where($where)->query();
			               
			return $arrRet;
		}	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */