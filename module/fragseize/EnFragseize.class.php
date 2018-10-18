<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnFragseize.class.php 208518 2015-11-10 09:51:50Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/fragseize/EnFragseize.class.php $
 * @author $Author: ShijieHan $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-11-10 09:51:50 +0000 (Tue, 10 Nov 2015) $
 * @version $Revision: 208518 $
 * @brief 
 *  
 **/
class EnFragseize
{
	private $addArr;
	/**
	 * 添加宝物碎片
	 * @param int $uid
	 * @param array $fragArr
	 * {
	 * 		fragId => $fragNum,
	 * 		.
	 * 		.
	 * 		.
	 * }
	 * @throws FakeException
	 * @throws ConfigException
	 */
	public static function addTreaFrag( $uid, $fragArr, $ifUpdateFrag = true )
	{
		$fragInst = FragseizeObj::getInstance( $uid );
		$fragInst->addFrags( $fragArr );
		if($ifUpdateFrag == true)
		{
			$fragInst->updateFrags();
		}
	}
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */