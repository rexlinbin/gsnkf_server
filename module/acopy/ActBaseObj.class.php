<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ActBaseObj.class.php 76606 2013-11-25 04:00:28Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/acopy/ActBaseObj.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-11-25 04:00:28 +0000 (Mon, 25 Nov 2013) $
 * @version $Revision: 76606 $
 * @brief 
 *  
 **/
class ActBaseObj extends ACopyObj
{
	
	public static function getBattleReward()
	{
	    $baseId     	= AtkInfo::getInstance()->getBaseId();
	    $baseLv  		= AtkInfo::getInstance()->getBaseLv();
	    $reward = CopyUtil::getBasePassAward($baseId, $baseLv);
	    return $reward;
	}
	
	
	public static function doneBattle($atkRet)
	{
		$copyId = AtkInfo::getInstance()->getCopyId();
	    $copyObj = MyACopy::getInstance()->getActivityCopyObj($copyId);
	    $pass = $atkRet['pass'];
	    if($pass)
	    {
	        if($copyObj == NULL)
	        {
	            throw new FakeException('empty activity obj');
	        }
	        if($copyObj->subCanDefeatNum() == FALSE)
	        {
	            throw new FakeException('not enough defeatnum.now is %d',$this->getCanDefeatNum());
	        }
	    }
	    return array();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */