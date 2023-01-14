<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Whist\Game;
use App\Entity\Whist\Player;
use App\Entity\Whist\Round;
use App\Entity\Whist\GameState;

use App\Service\CardService;

class WhistController extends AbstractController {
	
	protected $em;
	protected $cards;
	protected $session;
	
	protected $player = null;
	
	protected $rounds = [
		['name'=>'1 Ascending', 'cards'=>1, 'normal'=>true, 'index'=>1],
		['name'=>'2 Ascending', 'cards'=>2, 'normal'=>true, 'index'=>2],
		['name'=>'3 Ascending', 'cards'=>3, 'normal'=>true, 'index'=>3],
		['name'=>'4 Ascending', 'cards'=>4, 'normal'=>true, 'index'=>4],
		['name'=>'5 Ascending', 'cards'=>5, 'normal'=>true, 'index'=>5],
		['name'=>'6 Ascending', 'cards'=>6, 'normal'=>true, 'index'=>6],
		['name'=>'7 Ascending', 'cards'=>7, 'normal'=>true, 'index'=>7],
		['name'=>'Half-Blind', 'cards'=>7, 'normal'=>false, 'index'=>8],
		['name'=>'Blind', 'cards'=>7, 'normal'=>false, 'index'=>9],
		['name'=>'Misere', 'cards'=>7, 'normal'=>false, 'index'=>10],
		['name'=>'No-Trump', 'cards'=>7, 'normal'=>false, 'index'=>11],
		['name'=>'7 Descending', 'cards'=>7, 'normal'=>true, 'index'=>12],
		['name'=>'6 Descending', 'cards'=>6, 'normal'=>true, 'index'=>13],
		['name'=>'5 Descending', 'cards'=>5, 'normal'=>true, 'index'=>14],
		['name'=>'4 Descending', 'cards'=>4, 'normal'=>true, 'index'=>15],
		['name'=>'3 Descending', 'cards'=>3, 'normal'=>true, 'index'=>16],
		['name'=>'2 Descending', 'cards'=>2, 'normal'=>true, 'index'=>17],
		['name'=>'1 Descending', 'cards'=>1, 'normal'=>true, 'index'=>18]
	];
	
	public function __construct(EntityManagerInterface $em, CardService $cards, SessionInterface $session) {
		$this->em = $em;
		$this->cards = $cards;
		$this->session = $session;
		
		if ($this->session->get('playerId')) {
			$this->player = $this->em->getRepository(Player::class)->find($this->session->get('playerId'));
		}
	}
	
	/**
	 * @Route("/whist", name="WhistIndex")
	 */
	public function index(Request $request): Response {
		
		return $this->render('whist/index.html.twig', []);
	}
	
	/**
	 * @Route("/whist/data/playerList", name="WhistPlayerList")
	 */
	public function playerList(Request $request): Response {
		$allPlayers = $this->em->getRepository(Player::class)->findAll();
		$playerData = array();
		foreach ($allPlayers as $Player) {
			$playerData []= ['id'=>$Player->getId(),'name'=>$Player->getName(), 'color'=>$Player->getColor()];
		}
		
		return $this->json(['players'=>$playerData]);
	}
	
	/**
	 * @Route("/whist/control/createPlayer", name="WhistCreatePlayer")
	 */
	public function createPlayer(Request $request): Response {
		$playerName = $request->query->get('playerName');
		$playerColor = $request->query->get('playerColor');
		
		$Player = new Player();
		$Player->setName($playerName);
		$Player->setColor($playerColor);
		$Player->setLastPing(new \DateTime());
		$this->em->persist($Player);
		$this->em->flush();
		
		$this->session->set('playerId', $Player->getId());
		
		return $this->json(['id'=>$Player->getId()]);
	}
	
	/**
	 * @Route("/whist/control/selectPlayer", name="WhistSelectPlayer")
	 */
	public function selectPlayer(Request $request): Response {
		$playerId = $request->query->get('playerId');
		$Player = $this->em->getRepository(Player::class)->find($playerId);
		$this->player = $Player;
					
		$this->session->set('playerId', $Player->getId());
		$this->playerPing();
		$this->em->flush();
		
		$activeGameId = -1;
		foreach ($Player->getGames() as $Game) {
			if ($Game->getStatus() == 'playing') {
				$activeGameId = $Game->getId();
				break;
			}
		}
		
		return $this->json(['id'=>$Player->getId(),'gameId'=>$activeGameId]);
	}
	
	/**
	 * @Route("/whist/control/createGame", name="WhistCreateGame")
	 */
	public function createGame(Request $request): Response {
		$gameName = $request->query->get('gameName');
		
		$dupeQ = $this->em->createQuery('Select g from App\Entity\Whist\Game g where g.name=:newName');
		$dupeQ->setParameters(['newName'=>$gameName]);
		if (count($dupeQ->getResult()) > 0) {
			return $this->json(['error'=>'There is already an active game with that name.']);
		}
		
		$Game = new Game();
		$Game->setStarted(new \DateTime());
		$Game->setName($gameName);
		$Game->setDeckId('-');
		$Game->setStatus('gathering');
		$Game->addGamePlayer($this->player);
		// $Durandal = $this->em->getRepository(Player::class)->find(4);
		// $Game->addGamePlayer($Durandal);
		// $Leela = $this->em->getRepository(Player::class)->find(5);
		// $Game->addGamePlayer($Leela);
		$this->em->persist($Game);
		$this->playerPing();
		$this->em->flush();
		
		return $this->json(['gameId'=>$Game->getId()]);
	}
	
	/**
	 * @Route("/whist/control/joinGame", name="WhistJoinGame")
	 */
	public function joinGame(Request $request): Response {
		$gameId = $request->query->get('gameId');
		$Game = $this->em->getRepository(Game::class)->find($gameId);
		$Game->addGamePlayer($this->player);
		$this->playerPing();
		$this->em->flush();
		
		return $this->json(['gameId'=>$Game->getId()]);
	}
	
	/**
	 * @Route("/whist/control/cancelJoin", name="WhistCancelJoin")
	 */
	public function cancelJoin(Request $request): Response {
		if (!$this->player) {
			return $this->json(['error'=>'You do not appear to be logged in.']);
		}
		$gameQ = $this->em->createQuery('Select g from App\Entity\Whist\Game g join g.gamePlayers p where p.id=:player and g.status=:gather');
		$gameQ->setParameters(['player'=>$this->player->getId(), 'gather'=>'gathering']);
		$Game = $gameQ->getOneOrNullResult();
		if ($Game) {
			$Game->removeGamePlayer($this->player);
			$this->playerPing();
			$this->em->flush();
			
			return $this->json(['success'=>true]);
		} else {
			return $this->json(['error'=>'You do not appear to be trying to join a game.']);
		}
		
	}
	
	/**
	 * @Route("/whist/control/updateGameList", name="WhistUpdateGameList")
	 */
	public function updateGameList(Request $request): Response {
		$games = $this->em->getRepository(Game::class)->findBy(array('status'=>'gathering'));
		$this->playerPing();
		$this->em->flush();
		
		return $this->render('whist/gameList.json.twig', ['games'=>$games]);
	}
	
	/**
	 * @Route("/whist/data/checkGatherState/{gameId}", name="WhistCheckGatherState")
	 */
	public function checkGatherState(Request $request, $gameId): Response {
		$Game = $this->em->getRepository(Game::class)->find($gameId);
			$this->playerPing();
			$this->em->flush();
		
		return $this->render('whist/gatherState.json.twig', ['players'=>$Game->getGamePlayers()]);
	}
	
	/**
	 * @Route("/whist/control/startGame/{gameId}", name="WhistStartGame")
	 */
	public function startGame(Request $request, $gameId): Response {
		
		$deckInfo = $this->cards->newDeck();
		
		$Game = $this->em->getRepository(Game::class)->find($gameId);
		
		$Game->setStarted(new \DateTime());
		$Game->setDeckId($deckInfo['deck_id']);
		
		$this->playerPing();
		$this->em->flush();
		
		$playerList = array();
		foreach ($Game->getGamePlayers() as $Player) {
			$playerList []= ['name'=>$Player->getName(), 'id'=>$Player->getId(), 'color'=>$Player->getColor()];
		}
		
		return $this->json(['players'=>$playerList]);
	}
	
	/**
	 * @Route("/whist/control/{gameId}/next", name="WhistGameNextStep")
	 */
	public function nextStep(Request $request, $gameId): Response {
		$Game = $this->em->getRepository(Game::class)->find($gameId);
	}
	
	/**
	 * @Route("/whist/control/{gameId}/startRound", name="WhistGameStartRound")
	 */
	public function startRound(Request $request, $gameId): Response {
		$Game = $this->em->getRepository(Game::class)->find($gameId);
			
		$this->cards->shuffleDeck($Game);
		
		$roundIndex = count($Game->getRounds());
		$roundInfo = $this->rounds[$roundIndex];
		
		$Round = new Round();
		$Round->setGame($Game);
		$Round->setName($roundInfo['name']);
		$Round->setTrump(null);
		$this->em->persist($Round);
		
		if ($roundInfo['normal']) {
			$this->dealHand($Game, $roundInfo['cards']);
			$trumpInfo = $this->cards->drawCard($Game->getDeckId());
			$TrumpCard = $this->cards->cardToObject($trumpInfo['cards'][0]);
			$Round->setTrump(substr($TrumpCard->getSuit(),0,1));
			
			$next = 'bid';
		} else {
			switch ($roundInfo['name']) {
				case 'Half-Blind':
					$this->dealHand($Game, $roundInfo['cards']);
					$next = 'bid';
					break;
				case 'Blind':
					$next = 'bid';
					break;
				case 'Misere':
					$this->dealHand($Game, $roundInfo['cards']);
					$next = 'play';
					break;
				case 'No-Trump':
					$this->dealHand($Game, $roundInfo['cards']);
					$next = 'bid';
					break;
			}
		}
		$State = $Game->getGameState();
		if (!$State) {
			$State = new GameState();
			$State->setGame($Game);
			$this->em->persist($State);
		}
		$State->setCurRound($Round);
		$State->setTurnPlayer($Game->getGamePlayers()[0]);
		$this->em->flush();
		
		$gameData = ['id'=>$gameId,'name'=>$Game->getName(),'players'=>array(),'round'=>$Round->getName(),'turnPlayer'=>$State->getTurnPlayer()->getId()];
		for ($i=0; $i<count($Game->getGamePlayers()); $i++) {
			$GamePlayer = $Game->getGamePlayers()[$i];
			$hand = $this->cards->playerHand($Game, $GamePlayer);
			// if ($Player != $GamePlayer) {
			// 	$handParam = 'handCount';
			// 	$handVal = count($hand);
			// } else {
				$handParam = 'hand';
				$handVal = $hand;
			// }
			$gameData['players'][$i] = ['id'=>$GamePlayer->getId(),'playerNum'=>$i,'name'=>$GamePlayer->getName(),'color'=>$GamePlayer->getColor(),$handParam=>$handVal];
		}
		
		return $this->json($gameData);
	}
	
	/**
	 * @Route("/whist/data/gameState/{gameId}/{playerId}", name="WhistGameState")
	 */
	public function getGameState(Request $request, int $gameId, int $playerId): Response {
		$Game = $this->em->getRepository(Game::class)->find($gameId);
		$Player = $this->em->getRepository(Player::class)->find($playerId);
		
		if (!$Game) {
			return $this->json(['error','Invalid game ID '.$gameId.'.']);
		}
		if (!$Player) {
			return $this->json(['error','Invalid player ID '.$playerId.'.']);
		}
		if (!in_array($Player, $Game->getGamePlayers()->toArray())) {
			return $this->json(['error','Player '.$Player->getName().' is not in game ID '.$gameId.'.']);
		}
		
		$State = $Game->getGameState();
		$Round = $State->getCurRound();
		$roundJSON = ['name'=>$Round->getName(),'trump'=>$Round->getTrump(),'bids'=>array(),'tricks'=>array()];
		foreach ($Round->getBids() as $Bid) {
			$roundJSON['bids'] []= ['playerId'=>$Bid->getPlayer()->getId(),'tricks'=>$Bid->getTricks()];
		}
		foreach ($Round->getTricks() as $Trick) {
			$roundJSON['tricks'][$Trick->getOrderInRound()] = $Trick->getWinner()->getId();
		}
		foreach ($this->rounds as $round) {
			if ($round['name'] == $roundJSON['name']) {
				$roundJSON['index'] = $round['index'];
				break;
			}
		}
		
		$gameData = ['id'=>$gameId,'name'=>$Game->getName(),'players'=>array(),'round'=>$roundJSON];
		for ($i=0; $i<count($Game->getGamePlayers()); $i++) {
			$GamePlayer = $Game->getGamePlayers()[$i];
			$hand = $this->cards->playerHand($Game, $Player);
			if ($Player != $GamePlayer) {
				$handParam = 'handCount';
				$handVal = count($hand);
			} else {
				$handParam = 'hand';
				$handVal = $this->cards->sortHand($hand);
			}
			$gameData['players'][$i] = ['id'=>$GamePlayer->getId(),'playerNum'=>$i,'name'=>$GamePlayer->getName(),'color'=>$GamePlayer->getColor(),$handParam=>$handVal,];
		}
		
		return $this->json($gameData);
	}
	
	function dealHand($Game, $count) {
		for ($c=0; $c<$count; $c++) {
			foreach ($Game->getGamePlayers() as $Player) {
				$this->cards->playerDrawCard($Game, $Player);
			}
		}
	}
	
	function playerPing() {
		$this->player->setLastPing(new \DateTime());
	}
	
	/**
	 * @Route("/whist/makeGame", name="WhistMakeGame")
	 */
	public function makeGame(Request $request): Response {
		$data = array();
		
		
		
		return $this->render('whist/makeGame.html.twig', $data);
	}
	
}