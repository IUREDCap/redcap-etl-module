<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

/**
 * REDCap Project class for the REDCap-ETL External Module that uses REDCap's
 * developer methods instead of the REDCap API.
 * This class can only be used for the embedded server, because it will
 * only work when REDCap-ETL is running under REDCap.
 *
 * NOTE: not all methods and meothod options of the RedCapProject class
 * have been implemented, so this class could need to be expanded
 * if the new methods are added to the
 * REDCap-ETL's RedCapProject class (which this class extends), or if it
 * is modified to use addtional methods from the RedCapProject class.
 *
 * NOTE: this class is dependent on the format of REDCap's project XML. If the
 * format of the XML exported from REDCap changes, this code could break.
 */
class EtlExtRedCapProject extends \IU\REDCapETL\EtlRedCapProject
{
    const REDCAP_XML_NAMESPACE = 'https://projectredcap.org';

    private $projectXml = null;

    public function __construct(
        $apiUrl,
        $apiToken,
        $sslVerify = false,
        $caCertificateFile = null,
        $errorHandler = null,
        $connection = null
    ) {
        # Ignore arguments passed; they're not used
    }

    public function exportFieldNames()
    {
        $fields = \REDCap::getExportFieldNames();

        $fieldNames = array();

        foreach ($fields as $key => $value) {
            $fieldName = array();
            $fieldName['original_field_name'] = $key;
            if (is_array($value)) {
                foreach ($value as $choiceValue => $choiceLabel) {
                    $fieldName['choice_value'] = $choiceValue;
                    $fieldName['export_field_name'] = $choiceLabel;
                    array_push($fieldNames, $fieldName);
                }
            } else {
                $fieldName['choice_value'] = null;
                $fieldName['export_field_name'] = $value;
                array_push($fieldNames, $fieldName);
            }
        }

        return $fieldNames;
    }


    public function exportInstrumentEventMappings()
    {
        $projectXml = $this->getProjectXml();
        $projectXmlDom = new \DomDocument();
        $projectXmlDom->loadXML($projectXml);
        $mapping = array();

        $eventDefinitions = $projectXmlDom->getElementsByTagName('StudyEventDef');
        foreach ($eventDefinitions as $eventDef) {
            $map = array();

            $armNum = $eventDef->getAttribute('redcap:ArmNum');
            $map['arm_num'] = (int) $armNum;

            $uniqueEventName = $eventDef->getAttribute('redcap:UniqueEventName');
            $map['unique_event_name'] = $uniqueEventName;

            foreach ($eventDef->childNodes as $formNode) {
                if ($formNode->nodeName === 'FormRef') {
                    $formName = $formNode->getAttribute('redcap:FormName');
                    $map['form'] = $formName;
                    array_push($mapping, $map);
                }
            }
        }

        return $mapping;
    }

    public function exportInstruments()
    {
        return \REDCap::getInstrumentNames();
    }

    /**
     * Gets the project metadata, and uses caching so that
     * after the first retrieval of metadata from REDCap,
     * the cached values will be used to improve performance.
     *
     * @return array a map from name to value of the project's metadata.
     */
    public function exportMetadata()
    {
        $metadata = \REDCap::getDataDictionary('array');
        $metadata = array_values($metadata);
        return $metadata;
    }

    /**
     * There does not appear to be a corresponding method in the developer
     * methods, but it looks like project_id, project_title and
     * is_longitudinal are all that is used by REDCap-ETL.
     */
    public function exportProjectInfo()
    {
        $projectInfo = array();
        $projectInfo['project_id']      = PROJECT_ID;
        $projectInfo['project_title']   = \REDCap::getProjectTitle();
        $projectInfo['is_longitudinal'] = \REDCap::isLongitudinal();

        $projectXml = $this->getProjectXml();
        $projectXmlDom = new \DomDocument();
        $projectXmlDom->loadXML($projectXml);

        return $projectInfo;
    }

    public function exportProjectXml($metadataOnly)
    {
        $projectXml = \REDCap::getProjectXML($metadataOnly);
        return $projectXml;
    }

    #public function exportRecords()
    #{
    #    $data = REDCap::getData('array');
    #    $data = json_decode($data, true);
    #    return $data;
    #}

    public function exportRecordsAp($parameters)
    {
        $records = null;
        $fields  = null;
        $events  = null;

        $groups = null;
        $combineCheckboxValues  = false;
        $exportDataAccessGroups = false;

        $exportSurveyFields = false;
        $filterLogic        = null;

        if (array_key_exists('recordIds', $parameters)) {
            $records = $parameters['recordIds'];
        }

        if (array_key_exists('fields', $parameters)) {
            $fields = $parameters['fields'];
        }

        if (array_key_exists('events', $parameters)) {
            $events = $parameters['events'];
        }

        if (array_key_exists('exportDataAccessGroups', $parameters)) {
            $exportDataAccessGroups = $parameters['exportDataAccessGroups'];
        }

        if (array_key_exists('filterLogic', $parameters)) {
            $filterLogic = $parameters['filterLogic'];
        }

        $data = \REDCap::getData(
            'json',
            $records,
            $fields,
            $events,
            $groups,
            $combineCheckboxValues,
            $exportDataAccessGroups,
            $exportSurveyFields,
            $filterLogic
        );

        # Note: REDCap's 'array' format returns a nested array that will not
        #     work for REDCap-ETL
        $data = json_decode($data, true);

        return $data;
    }

    public function exportRedcapVersion()
    {
        // phpcs:disable
        global $redcap_version;
        return $redcap_version;
        // phpcs:enable
    }

    public function getRecordIdFieldName()
    {
        $recordIdFieldName = \REDCap::getRecordIdField();
        return $recordIdFieldName;
    }


    public function getProjectXml()
    {
        if (empty($this->projectXml)) {
            $this->projectXml = $this->exportProjectXml(true); // export metadata only
        }
        return $this->projectXml;
    }
}
