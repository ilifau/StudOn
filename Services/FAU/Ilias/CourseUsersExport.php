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
        if (count($this->terms) == 1) {
            $term = reset($this->terms);
        }
        else {
            $term = null;
        }
        
        // get the membersips/waiting lists from courses or groups within the category
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
            $obj_waiting = $this->dic->fau()->ilias()->repo()->getObjectsMemberIds([$filter_obj_id]);
            $user_ids = array_unique(array_merge(array_keys($obj_members), array_keys($obj_waiting)));
        }
        else {
            $user_ids = array_unique(array_merge(array_keys($this->users_member), array_keys($this->users_waiting)));
        }
        $this->users = $this->dic->fau()->user()->getUserData($user_ids, $this->cat_ref_id);

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

        $row = 2;
        foreach ($this->users as $user) {
            $data = [
                'login' => $user->getLogin(),
                'lastname' => $user->getLastname(),
                'firstname' => $user->getFirstname(),
                'gender' => $user->getGender(),
                'email' => $user->getEmail(),
                'matriculation' => $user->getMatriculation(),
                'studydata' => $this->dic->fau()->user()->getStudiesText($user->getPerson(), $term),
                'educations' => $this->dic->fau()->user()->getEducationsText($user->getEducations()),
                'memberships' => $this->getContainersAsText($this->users_member[$user->getUserId()] ?? []),
                'waiting_lists' =>  $this->getContainersAsText($this->users_waiting[$user->getUserId()] ?? []),
            ];
            $this->fillRowData($data, $mapping, $row++);
        }

        $this->adjustSizes();
        return $this->buildExportFile('course_users', $type);
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