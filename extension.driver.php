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
		 * Fetch a list of installed content types.
		 */
		public static function getContentTypes() {
			$content_types = (object)array(
				'text'		=> new TextContentType()
			);

			Symphony::ExtensionManager()->notifyMembers(
				'AppendContentType', '*', array(
					'items'	=> $content_types
				)
			);

			return (array)$content_types;
		}

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
				$page = Administration::instance()->Page;
				$url = URL . '/extensions/content_field/assets';

				$page->addStylesheetToHead($url . '/settings.css', 'screen');
				$page->addScriptToHead($url . '/settings.js');

				foreach (self::getContentTypes() as $instance) {
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
				$page = Administration::instance()->Page;
				$url = URL . '/extensions/content_field/assets';

				$page->addStylesheetToHead($url . '/publish.css', 'screen');
				$page->addScriptToHead($url . '/publish.js');
				$page->addScriptToHead($url . '/jquery.autoresize.js');

				foreach (self::getContentTypes() as $instance) {
					$instance->appendPublishHeaders($page);
				}

				self::$appendedHeaders &= $type;
			}
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
					`settings` TEXT DEFAULT NULL,
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
				self::FIELD_TABLE
			));
		}
	}