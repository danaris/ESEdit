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

use App\Service\SkyService;

#[AsCommand(
    name: 'es:pregen-js',
    description: 'Pre-generates the Javascript files for the game objects',
)]
class EsPregenJsCommand extends Command
{
	protected string $jsFileLocation = '';
	
	public function __construct(protected Environment $twig, protected EntityManagerInterface $em, protected LoggerInterface $logger, string $projectDir) {
		parent::__construct();
		$this->jsFileLocation = $projectDir.'/public/sky/data/';
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
		$onlyMissing = $input->getOption('onlyMissing');
		
		if (!$onlyMissing || !file_exists($this->jsFileLocation.'systems.js')) {
			$io->write('Writing systems.js...'."\n");
			$this->systemsJS();
		}
		
		if (!$onlyMissing || !file_exists($this->jsFileLocation.'ships.js')) {
			$io->write('Writing ships.js...'."\n");
			$this->shipsJS();
		}
		
		if (!$onlyMissing || !file_exists($this->jsFileLocation.'outfits.js')) {
			$io->write('Writing outfits.js...'."\n");
			$this->outfitsJS();
		}
		
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
		
        $io->success('JS files complete!');

        return Command::SUCCESS;
    }
	
	public function systemsJS(): void {
		$sysQ = $this->em->createQuery('Select s from App\Entity\Sky\System s index by s.name');
		$iterableResult = $sysQ->iterate();
		$outFileName = $this->jsFileLocation.'systems.js';
		file_put_contents($outFileName, '// Pregenerated system data file'."\n".'var systems = {};'."\n");
		foreach ($iterableResult as $row) {
			$System = $row[array_keys($row)[0]];
			$this->logger->debug('JSONifying '.$System->getName());
			$sysResponse = $this->twig->render('sky/data/system.js.twig', ['system'=>$System->toJSON(true)]);
			file_put_contents($outFileName, $sysResponse, FILE_APPEND);
		}
	}
	
	public function shipsJS(): void {
		$shipQ = $this->em->createQuery('Select s from App\Entity\Sky\Ship s');
		$iterableResult = $shipQ->iterate();
		$outFileName = $this->jsFileLocation.'ships.js';
		file_put_contents($outFileName, '// Pregenerated ship data file'."\n".'var ships = {};'."\n");
		foreach ($iterableResult as $row) {
			$Ship = $row[array_keys($row)[0]];
			$this->logger->debug('JSONifying '.$Ship->getName());
			$shipResponse = $this->twig->render('sky/data/ship.js.twig', ['ship'=>$Ship->toJSON(true)]);
			file_put_contents($outFileName, $shipResponse, FILE_APPEND);
		}
	}
	
	public function outfitsJS(): void {
		$outfitQ = $this->em->createQuery('Select s from App\Entity\Sky\Outfit s');
		$iterableResult = $outfitQ->iterate();
		$outFileName = $this->jsFileLocation.'outfits.js';
		file_put_contents($outFileName, '// Pregenerated outfit data file'."\n".'var outfits = {};'."\n");
		foreach ($iterableResult as $row) {
			$Outfit = $row[array_keys($row)[0]];
			$this->logger->debug('JSONifying '.$Outfit->getTrueName());
			$outfitResponse = $this->twig->render('sky/data/outfit.js.twig', ['outfit'=>$Outfit->toJSON(true)]);
			file_put_contents($outFileName, $outfitResponse, FILE_APPEND);
		}
	}
	
	public function planetsJS(): void {
		$planetQ = $this->em->createQuery('Select s from App\Entity\Sky\Planet s');
		$iterableResult = $planetQ->iterate();
		$outFileName = $this->jsFileLocation.'planets.js';
		file_put_contents($outFileName, '// Pregenerated planet data file'."\n".'var planets = {};'."\n");
		foreach ($iterableResult as $row) {
			$Planet = $row[array_keys($row)[0]];
			$this->logger->debug('JSONifying '.$Planet->getName());
			$planetResponse = $this->twig->render('sky/data/planet.js.twig', ['planet'=>$Planet->toJSON(true)]);
			file_put_contents($outFileName, $planetResponse, FILE_APPEND);
		}
	}
	
	public function governmentsJS(): void {
		$governmentQ = $this->em->createQuery('Select s from App\Entity\Sky\Government s');
		$iterableResult = $governmentQ->iterate();
		$outFileName = $this->jsFileLocation.'governments.js';
		file_put_contents($outFileName, '// Pregenerated government data file'."\n".'var governments = {};'."\n");
		foreach ($iterableResult as $row) {
			$Government = $row[array_keys($row)[0]];
			$this->logger->debug('JSONifying '.$Government->getName());
			$governmentResponse = $this->twig->render('sky/data/government.js.twig', ['government'=>$Government->toJSON(true)]);
			file_put_contents($outFileName, $governmentResponse, FILE_APPEND);
		}
	}
	
	public function spritesJS(): void {
		$spriteQ = $this->em->createQuery('Select s from App\Entity\Sky\Sprite s');
		$iterableResult = $spriteQ->iterate();
		$outFileName = $this->jsFileLocation.'sprites.js';
		file_put_contents($outFileName, '// Pregenerated sprite data file'."\n".'var sprites = {};'."\n");
		foreach ($iterableResult as $row) {
			$Sprite = $row[array_keys($row)[0]];
			$this->logger->debug('JSONifying '.$Sprite->getName());
			$spriteResponse = $this->twig->render('sky/data/sprite.js.twig', ['sprite'=>$Sprite->toJSON(true)]);
			file_put_contents($outFileName, $spriteResponse, FILE_APPEND);
		}
	}
	
	public function colorsJS(): void {
		$colorQ = $this->em->createQuery('Select s from App\Entity\Sky\Sprite s');
		$iterableResult = $colorQ->iterate();
		$outFileName = $this->jsFileLocation.'colors.js';
		file_put_contents($outFileName, '// Pregenerated color data file'."\n".'var colors = {};'."\n");
		foreach ($iterableResult as $row) {
			$Color = $row[array_keys($row)[0]];
			$this->logger->debug('JSONifying '.$Color->getName());
			$colorResponse = $this->twig->render('sky/data/color.js.twig', ['color'=>$Color->toJSON(true)]);
			file_put_contents($outFileName, $colorResponse, FILE_APPEND);
		}
	}
}
