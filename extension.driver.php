<?php


	Class extension_Section_Schemas extends Extension{

		public function about(){
			return array('name' => 'Section Schemas',
						 'version' => '1.2',
						 'release-date' => '2009-04-16',
						 'author' => array('name' => 'Nick Dunn',
										   'website' => 'http://nick-dunn.co.uk',
										   'email' => 'nick.dunn@airlock.com')
				 		);
		}
		
		public function fetchNavigation() {
			return array(
				array(
					'location'	=> 100,
					'name'	=> 'Section Schemas',
					'link'	=> '/list/'
				)
			);
		}
		
		public function getSectionSchema(&$result, $section_id) {
		
			$entryManager = new EntryManager($this->_Parent);
			$sm = new SectionManager($this->_Parent);
			
			// retrieve this section
		  	$section = $sm->fetch($section_id);
			
			$result->setAttribute('id', $section_id);
			$result->setAttribute('handle', $section->_data['handle']);
			
			$entry_count = intval($this->_Parent->Database->fetchVar('count', 0, "SELECT count(*) AS `count` FROM `tbl_entries` WHERE `section_id` = '".$section_id."' "));
			$result->setAttribute('total-entries', $entry_count);
			
			// instantiate a dummy entry to instantiate fields and default values
			$entry =& $entryManager->create();
			$entry->set('section_id', $section_id);
			
			$section_fields = $section->fetchFields();
			
			// for each field in the section
			foreach($section_fields as $section_field){

				$field = $section_field->get();
				$f = new XMLElement($field['element_name']);
				$f->setAttribute('required', $field['required']);
				
				foreach($field as $key => $value) {
					// Core attributes, these are common to all fields
					if (in_array($key, array('id', 'type', 'required', 'label', 'location', 'sortorder'))) {
						$f->setattribute($key, $value);
					}
					/*
						Other properties are output as element nodes. Here we filter those we
						definitely don't want. Fields can have any number of properties, so it 
						makes sense to filter out those we don't want rather than explicitly 
						choose the ones we do.
					*/
					if (!in_array($key, array('id', 'type', 'required', 'label', 'show_column', 'sortorder', 'element_name', 'parent_section', 'location', 'field_id', 'related_field_id', 'static_options', 'dynamic_options', 'pre_populate_source', 'limit', 'allow_author_change'))) {
						if (strlen($value) > 0) {
							$f->appendChild(new XMLElement(Lang::createHandle($key), $value));
						}
					}					
				}
				
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

					$options_xpath = new DomXPath($optgroup);
					
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
				$total = $this->_Parent->Database->fetchCol('count', sprintf('SELECT COUNT(handle) AS count FROM sym_entries_data_%s WHERE handle="%s"', $field['id'], $handle));
				$option_element->setAttribute('count', $total[0]);
			}

			$container->appendChild($option_element);
		}
		
	}
		
