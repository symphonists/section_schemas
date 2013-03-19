<?php

	require_once(TOOLKIT . '/class.datasource.php');
	require_once(TOOLKIT . '/class.entrymanager.php');
	require_once(FACE . '/interface.datasource.php');

	Class SectionSchemaDatasource extends DataSource implements iDatasource {

		private static $_incompatible_publishpanel = array('mediathek', 'subsectionmanager', 'imagecropper', 'readonlyinput', 'author', 'entry_versions', 'status');

		public static function getName() {
			return __('Section Schema');
		}

		public static function getClass() {
			return __CLASS__;
		}

		public function getSource() {
			return self::getClass();
		}

		public static function getTemplate(){
			return EXTENSIONS . '/section_schemas/templates/blueprints.datasource.tpl';
		}

		public function settings() {
			$settings = array();
			$settings[self::getClass()]['section'] = $this->dsParamSECTION;
			$settings[self::getClass()]['fields'] = $this->dsParamFIELDS;

			if(is_null($settings[self::getClass()]['fields'])) $settings[self::getClass()]['fields'] = array();

			return $settings;
		}

	/*-------------------------------------------------------------------------
		Utilities
	-------------------------------------------------------------------------*/

		/**
		 * Returns the source value for display in the Datasources index
		 *
		 * @param string $file
		 *  The path to the Datasource file
		 * @return string
		 */
		public function getSourceColumn($handle) {
			$datasource = DatasourceManager::create($handle, array(), false);
			return 'Section Schema: ' . $datasource->dsParamSECTION;
		}


	/*-------------------------------------------------------------------------
		Editor
	-------------------------------------------------------------------------*/

		public static function buildEditor(XMLElement $wrapper, array &$errors = array(), array $settings = null, $handle = null) {

			Administration::instance()->Page->addScriptToHead(URL . '/extensions/section_schemas/assets/section_schemas.datasource.js', 100);

			if(is_null($settings[self::getClass()]['fields'])) $settings[self::getClass()]['fields'] = array();

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual ' . __CLASS__);
			$fieldset->appendChild(new XMLElement('legend', self::getName()));

			$group = new XMLElement('div');
			$group->setAttribute('class', 'two columns');

			$options = array();
			$sections = SectionManager::fetch();
			foreach($sections as $section) {
				$options[] = array(
					$section->get('handle'), $settings[self::getClass()]['section'] == $section->get('handle'), $section->get('name')
				);
			}

			$label = Widget::Label(__('Section'));
			$label->setAttribute('class', 'column');
			$label->appendChild(
				Widget::Select('fields[' . self::getClass() . '][section]', $options)
			);
			$group->appendChild($label);

			foreach($sections as $section) {

				$fields = $section->fetchFields();
				$options = array();
				foreach($fields as $field) {
					$options[] = array(
						$field->get('element_name'), in_array($field->get('element_name'), $settings[self::getClass()]['fields']), $field->get('label') . ' (' . $field->get('type') . ')'
					);
				}

				$label = Widget::Label(__('Fields'));
				$label->setAttribute('class', 'column fields fields-for-' . $section->get('handle'));
				$label->appendChild(
					Widget::Select('fields[' . self::getClass() . '][fields][]', $options, array('multiple' => 'multiple'))
				);
				$group->appendChild($label);

			}

			$fieldset->appendChild($group);

			$wrapper->appendChild($fieldset);
		}

		public static function validate(array &$settings, array &$errors) {
			return true;
		}

		public static function prepare(array $settings, array $params, $template) {
			return sprintf($template,
				$params['rootelement'],
				$settings[self::getClass()]['section'],
				count($settings[self::getClass()]['fields'] > 0) ? "'" . implode("','", $settings[self::getClass()]['fields']) . "'" : ''
			);
		}

	/*-------------------------------------------------------------------------
		Execution
	-------------------------------------------------------------------------*/

		public function grab(array &$param_pool=NULL) {
			$result = new XMLElement($this->dsParamROOTELEMENT);

			// retrieve this section
			$section_id = SectionManager::fetchIDFromHandle($this->dsParamSECTION);
		  	$section = SectionManager::fetch($section_id);

			$result->setAttribute('id', $section_id);
			$result->setAttribute('handle', $section->get('handle'));

			$entry_count = EntryManager::fetchCount($section_id);
			$result->setAttribute('total-entries', $entry_count);

			// instantiate a dummy entry to instantiate fields and default values
			$entry = EntryManager::create();
			$entry->set('section_id', $section_id);

			$section_fields = $section->fetchFields();

			// for each field in the section
			foreach($section_fields as $section_field){

				$field = $section_field->get();

				// Skip fields that have not been selected:
				if (!in_array($field['element_name'], $this->dsParamFIELDS)) continue;

				$f = new XMLElement($field['element_name']);
				$f->setAttribute('required', $field['required']);

				foreach($field as $key => $value) {
					// Core attributes, these are common to all fields
					if (in_array($key, array('id', 'type', 'required', 'label', 'location', 'show_column', 'sortorder'))) {
						$f->setattribute(Lang::createHandle($key), General::sanitize($value));
					}
					/*
						Other properties are output as element nodes. Here we filter those we
						definitely don't want. Fields can have any number of properties, so it
						makes sense to filter out those we don't want rather than explicitly
						choose the ones we do.
					*/
					if (!in_array($key, array('id', 'type', 'required', 'label', 'show_column', 'sortorder', 'element_name', 'parent_section', 'location', 'field_id', 'related_field_id', 'static_options', 'dynamic_options', 'pre_populate_source', 'limit', 'allow_author_change'))) {
						if (strlen($value) > 0) {
							$f->appendChild(new XMLElement(Lang::createHandle($key), General::sanitize($value)));
						}
					}
				}

				// Allow a field to define its own schema XML:
				if (method_exists($section_field, 'appendFieldSchema')) {
					$section_field->appendFieldSchema($f);
					$result->appendChild($f);
					continue;
				}

				// check that we can safely inspect output of displayPublishPanel (some custom fields do not work)
				if (in_array($field['type'], self::$_incompatible_publishpanel)) continue;

				// grab the HTML used in the Publish entry form
				$html = new XMLElement('html');
				$section_field->displayPublishPanel($html);

				$dom = new DomDocument();
				$dom->loadXML($html->generate());

				$xpath = new DomXPath($dom);

				$options = new XMLElement('options');

				// find optgroup elements (primarily in Selectbox Link fields)
				foreach($xpath->query("//*[name()='optgroup']") as $optgroup) {

					$optgroup_element = new XMLElement('optgroup');
					$optgroup_element->setAttribute('label', $optgroup->getAttribute('label'));

					// append child options of this group
					foreach($optgroup->getElementsByTagName('option') as $option) {
						$this->__appendOption($option, $optgroup_element, $field);
					}

					$options->appendChild($optgroup_element);
				}

				// find options that aren't children of groups, and list items (primarily for Taglists)
				foreach($xpath->query("//*[name()='option' and not(parent::optgroup)] | //*[name()='li']") as $option) {
					$this->__appendOption($option, $options, $field);
				}

				if ($options->getNumberOfChildren() > 0) {
					$f->appendChild($options);
				}

				/*
					When an input has a value and is a direct child of the label, we presume we may need
					its value (e.g. a pre-populated Date, Order Entries etc.)
				*/
				$single_input_value = $xpath->query("//label/input[@value!='']")->item(0);
				if ($single_input_value) {
					$f->appendChild(new XMLElement('initial-value', $single_input_value->getAttribute('value')));
				}

				$result->appendChild($f);
			}

			return $result;

		}

		function __appendOption($option, &$container, $field) {
			$option_element = new XMLElement('option', $option->nodeValue);

			if (strlen($option->nodeValue) == 0) return;

			$handle = Lang::createHandle($option->nodeValue);

			if ($option->getAttribute('value')) {
				$option_element->setAttribute('value', $option->getAttribute('value'));
			}

			// add handles to option elements
			if (in_array($field['type'], array('select', 'taglist', 'author'))) {
				$option_element->setAttribute('handle', $handle);
			}

			// generate counts for tags
			if ($field['type'] == 'taglist') {
				$total = Symphony::Database()->fetchCol('count', sprintf('SELECT COUNT(handle) AS count FROM tbl_entries_data_%s WHERE handle="%s"', $field['id'], $handle));
				$option_element->setAttribute('count', $total[0]);
			}

			$container->appendChild($option_element);
		}
	}

	return 'SectionSchemaDatasource';
