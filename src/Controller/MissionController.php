<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

use App\Service\ESDataService;

class MissionController extends AbstractController {

	private string $spriteBasePath;

	public function __construct(protected LoggerInterface $logger,
								protected ESDataService $dataService,
								protected string $projectDir) {
		$this->spriteBasePath = $this->projectDir.'/public/sprites/';
	}

	#[Route(path: '/missions', name: 'Missions')]
	public function missions(Request $request): Response {
		$esData = $this->dataService->data();

		$data = array();
		$data['missionNames'] = $esData['missionNames'];

		sort($data['missionNames']);

		return $this->render('missions.html.twig', $data);
	}

	#[Route(path: '/mission/{missionName}', name: 'MissionByName', requirements: ['missionName'=>'.+'])]
	public function missionByName(Request $request, string $missionName): Response {
		$missionData = $this->dataService->missionData($missionName);

		$data = array();
		$data['mission'] = $missionData;
		$data['tokenReplacements'] = ['<commodity>'=>'<span class="replaceableToken">&lt;commodity&gt;</span>','<tons>'=>'<span class="replaceableToken">&lt;tons&gt;</span>','<cargo>'=>'<span class="replaceableToken">&lt;cargo&gt;</span>','<bunks>'=>'<span class="replaceableToken">&lt;bunks&gt;</span>','<passengers>'=>'<span class="replaceableToken">&lt;passengers&gt;</span>','<fare>'=>'<span class="replaceableToken">&lt;fare&gt;</span>','<origin>'=>'<span class="replaceableToken">&lt;origin&gt;</span>','<planet>'=>'<span class="replaceableToken">&lt;planet&gt;</span>','<system>'=>'<span class="replaceableToken">&lt;system&gt;</span>','<destination>'=>'<span class="replaceableToken">&lt;destination&gt;</span>','<stopovers>'=>'<span class="replaceableToken">&lt;stopovers&gt;</span>','<planet stopovers>'=>'<span class="replaceableToken">&lt;planet stopovers&gt;</span>','<waypoints>'=>'<span class="replaceableToken">&lt;waypoints&gt;</span>','<payment>'=>'<span class="replaceableToken">&lt;payment&gt;</span>','<fine>'=>'<span class="replaceableToken">&lt;fine&gt;</span>','<date>'=>'<span class="replaceableToken">&lt;date&gt;</span>','<day>'=>'<span class="replaceableToken">&lt;day&gt;</span>','<npc>'=>'<span class="replaceableToken">&lt;npc&gt;</span>','<npc model>'=>'<span class="replaceableToken">&lt;npc model&gt;</span>','<first>'=>'<span class="replaceableToken">&lt;first&gt;</span>','<last>'=>'<span class="replaceableToken">&lt;last&gt;</span>','<ship>'=>'<span class="replaceableToken">&lt;ship&gt;</span>'];
		$data['triggerNames'] = ['COMPLETE', 'OFFER', 'ACCEPT', 'DECLINE', 'FAIL', 'ABORT', 'DEFER', 'VISIT', 'STOPOVER', 'WAYPOINT', 'DAILY', 'DISABLED'];

		return $this->render('mission.html.twig', $data);
	}

	#[Route(path: '/missionRaw/{missionName}.json', name: 'MissionJSON', requirements: ['missionName'=>'.+'])]
	public function missionJSON(Request $request, string $missionName): Response {
		$missionData = $this->dataService->missionData($missionName);

		return $this->json($missionData);
	}

	#[Route(path: '/missions/connections', name: 'MissionConnections')]
	public function missionConnections(Request $request): Response {
		$esData = $this->dataService->data();
		$missionNames = $esData['missionNames'];

		$connections = $this->calculateMissionRelations($missionNames);

		$openMissionsByFile = array();
		foreach ($connections as $missionName => $connect) {
			if ($connect['unlockedBy'] == []) {
				$mission = $this->dataService->missionData($missionName);
				$file = $mission['_sourceFile'];
				if (!isset($openMissionsByFile[$file])) {
					$openMissionsByFile[$file] = array();
				}
				$openMissionsByFile[$file] []= $missionName;
			}
		}

		$data = array();
		$data['connections'] = $connections;
		$data['openMissions'] = $openMissionsByFile;

		return $this->render('missionConnections.html.twig', $data);
	}
	
	public function calculateMissionRelations($missionNames): array {
		$doneStr = ': done';
		$offeredStr = ': offered';
		$failedStr = ': failed';
		$activeStr = ': active';
		$doneLength = strlen($doneStr);
		$offeredLength = strlen($offeredStr);
		$failedLength = strlen($failedStr);
		$activeLength = strlen($activeStr);
		$missionsDone = array_map(function($missionName) use ($doneStr) {
			return $missionName . $doneStr;
		}, $missionNames);
		$missionsOffered = array_map(function($missionName) use ($offeredStr) {
			return $missionName . $offeredStr;
		}, $missionNames);
		$missionsFailed = array_map(function($missionName) use ($failedStr) {
			return $missionName . $failedStr;
		}, $missionNames);
		$missionsActive = array_map(function($missionName) use ($activeStr) {
			return $missionName . $activeStr;
		}, $missionNames);
		$eventStr = 'event: ';

		$connections = [];
		foreach ($missionNames as $name) {
			$connections[$name] = ['blocks'=>[],'unlocks'=>[],'blockedBy'=>[],'unlockedBy'=>[]];
		}
		
		$missionStatusArray = ['done'=> ['length'=>$doneLength, 'names'=>$missionsDone], 'offered'=> ['length'=>$offeredLength, 'names'=>$missionsOffered], 'failed'=> ['length'=>$failedLength, 'names'=>$missionsFailed], 'active'=> ['length'=>$activeLength, 'names'=>$missionsActive]];
		
		foreach ($missionNames as $missionName) {
			$mission = $this->dataService->missionData($missionName);
			$offerConditions = $this->conditionsChecked($mission['toOffer']);
			foreach ($offerConditions as $toOfferExpression) {
				foreach ($missionStatusArray as $statusName => $statusInfo) {
					// Check that to offer requires "has" each of the possible mission statuses for all the missions
					if (in_array($toOfferExpression['var'], $statusInfo['names'])) {
						$prereqName = substr($toOfferExpression['var'], 0, strlen($toOfferExpression['var']) - $statusInfo['length']);
						/// $prereqMission = $this->missions[$prereqName];
						if ($toOfferExpression['operator'] == 'has') {
							$connections[$prereqName]['unlocks'] []= ['type'=>'mission','mission'=>$missionName, 'on'=>$statusName];
							$connections[$missionName]['unlockedBy'] []= ['type'=>'mission','mission'=>$prereqName, 'on'=>$statusName];
						} else if ($toOfferExpression['operator'] == 'not') {
							$connections[$prereqName]['blocks'] []= ['type'=>'mission','mission'=>$missionName, 'on'=>$statusName];
							$connections[$missionName]['blockedBy'] []= ['type'=>'mission','mission'=>$prereqName, 'on'=>$statusName];
						}
					} else if (substr($toOfferExpression['var'], 0, strlen($eventStr)) == $eventStr) {
						$eventName = substr($toOfferExpression['var'], strlen($eventStr));
						if ($toOfferExpression['operator'] == 'has') {
							$connections[$missionName]['unlockedBy'] []= ['type'=>'event','event'=>$eventName];
						} else if ($toOfferExpression['operator'] == 'not') {
							$connections[$missionName]['blockedBy'] []= ['type'=>'event','event'=>$eventName];
						} else {
							$connections[$missionName]['unlockedBy'] []= ['type'=>'event','event'=>$eventName, 'on'=>$toOfferExpression['expression']];
						}
					} else {
						if ($toOfferExpression['operator'] == 'has') {
							$connections[$missionName]['unlockedBy'] []= ['type'=>'attribute','attribute'=>$toOfferExpression['var']];
						} else if ($toOfferExpression['operator'] == 'not') {
							$connections[$missionName]['blockedBy'] []= ['type'=>'attribute','attribute'=>$toOfferExpression['var']];
						} else {
							$connections[$missionName]['unlockedBy'] []= ['type'=>'attribute','attribute'=>$toOfferExpression['var'], 'on'=>$toOfferExpression['expression']];
						}
					}
				}
			}
			
			// foreach ($mission->getActions() as $trigger => $action) {
			// 	foreach ($action->getAction()->getEvents() as $eventName => $eventData) {
			// 		if (!isset($mission->triggersEventsOn[$trigger])) {
			// 			$mission->triggersEventsOn[$trigger] = [];
			// 		}
			// 		$mission->triggersEventsOn[$trigger] []= ['name'=>$eventName, 'minDays'=>$eventData['minDays'], 'maxDays' => $eventData['maxDays']];
			// 	}
			// }
			// TODO: events
		}

		return $connections;
	}

	public function conditionsChecked(array $conditionSet): array {
		$conditions = [];
		$compareOps = ['==','!=','<=','>=','<','>'];
		foreach ($conditionSet['children'] as $childSet) {
			if ($conditionSet['operator'] == 'has') {
				$conditions []= ['var'=>$childSet['name'],'op'=>'has'];
			} else if ($conditionSet['operator'] == 'not') {
				$conditions []= ['var'=>$childSet['name'],'op'=>'not'];
			} else if (in_array($conditionSet['operator'],$compareOps)) {
				$condition = $this->conditionsChecked($conditionSet['children'][0])[0];
				$expressionParts = [];
				for ($i=1; $i<count($conditionSet['children']); $i++) {
					$expressionParts []= $conditionSet['children'][$i];
				}
				$expression = $this->combineExpressions($expressionParts);
				$conditions []= ['var'=>$condition, 'op'=>$conditionSet['operator'], 'expression'=>$expression];
			}
		}

		return $conditions;
	}

	public function combineExpressions(array $parts): string {
		$expression = '';

		foreach ($parts as $part) {
			if ($expression != '') {
				$expression .= ' ';
			}
			if ($part['operator'] == 'var') {
				$expression .= $part['name'];
			} else if ($part['operator'] == 'lit') {
				$expression .= $part['literal'];
			} else {
				$expression .= $this->combineExpressions($part['children'][0]).' '.$part['operator'].' '.$this->combineExpressions(array_slice($part['children'], 1));
			}
		}

		return $expression;
	}

	#[Route(path: '/conversation', name: 'BasicConversation')]
	public function BasicConversation(Request $request): Response {
		return $this->render('basicConversation.html.twig');
	}

}