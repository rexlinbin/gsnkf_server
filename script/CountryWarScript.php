<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarScript.php 214469 2015-12-08 05:53:06Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/CountryWarScript.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-08 05:53:06 +0000 (Tue, 08 Dec 2015) $
 * @version $Revision: 214469 $
 * @brief 
 *  
 **/

class CountryWarScript extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		Logger::info('Countrywar begin, args: %s',$arrOption);
		if( !isset( $arrOption[0] ) )
		{
			throw new InterException( 'need operation' );
		}
		if( $arrOption[0] == 'team' )
		{
			if( !CountryWarUtil::isStage( CountryWarStage::TEAM ) )
			{
				Logger::fatal('not team period');
				return;
			}
			CountryWarScrLogic::syncAllTeamFromPlat2Cross();
		}
		elseif( $arrOption[0] == 'range' )
		{
			if( !CountryWarUtil::isStage( CountryWarStage::RANGE_ROOM ) )
			{
				Logger::fatal('not range period');
				return;
			}
			CountryWarLogic::scrRangeRoom();
		}
		else
		{
			Logger::fatal('invalid op');
			return;
		}
		
	}

}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */