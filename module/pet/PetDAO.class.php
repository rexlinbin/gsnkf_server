<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PetDAO.class.php 201200 2015-10-09 09:11:06Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pet/PetDAO.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-10-09 09:11:06 +0000 (Fri, 09 Oct 2015) $
 * @version $Revision: 201200 $
 * @brief 
 *  
 **/
class PetDAO
{
	public static $petTbl= 't_pet';
	public static $keeperTbl = 't_keeper';
	
	static function selectAllPet( $uid )
	{
		$fields = array (
				'uid',
				PetDef::PET_ID,
				PetDef::PETTMPL,
				PetDef::LEVEL,
				PetDef::EXP,
				PetDef::SKILLPOINT,
				PetDef::SWALLOW,
				PetDef::TRAINTIME,
				PetDef::DELETE_TIME,
				PetDef::VAPET,
				
		);
	
		$data = new CData();
		$arrRet = $data->select( $fields )
		->from(self::$petTbl)
		->where(array("uid", "=", $uid))
		->where(array( PetDef::DELETE_TIME,'=',PetDef::OK ))
		->query();
		
		if ( !empty( $arrRet ) )
		{
			return $arrRet;
		}
		
		return array();
	}
	
	
	static function addNewPet( $valueArr )
	{
		$data = new CData();
		$data->insertInto(self::$petTbl)->values( $valueArr )->query();
	}
	
	
	static function updatePet ( $petid, $set )
	{
		$where = array('petid', '=', $petid);
		$data = new CData();
		$arrRet = $data->update(self::$petTbl)
		->set($set)
		->where($where)->query();
		 
		return $arrRet;
	}
	
	static function selectKeeper( $uid )
	{
		$where = array("uid", "=", $uid);
		$fields = array (
				'uid',
				PetDef::KEEPERSLOT,
				PetDef::PET_FIGHTFORCE,
				PetDef::VAKEEPER,
		);
		
		$data = new CData();
		$arrRet = $data->select( $fields )
		->from(self::$keeperTbl)
		->where($where)->query();
		if ( !empty( $arrRet ) )
		{
			return $arrRet[0];
		}
		
		return array();
	}
	
	static function addKeeper( $valueArr )
	{
		$data = new CData();
		$data->insertOrUpdate( self::$keeperTbl )->values( $valueArr )->query();
	}
	
	static function updateKeeper ( $uid, $set )
	{
		$where = array('uid', '=', $uid);
		$data = new CData();
		$arrRet = $data->update(self::$keeperTbl)
		->set($set)
		->where($where)->query();
			
		return $arrRet;
	}
	
	static function deletePet( $uid, $deletePet )
	{
		if ( empty( $deletePet ) )
		{
			return ;
		}
		$where1 = array('uid', '=', $uid);
		$where2 = array( PetDef::PETID, '=', $deletePet );
		$data = new CData();
		$ret = $data->update(self::$petTbl)
		->set(array( PetDef::DELETE_TIME => Util::getTime() ))
		->where($where1)
		->where($where2)
		->query();
			
		if ( $ret[DataDef::AFFECTED_ROWS]  == 0)
		{
			throw new InterException( 'delete failed: %s', $deletePet );
		}
	}
	
	static function getRankList()
	{
		$selects = array('uid',PetDef::PET_FIGHTFORCE,PetDef::KEEPERSLOT,PetDef::VAKEEPER);
		$data = new CData();
		$ret = $data->select( $selects )->from( self::$keeperTbl )
		-> where(array( PetDef::PET_FIGHTFORCE,'>',0 ))->orderBy(PetDef::PET_FIGHTFORCE, false)
        ->orderBy(PetDef::UID, true)->limit(0 , 50)->query();
		if( empty( $ret ) )
		{
			return array();
		}
		
		$return = Util::arrayIndex($ret, 'uid');
		
		return $return;
	}
	
	static function getRankPetInfo( $arrPetid )
	{
		if( empty( $arrPetid ) )
		{
			return array();
		}
		$fields = array (
				'uid',
				PetDef::PET_ID,
				PetDef::PETTMPL,
				PetDef::LEVEL,
				PetDef::EXP,
				PetDef::SKILLPOINT,
				PetDef::SWALLOW,
				PetDef::TRAINTIME,
				PetDef::DELETE_TIME,
				PetDef::VAPET,
		
		);
		
		$data = new CData();
		$ret = $data->select( $fields )->from(self::$petTbl)
		->where( array(PetDef::PET_ID,'IN', $arrPetid) )
		->where(array( PetDef::DELETE_TIME,'=',0 ))
		->query();
		if( empty( $ret ) )
		{
			return array();
		}
		$return = Util::arrayIndex($ret, 'uid');
		
		return $return;
	}
	
	static function getPetRank( $myFightPet )
	{
		$data  = new CData();
		//这个地方只是用了>= 排名会不准确，当然也可以用50+1的方法，同样也是不准确的
		$ret = $data->selectCount()->from(self::$keeperTbl)->where( array( PetDef::PET_FIGHTFORCE,'>=',$myFightPet ) )
		->query();
	
		return $ret[0][DataDef::COUNT];
	}
	
	static function getAllPetCount( $uid )
	{
		$data = new CData();
		$ret = $data->selectCount()
		->from(self::$petTbl)->where(array("uid", "=", $uid))
		->query();
		
		return $ret[0][DataDef::COUNT];
	}
	
	static function getPetArrIncludeDeleted($uid,$offset)
	{
		$fields = array (
				'uid',
				PetDef::PET_ID,
				PetDef::PETTMPL,
/* 				PetDef::LEVEL,
				PetDef::EXP,
				PetDef::SKILLPOINT,
				PetDef::SWALLOW,
				PetDef::TRAINTIME,
				PetDef::DELETE_TIME,
				PetDef::VAPET, */
		
		);
		
		$data = new CData();
		$arrRet = $data->select( $fields )
		->from(self::$petTbl)
		->where(array("uid", "=", $uid))
		->orderBy( PetDef::PET_ID , true)
		->limit($offset, DataDef::MAX_FETCH)
		->query();
		
		if ( !empty( $arrRet ) )
		{
			return $arrRet;
		}
		
		return array();
		
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */