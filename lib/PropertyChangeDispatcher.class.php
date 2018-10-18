<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PropertyChangeDispatcher.class.php 80342 2013-12-11 10:41:23Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/PropertyChangeDispatcher.class.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-12-11 10:41:23 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80342 $
 * @brief 事件分发类，所有需要有属性改变监听的类都继承自本类
 *
 **/
class PropertyChangeDispatcher extends Object
{

	/**
	 * 所有的监听类
	 * @var array
	 */
	private $arrListener;

	public function __construct()
	{

		$this->arrListener = array ();
	}

	/**
	 * 添加监听器
	 * @param string $prop 要监听的属性
	 * @param mixed $listener 当属性改变时的回调，函数原型为void func($prop, $oldValue, $newValue)
	 */
	public function addPropertyChangeListener($prop, $listener)
	{

		if (isset ( $this->arrListener [$prop] ))
		{
			foreach ( $this->arrListener [$prop] as $index => $listener_ )
			{
				if ($listener_ == $listener)
				{
					$this->arrListener [$prop] [$index] = $listener;
					return;
				}
			}
		}
		$this->arrListener [$prop] [] = $listener;
	}

	/**
	 * 删除监听器
	 * @param string $prop 要删除的属性
	 * @param mixed $listener 注册的回调，函数原型为void func($prop, $oldValue, $newValue)
	 */
	public function removePropertyChangeListener($prop, $listener)
	{

		if (! isset ( $this->arrListener [$prop] ))
		{
			return;
		}

		foreach ( $this->arrListener [$prop] as $index => $listener_ )
		{
			if ($listener_ == $listener)
			{
				unset ( $this->arrListener [$prop] [$index] );
				break;
			}
		}
	}

	/**
	 * 属性改变时的动作，依次调用回调函数
	 * @param string $prop 属性
	 * @param mixed $oldValue 旧值
	 * @param mixed $newValue 新值
	 */
	protected function propertyChanged($prop, $oldValue, $newValue)
	{

		if (! isset ( $this->arrListener [$prop] ))
		{
			return;
		}

		foreach ( $this->arrListener [$prop] as $listener )
		{
			call_user_func_array ( $listener, array ($prop, $oldValue, $newValue ) );
		}
	}

	/**
	 * 重载的设置属性函数，当有变量改变时调用此
	 * @param string $key
	 * @param mixed $value
	 */
	function __set($key, $value)
	{

		if (! isset ( $this->$key ))
		{
			$oldValue = null;
		}
		else
		{
			$oldValue = $this->$key;

		}
		if ($oldValue != $value)
		{
			parent::__set ( $key, $value );
			$this->propertyChanged ( $key, $oldValue, $value );
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */