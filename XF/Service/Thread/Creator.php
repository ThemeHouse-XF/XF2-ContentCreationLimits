<?php

namespace ThemeHouse\ContentCreationLimits\XF\Service\Thread;

use XF\Entity\Node;
use XF\Entity\User;

/**
 * Class Creator
 * @package ThemeHouse\ContentCreationLimits\XF\Service\Thread
 */
class Creator extends XFCP_Creator
{
    /**
     * @return array
     */
    protected function _validate()
    {
        if ($this->performValidations) {
            $thread = $this->getThread();
            /** @var User $user */
            $user = \XF::em()->find('XF:User', $thread->user_id);

            if ($user) {

                $totalLimit = $user->hasNodePermission($thread->node_id, 'thccl_totalThreadLimit');
                $totalTime = $user->hasNodePermission($thread->node_id, 'thccl_totalThreadTime') * 3600;

                if ($totalTime && $totalLimit > 0) {
                    $total = \XF::db()->fetchOne('
                    SELECT
                        COUNT(*)
                    FROM
                      xf_thread
                    WHERE
                      user_id = ?
                      ' . (!\XF::options()->thcontentcreationlimits_limitSoftDeleted ? 'AND discussion_state != \'deleted\'' : '') . '
                      AND post_date > ?
                ', [$user->user_id, $totalTime > 0 ? \XF::$time - $totalTime : 0]);

                    if ($total >= $totalLimit) {
                        if ($totalTime >= 0) {
                            return [
                                \XF::phrase('thccl_you_may_only_create_x_new_threads_within_y_hours', [
                                    'threads' => $totalLimit,
                                    'hours' => $totalTime / 3600
                                ])
                            ];
                        } else {
                            return [
                                \XF::phrase('thccl_you_may_only_create_x_new_threads', [
                                    'threads' => $totalLimit
                                ])
                            ];
                        }
                    }
                }

                $forumLimit = $user->hasNodePermission($thread->node_id, 'thcc_forumThreadLimit');
                $forumTime = $user->hasNodePermission($thread->node_id, 'thcc_forumThreadTime') * 3600;

                if ($forumTime && $forumLimit > 0) {
                    $forumTotal = \XF::db()->fetchOne('
                    SELECT
                        COUNT(*)
                    FROM
                      xf_thread
                    WHERE
                      user_id = ?
                      AND post_date > ?
                      AND node_id = ?
                ', [$user->user_id, $forumTime > 0 ? \XF::$time - $forumTime : 0, $thread->node_id]);

                    /** @var Node $node */
                    $node = \XF::em()->find('XF:Forum', $thread->node_id);

                    if ($forumTotal >= $forumLimit) {
                        if ($forumTime >= 0) {
                            return [
                                \XF::phrase('thccl_you_may_only_create_x_new_threads_within_y_hours_in_forum_z', [
                                    'threads' => $forumLimit,
                                    'hours' => $forumTime / 3600,
                                    'forum' => $node->title
                                ])
                            ];
                        } else {
                            return [
                                \XF::phrase('thccl_you_may_only_create_x_new_threads_in_forum_y', [
                                    'threads' => $forumLimit,
                                    'forum' => $node->title
                                ])
                            ];
                        }
                    }
                }
            }
        }

        return parent::_validate();
    }
}
