<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

use App\Entity\Whist\Card;

class CardService {
	
	public static $suits = ['C' => 'Clubs', 'D' => 'Diamonds', 'H' => 'Hearts', 'S' => 'Spades'];
	public static $valueDisplay = [
		2 => '2',
		3 => '3',
		4 => '4',
		5 => '5',
		6 => '6',
		7 => '7',
		8 => '8',
		9 => '9',
		'0' => '10',
		'J' => 'Jack',
		'Q' => 'Queen',
		'K' => 'King',
		'A' => 'Ace'
	];
	public static $value = [
		2 => 2,
		3 => 3,
		4 => 4,
		5 => 5,
		6 => 6,
		7 => 7,
		8 => 8,
		9 => 9,
		'0' => 10,
		'J' => 11,
		'Q' => 12,
		'K' => 13,
		'A' => 14
	];
	
	protected $client;
	
	protected $newDeckURL = 'https://deckofcardsapi.com/api/deck/new/shuffle/';
	protected $shuffleURL = 'https://deckofcardsapi.com/api/deck/%%deckId%%/shuffle/';
	protected $drawURL = 'https://deckofcardsapi.com/api/deck/%%deckId%%/draw/?count=%%drawCount%%';
	protected $pileAddURL = 'https://deckofcardsapi.com/api/deck/%%deckId%%/pile/%%pile_name%%/add/?cards=%%cards%%';
	protected $pileListURL = 'https://deckofcardsapi.com/api/deck/%%deckId%%/pile/%%pile_name%%/list/';
	
	public function __construct(HttpClientInterface $client) {
		$this->client = $client;
	}
	
	function pileNameFor($Game, $Player) {
		return 'whist_'.$Game->getId().'_'.$Player->getId();
	}
	
	function pileAddURLFor($Game, $Player) {
		return str_replace(array('%%deckId%%', '%%pile_name%%'), array($Game->getDeckId(), $this->pileNameFor($Game, $Player)), $this->pileAddURL);
	}
	
	function pileListURLFor($Game, $Player) {
		return str_replace(array('%%deckId%%', '%%pile_name%%'), array($Game->getDeckId(), $this->pileNameFor($Game, $Player)), $this->pileListURL);
	}
	
	public function cardCodeToObject($code) {
		$valueInitial = substr($code, 0, 1);
		$suitInitial = substr($code, 1, 1);
		
		$Card = new Card();
		$Card->setCode($code);
		$Card->setSuit(CardService::$suits[$suitInitial]);
		$Card->setValue(CardService::$valueDisplay[$valueInitial]);
		$Card->setImage('https://deckofcardsapi.com/static/img/'.$code.'.png');
		
		return $Card;
	}
	
	public function cardToObject($cardArray) {
		$Card = new Card();
		$Card->setCode($cardArray['code']);
		$Card->setValue($cardArray['value']);
		$Card->setSuit($cardArray['suit']);
		$Card->setImage($cardArray['image']);
		
		return $Card;
	}
	
	public function newDeck() {
		$response = $this->client->request('GET', $this->newDeckURL);
		
		return $response->toArray();
	}
	
	public function drawCard($deckId) {
		$myDrawURL = str_replace(array('%%deckId%%', '%%drawCount%%'), array($deckId, 1), $this->drawURL);
		
		$response = $this->client->request('GET', $myDrawURL);
		
		return $response->toArray();
	}
	
	public function shuffleDeck($Game) {
		$myShuffleURL = str_replace('%%deckId%%', $Game->getDeckId(), $this->shuffleURL);
		
		$response = $this->client->request('GET', $myShuffleURL);
		$data = $response->toArray();
		if ($data['success']) {
			return $data;
		} else {
			return null;
		}
	}
	
	public function playerDrawCard($Game, $Player) {
		$myDrawURL = str_replace(array('%%deckId%%', '%%drawCount%%'), array($Game->getDeckId(), 1), $this->drawURL);
		error_log('Drawing for '.$Player);
		
		$response = $this->client->request('GET', $myDrawURL);
		
		$data = $response->toArray();
		if ($data['success']) {
			$cardCode = $data['cards'][0]['code'];
			
			$myPileAddURL = str_replace('%%cards%%', $cardCode, $this->pileAddURLFor($Game, $Player));
			
			error_log('Adding '.$cardCode.' to hand of '.$Player);
			
			$response = $this->client->request('GET', $myPileAddURL);
		} else {
			// TODO: Some kind of error; maybe a retry?
			error_log('Draw failure: '.print_r($data,true));
			
			return null;
		}
		
		return $response->toArray();
	}
	
	public function playerHand($Game, $Player) {
		$response = $this->client->request('GET', $this->pileListURLFor($Game, $Player));
		
		$data = $response->toArray();
		$myPile = $data['piles'][$this->pileNameFor($Game, $Player)];
		$myHandJSON = $myPile['cards'];
		
		$myHand = array();
		foreach ($myHandJSON as $cardJSON) {
			$myHand []= $this->cardToObject($cardJSON);
		}
		
		return $myHand;
	}
	
	private $suitOrder = ['D'=>1,'C'=>2,'H'=>3,'S'=>4,'DIAMONDS'=>1,'CLUBS'=>2,'HEARTS'=>3,'SPADES'=>4];
	private $valueOrder = [0=>10,1,2,3,4,5,6,7,8,9,10,'J'=>11,'Q'=>12,'K'=>13,'A'=>14,'JACK'=>11,'QUEEN'=>12,'KING'=>13,'ACE'=>14];
	public function sortHand($hand) {
		
		usort($hand, function(Card $a, Card $b) {
			$aSuitVal = $this->suitOrder[$a->getSuit()];
			$bSuitVal = $this->suitOrder[$b->getSuit()];
			if ($aSuitVal < $bSuitVal) {
				return 1;
			} else if ($aSuitVal > $bSuitVal) {
				return -1;
			} else {
				$aCardVal = $this->valueOrder[$a->getValue()];
				$bCardVal = $this->valueOrder[$b->getValue()];
				if ($aCardVal < $bCardVal) {
					return 1;
				} else if ($aCardVal > $bCardVal) {
					return -1;
				} else {
					return 0;
				}
			}
		});
		
		return $hand;
	}
	
}