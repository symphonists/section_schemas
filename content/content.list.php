<?php

	require_once(CONTENT . '/content.blueprintsdatasources.php');
	require_once(TOOLKIT . '/class.eventmanager.php');

	class contentExtensionSection_SchemasList extends AdministrationPage {
		protected $_driver;

		public function __viewIndex() {
			$this->_driver = $this->_Parent->ExtensionManager->create('section_schemas');

			$this->setPageType('form');
			$this->setTitle('Symphony &ndash; ' . __('Section Schemas'));

			$this->appendSubheading(__('Section Schemas'));

			$container = new XMLElement('fieldset');
			$container->setAttribute('class', 'settings');
			$container->appendChild(
				new XMLElement('legend', __('Sections'))
			);

			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');

			$sm = new SectionManager($this->_Parent);
			$sections = $sm->fetch();
			$options = array();

			$dsm = new DatasourceManager($this->_Parent);
			$datasources = $dsm->listAll();

			foreach ($sections as $section) {

				$selected = in_array('section_schema_' . str_replace('-', '_', $section->get('handle')), array_keys($datasources));

				$options[] = array(
					$section->get('handle'), $selected, $section->get('name')
				);
			}

			$section = Widget::Label(__('Create data sources for these sections:'));
			$section->appendChild(Widget::Select(
				'sections[]', $options, array(
					'multiple'	=> 'multiple'
				)
			));

			$group->appendChild($section);

			$container->appendChild($group);
			$this->Form->appendChild($container);

		//---------------------------------------------------------------------

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');

			$attr = array('accesskey' => 's');
			$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit', $attr));

			$this->Form->appendChild($div);
		}

		public function __actionIndex() {
			$sections_post  = @$_POST['sections'];

			if (empty($this->_driver)) {
				$this->_driver = $this->_Parent->ExtensionManager->create('section_schemas');
			}

			if (@isset($_POST['action']['save'])) {

				$blueprint = new contentBlueprintsDatasources($this->_Parent);

				$sm = new SectionManager($this->_Parent);
				$sections = $sm->fetch();

				foreach ($sections as $section) {

					$file = DATASOURCES . '/data.section_schema_' . str_replace('-', '_', $section->get('handle')) . '.php';

					General::deleteFile($file);

					if (in_array($section->get('handle'), $sections_post)) {

						$dsShell = file_get_contents(TEMPLATE . '/datasource.tpl');

						$dsShell = str_replace("require_once(TOOLKIT . '/class.datasource.php');", "require_once(TOOLKIT . '/class.datasource.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(TOOLKIT . '/class.fieldmanager.php');
	require_once(TOOLKIT . '/class.entrymanager.php');", $dsShell);

						$dsShell = str_replace('<!-- CLASS NAME -->', 'section_schema_' . str_replace('-', '_', $section->get('handle')), $dsShell);
						$dsShell = str_replace('<!-- FILTERS -->', '', $dsShell);
						$dsShell = str_replace('<!-- INCLUDED ELEMENTS -->', '', $dsShell);
						$dsShell = str_replace('<!-- DS DEPENDENCY LIST -->', '""', $dsShell);

						$params['rootelement'] = 'section-schema';
						$blueprint->__injectVarList($dsShell, $params);

						$about = array(
							'name' => 'Section Schema: ' . $section->get('name'),
							'version' => '1.0',
							'release date' => DateTimeObj::getGMT('c'),
							'author name' => $this->_Parent->Author->getFullName(),
							'author website' => URL,
							'author email' => $this->_Parent->Author->get('email')
						);

						$blueprint->__injectAboutInformation($dsShell, $about);

						$dsShell = str_replace('<!-- SOURCE -->', $section->get('id'), $dsShell);

						$dsShell = str_replace('return true;', 'return false;', $dsShell);

						$dsShell = str_replace('<!-- GRAB -->', "\$extension = \$this->_Parent->ExtensionManager->create('section_schemas');" . self::CRLF . "\t\t\t\t\$extension->getSectionSchema(\$result, \$this->getSource());", $dsShell);

						$dsShell = str_replace('<!-- EXTRAS -->', '', $dsShell);

						if(!is_writable(dirname($file)) || !$write = General::writeFile($file, $dsShell, $this->_Parent->Configuration->get('write_mode', 'file'))) {
							$this->pageAlert(__('Failed to write data sources to <code>%s</code>. Please check permissions.', array(DATASOURCES)), Alert::ERROR);
						} else {
							$this->pageAlert('Section Schema data sources saved.', Alert::SUCCESS);
						}

					}
				}
			}

		}

	}

?>