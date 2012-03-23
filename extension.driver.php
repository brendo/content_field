<?php

	/**
	 * @package content_field
	 */

	require_once __DIR__ . '/libs/content-type.php';
	require_once __DIR__ . '/libs/text-content-type.php';

	class Extension_Content_Field extends Extension {
		/**
		 * The name of the field settings table.
		 */
		const FIELD_TABLE = 'tbl_fields_content';

		/**
		 * Create tables and configuration.
		 *
		 * @return boolean
		 */
		public function install() {
			Symphony::Database()->query(sprintf("
				CREATE TABLE IF NOT EXISTS `%s` (
					`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`field_id` INT(11) UNSIGNED NOT NULL,
					`settings` TEXT DEFAULT NULL,
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
				self::FIELD_TABLE
			));

			return true;
		}

		public static function getContentTypes() {
			return array(
				'text'		=> new TextContentType()
			);
		}
	}