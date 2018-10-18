CREATE TABLE IF NOT EXISTS `t_tmp_user` (
	`uid`		INT unsigned NOT NULL DEFAULT 0,
	`game_id`	VARCHAR(255) NOT NULL,
	`pid`		INT unsigned NOT NULL DEFAULT 0,
	`name`		CHAR(32) NOT NULL,
	`deal`		INT unsigned NOT NULL DEFAULT 0,
	`new_uid`	INT unsigned NOT NULL DEFAULT 0,
	primary key(`uid`, `game_id`),
	key(`uid`)
)engine = InnoDb default charset utf8;