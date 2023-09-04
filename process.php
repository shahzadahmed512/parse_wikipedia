<?php
session_start();
$_SESSION['validate'] = [];
require_once ('jpgraph/jpgraph.php');
require_once ('jpgraph/jpgraph_line.php');


class ParseWikipediaData {
    private $inputErrorMessage = "";
    private $pageName = "";
    private $inputUrl = "";
    function __construct($inputUrl) {
        $this->inputUrl = $inputUrl;
    }

    private function preventXSSAttack() {
        $data = trim($this->inputUrl);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    private function getPageNameFromUrl(){
        $urlComponents = parse_url($this->inputUrl);
        if(isset($urlComponents['path']) && !empty($urlComponents['path']) && $urlComponents['path']!='/')  {
            $urlPath = $urlComponents['path'];
            $urlPathArray = explode("/", $urlPath);
            if(isset($urlPathArray) && !empty($urlPathArray)) {
                if((isset($urlPathArray[1]) && $urlPathArray[1]!== 'wiki') || ( !isset($urlPathArray[1])) || ( !isset($urlPathArray[2])) || empty($urlPathArray[2]) ) {
                    $this->inputErrorMessage = "Please enter a valid wikipedia page name";        
                } else {
                    $urlDecode =  urldecode($urlPathArray[2]);
                    $this->pageName = preg_replace("/_+/"," ", $urlDecode);
                    
                }
            }

        } else {
            $this->inputErrorMessage = "Url should contain a wikipedia 'Page Name'";
        }
        
    }

    public function validatUserInput() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (empty($this->inputUrl)) {
                $this->inputErrorMessage = "Page Name is required";
              } else {
                $this->preventXSSAttack();
                if (filter_var($this->inputUrl, FILTER_VALIDATE_URL) === FALSE) {
                    $this->inputErrorMessage = "Please enter a valid url";
                } 
                $this->getPageNameFromUrl();
              }
          }
          return $this->inputErrorMessage;
    }

    

    public function getWikiPediaAPiTableData() {

        $endPoint = "https://en.wikipedia.org/w/api.php";
        $params = [
            "action" => "parse",
            "page" => $this->pageName,
            "format" => "json",
            "prop" => "text",
        ];
        $url = $endPoint . "?" . http_build_query( $params );
        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        $output = curl_exec( $ch );
        curl_close( $ch );

        $result = json_decode( $output, true );
        $data  = $result["parse"]["text"]["*"];
        preg_match('/<table class="wikitable sortable">(.*?)<\/table>/s', $data, $match);
        $getTableDataArray  = $match[0];
        return $getTableDataArray;
    } 

    public function getTableNumericColumn($getTableDataArray) {
        $DOM = new DOMDocument; 
        $DOM->loadHTML($getTableDataArray); 
        $items = $DOM->getElementsByTagName('tr'); 
        $tableHeaders = [];
        $firstNumericColumnFound = false;
        $firstNumericColumnKey = -1; 
        foreach ($items as $node) { 
            $count = 0;
            foreach ($node->childNodes as  $element) { 
 
                if($firstNumericColumnFound && $count > $firstNumericColumnKey) {
                    break;
                }
                
                if($element->nodeName == 'th' || $element->nodeName == 'td' ) {
                    $count++;    
                    if($element->nodeName == 'td') {
                        if(is_numeric($element->nodeValue[0]) || strtotime($element->nodeValue)){
                            $numericColumn[0][] = floatval($element->nodeValue);
                            $numericColumn[1] = $tableHeaders[$count-1];   
                            $firstNumericColumnFound = true;
                            $firstNumericColumnKey = $count; 
                        } 

                    } else {
                        $tableHeaders[] = $element->nodeValue;
                    }
                } 
            } 
        } 
        return $numericColumn;
    }   

    public function drawImageChart($numericColumnData) {
        $ydata = $numericColumnData[0];
        // Create the graph. These two calls are always required
        $graph = new Graph(350,250);
        $graph->SetScale('textlin');

        // Create the linear plot
        $lineplot=new LinePlot($ydata);
        $lineplot->SetColor('blue');

        // Add the plot to the graph
        $graph->Add($lineplot);

        // plots the numbers on an image as a chart
        $graph->Stroke();
        $this->saveFile($numericColumnData);
    }

    protected function saveFile($numericColumnData) {
        // save numeric column data in to a file
        $results = fopen("wikepedia_numeric_column_file.txt", "w") or die("Unable to open file!");
        fwrite($results, $numericColumnData[1]); 
        $output = "";
        foreach ($numericColumnData[0] as $columnValue) {
            $output .= $columnValue. " \r\n";
        }
        // Write output
        fwrite($results, $output); 
        // Close file
        fclose($results);
    }
}
$parseWikiPediaData = new ParseWikipediaData($_POST["name"]);
$inputErrorMessage = $parseWikiPediaData->validatUserInput();
if ( (isset($inputErrorMessage)) && !empty($inputErrorMessage)) {
    $_SESSION['validate']['name'] = $inputErrorMessage; 
    header("location:index.php"); exit;
}

$tableArray = $parseWikiPediaData->getWikiPediaAPiTableData();

$numericColumnArray = $parseWikiPediaData->getTableNumericColumn($tableArray);
$parseWikiPediaData->drawImageChart($numericColumnArray);

//print_r($numericColumnArray); exit;
exit;
    $urlDecode =  urldecode($_POST["name"]);
    $finalUrl = preg_replace("/_+/"," ", $urlDecode);

/*
    parse.php

    MediaWiki API Demos
    Demo of `Parse` module: Parse content of a page

    MIT License
*/

$endPoint = "https://en.wikipedia.org/w/api.php";
$params = [
    "action" => "parse",
    "page" => $finalUrl,
    //"page" => "Women's high jump world record progression",
    //"page" => "Women%27s_high_jump_world_record_progression",
    "format" => "json",
    "prop" => "text",
];

$url = $endPoint . "?" . http_build_query( $params );

$ch = curl_init( $url );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
$output = curl_exec( $ch );
curl_close( $ch );

$result = json_decode( $output, true );
$data  = $result["parse"]["text"]["*"];
preg_match('/<table class="wikitable sortable">(.*?)<\/table>/s', $data, $match);
$match  = $match[0];

function getdata($match) 
{ 
    
    $DOM = new DOMDocument; 
    $DOM->loadHTML($match); 
    $items = $DOM->getElementsByTagName('tr'); 
    $tableHeaders = [];
    $numericColumn = [];
    $firstNumericColumnFound = false;
    $firstNumericColumnKey = -1; 
    foreach ($items as $node) { 

        $count = 0;
        $str = ""; 
       
        foreach ($node->childNodes as  $element) { 
         if($firstNumericColumnFound && $count > $firstNumericColumnKey) {
              $numericColumn['file_data'] = $str;
              break;
            }
            if($element->nodeName == 'th' || $element->nodeName == 'td' ) {
              $str .= $element->nodeValue. ", ";
                $count++;    
            
                if($element->nodeName == 'td') {

                    if(is_numeric($element->nodeValue[0]) || strtotime($element->nodeValue)){
                            $numericColumn[0][] = floatval($element->nodeValue);
                            $numericColumn[1] = $tableHeaders[$count-1];   
                            $firstNumericColumnFound = true;
                            $firstNumericColumnKey = $count; 
                    } 
    
                } else {
                    $tableHeaders[] = $element->nodeValue;
                }
            } 
        } 
    } 
    return $numericColumn;
    
} 
$numericColumnData  = getdata($match);
$ydata = $numericColumnData[0];
// Create the graph. These two calls are always required
$graph = new Graph(350,250);
$graph->SetScale('textlin');

// Create the linear plot
$lineplot=new LinePlot($ydata);
$lineplot->SetColor('blue');

// Add the plot to the graph
$graph->Add($lineplot);

// plots the numbers on an image as a chart
$graph->Stroke();

$results = fopen("results.txt", "w") or die("Unable to open file!");
//print_r($numericColumnData[2]); exit;
fwrite($results, $numericColumnData[1]); 
// Create output string to save multiple writes
$output = "";
foreach ($numericColumnData[0] as $columnValue) {
    $output .= $columnValue. " \r\n";  // Add letter followed by a space
}
// Write output
fwrite($results, $output); 
// Close file
fclose($results);