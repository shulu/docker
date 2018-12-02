CREATE TABLE user_profile (
  user_id BIGINT NOT NULL,
  cover_url VARCHAR(2083) DEFAULT NULL,
  signature VARCHAR(200) DEFAULT NULL,
  honmei VARCHAR(200) DEFAULT NULL,
  attributes VARCHAR(1000) DEFAULT NULL,
  skills VARCHAR(200) DEFAULT NULL,
  constellation VARCHAR(20) DEFAULT NULL,
  age INT DEFAULT NULL,
  location VARCHAR(200) DEFAULT NULL,
  school VARCHAR(100) DEFAULT NULL,
  community VARCHAR(100) DEFAULT NULL,
  fancy VARCHAR(200) DEFAULT NULL,
  PRIMARY KEY(user_id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

INSERT INTO user_profile(user_id, signature, cover_url) SELECT id as user_id, signature, cover_url FROM user;