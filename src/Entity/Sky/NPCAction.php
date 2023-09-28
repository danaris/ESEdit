<?php

namespace App\Entity\Sky;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\DataNode;
use App\Entity\DataWriter;

#[ORM\Entity]
#[ORM\Table(name: 'NPCAction')]
class NPCAction {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	#[ORM\Column(type: 'string', name: 'triggerName')]
	private string $trigger;
	#[ORM\Column(type: 'boolean')]
	private bool $triggered = false;
	
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\MissionAction', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false)]
	private MissionAction $action;

    #[ORM\ManyToOne(inversedBy: 'npcActionCollection')]
    #[ORM\JoinColumn(nullable: true)]
    private ?NPC $npc = null;
	
	// Construct and Load() at the same time.
	public function __construct(?DataNode $node = null, ?string $missionName = null) {
		$this->action = new MissionAction();
		if ($node && $missionName) {
			$this->load($node, $missionName);
		}
	}
	
	public function load(DataNode $node, string $missionName): void {
		if ($node->size() >= 2) {
			$this->trigger = $node->getToken(1);
		}
	
		foreach ($node as $child) {
			$key = $child->getToken(0);
	
			if ($key == "triggered") {
				$this->triggered = true;
			} else {
				$this->action->loadSingle($child, $missionName);
			}
		}
	}
	
	
	// 
	// // Note: the Save() function can assume this is an instantiated action, not
	// // a template, so it only has to save a subset of the data.
	// void NPCAction::Save(DataWriter &out) const
	// {
	// 	out.Write("on", trigger);
	// 	out.BeginChild();
	// 	{
	// 		if(triggered)
	// 			out.Write("triggered");
	// 
	// 		action.SaveBody(out);
	// 	}
	// 	out.EndChild();
	// }
	// 
	// 
	// 
	// // Check this template or instantiated NPCAction to see if any used content
	// // is not fully defined (e.g. plugin removal, typos in names, etc.).
	// string NPCAction::Validate() const
	// {
	// 	return action.Validate();
	// }
	// 
	// 
	// 
	// void NPCAction::Do(PlayerInfo &player, UI *ui)
	// {
	// 	// All actions are currently one-time-use. Actions that are used
	// 	// are marked as triggered, and cannot be used again.
	// 	if(triggered)
	// 		return;
	// 	triggered = true;
	// 	action.Do(player, ui);
	// }
	// 
	// 
	// 
	// // Convert this validated template into a populated action.
	// NPCAction NPCAction::Instantiate(map<string, string> &subs, const System *origin,
	// 	int jumps, int64_t payload) const
	// {
	// 	NPCAction result;
	// 	result.trigger = trigger;
	// 	result.action = action.Instantiate(subs, origin, jumps, payload);
	// 	return result;
	// }
	
	public function toJSON($justArray=false): array|string {
		$jsonArray = [];
		$jsonArray['id'] = $this->id;
         
		$jsonArray['trigger'] = $this->trigger;
		$jsonArray['action'] = $this->action->toJSON(true);
		
		if ($justArray) {
			return $jsonArray;
		}
		
		return json_encode($jsonArray);
	}

    public function getNpc(): ?NPC
    {
        return $this->npc;
    }

    public function setNpc(?NPC $npc): static
    {
        $this->npc = $npc;

        return $this;
    }

}