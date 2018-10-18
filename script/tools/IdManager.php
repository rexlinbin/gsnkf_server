<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IdManager.php 60629 2013-08-21 09:51:53Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/IdManager.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-08-21 09:51:53 +0000 (Wed, 21 Aug 2013) $
 * @version $Revision: 60629 $
 * @brief
 *
 **/
class IdManager extends BaseScript
{

	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption)
	{

		if (empty ( $arrOption [0] ) || empty ( $arrOption [1] ))
		{
			$this->usage ();
			return;
		}

		$operand = $arrOption [0];
		$id = $arrOption [1];
		switch ($operand)
		{
			case 'show' :
				$ret = IdGenerator::showId ( $id );
				break;
			case 'set' :
				if (empty ( $arrOption [2] ))
				{
					$this->usage ();
					return;
				}
				$ret = IdGenerator::setId ( $id, intval ( $arrOption [2] ) );
				break;
			default :
				$this->usage ();
				return;
		}

		echo implode ( ' ', $arrOption ) . ' return ' . $ret . "\n";
	}

	private function usage()
	{

		echo "usage: btscript IdManager.php show|set id [num]\n";
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */