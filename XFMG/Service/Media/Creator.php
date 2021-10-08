<?php

namespace ThemeHouse\ContentCreationLimits\XFMG\Service\Media;

use XF\Entity\User;

/**
 * Class Creator
 * @package ThemeHouse\ContentCreationLimits\XFMG\Service\Media
 */
class Creator extends XFCP_Creator
{
    /**
     * @return array
     */
    protected function _validate()
    {
        $mediaItem = $this->getMediaItem();
        /** @var User $user */
        $user = \XF::em()->find('XF:User', $mediaItem->user_id);

        $totalLimit = $user->hasPermission('xfmg', 'thccl_mediaLimit');
        $totalTime = $user->hasPermission('xfmg', 'thccl_mediaTime') * 3600;

        if ($totalTime && $totalLimit > 0) {
            $total = \XF::db()->fetchOne('
                    SELECT
                        COUNT(*)
                    FROM
                      xf_mg_media_item
                    WHERE
                      user_id = ?
                      ' . (!\XF::options()->thcontentcreationlimits_limitSoftDeleted ? 'AND media_state != \'deleted\'' : '') . '
                      AND media_date > ?
                ', [$user->user_id, $totalTime > 0 ? \XF::$time - $totalTime : 0]);

            if ($total >= $totalLimit) {
                if ($totalTime >= 0) {
                    return [
                        \XF::phrase('thccl_you_may_only_create_x_media_within_y_hours', [
                            'media' => $totalLimit,
                            'hours' => $totalTime / 3600
                        ])
                    ];
                } else {
                    return [
                        \XF::phrase('thccl_you_may_only_create_x_media', [
                            'media' => $totalLimit
                        ])
                    ];
                }
            }
        }

        return parent::_validate();
    }
}
