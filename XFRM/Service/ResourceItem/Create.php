<?php

namespace ThemeHouse\ContentCreationLimits\XFRM\Service\ResourceItem;

use XF\Entity\User;

/**
 * Class Create
 * @package ThemeHouse\ContentCreationLimits\XFRM\Service\ResourceItem
 */
class Create extends XFCP_Create
{
    /**
     * @return array
     */
    protected function _validate()
    {
        if ($this->performValidations) {
            $resource = $this->getResource();
            /** @var User $user */
            $user = \XF::em()->find('XF:User', $resource->user_id);

            $totalLimit = $user->hasPermission('resource', 'thccl_resourceLimit');
            $totalTime = $user->hasPermission('resource', 'thccl_resourceTime') * 3600;

            if ($totalTime && $totalLimit > 0) {
                $total = \XF::db()->fetchOne('
                    SELECT
                        COUNT(*)
                    FROM
                      xf_rm_resource
                    WHERE
                      user_id = ?
                      AND resource_date > ?
                ', [$user->user_id, $totalTime > 0 ? \XF::$time - $totalTime : 0]);

                if ($total >= $totalLimit) {
                    if ($totalTime >= 0) {
                        return [
                            \XF::phrase('thccl_you_may_only_create_x_resources_within_y_hours', [
                                'resources' => $totalLimit,
                                'hours' => $totalTime / 3600
                            ])
                        ];
                    } else {
                        return [
                            \XF::phrase('thccl_you_may_only_create_x_resources', [
                                'resources' => $totalLimit
                            ])
                        ];
                    }
                }
            }
        }

        return parent::_validate();
    }
}
