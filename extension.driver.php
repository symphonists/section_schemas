<?php


	Class extension_Section_Schemas extends Extension{

		public function about(){
			return array('name' => 'Section Schemas',
						 'version' => '0.1',
						 'release-date' => '2009-01-21',
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
				
				// grab existing values for select boxes and tag lists
				foreach($xpath->query("//*[name()='option' or name()='li']") as $option) {
					$option_element = new XMLElement('option', $option->nodeValue);
					if ($option->getAttribute('value')) {
						$option_element->setAttribute('value', $option->getAttribute('value'));
					}					
					$options->appendChild($option_element);
				}
				
				if ($options->getNumberOfChildren() > 0) {
					$f->appendChild($options);
				}
				
				$result->appendChild($f);
			}
			
			return $result;
		}
		
	}
		
