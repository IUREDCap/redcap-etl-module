<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use IU\REDCapETL\Rules\FieldRule;
use IU\REDCapETL\Rules\TableRule;
use IU\REDCapETL\Schema\Field;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\RowsType;
use IU\REDCapETL\Schema\Schema;
use IU\REDCapETL\Schema\Table;

/**
 * Transformation rules used for transforming data from
 * the extracted format to the load format used in the
 * target database.
 */
class SchemaGenerator
{
    # Suffix for the REDCap field indicated the form has been completed
    const FORM_COMPLETE_SUFFIX = '_complete';

    # Parse status
    const PARSE_VALID = 'valid';
    const PARSE_ERROR = 'error';
    const PARSE_WARN  = 'warn';


    private $rules;

    private $lookupChoices;
    private $lookupTable;
    private $lookupTableIn;

    private $dataProject;
    private $logger;
    private $configuration;
    private $tablePrefix;

    /**
     * Constructor.
     *
     * @param EtlRedCapProject $dataProject the REDCap project that
     *     contains the data to extract.
     * @param Configuration $configuration ETL configuration information.
     * @param Logger $logger logger for logging ETL process information
     *     and errors.
     */
    public function __construct($dataProject, $configuration, $logger)
    {
        $this->dataProject   = $dataProject;
        $this->configuration = $configuration;
        $this->tablePrefix   = $configuration->getTablePrefix();
        $this->logger        = $logger;
    }


    /**
     * Generates the database schema from the rules (text).
     *
     * @param string $rulesText the transformation rules in text
     *     format.
     *
     * @return array the first element of the array is the Schema
     *    object for the database, the second is and array where
     *    the first element is that parse status, and the second
     *    is a string with info, warning and error messages.
     */
    public function generateSchema($rulesText)
    {
        $projectInfo       = $this->dataProject->exportProjectInfo();
        $recordIdFieldName = $this->dataProject->getRecordIdFieldName();
        $fieldNames        = $this->dataProject->getFieldNames();


        $formInfo = $this->dataProject->exportInstruments();
        $formNames = array_keys($formInfo);

        #----------------------------------------------------------
        # If surveys have been enabled, create a map of
        # survey timestamp fields for checking for field validity
        #----------------------------------------------------------
        $timestampFields = array();
        $surveysEnabled = $projectInfo['surveys_enabled'];
        if ($surveysEnabled) {
            foreach ($formNames as $formName) {
                $timestampFields[$formName.'_timestamp'] = 1;
            }
        }

        #------------------------------------------------------------------------------
        # Set up $unmappedRedCapFields to keep track of the user-created REDCap fields
        # (i.e., not ones automatically generated by REDCap) that have not been mapped
        # by the transformation rules
        #------------------------------------------------------------------------------
        $unmappedRedCapFields  = $this->dataProject->getFieldNames();
        foreach ($unmappedRedCapFields as $fieldName => $val) {
            if (preg_match('/'.self::FORM_COMPLETE_SUFFIX.'$/', $fieldName)) {
                unset($unmappedRedCapFields[$fieldName]);
            } elseif ($fieldName === $recordIdFieldName) {
                unset($unmappedRedCapFields[$fieldName]);
            }
        }

        $this->lookupChoices = $this->dataProject->getLookupChoices();
        $keyType = $this->configuration->getGeneratedKeyType();
        $lookupTableName = $this->configuration->getLookupTableName();
        $this->lookupTable = new LookupTable($this->lookupChoices, $this->tablePrefix, $keyType, $lookupTableName);

        $info = '';
        $warnings = '';
        $errors = '';

        $schema = new Schema();

        // Log how many fields in REDCap could be parsed
        $message = "Found ".count($unmappedRedCapFields)." user-defined fields in REDCap.";
        $this->logger->log($message);
        $info .= $message."\n";

        $table = null;
        
        $rulesParser = new RulesParser();
        $parsedRules = $rulesParser->parse($rulesText);
        $analyzer = new RulesSemanticAnalyzer();
        $parsedRules = $analyzer->check($parsedRules);
        
        # Log parsing errors, and add them to the errors string
        foreach ($parsedRules->getRules() as $rule) {
            foreach ($rule->getErrors() as $error) {
                $this->log($error);
                $errors .= $error."\n";
            }
        }
            
        // Process each rule from first to last
        foreach ($parsedRules->getRules() as $rule) {
            if ($rule->hasErrors()) {
                ;
            } elseif ($rule instanceof TableRule) {     # TABLE RULE
                #------------------------------------------------------------
                # Get the parent table - this will be:
                #   a Table object for non-root tables
                #   a string that is the primary key for root tables
                #------------------------------------------------------------
                $parentTableName = $this->tablePrefix . $rule->parentTable;
                $parentTable = $schema->getTable($parentTableName);

                # Table creation will create the primary key
                $table = $this->generateTable($rule, $parentTable, $this->tablePrefix, $recordIdFieldName);

                $schema->addTable($table);

                #----------------------------------------------------------------------------
                # If the "parent table" is actually a table (i.e., this table is a child
                # table and not a root table)
                #----------------------------------------------------------------------------
                if (is_a($parentTable, Table::class)) {
                    $table->setForeign($parentTable);  # Add a foreign key
                    $parentTable->addChild($table);    # Add as a child of parent table
                }
            } elseif ($rule instanceof FieldRule) {     # FIELD RULE
                // generate Fields...
                 
                if ($table == null) {
                    break; // table not set, probably error with table rule
                    // Actually this should be flagged as an error
                }
                
                $fields = $this->generateFields($rule, $table);
                

                # For a single checkbox, one field will be generated for each option.
                # These generated fields will have type INT and an original field
                # type of CHECKBOX.
                $originalFieldType = $rule->dbFieldType;
                                        
                #-----------------------------------------------------------
                # Process each field
                #
                # Note: there can be more than one field, because a single
                # checkbox REDCap field may be stored as multiple database
                # fields (one for each option)
                #------------------------------------------------------------
                foreach ($fields as $field) {
                    $fname = $field->name;

                    #--------------------------------------------------------
                    # Replace '-' with '_'; needed for case where multiple
                    # choice values are specified as negative numbers
                    # or as text and have a '-' in them
                    #--------------------------------------------------------
                    $fname = str_replace('-', '_', $fname);

                    //-------------------------------------------------------------
                    // !SUFFIXES: Prep for and warn that map field is not in REDCap
                    //-------------------------------------------------------------
                    if (!RowsType::hasSuffixes($table->rowsType) &&
                            $fname !== 'redcap_data_access_group' &&
                            $fname !== 'redcap_survey_identifier' &&
                            empty($timestampFields[$fname]) &&
                            (empty($fieldNames[$fname]))) {
                        $message = "Field not found in REDCap: '".$fname."'";
                        $this->logger->log($message);
                        $warnings .= $message."\n";
                        continue 2;
                    }

                    //------------------------------------------------------------
                    // SUFFIXES: Prep for warning that map field is not in REDCap
                    //           Prep for warning that REDCap field is not in Map
                    //------------------------------------------------------------

                    // For fields in a SUFFIXES table, use the possible suffixes,
                    // including looking up the tree of parent tables, to look
                    // for at least one matching field in the exportfieldnames
                    if (RowsType::hasSuffixes($table->rowsType)) {
                        $fieldFound = false;

                        // Foreach possible suffix, is the field found?
                        foreach ($table->getPossibleSuffixes() as $suffix) {
                            // In case this is a checkbox field
                            if ($originalFieldType === FieldType::CHECKBOX) {
                                // Separate root from category
                                list($rootName, $category) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $fname);

                                // Form the exported field name
                                $exportFieldName = $rootName.$suffix.RedCapEtl::CHECKBOX_SEPARATOR.$category;

                                // Form the original field name
                                // Checkbox fields have a single metadata field name, but
                                // (usually) multiple exported field names
                                $originalFieldName = $rootName.$suffix;
                            } else {
                                // Otherwise, just append suffix
                                $exportFieldName   = $fname.$suffix;
                                $originalFieldName = $fname.$suffix;
                            }

                            //--------------------------------------------------------------
                            // SUFFIXES: Remove from warning that REDCap field is not in Map
                            //--------------------------------------------------------------
                            if (!empty($fieldNames[$exportFieldName])) {
                                $fieldFound = true;
                                 // Remove this field from the list of fields to be mapped
                                unset($unmappedRedCapFields[$exportFieldName]);
                            }
                        } // Foreach possible suffix

                        //------------------------------------------------------------
                        // SUFFIXES: Warn that map field is not in REDCap
                        //------------------------------------------------------------
                        if (false === $fieldFound) {
                            $message = "Suffix field not found in REDCap: '".$fname."'";
                            $this->log($message);
                            $warnings .= $message."\n";
                            break; // continue 2;
                        }
                    } else {
                        //------------------------------------------------------------
                        // !SUFFIXES: Prep for warning that REDCap field is not in Map
                        //------------------------------------------------------------

                        // Not BY_SUFFIXES, and field was found

                        // In case this is a checkbox field
                        if ($originalFieldType === FieldType::CHECKBOX) {
                            // Separate root from category
                            list($rootName, $category) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $fname);

                            // Form the metadata field name
                            // Checkbox fields have a single metadata field name, but
                            // (usually) multiple exported field names
                            $originalFieldName = $rootName;
                        } else {
                            // $originalFieldName is redundant here, but used later when
                            // deciding whether or not to create rows in Lookup
                            $originalFieldName = $fname;
                        }

                        //---------------------------------------------------------------
                        // !SUFFIXES: Remove from warning that REDCap field is not in Map
                        //---------------------------------------------------------------

                        // Remove this field from the list of fields to be mapped
                        unset($unmappedRedCapFields[$fname]);
                    }
        
                    #-----------------------------------------------------------------
                    # If the field name is the record ID field name, don't process
                    # it, because it should have already been automatically added
                    #-----------------------------------------------------------------
                    if ($field->dbName !== $recordIdFieldName) {
                        // Add Field to current Table (error if no current table)
                        $table->addField($field);

                        // If this field has category/label choices
                        if (array_key_exists($originalFieldName, $this->lookupChoices)) {
                            $this->lookupTable->addLookupField($table->name, $originalFieldName);
                            $field->usesLookup = $originalFieldName;
                            $table->usesLookup = true;
                        }
                    }
                } // End foreach field to be created
            } // End if for rule types
        } // End foreach
        
        
        if ($parsedRules->getParsedLineCount() < 1) {
            $message = "Found no transformation rules.";
            $this->log($message);
            $errors .= $message."\n";
        }

        // Log how many fields in REDCap could be parsed
        $message = "Found ".count($unmappedRedCapFields)." unmapped user-defined fields in REDCap.";
        $this->logger->log($message);

        // Set warning if count of remaining redcap fields is above zero
        if (count($unmappedRedCapFields) > 0) {
            $warnings .= $message."\n";

            // List fields, if count is ten or less
            if (count($unmappedRedCapFields) <= 10) {
                $message = "Unmapped fields: ".  implode(', ', array_keys($unmappedRedCapFields));
                $this->logger->log($message);
                $warnings .= $message;
            }
        }

        $messages = array();
        if ('' !== $errors) {
            $messages = array(self::PARSE_ERROR,$errors.$info.$warnings);
        } elseif ('' !== $warnings) {
            $messages = array(self::PARSE_WARN,$info.$warnings);
        } else {
            $messages = array(self::PARSE_VALID,$info);
        }

        $schema->setLookupTable($this->lookupTable);
        
        return array($schema, $messages);
    }


    public function generateTable($rule, $parentTable, $tablePrefix, $recordIdFieldName)
    {
        $tableName = $this->tablePrefix . $rule->tableName;
        $rowsType  = $rule->rowsType;

        $keyType = $this->configuration->getGeneratedKeyType();
        
        # Create the table
        $table = new Table(
            $tableName,
            $parentTable,
            $keyType,
            $rowsType,
            $rule->suffixes,
            $recordIdFieldName
        );

        #---------------------------------------------------------
        # Add the record ID field as a field for all tables
        # (unless the primary key or foreign key has the same
        # name).
        #
        # Note that it looks like this really needs to be added
        # as a string type, because even if it is specified as
        # an Integer in REDCap, there will be no length
        # restriction (unless a min and max are explicitly
        # specified), so a value can be entered that the
        # database will not be able to handle.
        #---------------------------------------------------------
        if ($table->primary === $recordIdFieldName) {
            $error = 'Primary key field has same name as REDCap record id "'
                .$recordIdFieldName.'" on line '
                .$rule->getLineNumber().': "'.$rule->getLine().'"';
            return table;   // try to fix
        } else {
            $fieldTypeSpecifier = $this->configuration->getGeneratedRecordIdType();
            $field = new Field(
                $recordIdFieldName,
                $fieldTypeSpecifier->getType(),
                $fieldTypeSpecifier->getSize()
            );
            $table->addField($field);
        }


        #--------------------------------------------------------------
        # Figure out which identifier fields the rows should contain
        #--------------------------------------------------------------
        $hasEvent      = false;
        $hasInstrument = false;
        $hasInstance   = false;
        $hasSuffixes   = false;

        if (in_array(RowsType::BY_SUFFIXES, $rowsType)) {
            $hasSuffixes = true;
        }

        if ($this->dataProject->isLongitudinal()) {
            # Longitudinal study
            if (in_array(RowsType::BY_REPEATING_INSTRUMENTS, $rowsType)) {
                $hasEvent      = true;
                $hasInstrument = true;
                $hasInstance   = true;
            } elseif (in_array(RowsType::BY_REPEATING_EVENTS, $rowsType)) {
                $hasEvent      = true;
                $hasInstance   = true;
            } elseif (in_array(RowsType::BY_EVENTS, $rowsType)) {
                $hasEvent      = true;
            }

            if (in_array(RowsType::BY_EVENTS_SUFFIXES, $rowsType)) {
                $hasEvent      = true;
                $hasSuffixes   = true;
            }
        } else {
            # Classic (non-longitudinal) study
            if (in_array(RowsType::BY_REPEATING_INSTRUMENTS, $rowsType)) {
                $hasInstrument = true;
                $hasInstance   = true;
            }
        }

        #--------------------------------------------------------------
        # Create event/instrument/instance/suffix identifier fields
        #--------------------------------------------------------------
        if ($hasEvent) {
            $fieldTypeSpecifier = $this->configuration->getGeneratedNameType();
            $field = new Field(RedCapEtl::COLUMN_EVENT, $fieldTypeSpecifier->getType(), $fieldTypeSpecifier->getSize());
            $table->addField($field);
        }

        if ($hasInstrument) {
            $fieldTypeSpecifier = $this->configuration->getGeneratedNameType();
            $field = new Field(
                RedCapEtl::COLUMN_REPEATING_INSTRUMENT,
                $fieldTypeSpecifier->getType(),
                $fieldTypeSpecifier->getSize()
            );
            $table->addField($field);
        }

        if ($hasInstance) {
            $fieldTypeSpecifier = $this->configuration->getGeneratedInstanceType();
            $field = new Field(
                RedCapEtl::COLUMN_REPEATING_INSTANCE,
                $fieldTypeSpecifier->getType(),
                $fieldTypeSpecifier->getSize()
            );
            $table->addField($field);
        }

        if ($hasSuffixes) {
            $fieldTypeSpecifier = $this->configuration->getGeneratedSuffixType();
            $field = new Field(
                RedCapEtl::COLUMN_SUFFIXES,
                $fieldTypeSpecifier->getType(),
                $fieldTypeSpecifier->getSize()
            );
            $table->addField($field);
        }

        return $table;
    }

    /**
     * Generates the field(s) for a FIELD rule.
     *
     * @param Rule $rule the (FIELD) rule to generate the fields for.
     * @param Table $table the table the rules are being generated for.
     *
     * @return array an array of Field objects that represent the
     *     field(s) needed for this rule.
     */
    public function generateFields($rule, $table)
    {
        #------------------------------------------------------------
        # Create the needed fields
        #------------------------------------------------------------
        $fieldName   = $rule->redCapFieldName;
        $fieldType   = $rule->dbFieldType;
        $fieldSize   = $rule->dbFieldSize;
        $dbFieldName = $rule->dbFieldName;

        $fields = array();
                
        // If this is a checkbox field
        if ($fieldType === FieldType::CHECKBOX) {
            # For a checkbox in a Suffix table, append a valid suffix to
            # the field name to get a lookup table field name
            if (RowsType::hasSuffixes($table->rowsType)) {
                # Lookup the choices using any one of the valid suffixes,
                # since, for the same base field,  they all should have
                # the same choices
                $suffixes = $table->getPossibleSuffixes();
                $lookupFieldName = $fieldName.$suffixes[0];
            } else {
                $lookupFieldName = $fieldName;
            }

            $redcapFieldType = $this->dataProject->getFieldType($fieldName);
            
            # Process each value of the checkbox
            foreach ($this->lookupChoices[$lookupFieldName] as $value => $label) {
                # It looks like REDCap uses the lower-case version of the
                # value for making the field name
                $value = strtolower($value);
                // Form the field names for this value
                $checkBoxFieldName = $fieldName.RedCapEtl::CHECKBOX_SEPARATOR.$value;
                $checkBoxDbFieldName = '';
                if (!empty($dbFieldName)) {
                    $checkBoxDbFieldName = $dbFieldName.RedCapEtl::CHECKBOX_SEPARATOR.$value;
                }

                $field = new Field($checkBoxFieldName, FieldType::INT, null, $checkBoxDbFieldName, $redcapFieldType);
                $fields[$fieldName.RedCapEtl::CHECKBOX_SEPARATOR.$value] = $field;
            }
        } else {  # Non-checkbox field
            // Process a single field
            $redcapFieldType = $this->dataProject->getFieldType($fieldName);
            $field = new Field($fieldName, $fieldType, $fieldSize, $dbFieldName, $redcapFieldType);
            $fields[$fieldName] = $field;
        }

        return $fields;
    }

    protected function log($message)
    {
        $this->logger->log($message);
    }
}
