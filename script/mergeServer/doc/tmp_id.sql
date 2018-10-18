

CREATE TABLE IF NOT EXISTS t_tmp_id_info
(
    id_name              varchar(32) not null,
    game_id              varchar(255) not null,
    min_id               int(10) unsigned not null,
    max_id               int(10) unsigned not null,
    primary key(id_name,game_id)
)default charset utf8 engine = InnoDb;



CREATE TABLE IF NOT EXISTS t_tmp_id_proto
(
    old_id               int(10) unsigned not null,
    new_id               int(10) unsigned not null,
    primary key(old_id)
)default charset utf8 engine = InnoDb;