<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GetConfFromArray.php 84944 2014-01-06 03:25:37Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/GetConfFromArray.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-01-06 03:25:37 +0000 (Mon, 06 Jan 2014) $
 * @version $Revision: 84944 $
 * @brief 
 *  
 **/
class GetConfFromArray extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        $csvFile = $arrOption[0];
        $file = fopen($csvFile,'r');
        // 略过前两行
        $line = fgetcsv($file);
        $line = fgetcsv($file);
        $data = array();
        while(TRUE)
        {
            $line = fgetcsv($file);
            if(empty($line))
            {
                break;
            }
            $data[] = $line;
        }
        fclose($file);
        $ret = EnHeroShop::readRewardCSV($data);
        var_dump($ret);
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */