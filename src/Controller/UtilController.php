<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

class UtilController extends AbstractController {
	
	protected $outfitFile = array();
	protected $outfitHeaders = array();
	protected $dataDir = '';
	
	public function __construct() {
		$this->dataDir = $_ENV['DATA_PATH'].'data';
	}
	
	#[Route("/util/endlessOutfits", name: "EndlessOutfits")]
	public function endlessOutfits(Request $request): Response {
		
		error_log('**** Beginning outfit scan');
		
		$output = $this->processDataDir($this->dataDir);
		
		$outStr = implode("\n", $output);
		
		$outfitCSV = '';
		foreach ($this->outfitHeaders as $header) {
			if ($outfitCSV != '') {
				$outfitCSV .= ',';
			}
			if (str_contains($header, '"')) {
				$outfitCSV .= '`'.$header.'`';
			} else if (str_contains($header, ' ')) {
				$outfitCSV .= '"'.$header.'"';
			} else {
				$outfitCSV .= $header;
			}
		}
		$outfitCSV .= "\n";
		$headerCount = count($this->outfitHeaders);
		foreach ($this->outfitFile as $outfit) {
			$outArray = array_fill(0, $headerCount, '');
			
			foreach ($outfit as $key => $val) {
				$keyIndex = array_search($key, $this->outfitHeaders);
				if ($keyIndex === false) {
					error_log('Missing header ['.$key.']!');
					continue;
				}
				if (count($val) == 0) {
					$val = 1;
				} else if (count($val) > 1) {
					$outVal = implode(', ', $val);
				} else if (!isset($val[0])) {
					error_log('--- Error outputting value ['.print_r($val, true).']');
				} else {
					$outVal = $val[0];
				}
				$outArray[$keyIndex] = $outVal;
			}
			for ($i=0; $i<$headerCount; $i++) {
				if ($i > 0) {
					$outfitCSV .= ',';
				}
				if (str_contains($outArray[$i], '"')) {
					$outfitCSV .= '`'.$outArray[$i].'`';
				} else if (str_contains($outArray[$i], ' ')) {
					$outfitCSV .= '"'.$outArray[$i].'"';
				} else {
					$outfitCSV .= $outArray[$i];
				}
			}
			$outfitCSV .= "\n";
		}
		
		return new Response($outfitCSV);
	}
	
	function processDataDir($curDataDir) {
		error_log('Processing data files in dir ['.$curDataDir.']');
		$dirFiles = scandir($curDataDir);
		error_log(' - Got files: ['.implode(', ', $dirFiles).']');
		$output = array();
		foreach ($dirFiles as $filename) {
			$fullPath = $curDataDir.'/'.$filename;
			if (substr($filename, 0, 1) == '.') {
				error_log('Skipping dotdir (['.$filename.'])');
				continue;
			}
			if (is_dir($fullPath)) {
				$output = array_merge($output, $this->processDataDir($fullPath));
			}
			if (substr($fullPath, -4) == '.txt') {
				error_log(' - Processing data file '.$fullPath);
				$output []= $this->processDataFile($fullPath);
			}
		}
		return $output;
	}
	
	function processDataFile($fullPath) {
		$data = file_get_contents($fullPath);
		$lines = explode("\n", $data);
		$inOutfit = false;
		$thisOutfit = array();
		$outfits = 0;
		foreach ($lines as $line) {
			error_log(' - - line ['.$line.']');
			if (substr($line, 0, 7) == 'outfit ') {
				$inOutfit = true;
				$this->outfitFile []= $thisOutfit;
				$thisOutfit = array();
				$outfits++;
			} else if ($inOutfit && trim($line) == '') {
				$inOutfit = false;
			}
			if ($inOutfit) {
				$tokens = $this->tokenize($line);
				$tokenInfo = ' - - tokens [';
				foreach ($tokens as $tKey => $tVal) {
					$tokenInfo .= $tKey.' => '.$tVal.', ';
				}
				$tokenInfo .= ']';
				error_log($tokenInfo);
				if ($tokens[0] == 'outfit') {
					$tokens[0] = 'name';
				}
				if (!in_array($tokens[0], $this->outfitHeaders)) {
					$this->outfitHeaders []= $tokens[0];
				}
				if (!isset($thisOutfit[$tokens[0]])) {
					$thisOutfit[$tokens[0]] = array();
				}
				for ($i=1; $i<count($tokens); $i++) {
					$thisOutfit[$tokens[0]] []= $tokens[$i];
				}
			}
		}
		
		return "Processed ".$outfits." outfits.";
	}
	
	function tokenize($line) {
		$tokens = array();
		$curTokenId = -1;
		$indexInToken = 0;
		$curDelimiter = null;
		$finishedToken = true;
		$validDelimiters = ['`','"'];
		$whitespace = [' ','	', "\n"];
		for ($i=0; $i<strlen($line); $i++) {
			if (!$curDelimiter) {
				//error_log(' - - + No delimiter');
				if (in_array($line[$i],$validDelimiters)) {
					//error_log(' - - + Found delimiter ['.$line[$i].']');
					$curDelimiter = $line[$i];
					$curTokenId++;
					$tokens[$curTokenId] = '';
					$finishedToken = false;
					$indexInToken = 0;
					continue;
				}
			} else {
				if (in_array($line[$i], $validDelimiters)) {
					$finishedToken = true;
					$curDelimiter = null;
					continue;
				}
			}
			if (!$curDelimiter && in_array($line[$i], $whitespace)) {
				if (!$finishedToken) {
					$finishedToken = true;
				}
				continue;
			} else if ($finishedToken && !in_array($line[$i], $whitespace)) {
				$finishedToken = false;
				$curTokenId++;
				$tokens[$curTokenId] = '';
				$indexInToken = 0;
			}
			$tokens[$curTokenId] .= $line[$i];
		}
		return $tokens;
	}
	
}