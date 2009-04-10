<?php


	Class extension_Section_Schemas extends Extension{

		public function about(){
			return array('name' => 'Section Schemas',
						 'version' => '1.1',
						 'release-date' => '2009-04-10',
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
		  	$section = $sm->fetch($section_id);
		
			$result->setAttribute('id', $section_id);
			$result->setAttribute('handle', $section->_data['handle']);
			
			$entry_count = intval($this->_Parent->Database->fetchVar('count', 0, "SELECT count(*) AS `count` FROM `tbl_entries` WHERE `section_id` = '".$section_id."' "));
			$result->setAttribute('total-entries', $entry_count);
			
			$entry =& $entryManager->create();
			$entry->set('section_id', $section_id);
			
			$section_fields = $section->fetchFields();
						
			foreach($section_fields as $section_field){
				$field = $section_field->get();
				$f = new XMLElement($field['element_name']);
				$f->setAttribute('required', $field['required']);
				
				foreach($field as $key => $value) {
					// core attributes
					if (in_array($key, array('id', 'type', 'required', 'label'))) {
						$f->setattribute($key, $value);
					}
					// filter out unwanted elements
					if (!in_array($key, array('id', 'type', 'required', 'label', 'show_column', 'sortorder', 'element_name', 'parent_section', 'location', 'field_id', 'related_field_id', 'static_options', 'dynamic_options', 'pre_populate_source', 'limit', 'allow_author_change'))) {
						if (strlen($value) > 0) {
							$f->appendChild(new XMLElement($key, $value));
						}
					}					
				}
				
				$html = new XMLElement('html');
				$section_field->displayPublishPanel($html);
				
				$dom = new DomDocument();
				$dom->loadXML($html->generate());
				$xpath = new DomXPath($dom);
				
				$options = new XMLElement('options');
				
				foreach($xpath->query("//*[name()='optgroup']") as $optgroup) {
					
					$optgroup_element = new XMLElement('optgroup');
					$optgroup_element->setAttribute('label', $optgroup->getAttribute('label'));

					$options_xpath = new DomXPath($optgroup);
					
					foreach($optgroup->getElementsByTagName('option') as $option) {
						$this->__appendOption($option, $optgroup_element);
					}					
					
					$options->appendChild($optgroup_element);
				}
				
				foreach($xpath->query("//*[name()='li'] | *[name()='option']") as $option) {
					$this->__appendOption($option, $options, ($field['type'] == 'taglist' ? $field['id'] : false));
				}
				
				if ($options->getNumberOfChildren() > 0) {
					$f->appendChild($options);
				}
				
				$result->appendChild($f);
			}
			
			return $result;
		}
		
		function __appendOption($option, &$container, $field_id=NULL) {
			$option_element = new XMLElement('option', $option->nodeValue);

			if ($option->getAttribute('value')) {
				$option_element->setAttribute('value', $option->getAttribute('value'));
			}
			
			if ($field_id) {
				$total = $this->_Parent->Database->fetchCol('count', sprintf('SELECT COUNT(handle) AS count FROM sym_entries_data_%s WHERE handle="%s"', $field_id, Lang::createHandle($option->nodeValue)));
				$option_element->setAttribute('count', $total[0]);
			}

			$container->appendChild($option_element);
		}
		
	}
		
