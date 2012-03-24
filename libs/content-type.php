<?php

	/**
	 * @package content_field
	 */
	interface ContentType {
		/**
		 * Append the settings interface stylesheets and scripts.
		 */
		public function appendSettingsHeaders(HTMLPage $page);

		/**
		 * Append the settings fieldset interface.
		 * @param XMLElement $wrapper
		 * @param string $field_name
		 * @param StdClass $settings
		 */
		public function appendSettingsInterface(XMLElement $wrapper, $field_name, StdClass $settings = null, MessageStack $errors);

		/**
		 * Make sure the settings are in the correct format.
		 * @param mixed $settings
		 * @return StdClass
		 */
		public function sanitizeSettings($settings);

		/**
		 * Check that the settings are valid.
		 * @param StdClass $data
		 * @param mixed $entry_id
		 * @return StdClass
		 */
		public function validateSettings(StdClass $data, MessageStack $errors);

		/**
		 * Append the publish interface stylesheets and scripts.
		 */
		public function appendPublishHeaders(HTMLPage $page);

		/**
		 * Append the publish duplicator interface.
		 * @param XMLElement $wrapper
		 * @param string $field_name
		 * @param StdClass $data
		 * @param mixed $entry_id
		 */
		public function appendPublishInterface(XMLElement $wrapper, $field_name, StdClass $data, MessageStack $errors, $entry_id = null);

		/**
		 * Prepare the data to be saved in the database.
		 * @param StdClass $data
		 * @param mixed $entry_id
		 * @return StdClass
		 */
		public function processData(StdClass $data, $entry_id = null);

		/**
		 * Make sure data is in a format that processData and validateData expect.
		 * @param mixed $data
		 * @return StdClass
		 */
		public function sanitizeData($data);

		/**
		 * Check that the data is valid.
		 * @param StdClass $data
		 * @param mixed $entry_id
		 * @return StdClass
		 */
		public function validateData(StdClass $data, MessageStack $errors, $entry_id = null);
	}