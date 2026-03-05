<?php

namespace App\Utils;

class DomainConst {
    //-----------------------------------------------------
    // Domain constants
    //-----------------------------------------------------
    const VERSION_NUMBER    = '0.0.7';

    const VALUE_ZERO        = 0;

    const VALUE_ONE         = 1;

    const DEFAULT_PAGE_SIZE = 10;

    const ACTION_CREATE     = 'create';

    const ACTION_EDIT       = 'edit';

    const ACTION_INDEX      = 'index';

    const ACTION_SHOW       = 'show';

    const ACTION_PERMISSION = 'permission';

    const ACTION_UPDATE     = 'update';

    const ACTION_DESTROY    = 'destroy';

    const ACTION_STORE      = 'store';

    const VALUE_TRUE        = true;

    const VALUE_FALSE       = false;

    const VALUE_EMPTY       = '';

    const MAX_LENGTH_LOG_DATA = 1000;

    // Gender constants
    const GENDER_MALE   = 1;

    const GENDER_FEMALE = 2;

    const GENDER_OTHER  = 3;

    // API Response status constants
    const API_RESPONSE_STATUS_SUCCESS = 1;

    const API_RESPONSE_STATUS_FAILED = 0;

    // API Error messages
    const API_ERROR_INVALID_INPUT_DATA = 'Invalid input data';
}
