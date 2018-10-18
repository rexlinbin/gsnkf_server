<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FriendDao.class.php 108348 2014-05-15 03:55:34Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/friend/FriendDao.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-05-15 03:55:34 +0000 (Thu, 15 May 2014) $
 * @version $Revision: 108348 $
 * @brief 
 *  
 **/
class FriendDao
{
	
	static function getFriendList( $arrCond, $offset, $limit, $AorB )
	{
		$arr_feilds = array(
				'uid' , 'fuid'
		);
		
		
		if ( $AorB == 'A' )
		{
			$arr_feilds[] = 'alove_time as lovedTime';
		}
		else if ( $AorB == 'B' )
		{
			$arr_feilds[] = 'blove_time as lovedTime';
		}
		else 
			throw new InterException( 'type: %s not permitted', $AorB );
		
		
		$data = new CData ();
		$arrRet = $data->select ( $arr_feilds )->from ( FriendDef::TBL_NAME );
		foreach ( $arrCond as $cond )
		{
			$data->where ( $cond );
		}
		return $data->limit ( $offset, $limit )->query ();
	}
	
	static function getFriendCount( $uid )
	{
		$data = new CData();
		$arrRetA = $data->selectCount()->from( FriendDef::TBL_NAME)
		->where ( 'uid', '=' , $uid )
		->where ( 'status', '=' , FriendDef::STATUS_OK )->query ();
		$arrRetB = $data->selectCount()->from( FriendDef::TBL_NAME )
		->where ( 'fuid', '=', $uid )
		->where ( 'status', '=', FriendDef::STATUS_OK )->query ();
		
		return ( $arrRetA [0] ['count'] + $arrRetB [0] ['count'] );
	}
	
	static function getFriendship( $uid , $fuid )
	{
		$data = new CData();
		
		$arrFields = array(
			'uid', 'fuid', 'alove_time ', 'blove_time','reftime_apk','reftime_bpk','apk_num','bpk_num',
		);
		
		$arrCond = array( 
				array( 'uid' , 'IN' , array( $uid, $fuid ) ) , 
				array( 'fuid' , 'IN' , array( $uid, $fuid ) ) , 
				array( 'status' , '=' ,FriendDef::STATUS_OK )
		);
		$data->select( $arrFields )->from( FriendDef::TBL_NAME );
		foreach ( $arrCond as $cond )
		{
			$data->where ( $cond );
		}
		$arrRet = $data->query();
		
		if ( empty( $arrRet ) )
		{
			return array();
		}
		else return $arrRet[0];
	}
	
	static function getFriendPair( $uid , $fuid )
	{
		$data = new CData();
		
		$arr_feilds = array(
				'uid' , 'fuid'
		);
		$arrCond = array(
				array( 'uid' , '=' , $uid ) , 
				array( 'fuid' , '=' , $fuid ) 
		);
		$data->select( $arr_feilds )->from( FriendDef::TBL_NAME );
		foreach ( $arrCond as $cond )
		{
			$data->where ( $cond );
		}
		$arrRet = $data->query();
		
		if ( isset( $arrRet[ 0 ] ) )
		{
			return $arrRet[ 0 ];
		}
		return array();
	}
	
	static function addFriend( $applicantUid, $accepterUid )
	{
		return self::modifyFriendship( $applicantUid , $accepterUid , FriendDef::STATUS_OK );
	}
	
	
	static function delFriend( $delUid , $beDelUid )
	{
		return self::modifyFriendship( $delUid , $beDelUid , FriendDef::STATUS_DEL );
	}
	
	static function modifyFriendship( $uid , $fuid , $modify )
	{
		//由于两个人只维护一条数据， 不管几次添加还是删除，为了达到好友状态和赠送状态分开操作， doc中有default，在插入时，
		//此后只有进行love时才对赠送状态进行操作
		$arrValuesA = array(
				'uid' => $uid ,
				'fuid' => $fuid ,
				'status' => $modify
		);
		$arrValuesB = array(
				'uid' => $fuid ,
				'fuid' => $uid ,
				'status' => $modify
		);
		$arrValues = $arrValuesA;
		
		$frindPair = self::getFriendPair( $uid , $fuid );
		if ( empty( $frindPair ) )
		{
			$arrValues = $arrValuesB;
		}
		$data = new CData ();
		$data->insertOrUpdate ( FriendDef::TBL_NAME )->values ( $arrValues )->query ();
	}
	
	static function setLoveTime( $wheres, $arrValues )
	{
		$data = new CData ();
		$data->update ( FriendDef::TBL_NAME )->set ( $arrValues );
		foreach ( $wheres as $key => $whereOne )
		{
			$data->where( $whereOne );
		}
		$ret = $data->query ();
		
		if ( $ret[DataDef::AFFECTED_ROWS] == 0 )
		{
			throw new FakeException( 'update lovetime failed, wheres: %s, vals: %s', $wheres, $arrValues  );
		}
	}
	
	static function getAllLove( $uid )
	{
		$arrFields = array(
			'uid', 'num', 'reftime', 'pk_num','bepk_num','va_love',
		);
		$data = new CData();
		$ret = $data->select( $arrFields )
		->from( FriendDef::LOVE_TBL_NAME )
		->where( array( 'uid', '=', $uid ) )
		->query();
		
		if ( empty( $ret ) )
		{
			return array();
		}
		return $ret[0];
	}
	
	static function insertAllLove( $uid, $arr )
	{
		$data = new CData();
		$ret = $data->insertInto( FriendDef::LOVE_TBL_NAME )
		->values( $arr )
		->query();
		
		if ( $ret[ DataDef::AFFECTED_ROWS ] == 0 )
		{
			throw new InterException( 'insert to t_friendlove fail, $val: %s', $arr );
		}
	}
	
	static function updateAllLove( $uid, $updateArr )
	{
		$data = new CData();
		$ret = $data->update( FriendDef::LOVE_TBL_NAME )
		->set( $updateArr )
		->where( 'uid', '=', $uid ) 
		->query();
		
		if ( $ret[ DataDef::AFFECTED_ROWS ] == 0 )
		{
			throw new InterException( 'update t_friendlove fail, $updateArr: %s', $updateArr );
		}
	}
	
	static function setSameFriendBepkNum( $wheres, $arrvalues )
	{
		$data = new CData();
		$data->update( FriendDef::TBL_NAME )->set( $arrvalues );
		foreach ( $wheres as $where )
		{
			$data->where($where);
		}
		$ret = $data->query();
		if ( $ret[DataDef::AFFECTED_ROWS] <= 0 )
		{
			throw new InterException( 'update same friend bepknum failed' );
		}
		
	}
	
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */