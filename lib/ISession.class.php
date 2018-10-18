<?php
/**********************************************************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ISession.class.php 80342 2013-12-11 10:41:23Z wuqilin $
 * 
 **********************************************************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/ISession.class.php $
 * @author $Author: wuqilin $(baihaoping@babeltime.com)
 * @date $Date: 2013-12-11 10:41:23 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80342 $
 * @brief 
 * 
 **/
interface ISession
{

	/**
	 * 开启session
	 */
	public function start($arrSession, $arrISession = null);

	/**
	 * 关闭session
	 */
	public function end();

	/**
	 * 获取session
	 * @param string $key
	 * @return 对应的value
	 */
	public function getSession($key);

	/**
	 * 判斷當前的session是否已經修改
	 */
	public function changed();

	/**
	 * 获取所有的session
	 * @return array
	 */
	public function getSessions();

	/**
	 * 重置某個session
	 * @param string $key
	 */
	public function unsetSession($key);

	/**
	 * 设置某个session
	 * @param string $key
	 * @param mixed $value
	 */
	public function setSession($key, $value);

	/**
	 * 重置session
	 */
	public function resetSession();
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */