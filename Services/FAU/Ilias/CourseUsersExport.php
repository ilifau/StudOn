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
    protected ?Term $term;
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
     * Constructor
     * @param string $term_id
     * @param int    $cat_ref_id
     */
    public function __construct(int $cat_ref_id, ?Term $term, bool $export_with_groups = false)
    {
        parent::__construct();

        $this->term = $term;
        $this->cat_ref_id = $cat_ref_id;
        $this->export_with_groups = $export_with_groups;
    }

    /**
     * Export the course users
     * @param string $type  type constant for the export
     * @return string file path of an exported file
     */
    public function exportCoursesUsers(string $type = self::TYPE_EXCEL) : string
    {
        foreach ($this->dic->fau()->ilias()->repo()->findCoursesOrGroups($this->cat_ref_id, $this->term, $this->export_with_groups) as $container) {
            if ($this->dic->access()->checkAccess('manage_members', '', $container->getRefId())
            ) {
                $this->containers[$container->getObjId()] = $container;
            }
        }

        $this->users_member = $this->dic->fau()->ilias()->repo()->getObjectsMemberIds(array_keys($this->containers));
        $this->users_waiting = $this->dic->fau()->ilias()->repo()->getObjectsWaitingIds(array_keys($this->containers));

        $user_ids = array_unique(array_merge(array_keys($this->users_member), array_keys($this->users_waiting)));
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
                'studydata' => $this->dic->fau()->user()->getStudiesText($user->getPerson(), $this->term),
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