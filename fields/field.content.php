<?php

	/**
	 * @package content_field
	 */

	require_once __DIR__ . '/../libs/message-stack.php';
	require_once __DIR__ . '/../libs/content-type.php';
	require_once __DIR__ . '/../libs/text-content-type.php';

	class FieldContent extends Field {
		protected $errors;

		public function __construct() {
			parent::__construct();

			$this->_name = 'Content';
			$this->_required = true;
			$this->_showcolumn = false;
			$this->errors = array();
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

		/**
		 * Fetch a list of installed content types.
		 */
		public function getInstances() {
			$instances = (object)array(
				'text-content'	=> new TextContentType()
			);

			Symphony::ExtensionManager()->notifyMembers(
				'AppendContentType', '*', array(
					'items'	=> $instances
				)
			);

			$instances = (array)$instances;

			uksort($instances, function($a, $b) {
				return strcasecmp($a, $b);
			});

			return $instances;
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
			$fields['default_type'] = 'text-content';
			$fields['settings'] = new StdClass();
		}

		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);
			Extension_Content_Field::appendSettingsHeaders();

			$all_instances = $this->getInstances();
			$all_settings = $this->getSettings();
			$all_errors = isset($errors['settings'])
				? $errors['settings']
				: array();
			$order = $this->get('sortorder');

			// Default size
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');

			$values = array();

			foreach ($all_instances as $type => $instance) {
				$values[] = array($type, $this->get('default_type') == $type, $instance->getName());
			}

			$label = Widget::Label('Default Content Type');
			$label->appendChild(Widget::Select(
				"fields[{$order}][default_type]", $values
			));

			$group->appendChild($label);
			$wrapper->appendChild($group);

			$this->appendRequiredCheckbox($wrapper);

			foreach ($all_instances as $type => $instance) {
				$interface = new XMLElement('fieldset');
				$interface->addClass('content-type content-type-' . $type);
				$interface->setAttribute('data-type', $type);
				$field_name = "fields[{$order}][settings][$type]";

				$input = Widget::Input("{$field_name}[enabled]", 'no', 'hidden');
				$wrapper->appendChild($input);

				$legend = new XMLElement('legend');
				$legend->setValue($instance->getName());
				$interface->appendChild($legend);

				$settings = $instance->sanitizeSettings(
					isset($all_settings->{$type})
						? $all_settings->{$type}
						: new StdClass()
				);
				$messages = isset($all_errors[$type])
					? $all_errors[$type]
					: new MessageStack();

				$instance->appendSettingsInterface(
					$interface, $field_name,
					$settings, $messages
				);

				// Enable this content type:
				$input = Widget::Input("{$field_name}[enabled]", 'yes', 'checkbox');

				if ($settings->{'enabled'} == 'yes') {
					$input->setAttribute('checked', 'checked');
				}

				$label = Widget::Label(
					__('%s Enable this content type', array(
						$input->generate()
					))
				);
				$label->addClass('enable-content-type');
				$interface->appendChild($label);

				$wrapper->appendChild($interface);
			}
		}

		public function checkFields(array &$errors, $checkForDuplicates = true) {
			parent::checkFields($errors, $checkForDuplicates);

			$all_instances = $this->getInstances();
			$all_settings = $this->getSettings();
			$all_errors = array();
			$status = is_array($errors) && !empty($errors)
				? self::__ERROR__
				: self::__OK__;

			foreach ($all_instances as $type => $instance) {
				$settings = $instance->sanitizeSettings(
					isset($all_settings->{$type})
						? $all_settings->{$type}
						: new StdClass()
				);
				$all_errors[$type] = new MessageStack();
				$valid = $instance->validateSettings($settings, $all_errors[$type]);

				// An error occured:
				if ($valid === false) {
					$status = self::__ERROR__;
				}
			}

			if ($status == self::__ERROR__) {
				$errors['settings'] = $all_errors;
			}

			return $status;
		}

		public function commit() {
			if (!parent::commit()) return false;

			$id = $this->get('id');
			$handle = $this->handle();

			if ($id === false) return false;

			$fields = array(
				'field_id'		=> $id,
				'default_type'	=> $this->get('default_type'),
				'settings'		=> is_string($this->get('settings'))
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

		public function displayPublishPanel(XMLElement &$wrapper, $all_data = null, $error = null, $prefix = null, $postfix = null, $entry_id = null) {
			Extension_Content_Field::appendPublishHeaders();

			$all_instances = $this->getInstances();
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
			$duplicator->setAttribute('data-preselect', $this->get('default_type'));

			// Data is given is stupid backwars form, fix it:
			if (is_array($all_data)) {
				$temp = array();

				foreach ($all_data as $key => $values) {
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

				$all_data = $temp;
			}

			// Append content:
			if (is_array($all_data)) foreach ($all_data as $index => $item) {
				$field_name = "fields[$element_name][$index]";

				$type = $item['type'];
				$data = isset($item['data'])
					? json_decode($item['data'])
					: null;

				// No content type found:
				if (array_key_exists($item['type'], $all_instances) === false) {
					continue;
				}

				$instance = $all_instances[$type];
				$settings = $instance->sanitizeSettings(
					isset($all_settings->{$type})
						? $all_settings->{$type}
						: new StdClass()
				);
				$errors = isset($this->errors[$index])
					? $this->errors[$index]
					: new MessageStack();
				$data = $instance->sanitizeData($settings, $data);

				$item = new XMLElement('li');
				$item->addClass('content-type-' . $type);
				$item->setAttribute('data-type', $type);

				$header = new XMLElement('header');
				$header->addClass('main');
				$header->appendChild(
					new XMLElement('strong', $instance->getName())
				);
				$item->appendChild($header);

				$interface = new XMLElement('div');
				$item->appendChild($interface);

				$instance->appendPublishInterface(
					$interface, $field_name, $settings, $data, $errors, $entry_id
				);

				// Append content type:
				$input = new XMLElement('input');
				$input->setAttribute('name', "{$field_name}[type]");
				$input->setAttribute('type', 'hidden');
				$input->setAttribute('value', $type);
				$item->appendChild($input);

				$duplicator->appendChild($item);
			}

			// Append content templates:
			foreach ($all_instances as $type => $instance) {
				$field_name = "fields[$element_name][-1]";

				$settings = $instance->sanitizeSettings(
					isset($all_settings->{$type})
						? $all_settings->{$type}
						: new StdClass()
				);
				$errors = new MessageStack();
				$data = $instance->sanitizeData($settings, null);

				if ($settings->{'enabled'} !== 'yes') {
					continue;
				}

				$item = new XMLElement('li');
				$item->addClass('template content-type-' . $type);
				$item->setAttribute('data-type', $type);

				$header = new XMLElement('header');
				$header->addClass('main');
				$header->appendChild(
					new XMLElement('strong', $instance->getName())
				);
				$item->appendChild($header);

				$interface = new XMLElement('div');
				$item->appendChild($interface);

				$instance->appendPublishInterface(
					$interface, $field_name, $settings, $data, $errors, $entry_id
				);

				// Append content type:
				$input = new XMLElement('input');
				$input->setAttribute('name', "{$field_name}[type]");
				$input->setAttribute('type', 'hidden');
				$input->setAttribute('value', $type);
				$item->appendChild($input);

				$duplicator->appendChild($item);
			}

			$frame = new XMLElement('div');
			$frame->addClass('frame');
			$frame->appendChild($duplicator);
			$wrapper->appendChild($frame);
		}

		public function checkPostFieldData($data, &$message, $entry_id = null) {
			$is_required = $this->get('required') == 'yes';
			$all_instances = $this->getInstances();
			$all_settings = $this->getSettings();
			$has_content = false;
			$this->errors = array();

			if (is_array($data)) foreach ($data as $index => $item) {
				$has_content = true;
				$item_type = $item['type'];
				$item_data = isset($item['data'])
					? $item['data'] : null;

				// No content type found:
				if (array_key_exists($item['type'], $all_instances) === false) {
					$message = __(
						'Unable to locate content type "%s".',
						array($item['type'])
					);

					return self::__INVALID_FIELDS__;
				}

				$this->errors[$index] = new MessageStack();
				$instance = $all_instances[$item_type];
				$settings = $instance->sanitizeSettings(
					isset($all_settings->{$item_type})
						? $all_settings->{$item_type}
						: new StdClass()
				);
				$item_data = $instance->sanitizeData($settings, $item_data);
				$valid = $instance->validateData($settings, $item_data, $this->errors[$index], $entry_id);

				// An error occured:
				if ($valid === false) {
					// Show generic error message:
					if ($this->errors[$index]->valid() === false) {
						$message = __(
							"An error occured in '%s'.",
							array($this->get('label'))
						);
					}

					return self::__INVALID_FIELDS__;
				}
			}

			// Complain if no items where added:
			if ($is_required && $has_content === false) {
				$message = __(
					"'%s' is a required field.",
					array($this->get('label'))
				);

				return self::__MISSING_FIELDS__;
			}

			return self::__OK__;
		}

		public function processRawFieldData($all_data, &$status, &$message = null, $simulate = false, $entry_id = null) {
			$allowed_keys = array('handle', 'value', 'value_formatted', 'type', 'data');
			$all_instances = $this->getInstances();
			$all_settings = $this->getSettings();
			$status = self::__OK__;
			$results = array();

			if (is_array($all_data)) foreach ($all_data as $index => $item) {
				$type = $item['type'];
				$data = isset($item['data'])
					? $item['data'] : null;

				// No content type found:
				if (array_key_exists($item['type'], $all_instances) === false) {
					$message = __(
						'Unable to locate content type "%s".',
						array($item['type'])
					);
					$status = self::__ERROR__;

					return $results;
				}

				$instance = $all_instances[$type];
				$settings = $instance->sanitizeSettings(
					isset($all_settings->{$type})
						? $all_settings->{$type}
						: new StdClass()
				);
				$data = $instance->sanitizeData($settings, $data);
				$data = $instance->processData($settings, $data, $entry_id);
				$data->type = $type;
				$data->data = json_encode($data);

				foreach ($data as $key => $value) {
					if (in_array($key, $allowed_keys) === false) continue;

					$results[$key][$index] = $value;
				}
			}

			return $results;
		}

		public function fetchIncludableElements() {
			return array(
				$this->get('element_name') . ': all-items',
				$this->get('element_name') . ': one-items',
				$this->get('element_name') . ': three-items'
			);
		}

		public function appendFormattedElement(XMLElement $wrapper, $all_data, $encode = false, $mode = null, $entry_id = null) {
			$all_instances = $this->getInstances();
			$all_settings = $this->getSettings();

			// Data is given is stupid backwars form, fix it:
			if (is_array($all_data)) {
				$temp = array();

				foreach ($all_data as $key => $values) {
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

				$all_data = $temp;
			}

			$element = new XMLElement($this->get('element_name'));
			$element->setAttribute('mode', $mode);

			if (is_array($all_data)) foreach ($all_data as $index => $item) {
				$type = $item['type'];
				$data = isset($item['data'])
					? json_decode($item['data'])
					: null;

				// Limit reached
				if ($mode == 'one-items' && $index > 0) break;
				if ($mode == 'three-items' && $index > 2) break;

				// No content type found:
				if (array_key_exists($item['type'], $all_instances) === false) {
					continue;
				}

				$instance = $all_instances[$type];
				$settings = $instance->sanitizeSettings(
					isset($all_settings->{$type})
						? $all_settings->{$type}
						: new StdClass()
				);
				$errors = isset($this->errors[$index])
					? $this->errors[$index]
					: new MessageStack();
				$data = $instance->sanitizeData($settings, $data);

				$item = new XMLElement('item');
				$item->setAttribute('type', $type);

				$instance->appendFormattedElement(
					$item, $settings, $data, $entry_id
				);

				$element->appendChild($item);
			}

			$wrapper->appendChild($element);
		}

		public function prepareTableValue($data, XMLElement $link = null, $entry_id = null) {
			return null;
		}
	}