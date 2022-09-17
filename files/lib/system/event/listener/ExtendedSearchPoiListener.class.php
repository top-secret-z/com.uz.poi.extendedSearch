<?php
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
 *
 * @author		2019-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.poi.extendedSearch
 */
class ExtendedSearchPoiListener implements IParameterizedEventListener {
	private $eventObj;
	
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		$this->eventObj = $eventObj;
		
		if (EXTENDED_SEARCH_POI_ENABLED && in_array($this->eventObj->getSearchType(), ['everywhere', 'com.uz.poi.poi'])) {
			$eventObj->data[] = $this->getEntries();
		}
	}
	
	/**
	 * Returns the poi list
	 */
	private function getEntries() {
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
