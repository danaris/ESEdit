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
	
	private $catMap = ['Pills and supplements'=>'Health','Meal kits'=>'Food', 'Groceries'=>'Food','Therapy'=>'Health','Doctors'=>'Health','Subscriptions'=>'Entertainment / Travel','Utilities'=>'Utilities','Gas'=>'House and car','Servers'=>'Entertainment / Travel','Income'=>'Income','Transfer'=>'Income','Mortgage'=>'House and car'];
	
	private $amazonCategories = ['Pills and supplements'=>['Potassium Citrate','Magnesium Citrate','Flower Remedies']];
	
	private $chaseCategories = ['Meal kits'=>['GREEN CHEF','BLUEAPRON','HOME CHEF'],'Groceries'=>['PRICE CHOPPER','WEGMANS','HANNAFORD','GRANOLA FACTORY'],'Therapy'=>['JENNIFER BARTKOWIAK'],'Doctors'=>['JJR RESEARCH LLC'],'Utilities'=>['VZWRLSS','SPECTRUM'],'Subscriptions'=>['NETFLIX.COM','Spotify USA','ERFWORLD','SQUARE ENIX'],'Gas'=>['SUNOCO','SPEEDWAY'],'Servers'=>['DIGITALOCEAN.COM']];
	
	private $nbtCategories = ['Income'=>['COLGATE UNIVERS PAYROLL','Patreon'],'Transfer'=>['ZELLE'],'Utilities'=>['Village of Hamil'],'Mortgage'=>['005800132497']];
	
	private $toRemove = ['CREDIT CRD EPAY','Payment Thank You'];

	/**
	 * @Route("/util/accountingHelper", name="AccountingHelper")
	 */
	public function accountingHelper(Request $request): Response {
		$data = array();
		
		$accountingForm = $this->createFormBuilder(array())
							->add('TAmazon', FileType::class, ['label'=>'T Amazon Export: ', 'required'=>true, 'mapped'=>false])
							->add('CAmazon', FileType::class, ['label'=>'C Amazon Export: ', 'required'=>true, 'mapped'=>false])
							->add('Chase', FileType::class, ['label'=>'Chase Export: ', 'required'=>true, 'mapped'=>false])
							->add('NBT', FileType::class, ['label'=>'NBT Export: ', 'required'=>true, 'mapped'=>false])
							->add('Process', SubmitType::class, ['label'=>'Process'])
							->getForm();
		
		$accountingForm->handleRequest($request);
		
		if ($accountingForm->isSubmitted() && $accountingForm->isValid()) {
			$outputData = [['Item','Subcategory','Category','Date','Amount','Notes']];
			$deserializer = new Serializer([new ObjectNormalizer(), new ArrayDenormalizer()], [new CsvEncoder()]);

			$tAmazonFile = $accountingForm->get('TAmazon')->getData();
			$tAmazonData = file_get_contents($tAmazonFile->getRealPath());
			$tAmazonData = str_replace(['Order Date','Item Total'], ['OrderDate','ItemTotal'], $tAmazonData);
			
			$tAmazon = $deserializer->deserialize($tAmazonData, AmazonFile::class.'[]', 'csv');
			foreach ($tAmazon as $aData) {
				$outputLine = [$aData->Title, $aData->Category, '', $aData->OrderDate, $aData->ItemTotal];
				foreach ($this->toRemove as $removeStr) {
					if (str_contains($outputLine[0], $removeStr)) {
						continue 2;
					}
				}
				$outputLine = $this->preprocess($outputLine, $this->amazonCategories);
				$outputData []= $outputLine;
			}
			$cAmazonFile = $accountingForm->get('CAmazon')->getData();
			$cAmazonData = file_get_contents($cAmazonFile->getRealPath());
			$cAmazonData = str_replace(['Order Date','Item Total'], ['OrderDate','ItemTotal'], $cAmazonData);
			
			$cAmazon = $deserializer->deserialize($cAmazonData, AmazonFile::class.'[]', 'csv');
			foreach ($cAmazon as $aData) {
				$outputLine = [$aData->Title, $aData->Category, '', $aData->OrderDate, $aData->ItemTotal];
				foreach ($this->toRemove as $removeStr) {
					if (str_contains($outputLine[0], $removeStr)) {
						continue 2;
					}
				}
				$outputLine = $this->preprocess($outputLine, $this->amazonCategories);
				$outputData []= $outputLine;
			}
			
			$chaseFile = $accountingForm->get('Chase')->getData();
			$chaseData = file_get_contents($chaseFile->getRealPath());
			$chaseData = str_replace(['Transaction Date'], ['TransactionDate'], $chaseData);
			
			$chase = $deserializer->deserialize($chaseData, ChaseFile::class.'[]', 'csv');
			foreach ($chase as $cData) {
				$outputLine = [$cData->Description, $cData->Category, '', $cData->TransactionDate, -1 * $cData->Amount];
				foreach ($this->toRemove as $removeStr) {
					if (str_contains($outputLine[0], $removeStr)) {
						continue 2;
					}
				}
				$outputLine = $this->preprocess($outputLine, $this->chaseCategories);
				$outputData []= $outputLine;
			}
			
			$nbtFile = $accountingForm->get('NBT')->getData();
			$nbtData = file_get_contents($nbtFile->getRealPath());
			$nbtData = str_replace(['Check Number'], ['CheckNumber'], $nbtData);
			
			$nbt = $deserializer->deserialize($nbtData, NBTFile::class.'[]', 'csv');
			foreach ($nbt as $nData) {
				$negative = false;
				if (str_contains($nData->Amount,'-')) {
					$negative = true;
				}
				$amount = str_replace(['$','-',','],['',''],$nData->Amount);
				if ($negative) {
					$amount *= -1;
				}
				if ($nData->Description == "CHECK") {
					$nData->Description .= ' '.(1*$nData->CheckNumber);
				}
				$outputLine = [$nData->Description, $nData->Comments, '', $nData->Date, $amount];
				foreach ($this->toRemove as $removeStr) {
					if (str_contains($outputLine[0], $removeStr)) {
						continue 2;
					}
				}
				$outputLine = $this->preprocess($outputLine, $this->nbtCategories);
				$outputData []= $outputLine;
			}
			
			$data['outputData'] = $outputData;
			$outputFileCSV = $deserializer->serialize($outputData, 'csv');
			$now = new \DateTime();
			$response = new Response($outputFileCSV);
			$disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, 'WaffleFinances'.$now->format('Ymd').'.csv');
			$response->headers->set('Content-Disposition',$disposition);
			$response->headers->set('Content-Type','text/csv');
			
			return $response;
		}
		
		$data['accountingForm'] = $accountingForm->createView();
		
		$response = $this->render('util/accounting.html.twig', $data);
		
		return $response;
	} 
	
	private function preprocess($line, $categories) {
		$item = $line[0];
		foreach ($categories as $subcat => $fragments) {
			foreach ($fragments as $fragment) {
				//echo "Checking [$item] for [$fragment]";
				if (str_contains($item, $fragment)) {
					//echo " - found<br>";
					$cat = $this->catMap[$subcat];
					$line[1] = $subcat;
					$line[2] = $cat;
					return $line;
				} else {
					//echo "<br>";
				}
			}
		}
		
		return $line;
	}
	
}

class AmazonFile {
	public $OrderDate;
	public $OrderID;
	public $Title;
	public $Category;
	public $ASIN;
	public $UNSPSCCode;
	public $Website;
	public $ReleaseDate;
	public $Condition;
	public $Seller;
	public $SellerCredentials;
	public $ListPricePerUnit;
	public $PurchasePricePerUnit;
	public $Quantity;
	public $PaymentInstrumentType;
	public $PurchaseOrderNumber;
	public $POLineNumber;
	public $OrderingCustomerEmail;
	public $ShipmentDate;
	public $ShippingAddressName;
	public $ShippingAddressStreet1;
	public $ShippingAddressStreet2;
	public $ShippingAddressCity;
	public $ShippingAddressState;
	public $ShippingAddressZip;
	public $OrderStatus;
	public $CarrierInfo;
	public $ItemSubtotal;
	public $ItemSubtotalTax;
	public $ItemTotal;
	public $TaxExemptionApplied;
	public $TaxExemptionType;
	public $ExemptionOptOut;
	public $BuyerName;
	public $Currency;
	public $GroupName;
	
	public function __toString() {
		return 'OrderDate: "'.$this->OrderDate.'" OrderID: "'.$this->OrderID.'" Title: "'.$this->Title.'" Category: "'.$this->Category.'" ASIN: "'.$this->ASIN.'" UNSPSCCode: "'.$this->UNSPSCCode.'" Website: "'.$this->Website.'" ReleaseDate: "'.$this->ReleaseDate.'" Condition: "'.$this->Condition.'" Seller: "'.$this->Seller.'" SellerCredentials: "'.$this->SellerCredentials.'" ListPricePerUnit: "'.$this->ListPricePerUnit.'" PurchasePricePerUnit: "'.$this->PurchasePricePerUnit.'" Quantity: "'.$this->Quantity.'" PaymentInstrumentType: "'.$this->PaymentInstrumentType.'" PurchaseOrderNumber: "'.$this->PurchaseOrderNumber.'" POLineNumber: "'.$this->POLineNumber.'" OrderingCustomerEmail: "'.$this->OrderingCustomerEmail.'" ShipmentDate: "'.$this->ShipmentDate.'" ShippingAddressName: "'.$this->ShippingAddressName.'" ShippingAddressStreet1: "'.$this->ShippingAddressStreet1.'" ShippingAddressStreet2: "'.$this->ShippingAddressStreet2.'" ShippingAddressCity: "'.$this->ShippingAddressCity.'" ShippingAddressState: "'.$this->ShippingAddressState.'" ShippingAddressZip: "'.$this->ShippingAddressZip.'" OrderStatus: "'.$this->OrderStatus.'" CarrierInfo: "'.$this->CarrierInfo.'" ItemSubtotal: "'.$this->ItemSubtotal.'" ItemSubtotalTax: "'.$this->ItemSubtotalTax.'" ItemTotal: "'.$this->ItemTotal.'" TaxExemptionApplied: "'.$this->TaxExemptionApplied.'" TaxExemptionType: "'.$this->TaxExemptionType.'" ExemptionOptOut: "'.$this->ExemptionOptOut.'" BuyerName: "'.$this->BuyerName.'" Currency: "'.$this->Currency.'" GroupName: "'.$this->GroupName.'"';
	}
}

class ChaseFile {
	public $TransactionDate;
	public $PostDate;
	public $Description;
	public $Category;
	public $Type;
	public $Amount;
	public $Memo;

}

class NBTFile {
	public $Date;
	public $Description;
	public $Comments;
	public $CheckNumber;
	public $Amount;
	public $Balance;
}

class OutputFile {
	public $Item;
	public $Subcategory;
	public $Category;
	public $Date;
	public $Amount;
	public $Notes;
}