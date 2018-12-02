CREATE TABLE IF NOT EXISTS `ciyocon_oss`.`event_post_view` (
  id BIGINT NOT NULL AUTO_INCREMENT,
  `time` INT NOT NULL,
  post_id BIGINT NOT NULL,
  user_id BIGINT NULL DEFAULT NULL,
  platform SMALLINT NULL DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS `ciyocon_oss`.`event_promotion_view` (
  id BIGINT NOT NULL AUTO_INCREMENT,
  `time` INT NOT NULL,
  promotion_id BIGINT NOT NULL,
  user_id BIGINT NULL DEFAULT NULL,
  topic_id BIGINT NULL DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS `ciyocon_oss`.`event_post_share` (
  id BIGINT NOT NULL AUTO_INCREMENT,
  `time` INT NOT NULL,
  post_id BIGINT NOT NULL,
  user_id BIGINT NULL DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS `ciyocon_oss`.`event_rec_banner_view` (
  id BIGINT NOT NULL AUTO_INCREMENT,
  `time` INT NOT NULL,
  banner_id BIGINT NOT NULL,
  user_id BIGINT NULL DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS `ciyocon_oss`.`event_game_banner_view` (
  id BIGINT NOT NULL AUTO_INCREMENT,
  `time` INT NOT NULL,
  banner_id BIGINT NOT NULL,
  user_id BIGINT NULL DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS `ciyocon_oss`.`event_official_notification_view` (
  id BIGINT NOT NULL AUTO_INCREMENT,
  `time` INT NOT NULL,
  notification_id BIGINT NOT NULL,
  user_id BIGINT NULL DEFAULT NULL,
  PRIMARY KEY (id)
);
