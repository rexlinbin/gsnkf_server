<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FragseizeDao.class.php 83110 2013-12-25 19:40:32Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/fragseize/FragseizeDao.class.php $
 * @author $Author: wuqilin $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-12-25 19:40:32 +0000 (Wed, 25 Dec 2013) $
 * @version $Revision: 83110 $
 * @brief 
 *  
 **/
class FragseizeDAO
{
	public static $table = 't_fragseize';
	public static $seizerTable = 't_seizer';
	
	public static function getFragByUid( $uid )
	{
		$wheres[] = array( 'uid', '=', $uid ) ;
		$selects = array( FragseizeDef::FRAG_ID, FragseizeDef::FRAG_NUM );
		$offset = 0;
		$return = array();
		while ( true )
		{
			$ret = self::getPartFragsByUid( $offset, DataDef::MAX_FETCH, $selects, $wheres );
			if ( empty( $ret ) )
			{
				break;
			}
			$return = array_merge( $return, $ret );
			if ( count( $ret ) < DataDef::MAX_FETCH )
			{
				break;
			}
			$offset += count( $ret );
		}
		
		return $return;
	}
	
	public static function getPartFragsByUid( $offset, $limit, $selects, $wheres )
	{
		$data = new CData();
		$data->select( $selects )
		->from( self::$table );
		foreach ( $wheres as $where )
		{
			$data->where( $where );
		}
		$ret = $data->orderBy( 'uid' , true)->orderBy( FragseizeDef::FRAG_ID , true)
		->limit($offset, $limit)->query();
		if ( empty( $ret ) )
		{
			return array();
		}
		return $ret;
	}
	
	public static function getRecByUidFragarr( $uidArr, $fragArr )
	{
		if ( empty( $fragArr ) )
		{
			throw new InterException( 'fragArr is empty' );
		}
		$wheres[] = array( FragseizeDef::FRAG_ID, 'IN', $fragArr );
		$wheres[] = array( 'uid', 'IN', $uidArr );
		$selects = array( 'uid', FragseizeDef::FRAG_ID, FragseizeDef::FRAG_NUM );
		$offset = 0;
		$return = array();
		while ( true )
		{
			$ret = self::getPartFragsByUid( $offset, DataDef::MAX_FETCH, $selects, $wheres );
			if ( empty( $ret ) )
			{
				break;
			}
			$return = array_merge( $return, $ret );
			if ( count( $ret ) < DataDef::MAX_FETCH )
			{
				break;
			}
			$offset += count( $ret );
		}
		
		return $return;
		
	}
	
	public static function getSeizer( $uid )
	{
		$arrField = array( FragseizeDef::WHITE_END_TIME, FragseizeDef::FIRST_TIME );
		$data = new CData();
		$ret = $data->select( $arrField )
		->from( self::$seizerTable )
		->where( array( 'uid', '=', $uid ) )
		->query();
		
		if ( empty( $ret ) )
		{
			return array();
		}
		return $ret[0];
	}
	
	public static function insertSeizer( $uid, $initArr )
	{
		$data = new CData();
		$ret = $data->insertInto( self::$seizerTable )
		->values( $initArr )
		->query();
		if ( $ret[ DataDef::AFFECTED_ROWS ] != 1 )
		{
			throw new FakeException( 'fail insert db t_seizer' );
		}
	}
	
	public static function getWhiteEndTime( $uid )
	{
		$arrField = array( FragseizeDef::WHITE_END_TIME,  );
		$data = new CData();
		$ret = $data->select( array( FragseizeDef::WHITE_END_TIME ) )
		->from( self::$seizerTable )
		->where( array( 'uid', '=', $uid ) )
		->query();
		
		if ( empty( $ret ) )
		{
			return 0;
		}
		return $ret[0][FragseizeDef::WHITE_END_TIME];
	}
	
	public static function getFragByFragidArr( $uid, $fragIdArr )
	{
		$arrFields = array( 
				FragseizeDef::FRAG_ID, 
				FragseizeDef::FRAG_NUM,
				FragseizeDef::SEIZE_NUM,
		 );
		$data = new CData();
		$ret = $data->select( $arrFields )
		->from( self::$table )
		->where( array( FragseizeDef::FRAG_ID, 'IN', $fragIdArr ) )
		->where( array( 'uid', '=', $uid ) )
		->query();
		
		if ( empty( $ret ) )
		{
			return array();
		}
		return $ret;
	}
	
	public static function updateSeizer( $uid, $updateArr )
	{
		$data = new CData();
		$ret = $data->update(self::$seizerTable)
		->set( $updateArr )
		->where( array('uid', '=', $uid ) )
		->query();
		if ( $ret[ DataDef::AFFECTED_ROWS ] != 1 )
		{
			throw new InterException( 'update failed' );
		}
	}
	
	private static function subFragNum( $uid, $fragid, $fragNum, $seizeNum )
	{
		$data = new CData();
		$dec = new DecOperator( intval( $fragNum ) );
		$valArr = array( 
				FragseizeDef::FRAG_NUM => $dec,
				FragseizeDef::SEIZE_NUM => $seizeNum,
		);
		$ret = $data->update( self::$table )
		->where( array( FragseizeDef::FRAG_ID, '=', $fragid ) )
		->where( array( 'uid', '=', $uid ) )
		->where( array( FragseizeDef::FRAG_NUM, '>=', $fragNum ) )
		->set( $valArr )
		->query();
		if ( $ret[DataDef::AFFECTED_ROWS] ==0 )
		{
			return false;
		}
		return true;
	}
	
	private static function addFragNum( $uid, $fragid, $fragNum, $seizeNum )
	{
		$inc = new IncOperator( $fragNum );
		$valArr = array(
				FragseizeDef::FRAG_ID => $fragid,
				FragseizeDef::FRAG_NUM => $inc,
				FragseizeDef::SEIZE_NUM => $seizeNum,
				'uid' => $uid,
		);
		$data = new CData();
		$ret = $data->insertOrUpdate( self::$table )
		->where( array( FragseizeDef::FRAG_ID, '=', $fragid ) )
		-> where( array( 'uid', '=', $uid ) )
		->values( $valArr )
		->query();
		if ( $ret[DataDef::AFFECTED_ROWS] == 0 )
		{
			return false;
		}
		return true;
	}
	
	public static function updateFrags( $uid, $updateArr )
	{
		$data = new CData();
		
		foreach ( $updateArr as $fragId => $fragInfo )
		{
			//普通的更新字段
			$valueArr = array(
					'uid' => $uid,
					FragseizeDef::FRAG_ID => $fragId,
					FragseizeDef::SEIZE_NUM => $fragInfo[FragseizeDef::SEIZE_NUM],
			);
			$fragNum = $fragInfo[FragseizeDef::FRAG_NUM];
			$seizeNum = $fragInfo[FragseizeDef::SEIZE_NUM];
			
			$ret = false;
			if($fragInfo[FragseizeDef::FRAG_NUM] >= 0)
			{
				$ret = self::addFragNum($uid, $fragId, $fragNum, $seizeNum);
			}
			else
			{
				$ret = self::subFragNum($uid, $fragId, -$fragNum, $seizeNum);
			}
			if($ret == false)
			{
				throw new InterException( 'update t_fragseize fail, uid:%s, info:%s', $uid, $fragInfo );
			}
		}
	}
	
	public static function getUidArrByFragId( $fragId )
	{
		$arrField = array( 'uid', FragseizeDef::FRAG_NUM );
		if ( empty( $fragId ) )
		{
			throw new InterException( 'fragid empty' );
		}
		
		$data = new CData();
		$ret = $data->select( $arrField )->from( self::$table )
		->where( array( FragseizeDef::FRAG_ID, '=', $fragId ) )
		->where( array( FragseizeDef::FRAG_NUM, '>', 0 ) )
		->limit( 0 , DataDef::MAX_FETCH)
		->query();
		
		if ( empty( $ret ) )
		{
			return array();
		}
		return $ret;
	}
	
	public static function getSeizerNotInWhiteFlag( $arrUid )
	{
		if ( empty( $arrUid ) )
		{
			return array();
		}
		$data = new CData();
		$ret = $data->select( array('uid') )
		-> from( self::$seizerTable )
		-> where( array( 'uid', 'IN', $arrUid ) )
		-> where( array( FragseizeDef::WHITE_END_TIME, '<=', Util::getTime() ) )
		-> query();
		
		if ( empty( $ret ) )
		{
			return array();
		}
		return Util::arrayExtract( $ret , 'uid');
	}
		
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */