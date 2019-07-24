#!/bin/bash

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

files=( "./web/user_manual.php" "./web/admin/info.php" );

for file in "${files[@]}"
do
    echo "FILE: ${file}";
    echo "===============================================================";
    cat ${file} \
        | sed 's/[Aa]dmin//g;' \
        | sed 's/alt=//g;' \
        | sed 's/API//g;' \
        | sed 's/[Cc]onfig[^u]//g;' \
        | sed 's/\.com//g;' \
        | sed 's/css//g;' \
        | sed 's/ETL//g;' \
        | sed 's/github//g;' \
        | sed 's/https//g;' \
        | sed 's/IU//g;' \
        | sed 's/<\?php//g;' \
        | sed 's/\.png//g;' \
        | sed 's/redcap-etl//g;' \
        | sed 's/REDCap-ETL//g;' \
        | sed 's/REDCap//g;' \
        | sed 's/SPDX-License-Identifier//g;' \
        | sed 's/SSH//g;' \
        | sed 's/<[^>]*>//g;' \
        | sed 's/use [_a-zA-Z0-9\\]*;//g' \
        | sed 's/require_once[^;]*;//g' \
        | sed 's/\@[_a-zA-Z][_a-zA-Z0-9]*//g' \
        | sed 's/\$[_a-zA-Z][_a-zA-Z0-9]*//g' \
        | sed 's/->[_a-zA-Z][_a-zA-Z0-9]*(//g' \
        | sed 's/[_a-zA-Z][_a-zA-Z0-9]*(//g' \
        | sed 's/[_a-zA-Z][_a-zA-Z0-9]*::[_a-zA-Z][_a-zA-Z0-9]*//g' \
        | sed 's/\?[_a-zA-Z][_a-zA-Z0-9]*=//g' \
        | sed 's/\&[_a-zA-Z][_a-zA-Z0-9]*=//g' \
        | sed 's/RedCapEtlModule//g;' \
        | spell \
        | sort \
        | uniq;
    echo;
done

