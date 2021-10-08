<?php

namespace ThemeHouse\ContentCreationLimits\XFMG\Service\Album;

use XF\Entity\User;

/**
 * Class Creator
 * @package ThemeHouse\ContentCreationLimits\XFMG\Service\Album
 */
class Creator extends XFCP_Creator
{
    /**
     * @return array
     */
    protected function _validate()
    {
        $album = $this->getAlbum();
        /** @var User $user */
        $user = \XF::em()->find('XF:User', $album->user_id);

        $totalLimit = $user->hasPermission('xfmg', 'thccl_albumLimit');
        $totalTime = $user->hasPermission('xfmg', 'thccl_albumTime') * 3600;

        if ($totalTime && $totalLimit > 0) {
            $total = \XF::db()->fetchOne('
                    SELECT
                        COUNT(*)
                    FROM
                      xf_mg_album
                    WHERE
                      user_id = ?
                      ' . (!\XF::options()->thcontentcreationlimits_limitSoftDeleted ? 'AND album_state != \'deleted\'' : '') . '
                      AND create_date > ?
                ', [$user->user_id, $totalTime > 0 ? \XF::$time - $totalTime : 0]);

            if ($total >= $totalLimit) {
                if ($totalTime >= 0) {
                    return [
                        \XF::phrase('thccl_you_may_only_create_x_albums_within_y_hours', [
                            'albums' => $totalLimit,
                            'hours' => $totalTime / 3600
                        ])
                    ];
                } else {
                    return [
                        \XF::phrase('thccl_you_may_only_create_x_albums', [
                            'albums' => $totalLimit
                        ])
                    ];
                }
            }
        }

        return parent::_validate();
    }
}
