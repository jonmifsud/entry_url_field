<?php
	
	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	class FieldEntry_URL extends Field {
		protected static $ready = true;
		
		public function __construct() {
			parent::__construct();
			
			$this->_name = 'Entry URL';
			$this->_driver = ExtensionManager::create('entry_url_field');
			
			// Set defaults:
			$this->set('show_column', 'no');
			$this->set('new_window', 'no');
			$this->set('hide', 'no');
		}
		
		public function createTable() {
			$field_id = $this->get('id');
			
			return Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_entries_data_{$field_id}` (
					`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`entry_id` INT(11) UNSIGNED NOT NULL,
					`label` TEXT DEFAULT NULL,
					`value` TEXT DEFAULT NULL,
					`handle` VARCHAR(255) DEFAULT NULL,
					PRIMARY KEY (`id`),
					KEY `entry_id` (`entry_id`)
				)
			");
		}

    /*-------------------------------------------------------------------------
        Definition:
    -------------------------------------------------------------------------*/

	    public function canFilter()
	    {
	        if ( $this->get('handle_source') != "" ){
	        	return true;
	        } else return false;
	    }

    /*-------------------------------------------------------------------------
        Filtering:
    -------------------------------------------------------------------------*/

	    public function fetchFilterableOperators() {
	        return array(
	            array(
	                'title' => 'handle is',
	                'filter' => ' ',
	                'help' => __('Find values that are an exact match for the given string.')
	            ),
	            array(
	                'title' => 'handle contains',
	                'filter' => 'regexp: ',
	                'help' => __('Find values that match the given <a href="%s">MySQL regular expressions</a>.', array(
	                    'http://dev.mysql.com/doc/mysql/en/Regexp.html'
	                ))
	            ),
	            array(
	                'title' => 'handle does not contain',
	                'filter' => 'not-regexp: ',
	                'help' => __('Find values that do not match the given <a href="%s">MySQL regular expressions</a>.', array(
	                    'http://dev.mysql.com/doc/mysql/en/Regexp.html'
	                ))
	            ),
	        );
	    }

	    public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false) {
	        $field_id = $this->get('id');

	        if (self::isFilterRegex($data[0])) {
	            $this->buildRegexSQL($data[0], array('value', 'handle'), $joins, $where);
	        } else if ($andOperation) {
	            foreach ($data as $value) {
	                $this->_key++;
	                $value = $this->cleanValue($value);
	                $joins .= "
	                    LEFT JOIN
	                        `tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
	                        ON (e.id = t{$field_id}_{$this->_key}.entry_id)
	                ";
	                $where .= "
	                    AND (
	                        t{$field_id}_{$this->_key}.handle = '{$value}'
	                    )
	                ";
	            }
	        } else {
	            if (!is_array($data)) {
	                $data = array($data);
	            }

	            foreach ($data as &$value) {
	                $value = $this->cleanValue($value);
	            }

	            $this->_key++;
	            $data = implode("', '", $data);
	            $joins .= "
	                LEFT JOIN
	                    `tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
	                    ON (e.id = t{$field_id}_{$this->_key}.entry_id)
	            ";
	            $where .= "
	                AND (
	                    t{$field_id}_{$this->_key}.handle IN ('{$data}')
	                )
	            ";
	        }

	        return true;
	    }
		
	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/
		
		public function displaySettingsPanel(&$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);
			
			$order = $this->get('sortorder');
			
			$label = Widget::Label(__('Anchor Label'));
			$label->appendChild(Widget::Input(
				"fields[{$order}][anchor_label]",
				$this->get('anchor_label')
			));
			$wrapper->appendChild($label);
			
			$label = Widget::Label(__('Anchor URL (XPath expression)'));
			$label->appendChild(Widget::Input(
				"fields[{$order}][expression]",
				$this->get('expression')
			));			
			$help = new XMLElement('p', __('To access the other fields, use XPath: <code>{entry/field-one} static text {entry/field-two}</code>. You can also link to an XSLT file within your workspace folder.'));
			$help->setAttribute('class', 'help');
			$label->appendChild($help);
			$wrapper->appendChild($label);
			
			$label = Widget::Label(__('Handle Source (optional field handle)'));
			$label->appendChild(Widget::Input(
				"fields[{$order}][handle_source]",
				$this->get('handle_source')
			));			
			$wrapper->appendChild($label);

			$label = Widget::Label(__('Handle Length (max 255)'));
			$label->appendChild(Widget::Input(
				"fields[{$order}][handle_length]",
				$this->get('handle_length')
			));			
			$wrapper->appendChild($label);

			$selectedDatasources = explode(',',$this->get('datasources'));
			$datasources = DatasourceManager::listAll();
			$options = array();
			foreach ($datasources as $handle => $datasource) {
				$selected = in_array($handle,$selectedDatasources);
				$options[] = array($handle, $selected, $datasource['name']);
			}
			$label = Widget::Label(__('Datasources to include for processing'));
			$label->appendChild(Widget::Select(
				"fields[{$order}][datasources]",
				$options,
				array('multiple'=>'multiple')
			));			
			$help = new XMLElement('p', __('Add datasources to allow you to build links which depend on other sections.'));
			$help->setAttribute('class', 'help');
			$label->appendChild($help);
			$wrapper->appendChild($label);
			
			$label = Widget::Label();
			$input = Widget::Input("fields[{$order}][new_window]", 'yes', 'checkbox');
			if ($this->get('new_window') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' ' . __('Open links in a new window'));
			$wrapper->appendChild($label);
			
			$label = Widget::Label();
			$input = Widget::Input("fields[{$order}][sync]", 'yes', 'checkbox');
			if ($this->get('sync') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' ' . __('Sync handle when it matches source (keeps handle same as source unless manually edited)'));
			$wrapper->appendChild($label);
			
			$label = Widget::Label();
			$input = Widget::Input("fields[{$order}][hide]", 'yes', 'checkbox');
			if ($this->get('hide') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' ' . __('Hide this field on publish page'));
			$wrapper->appendChild($label);
			
			$this->appendShowColumnCheckbox($wrapper);
			
		}
		
		public function commit() {
			if (!parent::commit()) return false;
			
			$id = $this->get('id');
			$handle = $this->handle();
			
			if ($id === false) return false;
			
			$fields = array(
				'field_id'			=> $id,
				'anchor_label'		=> $this->get('anchor_label'),
				'expression'		=> $this->get('expression'),
				'datasources'		=> $this->get('datasources'),
				'handle_source'		=> $this->get('handle_source'),
				'handle_length'		=> $this->get('handle_length'),
				'new_window'		=> $this->get('new_window'),
				'hide'				=> $this->get('hide')
			);
			
			Symphony::Database()->query("DELETE FROM `tbl_fields_{$handle}` WHERE `field_id` = '{$id}' LIMIT 1");
			return Symphony::Database()->insert($fields, "tbl_fields_{$handle}");
		}
		
	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/


		private function strbefore($string, $substring) {
			$pos = strpos($string, $substring);
			if ($pos === false)
				return $string;
			else 
				return(substr($string, 0, $pos));
		}

		private function strafter($string, $substring) {
			$pos = strpos($string, $substring);
			if ($pos === false)
				return $string;
			else 
				return(substr($string, $pos+strlen($substring)));
		}
		
		public function displayPublishPanel(&$wrapper, $data = null, $flagWithError = null, $prefix = null, $postfix = null) {
			$label = Widget::Label($this->get('label'));
			$span = new XMLElement('span', null, array('class' => 'frame'));
			
			if ( $this->get('handle_source') != "" ){

				$handle = $data['handle'];

				if (!empty($handle)){

					$url = $this->formatURL((string)$data['value']);

					// get part before
					$urlContents = $this->strbefore($url, $handle);

					// show handle in editable field
					$urlContents .= "<span contentEditable='true' class='url-entry-handle' data-source='{$this->get('handle_source')}' data-length='{$this->get('handle_length')}' data-sync='{$this->get('sync')}'>{$handle}</span>";

					// show whatever is after
					$urlContents .= $this->strafter($url, $handle);

					$fullEntryUrl = new XMLElement('span', $urlContents, array('class' => 'full-entry-url'));
					$span->appendChild($fullEntryUrl);
				} else {
					$fullEntryUrl = new XMLElement('span', "<span contentEditable='true' class='url-entry-handle empty' data-source='{$this->get('handle_source')}' data-length='{$this->get('handle_length')}'></span>", array('class' => 'full-entry-url'));
					$span->appendChild($fullEntryUrl);
				}

				// var_dump($this->get('handle_source'));die;
				$handleInput = Widget::Input('fields'.$prefix.'['.$this->get('element_name').'][handle]'.$postfix, $handle, 'hidden');
				$span->appendChild($handleInput);
			}

			$anchor = Widget::Anchor(
				(string)$data['label'],
				is_null($data['value']) ? '#' : $this->formatURL((string)$data['value'])
			);
			
			if ($this->get('new_window') == 'yes') {
				$anchor->setAttribute('target', '_blank');
			}
			
			$callback = Administration::instance()->getPageCallback();
			if (is_null($callback['context']['entry_id'])) {
				$span->setValue(__('The link will be created after saving this entry'));
				$span->setAttribute('class', 'frame inactive');
			} else {
				$span->appendChild($anchor);
			}
			
			$label->appendChild($span);
			
			if ($this->get('hide') == 'no') $wrapper->appendChild($label);
		}
		
	/*-------------------------------------------------------------------------
		Input:
	-------------------------------------------------------------------------*/
		
		public function checkPostFieldData($data, &$message, $entry_id = null) {
			$this->_driver->registerField($this);
			
			return self::__OK__;
		}
		
		public function processRawFieldData($data, &$status, $simulate = false, $entry_id = null) {
			$status = self::__OK__;

			$result =  array('label' => null, 'value' => null);

			if ($data['handle']){
				$result['handle'] = General::createHandle($data['handle'],$this->get('handle_length'));
			}
			
			return $result;
		}
		
	/*-------------------------------------------------------------------------
		Import:
	-------------------------------------------------------------------------*/

		public function getImportModes(){
			//only support array data
			return array(
				'getPostdata' =>	ImportableField::ARRAY_VALUE,
				'handle' =>	ImportableField::STRING_VALUE
			);
		}

		public function prepareImportValue($data, $mode, $entry_id = null){

			if ($mode == $this->getImportModes()['handle']){
				
				return array('handle'=>$data);
				
			} else {
				$result = $data;
			}

			return $result;
		}
		
	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/
		
		public function appendFormattedElement(&$wrapper, $data, $encode = false) {

			//handle might still be required to generate the full url
			$element = new XMLElement($this->get('element_name'));
			$element->setAttribute('handle', General::sanitize($data['handle']));

			if (self::$ready){
				//only show the full value if it is ready
				$element->setAttribute('label', General::sanitize($data['label']));
				$element->setValue(General::sanitize($data['value']));
			}
			
			$wrapper->appendChild($element);
		}
		
		public function prepareTableValue($data, XMLElement $link = null) {
			if (empty($data)) return;
			
			$anchor =  Widget::Anchor($data['label'], $this->formatURL($data['value']));
			if ($this->get('new_window') == 'yes') $anchor->setAttribute('target', '_blank');
			return $anchor->generate();
		}
		
		public function formatURL($url) {
			// ignore if an absolute URL
			if(preg_match("/^http/", $url)) return $url;
			// deal with Sym in subdirectories
			return URL . $url;
		}

		public function getParameterPoolValue(array $data, $entry_id=NULL) {
			if ($this->get('handle_source') == '') {
				return $data['value'];
			}

			return $data['handle'];
		}
		
	/*-------------------------------------------------------------------------
		Compile:
	-------------------------------------------------------------------------*/

		public function setReady($ready){
			self::$ready = $ready;
		}
		
		public function compile($entry) {
			self::$ready = false;

			//Fetch any dependent datasources. These can be used to build the urls in xpath
			$datasources = explode(',',$this->get('datasources'));
			
			$dom = $this->_driver->getDom($entry,$datasources,$this->get('id'));
			
			$value = $this->getExpression($dom, 'expression');
			$label = $this->getExpression($dom, 'anchor_label');

			// Save:
			Symphony::Database()->update(
				array(
					'label' => $label,
					'value' => $value
				),
				sprintf("tbl_entries_data_%s", $this->get('id')),
				sprintf("`entry_id` = '%s'", $entry->get('id'))
			);
		}


		private function getExpression($dom, $handle){
			$expression = $this->get($handle);
			$replacements = array();

			if(substr($expression, -4) === ".xsl"){
				// If expression is an XSL file should use xsl templates 
				$xsl = new DOMDocument;
				// $xsl->load(WORKSPACE.'/sections/pages.xsl');
				$xsl->load(WORKSPACE.$expression);

				$proc = new XSLTProcessor;
				$proc->importStyleSheet($xsl);

				return trim(substr($proc->transformToXML($dom), strlen('<?xml version="1.0"?>')));
			} else {
				$xpath = new DOMXPath($dom);

				// Find queries:
				preg_match_all('/\{[^\}]+\}/', $expression, $matches);

				// Find replacements:
				foreach ($matches[0] as $match) {
					$results = @$xpath->query(trim($match, '{}'));

					if ($results->length) {
						$replacements[$match] = $results->item(0)->nodeValue;
					} else {
						$replacements[$match] = '';
					}
				}

				// Apply replacements:
				$value = str_replace(
					array_keys($replacements),
					array_values($replacements),
					$expression
				);

				return $value;

			}
		}
		
	}
