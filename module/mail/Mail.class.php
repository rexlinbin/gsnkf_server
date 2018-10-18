<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Mail.class.php 142776 2014-11-27 02:10:10Z ShiyuZhang $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mail/Mail.class.php $
 * @author $Author: ShiyuZhang $(hoping@babeltime.com)
 * @date $Date: 2014-11-27 02:10:10 +0000 (Thu, 27 Nov 2014) $
 * @version $Revision: 142776 $
 * @brief
 *
 **/





class Mail implements IMail
{

	/* (non-PHPdoc)
	 * @see IMail::sendMail()
	 */
	public function sendMail($reciever_uid, $subject, $content)
	{
		//格式化输入
		$reciever_uid = intval($reciever_uid);

		//检测是否主题超长
		if ( mb_strlen($subject, MailConf::ENCODING_TYPE) > MailConf::SUBJECT_MAX_LENGTH )
		{
			Logger::DEBUG('extend max subject length!');
			return FALSE;
		}

		//检测是否内容超长
		if ( mb_strlen($content, MailConf::ENCODING_TYPE) > MailConf::CONTENT_MAX_LENGTH )
		{
			Logger::DEBUG('extend max content length!');
			return FALSE;
		}

		$sender_uid = RPCContext::getInstance ()->getSession ( 'global.uid' );

		$userInfos = EnUser::getArrUser(array($reciever_uid), array('uname'));

		if ( empty($userInfos[$reciever_uid]) )
		{
			Logger::FATAL('invalid reciever_uid:%d', $reciever_uid);
			throw new Exception('fake');
		}

		MailLogic::sendPlayerMail ( $sender_uid, $reciever_uid, $subject, $content);
		return true;
	}

	/* (non-PHPdoc)
	 * @see IMail::getMailBoxList()
	 */
	public function getMailBoxList($offset, $limit, $older = true)
	{
		//格式化输入
		$offset = intval($offset);
		$limit = intval($limit);

		if ( $offset < 0  || $limit <= 0 )
		{
			Logger::FATAL('invalid offset:%d, limit:%d', $offset, $limit);
			throw new Exception('fake');
		}

		$recieverUid = RPCContext::getInstance ()->getSession ( 'global.uid' );
		return MailLogic::getMailBoxList ( $recieverUid, $offset, $limit, $older );
	}

	/* (non-PHPdoc)
	 * @see IMail::getSysMailList()
	 */
	public function getSysMailList($offset, $limit, $older = true)
	{
		//格式化输入
		$offset = intval($offset);
		$limit = intval($limit);

		if ( $offset < 0 || $limit <= 0 )
		{
			Logger::FATAL('invalid offset:%d, limit:%d', $offset, $limit);
			throw new Exception('fake') ;
		}

		$recieverUid = RPCContext::getInstance ()->getSession ( 'global.uid' );
		return MailLogic::getSysMailList ( $recieverUid, $offset, $limit, $older );
	}

	/* (non-PHPdoc)
	 * @see IMail::getSysItemMailList()
	 */
	public function getSysItemMailList($offset, $limit, $older = true)
	{
		//格式化输入
		$offset = intval($offset);
		$limit = intval($limit);

		if ( $offset < 0 || $limit <= 0 )
		{
			Logger::FATAL('invalid offset:%d, limit:%d', $offset, $limit);
			throw new Exception('fake');
		}

		$recieverUid = RPCContext::getInstance ()->getSession ( 'global.uid' );
		return MailLogic::getSysItemMailList ( $recieverUid, $offset, $limit, $older );
	}

	/* (non-PHPdoc)
	 * @see IMail::getPlayMailList()
	 */
	public function getPlayMailList($offset, $limit, $older = true)
	{
		//格式化输入
		$offset = intval($offset);
		$limit = intval($limit);

		if ( $offset < 0 || $limit <= 0 )
		{
			Logger::FATAL('invalid offset:%d, limit:%d', $offset, $limit);
			throw new Exception('fake');
		}

		$recieverUid = RPCContext::getInstance ()->getSession ( 'global.uid' );
		return MailLogic::getPlayerMailList ( $recieverUid, $offset, $limit, $older );
	}

	/* (non-PHPdoc)
	 * @see IMail::getBattleMailList()
	 */
	public function getBattleMailList($offset, $limit, $older = true)
	{
		//格式化输入
		$offset = intval($offset);
		$limit = intval($limit);

		if ( $offset < 0  || $limit <= 0 )
		{
			Logger::FATAL('invalid offset:%d, limit:%d', $offset, $limit);
			throw new Exception('fake');
		}

		$recieverUid = RPCContext::getInstance ()->getSession ( 'global.uid' );
		return MailLogic::getBattleMailList ( $recieverUid, $offset, $limit, $older );
	}
	
	public function getMineralMailList( $offset, $limit, $older = true )
	{
		//格式化输入
		$offset = intval($offset);
		$limit = intval($limit);
		
		if ( $offset < 0 || $limit <= 0 )
		{
			Logger::FATAL('invalid offset:%d, limit:%d', $offset, $limit);
			throw new Exception('fake');
		}
		
		$recieverUid = RPCContext::getInstance ()->getSession ( 'global.uid' );
		return MailLogic::getMineralMailList( $recieverUid, $offset, $limit, $older );
	}

	/* (non-PHPdoc)
	 * @see IMail::getMailDetail()
	 */
	public function getMailDetail($mid)
	{
		//格式化输入
		$mid = intval($mid);

		$recieverUid = RPCContext::getInstance ()->getSession ( 'global.uid' );
		return MailLogic::getMail ( $recieverUid, $mid );
	}

	/* (non-PHPdoc)
	 * @see IMail::fetchItem()
	 */
	public function fetchItem($mid, $item_id)
	{
		//格式化输入
		$mid = intval($mid);
		$item_id = intval($item_id);

		$recieverUid = RPCContext::getInstance ()->getSession ( 'global.uid' );
		return MailLogic::fetchItem ( $recieverUid, $mid, $item_id );
	}

	/* (non-PHPdoc)
	 * @see IMail::fetchAllItems()
	 */
	public function fetchAllItems($mid)
	{
		//格式化输入
		$mid = intval($mid);

		$recieverUid = RPCContext::getInstance ()->getSession ( 'global.uid' );
		return MailLogic::fetchAllItems($recieverUid, $mid);
	}

	/* (non-PHPdoc)
	 * @see IMail::deleteMail()
	 */
	public function deleteMail($mid)
	{
		//格式化输入
		$mid = intval($mid);

		$recieverUid = RPCContext::getInstance ()->getSession ( 'global.uid' );
		return MailLogic::deleteMail ( $recieverUid, $mid );
	}

	/* (non-PHPdoc)
	 * @see IMail::deleteAllSystemMail()
	 */
	public function deleteAllSystemMail()
	{

		$recieverUid = RPCContext::getInstance ()->getSession ( 'global.uid' );
		return MailLogic::deleteAllSystemMail ( $recieverUid );
	}

	/* (non-PHPdoc)
	 * @see IMail::deleteAllBattleMail()
	 */
	public function deleteAllBattleMail()
	{

		$recieverUid = RPCContext::getInstance ()->getSession ( 'global.uid' );
		return MailLogic::deleteAllBattleMail ( $recieverUid );
	}

	/* (non-PHPdoc)
	 * @see IMail::deleteAllPlayerMail()
	 */
	public function deleteAllPlayerMail()
	{

		$recieverUid = RPCContext::getInstance ()->getSession ( 'global.uid' );
		return MailLogic::deleteAllPlayerMail ( $recieverUid );
	}

	/* (non-PHPdoc)
	 * @see IMail::deleteAllMailBoxMail()
	 */
	public function deleteAllMailBoxMail()
	{

		$recieverUid = RPCContext::getInstance ()->getSession ( 'global.uid' );
		return MailLogic::deleteAllMailBoxMail ( $recieverUid );
	}
	
	public function setApplyMailAdded( $senderUid )
	{
		$recieverUid = RPCContext::getInstance ()->getSession ( 'global.uid' );
		return MailLogic::setApplyMailStatus($recieverUid, $senderUid, 1);
	
	}
	
	public function setApplyMailRejected( $senderUid )
	{
		$recieverUid = RPCContext::getInstance ()->getSession ( 'global.uid' );
		return MailLogic::setApplyMailStatus($recieverUid, $senderUid, 2);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
