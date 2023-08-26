<?php

namespace App\Entity\Sky;

use App\Entity\DataNode;

class ShipEvent {
	
	// This is a "null" event.
	const NONE = 0;
	// This ship did something good for the given ship.
	const ASSIST = (1 << 0);
	// This ship scanned the given ship's cargo. This is not necessarily an
	// act of aggression, but it implies mistrust or underhanded intentions.
	// Also, a mission may fail if a ship of a certain government scans your
	// cargo and discovers you are carrying contraband.
	const SCAN_CARGO = (1 << 1);
	// This ship scanned the given ship's outfits. (If it turns out the
	// outfits include something illegal, this may result in a fine or an
	// outright attack on the ship that was scanned.)
	const SCAN_OUTFITS = (1 << 2);
	// This ship damaged the given ship while not currently being an enemy
	// of that ship's government; this will result in temporary animosities
	// between the two governments. If a ship is "forbearing," it can only
	// be "provoked" if its shields are below 90%.
	// Some governments are provoked by starting a scan.
	const PROVOKE = (1 << 3);
	// This ship disabled the given ship. This will have a permanent effect
	// on your reputation with the given government. This event is generated
	// when a ship takes damage that switches it to being disabled.
	const DISABLE = (1 << 4);
	// This ship boarded the given ship. This may either be an attempt to
	// render assistance, or an attempt to capture the ship.
	const BOARD = (1 << 5);
	// This ship captured the given ship.
	const CAPTURE = (1 << 6);
	// This ship destroyed the given ship. If your projectiles hit a ship
	// that is already exploding, that does not generate a "destroy" event;
	// this is only for the one projectile that caused the explosion.
	const DESTROY = (1 << 7);
	// This is a crime that is so bad that it not only has a negative effect
	// on your reputation, but entirely wipes out any positive reputation
	// you had with the given government, first.
	const ATROCITY = (1 << 8);
	// This ship just jumped into a different system.
	const JUMP = (1 << 9);
	
	// public:
	// 	ShipEvent(const Government *actor, const std::shared_ptr<Ship> &target, int type);
	// 	ShipEvent(const std::shared_ptr<Ship> &actor, const std::shared_ptr<Ship> &target, int type);
	// 
	// 	const std::shared_ptr<Ship> &Actor() const;
	// 	const Government *ActorGovernment() const;
	// 	const std::shared_ptr<Ship> &Target() const;
	// 	const Government *TargetGovernment() const;
	// 	int Type() const;
	// 
	// 
	// private:
	// 	std::shared_ptr<Ship> actor;
	// 	const Government *actorGovernment = nullptr;
	// 	std::shared_ptr<Ship> target;
	// 	const Government *targetGovernment = nullptr;
	// 	int type;

}