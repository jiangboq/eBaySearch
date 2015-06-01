/*
	This PHP script aims at extracting information from the form of web page, 
	and construct the URL of ebay API. The return data from ebay.com is XML format. 
	Finally, it transforms XML data to JSON format and returned to front-end.
*/
<?php
	$totalNumber = 0;
	$url = 'http://svcs.ebay.com/services/search/FindingService/v1';
	$responseEncoding = 'XML'; 
	$keyWords = urlencode($_GET["KeyWords"]);
	$sortBy = $_GET["sort"];
	$resultsPerPage = $_GET["result"];
	$pageNumber = $_GET["pageNumber"];

	/*construct URL for searching*/
	$call = "$url?siteid=0&SECURITY-APPNAME=jiangboq-1b4e-4b10-a880-00c4d689db81&OPERATION-NAME=findItemsAdvanced&SERVICE-VERSION=1.0.0&RESPONSE-DATA-FORMAT=XML"
						."&keywords=$keyWords"
						."&sortOrder= $sortBy"
						."&paginationInput.entriesPerPage=$resultsPerPage"
						."&outputSelector[0]=SellerInfo"
						."&outputSelector[1]=PictureURLSuperSize"
						."&outputSelector[2]=StoreInfo"
						."&paginationInput.pageNumber=$pageNumber";
	$index = 0;		
	if(!empty($_GET["low"])){
		$minPrice = (float)$_GET["low"];
		$call=$call."&itemFilter($index).name=MinPrice"
				."&itemFilter($index).value=$minPrice";
		$index++;
	}			
	if(!empty($_GET["high"])){	
		$maxPrice = (float)$_GET["high"];		
		$call=$call."&itemFilter($index).name=MaxPrice"
			."&itemFilter($index).value=$maxPrice";
		$index++;		   
	}
	if(!empty($_GET["condition"])){
		$condition = $_GET["condition"];
		$call=$call."&itemFilter($index).name=Condition";
		foreach($condition as $key=>$value){
			$call=$call."&itemFilter($index).value[$key]=$value";		   
		}	
		$index++;
	}
	if(!empty($_GET["formats"])){
		$buyingFormats = $_GET["formats"];
		$call=$call."&itemFilter($index).name=ListingType";
		foreach($buyingFormats as $key=>$value){
			$call=$call."&itemFilter($index).value[$key]=$value";
		}
		$index++;
	}
	if(!empty($_GET["seller"])){
		$call=$call."&itemFilter($index).name=ReturnsAcceptedOnly"
			."&itemFilter($index).value=true";
		$index++;
	}
	if(!empty($_GET["freeShipping"])){
		$call=$call."&itemFilter($index).name=FreeShippingOnly"
			."&itemFilter($index).value=true";
		$index++;	
	}	
	if(!empty($_GET["expedited"])){
		$call=$call."&itemFilter($index).name=ExpeditedShippingType"
			."&itemFilter($index).value=Expedited";
		$index++;
	}
	if(!empty($_GET["maxTime"])){
		$maxHandlingTime = (int)$_GET["maxTime"];		
		$call=$call."&itemFilter($index).name=MaxHandlingTime"
			."&itemFilter($index).value=$maxHandlingTime";
		$index++;
	}	
	$call.="&RESPONSE-DATA-FORMAT=$responseEncoding";
			
	$resp = simplexml_load_file($call);				
			
	$ack = $resp->ack;
	$totalNumber = $resp->paginationOutput->totalEntries;
	$pageNumber = $resp->paginationOutput->pageNumber;
	$itemCount = $resp->paginationOutput->entriesPerPage;
	if($totalNumber==0){
		echo "{\"ack\":\"No results found\"}";
	}
	else{	
		$i =0;
		$array = array();
		$array=array_merge($array, array("ack"=>"$ack"), array("resultCount"=>"$totalNumber"), array("pageNumber"=>"$pageNumber"), array("itemCount"=>"$itemCount"));
			
		foreach($resp->searchResult->item as $item){
			$title= $item->title;
			$itemURL = $item->viewItemURL;
			$galleryURL = $item->galleryURL;
			$URLSuperSize = $item->pictureURLSuperSize;
			$convertedCurrentPrice = $item->sellingStatus->convertedCurrentPrice;			
			$shippingServiceCost = $item->shippingInfo->shippingServiceCost;
			$conditionDisplayName = $item->condition->conditionDisplayName;
			$listingType = $item->listingInfo->listingType;
			$location = $item->location;
			$categoryName = $item->primaryCategory->categoryName;
			$topRatedListing = $item->topRatedListing;
			
			$sellerUserName = $item->sellerInfo->sellerUserName;
			$feedbackScore = $item->sellerInfo->feedbackScore;
			$positiveFeedbackPercent = $item->sellerInfo->positiveFeedbackPercent;
			$feedbackRatingStar = $item->sellerInfo->feedbackRatingStar;
			$topRatedSeller = $item->sellerInfo->topRatedSeller;
			$sellerStoreName = $item->storeInfo-> storeName;
			$sellerStoreURL = $item->storeInfo-> storeURL;
				
			$shippingType = $item->shippingInfo->shippingType;
			$shipToLocations = $item->shippingInfo->shipToLocations;
			$expeditedShipping = $item->shippingInfo->expeditedShipping;
			$oneDayShippingAvailable = $item->shippingInfo->oneDayShippingAvailable;
			$returnsAccepted = $item->returnsAccepted;
			$handlingTime = $item->shippingInfo->handlingTime;
				
			$basic = array("title"=>"$title","viewItemURL"=>"$itemURL","galleryURL"=>"$galleryURL","pictureURLSuperSize"=>"$URLSuperSize","convertedCurrentPrice"=>"$convertedCurrentPrice","shippingServiceCost"=>"$shippingServiceCost","conditionDisplayName"=>"$conditionDisplayName","listingType"=>"$listingType","location"=>"$location","categoryName"=>"$categoryName","topRatedListing"=>"$topRatedListing");
			$sellerInfo = array("sellerUserName"=>"$sellerUserName", "feedbackScore"=>"$feedbackScore", "positiveFeedbackPercent"=>"$positiveFeedbackPercent", "feedbackRatingStar"=>"$feedbackRatingStar","topRatedSeller"=>"$topRatedSeller","sellerStoreName"=>"$sellerStoreName","sellerStoreURL"=>"$sellerStoreURL");
			$shippingInfo = array("shippingType"=>"$shippingType", "shipToLocations"=>"$shipToLocations","expeditedShipping"=>"$expeditedShipping","oneDayShippingAvailable"=>"$oneDayShippingAvailable","returnsAccepted"=>"$returnsAccepted","handlingTime"=>"$handlingTime");
			$array = array_merge($array, array("item$i"=>array("basicInfo"=>$basic,"sellerInfo"=>$sellerInfo,"shippingInfo"=>$shippingInfo)));
			$i++;
		}
		/*transform data to JSON format, and return to front-end*/
		$json = json_encode($array);
		echo $json;
	}	
?>
