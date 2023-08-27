<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;

class CommandErrorListener {
	
	public function onCommandError(ConsoleErrorEvent $event) {
		$output = $event->getOutput();
		
		$command = $event->getCommand();
		
		if ($command) {
			$output->writeln(sprintf('Oops, exception thrown while running command <info>%s</info>', $command->getName()));
		}
		
		$output->getErrorOutput()->writeln('Error trace: '.$event->getError());
	}
	
}