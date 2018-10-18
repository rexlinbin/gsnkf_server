<?php
/***************************************************************************
 *
 * Copyright (c) 2014 babeltime.com, Inc. All Rights Reserved
 * $Id: EnHCopy.class.php 110528 2014-05-23 10:04:41Z QiangHuang $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hcopy/EnHCopy.class.php $
 * @author $Author: QiangHuang $(huangqiang@babeltime.com)
 * @date $Date: 2014-05-23 10:04:41 +0000 (Fri, 23 May 2014) $
 * @version $Revision: 110528 $
 * @brief
 *
 **/

class EnHCopy
{
	public static function setHCopyPassNum($uid, $copyId, $level, $num)
	{
		$man = HCopy::getManager($uid, $copyId, $level);
		$man->setCopyFinishNum($num);
		$man->save();
	}
	
	public static function getHCopyPassNum($uid, $copyId, $level)
	{
		return HCopy::getManager($uid, $copyId, $level)->getCopyFinishNum();
	}
	
}
