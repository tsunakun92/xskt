<?php

namespace Modules\Api\GraphQL\Resolvers;

use Modules\Crm\Models\CrmCustomer;
use Modules\Hr\Models\HrProfile;

/**
 * Profile Resolver
 *
 * Handles field resolution for Profile type
 * Supports both HrProfile and CrmCustomer models
 */
class ProfileResolver {
    /**
     * Get fullname field
     * For both CrmCustomer and HrProfile: returns fullname accessor
     *
     * @param  HrProfile|CrmCustomer  $root
     * @return string
     */
    public function fullname($root): string {
        if ($root instanceof CrmCustomer || $root instanceof HrProfile) {
            return $root->fullname ?? '';
        }

        return '';
    }

    /**
     * Get email field
     * Uses related User email for both CrmCustomer and HrProfile
     *
     * @param  HrProfile|CrmCustomer  $root
     * @return string|null
     */
    public function email($root): ?string {
        if ($root instanceof CrmCustomer || $root instanceof HrProfile) {
            return $root->rUser->email ?? null;
        }

        return null;
    }

    /**
     * Get phone_number field
     * Returns phone_number for both CrmCustomer and HrProfile
     *
     * @param  HrProfile|CrmCustomer  $root
     * @return string|null
     */
    public function phoneNumber($root): ?string {
        if ($root instanceof CrmCustomer || $root instanceof HrProfile) {
            return $root->phone_number ?? null;
        }

        return null;
    }

    /**
     * Get company_id field
     * For CrmCustomer: always returns null (customers don't have company)
     * For HrProfile: returns company_id
     *
     * @param  HrProfile|CrmCustomer  $root
     * @return int|null
     */
    public function companyId($root) {
        if ($root instanceof CrmCustomer) {
            return null;
        }

        if ($root instanceof HrProfile) {
            return $root->company_id;
        }

        return null;
    }

    /**
     * Get company relationship
     * For CrmCustomer: always returns null
     * For HrProfile: returns rCompany relationship
     *
     * @param  HrProfile|CrmCustomer  $root
     * @return \Modules\Hr\Models\HrCompany|null
     */
    public function company($root) {
        if ($root instanceof CrmCustomer) {
            return null;
        }

        if ($root instanceof HrProfile) {
            return $root->rCompany;
        }

        return null;
    }
}
