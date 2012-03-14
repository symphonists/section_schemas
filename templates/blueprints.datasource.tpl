<?php

	require_once(EXTENSIONS . '/section_schemas/data-sources/datasource.section_schema.php');

	Class datasource<!-- CLASS NAME --> extends SectionSchemaDatasource {

		public $dsParamROOTELEMENT = '%s';
		public $dsParamSECTION = '%s';
		public $dsParamFIELDS = array(%s);

		public function about(){
			return array(
				'name' => '<!-- NAME -->',
				'author' => array(
					'name' => '<!-- AUTHOR NAME -->',
					'website' => '<!-- AUTHOR WEBSITE -->',
					'email' => '<!-- AUTHOR EMAIL -->'),
				'version' => '<!-- VERSION -->',
				'release-date' => '<!-- RELEASE DATE -->'
			);
		}

		public function allowEditorToParse(){
			return true;
		}

	}
