<?php

	/**
	 * @package content_field
	 */

	require_once __DIR__ . '/libs/message-stack.php';
	require_once __DIR__ . '/libs/content-type.php';
	require_once __DIR__ . '/libs/text-content-type.php';

	class Extension_Content_Field extends Extension {
		/**
		 * The name of the field settings table.
		 */
		const FIELD_TABLE = 'tbl_fields_content';

		/**
		 * Publish page headers.
		 */
		const PUBLISH_HEADERS = 1;

		/**
		 * Datasource filter page headers.
		 */
		const FILTER_HEADERS = 2;

		/**
		 * Publish settings page headers.
		 */
		const SETTING_HEADERS = 4;

		/**
		 * What headers have been appended?
		 *
		 * @var integer
		 */
		static protected $appendedHeaders = 0;

		/**
		 * Add settings interface headers to the page.
		 */
		static public function appendSettingsHeaders() {
			$type = self::SETTING_HEADERS;

			if (
				(self::$appendedHeaders & $type) !== $type
				&& class_exists('Administration')
				&& Administration::instance() instanceof Administration
				&& Administration::instance()->Page instanceof HTMLPage
			) {
				$field = new FieldContent();
				$page = Administration::instance()->Page;

				foreach ($field->getInstances() as $instance) {
					$instance->appendSettingsHeaders($page);
				}

				self::$appendedHeaders &= $type;
			}
		}

		/**
		 * Add settings interface headers to the page.
		 */
		static public function appendPublishHeaders() {
			$type = self::PUBLISH_HEADERS;

			if (
				(self::$appendedHeaders & $type) !== $type
				&& class_exists('Administration')
				&& Administration::instance() instanceof Administration
				&& Administration::instance()->Page instanceof HTMLPage
			) {
				$field = new FieldContent();
				$page = Administration::instance()->Page;

				foreach ($field->getInstances() as $instance) {
					$instance->appendPublishHeaders($page);
				}

				self::$appendedHeaders &= $type;
			}
		}

		/**
		 * Cleanup installation.
		 *
		 * @return boolean
		 */
		public function uninstall() {
			Symphony::Database()->query(sprintf(
				"DROP TABLE `%s`",
				self::FIELD_TABLE
			));

			return true;
		}

		/**
		 * Create tables and configuration.
		 *
		 * @return boolean
		 */
		public function install() {
			return Symphony::Database()->query(sprintf("
				CREATE TABLE IF NOT EXISTS `%s` (
					`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`field_id` INT(11) UNSIGNED NOT NULL,
					`default_type` VARCHAR(255) DEFAULT NULL,
					`settings` TEXT DEFAULT NULL,
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
				self::FIELD_TABLE
			));
		}
	}