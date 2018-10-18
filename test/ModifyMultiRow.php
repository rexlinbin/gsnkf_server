<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ModifyMultiRow.php 85807 2014-01-10 02:47:18Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/ModifyMultiRow.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-01-10 02:47:18 +0000 (Fri, 10 Jan 2014) $
 * @version $Revision: 85807 $
 * @brief 
 *  
 **/
class ModifyMultiRow extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        $data = new CData();
        $data->update('t_user')
             ->set(array('level'=>40))
             ->where(array('uid','>',0))
             ->query();
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */