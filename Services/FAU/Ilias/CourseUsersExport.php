<?php

namespace FAU\Ilias;

use FAU\Study\Data\SearchCondition;
use ILIAS\DI\Container;
use FAU\Study\Data\SearchResultEvent;
use FAU\User\Data\UserData;
use FAU\Study\Data\Term;

class CourseUsersExport extends AbstractExport
{
    protected Container $dic;
    protected \ilLanguage $lng;

    protected Term $term;
    protected int $cat_ref_id;

    /** @var SearchResultEvent[]  (indexed by obj_id) */
    protected array $events = [];

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
    public function __construct(Term $term, int $cat_ref_id)
    {
        parent::__construct();

        $this->term = $term;
        $this->cat_ref_id = $cat_ref_id;
    }

    /**
     * Export the course users
     * @param string $type  type constant for the export
     * @return string file path of an exported file
     */
    public function exportCoursesUsers(string $type = self::TYPE_EXCEL) : string
    {
        $condition = new SearchCondition('', $this->term->toString(), '', '', $this->cat_ref_id, false);
        $condition = $this->dic->fau()->study()->search()->getProcessedCondition($condition)->withLimit(999999);

        foreach ($this->dic->fau()->study()->search()->getEventList($condition) as $event) {
            if (!empty($event->getIliasRefId())
                && $this->dic->access()->checkAccess('manage_members', '', $event->getIliasRefId())
            ) {
                $this->events[$event->getIliasObjId()] = $event;
            }
        }

        $this->users_member = $this->dic->fau()->ilias()->repo()->getObjectsMemberIds(array_keys($this->events));
        $this->users_waiting = $this->dic->fau()->ilias()->repo()->getObjectsWaitingIds(array_keys($this->events));


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
                'memberships' => $this->getEventsAsText($this->users_member[$user->getUserId()] ?? []),
                'waiting_lists' =>  $this->getEventsAsText($this->users_waiting[$user->getUserId()] ?? []),
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
    protected function getEventsAsText(array $obj_ids) : string
    {
        $texts = [];
        foreach ($obj_ids as $obj_id) {
            $event = $this->events[$obj_id];
            $texts[] = $event->getIliasTitle() . ' [' . \ilLink::_getStaticLink($event->getIliasRefId()) . ']';
        }
        return implode("\n", $texts);
    }
}