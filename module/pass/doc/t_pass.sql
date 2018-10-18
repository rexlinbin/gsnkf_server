set names utf8;

CREATE TABLE IF NOT EXISTS t_pass
(
   uid  			int unsigned not null comment 'uid',
   refresh_time		int unsigned not null comment '刷新时间',
   luxurybox_num    int unsigned not null comment '已开启宝藏宝箱的次数',
   cur_base			int unsigned not null comment '当前的位置',
   reach_time		int unsigned not null comment '达到这个积分的时间',
   pass_num			int unsigned not null comment '今天闯过的关数',
   point 			int unsigned not null comment '积分',
   star_star		int unsigned not null comment '星星',
   coin				int unsigned not null comment '神兵币',
   reward_time		int unsigned not null comment '发奖时间',
   lose_num			int unsigned not null comment '今天输的次数',
   buy_num			int unsigned not null comment '今天购买的次数',

   va_pass  		blob not null comment ' 	
	 	  	 				heroInfo =>( hid => array( 0=>int 1=>int 2=>int )),							
	 						chestShow => array( freeChest => int, goldChest => int),
	 						buffShow => array( array(status => int, buff => array()),array( ... ),array() ),
	 						formation => array(),	
							bench => array(),					
	 						buffInfo => array(),
	 						opponentInfo => array(1=>array(uid,arrHero =>hprageArr) 2=> 3=> )
							unionInfo = array(),',
	 						
   primary key(uid),
   index time_point_num(reach_time, point, pass_num)
   
)engine = InnoDb default charset utf8;