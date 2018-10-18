set names utf8;

CREATE TABLE IF NOT EXISTS t_pet
(	
  	petid					int(10) unsigned not null comment '宠物ID',	
	uid						int(10) unsigned not null comment '用户ID',	
	pet_tmpl				int(10) unsigned not null comment '宠物模板id',
	level					int(10) unsigned not null comment '宠物等级',
	exp						int(10) unsigned not null comment '宠物经验',
	skill_point				int(10) unsigned not null comment '现在拥有的技能点',
	swallow					int(10) unsigned not null comment '已经吞噬宠物的数量',
	traintime				int(10) unsigned not null comment '训练时间',
	delete_time				int(10) unsigned not null comment '宠物删除时间',

	va_pet 				    blob not null comment 'array(
	                           skillTalent => array(0 => array(id => int,level => int)),
													 	 skillNormal => array( 0 => array(id => int,level => int, status => int) ),
													 	 skillProduct => array( 0 => array(id => int,level => int) )
													 	 evolveNum => int 进阶等级,
													 	 confirm => array(id属性 => int等级, id => int, id => int, id => int)大小4的map,
													 	 toConfirm => array(同上),
													 	 failNum => int领悟技能失败次数,
													 	 )',
	primary key(petid),
	index uid(uid)
)default charset utf8 engine = InnoDb;