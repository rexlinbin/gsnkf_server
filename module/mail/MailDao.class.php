<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MailDao.class.php 128913 2014-08-25 08:45:33Z ShiyuZhang $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mail/MailDao.class.php $
 * @author $Author: ShiyuZhang $(hoping@babeltime.com)
 * @date $Date: 2014-08-25 08:45:33 +0000 (Mon, 25 Aug 2014) $
 * @version $Revision: 128913 $
 * @brief
 *
 **/


class MailDao
{

	public static function saveMail($mailType, $senderUid, $recieverUid, $templateId,
			$subject, $content,	$vaExtra = null, $db = '')
	{

		$arrBody = array (
				MailDef::MAIL_SQL_TYPE => $mailType,
				MailDef::MAIL_SQL_SENDER => $senderUid,
				MailDef::MAIL_SQL_RECIEVER => $recieverUid,
				MailDef::MAIL_SQL_TEMPLATE_ID => $templateId,
				MailDef::MAIL_SQL_SUBJECT => $subject,
				MailDef::MAIL_SQL_CONTENT => $content,
				MailDef::MAIL_SQL_EXTRA => $vaExtra,
				MailDef::MAIL_SQL_RECV_TIME => Util::getTime (),
				MailDef::MAIL_SQL_READ_TIME => 0,
				MailDef::MAIL_SQL_DELETED => 0
		);

		$data = new CData ();
		if( !empty( $db ) )
		{
			$data->useDb($db);
		}
		
		$arrRet = $data->insertInto(MailDef::MAIL_SQL_TABLE)->values($arrBody)
			->uniqueKey(MailDef::MAIL_SQL_ID)->query ();
		return $arrRet [MailDef::MAIL_SQL_ID];
	}

	public static function getMailList($recieverUid, $mailTypes, $arrField, $offset, $limit, $older )
	{

		if ($limit > CData::MAX_FETCH_SIZE)
		{
			Logger::FATAL('limit:%d exceed max fetch mail size!', $limit);
			throw new Exception ( 'fake' );
		}

		$mailTypes = array_unique($mailTypes);
		if ( !in_array(MailType::SYSTEM_ITEM_MAIL, $mailTypes) )
		{
			return self::getOtherMailList($recieverUid, $mailTypes, $arrField, $offset, $limit, $older);
		}
		else if ( count($mailTypes) == 1 )
		{
			return self::getSysItemMailList($recieverUid, $mailTypes, $arrField, $offset, $limit );
		}
		else
		{
			if ( self::__getMailCount($recieverUid, $mailTypes, FALSE, 0) == 0 )
			{
				return array();
			}

			$return = self::__getMailList($recieverUid, $mailTypes, $arrField, $offset, $limit, $older);
			$count = count($return);
			if ( $count >= $limit )
			{
				return $return;
			}
			else if ( $count == 0 )
			{
				$mailCount = self::__getMailCount($recieverUid,
						$mailTypes, FALSE);
				$sysMailCount = self::__getMailCount($recieverUid,
						array(MailType::SYSTEM_ITEM_MAIL), FALSE);
				return self::getSysItemMailList($recieverUid, $mailTypes,
					 $arrField, $offset-$mailCount+$sysMailCount, $limit);
			}
			else
			{
				$sysMailCount = self::__getMailCount($recieverUid,
						array(MailType::SYSTEM_ITEM_MAIL), FALSE);
				$appendRet = self::getSysItemMailList($recieverUid, $mailTypes, $arrField,
					 $sysMailCount, $limit - $count);
				return array_merge($return, $appendRet);
			}
		}
	}

	private static function getOtherMailList($recieverUid, $mailTypes, $arrField, $offset, $limit, $older)
	{
		return self::__getMailList($recieverUid, $mailTypes, $arrField, $offset, $limit, $older);
	}

	private static function getSysItemMailList($recieverUid, $mailTypes, $arrField, $offset, $limit)
	{
		return self::__getMailList($recieverUid, array(MailType::SYSTEM_ITEM_MAIL),
			 $arrField, $offset, $limit, true, 0);
	}

	private static function __getMailList($recieverUid, $mailTypes, $arrField,
		 $offset, $limit, $older, $timeLimit = MailConf::MAIL_LIFE_TIME )
	{
		$wheres = array (
				array (MailDef::MAIL_SQL_RECIEVER, '=', $recieverUid ),
				array (MailDef::MAIL_SQL_TYPE, 'IN', $mailTypes ),
				array (MailDef::MAIL_SQL_DELETED, '=', 0),
		);
		if ( $offset != 0 )
		{
			if ( $older )
			{
				$wheres[] = array( MailDef::MAIL_SQL_ID, '<', $offset );
			}
			else if( !$older )
			{
				$wheres[] = array( MailDef::MAIL_SQL_ID, '>', $offset );
			}
			else
			{
				throw new FakeException( 'older or not should be bool' );
			}
		}
		else 
		{
			$older = true;
		}

		if ( !empty($timeLimit) )
		{
			$wheres[] = array (MailDef::MAIL_SQL_RECV_TIME, '>',
				 Util::getTime() - $timeLimit );
		}

		$data = new CData ();
		$data->select ( $arrField )->from ( MailDef::MAIL_SQL_TABLE );
		foreach ( $wheres as $where )
			$data->where ( $where );
		return $data->orderBy (MailDef::MAIL_SQL_ID, !$older )->limit ( 0, $limit )->query ();
	}

	public static function getMailCount($recieverUid, $mailTypes)
	{
		$mailTypes = array_unique($mailTypes);
		if ( !in_array(MailType::SYSTEM_ITEM_MAIL, $mailTypes) )
		{
			return self::getOtherMailCount($recieverUid, $mailTypes);
		}
		else
		{
			if ( count($mailTypes) == 1 )
			{
				return self::getSysItemMailCount($recieverUid, $mailTypes);
			}
			else
			{
				return self::getOtherMailCount($recieverUid, array_diff($mailTypes, array(MailType::SYSTEM_ITEM_MAIL)))
					+ self::getSysItemMailCount($recieverUid, $mailTypes);
			}
		}
	}

	public static function getUnreadMailCount($recieverUid, $mailTypes)
	{
		$mailTypes = array_unique($mailTypes);
		if ( !in_array(MailType::SYSTEM_ITEM_MAIL, $mailTypes) )
		{
			return self::getOtherMailCount($recieverUid, $mailTypes, TRUE);
		}
		else
		{
			if ( count($mailTypes) == 1 )
			{
				return self::getSysItemMailCount($recieverUid, $mailTypes, TRUE);
			}
			else
			{
				return self::getOtherMailCount($recieverUid,
					 array_diff($mailTypes, array(MailType::SYSTEM_ITEM_MAIL)), TRUE)
					+ self::getSysItemMailCount($recieverUid, $mailTypes, TRUE);
			}
		}
	}

	private static function getOtherMailCount($recieverUid, $mailTypes, $unRead = FALSE)
	{
		return self::__getMailCount($recieverUid, $mailTypes, $unRead);
	}

	private static function getSysItemMailCount($recieverUid, $unRead = FALSE )
	{
		return self::__getMailCount($recieverUid, array(MailType::SYSTEM_ITEM_MAIL),
			 $unRead, 0);
	}

	private static function __getMailCount($recieverUid, $mailTypes,
			$unRead = FALSE, $timeLimit = MailConf::MAIL_LIFE_TIME)
	{
		$wheres = array (
				array (MailDef::MAIL_SQL_RECIEVER, '=', $recieverUid ),
				array (MailDef::MAIL_SQL_TYPE, 'IN', $mailTypes ),
				array (MailDef::MAIL_SQL_DELETED, '=', 0),
		);

		if ( !empty($timeLimit) )
		{
			$wheres[] = array (MailDef::MAIL_SQL_RECV_TIME, '>',
				 Util::getTime() - $timeLimit );
		}

		if ( $unRead === TRUE )
		{
			$wheres[] = array (MailDef::MAIL_SQL_READ_TIME, '=', 0 );
		}

		$data = new CData ();
		$data->selectCount ()->from ( MailDef::MAIL_SQL_TABLE );
		foreach ( $wheres as $where )
			$data->where ( $where );
		$arrRet = $data->query ();

		return $arrRet [0] [DataDef::COUNT];
	}

	public static function getMail($recieverUid, $mid, $arrField)
	{
		$wheres = array (
			array (MailDef::MAIL_SQL_ID, '=', $mid ),
			array (MailDef::MAIL_SQL_RECIEVER, '=', $recieverUid ),
			array (MailDef::MAIL_SQL_DELETED, '=', 0),
		);

		$data = new CData ();
		$data->select ( $arrField )->from ( MailDef::MAIL_SQL_TABLE );
		foreach ( $wheres as $where )
			$data->where ( $where );
		$arrRet = $data->query ();
		if (empty ( $arrRet [0] ))
		{
			return array ();
		}
		return $arrRet [0];
	}

	public static function updateMail($recieverUid, $mid, $arrBody)
	{
		$wheres = array (
			array (MailDef::MAIL_SQL_ID, '=', $mid ),
			array (MailDef::MAIL_SQL_RECIEVER, '=', $recieverUid )
		);

		$data = new CData ();
		$data->update ( MailDef::MAIL_SQL_TABLE )->set ( $arrBody );
		foreach ( $wheres as $where )
			$data->where ( $where );
		$arrRet = $data->query ();
		return $arrRet;
	}

	public static function deleteMail($recieverUid, $mid)
	{
		$wheres = array (
			array (MailDef::MAIL_SQL_ID, '=', $mid ),
			array (MailDef::MAIL_SQL_RECIEVER, '=', $recieverUid )
		);
		$values = array (MailDef::MAIL_SQL_DELETED => 1);

		$data = new CData ();
		$data->update ( MailDef::MAIL_SQL_TABLE )->set ( $values );
		foreach ( $wheres as $where )
			$data->where ( $where );
		$arrRet = $data->query ();
		return $arrRet;
	}

	public static function deleteMailbyType($recieverUid, $mailTypes)
	{
		$wheres = array (
			array (MailDef::MAIL_SQL_TYPE, 'IN', $mailTypes),
			array (MailDef::MAIL_SQL_RECIEVER, '=', $recieverUid )
		);
		$values = array (MailDef::MAIL_SQL_DELETED => 1);

		$data = new CData ();
		$data->update ( MailDef::MAIL_SQL_TABLE )->set ( $values );
		foreach ( $wheres as $where )
			$data->where ( $where );
		$arrRet = $data->query ();
		return $arrRet;
	}
	
	public static function getFriendApplyIntime( $recieverUid, $senderUid, $seconds, $arrFields )
	{
		$curTime = Util::getTime();
		$conditonTime = $curTime - $seconds;
		$data = new CData();
		$ret = $data->select( $arrFields )
				->from( MailDef::MAIL_SQL_TABLE )
				->where(array( MailDef::MAIL_SQL_RECIEVER, '=', $recieverUid ) )
				->where(array( MailDef::MAIL_SQL_SENDER, '=', $senderUid ))
				->where(array( MailDef::MAIL_SQL_RECV_TIME, '>',$conditonTime ))
				->where(array( MailDef::MAIL_SQL_TEMPLATE_ID, '=', MailTemplateID::FRIEND_APPLY ))
				->orderBy( MailDef::MAIL_SQL_RECV_TIME , FALSE )
				->limit(0, 1)
				->query();
		if ( empty( $ret ) )
		{
			return array();
		}
		return $ret[ 0 ];
	}
	
	public static function setApplyStatus( $receiverUid, $senderUid, $arrUpdate )
	{
		
		$data = new CData();
		$data->update( MailDef::MAIL_SQL_TABLE )
		->set( $arrUpdate )
		->where( array( MailDef::MAIL_SQL_RECIEVER, '=', $receiverUid ) )
		->where( array( MailDef::MAIL_SQL_SENDER, '=', $senderUid ) )
		->where( array( MailDef::MAIL_SQL_RECV_TIME, '>', Util::getTime()-MailConf::MAIL_LIFE_TIME ) )
		->where( array( MailDef::MAIL_SQL_TEMPLATE_ID, '=', MailTemplateID::FRIEND_APPLY ) )
		->query();
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
