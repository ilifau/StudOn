<?php

namespace FAU\Ilias\Helper;

/**
 * trait for providing additional ilUserQuery methods
 */
trait UserQueryHelper 
{
    // fau: userData - class variable for ref_id to filter educations
    private $educations_ref_id = null;
    // fau.
    
    // fau: userData - getter and setter for ref_id to filter educations
    /**
     * Set the ref_id to filter the list of educations
     */
    public function setEducationsRefId(?int $ref_id)
    {
        $this->educations_ref_id = $ref_id;
    }

    /**
     * Get the ref_id to filter the list of educations
     */
    public function getEducationsRefId() : ?int
    {
        return $this->educations_ref_id;
    }
    // fau.

    // fau: userData add ref id to filter the display of educations as parameter
    /**
     * Get data for user administration list.
     * @deprecated
     */
    public static function getUserListData(
        $a_order_field,
        $a_order_dir,
        $a_offset,
        $a_limit,
        $a_string_filter = "",
        $a_activation_filter = "",
        $a_last_login_filter = null,
        $a_limited_access_filter = false,
        $a_no_courses_filter = false,
        $a_course_group_filter = 0,
        $a_role_filter = 0,
        $a_user_folder_filter = null,
        $a_additional_fields = '',
        $a_user_filter = null,
        $a_first_letter = "",
        $a_authentication_filter = null,
        $a_educations_ref_id = null
    ) {
        $query = new ilUserQuery();
        $query->setOrderField($a_order_field);
        $query->setOrderDirection($a_order_dir);
        $query->setOffset($a_offset);
        $query->setLimit($a_limit);
        $query->setTextFilter($a_string_filter);
        $query->setActionFilter($a_activation_filter);
        $query->setLastLogin($a_last_login_filter);
        $query->setLimitedAccessFilter($a_limited_access_filter);
        $query->setNoCourseFilter($a_no_courses_filter);
        $query->setCourseGroupFilter($a_course_group_filter);
        $query->setRoleFilter($a_role_filter);
        $query->setUserFolder($a_user_folder_filter);
        $query->setAdditionalFields($a_additional_fields);
        $query->setUserFilter($a_user_filter);
        $query->setFirstLetterLastname($a_first_letter);
        $query->setAuthenticationFilter($a_authentication_filter);
        $query->setEducationsRefId($a_educations_ref_id);
        return $query->query();
    }
    // fau.    
}