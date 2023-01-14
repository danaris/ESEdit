<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\TaskDefinition;
use App\Entity\TaskDone;
use App\Entity\TaskCategory;
use App\Entity\WaffleDevice;

class WaffleController extends AbstractController {
    
    protected $em;
    
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }
    
    function whichWaffle($ip) {
        $device = $this->em->getRepository(WaffleDevice::class)->findOneBy(['ipAddress'=>$ip]);
        if ($device) {
            return $device->getWaffleName();
        } else {
            return null;
        }
    }
    
    /**
     * @Route("/waffle/", name="WaffleTracker")
     */
    public function index(Request $request): Response {
        
        $categoryQ = $this->em->createQuery('Select c from App\Entity\TaskCategory c where c.parent is NULL order by c.viewOrder');
        $categories = $categoryQ->getResult();
        
        $waffle = $this->whichWaffle($request->getClientIp());
        
        $taskTree = array();
        $tasksDone = array();
        $redTasks = array();
        $redCount = 0;
        foreach ($categories as $Category) {
            foreach ($Category->getChildren() as $Subcategory) {
                foreach ($Subcategory->getTasks() as $TaskDef) {
                    if (!$TaskDef->getEnabled()) {
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
                       // error_log('Checking red status for '.$TaskDef->getName().' ('.$TaskDef->getRedDays().')...');
                        $redDays = intval($TaskDef->getRedDays());
                        $redTime = $now->sub(new \DateInterval('P'.$redDays.'D'));
                        //error_log('Red time: '.$redTime->format('Y-m-d'));
                        if ($lastDone && $lastDone->getDoneOn() < $redTime) {
                            //error_log($lastDone->getDoneOn()->format('Y-m-d').' is red');
                            $TaskDef->setRed(true);
                            if (!isset($redTasks[$TaskDef->getCategory()->getParent()->getId()])) {
                                $redTasks[$TaskDef->getCategory()->getParent()->getId()] = array();
                            }
                            if (!isset($redTasks[$TaskDef->getCategory()->getParent()->getId()][$TaskDef->getCategory()->getId()])) {
                                $redTasks[$TaskDef->getCategory()->getParent()->getId()][$TaskDef->getCategory()->getId()] = array();
                            }
                            $redTasks[$TaskDef->getCategory()->getParent()->getId()][$TaskDef->getCategory()->getId()] []= $TaskDef;
                            $redCount++;
                        } 
                        // else if ($lastDone) {
                        //     error_log($lastDone->getDoneOn()->format('Y-m-d').' is just fine');
                        // }
                    } else if ($TaskDef->getInNeed()) {
                        if (!isset($redTasks[$TaskDef->getCategory()->getParent()->getId()][$TaskDef->getCategory()->getId()])) {
                            $redTasks[$TaskDef->getCategory()->getParent()->getId()][$TaskDef->getCategory()->getId()] = array();
                        }
                        $redTasks[$TaskDef->getCategory()->getParent()->getId()][$TaskDef->getCategory()->getId()] []= $TaskDef;
                        $redCount++;
                    }
                }
            }
        }
        
        $data = array();
        $data['categories'] = $categories;
        $data['tasks'] = $tasksDone;
        $data['redTasks'] = $redTasks;
        $data['redCount'] = $redCount;
        $today = new \DateTime();
        $data['today'] = $today->format('Y-m-d');
        $data['waffle'] = $waffle;
        
        return $this->render('index.html.twig', $data);
    }
    
    /**
     * @Route("/waffle/addDevice", name="WaffleAddDevice")
     */
    public function addDevice(Request $request) {
        $deviceIP = $request->getClientIp();
        $waffleName = $request->request->get('waffleName');
        
        $dupeCheck = $this->whichWaffle($deviceIP);
        if (!$dupeCheck) {
            $Device = new WaffleDevice();
            $Device->setIpAddress($deviceIP);
            $Device->setWaffleName($waffleName);
            $this->em->persist($Device);
            $this->em->flush();
            
            return $this->render('success.json.twig');
        } else {
            return $this->render('error.json.twig', ['error'=>'IP address '.$deviceIP.' already belongs to '.$dupeCheck.'. Talk to Timothy if that\'s wrong.']);
        }
    }
    
    /**
     * @Route("/waffle/task", name="WaffleTaskMark")
     */
    public function markTask(Request $request) {
        $action = $request->request->get('action');
        $taskId = $request->request->get('id');
        
        if (!$action) {
            return $this->render('error.json.twig', ['error'=>'No action given.']);
        }
        if (!$taskId) {
            return $this->render('error.json.twig', ['error'=>'No task ID specified.']);
        }
        $TaskDef = $this->em->getRepository(TaskDefinition::class)->find($taskId);
        if (!$TaskDef) {
            return $this->render('error.json.twig', ['error'=>'Invalid task ID specified.']);
        }
        
        if ($action == 'did') {
            $extra = $request->request->get('extra');
            $date = $request->request->get('doneOn');
            $TaskDone = new TaskDone();
            $TaskDone->setDefinition($TaskDef);
            $TaskDone->setDoneOn(new \DateTime($date));
            
            $doneBy = $this->whichWaffle($request->getClientIp());
            
            $TaskDone->setDoneBy($doneBy);
            if ($extra) {
                $TaskDone->setExtra($extra);
            } else {
                $TaskDone->setExtra(null);
            }
            
            $this->em->persist($TaskDone);
            if ($TaskDef->getInNeed()) {
                $TaskDef->setInNeed(false);
            }
            $this->em->flush();
            
            return $this->render('taskSuccess.json.twig', ['taskDef'=>$TaskDef, 'taskDone'=>$TaskDone]);
        } else if ($action == 'inNeed') {
            if ($TaskDef->getInNeed()) {
                $TaskDef->setInNeed(false);
            } else {
                $TaskDef->setInNeed(true);
            }
            $this->em->flush();
            
            return $this->render('needSuccess.json.twig', ['taskDef'=>$TaskDef]);
        }
    }
    
    /**
     * @Route("/waffle/api/tasks", name="APIGetTasks")
     */
    public function apiTasks(Request $request) {
        $allTasks = $this->em->getRepository(TaskDefinition::class)->findAll();
        
        foreach ($allTasks as $TaskDef) {
            $now = new \DateTime();
            
            if (count($TaskDef->getInstances()) > 0) {
                $lastDone = $TaskDef->getInstances()[0];
            } else {
                $lastDone = false;
            }
    
            if ($TaskDef->getRedDays() != null) {
                $redDays = intval($TaskDef->getRedDays());
                $redTime = $now->sub(new \DateInterval('P'.$redDays.'D'));
                if ($lastDone && $lastDone->getDoneOn() < $redTime) {
                    $TaskDef->setRed(true);
                }
            }
        }
        
        return $this->render('waffle/apiTasks.json.twig', ['tasks'=>$allTasks]);
    }
}
