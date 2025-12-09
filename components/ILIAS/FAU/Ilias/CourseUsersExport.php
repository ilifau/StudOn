<?php

namespace FAU\Ilias;

use FAU\Ilias\Data\ContainerData;
use FAU\Study\Data\SearchCondition;
use ILIAS\DI\Container;
use FAU\Study\Data\SearchResultEvent;
use FAU\User\Data\UserData;
use FAU\Study\Data\Term;

class CourseUsersExport extends AbstractExport
{
    /** @var int Excel row number where data rows start (after header) */
    private const EXCEL_FIRST_DATA_ROW = 2;

    protected Container $dic;
    protected \ilLanguage $lng;

    protected int $cat_ref_id;
    /**
     * @var Term[] $terms
     */
    protected array $terms;
    protected bool $export_with_groups;


    /** @var ContainerData[] (indexed by obj_id) */
    protected array $containers = [];

    /** @var UserData[] (indexed by usr_id) */
    protected array $users = [];

    /** @var int[][] user_id => obj_ids */
    protected array $users_member = [];

    /** @var int[][] user_id => obj_ids */
    protected array $users_waiting = [];

    /**
     * Export of members and waiting users of courses or groups within a category
     * 
     * @param Term[] $terms
     * @param int    $cat_ref_id - ref_id of the category to search for courses or groups within
     * @param bool  $export_with_groups - export with memberships or waiting lists of groups     
     */
    public function __construct(int $cat_ref_id, array $terms, bool $export_with_groups = false)
    {
        parent::__construct();

        $this->terms = $terms;
        $this->cat_ref_id = $cat_ref_id;
        $this->export_with_groups = $export_with_groups;
    }

    /**
     * Check if objects of a certain type can be used to filter the export of users
     * @see exportCourseUsers
     */
    public static function supportsUsersFilterObjectType(string $type) : bool
    {
        return ($type == 'crs' || $type == 'grp');
    }
    

    /**
     * Export the users of courses or groups
     * 
     * If no filter_obj_id is given, then the members and waiting users of all courses/groups in the constructor category are exported
     * In this case the current user must have manage_memgers permission to export their data
     * 
     * If a filter_obj_id is given, then the list of exported users is restricted to the members and waiting users of that object
     * In this case the list of membership / waiting statuses of all courses/groups in the category are added for each user
     *
     * @param string $type  type constant for the export
     * @param ?int $filter_obj_id id of course or group to restrict the users                     
     * @return string file path of an exported file
     */
    public function exportCoursesUsers(string $type = self::TYPE_EXCEL, ?int $filter_obj_id = null) : string
    {
        // Use single term if only one is provided, otherwise use null (exports across all terms)
        if (count($this->terms) == 1) {
            $term = reset($this->terms);
        }
        else {
            $term = null;
        }

        // get the memberships/waiting lists from courses or groups within the category
        foreach ($this->terms as $term) {
            foreach ($this->dic->fau()->ilias()->repo()->findCoursesOrGroups($this->cat_ref_id, $term, $this->export_with_groups) as $container) {
                if (isset($filter_obj_id) || $this->dic->access()->checkAccess('manage_members', '', $container->getRefId())
                ) {
                    $this->containers[$container->getObjId()] = $container;
                }
            }
        }
        $this->users_member = $this->dic->fau()->ilias()->repo()->getObjectsMemberIds(array_keys($this->containers));
        $this->users_waiting = $this->dic->fau()->ilias()->repo()->getObjectsWaitingIds(array_keys($this->containers));

        // get the users to export - either from the given object or from the courses or groups within the category
        if (isset($filter_obj_id)) {
            $obj_members = $this->dic->fau()->ilias()->repo()->getObjectsMemberIds([$filter_obj_id]);
            $obj_waiting = $this->dic->fau()->ilias()->repo()->getObjectsWaitingIds([$filter_obj_id]);
            $user_ids = array_unique(array_merge(array_keys($obj_members), array_keys($obj_waiting)));
        }
        else {
            $user_ids = array_unique(array_merge(array_keys($this->users_member), array_keys($this->users_waiting)));
        }
        $this->users = $this->dic->fau()->user()->getUserData($user_ids, $this->cat_ref_id);

        // Set title for first worksheet
        $this->spreadsheet->getActiveSheet()->setTitle($this->lng->txt('fau_export_sheet_persons'));

        $columns = array(
            'login' => $this->lng->txt('login'),
            'lastname' => $this->lng->txt('lastname'),
            'firstname' => $this->lng->txt('firstname'),
            'gender' => $this->lng->txt('gender'),
            'email' => $this->lng->txt('email'),
            'matriculation' => $this->lng->txt('matriculation'),
            'studydata' => $this->lng->txt('studydata'),
            'educations' => $this->lng->txt('fau_educations'),
            'memberships' => $this->lng->txt('member'),
            'waiting_lists' => $this->lng->txt('on_waiting_list'),
        );
        $mapping = $this->fillHeaderRow($columns);

        $row = self::EXCEL_FIRST_DATA_ROW;
        foreach ($this->users as $user) {
            // Build data array: base user data + membership-specific fields
            $data = array_merge(
                $this->getUserDataArray($user, $term, true),
                [
                    'memberships' => $this->getContainersAsText($this->users_member[$user->getUserId()] ?? []),
                    'waiting_lists' => $this->getContainersAsText($this->users_waiting[$user->getUserId()] ?? []),
                ]
            );
            $this->fillRowData($data, $mapping, $row++);
        }

        $this->adjustSizes();

        // Load shorttexts for detail worksheets (only needed for the new worksheets below)
        // Collect all course_ids from the containers' import_ids
        $shorttext_course_ids = [];
        foreach ($this->containers as $container) {
            try {
                // getImportId() has wrong type declaration (string instead of ImportId)
                // We catch the TypeError and skip containers without valid ImportId
                $import_id = $container->getImportId();
                if ($import_id !== null) {
                    $course_id = $import_id->getCourseId();
                    if (!empty($course_id)) {
                        $shorttext_course_ids[] = $course_id;
                    }
                }
            } catch (\TypeError $e) {
                // Skip containers without valid ImportId (e.g., non-Campo courses)
                continue;
            }
        }
        // Load all shorttexts in one query (performance optimization)
        $unique_course_ids = array_unique($shorttext_course_ids);
        $course_shorttexts = $this->dic->fau()->study()->repo()->getCoursesShorttexts($unique_course_ids);

        // Create additional worksheets with detailed membership and waiting list information
        $this->createDetailWorksheet(
            $this->lng->txt('fau_export_sheet_courses'),
            $this->lng->txt('member'),
            $this->users_member,
            $course_shorttexts,
            $term,
            true  // include educations column
        );
        $this->createDetailWorksheet(
            $this->lng->txt('fau_export_sheet_waiting'),
            $this->lng->txt('on_waiting_list'),
            $this->users_waiting,
            $course_shorttexts,
            $term,
            false  // no educations column
        );

        // Return to the first worksheet (important for proper Excel file structure)
        $this->spreadsheet->setActiveSheetIndex(0);

        return $this->buildExportFile('course_users', $type);
    }

    /**
     * Get base user data as array for export
     * Extracts common user fields to avoid code duplication
     *
     * @param UserData $user The user to extract data from
     * @param Term|null $term Current term for study data
     * @param bool $includeEducations Whether to include educations field
     * @return array User data array with keys matching column names
     */
    protected function getUserDataArray(UserData $user, ?Term $term, bool $includeEducations = true): array
    {
        $data = [
            'login' => $user->getLogin(),
            'lastname' => $user->getLastname(),
            'firstname' => $user->getFirstname(),
            'gender' => $user->getGender(),
            'email' => $user->getEmail(),
            'matriculation' => $user->getMatriculation(),
            'studydata' => $this->dic->fau()->user()->getStudiesText($user->getPerson(), $term),
        ];

        if ($includeEducations) {
            $data['educations'] = $this->dic->fau()->user()->getEducationsText($user->getEducations());
        }

        return $data;
    }

    /**
     * Create a detailed worksheet with user memberships or waiting list entries
     * This is a generic helper method to avoid code duplication between membership and waiting list worksheets
     *
     * @param string $sheetTitle Title of the worksheet (e.g., "Memberships" or "Waiting Lists")
     * @param string $courseTitleLabel Label for the course title column (e.g., "Member" or "Enrolled on Waiting List")
     * @param array $userObjIds Array mapping user_id to obj_ids (e.g., $this->users_member or $this->users_waiting)
     * @param array $course_shorttexts Array of shorttexts indexed by course_id
     * @param Term|null $term Current term for study data
     * @param bool $includeEducations Whether to include the educations column
     */
    protected function createDetailWorksheet(
        string $sheetTitle,
        string $courseTitleLabel,
        array $userObjIds,
        array $course_shorttexts,
        ?Term $term,
        bool $includeEducations = true
    ): void {
        // Create a new worksheet
        $sheet = $this->spreadsheet->createSheet();
        $sheet->setTitle($sheetTitle);

        // Set the new sheet as active so fillHeaderRow() and fillRowData() write to it
        $this->spreadsheet->setActiveSheetIndex($this->spreadsheet->getIndex($sheet));

        // Define base columns
        $columns = [
            'shorttext' => $this->lng->txt('fau_export_column_short_text'),
            'course_title' => $courseTitleLabel,
            'login' => $this->lng->txt('login'),
            'lastname' => $this->lng->txt('lastname'),
            'firstname' => $this->lng->txt('firstname'),
            'gender' => $this->lng->txt('gender'),
            'email' => $this->lng->txt('email'),
            'matriculation' => $this->lng->txt('matriculation'),
            'studydata' => $this->lng->txt('studydata'),
        ];

        // Optionally add educations column
        if ($includeEducations) {
            $columns['educations'] = $this->lng->txt('fau_educations');
        }

        // Fill header row and get column mapping
        $mapping = $this->fillHeaderRow($columns);

        // Fill data rows - one row per membership/waiting list entry per user
        $row = self::EXCEL_FIRST_DATA_ROW;
        foreach ($this->users as $user) {
            // Get all obj_ids for this user from the provided array
            $obj_ids = $userObjIds[$user->getUserId()] ?? [];

            foreach ($obj_ids as $obj_id) {
                // Skip if container doesn't exist (safety check)
                if (!isset($this->containers[$obj_id])) {
                    continue;
                }

                $container = $this->containers[$obj_id];

                // Get shorttext from our loaded array (empty string if not found or NULL)
                $shorttext = '';
                try {
                    // getImportId() has wrong type declaration (string instead of ImportId)
                    // We catch the TypeError and use empty shorttext for containers without valid ImportId
                    $import_id = $container->getImportId();
                    if ($import_id !== null) {
                        $course_id = $import_id->getCourseId();
                        if ($course_id && isset($course_shorttexts[$course_id])) {
                            $shorttext = $course_shorttexts[$course_id];
                        }
                    }
                } catch (\TypeError $e) {
                    // Skip containers without valid ImportId - shorttext remains empty
                    $shorttext = '';
                }

                // Build data array: course-specific fields + user data
                $data = array_merge(
                    [
                        'shorttext' => $shorttext,
                        'course_title' => $container->getTitle(),
                    ],
                    $this->getUserDataArray($user, $term, $includeEducations)
                );

                $this->fillRowData($data, $mapping, $row++);
            }
        }

        // Adjust column widths for the worksheet
        $this->adjustSizes($mapping);
    }

    /**
     * Get e text list of events
     * @param array $obj_ids
     * @return string
     */
    protected function getContainersAsText(array $obj_ids) : string
    {
        $texts = [];
        foreach ($obj_ids as $obj_id) {
            $container = $this->containers[$obj_id];
            $texts[] = $container->getTitle() . ' [' . \ilLink::_getStaticLink($container->getRefId()) . ']';
        }
        return implode("\n", $texts);
    }
}