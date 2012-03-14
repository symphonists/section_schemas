<?php

	require_once EXTENSIONS . '/section_schemas/data-sources/datasource.section_schema.php';

	Class Extension_Section_Schemas extends Extension {

		private static $provides = array();
		
		public static function registerProviders() {
			self::$provides = array(
				'data-sources' => array(
					'SectionSchemaDatasource' => SectionSchemaDatasource::getName()
				)
			);
			return true;
		}

		public static function providerOf($type = null) {
			self::registerProviders();
			if(is_null($type)) return self::$provides;
			if(!isset(self::$provides[$type])) return array();
			return self::$provides[$type];
		}

	}