<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Test\Participants;

use ILIAS\Language\Language;
use ILIAS\Test\Results\Data\Repository as TestResultRepository;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Component\Modal\Modal;
use ILIAS\UI\Component\Table\Action\Standard as StandardAction;
use ILIAS\UI\URLBuilder;
use ILIAS\UI\URLBuilderToken;
use Psr\Http\Message\ServerRequestInterface;

// fau: sendSimpleResults - send the result of the scored attempt as mail to selected participants
class ParticipantTableSendSimpleResultsAction implements TableAction
{
    public const ACTION_ID = 'send_simple_results_to_participants';

    public function __construct(
        private readonly Language $lng,
        private readonly \ilGlobalTemplateInterface $tpl,
        private readonly UIFactory $ui_factory,
        private readonly \ilDBInterface $db,
        private readonly \ilTestParticipantAccessFilterFactory $participant_access_filter_factory,
        private readonly \ilTestAccess $test_access,
        private readonly \ilObjTest $test_obj,
        private readonly TestResultRepository $test_result_repository
    ) {
    }

    public function getActionId(): string
    {
        return self::ACTION_ID;
    }

    public function isAvailable(): bool
    {
        return $this->test_access->checkManageParticipantsAccess()
            && !$this->test_obj->getAnonymity();
    }

    public function getTableAction(
        URLBuilder $url_builder,
        URLBuilderToken $row_id_token,
        URLBuilderToken $action_token,
        URLBuilderToken $action_type_token
    ): StandardAction {
        return $this->ui_factory->table()->action()->standard(
            $this->lng->txt(self::ACTION_ID),
            $url_builder
                ->withParameter($action_token, self::ACTION_ID)
                ->withParameter($action_type_token, ParticipantTableActions::SHOW_ACTION),
            $row_id_token
        )->withAsync();
    }

    public function getModal(
        URLBuilder $url_builder,
        array $selected_participants,
        bool $all_participants_selected
    ): ?Modal {
        return $this->ui_factory->modal()->interruptive(
            $this->lng->txt(self::ACTION_ID),
            $this->lng->txt('send_simple_results_to_participants_confirm'),
            $url_builder->buildURI()->__toString()
        )->withActionButtonLabel($this->lng->txt('confirm'))
        ->withAffectedItems(
            array_map(
                fn(Participant $participant) => $this->ui_factory->modal()->interruptiveItem()->standard(
                    (string) $participant->getUserId(),
                    $participant->getDisplayName($this->lng)
                ),
                $selected_participants
            )
        );
    }

    public function onSubmit(
        URLBuilder $url_builder,
        ServerRequestInterface $request,
        array $selected_participants,
        bool $all_participants_selected
    ): ?Modal {
        if (!$this->isAvailable()) {
            $this->tpl->setOnScreenMessage(
                \ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                $this->lng->txt('no_permission'),
                true
            );
            return null;
        }

        $participant_data = new \ilTestParticipantData($this->db, $this->lng);
        $participant_data->setParticipantAccessFilter(
            $this->participant_access_filter_factory->getManageParticipantsUserFilter($this->test_obj->getRefId())
        );
        $participant_data->setActiveIdsFilter(
            array_map(static fn(Participant $v): int => $v->getActiveId(), $selected_participants)
        );
        $participant_data->load($this->test_obj->getTestId());

        $sent = [];
        $failed = [];
        foreach ($participant_data->getActiveIds() as $active_id) {
            $name = $participant_data->getFormatedFullnameByActiveId($active_id);
            $result = $this->test_result_repository->getTestResult($active_id);
            if ($result === null) {
                $failed[] = $name;
                continue;
            }
            $user = new \ilObjUser($participant_data->getUserIdByActiveId($active_id));
            $time = $this->getAttemptDate($active_id);

            $subject = sprintf(
                str_replace('\n', "\n", $this->lng->txt('send_simple_results_subject')),
                $this->test_obj->getTitle(),
                $time
            );
            $body = sprintf(
                str_replace('\n', "\n", $this->lng->txt('send_simple_results_body')),
                $this->buildMessage($result),
                $name,
                $user->getMatriculation(),
                $time
            );

            if ($this->deliver($user, $subject, $body)) {
                $sent[] = $name;
            } else {
                $failed[] = $name;
            }
        }

        if ($failed !== []) {
            $this->tpl->setOnScreenMessage(
                \ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                sprintf($this->lng->txt('send_simple_results_to_participants_failed'), implode(', ', $failed)),
                true
            );
        }
        if ($sent !== []) {
            $this->tpl->setOnScreenMessage(
                \ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
                sprintf($this->lng->txt('send_simple_results_to_participants_success'), implode(', ', $sent)),
                true
            );
        }
        return null;
    }

    public function allowActionForRecord(Participant $record): bool
    {
        return $record->getActiveId() !== null
            && $record->getLastFinishedAttempt() !== null;
    }

    public function getSelectionErrorMessage(): ?string
    {
        return $this->lng->txt('send_simple_results_no_valid_participants');
    }

    private function deliver(\ilObjUser $user, string $subject, string $body): bool
    {
        $errors = (new \ilMail(ANONYMOUS_USER_ID))->enqueue(
            $user->getLogin(),
            '',
            '',
            $subject,
            $body,
            []
        );
        return $errors === [];
    }

    private function buildMessage(\ILIAS\Test\Results\Data\ParticipantResult $result): string
    {
        $builder = new \ilTestGradingMessageBuilder($this->lng, $this->tpl, $this->test_obj, $result);
        $message = $builder->buildGradingMarkMsg();
        $message = str_replace('<br />', "\n", $message);
        $message = preg_replace('/<a.*href="([^"]*)".*>(.*)<\/a>/', '$2 ($1)', $message);
        return htmlspecialchars_decode(strip_tags($message), ENT_QUOTES);
    }

    private function getAttemptDate(int $active_id): string
    {
        $row = $this->db->fetchAssoc($this->db->queryF(
            'SELECT submittimestamp FROM tst_active WHERE active_id = %s',
            ['integer'],
            [$active_id]
        ));
        $timestamp = $row['submittimestamp'] ?? null;
        if (empty($timestamp)) {
            $timestamp = $this->test_obj->_getLastAccess($active_id);
        }
        return empty($timestamp) ? '' : date('d.m.Y H:i', strtotime((string) $timestamp));
    }
}
// fau.
