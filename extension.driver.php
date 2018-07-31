<?php
	
	class Extension_Entry_URL_Field extends Extension {
		
		protected static $fields = array();
		
		public function uninstall() {
			Symphony::Database()->query("DROP TABLE `tbl_fields_entry_url`");
		}

		public function update($previousVersion = false){
			if( version_compare($prev_version, '1.3.0', '<') ){
				$fields = Symphony::Database()->fetch("SELECT `field_id`,`anchor_label` FROM `tbl_fields_entry_url`");

				foreach( $fields as $field ){
					$entries_table = 'tbl_entries_data_'.$field["field_id"];

					Symphony::Database()->query("ALTER TABLE `{$entries_table}` ADD COLUMN `label` TEXT DEFAULT NULL");
					Symphony::Database()->update(array('label' => $field['anchor_label']), $entries_table);
				}
			}
			if( version_compare($prev_version, '2.0', '<') ){
				Symphony::Database()->query("ALTER TABLE `tbl_fields_entry_url` ADD COLUMN `datasources` VARCHAR(255) DEFAULT NULL");
				Symphony::Database()->query("ALTER TABLE `tbl_fields_entry_url` ADD COLUMN `handle_source` VARCHAR(255) DEFAULT NULL");
				Symphony::Database()->query("ALTER TABLE `tbl_fields_entry_url` ADD COLUMN `handle_length` TINYINT(3) UNSIGNED DEFAULT 255");

				$fields = Symphony::Database()->fetch("SELECT `field_id` FROM `tbl_fields_entry_url`");

				foreach( $fields as $field ){
					$entries_table = 'tbl_entries_data_'.$field["field_id"];

					Symphony::Database()->query("ALTER TABLE `{$entries_table}` ADD COLUMN `handle` VARCHAR(255) DEFAULT NULL");
				}
			}
			if( version_compare($prev_version, '2.2.0', '<') ){
				Symphony::Database()->query("ALTER TABLE `tbl_fields_entry_url` ADD COLUMN `sync` ENUM('yes', 'no') DEFAULT 'no'");
			}

			return true;
		}
		
		public function install() {
			Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_entry_url` (
					`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`field_id` INT(11) UNSIGNED NOT NULL,
					`anchor_label` VARCHAR(255) DEFAULT NULL,
					`expression` VARCHAR(255) DEFAULT NULL,
					`datasources` VARCHAR(255) DEFAULT NULL,
					`new_window` ENUM('yes', 'no') DEFAULT 'no',
					`handle_source` VARCHAR(255) DEFAULT NULL,
					`handle_length` TINYINT(3) UNSIGNED DEFAULT 255,
					`hide` ENUM('yes', 'no') DEFAULT 'no',
  					`sync` ENUM('yes', 'no') DEFAULT 'no',
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				)
			");
			
			return true;
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/publish/new/',
					'delegate'	=> 'EntryPostCreate',
					'callback'	=> 'compileBackendFields'
				),
				array(
					'page'		=> '/publish/edit/',
					'delegate'	=> 'EntryPostEdit',
					'callback'	=> 'compileBackendFields'
				),
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'EventPostSaveFilter',
					'callback'	=> 'compileFrontendFields'
				),
				array(
					'page' => '/xmlimporter/importers/run/',
					'delegate' => 'XMLImporterEntryPostCreate',
					'callback' => 'compileImportFields',
				),
				array(
					'page' => '/xmlimporter/importers/run/',
					'delegate' => 'XMLImporterEntryPostEdit',
					'callback' => 'compileImportFields',
				),
				array(
					'page' => '/backend/',
					'delegate' => 'InitialiseAdminPageHead',
					'callback' => 'initializeAdmin',
				),
			);
		}
		
	/*-------------------------------------------------------------------------
		Utilities:
	-------------------------------------------------------------------------*/
		
		public function getDom($entry,$datasources = array(),$entry_url_field_id) {
			$entry_xml = new XMLElement('entry');
			$data = $entry->getData();
			
			$entry_xml->setAttribute('id', $entry->get('id'));
			
			$associated = $entry->fetchAllAssociatedEntryCounts();
			
			if (is_array($associated) and !empty($associated)) {
				foreach ($associated as $section => $count) {
					$related_section = SectionManager::fetch($section);
					$entry_xml->setAttribute($related_section->get('handle'), (string)$count);
				}
			}
			
			// Add fields:
			foreach ($data as $field_id => $values) {
				if (empty($field_id)) continue;
				
				$field = FieldManager::fetch($field_id);
				$field->appendFormattedElement($entry_xml, $values, false);
			}

			FieldManager::fetch($entry_url_field_id)->setReady(true);
			
			$xml = new XMLElement('data');
			$xml->appendChild($entry_xml);

			//generate parameters such as root and add into dom
			$date = new DateTime();
			$params = array(
	            'today' => $date->format('Y-m-d'),
	            'current-time' => $date->format('H:i'),
	            'this-year' => $date->format('Y'),
	            'this-month' => $date->format('m'),
	            'this-day' => $date->format('d'),
	            'timezone' => $date->format('P'),
	            'website-name' => Symphony::Configuration()->get('sitename', 'general'),
	            'root' => URL,
	            'workspace' => URL . '/workspace',
	            'http-host' => HTTP_HOST
	        );

			if ($datasources){
				foreach ($datasources as $dsName) {
					$ds = DatasourceManager::create($dsName, $params);
					$arr = array();
					$dsXml = $ds->execute($arr); 
					$xml->appendChild($dsXml);
				}
			}

			//in case there are url params they will also be added in the xml
	        $paramsXML = new XMLElement('params');
	        foreach ($params as $key => $value) {
	        	$paramsXML->appendChild(new XMLElement($key,$value));
	        }
			$xml->appendChild($paramsXML);
			
			$dom = new DOMDocument();
			$dom->loadXML($xml->generate(true));
			
			return $dom;
		}
		
	/*-------------------------------------------------------------------------
		Fields:
	-------------------------------------------------------------------------*/
		
		public function registerField($field) {
			self::$fields[] = $field;
		}
		
		public function compileBackendFields($context) {
			foreach (self::$fields as $field) {
				$field->compile($context['entry']);
			}
		}
		
		public function compileFrontendFields($context) {
			foreach (self::$fields as $field) {
				$field->compile($context['entry']);
			}
		}
		
		public function compileImportFields($context) {
			foreach (self::$fields as $field) {
				$field->compile($context['entry']);
			}
		}

	
		/**
		 * Some admin customisations
		 */
		public function initializeAdmin($context) {
			$LOAD_NUMBER = 93593559;

			$page = Administration::instance()->Page;
			$assets_path = URL . '/extensions/entry_url_field/assets';
					
			// Only load on /publish/.../new/ OR /publish/.../edit/
			if (in_array($page->_context['page'], array('new', 'edit'))) {
				$page->addScriptToHead($assets_path . '/js/publish.js', $LOAD_NUMBER++);
				$page->addStylesheetToHead('https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css');
			}
			
		}
	}
