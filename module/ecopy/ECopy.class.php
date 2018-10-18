<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ECopy.class.php 183915 2015-07-13 09:05:02Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ecopy/ECopy.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-07-13 09:05:02 +0000 (Mon, 13 Jul 2015) $
 * @version $Revision: 183915 $
 * @brief 
 *  
 **/
class ECopy implements IECopy
{

	public function getEliteCopyInfo()
	{
	    if(EnSwitch::isSwitchOpen(SwitchDef::ELITECOPY) == FALSE)
	    {
	        throw new FakeException('EliteCopy switch is not open.');
	    }
		$copyInfo = ECopyLogic::getEliteCopyInfo();
		return $copyInfo;
	}


	public function enterCopy($copyId)
	{
		$copyId = intval($copyId);
		if($copyId <= 0)
		{
			throw new FakeException('error param! copyid %s.',$copyId);
		}
		$ret = ECopyLogic::enterCopy($copyId);
		return $ret;
	}


	public function doBattle($copyId, $army_id,$fmt=array())
	{
		$copyId = intval($copyId);
		$army_id = intval($army_id);
		if($copyId <= 0 || ($army_id <= 0))
		{
			throw new FakeException('error param! copyid %s,armyid %s.',$copyId,$army_id);
		}
		if(is_array($fmt) == FALSE)
		{
		    throw new FakeException('error param!fmt %s.',$fmt);
		}
		$ret = ECopyLogic::doBattle($copyId, $army_id,$fmt);
		return $ret;
	}

	public function leaveCopy($copyId)
	{
		$copyId = intval($copyId);
		if($copyId <= 0)
		{
			throw new FakeException('error param! copyid %s.',$copyId);
		}
		$baseId = btstore_get()->ELITECOPY[$copyId]['base_id'];		
		CopyUtil::leaveBaseLevel($copyId, $baseId, BaseLevel::SIMPLE);
	}

	public function getCopyDefeatInfo($copyId)
	{
		$copyId = intval($copyId);
		if($copyId <= 0)
		{
			throw new FakeException('error param! copyid %s.',$copyId);
		}
		$baseId = btstore_get()->ELITECOPY[$copyId]['base_id'];
		$level    = BaseLevel::SIMPLE;
		$copyDefeat = array();
		$copyDefeat['replay'] = CopyUtil::getReplayList($baseId, $level);
		$copyDefeat['rank'] = CopyUtil::getPreBaseAttackPlayer($baseId, $level);
		return $copyDefeat;
	}

	public function reviveCard($copyId, $card_id)
	{
	    throw new FakeException('ecopy.reviveCard is close');
		$copyId = intval($copyId);
		$card_id = intval($card_id);
		if($copyId <= 0 || ($card_id <= 0))
		{
			throw new FakeException('error param!copyid %s.cardid %s.',$copyId,$card_id);
		}
		$baseId = btstore_get()->ELITECOPY[$copyId]['base_id'];
		$baseLv = BaseLevel::SIMPLE;
		$ret = CopyUtil::reviveCard($baseId, $baseLv, $card_id);
		return $ret;
	}
	public function reFight($copyId)
	{
		$copyId = intval($copyId);
		if($copyId <= 0)
		{
			throw new FakeException('error param!copyid %s.',$copyId);
		}
		$baseId = btstore_get()->ELITECOPY[$copyId]['base_id'];
		$baseLv = BaseLevel::SIMPLE;
		$ret = CopyUtil::reFight($copyId, $baseId, $baseLv);
		return $ret;
	}
	
	public function buyAtkNum($num)
	{
	    list($num) = Util::checkParam(__METHOD__, func_get_args());
	    ECopyLogic::buyAtkNum($num);
	    return 'ok';
	}
	
	public function sweep($copyId, $num=1)
	{
	    $copyId = intval($copyId);
	    $num = intval($num);
	    if($copyId <= 0 || $num <= 0)
	    {
	        throw new FakeException('sweep invalid params. %d %d',$copyId,$num);
	    }
	    $uid = RPCContext::getInstance()->getUid();
	    return ECopyLogic::sweep($uid, $copyId, $num);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */