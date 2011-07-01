<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Thomas Maroschik <tmaroschik@dfau.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 *
 * This Class groups together useful methods stolen from the Installer
 */
class Tx_Identity_Install_Installer implements t3lib_Singleton {

	/**
	 * Caching ou tput of $GLOBALS['TYPO3_DB']->admin_get_charsets()
	 * 
	 * @var array
	 */
	protected $character_sets = array(); 

	/**
	 * Prefix used for tables/fields when deleted/renamed.
	 * 
	 * @var string
	 */	
	protected $deletedPrefixKey = 'zzz_deleted_';
	
	
	/**
	 * Returns an array where every entry is a single SQL-statement. Input must be formatted like an ordinary MySQL-dump files.
	 *
	 * @param	string		The SQL-file content. Provided that 1) every query in the input is ended with ';' and that a line in the file contains only one query or a part of a query.
	 * @param	boolean		If set, non-SQL content (like comments and blank lines) is not included in the final output
	 * @param	string		Regex to filter SQL lines to include
	 * @return	array		Array of SQL statements
	 */
	function getStatementArray($sqlcode, $removeNonSQL = 0, $query_regex = '') {
		$sqlcodeArr = explode(LF, $sqlcode);

			// Based on the assumption that the sql-dump has
		$statementArray = array();
		$statementArrayPointer = 0;

		foreach ($sqlcodeArr as $line => $lineContent) {
			$is_set = 0;

				// auto_increment fields cannot have a default value!
			if (stristr($lineContent, 'auto_increment')) {
				$lineContent = preg_replace('/ default \'0\'/i', '', $lineContent);
			}

			if (!$removeNonSQL || (strcmp(trim($lineContent), '') && substr(trim($lineContent), 0, 1) != '#' && substr(trim($lineContent), 0, 2) != '--')) { // '--' is seen as mysqldump comments from server version 3.23.49
				$statementArray[$statementArrayPointer] .= $lineContent;
				$is_set = 1;
			}
			if (substr(trim($lineContent), -1) == ';') {
				if (isset($statementArray[$statementArrayPointer])) {
					if (!trim($statementArray[$statementArrayPointer]) || ($query_regex && !preg_match('/' . $query_regex . '/i', trim($statementArray[$statementArrayPointer])))) {
						unset($statementArray[$statementArrayPointer]);
					}
				}
				$statementArrayPointer++;

			} elseif ($is_set) {
				$statementArray[$statementArrayPointer] .= LF;
			}
		}

		return $statementArray;
	}
	
	
	/*************************************
	 *
	 * SQL
	 *
	 *************************************/

	/**
	 * Reads the field definitions for the input SQL-file string
	 *
	 * @param	string		Should be a string read from an SQL-file made with 'mysqldump [database_name] -d'
	 * @return	array		Array with information about table.
	 */
	function getFieldDefinitions_fileContent($fileContent) {
		$lines = t3lib_div::trimExplode(LF, $fileContent, 1);
		$table = '';
		$total = array();

		foreach ($lines as $value) {
			if (substr($value, 0, 1) == '#') {
				continue; // Ignore comments
			}

			if (!strlen($table)) {
				$parts = t3lib_div::trimExplode(' ', $value, TRUE);
				if (strtoupper($parts[0]) === 'CREATE' && strtoupper($parts[1]) === 'TABLE') {
					$table = str_replace('`', '', $parts[2]);
					if (TYPO3_OS == 'WIN') { // tablenames are always lowercase on windows!
						$table = strtolower($table);
					}
				}
			} else {
				if (substr($value, 0, 1) == ')' && substr($value, -1) == ';') {
					$ttype = array();
					if (preg_match('/(ENGINE|TYPE)[ ]*=[ ]*([a-zA-Z]*)/', $value, $ttype)) {
						$total[$table]['extra']['ENGINE'] = $ttype[2];
					} // Otherwise, just do nothing: If table engine is not defined, just accept the system default.

						// Set the collation, if specified
					if (preg_match('/(COLLATE)[ ]*=[ ]*([a-zA-z0-9_-]+)/', $value, $tcollation)) {
						$total[$table]['extra']['COLLATE'] = $tcollation[2];
					} else {
							// Otherwise, get the CHARACTER SET and try to find the default collation for it as returned by "SHOW CHARACTER SET" query (for details, see http://dev.mysql.com/doc/refman/5.1/en/charset-table.html)
						if (preg_match('/(CHARSET|CHARACTER SET)[ ]*=[ ]*([a-zA-z0-9_-]+)/', $value, $tcharset)) { // Note: Keywords "DEFAULT CHARSET" and "CHARSET" are the same, so "DEFAULT" can just be ignored
							$charset = $tcharset[2];
						} else {
							$charset = $GLOBALS['TYPO3_DB']->default_charset; // Fallback to default charset
						}
						$total[$table]['extra']['COLLATE'] = $this->getCollationForCharset($charset);
					}
					$table = ''; // Remove table marker and start looking for the next "CREATE TABLE" statement
				} else {
					$lineV = preg_replace('/,$/', '', $value); // Strip trailing commas
					$lineV = str_replace('`', '', $lineV);
					$lineV = str_replace('  ', ' ', $lineV); // Remove double blanks

					$parts = explode(' ', $lineV, 2);
					if (!preg_match('/(PRIMARY|UNIQUE|FULLTEXT|INDEX|KEY)/', $parts[0])) { // Field definition

							// Make sure there is no default value when auto_increment is set
						if (stristr($parts[1], 'auto_increment')) {
							$parts[1] = preg_replace('/ default \'0\'/i', '', $parts[1]);
						}
							// "default" is always lower-case
						if (stristr($parts[1], ' DEFAULT ')) {
							$parts[1] = str_ireplace(' DEFAULT ', ' default ', $parts[1]);
						}

							// Change order of "default" and "null" statements
						$parts[1] = preg_replace('/(.*) (default .*) (NOT NULL)/', '$1 $3 $2', $parts[1]);
						$parts[1] = preg_replace('/(.*) (default .*) (NULL)/', '$1 $3 $2', $parts[1]);

						$key = $parts[0];
						$total[$table]['fields'][$key] = $parts[1];

					} else { // Key definition
						$search = array('/UNIQUE (INDEX|KEY)/', '/FULLTEXT (INDEX|KEY)/', '/INDEX/');
						$replace = array('UNIQUE', 'FULLTEXT', 'KEY');
						$lineV = preg_replace($search, $replace, $lineV);

						if (preg_match('/PRIMARY|UNIQUE|FULLTEXT/', $parts[0])) {
							$parts[1] = preg_replace('/^(KEY|INDEX) /', '', $parts[1]);
						}

						$newParts = explode(' ', $parts[1], 2);
						$key = $parts[0] == 'PRIMARY' ? $parts[0] : $newParts[0];

						$total[$table]['keys'][$key] = $lineV;

							// This is a protection against doing something stupid: Only allow clearing of cache_* and index_* tables.
						if (preg_match('/^(cache|index)_/', $table)) {
								// Suggest to truncate (clear) this table
							$total[$table]['extra']['CLEAR'] = 1;
						}
					}
				}
			}
		}
		$this->getFieldDefinitions_sqlContent_parseTypes($total);
		return $total;
	}

	/**
	 * Look up the default collation for specified character set based on "SHOW CHARACTER SET" output
	 *
	 * @param	string		Character set
	 * @return	string		Corresponding default collation
	 */
	function getCollationForCharset($charset) {
			// Load character sets, if not cached already
		if (!count($this->character_sets)) {
			if (method_exists($GLOBALS['TYPO3_DB'], 'admin_get_charsets')) {
				$this->character_sets = $GLOBALS['TYPO3_DB']->admin_get_charsets();
			} else {
				$this->character_sets[$charset] = array(); // Add empty element to avoid that the check will be repeated
			}
		}

		$collation = '';
		if (isset($this->character_sets[$charset]['Default collation'])) {
			$collation = $this->character_sets[$charset]['Default collation'];
		}

		return $collation;
	}
	
	
	/**
	 * Multiplies varchars/tinytext fields in size according to $this->multiplySize
	 * Useful if you want to use UTF-8 in the database and needs to extend the field sizes in the database so UTF-8 chars are not discarded. For most charsets available as single byte sets, multiplication with 2 should be enough. For chinese, use 3.
	 *
	 * @param	array		Total array (from getFieldDefinitions_fileContent())
	 * @return	void
	 * @access private
	 * @see getFieldDefinitions_fileContent()
	 */
	function getFieldDefinitions_sqlContent_parseTypes(&$total) {

		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['multiplyDBfieldSize'] >= 1 && $GLOBALS['TYPO3_CONF_VARS']['SYS']['multiplyDBfieldSize'] <= 5) {
			$this->multiplySize = (double) $GLOBALS['TYPO3_CONF_VARS']['SYS']['multiplyDBfieldSize'];
		}
		
		$mSize = (double) $this->multiplySize;
		if ($mSize > 1) {

				// Init SQL parser:
			$sqlParser = t3lib_div::makeInstance('t3lib_sqlparser');
			foreach ($total as $table => $cfg) {
				if (is_array($cfg['fields'])) {
					foreach ($cfg['fields'] as $fN => $fType) {
						$orig_fType = $fType;
						$fInfo = $sqlParser->parseFieldDef($fType);

						switch ($fInfo['fieldType']) {
							case 'char':
							case 'varchar':
								$newSize = round($fInfo['value'] * $mSize);

								if ($newSize <= 255) {
									$fInfo['value'] = $newSize;
								} else {
									$fInfo = array(
										'fieldType' => 'text',
										'featureIndex' => array(
											'NOTNULL' => array(
												'keyword' => 'NOT NULL'
											)
										)
									);
										// Change key definition if necessary (must use "prefix" on TEXT columns)
									if (is_array($cfg['keys'])) {
										foreach ($cfg['keys'] as $kN => $kType) {
											$match = array();
											preg_match('/^([^(]*)\(([^)]+)\)(.*)/', $kType, $match);
											$keys = array();
											foreach (t3lib_div::trimExplode(',', $match[2]) as $kfN) {
												if ($fN == $kfN) {
													$kfN .= '(' . $newSize . ')';
												}
												$keys[] = $kfN;
											}
											$total[$table]['keys'][$kN] = $match[1] . '(' . implode(',', $keys) . ')' . $match[3];
										}
									}
								}
							break;
							case 'tinytext':
								$fInfo['fieldType'] = 'text';
							break;
						}

						$total[$table]['fields'][$fN] = $sqlParser->compileFieldCfg($fInfo);
						if ($sqlParser->parse_error) {
							throw new RuntimeException(
								'TYPO3 Fatal Error: ' . $sqlParser->parse_error,
								1270853961
							);
						}
					}
				}
			}
		}
	}
	
	/**
	 * Reads the field definitions for the current database
	 *
	 * @return	array		Array with information about table.
	 */
	function getFieldDefinitions_database() {
		$total = array();
		$tempKeys = array();
		$tempKeysPrefix = array();

		$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db);
		echo $GLOBALS['TYPO3_DB']->sql_error();

		$tables = $GLOBALS['TYPO3_DB']->admin_get_tables(TYPO3_db);
		foreach ($tables as $tableName => $tableStatus) {

				// Fields:
			$fieldInformation = $GLOBALS['TYPO3_DB']->admin_get_fields($tableName);
			foreach ($fieldInformation as $fN => $fieldRow) {
				$total[$tableName]['fields'][$fN] = $this->assembleFieldDefinition($fieldRow);
			}

				// Keys:
			$keyInformation = $GLOBALS['TYPO3_DB']->admin_get_keys($tableName);

			foreach ($keyInformation as $keyRow) {
				$keyName = $keyRow['Key_name'];
				$colName = $keyRow['Column_name'];
				if ($keyRow['Sub_part']) {
					$colName .= '(' . $keyRow['Sub_part'] . ')';
				}
				$tempKeys[$tableName][$keyName][$keyRow['Seq_in_index']] = $colName;
				if ($keyName == 'PRIMARY') {
					$prefix = 'PRIMARY KEY';
				} else {
					if ($keyRow['Index_type'] == 'FULLTEXT') {
						$prefix = 'FULLTEXT';
					} elseif ($keyRow['Non_unique']) {
						$prefix = 'KEY';
					} else {
						$prefix = 'UNIQUE';
					}
					$prefix .= ' ' . $keyName;
				}
				$tempKeysPrefix[$tableName][$keyName] = $prefix;
			}

				// Table status (storage engine, collaction, etc.)
			if (is_array($tableStatus)) {
				$tableExtraFields = array(
					'Engine' => 'ENGINE',
					'Collation' => 'COLLATE',
				);

				foreach ($tableExtraFields as $mysqlKey => $internalKey) {
					if (isset($tableStatus[$mysqlKey])) {
						$total[$tableName]['extra'][$internalKey] = $tableStatus[$mysqlKey];
					}
				}
			}
		}

			// Compile key information:
		if (count($tempKeys)) {
			foreach ($tempKeys as $table => $keyInf) {
				foreach ($keyInf as $kName => $index) {
					ksort($index);
					$total[$table]['keys'][$kName] = $tempKeysPrefix[$table][$kName] . ' (' . implode(',', $index) . ')';
				}
			}
		}

		return $total;
	}

	/**
	 * Converts a result row with field information into the SQL field definition string
	 *
	 * @param	array		MySQL result row
	 * @return	string		Field definition
	 */
	function assembleFieldDefinition($row) {
		$field = array($row['Type']);

		if ($row['Null'] == 'NO') {
			$field[] = 'NOT NULL';
		}
		if (!strstr($row['Type'], 'blob') && !strstr($row['Type'], 'text')) {
				// Add a default value if the field is not auto-incremented (these fields never have a default definition)
			if (!stristr($row['Extra'], 'auto_increment')) {
				$field[] = 'default \'' . addslashes($row['Default']) . '\'';
			}
		}
		if ($row['Extra']) {
			$field[] = $row['Extra'];
		}

		return implode(' ', $field);
	}
	
	
	/**
	 * Compares two arrays with field information and returns information about fields that are MISSING and fields that have CHANGED.
	 * FDsrc and FDcomp can be switched if you want the list of stuff to remove rather than update.
	 *
	 * @param	array		Field definitions, source (from getFieldDefinitions_fileContent())
	 * @param	array		Field definitions, comparison. (from getFieldDefinitions_database())
	 * @param	string		Table names (in list) which is the ONLY one observed.
	 * @param	boolean		If set, this function ignores NOT NULL statements of the SQL file field definition when comparing current field definition from database with field definition from SQL file. This way, NOT NULL statements will be executed when the field is initially created, but the SQL parser will never complain about missing NOT NULL statements afterwards.
	 * @return	array		Returns an array with 1) all elements from $FDsrc that is not in $FDcomp (in key 'extra') and 2) all elements from $FDsrc that is different from the ones in $FDcomp
	 */
	function getDatabaseExtra($FDsrc, $FDcomp, $onlyTableList = '', $ignoreNotNullWhenComparing = TRUE) {
		$extraArr = array();
		$diffArr = array();

		if (is_array($FDsrc)) {
			foreach ($FDsrc as $table => $info) {
				if (!strlen($onlyTableList) || t3lib_div::inList($onlyTableList, $table)) {
					if (!isset($FDcomp[$table])) {
						$extraArr[$table] = $info; // If the table was not in the FDcomp-array, the result array is loaded with that table.
						$extraArr[$table]['whole_table'] = 1;
					} else {
						$keyTypes = explode(',', 'extra,fields,keys');
						foreach ($keyTypes as $theKey) {
							if (is_array($info[$theKey])) {
								foreach ($info[$theKey] as $fieldN => $fieldC) {
									$fieldN = str_replace('`', '', $fieldN);
									if ($fieldN == 'COLLATE') {
										continue; // TODO: collation support is currently disabled (needs more testing)
									}

									if (!isset($FDcomp[$table][$theKey][$fieldN])) {
										$extraArr[$table][$theKey][$fieldN] = $fieldC;
									} else {
										$fieldC = trim($fieldC);
										if ($ignoreNotNullWhenComparing) {
											$fieldC = str_replace(' NOT NULL', '', $fieldC);
											$FDcomp[$table][$theKey][$fieldN] = str_replace(' NOT NULL', '', $FDcomp[$table][$theKey][$fieldN]);
										}
										if ($fieldC !== $FDcomp[$table][$theKey][$fieldN]) {
											$diffArr[$table][$theKey][$fieldN] = $fieldC;
											$diffArr_cur[$table][$theKey][$fieldN] = $FDcomp[$table][$theKey][$fieldN];
										}
									}
								}
							}
						}
					}
				}
			}
		}

		$output = array(
			'extra' => $extraArr,
			'diff' => $diffArr,
			'diff_currentValues' => $diffArr_cur
		);

		return $output;
	}
	
	
	/**
	 * Returns an array with SQL-statements that is needed to update according to the diff-array
	 *
	 * @param	array		Array with differences of current and needed DB settings. (from getDatabaseExtra())
	 * @param	string		List of fields in diff array to take notice of.
	 * @return	array		Array of SQL statements (organized in keys depending on type)
	 */
	function getUpdateSuggestions($diffArr, $keyList = 'extra,diff') {
		$statements = array();
		$deletedPrefixKey = $this->deletedPrefixKey;
		$remove = 0;
		if ($keyList == 'remove') {
			$remove = 1;
			$keyList = 'extra';
		}
		$keyList = explode(',', $keyList);
		foreach ($keyList as $theKey) {
			if (is_array($diffArr[$theKey])) {
				foreach ($diffArr[$theKey] as $table => $info) {
					$whole_table = array();
					if (is_array($info['fields'])) {
						foreach ($info['fields'] as $fN => $fV) {
							if ($info['whole_table']) {
								$whole_table[] = $fN . ' ' . $fV;
							} else {
									// Special case to work around MySQL problems when adding auto_increment fields:
								if (stristr($fV, 'auto_increment')) {
										// The field can only be set "auto_increment" if there exists a PRIMARY key of that field already.
										// The check does not look up which field is primary but just assumes it must be the field with the auto_increment value...
									if (isset($diffArr['extra'][$table]['keys']['PRIMARY'])) {
											// Remove "auto_increment" from the statement - it will be suggested in a 2nd step after the primary key was created
										$fV = str_replace(' auto_increment', '', $fV);
									} else {
											// In the next step, attempt to clear the table once again (2 = force)
										$info['extra']['CLEAR'] = 2;
									}
								}
								if ($theKey == 'extra') {
									if ($remove) {
										if (substr($fN, 0, strlen($deletedPrefixKey)) != $deletedPrefixKey) {
											$statement = 'ALTER TABLE ' . $table . ' CHANGE ' . $fN . ' ' . $deletedPrefixKey . $fN . ' ' . $fV . ';';
											$statements['change'][md5($statement)] = $statement;
										} else {
											$statement = 'ALTER TABLE ' . $table . ' DROP ' . $fN . ';';
											$statements['drop'][md5($statement)] = $statement;
										}
									} else {
										$statement = 'ALTER TABLE ' . $table . ' ADD ' . $fN . ' ' . $fV . ';';
										$statements['add'][md5($statement)] = $statement;
									}
								} elseif ($theKey == 'diff') {
									$statement = 'ALTER TABLE ' . $table . ' CHANGE ' . $fN . ' ' . $fN . ' ' . $fV . ';';
									$statements['change'][md5($statement)] = $statement;
									$statements['change_currentValue'][md5($statement)] = $diffArr['diff_currentValues'][$table]['fields'][$fN];
								}
							}
						}
					}
					if (is_array($info['keys'])) {
						foreach ($info['keys'] as $fN => $fV) {
							if ($info['whole_table']) {
								$whole_table[] = $fV;
							} else {
								if ($theKey == 'extra') {
									if ($remove) {
										$statement = 'ALTER TABLE ' . $table . ($fN == 'PRIMARY' ? ' DROP PRIMARY KEY' : ' DROP KEY ' . $fN) . ';';
										$statements['drop'][md5($statement)] = $statement;
									} else {
										$statement = 'ALTER TABLE ' . $table . ' ADD ' . $fV . ';';
										$statements['add'][md5($statement)] = $statement;
									}
								} elseif ($theKey == 'diff') {
									$statement = 'ALTER TABLE ' . $table . ($fN == 'PRIMARY' ? ' DROP PRIMARY KEY' : ' DROP KEY ' . $fN) . ';';
									$statements['change'][md5($statement)] = $statement;
									$statement = 'ALTER TABLE ' . $table . ' ADD ' . $fV . ';';
									$statements['change'][md5($statement)] = $statement;
								}
							}
						}
					}
					if (is_array($info['extra'])) {
						$extras = array();
						$extras_currentValue = array();
						$clear_table = FALSE;

						foreach ($info['extra'] as $fN => $fV) {

								// Only consider statements which are missing in the database but don't remove existing properties
							if (!$remove) {
								if (!$info['whole_table']) { // If the whole table is created at once, we take care of this later by imploding all elements of $info['extra']
									if ($fN == 'CLEAR') {
											// Truncate table must happen later, not now
											// Valid values for CLEAR: 1=only clear if keys are missing, 2=clear anyway (force)
										if (count($info['keys']) || $fV == 2) {
											$clear_table = TRUE;
										}
										continue;
									} else {
										$extras[] = $fN . '=' . $fV;
										$extras_currentValue[] = $fN . '=' . $diffArr['diff_currentValues'][$table]['extra'][$fN];
									}
								}
							}
						}
						if ($clear_table) {
							$statement = 'TRUNCATE TABLE ' . $table . ';';
							$statements['clear_table'][md5($statement)] = $statement;
						}
						if (count($extras)) {
							$statement = 'ALTER TABLE ' . $table . ' ' . implode(' ', $extras) . ';';
							$statements['change'][md5($statement)] = $statement;
							$statements['change_currentValue'][md5($statement)] = implode(' ', $extras_currentValue);
						}
					}
					if ($info['whole_table']) {
						if ($remove) {
							if (substr($table, 0, strlen($deletedPrefixKey)) != $deletedPrefixKey) {
								$statement = 'ALTER TABLE ' . $table . ' RENAME ' . $deletedPrefixKey . $table . ';';
								$statements['change_table'][md5($statement)] = $statement;
							} else {
								$statement = 'DROP TABLE ' . $table . ';';
								$statements['drop_table'][md5($statement)] = $statement;
							}
								// count:
							$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', $table);
							$statements['tables_count'][md5($statement)] = $count ? 'Records in table: ' . $count : '';
						} else {
							$statement = 'CREATE TABLE ' . $table . " (\n" . implode(",\n", $whole_table) . "\n)";
							if ($info['extra']) {
								foreach ($info['extra'] as $k => $v) {
									if ($k == 'COLLATE' || $k == 'CLEAR') {
										continue; // Skip these special statements. TODO: collation support is currently disabled (needs more testing)
									}
									$statement .= ' ' . $k . '=' . $v; // Add extra attributes like ENGINE, CHARSET, etc.
								}
							}
							$statement .= ';';
							$statements['create_table'][md5($statement)] = $statement;
						}
					}
				}
			}
		}
		return $statements;
	}
	
	/**
	 * Remove statements that contains not a uuid statement
	 *
	 * @param array $statements
	 * @return array
	 */
	public function filterByIdentityField($statements) {
		
		$identityConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['identity'];
		$identityProviders = $identityConfiguration[Tx_Identity_Configuration_IdentityProviderInterface::PROVIDERS_LIST];
		$identityField = $identityProviders['recordUuid'][Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD];
		
		$result = array();
		foreach ($statements as $key => $statement) {
			if (strpos($statement, 'ADD ' . $identityField . ' ') !== FALSE ||
					strpos($statement, 'ADD KEY ' . $identityField . ' ') !== FALSE ) {
				$result[$key] = $statement;
			}
		}
		
		return $result;
	}

	
	protected $templateFilePath = 'typo3/sysext/install/Resources/Private/Templates/';
	protected $dbUpdateCheckboxPrefix = 'TYPO3_INSTALL[database_update]'; // Prefix for checkbox fields when updating database.
	protected $backPath = '../'; // Backpath (used for icons etc.)
	
	/**
	 * Creates a table which checkboxes for updating database.
	 *
	 * @param array $arr Array of statements (key / value pairs where key is used for the checkboxes)
	 * @param string $label Label for the table.
	 * @param boolean $checked If set, then checkboxes are set by default.
	 * @param boolean $iconDis If set, then icons are shown.
	 * @param array $currentValue Array of "current values" for each key/value pair in $arr. Shown if given.
	 * @param boolean $cVfullMsg If set, will show the prefix "Current value" if $currentValue is given.
	 * @return string HTML table with checkboxes for update. Must be wrapped in a form.
	 */
	function generateUpdateDatabaseForm_checkboxes($arr,$label,$checked=1,$iconDis=0,$currentValue=array(),$cVfullMsg=0) {
		$out = array();
		$tableId = uniqid('table');
		$templateMarkers = array();
		if (is_array($arr)) {
				// Get the template file
			$templateFile = @file_get_contents(PATH_site . $this->templateFilePath . 'GenerateUpdateDatabaseFormCheckboxes.html');
				// Get the template part from the file
			$content = t3lib_parsehtml::getSubpart($templateFile, '###TEMPLATE###');
				// Define the markers content
			$templateMarkers = array(
				'label' => $label,
				'tableId' => $tableId
			);
				// Select/Deselect All
			if (count($arr) > 1) {
					// Get the subpart for multiple tables
				$multipleTablesSubpart = t3lib_parsehtml::getSubpart($content, '###MULTIPLETABLES###');
					// Define the markers content
				$multipleTablesMarkers = array(
					'label' => $label,
					'tableId' => $tableId,
					'checked' => ($checked ? ' checked="checked"' : ''),
					'selectAllId' => 't3-install-' . $tableId . '-checkbox',
					'selectDeselectAll' => 'select/deselect all'
				);
					// Fill the markers in the subpart
				$multipleTablesSubpart = t3lib_parsehtml::substituteMarkerArray(
					$multipleTablesSubpart,
					$multipleTablesMarkers,
					'###|###',
					TRUE,
					FALSE
				);
			}
				// Substitute the subpart for multiple tables
			$content = t3lib_parsehtml::substituteSubpart(
				$content,
				'###MULTIPLETABLES###',
				$multipleTablesSubpart
			);
				// Rows
			foreach ($arr as $key => $string) {
					// Get the subpart for rows
				$rowsSubpart = t3lib_parsehtml::getSubpart($content, '###ROWS###');
				$currentSubpart = '';
				$ico = '';
				$warnings = array();
					// Define the markers content
				$rowsMarkers = array(
					'checkboxId' => 't3-install-db-' . $key,
					'name' => $this->dbUpdateCheckboxPrefix . '[' . $key . ']',
					'checked' => ($checked ? 'checked="checked"' : ''),
					'string' => htmlspecialchars($string)
				);

				if ($iconDis) {
					$iconMarkers['backPath'] = $this->backPath;
					if (preg_match('/^TRUNCATE/i', $string)) {
						$iconMarkers['iconText'] = '';
						$warnings['clear_table_info'] = 'Clearing the table is sometimes neccessary when adding new keys. In case of cache_* tables this should not hurt at all. However, use it with care.';
					} elseif (stristr($string,' user_')) {
						$iconMarkers['iconText'] = '(USER)';
					} elseif (stristr($string,' app_')) {
						$iconMarkers['iconText'] = '(APP)';
					} elseif (stristr($string,' ttx_') || stristr($string,' tx_')) {
						$iconMarkers['iconText'] = '(EXT)';
					}

					if (!empty($iconMarkers)) {
							// Get the subpart for icons
						$iconSubpart = t3lib_parsehtml::getSubpart($content, '###ICONAVAILABLE###');
							// Fill the markers in the subpart
						$iconSubpart = t3lib_parsehtml::substituteMarkerArray(
							$iconSubpart,
							$iconMarkers,
							'###|###',
							TRUE,
							TRUE
						);
					}
				}
					// Substitute the subpart for icons
				$rowsSubpart = t3lib_parsehtml::substituteSubpart(
					$rowsSubpart,
					'###ICONAVAILABLE###',
					$iconSubpart
				);

				if (isset($currentValue[$key])) {
						// Get the subpart for current
					$currentSubpart = t3lib_parsehtml::getSubpart($rowsSubpart, '###CURRENT###');
						// Define the markers content
					$currentMarkers = array (
						'message' => (!$cVfullMsg ? 'Current value:': ''),
						'value' => $currentValue[$key]
					);
						// Fill the markers in the subpart
					$currentSubpart = t3lib_parsehtml::substituteMarkerArray(
						$currentSubpart,
						$currentMarkers,
						'###|###',
						TRUE,
						FALSE
					);
				}
					// Substitute the subpart for current
				$rowsSubpart = t3lib_parsehtml::substituteSubpart(
					$rowsSubpart,
					'###CURRENT###',
					$currentSubpart
				);

				$errorSubpart = '';
				if (isset($this->databaseUpdateErrorMessages[$key])) {
						// Get the subpart for current
					$errorSubpart = t3lib_parsehtml::getSubpart($rowsSubpart, '###ERROR###');
						// Define the markers content
					$currentMarkers = array (
						'errorMessage' => $this->databaseUpdateErrorMessages[$key],
					);
						// Fill the markers in the subpart
					$errorSubpart = t3lib_parsehtml::substituteMarkerArray(
						$errorSubpart,
						$currentMarkers,
						'###|###',
						TRUE,
						FALSE
					);
				}
					// Substitute the subpart for error messages
				$rowsSubpart = t3lib_parsehtml::substituteSubpart(
					$rowsSubpart,
					'###ERROR###',
					$errorSubpart
				);

					// Fill the markers in the subpart
				$rowsSubpart = t3lib_parsehtml::substituteMarkerArray(
					$rowsSubpart,
					$rowsMarkers,
					'###|###',
					TRUE,
					FALSE
				);

				$rows[] = $rowsSubpart;
			}
				// Substitute the subpart for rows
			$content = t3lib_parsehtml::substituteSubpart(
				$content,
				'###ROWS###',
				implode(LF, $rows)
			);

			if (count($warnings)) {
					// Get the subpart for warnings
				$warningsSubpart = t3lib_parsehtml::getSubpart($content, '###WARNINGS###');
				$warningItems = array();

				foreach ($warnings as $warning) {
						// Get the subpart for single warning items
					$warningItemSubpart = t3lib_parsehtml::getSubpart($warningsSubpart, '###WARNINGITEM###');
						// Define the markers content
					$warningItemMarker['warning'] = $warning;
						// Fill the markers in the subpart
					$warningItems[] = t3lib_parsehtml::substituteMarkerArray(
						$warningItemSubpart,
						$warningItemMarker,
						'###|###',
						TRUE,
						FALSE
					);
				}
					// Substitute the subpart for single warning items
				$warningsSubpart = t3lib_parsehtml::substituteSubpart(
					$warningsSubpart,
					'###WARNINGITEM###',
					implode(LF, $warningItems)
				);
			}
				// Substitute the subpart for warnings
			$content = t3lib_parsehtml::substituteSubpart(
				$content,
				'###WARNINGS###',
				$warningsSubpart
			);
		}
			// Fill the markers
		$content = t3lib_parsehtml::substituteMarkerArray(
			$content,
			$templateMarkers,
			'###|###',
			TRUE,
			FALSE
		);

		return $content;
	}
	
	/**
	 * Performs the queries passed from the input array.
	 *
	 * @param	array		Array of SQL queries to execute.
	 * @param	array		Array with keys that must match keys in $arr. Only where a key in this array is set and TRUE will the query be executed (meant to be passed from a form checkbox)
	 * @return	mixed		Array with error message from database if any occured. Otherwise TRUE if everything was executed successfully.
	 */
	function performUpdateQueries($arr, $keyArr) {
		$result = array();
		if (is_array($arr)) {
			foreach ($arr as $key => $string) {
				if (isset($keyArr[$key]) && $keyArr[$key]) {
					$res = $GLOBALS['TYPO3_DB']->admin_query($string);
					if ($res === FALSE) {
						$result[$key] = $GLOBALS['TYPO3_DB']->sql_error();
					} elseif (is_resource($res)) {
						$GLOBALS['TYPO3_DB']->sql_free_result($res);
					}
				}
			}
		}
		if (count($result) > 0) {
			return $result;
		} else {
			return TRUE;
		}
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['EXT:uuid/Class/Registry.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['EXT:uuid/Class/Registry.php']);
}

?>