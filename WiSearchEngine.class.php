<?php
    require_once("WiDataStorage.class.php");

    /**
     * WiSearchEngine - abstract class
     * This class serves as a base for specific implementations of search engine results as API/Crawler.
     */
    abstract class WiSearchEngine
    {
        /**
         * WiDataStorage instance for database access.
         *
         * @var WiDataStorage
         */
        private WiDataStorage $db;

        /**
         * Instance variable to store the retrieved keywords.
         * 
         * @var array
         */
        private $keywords;

        /**
         * Instance variable for the search engine used for data retrieval.
         * This instance variable contains the name of the search engine used to retrieve the data.
         * 
         * @var string
         */
        protected $search_engine;

        /**
         * The ID of the search engine for the API call.
         *
         * @var string
         */
        protected $search_engine_id;

        /**
         * The API key for the API call.
         *
         * @var string
         */
        protected $api_key;

        /**
         * The complete API call for the search engine.
         *
         * This value is created by combining the search engine API command
         * with the variables $search_engine_id and $api_key.
         *
         * @var string
         */
        protected $api_call;  

        /**
         * Constructor of the WiSearchEngine class.
         * However, this class is abstract and cannot be directly instantiated.
         *
         * @param string|null $search_engine The search engine to be used by the derived classes.
         * @param string $search_engine_id The ID of the search engine for the API call.
         * @param string $api_key The API key for the API call.
         * @param string $api_call The complete API call for the search engine.
         */
        function __construct($search_engine, $search_engine_id, $api_key, $api_call)
        {
            // Initialize a new WiDataStorage instance for accessing the database.
            $this->db = new WiDataStorage();

            // Initialize an empty array for keywords.
            $this->keywords = array();

            // Set the specified search engine.
            $this->search_engine = $search_engine;

            // Set the specified search engine ID for the API call.
            $this->search_engine_id = $search_engine_id;

            // Set the API key for the API call.
            $this->api_key = $api_key;

            // Set the complete API call for the search engine.
            $this->api_call = $api_call;
        }

        /**
         * Stores the retrieved keywords from the database into the instance array.
         *
         * @param string $category The category of keywords to be retrieved.
         * @param string $language The language for which the keywords should be retrieved.
         * @param int $limit The maximum number of keywords to be retrieved from the database.
         * @param int $verbose (Optional) Controls whether verbose output is enabled or not.
         * 
         * @return int The number of retrieved keywords. The retrieved keywords themselves are stored in the private instance variable "$keywords".
         */
        function init($category, $language, $limit, $verbose=0)
        {
            // Retrieve keywords from the database based on the specified category, language, and limit.
            $this->keywords = $this->db->getKeywords($category, $language, $limit, $verbose);

            // Return the number of retrieved keywords, which is the size of the "$keywords" array.
            return sizeof($this->keywords);
        }

        /**
         * Selects a keyword from the list of keywords.
         * This function is used to choose and return a keyword from the array of keywords.
         *  The keyword is removed from the list
         *
         * @return object|null The selected keyword from the array of keywords. If the array of keywords is not empty, the selected keyword is returned. Otherwise, "null" is returned.
         */
        function selectKeyword() 
        {
            $size_keywords = sizeof($this->keywords);
            
            // Check if the list of keywords is not empty
            if ($size_keywords > 0) 
            {
                // Generate a random index within the range of available keywords
                $rand = mt_rand(0, $size_keywords - 1);
                
                // Retrieve the selected keyword from the list
                $value = $this->keywords[$rand];
                
                // Remove the selected keyword from the list to avoid duplicates
                array_splice($this->keywords, $rand, 1);
                
                return $value;
            }
            
            // If the list of keywords is empty and no keyword can be selected,
            // "null" is returned as the result.
            return null;
        }

        /**
         * Stores the search engine results into the database.
         * To obtain the search engine results for the given keyword, the "search" function is called.
         *
         * @param object $selected_keyword An entry of the keyword retrieved from the database.
         * @param int $pages The number of pages to be fetched. Each page contains 10 search results.
         * 
         * @return int $numRows The number of affected rows in the database.
         */
        function store_item($domain, $url, $keyword, $keywordid, $title, $search_engine) 
        {
            // Insert or update the search engine result into the "wi_search_engine_result" table in the database.
            $numRows = $this->db->insertIntoWiSearchEngineResult($domain, $url, $keyword, $keywordid, $title, "", 0, "", "", 0, $search_engine);

            // Return the number of affected rows in the database as the result of the function.
            return $numRows;
        }

        /**
         * Stores the search engine results into the database.
         * To obtain the search engine results for the given keyword, the "search" function is called.
         *
         * @param object $selected_keyword An entry of the keyword retrieved from the database.
         * @param int $pages The number of pages to be fetched. Each page contains 10 search results.
         * 
         * @return int $count The number of results inserted into the database.
         */   
        abstract function crawl_api($selected_keyword, $pages);

        /**
         * Searches for a query in the API (e.g. Google, Bing, Yandex, ...) for search results.
         * This abstract method serves as a template that must be overridden by the derived classes.
         *
         * @param string $query The query to be searched, which serves as a parameter for the Google API.
         * 
         * @return string|false The API response as a string or "false" if the search was not successful.
         */
        abstract function search($query);

    }


?>