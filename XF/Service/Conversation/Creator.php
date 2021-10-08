<?php

namespace ThemeHouse\ContentCreationLimits\XF\Service\Conversation;

use XF\Entity\User;

/**
 * Class Creator
 * @package ThemeHouse\ContentCreationLimits\XF\Service\Conversation
 */
class Creator extends XFCP_Creator
{
    /**
     * @return array
     */
    protected function _validate()
    {
        if ($this->performValidations) {
            $conversation = $this->getConversation();
            /** @var User $user */
            $user = \XF::em()->find('XF:User', $conversation->user_id);

            $totalLimit = $user->hasPermission('conversation', 'thccl_conversationLimit');
            $totalTime = $user->hasPermission('conversation', 'thccl_conversationTime') * 3600;

            if ($totalTime && $totalLimit > 0) {
                $total = \XF::db()->fetchOne('
                    SELECT
                        COUNT(*)
                    FROM
                      xf_conversation_master
                    WHERE
                      user_id = ?
                      AND start_date > ?
                ', [$user->user_id, $totalTime > 0 ? \XF::$time - $totalTime : 0]);

                if ($total >= $totalLimit) {
                    if($totalTime >= 0) {
                        return [
                            \XF::phrase('thccl_you_may_only_create_x_conversations_within_y_hours', [
                                'conversations' => $totalLimit,
                                'hours' => $totalTime / 3600
                            ])
                        ];
                    }
                    else {
                        return [
                            \XF::phrase('thccl_you_may_only_create_x_conversations', [
                                'conversations' => $totalLimit
                            ])
                        ];
                    }
                }
            }
        }

        return parent::_validate();
    }
}
