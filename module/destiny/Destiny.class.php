<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Destiny.class.php 81737 2013-12-19 07:16:00Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/destiny/Destiny.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-12-19 07:16:00 +0000 (Thu, 19 Dec 2013) $
 * @version $Revision: 81737 $
 * @brief 
 *  
 **/
class Destiny implements IDestiny
{
	/* (non-PHPdoc)
     * @see IDestiny::getDestinyInfo()
     */
    public function getDestinyInfo ()
    {
        // TODO Auto-generated method stub
        $destinyInfo = DestinyLogic::getDestinyInfo();
        return $destinyInfo;
    }

	/* (non-PHPdoc)
     * @see IDestiny::activateDestiny()
     */
    public function activateDestiny ($destinyId)
    {
        // TODO Auto-generated method stub
        list($destinyId) = Util::checkParam(__METHOD__, func_get_args());
        $costScore = DestinyLogic::activateDestiny($destinyId);
        return $costScore;
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */