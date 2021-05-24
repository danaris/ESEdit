<?php
// src/Command/NotifyCommand.php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

use App\Entity\TaskCategory;

class NotifyCommand extends Command {
	protected static $defaultName = 'app:notify';
	
	protected $em;
	protected $mailer;
	
	public function __construct(EntityManagerInterface $em, MailerInterface $mailer) {
		$this->em = $em;
		$this->mailer = $mailer;
		
		parent::__construct();
	}
	
	protected function configure() {
		$this->setDescription('Sends email notifications.');
		$this->setHelp('Sends email notifications to all Waffles of the currently-outstanding red & needs-doing items.');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output): int {
		
		$allCategories = $this->em->getRepository(TaskCategory::class)->findAll();
		$needTasks = array();
		$needCount = 0;
		$doneTasks = array();
		foreach ($allCategories as $Category) {
			foreach ($Category->getChildren() as $Subcategory) {
				foreach ($Subcategory->getTasks() as $TaskDef) {
					if ($TaskDef->getInNeed()) {
						if (!isset($needTasks[$TaskDef->getCategory()->getParent()->getId()])) {
							$needTasks[$TaskDef->getCategory()->getParent()->getId()] = array();
						}
						if (!isset($needTasks[$TaskDef->getCategory()->getParent()->getId()][$TaskDef->getCategory()->getId()])) {
							$needTasks[$TaskDef->getCategory()->getParent()->getId()][$TaskDef->getCategory()->getId()] = array();
						}
						$needTasks[$TaskDef->getCategory()->getParent()->getId()][$TaskDef->getCategory()->getId()] []= $TaskDef;
						continue;
					}
					$now = new \DateTime();
					
					if (count($TaskDef->getInstances()) > 0) {
						$lastDone = $TaskDef->getInstances()[0];
						$tasksDone[$TaskDef->getId()] = $lastDone;
					} else {
						$lastDone = false;
					}
			
					if ($TaskDef->getRedDays() != null) {
						$redDays = intval($TaskDef->getRedDays());
						$redTime = $now->sub(new \DateInterval('P'.$redDays.'D'));
						if ($lastDone && $lastDone->getDoneOn() < $redTime) {
							$TaskDef->setRed(true);
							if (!isset($needTasks[$TaskDef->getCategory()->getParent()->getId()])) {
								$needTasks[$TaskDef->getCategory()->getParent()->getId()] = array();
							}
							if (!isset($needTasks[$TaskDef->getCategory()->getParent()->getId()][$TaskDef->getCategory()->getId()])) {
								$needTasks[$TaskDef->getCategory()->getParent()->getId()][$TaskDef->getCategory()->getId()] = array();
							}
							$needTasks[$TaskDef->getCategory()->getParent()->getId()][$TaskDef->getCategory()->getId()] []= $TaskDef;
							$doneTasks[$TaskDef->getId()] = $lastDone;
							$needCount++;
						}
					}
				}
			}
		}
		
		//$output->writeLn(['Currently '.$needCount.' task(s) in need:', '----------------']);
		$text = 'Currently '.$needCount.' task(s) in need:'."\n";
		$text .= '----------------'."\n";
		
		foreach ($needTasks as $needCatId => $needSubcats) {
			foreach ($needSubcats as $needSubcatId => $needTaskList) {
				$firstTask = $needTaskList[0];
				//$output->writeLn(['   '.$firstTask->getCategory()->getParent()->getName().' | '.str_replace('&amp;','&',$firstTask->getCategory()->getName())]);
				$text .= '   '.$firstTask->getCategory()->getParent()->getName().' | '.str_replace('&amp;','&',$firstTask->getCategory()->getName())."\n";
				foreach ($needTaskList as $TaskDef) {
					//$output->writeLn(['      '.$TaskDef->getName()]);
					$text .= '      '.$TaskDef->getName()."\n";
				}
			}
		}
		
		$email = (new TemplatedEmail())->from('noreply@home.topazgryphon.org')->to('danaris@mac.com')->addTo('penguinpi@gmail.com')->addTo('jmiller@colgate.edu')->subject('Waffle Notification Test')->text($text)->htmlTemplate('waffle/notifyEmail.html.twig')->context(['needCount'=>$needCount,'needTasks'=>$needTasks,'tasks'=>$doneTasks]);
		
		try {
			$this->mailer->send($email);
			$output->writeLn(['Successfully sent message.']);
		} catch (\Exception $e) {
			$output->writeLn(['Error sending message: ',$e->getMessage()]);
			return Command::FAILURE;
		}
		
		return Command::SUCCESS;
	}
}