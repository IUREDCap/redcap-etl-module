<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Schema;

use IU\REDCapETL\RedCapEtl;

/**
 * Table is used to store information about a relational table
 */
class Table
{
    public $name = '';

    public $parent = '';        // Table

    public $primary = '';       // Field used as primary key
    public $foreign = '';       // Field used as foreign key to parent

    protected $children = array();   // Child tables

    public $rowsType = array();            // RowsTypes specified for this table (joined with &)
    public $rowsSuffixes = array();        // Suffixes specified for this table
    private $possibleSuffixes = array();   // Suffixes allowed for this table
                                           //   combined with any suffixes
                                           //   allowed for its parent table

    protected $fields = array();
    protected $rows = array();

    private $primaryKey = 1;

    public $usesLookup = false;   // Are fields in this table represented
                                  // in the Lookup table?

    private $recordIdFieldName;
    
    private $keyType;

    /**
     * Creates a Table object.
     *
     * @param string $name the name of the table.
     *
     * @param mixed $parent a schmema object or a string, if the table
     *    is a root table, it will be a string that represents the
     *    name to use as the synthetic primary key. Otherwise it
     *    should be the table's parent Table object.
     *
     * @param string $recordIdFieldName the field name of the record ID
     *     in the REDCap data project.
     */
    public function __construct(
        $name,
        $parent,
        $keyType,
        $rowsType = array(),
        $suffixes = array(),
        $recordIdFieldName = null
    ) {
        $this->recordIdFieldName = $recordIdFieldName;
        $this->keyType = $keyType;
        
        $this->name = str_replace(' ', '_', $name);
        $this->parent = $parent;

        $this->rowsType = $rowsType;
        $this->rowsSuffixes = $suffixes;

        // If Root, set the primary key based on what is given
        // ASSUMES: The field for the primary key will be given in
        //          the place of where a parent table would have been and
        //          will be of type string.
        if (in_array(RowsType::ROOT, $this->rowsType, true)) {
            $field = new Field($parent, $this->keyType->getType(), $this->keyType->getSize());
            $this->primary = $field;
        } else {
            // Otherwise, create a new synthetic primary key
            $this->createPrimary();
        }
    }

    /**
     * Creates primary key field using the table name with
     * '_id' appended to it as the field's name.
     */
    public function createPrimary()
    {
        $primaryId = strtolower($this->name).'_id';
    
        $field = new Field($primaryId, $this->keyType->getType(), $this->keyType->getSize());

        $this->primary = $field;
    }


    public function setForeign($parentTable)
    {
        $this->foreign = $parentTable->primary;
    }

    /**
     * @param Field $field the field to add to the table.
     */
    public function addField($field)
    {
        // If the field being added has the same name as the primary key,
        // do not add it again
        if ($this->primary->name != $field->dbName) {
            array_push($this->fields, $field);
        }
    }

    /**
     * Adds a row to the table.
     *
     * @param Row $row the row to be added.
     */
    public function addRow($row)
    {
        array_push($this->rows, $row);
    }

    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Returns regular fields, primary field, and, if
     * applicable, foreign field
     */
    public function getAllFields()
    {
        $allFields = $this->getFields();

        $fieldNames = array_column($allFields, 'name');

        # If the foreign (key) is an object (Field?) and
        # the name of the foreign key is in the field names,
        # add the foreign key field to the beginning of the fields
        if (is_object($this->foreign)) {
            if (!in_array($this->foreign->name, $fieldNames, true)) {
                array_unshift($allFields, $this->foreign);
            }
        }

        # Add primary key field to the beginning of the fields
        array_unshift($allFields, $this->primary);

        return($allFields);
    }

    public function getAllNonAutoIncrementFields()
    {
        $fields = $this->getAllFields();
        for ($i = 0; $i < count($fields); $i++) {
            $field = $fields[$i];
            if ($field->type === FieldType::AUTO_INCREMENT) {
                unset($fields[$i]);
            }
        }
        return $fields;
    }



    public function getRows()
    {
        return($this->rows);
    }

    public function getNumRows()
    {
        return(count($this->rows));
    }

    public function emptyRows()
    {
        $this->rows = array();
        return(true);
    }

    public function addChild($table)
    {
        array_push($this->children, $table);
    }

    public function getChildren()
    {
         return($this->children);
    }

    public function nextPrimaryKey()
    {
        $this->primaryKey += 1;
        return($this->primaryKey - 1);
    }


    /**
     * Creates a row with the specified data in the table.
     *
     * @param array $data the data values used to create the rowi; represented as a map from
     *     field names to field values.
     * @param string $foreignKey the name of the foreign key field for the row.
     * @param string $suffix the suffix value for the row (if any).
     * @param string $rowType the type of the row (which should be a constant from RowsType).
     *
     * @return bool|int TRUE if row was created, FALSE if ignored
     */
    public function createRow($data, $foreignKey, $suffix, $rowType, $calcFieldIgnorePattern = '')
    {
        #---------------------------------------------------------------
        # If a row is being created for a repeating instrument, don't
        # include the data if it doesn't contain a repeating instrument
        # field. For longitudinal studies (where redcap_event_name and
        # redcap_repeat_instrument fields exist), don't include data if
        # there is no value for redcap_event_name, redcap_repeat_instance
        # or redcap_repeat_instrument
        #---------------------------------------------------------------
        if ($rowType === RowsType::BY_REPEATING_INSTRUMENTS) {
            if (!array_key_exists(RedCapEtl::COLUMN_REPEATING_INSTRUMENT, $data)) {
                return false;
            } elseif (array_key_exists(RedCapEtl::COLUMN_EVENT, $data) &&
                    array_key_exists(RedCapEtl::COLUMN_REPEATING_INSTANCE, $data)) {
                if (empty($data[RedCapEtl::COLUMN_EVENT]) ||
                    empty($data[RedCapEtl::COLUMN_REPEATING_INSTRUMENT]) ||
                    empty($data[RedCapEtl::COLUMN_REPEATING_INSTANCE])) {
                        return false;
                }
            }
        } elseif ($rowType === RowsType::BY_EVENTS) {
            #---------------------------------------------------------------
            # If a row is being created for an EVENT table, don't include
            # the data if it contains a value for redcap_repeat_instrument/
            # redcap_repeat_instance column. Values present in either
            # column indicate a repeating instrument or repeating event
            #---------------------------------------------------------------
            if (array_key_exists(RedCapEtl::COLUMN_REPEATING_INSTRUMENT, $data)) {
                if (!empty($data[RedCapEtl::COLUMN_REPEATING_INSTRUMENT])) {
                    return false;
                }
            }
            if (array_key_exists(RedCapEtl::COLUMN_REPEATING_INSTANCE, $data)) {
                if (!empty($data[RedCapEtl::COLUMN_REPEATING_INSTANCE])) {
                    return false;
                }
            }
        } elseif ($rowType === RowsType::BY_REPEATING_EVENTS) {
            #---------------------------------------------------------------
            # If a row is being created for a REPEATING_EVENTS table, only
            # include data if redcap_event_name and redcap_repeat_instance
            # are present, and redcap_repeat_instrument is empty
            #---------------------------------------------------------------
            if (!array_key_exists(RedCapEtl::COLUMN_REPEATING_INSTANCE, $data) ||
                !array_key_exists(RedCapEtl::COLUMN_EVENT, $data)) {
                return false;
            }
            if (array_key_exists(RedCapEtl::COLUMN_REPEATING_INSTRUMENT, $data)) {
                if (!empty($data[RedCapEtl::COLUMN_REPEATING_INSTRUMENT])) {
                    return false;
                }
            }
            if (array_key_exists(RedCapEtl::COLUMN_EVENT, $data) &&
                    empty($data[RedCapEtl::COLUMN_EVENT])) {
                return false;
            }
            if (array_key_exists(RedCapEtl::COLUMN_REPEATING_INSTANCE, $data) &&
                    empty($data[RedCapEtl::COLUMN_REPEATING_INSTANCE])) {
                return false;
            }
        }

        // create potential Row
        $row = new Row($this);

        // set foreign key of potential Row
        if (strlen($foreignKey) != 0) {
            $row->data[$this->foreign->name] = $foreignKey;
        }

        $dataFound = false;

        // Foreach field
        foreach ($this->getFields() as $field) {
            if (isset($this->recordIdFieldName) && $field->name === $this->recordIdFieldName) {
                $row->data[$field->dbName] = $data[$field->name];
                # If the record ID is the ONLY field in the table,
                # (and it has been found if you get to here)
                # consider the data to be found
                if (count($this->getFields()) === 1) {
                    $dataFound = true;
                }
            } elseif ($field->name === RedCapEtl::COLUMN_EVENT) {
                // If this is the field to store the current event
                $row->data[$field->dbName] = $data[$field->name];
            } elseif ($field->name === RedCapEtl::COLUMN_SUFFIXES) {
                // if this is the field to store the current suffix
                $row->data[$field->dbName] = $suffix;
            } elseif ($field->name === RedCapEtl::COLUMN_REPEATING_INSTRUMENT) {
                # Just copy the repeating instrument field and don't count it
                # as a "data found" field
                $row->data[$field->dbName] = $data[$field->name];
            } elseif ($field->name === RedCapEtl::COLUMN_REPEATING_INSTANCE) {
                # Just copy the repeating instance field and don't count it
                # as a "data found" field
                $row->data[$field->dbName] = $data[$field->name];
            } elseif ($field->name === RedCapEtl::COLUMN_SURVEY_IDENTIFIER) {
                # Just copy the field and don't count it as a "data found" field
                $row->data[$field->dbName] = $data[$field->name];
            } else {
                // Otherwise, get data
                
                $isCalcField     = false;
                $isCheckbox      = false;
                $isCompleteField = false;

                // If this is a checkbox field
                if (preg_match('/'.RedCapEtl::CHECKBOX_SEPARATOR.'/', $field->name)) {
                    $isCheckbox = true;
                    list($rootName,$choiceValue) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $field->name);
                    $choiceValue = str_replace('-', '_', $choiceValue);
                    $variableName = $rootName.$suffix.RedCapEtl::CHECKBOX_SEPARATOR.$choiceValue;
                } else {
                    // Otherwise, just append suffix (if any))
                    $variableName = $field->name.$suffix;
                                        
                    if ($field->redcapType === 'calc') {
                        $isCalcField = true;
                    }

                    if (preg_match('/_complete$/', $field->name)) {
                        $isCompleteField = true;
                    }
                }

                # print "TABLE: ".($this->name)." \n";
                # print "FIELD: ".($field->name)."\n";
    

                // Add field and value to row and
                // keep track of whether any data is found
                $row->data[$field->name] = '';
                $value = null;
                if (array_key_exists($variableName, $data)) {
                    $value = $data[$variableName];
                    $row->data[$field->name] = $value;
                }

                if (isset($value)) {
                    if (is_string($value)) {
                        $value = trim($value);
                    }

                    #----------------------------------------------------------
                    # If this is a checkbox or complete field, ignore zeroes
                    # when determining if data was found in the REDCap record,
                    # Else, only ignore blank strings
                    #----------------------------------------------------------
                    if ($isCheckbox || $isCompleteField) {
                        if ($value != '' && $value !== 0 && $value !== '0') {
                            # Zero (int and string) values are also ignored
                            $dataFound = true;
                        }
                    } elseif ($isCalcField) {
                        if (!empty($calcFieldIgnorePattern) && preg_match($calcFieldIgnorePattern, $value)) {
                            ; // ignore this field
                        } elseif ($value !== '') {
                            $dataFound = true;
                        }
                    } else {
                        if ($value !== '') {
                            $dataFound = true;
                        }
                    }
                }
            }
        }


        if ($dataFound) {
            // Get and set primary key
            $primaryKey = $this->nextPrimaryKey();
            $row->data[$this->primary->name] = $primaryKey;

            // Add Row
            $this->addRow($row);

            return($primaryKey);
        }

        return(false);
    }
    

    public function getPossibleSuffixes()
    {
        // If this table is BY_SUFFIXES and doesn't yet have its possible
        // suffixes set
        if ((in_array(RowsType::BY_SUFFIXES, $this->rowsType, true) ||
            in_array(RowsType::BY_EVENTS_SUFFIXES, $this->rowsType, true)) &&
            (empty($this->possibleSuffixes))) {
            // If there are no parent suffixes, use an empty string
            $parentSuffixes = $this->parent->getPossibleSuffixes();
            if (empty($parentSuffixes)) {
                $parentSuffixes = array('');
            }

            // Loop through all the possibleSuffixes of the parent table
            foreach ($parentSuffixes as $par) {
                // Loop through all the possibleSuffixes of the current table
                foreach ($this->rowsSuffixes as $cur) {
                        array_push($this->possibleSuffixes, $par . $cur);
                }
            }
        }
        
        return($this->possibleSuffixes);
    }

    /**
     * Returns a string representation of this table object (intended for
     * debugging purposes).
     *
     * @param integer $indent the number of spaces to indent each line.
     * @return string
     */
    public function toString($indent = 0)
    {
        $in = str_repeat(' ', $indent);
        $string = '';

        $string .= "{$in}{$this->name} [";
        if (gettype($this->parent) == 'object') {
            $string .= $this->parent->name."]\n";
        } else {
            $string .= $this->parent."]\n";
        }

        $string .= "{$in}primary key: ".$this->primary->toString(0);
        $string .= "{$in}foreign key: ";
        if (gettype($this->foreign) == 'object') {
            $string .= $this->foreign->toString(0);
        } else {
            $string .= $this->foreign."\n";
        }

        # Print the rows type(s)
        $string .= "{$in}rows type: ";
        if ($this->rowsType != null) {
            if (!is_array($this->rowsType)) {
                $string .= "{$this->rowsType}\n";
            } else {
                for ($i = 0; $i < count($this->rowsType); $i++) {
                    if ($i > 0) {
                        $string .= " & ";
                    }
                    $string .= $this->rowsType[$i];
                }
                $string .= "\n";
            }
        }

        $string .= "{$in}Rows Suffixes:";
        foreach ($this->rowsSuffixes as $suffix) {
            $string .= " ".$suffix;
        }
        $string .= "\n";

        $string .= "{$in}Possible Suffixes:";
        foreach ($this->possibleSuffixes as $suffix) {
            $string .= " ".$suffix;
        }
        $string .= "\n";

        $string .= "{$in}Fields:\n";
        foreach ($this->fields as $field) {
            $string .= $field->toString($indent + 4);
        }

        $string .= "{$in}Rows:\n";
        foreach ($this->rows as $row) {
            $string .= $row->toString($indent + 4);
        }

        $string .= "{$in}Children:\n";
        foreach ($this->children as $child) {
            $string .= "{$in}    ".$child->name."\n";
        }

        $string .= "{$in}primary key value: ".$this->primaryKey."\n";

        $string .= "{$in}uses lookup: ".$this->usesLookup."\n";

        return $string;
    }
    
    /**
     * Gets the table's name.
     *
     * @return string the name of the table.
     */
    public function getName()
    {
        return $this->name;
    }
}
