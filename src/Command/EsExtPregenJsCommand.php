<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Doctrine\ORM\EntityManagerInterface;

use App\Service\SkyExtService;

use App\Entity\Sky\Ship;

use ESLib\Color;
use ESLib\Files;
use ESLib\GameData;
use ESLib\Government;
use ESLib\System;

#[AsCommand(
    name: 'es:ext:pregen-js',
    description: 'Pre-generates the Javascript files for the game objects, using the extension',
)]
class EsExtPregenJsCommand extends Command
{
	protected string $jsFileLocation = '';
	protected string $cssFileLocation = '';

	public function __construct(protected SkyExtService $skyExtService, protected Environment $twig, protected EntityManagerInterface $em, protected LoggerInterface $logger, string $projectDir) {
		parent::__construct();
		$this->jsFileLocation = $projectDir.'/public/sky/data/';
		$this->cssFileLocation = $projectDir.'/public/css/';

		Files::init('eslib', '-r', '/Users/tcollett/Development/ESLib/endless-sky/', '-c', '/Users/tcollett/Development/ESLib/config/');

		GameData::beginLoad(false, true, false);

	}

    protected function configure(): void
    {
		$this->addOption('onlyMissing', null, InputOption::VALUE_NONE, 'Only create JS files that aren\'t already there');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

		$io->title('Writing JS Files');

		if (!file_exists($this->jsFileLocation)) {
			mkdir($this->jsFileLocation, 0777, true);
		}
		if (!file_exists($this->cssFileLocation)) {
			mkdir($this->cssFileLocation, 0777, true);
		}
		$onlyMissing = $input->getOption('onlyMissing');

		if (!$onlyMissing || !file_exists($this->jsFileLocation.'systems.js')) {
			$io->write('Writing systems.js...'."\n");
			$this->systemsJS();
		}

		// if (!$onlyMissing || !file_exists($this->jsFileLocation.'ships.js')) {
		// 	$io->write('Writing ships.js...'."\n");
		// 	$this->shipsJS();
		// }

		// if (!$onlyMissing || !file_exists($this->jsFileLocation.'outfits.js')) {
		// 	$io->write('Writing outfits.js...'."\n");
		// 	$this->outfitsJS();
		// }

		if (!$onlyMissing || !file_exists($this->jsFileLocation.'planets.js')) {
			$io->write('Writing planets.js...'."\n");
			$this->planetsJS();
		}

		if (!$onlyMissing || !file_exists($this->jsFileLocation.'governments.js')) {
			$io->write('Writing governments.js...'."\n");
			$this->governmentsJS();
		}

		if (!$onlyMissing || !file_exists($this->jsFileLocation.'sprites.js')) {
			$io->write('Writing sprites.js...'."\n");
			$this->spritesJS();
		}

		if (!$onlyMissing || !file_exists($this->jsFileLocation.'colors.js')) {
			$io->write('Writing colors.js...'."\n");
			$this->colorsJS();
		}

		if (!$onlyMissing || !file_exists($this->jsFileLocation.'galaxies.js')) {
			$io->write('Writing galaxies.js...'."\n");
			$this->galaxiesJS();
		}

		// if (!$onlyMissing || !file_exists($this->jsFileLocation.'sales.js')) {
		// 	$io->write('Writing sales.js...'."\n");
		// 	$this->salesJS();
		// }

		// if (!$onlyMissing || !file_exists($this->jsFileLocation.'conversations.js')) {
		// 	$io->write('Writing conversations.js...'."\n");
		// 	$this->conversationsJS();
		// }

		// if (!$onlyMissing || !file_exists($this->jsFileLocation.'missions.js')) {
		// 	$io->write('Writing missions.js...'."\n");
		// 	$this->missionsJS();
		// }

		if (!$onlyMissing || !file_exists($this->jsFileLocation.'wormholes.js')) {
			$io->write('Writing wormholes.js...'."\n");
			$this->wormholesJS();
		}

        $io->success('JS files complete!');

        return Command::SUCCESS;
    }

	public function systemsJS(): void {
		$outFileName = $this->jsFileLocation.'systems.js';
		file_put_contents($outFileName, '// Pregenerated system data file'."\n".'var systems = {};'."\n");
		foreach (GameData::allSystems() as $systemName => $System) {
			//$this->logger->debug('JSONifying '.$System->getName());
			$sysResponse = $this->twig->render('sky/data/system.js.twig', ['system'=>$this->skyExtService->systemToJSON($System)]);
			file_put_contents($outFileName, $sysResponse, FILE_APPEND);
		}
	}

// 	public function shipsJS(): void {
// 		$shipQ = $this->em->createQuery('Select s from App\Entity\Sky\Ship s');
// 		$iterableResult = $shipQ->toIterable();
// 		$outFileName = $this->jsFileLocation.'ships.js';
// 		file_put_contents($outFileName, '// Pregenerated ship data file'."\n".'var ships = {};'."\n");
// 		foreach ($iterableResult as $Ship) {
// 			//$this->logger->debug('JSONifying '.$Ship->getName());
// 			$shipResponse = $this->twig->render('sky/data/ship.js.twig', ['ship'=>$Ship->toJSON(true)]);
// 			file_put_contents($outFileName, $shipResponse, FILE_APPEND);
// 		}
// 	}
//
// 	public function outfitsJS(): void {
// 		$outfitQ = $this->em->createQuery('Select s from App\Entity\Sky\Outfit s');
// 		$iterableResult = $outfitQ->toIterable();
// 		$outFileName = $this->jsFileLocation.'outfits.js';
// 		file_put_contents($outFileName, '// Pregenerated outfit data file'."\n".'var outfits = {};'."\n");
// 		foreach ($iterableResult as $Outfit) {
// 			//$this->logger->debug('JSONifying '.$Outfit->getTrueName());
// 			$outfitResponse = $this->twig->render('sky/data/outfit.js.twig', ['outfit'=>$Outfit->toJSON(true)]);
// 			file_put_contents($outFileName, $outfitResponse, FILE_APPEND);
// 		}
// 	}
//
	public function planetsJS(): void {
		$outFileName = $this->jsFileLocation.'planets.js';
		file_put_contents($outFileName, '// Pregenerated planet data file'."\n".'var planets = {};'."\n");
		foreach (GameData::allPlanets() as $planetName => $Planet) {
			//$this->logger->debug('JSONifying '.$Planet->getName());
			error_log('JSONifying '.$planetName.' ('.$Planet->getName().')');
			$planetResponse = $this->twig->render('sky/data/planet.js.twig', ['planet'=>$this->skyExtService->planetToJSON($Planet)]);
			file_put_contents($outFileName, $planetResponse, FILE_APPEND);
		}
	}

	// Also includes the government colors CSS file
	public function governmentsJS(): void {
		$outFileName = $this->jsFileLocation.'governments.js';
		file_put_contents($outFileName, '// Pregenerated government data file'."\n".'var governments = {};'."\n");
		foreach (GameData::allGovernments() as $govName => $Government) {
			//$this->logger->debug('JSONifying '.$Government->getName());
			error_log('JSONifying '.$govName.' ('.$Government->getName().')');
			$governmentResponse = $this->twig->render('sky/data/government.js.twig', ['government'=>$this->skyExtService->governmentToJSON($Government)]);
			file_put_contents($outFileName, $governmentResponse, FILE_APPEND);
		}

		error_log('Creating gov CSS file...');
		$cssFileName = $this->cssFileLocation.'govColors.css';
		file_put_contents($cssFileName, '/* Pregenerated government colors file */'."\n");
		$governmentCSSResponse = $this->twig->render('sky/data/govColors.css.twig', ['governments'=>GameData::allGovernments()]);
		file_put_contents($cssFileName, $governmentCSSResponse, FILE_APPEND);
	}

	public function spritesJS(): void {
		$outFileName = $this->jsFileLocation.'sprites.js';
		file_put_contents($outFileName, '// Pregenerated sprite data file'."\n".'var sprites = {};'."\n");
		foreach (GameData::allSprites() as $spriteName => $Sprite) {
			//$this->logger->debug('JSONifying '.$Sprite->getName());
			$spriteResponse = $this->twig->render('sky/data/sprite.ext.js.twig', ['sprite'=>$this->skyExtService->spriteToJSON($Sprite)]);
			file_put_contents($outFileName, $spriteResponse, FILE_APPEND);
		}
	}

	public function colorsJS(): void {
		$outFileName = $this->jsFileLocation.'colors.js';
		file_put_contents($outFileName, '// Pregenerated color data file'."\n".'var colors = {};'."\n");
		foreach (GameData::allColors() as $colorName => $Color) {
			//$this->logger->debug('JSONifying '.$Color->name);
			$colorResponse = $this->twig->render('sky/data/color.js.twig', ['color'=>$this->skyExtService->colorToJSON($Color, $colorName)]);
			file_put_contents($outFileName, $colorResponse, FILE_APPEND);
		}
	}

	public function galaxiesJS(): void {
		$outFileName = $this->jsFileLocation.'galaxies.js';
		file_put_contents($outFileName, '// Pregenerated galaxy data file'."\n".'var galaxies = {};'."\n");
		foreach (GameData::allGalaxies() as $galaxyName => $Galaxy) {
			//$this->logger->debug('JSONifying '.$Galaxy->getName());
			$galaxyResponse = $this->twig->render('sky/data/galaxy.js.twig', ['galaxy'=>$this->skyExtService->galaxyToJSON($Galaxy, $galaxyName)]);
			file_put_contents($outFileName, $galaxyResponse, FILE_APPEND);
		}
	}

// 	public function salesJS(): void {
// 		$saleQ = $this->em->createQuery('Select s from App\Entity\Sky\Sale s');
// 		$iterableResult = $saleQ->toIterable();
// 		$outFileName = $this->jsFileLocation.'sales.js';
// 		file_put_contents($outFileName, '// Pregenerated sale data file'."\n".'var shipyards = {};'."\n".'var outfitters = {};'."\n");
// 		foreach ($iterableResult as $Sale) {
// 			//$this->logger->debug('JSONifying '.$Sale->getName());
// 			//$this->logger->debug('It has type ['.$Sale->getType().']');
// 			if ($Sale->getType() == Ship::class) {
// 				//$this->logger->debug('therefore is a shipyard');
// 				$saleType = 'shipyards';
// 			} else {
// 				//$this->logger->debug('therefore is an outfitter');
// 				$saleType = 'outfitters';
// 			}
// 			$saleResponse = $this->twig->render('sky/data/sale.js.twig', ['sale'=>$Sale->toJSON(true), 'saleType'=>$saleType]);
// 			file_put_contents($outFileName, $saleResponse, FILE_APPEND);
// 		}
// 	}
//
// 	public function conversationsJS(): void {
// 		$conversationQ = $this->em->createQuery('Select s from App\Entity\Sky\Conversation s');
// 		$iterableResult = $conversationQ->toIterable();
// 		$outFileName = $this->jsFileLocation.'conversations.js';
// 		file_put_contents($outFileName, '// Pregenerated conversation data file'."\n".'var conversations = {};'."\n");
// 		foreach ($iterableResult as $Conversation) {
// 			//$this->logger->debug('JSONifying Conversation '.$Conversation->getName() ?: $Conversation->getId());
// 			$conversationResponse = $this->twig->render('sky/data/conversation.js.twig', ['conversation'=>$Conversation->toJSON(true)]);
// 			file_put_contents($outFileName, $conversationResponse, FILE_APPEND);
// 		}
// 	}
//
// 	public function missionsJS(): void {
// 		$missionQ = $this->em->createQuery('Select s from App\Entity\Sky\Mission s');
// 		$iterableResult = $missionQ->toIterable();
// 		$outFileName = $this->jsFileLocation.'missions.js';
// 		file_put_contents($outFileName, '// Pregenerated mission data file'."\n".'var missions = {};'."\n");
// 		foreach ($iterableResult as $Mission) {
// 			//$this->logger->debug('JSONifying '.$Mission->getTrueName());
// 			$missionResponse = $this->twig->render('sky/data/mission.js.twig', ['mission'=>$Mission->toJSON(true)]);
// 			file_put_contents($outFileName, $missionResponse, FILE_APPEND);
// 		}
// 	}
//
	public function wormholesJS(): void {
		$outFileName = $this->jsFileLocation.'wormholes.js';
		file_put_contents($outFileName, '// Pregenerated wormhole data file'."\n".'var wormholes = {};'."\n");
		foreach (GameData::allWormholes() as $wormholeName => $Wormhole) {
			//$this->logger->debug('JSONifying '.$Wormhole->getTrueName());
			$wormholeResponse = $this->twig->render('sky/data/wormhole.ext.js.twig', ['wormhole'=>$this->skyExtService->wormholeToJSON($Wormhole)]);
			file_put_contents($outFileName, $wormholeResponse, FILE_APPEND);
		}
	}
}
