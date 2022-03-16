<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

/**
 * Data export right class. The values here should match those in REDCap.
 */
class DataExportRight
{
    public const NO_ACCESS             = 0;
    public const FULL_DATA_SET         = 1;
    public const DEIDENTIFIED          = 2;  // No tagged identifiers, and no free-form text or date/time field
    public const NO_TAGGED_IDENTIFIERS = 3;
}
