<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

/**
 * Data target class.
 */
class DataTarget
{
    #----------------------------------------------------------------
    # Data targets of ETL process
    #----------------------------------------------------------------
    const DBTYPE_CSV  = 'CSV';
    const DB          = 'db';
    const CSV_ZIP     = 'csv_zip';

    const DEFAULT_MAX_ZIP_DOWNLOAD_FILESIZE = 100; //MB

    public function exportEtlCsvZip($tempDir, $pid)
    {
        #$this->run();

        #-------------------------------------------------
        # Create zip file
        #-------------------------------------------------
        $zip = new \ZipArchive();

        #create the directory will all of the files to be zipped
        $zipFile = $tempDir . 'pid' . $pid . '_redcap-etl-csv.zip';
        $result = $zip->open($zipFile, \ZipArchive::CREATE);
        if ($result === false) {
            $message = 'Unable to create zip file.';
            throw new EtlException($message, EtlException::FILE_ERROR);
        }

        $pattern = $tempDir . '*' . \IU\REDCapETL\Database\CsvDbConnection::FILE_EXTENSION;
        $options = ['remove_all_path' => true];
        $zip->addGlob($pattern, null, $options);
        $zip->close();

        $files = glob($tempDir . '*' . \IU\REDCapETL\Database\CsvDbConnection::FILE_EXTENSION);
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        return $zipFile;
    }
}
