<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HeroBookDao.php 68381 2013-10-12 03:09:35Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hero/HeroBookDao.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-10-12 03:09:35 +0000 (Sat, 12 Oct 2013) $
 * @version $Revision: 68381 $
 * @brief 
 *  
 **/
class HeroBookDao
{
    const FIELD_VA_BOOK = 'va_book';
    const TBL_HERO_BOOK = 't_hero_book';
    
    public static function getHeroBook($uid)
    {
        $data = new CData();
        $ret = $data->select(array('uid',self::FIELD_VA_BOOK))
                    ->from(self::TBL_HERO_BOOK)
                    ->where(array('uid','=',$uid))
                    ->query();
        if(empty($ret))
        {
            return array();
        }
        return $ret[0];
    }
    public static function updateHeroBook($uid,$bookInfo)
    {
        $data = new CData();
        $ret = $data->insertOrUpdate(self::TBL_HERO_BOOK)
                    ->values($bookInfo)
                    ->query();
        return $ret;
    }
    public static function initHeroBook($uid,$bookInfo)
    {
        $data = new CData();
        $ret = $data->insertInto(self::TBL_HERO_BOOK)
                    ->values($bookInfo)
                    ->query();
        return $ret;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */