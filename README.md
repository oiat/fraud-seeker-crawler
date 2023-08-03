# fraud-seeker-crawler

## About / Synopsis
Manages the extraction of search engine results for special snippets/phrases. These phrases have been found to be used by certain clusters of fake shops.
By monitoring the search engines on a continuous basis new fake shops from the same make can be detected as soon as they can be found by consumers in search engines.

## Requirements
-	Mysql / Maria DB
-	PHP > 8.0

## Parameters
-	Config.php

## Needs 
-	database credentials
-	API credentials

## Database Tables
The source and destination tables can be generated with the sqls.sql script in a mysql-Database. WI_KEYWORDS needs to be populated with appropriate snippets to search for.
WI_KEYWORDS The table for keywords/phrases/snippets used to monitor/crawl the search engines.
TABLE_WI_SEARCH_ENGINE_RESULT The table for search engine results for the phrases given.
TABLE_WI_FINDINGS The table for new domain findings.
## Usage
```
php PhraseFinder.php crawl <number of phrases> <number of result pages> <search engine>
```
     
This call crawls the results with a number of phrases monitoring a range of result pages. As a standard Google is used as search engine. Other search engines could be implemented.
    
```
php PhraseFinder.php store <start date (YYYY-MM-DD)> <end date (YYYY-MM-DD)> <search engine>
```
This call analyzes the results of the crawl with a certain date range and stores new domains in the WI_FINDINGS

## Extensions
Later modules can classify (automatic classification with mal2 classificator) or visualize the results for expert classification setting the type of the result.
The abstract class WiSearchEngine.class can have additional subclasses to monitor / crawl other sources with the given phrases / snippets.

## Example call
```
php PhraseFinder.php crawl 5 2 google
```
Crawls google results with five keywords and up to 20 results
This can be performed multiple times as a random selection from the available keywords / snippets is used.

After some calls the data can be aggregated in the findings table
```
php PhraseFinder.php store 2023-09-01 2023-09-02 google 
```

Results are then available in the WiFindings Table for classification by further modules or expert classification and storing the result in the type variable.

## Licence
This software is available under the EUPL 1.2 open source license
https://joinup.ec.europa.eu/collection/eupl/solution/eupl-freeopen-source-software-licence-european-union/releases
