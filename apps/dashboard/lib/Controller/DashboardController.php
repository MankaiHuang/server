<?php
/**
 * @copyright Copyright (c) 2020 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Dashboard\Controller;

use OCA\Viewer\Event\LoadViewer;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Dashboard\IManager;
use OCP\Dashboard\IPanel;
use OCP\Dashboard\IRegisterPanelEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IInitialStateService;
use OCP\IRequest;

class DashboardController extends Controller {

	/** @var IInitialStateService */
	private $inititalStateService;
	/** @var IEventDispatcher */
	private $eventDispatcher;
	/** @var IManager */
	private $dashboardManager;

	public function __construct($appName, IRequest $request, IInitialStateService $initialStateService, IEventDispatcher $eventDispatcher, IManager $dashboardManager) {
		parent::__construct($appName, $request);

		$this->inititalStateService = $initialStateService;
		$this->eventDispatcher = $eventDispatcher;
		$this->dashboardManager = $dashboardManager;
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @return TemplateResponse
	 */
	public function index(): TemplateResponse {
		$this->eventDispatcher->dispatchTyped(new IRegisterPanelEvent($this->dashboardManager));

		$dashboardManager = $this->dashboardManager;
		$this->inititalStateService->provideLazyInitialState('dashboard', 'panels', function () use ($dashboardManager) {
			return array_map(function (IPanel $panel) {
				return [
					'id' => $panel->getId(),
					'title' => $panel->getTitle(),
					'iconClass' => $panel->getIconClass(),
					'url' => $panel->getUrl()
				];
			}, $dashboardManager->getPanels());
		});

		if (class_exists(LoadViewer::class)) {
			$this->eventDispatcher->dispatchTyped(new LoadViewer());
		}

		return new TemplateResponse('dashboard', 'index');
	}
}