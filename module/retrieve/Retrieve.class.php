<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Retrieve.class.php 257926 2016-08-23 09:15:28Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/retrieve/Retrieve.class.php $
 * @author $Author: GuohaoZheng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-08-23 09:15:28 +0000 (Tue, 23 Aug 2016) $
 * @version $Revision: 257926 $
 * @brief 
 *  
 **/
 
class Retrieve implements IRetrieve
{
	private $uid;
	
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
	}
	
	/* (non-PHPdoc)
	 * @see IRetrieve::getRetrieveInfo()
	*/
	public function getRetrieveInfo()
	{
		return RetrieveLogic::getRetrieveInfo($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IRetrieve::retrieveByGold()
	*/
	public function retrieveByGold($type, $isAll=0)
	{
	    $isAll = intval( $isAll );
	    
		if (!is_array($type))
		{
			if (is_numeric($type) && $type == intval($type))
			{
				$type = array(intval($type));
			}
			else
			{
				throw new FakeException('invalid retrieve type param:%s', $type);
			}
		}
		RetrieveLogic::checkType($type, TRUE);
		
		return RetrieveLogic::retrieve($this->uid, $type, TRUE, $isAll);
	}
	
	/* (non-PHPdoc)
	 * @see IRetrieve::retrieveBySilver()
	*/
	public function retrieveBySilver($type, $isAll=0)
	{
	    $isAll = intval( $isAll );
	    
		if (!is_array($type))
		{
			if (is_numeric($type) && $type == intval($type))
			{
				$type = array(intval($type));
			}
			else
			{
				throw new FakeException('invalid retrieve type param:%s', $type);
			}
		}
		RetrieveLogic::checkType($type, FALSE);
		
		return RetrieveLogic::retrieve($this->uid, $type, FALSE, $isAll);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */