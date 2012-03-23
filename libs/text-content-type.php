<?php

	/**
	 * @package content_field
	 */
	class TextContentType implements ContentType {
		public function getName() {
			return 'Text';
		}

		public function appendPublishInterface(XMLElement $wrapper, $field_name, StdClass $data, $entry_id = null) {
			$text = Widget::Textarea(
				"{$field_name}[data]", 3, 50, (
					isset($data->value)
						? $data->value
						: null
				)
			);
			$wrapper->appendChild($text);
		}

		public function appendSettingsInterface(XMLElement $wrapper, $field_name, StdClass $settings = null) {
			$legend = new XMLElement('legend');
			$legend->setValue(__('Text Content'));
			$wrapper->appendChild($legend);

			// Default size
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');

			$values = array(
				array('small', false, __('Small Box')),
				array('medium', false, __('Medium Box')),
				array('large', false, __('Large Box')),
				array('huge', false, __('Huge Box'))
			);

			foreach ($values as &$value) {
				$value[1] = $value[0] == $settings->{'text-size'};
			}

			$label = Widget::Label('Default Size');
			$label->appendChild(Widget::Select(
				"{$field_name}[text-size]", $values
			));

			$group->appendChild($label);

			// Text formatter:
			$field = new Field();
			$group->appendChild($field->buildFormatterSelect(
				isset($settings->{'text-formatter'})
					? $settings->{'text-formatter'}
					: null,
				"{$field_name}[text-formatter]",
				'Text Formatter'
			));
			$wrapper->appendChild($group);

			// Enable this content type:
			$input = Widget::Input("{$field_name}[enabled]", 'yes', 'checkbox');

			if ($settings->{'enabled'} == 'yes') {
				$input->setAttribute('checked', 'checked');
			}

			$wrapper->appendChild(Widget::Label(
				__('%s Enable the Text content type', array(
					$input->generate()
				))
			));
		}

		public function processData($data, $entry_id = null) {
			return (object)array(
				'handle'			=> Lang::createHandle($data->value),
				'value'				=> $data->value,
				'value_formatted'	=> htmlentities($data->value)
			);
		}

		public function sanitizeData($data) {
			$result = (object)array(
				'value'	=> null
			);

			if (is_object($data) && isset($data->value)) {
				return $data;
			}

			if (is_array($data) && isset($data['value'])) {
				return (object)$data;
			}

			if (is_string($data)) {
				$result->value = $data;
			}

			return $result;
		}

		public function sanitizeSettings($settings) {
			if (is_array($settings)) {
				$settings = (object)$settings;
			}

			else if (is_object($settings) === false) {
				$settings = new StdClass();
			}

			if (isset($settings->{'enabled'}) === false) {
				$settings->{'enabled'} = 'no';
			}

			if (isset($settings->{'text-size'}) === false) {
				$settings->{'text-size'} = 'medium';
			}

			if (isset($settings->{'text-formatter'}) === false) {
				$settings->{'text-formatter'} = null;
			}

			return $settings;
		}

		public function validateData($data, $entry_id = null) {
			if ($is_required) {
				return is_string($data->value)
					&& strlen(trim($data->value)) > 0;
			}

			return is_string($data->value);
		}
	}