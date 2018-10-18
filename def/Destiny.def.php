<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Destiny.def.php 81736 2013-12-19 07:15:05Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Destiny.def.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-12-19 07:15:05 +0000 (Thu, 19 Dec 2013) $
 * @version $Revision: 81736 $
 * @brief 
 *  
 **/
class DestinyDef
{
    const TBL_FIELD_UID = 'uid';
    const TBL_FIELD_CUR_BREAK = 'cur_break';
    const TBL_FIELD_CUR_DESTINY = 'cur_destiny';
    const TBL_FIELD_VA_DESTINY = 'va_destiny';
    
    public static $ARR_SELECT_FIELD = array(
            self::TBL_FIELD_UID,
            self::TBL_FIELD_CUR_DESTINY,
            self::TBL_FIELD_VA_DESTINY
            );
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */