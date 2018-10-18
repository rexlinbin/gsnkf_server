<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PrepareMassData.php 155792 2015-01-28 13:09:18Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pass/test/PrepareMassData.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-01-28 13:09:18 +0000 (Wed, 28 Jan 2015) $
 * @version $Revision: 155792 $
 * @brief 
 *  
 **/
class PrepareMassData extends BaseScript
{
	protected function executeScript($arrOption)
	{
		
		if( !FrameworkConfig::DEBUG )
		{
			throw new FakeException( 'not in debug mode' );
		}
		if( count($arrOption) < 1 )
		{
			printf("invalid parm.  btscript gamexxx $0 prepareNum \n");
			return;
		}
	
		$prepareNum = $arrOption[0];
		if( $prepareNum > 1000 )
		{
			throw new FakeException( 'too much' );
		}
		
		$offset = 0;
		$limit = 100;
		
		while (true)
		{
			$arrUid = self::selectUsers( 35 , $offset, $limit);
			foreach ( $arrUid as $index => $uidInfo )
			{
				PassLogic::enter( $uidInfo['uid'] );
				PassObj::releaseInstance( $uidInfo['uid'] );
			}
			$offset += count( $arrUid );
			if( count( $arrUid ) < $limit )
			{
				break;
			}
			//就拉整百吧 不管了
			if( $offset >= $prepareNum )
			{
				break;
			}
		}
		
	}
	
	
	function selectUsers( $level, $offset, $limit )
	{
		$data = new CData();
		$ret = $data->select( array( 'uid' ) )-> from( 't_user' )-> where(array('level','>=', $level))
		->limit( $offset , $limit)->query(); 

		if( empty( $ret ) )
		{
			return array();
		}
		
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */