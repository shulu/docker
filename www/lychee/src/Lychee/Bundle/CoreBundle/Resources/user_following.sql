CREATE TABLE user_following (
	id BIGINT AUTO_INCREMENT NOT NULL,
	follower_id BIGINT NOT NULL,
	followee_id BIGINT NOT NULL,
	state SMALLINT NOT NULL,
	update_time DATETIME NOT NULL,
	PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB AUTO_INCREMENT = 100000;

CREATE TABLE user_following_counting (
	target_id BIGINT NOT NULL,
	follower_count INT DEFAULT NULL,
	followee_count INT DEFAULT NULL,
	PRIMARY KEY(target_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;