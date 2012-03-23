<?php

	/**
	 * @package content_field
	 */
	interface ContentType {
		public function getName();

		public function appendPublishInterface(XMLElement $wrapper, $field_name, StdClass $data, $entry_id = null);
		public function processData($data, $entry_id = null);

		/**
		 * Make sure data is in a format that processData and validateData expect.
		 * @param mixed $data
		 * @return mixed
		 */
		public function sanitizeData($data);

		public function validateData($data, $entry_id = null);
	}

	class ContentTypeException extends Exception {}