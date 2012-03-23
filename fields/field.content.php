<?php

	/**
	 * @package content_field
	 */

	class FieldContent extends Field {
		public function __construct() {
			parent::__construct();

			$this->_name = 'Content';
			$this->_required = true;
			$this->_showcolumn = false;
		}

		public function createTable() {
			$field_id = $this->get('id');

			return Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_entries_data_{$field_id}` (
					`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`entry_id` INT(11) UNSIGNED NOT NULL,
					`handle` VARCHAR(255) DEFAULT NULL,
					`value` TEXT DEFAULT NULL,
					`value_formatted` TEXT DEFAULT NULL,
					`type` VARCHAR(64) DEFAULT NULL,
					`data` TEXT DEFAULT NULL,
					PRIMARY KEY (`id`),
					KEY `entry_id` (`entry_id`),
					FULLTEXT KEY `value` (`value`),
					FULLTEXT KEY `value_formatted` (`value_formatted`),
					FULLTEXT KEY `type` (`type`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;
			");
		}

		public function getSettings() {
			if (is_object($this->get('settings'))) {
				return $this->get('settings');
			}

			else if (is_array($this->get('settings'))) {
				return (object)$this->get('settings');
			}

			return json_decode($this->get('settings'));
		}

		public function findDefaults(array &$fields) {
			$fields['required'] = 'yes';
			$fields['settings'] = new StdClass();
		}

		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);
			$content_types = Extension_Content_Field::getContentTypes();
			$order = $this->get('sortorder');

			$this->appendRequiredCheckbox($wrapper);

			$all_settings = $this->getSettings();

			foreach ($content_types as $type => $instance) {
				$interface = new XMLElement('fieldset');
				$interface->addClass('content-type-' . $type);
				$interface->setAttribute('data-type', $type);

				$input = Widget::Input(
					"fields[$order][settings][$type][enabled]", 'no', 'hidden'
				);

				$settings = $instance->sanitizeSettings(
					isset($all_settings->{$type})
						? $all_settings->{$type}
						: new StdClass()
				);

				$instance->appendSettingsInterface(
					$interface, "fields[$order][settings][$type]", $settings
				);

				$wrapper->appendChild($interface);
			}
		}

		public function commit() {
			if (!parent::commit()) return false;

			$id = $this->get('id');
			$handle = $this->handle();

			if ($id === false) return false;

			$fields = array(
				'field_id'	=> $id,
				'settings'	=> is_string($this->get('settings'))
					? $this->get('settings')
					: json_encode($this->get('settings'))
			);

			Symphony::Database()->query("
				DELETE FROM
					`tbl_fields_{$handle}`
				WHERE
					`field_id` = '{$id}'
				LIMIT 1
			");

			return Symphony::Database()->insert($fields, "tbl_fields_{$handle}");
		}

		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $error = null, $prefix = null, $postfix = null, $entry_id = null) {
			$content_types = Extension_Content_Field::getContentTypes();
			$all_settings = $this->getSettings();

			$sortorder = $this->get('sortorder');
			$element_name = $this->get('element_name');
			$label = Widget::Label($this->get('label'));

			if ($this->get('required') != 'yes') {
				$label->appendChild(new XMLElement('i', __('Optional')));
			}

			if ($error != null) {
				$label = Widget::wrapFormElementWithError($label, $error);
			}

			$wrapper->appendChild($label);

			$duplicator = new XMLElement('ol');
			$duplicator->addClass('content-field-duplicator');

			// Data is given is stupid backwars form, fix it:
			if (is_array($data)) {
				$temp = array();

				foreach ($data as $key => $values) {
					if (is_array($values) === false) {
						if (isset($temp[0]) === false) {
							$temp[0] = array();
						}

						$temp[0][$key] = $values;
					}

					else foreach ($values as $index => $value) {
						$temp[$index][$key] = $value;
					}
				}

				$data = $temp;
			}

			// Append content:
			if (is_array($data)) foreach ($data as $index => $item) {
				$field_name = "fields[$element_name][$index]";

				$item_type = $item['type'];
				$item_data = isset($item['data'])
					? json_decode($item['data'])
					: null;

				// No content type found:
				if (array_key_exists($item['type'], $content_types) === false) {
					continue;
				}

				$instance = $content_types[$item_type];
				$item_data = $instance->sanitizeData($item_data);

				$item = new XMLElement('li');
				$item->addClass('content-type-' . $type);
				$item->setAttribute('data-type', $type);

				// Append header:
				$header = new XMLElement('header');
				$header->addClass('main');
				$header->appendChild(
					new XMLElement('strong', $instance->getName())
				);
				$item->appendChild($header);

				// Append content type:
				$input = new XMLElement('input');
				$input->setAttribute('name', "{$field_name}[type]");
				$input->setAttribute('type', 'hidden');
				$input->setAttribute('value', $item_type);
				$item->appendChild($input);

				// Append interface:
				$interface = new XMLElement('div');

				$instance->appendPublishInterface(
					$interface, $field_name, $item_data, $entry_id
				);

				$item->appendChild($interface);
				$duplicator->appendChild($item);
			}

			// Append content templates:
			foreach ($content_types as $type => $instance) {
				$field_name = "fields[$element_name][-1]";

				$settings = $instance->sanitizeSettings(
					isset($all_settings->{$type})
						? $all_settings->{$type}
						: new StdClass()
				);

				if ($settings->{'enabled'} !== 'yes') {
					continue;
				}

				$item = new XMLElement('li');
				$item->addClass('template content-type-' . $type);
				$item->setAttribute('data-type', $type);

				// Append header:
				$header = new XMLElement('header');
				$header->addClass('main');
				$header->appendChild(
					new XMLElement('strong', $instance->getName())
				);
				$item->appendChild($header);

				// Append content type:
				$input = new XMLElement('input');
				$input->setAttribute('name', "{$field_name}[type]");
				$input->setAttribute('type', 'hidden');
				$input->setAttribute('value', $type);
				$item->appendChild($input);

				// Append interface:
				$interface = new XMLElement('div');

				$instance->appendPublishInterface(
					$interface, $field_name,
					new StdClass(), $entry_id
				);

				$item->appendChild($interface);
				$duplicator->appendChild($item);
			}

			$frame = new XMLElement('div');
			$frame->addClass('frame');
			$frame->appendChild($duplicator);
			$wrapper->appendChild($frame);

			$script = new XMLElement('script', '
				jQuery("ol.content-field-duplicator")
					.symphonyDuplicator({
						orderable: true,
						collapsible: true
					});
			');
			$wrapper->appendChild($script);

			$style = new XMLElement('style', '
				div.field.field-content > label {
					margin-bottom: 2px;
				}
				div.field.field-content > div.frame {
					margin-bottom: 15px;
				}
			');
			$wrapper->appendChild($style);
		}

		public function checkPostFieldData(&$data, &$message, $entry_id = null) {
			$content_types = Extension_Content_Field::getContentTypes();
			$is_required = $this->get('required') == 'yes';
			$has_content = false;

			if (is_array($data)) foreach ($data as $item) {
				$has_content = true;
				$item_type = $item['type'];
				$item_data = isset($item['data'])
					? $item['data'] : null;

				// No content type found:
				if (array_key_exists($item['type'], $content_types) === false) {
					$message = __(
						'Unable to locate content type "%s".',
						array($item['type'])
					);

					return self::__INVALID_FIELDS__;
				}

				$instance = $content_types[$item_type];
				$item_data = $instance->sanitizeData($item_data);
				$item_ok = $instance->validateData($item_data, $entry_id);

				if ($item_ok === false) {
					return self::__INVALID_FIELDS__;
				}
			}

			if ($is_required && $has_content === false) {
				$message = __(
					"'%s' is a required field.",
					array($this->get('label'))
				);

				return self::__MISSING_FIELDS__;
			}

			return self::__OK__;
		}

		public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null) {
			$content_types = Extension_Content_Field::getContentTypes();
			$status = self::__OK__;
			$results = array();

			if (is_array($data)) foreach ($data as $index => $item) {
				$item_type = $item['type'];
				$item_data = isset($item['data'])
					? $item['data'] : null;

				// No content type found:
				if (array_key_exists($item['type'], $content_types) === false) {
					$message = __('Unable to locate content type "%s".', $item['type']);
					$status = self::__ERROR__;

					return $results;
				}

				$instance = $content_types[$item_type];
				$item_data = $instance->sanitizeData($item_data);
				$item_data = $instance->processData($item_data, $entry_id);

				if ($item_ok === false) {
					$status = self::__ERROR__;

					return $results;
				}

				$item_data->type = $item_type;
				$item_data->data = json_encode($item_data);

				foreach ($item_data as $key => $value) {
					$results[$key][$index] = $value;
				}
			}

			return $results;
		}

		public function prepareTableValue($data, XMLElement $link = null, $entry_id = null) {
			return null;
		}
	}