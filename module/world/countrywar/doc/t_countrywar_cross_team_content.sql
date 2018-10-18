set names utf8;

create table if not exists t_countrywar_cross_team_content
(
		war_id                  int unsigned not null comment '活动id ',                             
		team_id					int unsigned not null comment '组房id',
		resource_a				int unsigned not null comment 'group1的资源',	
		resource_b				int unsigned not null comment 'group2的资源',	
		num_country_1			int unsigned not null comment '1号国家人数',
		num_country_2			int unsigned not null comment '2号国家人数',
		num_country_3			int unsigned not null comment '3号国家人数',
		num_country_4			int unsigned not null comment '4号国家人数',
		room_num				int unsigned not null comment '该分组已经有的房间数量',
		va_extra				blob not null comment '分房间进度 roomDivideInfo => 
													{
														countryId => array(roomId,side),
													}',

    primary key(war_id,team_id)
)engine = InnoDb default charset utf8;
