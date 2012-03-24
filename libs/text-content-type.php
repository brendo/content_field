<?php

	/**
	 * @package content_field
	 */
	class TextContentType implements ContentType {
		public function appendSettingsHeaders(HTMLPage $page) {

		}

		public function appendSettingsInterface(XMLElement $wrapper, $field_name, StdClass $settings = null, MessageStack $errors) {
			$legend = new XMLElement('legend');
			$legend->setValue(__('Text Content'));
			$wrapper->appendChild($legend);

			// Default size
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');

			$values = array(
				array('auto', false, __('Automatic')),
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
				$settings->{'text-size'} = 'auto';
			}

			if (isset($settings->{'text-formatter'}) === false) {
				$settings->{'text-formatter'} = 'none';
			}

			return $settings;
		}

		public function validateSettings(StdClass $data, MessageStack $errors) {
			return true;
		}

		public function appendPublishHeaders(HTMLPage $page) {
			$url = URL . '/extensions/content_field/assets';
			$page->addStylesheetToHead($url . '/publish.css', 'screen');
			$page->addScriptToHead($url . '/publish.js');
		}

		public function appendPublishInterface(XMLElement $wrapper, $field_name, StdClass $settings, StdClass $data, MessageStack $errors, $entry_id = null) {
			$header = new XMLElement('header');
			$header->addClass('main');
			$header->appendChild(
				new XMLElement('strong', __('Text Content'))
			);
			$wrapper->appendChild($header);

			$content = new XMLElement('div');
			$wrapper->appendChild($content);

			$text = Widget::Textarea(
				"{$field_name}[data]", 1, 50, (
					isset($data->value)
						? $data->value
						: null
				)
			);
			$text->addClass('size-' . $settings->{'text-size'});

			if ($settings->{'text-formatter'} != 'none') {
				$text->addClass($settings->{'text-formatter'});
			}

			$content->appendChild($text);
		}

		public function processData(StdClass $settings, StdClass $data, $entry_id = null) {
			if ($settings->{'text-formatter'} != 'none') {
				$tfm = new TextformatterManager();
				$formatter = $tfm->create($settings->{'text-formatter'});
				$formatted = $formatter->run($data->value);
				$formatted = preg_replace('/&(?![a-z]{0,4}\w{2,3};|#[x0-9a-f]{2,6};)/i', '&amp;', $formatted);
			}

			else {
				$formatted = General::sanitize($data->value);
			}

			return (object)array(
				'handle'			=> null,
				'value'				=> $data->value,
				'value_formatted'	=> $formatted
			);
		}

		public function sanitizeData(StdClass $settings, $data) {
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

		public function validateData(StdClass $settings, StdClass $data, MessageStack $errors, $entry_id = null) {
			return is_string($data->value);
		}

		public function appendFormattedElement(XMLElement $wrapper, StdClass $settings, StdClass $data, $entry_id = null) {
			$wrapper->setValue($data->value_formatted);
		}
	}