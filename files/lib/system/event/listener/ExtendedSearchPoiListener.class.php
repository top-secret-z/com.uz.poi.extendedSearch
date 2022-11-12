<?php

/*
 * Copyright by Udo Zaydowicz.
 * Modified by SoftCreatR.dev.
 *
 * License: http://opensource.org/licenses/lgpl-license.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
namespace poi\system\event\listener;

use poi\data\poi\AccessiblePoiList;
use wcf\data\search\extended\SearchExtendedGroup;
use wcf\data\search\extended\SearchExtendedItem;
use wcf\system\application\ApplicationHandler;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Extended Search for POI entries.
 */
class ExtendedSearchPoiListener implements IParameterizedEventListener
{
    private $eventObj;

    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        $this->eventObj = $eventObj;

        if (EXTENDED_SEARCH_POI_ENABLED && \in_array($this->eventObj->getSearchType(), ['everywhere', 'com.uz.poi.poi'])) {
            $eventObj->data[] = $this->getEntries();
        }
    }

    /**
     * Returns the poi list
     */
    private function getEntries()
    {
        $items = [];
        $search = $this->eventObj->getSearchString(EXTENDED_SEARCH_SEARCH_TYPE);
        $poiList = new AccessiblePoiList();
        $poiList->getConditionBuilder()->add('(poi.subject LIKE ? OR poi.message LIKE ? OR poi.location LIKE ?)', [$search, $search, $search]);
        $poiList->getConditionBuilder()->add('poi.isDisabled = ?', [0]);
        $poiList->sqlOrderBy = 'poi.views DESC';
        $poiList->sqlLimit = EXTENDED_SEARCH_POI_COUNT;
        $poiList->readObjects();

        foreach ($poiList->getObjects() as $poi) {
            $items[] = new SearchExtendedItem($poi->getTitle(), $poi->getLink(), $poi->views, StringUtil::stripHTML($poi->getSimplifiedFormattedMessage()));
        }

        // display on top if active
        $activeApplicationAbbr = ApplicationHandler::getInstance()->getActiveApplication()->getAbbreviation();

        return new SearchExtendedGroup(WCF::getLanguage()->get('wcf.extendedSearch.group.poi'), $items, SearchExtendedGroup::POSITION_RIGHT, ($activeApplicationAbbr === 'poi' ? 1 : 20));
    }
}
