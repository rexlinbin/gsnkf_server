<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnCopy.class.php 68733 2013-10-14 07:34:41Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ncopy/EnCopy.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-10-14 07:34:41 +0000 (Mon, 14 Oct 2013) $
 * @version $Revision: 68733 $
 * @brief 
 *  
 **/
class  EnCopy
{
    static public function getTopUserByCopy($offset, $limit)
    {
        // 获取服务器成就排行
        $list = NCopyDao::getTopUserByCopy($offset, $limit);
        // 返回给前端
        return $list;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */