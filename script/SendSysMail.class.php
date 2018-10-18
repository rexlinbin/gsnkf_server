<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: SendSysMail.class.php 60629 2013-08-21 09:51:53Z wuqilin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/SendSysMail.class.php $
 * @author $Author: wuqilin $(jhd@babeltime.com)
 * @date $Date: 2013-08-21 09:51:53 +0000 (Wed, 21 Aug 2013) $
 * @version $Revision: 60629 $
 * @brief
 *
 **/

/**
 *
 * 通过邮件发送补偿(金币物品)
 *
 * @example btscript SendSysMail.class.php 20120501 20120510
 *
 * @author pkujhd
 *
 */
class SendSysMail extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript ($arrOption)
	{
		//最大循环执行次数
		$MAX_EXEC_TIME = 65536;
		//每次QUERY拉取的数量
		$USER_PRE_QUERY = DataDef::MAX_FETCH;

		$subject = MailTiTleMsg::MAINTAIN_COMPENSATION;
		$content = MailContentMsg::MAINTAIN_COMPENSATION;

		//检查参数是否合法
		if ( count($arrOption) != 2 )
		{
			echo "invalid args:SendSysMail.class.php start_date open_date\n";
			return;
		}

		//登录时间不应早于
		$send_time = strtotime(strval($arrOption[0]));

		if ( $send_time == 0 )
		{
			echo "invalid date:$arrOption[0]!\n";
			return;
		}

		//开服时间应该早于
		$open_time = strtotime(strval($arrOption[1]));
		if ( $open_time < strtotime(GameConf::SERVER_OPEN_YMD) )
		{
			echo "open date " . GameConf::SERVER_OPEN_YMD . " too late!\n";
			return;
		}

		//设置不向前端推送callback
		MailConf::$NO_CALLBACK = TRUE;

		//发送的物品ID
		$itemTemplates = array ( 70011 => 1 );

		$lastUid = 0;
		$uidCount = 0;
		$uidStart = 0;

		for ( $i = 0; $i < $MAX_EXEC_TIME; $i++ )
		{
			$uids = UserDao::getArrUser($uidStart, $USER_PRE_QUERY, array('uid', 'last_login_time'));
			if ( empty($uids) )
			{
				Logger::INFO('EXEC END!LAST UID:%d, USER COUNT:%d', $lastUid, $uidCount);
				return;
			}
			else
			{
				foreach ( $uids as $info )
				{
					$uid = $info['uid'];
					$last_login_time = $info['last_login_time'];
					if ( $last_login_time < $send_time )
					{
						Logger::INFO('EXEC USER:%d not login in two days!', $uid);
						continue;
					}

					$lastUid = $uid;
					$uidCount++;
					Logger::INFO('send mail to uid:%d, user count:%d', $uid, $uidCount);

					//通过邮件发送物品
					MailLogic::sendSysItemMailByTemplate($uid,
						MailConf::DEFAULT_TEMPLATE_ID, $subject, $content, $itemTemplates);
				}
				$uidStart += count($uids);
			}
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */