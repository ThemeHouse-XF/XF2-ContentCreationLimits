<?php

namespace ThemeHouse\ContentCreationLimits\XF\Service\Conversation;

use XF\Entity\User;

/**
 * Class Replier
 * @package ThemeHouse\ContentCreationLimits\XF\Service\Conversation
 */
class Replier extends XFCP_Replier
{
    /**
     * @return array
     */
    protected function _validate()
    {
        if ($this->performValidations) {
            $message = $this->getMessage();
            /** @var User $user */
            $user = \XF::em()->find('XF:User', $message->user_id);

            $totalLimit = $user->hasPermission('conversation', 'thccl_cMessageLimit');
            $totalTime = $user->hasPermission('conversation', 'thccl_cMessageTime') * 3600;

            if ($totalTime && $totalLimit > 0) {
                $total = \XF::db()->fetchOne('
                    SELECT
                        COUNT(*)
                    FROM
                      xf_conversation_message message
                      ' . (!\XF::options()->thcontentcreationlimits_includeFirst  ? 'LEFT JOIN xf_conversation_master master USING(conversation_id)' : '') . '
                    WHERE
                      message.user_id = ?
                      ' . (!\XF::options()->thcontentcreationlimits_includeFirst  ? 'AND message.message_id != master.first_message_id' : '') . '
                      AND message.message_date > ?
                ', [$user->user_id, $totalTime > 0 ? \XF::$time - $totalTime : 0]);

                if ($total >= $totalLimit) {
                    if($totalTime >= 0) {
                        return [
                            \XF::phrase('thccl_you_may_only_create_x_conversation_replies_within_y_hours', [
                                'messages' => $totalLimit,
                                'hours' => $totalTime / 3600
                            ])
                        ];
                    }
                    else {
                        return [
                            \XF::phrase('thccl_you_may_only_create_x_conversation_replies', [
                                'messages' => $totalLimit
                            ])
                        ];
                    }
                }
            }
        }

        return parent::_validate();
    }
}
