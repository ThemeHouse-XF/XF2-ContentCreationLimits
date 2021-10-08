<?php

namespace ThemeHouse\ContentCreationLimits\XF\Service\Thread;

use XF\Entity\Node;
use XF\Entity\User;

/**
 * Class Replier
 * @package ThemeHouse\ContentCreationLimits\XF\Service\Thread
 */
class Replier extends XFCP_Replier
{
    /**
     * @return array
     */
    protected function _validate()
    {
        if ($this->performValidations) {
            $thread = $this->getThread();

            /** @var User $user */
            $user = \XF::em()->find('XF:User', $this->getPost()->user_id);

            if ($user) {
                $totalLimit = $user->hasNodePermission($thread->node_id, 'thccl_totalPostLimit');
                $totalTime = $user->hasNodePermission($thread->node_id, 'thccl_totalPostTime') * 3600;

                if ($totalTime && $totalLimit > 0) {
                    $total = \XF::db()->fetchOne('
                    SELECT
                        COUNT(*)
                    FROM
                      xf_post
                    WHERE
                      user_id = ?
                      ' . (!\XF::options()->thcontentcreationlimits_limitSoftDeleted ? 'AND message_state != \'deleted\'' : '') . '
                      ' . (!\XF::options()->thcontentcreationlimits_includeFirst ? 'AND position > 0' : '') . '
                      AND post_date > ?
                ', [$user->user_id, $totalTime > 0 ? \XF::$time - $totalTime : 0]);

                    if ($total >= $totalLimit) {
                        if ($totalTime >= 0) {
                            return [
                                \XF::phrase('thccl_you_may_only_create_x_new_posts_within_y_hours', [
                                    'posts' => $totalLimit,
                                    'hours' => $totalTime / 3600
                                ])
                            ];
                        } else {
                            return [
                                \XF::phrase('thccl_you_may_only_create_x_new_posts', [
                                    'posts' => $totalLimit
                                ])
                            ];
                        }
                    }
                }

                $forumLimit = $user->hasNodePermission($thread->node_id, 'thcc_forumPostLimit');
                $forumTime = $user->hasNodePermission($thread->node_id, 'thcc_forumPostTime') * 3600;

                if ($forumTime && $forumLimit > 0) {
                    $forumTotal = \XF::db()->fetchOne('
                    SELECT
                        COUNT(*)
                    FROM
                      xf_post post
                    LEFT JOIN 
                      xf_thread thread USING(thread_id)
                    WHERE
                      post.user_id = ?
                      AND post.post_date > ?
                      AND thread.node_id = ?
                ', [$user->user_id, $forumTime > 0 ? \XF::$time - $forumTime : 0, $thread->node_id]);

                    /** @var Node $node */
                    $node = \XF::em()->find('XF:Forum', $thread->node_id);

                    if ($forumTotal >= $forumLimit) {
                        if ($forumTime >= 0) {
                            return [
                                \XF::phrase('thccl_you_may_only_create_x_new_posts_within_y_hours_in_forum_z', [
                                    'posts' => $forumLimit,
                                    'hours' => $forumTime / 3600,
                                    'forum' => $node->title
                                ])
                            ];
                        } else {
                            return [
                                \XF::phrase('thccl_you_may_only_create_x_new_posts_in_forum_y', [
                                    'posts' => $forumLimit,
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
