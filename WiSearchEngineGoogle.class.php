<?php
    require_once("WiSearchEngine.class.php");

    /**
     * WiSearchEngineGoogle - implements abstract class WiSearchEngine
     * 
     * This class utilizes the Google API to perform search queries and extract corresponding search results. Inherits base implementations from WiSearchEngine
     */
    class WiSearchEngineGoogle extends WiSearchEngine 
    {
        /**
         * Constant representing the search engine from which the API reads data.
         *
         * @var string SEARCH_ENGINE The name of the search engine ("google" in this case).
         */
        private const SEARCH_ENGINE = "google";

        /**
         * Constant representing the key to access the "displayLink" attribute in the API response.
         *
         * @var string DISPLAY_LINK The key for accessing the display link of a search result.
         */
        private const DISPLAY_LINK = "displayLink";

        /**
         * Constant representing the key to access the "link" attribute in the API response.
         *
         * @var string URL The key for accessing the URL of a search result.
         */
        private const URL = "link";

        /**
         * Constant representing the key to access the "title" attribute in the API response.
         *
         * @var string TITLE The key for accessing the title of a search result.
         */
        private const TITLE = "title";

        /**
         * Constant representing the key to access the "items" in the API response.
         *
         * @var string ITEMS The key for accessing the items of the search results.
         */
        private const ITEMS = "items";

        /**
         * Constant representing the parameter name for specifying the starting index in the Google Search API request.
         * start is not a page number but a result number
         *
         * @var string START The key for setting the starting index of search results.
         */
        private const START = "&start=";

        /**
         * Constant representing the maximum number of search results to be retrieved per API request.
         * Google only supports a maximum of 10
         *
         * @var int MAXLIMIT The maximum limit of search results to retrieve.
         */
        private const MAXLIMIT = 10;

        /**
         * Constant representing the complete parameter for specifying the maximum number of search results to be retrieved in the Google Search API request.
         * It is derived by concatenating the "&num=" prefix with the value of the MAXLIMIT constant.
         *
         * @var string NUM The key for setting the maximum number of search results.
         */
        private const NUM = "&num=" . self::MAXLIMIT;

        /**
         * Constant representing the parameter name for specifying the search query in the Google Search API request.
         *
         * @var string Q The key for setting the search query.
         */
        private const Q = "&q=";

        /**
         * Constructor of the WiSearchEngineGoogle class.
         * It calls the constructor of the parent class and passes the name of the search engine being used.
         */
        function __construct()
        {
            // Call the constructor of the parent class (WiSearchEngine).
            // The constants GOOGLE_SEARCH_ENGINE_ID, GOOGLE_API_KEY, and GOOGLE_API_CALL are declared in the config file
            // and are used for the API call to the Google API.
            parent::__construct(self::SEARCH_ENGINE, GOOGLE_SEARCH_ENGINE_ID, GOOGLE_API_KEY, GOOGLE_API_CALL);
        }
        
        /**
         * Search for a query in the Google API for search results.
         *
         * The "search" function calls the Google API to retrieve search results for a specific query.
         * The API request is determined by the "$query" parameter, which contains the query to be searched for.
         *
         * @param string $query The query to be searched for, used as a parameter for the Google API.
         * 
         * @return string The API response as a string.
         */
        function search($query) 
        {
            // Construct the API URL
            $url = $this->api_call . ($query);

            // Create a stream context array with HTTP settings for the retrieval
            $context = stream_context_create([
                'http' => [
                    'ignore_errors' => true, // Ignore HTTP errors
                    'timeout' => 10, // Timeout for the API response in seconds
                ]
            ]);

            // Send the GET request to the API and store the API response in $response
            $response = @file_get_contents($url, false, $context);            

            // Check if an error occurred while retrieving the API response
            if ($response === false) 
            {
                print("Error: Failure while retrieving API data.\n");
                exit(1);
            }

            // Parse the HTTP status from the response header to validate the API response
            $httpStatus = explode(' ', $http_response_header[0]);
            if ($httpStatus[1] !== '200') 
            {   
                if ($httpStatus[1] == "429")
                {
                    print("Error: API limit reached.\n");
                }
                else
                {
                    print("Error: False API response " . $httpStatus[1] . ".\n");
                }
                exit(1);
            }

            // Return the API response as the result
            return $response;
        }

        /**
         * Stores the search engine results into the database.
         * To obtain the search engine results for the given keyword, the "search" function is called.
         *
         * @param object $selected_keyword An entry of the keyword retrieved from the database.
         * @param int $pages The number of pages to be read. Each page contains 10 search results.
         * 
         * @return int $count The number of items that were inserted into the database.
         */
        function crawl_api($selected_keyword, $pages) 
        {
            // Extract the keyword and keyword ID from the provided object
            $keyword = $selected_keyword->keyword;
            $keywordid = $selected_keyword->keywordid;

            // Counter variable for the inserted items
            $count = 0;

            // Loop to iterate through the specified number of Google pages
            for ($i = 0; $i < $pages; $i++) 
            {
                // Create the query for the next Google page based on the current page number
                $query = self::START . (1 + (self::MAXLIMIT * $i)) . self::NUM . self::Q . urlencode($keyword);

                // Retrieve search results for the given query and decode as an associative array
                $result = $this->search($query);
                $result_decoded = json_decode($result, true);
				
                $items = $result_decoded[self::ITEMS];

                // Iterate through each search result and store relevant information into the database
                foreach ($items as $item) 
                {
                    $domain = $item[self::DISPLAY_LINK];
                    $url = $item[self::URL];
                    $title = $item[self::TITLE];
                    $search_engine = $this->search_engine;

                    // Store the search result into the database
                    $numRows = $this->store_item($domain, $url, $keyword, $keywordid, $title, $search_engine);
                    
                    // Check if the insertion was successful
                    // Use an if statement to prevent $count from being increased by +2 in case of an update
                    if ($numRows > 0) 
                    {
                        $count += 1;
                    }
                }

                // Check if the key 'nextPage' does not exist in the 'queries' array of $result_decoded
                if (!array_key_exists('nextPage', $result_decoded["queries"])) 
                {
                    // If the key 'nextPage' does not exist, the maximum number of pages has been reached.
                    print("Maximum of pages reached.\n");

                    // Exit the loop, as there are no nextPage.
                    break;
                }
            }

            // Return the number of the inserted items.
            return $count;
        }

    }

?>
