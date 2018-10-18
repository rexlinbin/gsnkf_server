<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ActivityConfTest.php 84694 2014-01-03 07:58:42Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/ActivityConfTest.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-01-03 07:58:42 +0000 (Fri, 03 Jan 2014) $
 * @version $Revision: 84694 $
 * @brief 
 *  
 **/
class ActivityConfTest extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        $name = $arrOption[0];
        $ret = EnActivity::getConfByName($name);
        var_dump($ret);
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */