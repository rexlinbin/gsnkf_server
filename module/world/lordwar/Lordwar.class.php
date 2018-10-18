<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Lordwar.class.php 129058 2014-08-25 14:18:03Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/lordwar/Lordwar.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-08-25 14:18:03 +0000 (Mon, 25 Aug 2014) $
 * @version $Revision: 129058 $
 * @brief 
 *  
 **/
class Lordwar implements ILordwar
{
	private $uid;
	
	function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
	}
	/* (non-PHPdoc)
	 * @see ILordwar::enterLordwar()
	 */
	public function enterLordwar() 
	{
		LordwarLogic::enterLordwar();
		
	}

	/* (non-PHPdoc)
	 * @see ILordwar::leaveLordwar()
	 */
	public function leaveLordwar() 
	{
		LordwarLogic::leaveLordwar();
	}

	/* (non-PHPdoc)
	 * @see ILordwar::register()
	 */
	public function register() 
	{
		return LordwarLogic::register($this->uid);
	}

	/* (non-PHPdoc)
	 * @see ILordwar::getMyTeamInfo()
	 */
	public function getMyTeamInfo() 
	{
		return LordwarLogic::getMyTeamInfo( $this->uid );
	}

	/* (non-PHPdoc)
	 * @see ILordwar::getMyRecord()
	 */
	public function getMyRecord() 
	{
		$ret = LordwarLogic::getMyRecord($this->uid);
		
		return $ret;
	}

	/* (non-PHPdoc)
	 * @see ILordwar::updateFightInfo()
	 */
	public function updateFightInfo() 
	{
		return LordwarLogic::updateFightInfo($this->uid);
	}

	/* (non-PHPdoc)
	 * @see ILordwar::clearFmtCd()
	 */
	public function clearFmtCd() 
	{
		return LordwarLogic::clearFmtCd( $this->uid );
	}


	/* (non-PHPdoc)
	 * @see ILordwar::support()
	 */
	public function support($pos, $teamType) 
	{
		LordwarLogic::support($pos, $teamType );
	}

	/* (non-PHPdoc)
	 * @see ILordwar::getMySupport()
	 */
	public function getMySupport() 
	{
		return LordwarLogic::getMySupport($this->uid);
	}

	/* (non-PHPdoc)
	 * @see ILordwar::getLordInfo()
	 */
	public function getLordInfo() 
	{
		return LordwarLogic::getLordInfo($this->uid);
	}

	/* (non-PHPdoc)
	 * @see ILordwar::getPromotionInfo()
	 */
	public function getPromotionInfo() 
	{
		return LordwarLogic::getPromotionInfo();
	}

	/* (non-PHPdoc)
	 * @see ILordwar::getPromotionBtlIds()
	 */
/* 	public function getPromotionBtlIds() 
	{
		//这个不必要但是可以有，看前端和自己是否方便 TODO
	}
	
 */
	/* (non-PHPdoc)
	 * @see ILordwar::getTempleInfo()
	*/
	public function getTempleInfo()
	{
		return LordwarLogic::getTempleInfo();
	}
	
	/* (non-PHPdoc)
	 * @see ILordwar::worship()
	*/
	public function worship($pos, $type) 
	{
		LordwarLogic::worship($pos, $type);
	}
	
	/* (non-PHPdoc)
	 * @see ILordwar::getPromotionBtl()
	 */
	public function getPromotionBtl($round, $teamType, $serverId1, $uid1, $serverId2, $uid2) 
	{
		$ret = LordwarLogic::getPromotionBtl($round,$teamType, $serverId1, $uid1, $serverId2, $uid2);
		
		return $ret;
		
	}
	
	public function getPromotionHistory($round) 
	{
		return LordwarLogic::getPromotionHistory( $round );
	}

	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */