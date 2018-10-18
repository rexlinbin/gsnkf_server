<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: checkAllUser.php 78364 2013-12-02 14:03:51Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/checkAllUser.php $
 * @author $Author: wuqilin $(tianming@babeltime.com)
 * @date $Date: 2013-12-02 14:03:51 +0000 (Mon, 02 Dec 2013) $
 * @version $Revision: 78364 $
 * @brief 
 *  
 **/
class CheckAllUser extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		$data = new CData();
		
		$forceFix = 0;
		if(isset($arrOption[0]))
		{
			$forceFix = intval($arrOption[0]);
		}
		
		$batchNum = 10;
		$offset = 0;
		while (true) 
		{
			printf("offset:%d, limit:%d\n", $offset, $batchNum);
			Logger::info('get users. offset:%d, limit:%d', $offset, $batchNum);
			
			$arrRet = $data->select(array('uid','va_hero'))->from('t_user')
					->where('uid','>',0)->orderBy('uid', true)
					->limit($offset, $batchNum)->query();
			$offset += $batchNum;
			
			foreach($arrRet as $value)
			{
				$uid = $value['uid'];
				$arrUnused = $value['va_hero']['unused'];
				$firstUnused = current($arrUnused);
				if( isset( $firstUnused['htid'] ) )
				{
					foreach($arrUnused as $key => $value)
					{
						$arrUnused[$key] = array( $value['htid'] );
						if( isset( $value['level'] ) )
						{
							$arrUnused[$key][1] = $value['level'];
						}
					}
					
					Logger::info('fix user va. uid:%d', $uid);
					$data->update('t_user')
						->set( array('va_hero'=>array('unused'=>$arrUnused)) )
						->where('uid', '=', $uid)->query();
					
				}				
				
				$ret = $data->select(array('va_formation'))->from('t_hero_formation')
									->where('uid', '=', $uid)->query();
				$formation = $ret[0];
				if( !isset($formation['va_formation']['formation']) )
				{
					$arrValue = array(
							'va_formation' => array(
									'formation' => $formation['va_formation'],
									'extra' => array()
									)							
							);
					Logger::info('fix formation va. uid:%d', $uid);
					$data->update('t_hero_formation')->set($arrValue)->where('uid', '=', $uid)->query();
				}
			}
			
			if( count($arrRet) <  $batchNum)
			{
				break;
			}
		}
		Logger::info('fix all users');
		printf("done \n");
	}
	

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */