set names utf8;

CREATE TABLE IF NOT EXISTS t_hero_formation(
	uid 			int unsigned not null comment '用户id',
	craft_id 		int unsigned not null comment '当前装备的阵法id，没有则为0',
    va_formation 	blob not null comment 'formation => array(hid => array(index,pos)) index为在我的阵容中的位置, pos为在我的阵型中的位置
										extra => array(index => hid) 我的小伙伴们的hid组
										extopen => array($index) 购买开启的小伙伴位置
										warcraft => array(id=>array(level => int)) 升过级的阵法
                                        attr_extra => array(index => hid) 我的属性小伙伴们的hid组
                                        attr_extra_open => array(index) 购买开启的属性小伙伴位置
                                        attr_extra_lv => array(index => level) 我的属性小伙伴每个位置的等级',
    primary key(uid)
)engine = InnoDb default charset utf8;
