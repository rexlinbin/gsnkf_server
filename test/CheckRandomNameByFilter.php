<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CheckRandomNameByFilter.php 80130 2013-12-11 02:30:56Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/CheckRandomNameByFilter.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-12-11 02:30:56 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80130 $
 * @brief 
 *  
 **/
class CheckRandomNameByFilter extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        $offset = 0;
        $limit = DataDef::MAX_FETCH;
        $filterName = array();
        while(TRUE)
        {
            $data = new CData();
            $ret = $data->select(array('id','name'))
                        ->from('t_random_name')
                        ->where(array('id','>',1))
                        ->limit($offset, $limit)
                        ->query();
            foreach($ret as $index => $nameInfo)
            {
                $name = $nameInfo['name'];
                $filterRet = TrieFilter::search ( $name );
                if(!empty($filterRet))
                {
                   echo $name.":".substr($name, $filterRet[0], $filterRet[1])."\n";
                }
            }
            if(count($ret) < $limit)
            {
                break;
            }
            $offset += $limit;
        }
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */