<?php

namespace App\Entity\Sky;

use App\Entity\DataNode;
use App\Entity\DataWriter;

enum PersonalityTrait : int {
	case PACIFIST = 0;
	case FORBEARING = 1;
	case TIMID = 2;
	case DISABLES = 3;
	case PLUNDERS = 4;
	case HUNTING = 5;
	case STAYING = 6;
	case ENTERING = 7;
	case NEMESIS = 8;
	case SURVEILLANCE = 9;
	case UNINTERESTED = 10;
	case WAITING = 11;
	case DERELICT = 12;
	case FLEEING = 13;
	case ESCORT = 14;
	case FRUGAL = 15;
	case COWARD = 16;
	case VINDICTIVE = 17;
	case SWARMING = 18;
	case UNCONSTRAINED = 19;
	case MINING = 20;
	case HARVESTS = 21;
	case APPEASING = 22;
	case MUTE = 23;
	case OPPORTUNISTIC = 24;
	case MERCIFUL = 25;
	case TARGET = 26;
	case MARKED = 27;
	case LAUNCHING = 28;
	case LINGERING = 29;
	case DARING = 30;
	case SECRETIVE = 31;
	case RAMMING = 32;
	case DECLOAKED = 33;
	
		// This must be last so it can be used for bounds checking.
	case LAST_ITEM_IN_PERSONALITY_TRAIT_ENUM = 34;
};

class Personality {
	// Make sure this matches the number of items in PersonalityTrait,
	// or the build will fail.
	const PERSONALITY_COUNT = 34;
	
	private bool $isDefined = false;
	
	private int $flags; //bitset<PERSONALITY_COUNT>
	private float $confusionMultiplier;
	private float $aimMultiplier;
	private Point $confusion;
	private Point $confusionVelocity;
	
	public static array $traitNames = [
		"pacifist" => PersonalityTrait::PACIFIST,
		"forbearing" => PersonalityTrait::FORBEARING,
		"timid" => PersonalityTrait::TIMID,
		"disables" => PersonalityTrait::DISABLES,
		"plunders" => PersonalityTrait::PLUNDERS,
		"hunting" => PersonalityTrait::HUNTING,
		"staying" => PersonalityTrait::STAYING,
		"entering" => PersonalityTrait::ENTERING,
		"nemesis" => PersonalityTrait::NEMESIS,
		"surveillance" => PersonalityTrait::SURVEILLANCE,
		"uninterested" => PersonalityTrait::UNINTERESTED,
		"waiting" => PersonalityTrait::WAITING,
		"derelict" => PersonalityTrait::DERELICT,
		"fleeing" => PersonalityTrait::FLEEING,
		"escort" => PersonalityTrait::ESCORT,
		"frugal" => PersonalityTrait::FRUGAL,
		"coward" => PersonalityTrait::COWARD,
		"vindictive" => PersonalityTrait::VINDICTIVE,
		"swarming" => PersonalityTrait::SWARMING,
		"unconstrained" => PersonalityTrait::UNCONSTRAINED,
		"mining" => PersonalityTrait::MINING,
		"harvests" => PersonalityTrait::HARVESTS,
		"appeasing" => PersonalityTrait::APPEASING,
		"mute" => PersonalityTrait::MUTE,
		"opportunistic" => PersonalityTrait::OPPORTUNISTIC,
		"merciful" => PersonalityTrait::MERCIFUL,
		"target" => PersonalityTrait::TARGET,
		"marked" => PersonalityTrait::MARKED,
		"launching" => PersonalityTrait::LAUNCHING,
		"lingering" => PersonalityTrait::LINGERING,
		"daring" => PersonalityTrait::DARING,
		"secretive" => PersonalityTrait::SECRETIVE,
		"ramming" => PersonalityTrait::RAMMING,
		"decloaked" => PersonalityTrait::DECLOAKED,
	];
	// Tokens that combine two or more flags.
	public static array $compositeTraits = [
		"heroic" => [PersonalityTrait::DARING, PersonalityTrait::HUNTING]
	];
	
	const DEFAULT_CONFUSION = 10.;
	
	public function load(DataNode $node) {
		$add = ($node->getToken(0) == "add");
		$remove = ($node->getToken(0) == "remove");
		if (!($add || $remove)) {
			$this->flags = 0;
		}
		for ($i = 1 + ($add || $remove); $i < $node->size(); ++$i) {
			$this->parse($node, $i, $remove);
		}
	
		foreach ($node as $child) {
			if ($child->getToken(0) == "confusion") {
				if ($add || $remove) {
					$child->printTrace("Error: Cannot \"" . $node->getToken(0) + "\" a confusion value:");
				} else if ($child->size() < 2) {
					$child->printTrace("Skipping \"confusion\" tag with no value specified:");
				} else {
					$this->confusionMultiplier = $child->getValue(1);
				}
			} else {
				for ($i = 0; $i < $child->size(); ++$i) {
					$this->parse($child, $i, $remove);
				}
			}
		}
		$this->isDefined = true;
	}
	
	
	public function parse(DataNode $node, int $index, bool $remove) {
		$token = $node->getToken($index);
		
		if (!isset(self::$traitNames[$token])) {
			if (!isset(self::$compositeTraits[$token])) {
				$node->printTrace("Warning: Skipping unrecognized personality \"" . $token . "\":");
			} else {
				$traits = self::$compositeTraits[$token];
				foreach ($traits as $trait) {
					if ($remove) {
						$this->flags &= ~(1 << $trait->value);
					} else {
						$this->flags |= 1 << $trait->value;
					}
				}
			}
		} else {
			if ($remove) {
				$this->flags &= ~(1 << self::$traitNames[$token]->value);
			} else {
				$this->flags |= 1 << self::$traitNames[$token]->value;
			}
		}
	}
	

	
	// public:
	// 	Personality() noexcept;
	// 
	// 	void Load(const DataNode &node);
	// 	void Save(DataWriter &out) const;
	// 
	// 	bool IsDefined() const;
	// 
	// 	// Who a ship decides to attack:
	// 	bool IsPacifist() const;
	// 	bool IsForbearing() const;
	// 	bool IsTimid() const;
	// 	bool IsHunting() const;
	// 	bool IsNemesis() const;
	// 	bool IsDaring() const;
	// 
	// 	// How they fight:
	// 	bool IsFrugal() const;
	// 	bool Disables() const;
	// 	bool Plunders() const;
	// 	bool IsVindictive() const;
	// 	bool IsUnconstrained() const;
	// 	bool IsCoward() const;
	// 	bool IsAppeasing() const;
	// 	bool IsOpportunistic() const;
	// 	bool IsMerciful() const;
	// 	bool IsRamming() const;
	// 
	// 	// Mission NPC states:
	// 	bool IsStaying() const;
	// 	bool IsEntering() const;
	// 	bool IsWaiting() const;
	// 	bool IsLaunching() const;
	// 	bool IsFleeing() const;
	// 	bool IsDerelict() const;
	// 	bool IsUninterested() const;
	// 
	// 	// Non-combat goals:
	// 	bool IsSurveillance() const;
	// 	bool IsMining() const;
	// 	bool Harvests() const;
	// 	bool IsSwarming() const;
	// 	bool IsLingering() const;
	// 	bool IsSecretive() const;
	// 
	// 	// Special flags:
	// 	bool IsEscort() const;
	// 	bool IsTarget() const;
	// 	bool IsMarked() const;
	// 	bool IsMute() const;
	// 	bool IsDecloaked() const;
	// 
	// 	// Current inaccuracy in this ship's targeting:
	// 	const Point &Confusion() const;
	// 	void UpdateConfusion(bool isFiring);
	// 
	// 	// Personality to use for ships defending a planet from domination:
	// 	static Personality Defender();
	// 	static Personality DefenderFighter();
	// 
	// 
	// private:
	// 	void Parse(const DataNode &node, int index, bool remove);

}