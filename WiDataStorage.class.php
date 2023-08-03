<?php
    require_once("DbConfig.php");

    /**
     * Database access class
     *
     * This class manages database access and provides methods for interacting
     * with the database.
     */
    class WiDataStorage 
    {
        /**
         * Constants for table names
         *
         * These constants define the table names used in the database.
         *
         * @var string TABLE_WI_KEYWORDS The table for keywords/phrases/snippets used to monitor/crawl the search engines.
         * @var string TABLE_WI_SEARCH_ENGINE_RESULT The table for search engine results for the phrases given.
         * @var string TABLE_WI_FINDINGS The table for new domain findings.
         */
        private const TABLE_WI_KEYWORDS =             "WI_KEYWORDS";
        private const TABLE_WI_SEARCH_ENGINE_RESULT = "WI_SEARCH_ENGINE_RESULT";
        private const TABLE_WI_FINDINGS =             "WI_FINDINGS";

        /**
         * The connection resource to the database.
         */
        private $connection;

        /**
         * Class constructor.
         *
         * Initializes the database connection.
         */
        public function __construct() 
        {
            $this->connection = mup_PDO_connectSequential();									   

            if(mysqli_connect_error()) 
            {
                echo("Connection error: " . mysqli_connect_error());
                echo(DB_USER . " connection to " . DB_NAME);
            }
            else 
            {
                $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
            }
        }

        /**
         * Returns a verbose SQL statement with values
         *
         * The verbose_output function is used to generate an SQL statement with the corresponding values for execution in the database.
         * It is often used for troubleshooting and debugging SQL queries.
         *
         * @param string $sql The SQL statement with named parameters (:key) to be replaced with their values.
         * @param array $vars An associative array containing named parameters (:key) as keys and their values as values.
         *
         * @return string A verbose SQL statement with values.
         */
        function verbose_output($sql, $vars)
        {
            $output = $sql . " ";
            $char = [" ", ",", ")", "\n"];

            foreach ($vars as $key => $value) 
            {
                $before = $output;

                foreach ($char as $sEnd) 
                {
                    if (gettype($value) == "string") 
                    {
                        $output = str_replace(":" . $key . $sEnd, '"' . $value . '"' . $sEnd, $output);
                    } 
                    else 
                    {
                        $output = str_replace(":" . $key . $sEnd, $value . $sEnd, $output);
                    }
                }

                // Print a warning if the parameter was not replaced
                if ($before == $output) 
                {
                    print("Warning: The parameter " . $key . " was defined but not found by verbose_output. If no PDO errors occur, check for unusual characters or similar issues.\n");
                }
            }

            return ("SQL command: $output <br>");
        }

        /**
         * Database query to retrieve new search engine entries
         *
         * The getNewSearchEngineEntries function executes an SQL query on the table WI_SEARCH_ENGINE_RESULT
         * to retrieve new doains (not yet present in WI_FINDINGS) for a specific time period.
         *
         * @param string $startdate The start date of the time period for which search engine entries should be retrieved (in the "YYYY-MM-DD" format).
         * @param string $enddate The end date of the time period for which search engine entries should be retrieved (in the "YYYY-MM-DD" format).
         * @param string $search_engine The search engine for which entries should be retrieved. By default, $search_engine = "1" (all search engines).
         * @param int $verbose Specifies whether detailed output should be displayed during execution. By default, $verbose = 0 (no output).
         *
         * @return array A list of retrieved search engine entries as an array of objects.
         * 
         * @throws PDOException If there is a problem with the database connection or executing the SQL query.
         */
        function getNewSearchEngineEntries($startdate, $enddate, $search_engine="1", $verbose=0)
        {
            $vars["startdate"] = $startdate;
            $vars["enddate"] = $enddate;

            // Create the SQL query to retrieve the new search engine entries
            $sql = "SELECT DISTINCT domain, last_keywordid FROM " . self::TABLE_WI_SEARCH_ENGINE_RESULT . " WHERE (inserted >= :startdate AND inserted <= :enddate)";
            if ($search_engine != "1") 
            {
                $vars["search_engine"] = $search_engine;
                $sql .= " AND search_engine = :search_engine";
            }
            $sql .= " AND domain NOT IN (SELECT domain FROM " . self::TABLE_WI_FINDINGS . ")";

            // If verbose output is enabled, print the verbose message
            if ($verbose) 
            {
                echo ("getNewSearchEngineEntries: " . $this->verbose_output($sql, $vars));
            }

            // Prepare and execute the SQL statement with the provided variables
            $statement = $this->connection->prepare($sql);
            if (!$statement) 
            {
                echo ("Problem with: " . $sql);
            }
            
            $statement->execute($vars);

            // Retrieve the fetched rows as objects
            $result = $statement->fetchAll(PDO::FETCH_OBJ);

            return ($result);
        }

        /**
         * Retrieve keywords from the database
         *
         * This function retrieves keywords from the database based on the specified category and an optional limit.
         *
         * @param string $category The category of keywords to retrieve. Specify "1" to retrieve keywords from all categories, or a specific category to retrieve keywords from that category only.
         * @param string $language The language of keywords to retrieve. Specify "1" to retrieve keywords in any language, or a specific language to retrieve keywords in that language only.
         * @param int $limit The maximum number of keywords to retrieve. If set to -1, all matching keywords will be returned.
         * @param int $verbose (Optional) Controls whether verbose output is enabled or not. If the value is 1, verbose output will be displayed. By default, the value is 0, which disables verbose output.
         *
         * @return array An array containing the retrieved keywords as objects. Each keyword is represented as an object with column names corresponding to object properties.
         *
         * @throws PDOException If there is a problem with the database connection or executing the SQL query.
         */
        function getKeywords($category, $language, $limit="-1", $verbose=0)
        {
            // Build the SQL query based on the category
            if ($category === "1") 
            {
                $sql = "SELECT * FROM " . self::TABLE_WI_KEYWORDS;
                $vars = array();
            } 
            else 
            {
                $sql = "SELECT * FROM " . self::TABLE_WI_KEYWORDS . " WHERE category = :category";
                $vars["category"] = $category;
            }

            // Add the specific language of keywords to the SQL query
            if ($language != "1") 
            {
                if ($category === "1")
                {
                    $sql .= " WHERE ";
                }
                else 
                {
                    $sql .= " AND ";
                }
                $sql .= " language = :language";
                $vars["language"] = $language;
            }

            // Add the limitation to the SQL query if the limit is specified
            if ($limit != "-1") 
            {
                $sql .= " LIMIT 0, :limit";
                $vars["limit"] = $limit;
            }

            // If verbose output is enabled, print the verbose message
            if ($verbose) 
            {
                echo("getKeywords: " . $this->verbose_output($sql, $vars));
            }

            // Prepare and execute the SQL statement with the provided variables
            $statement = $this->connection->prepare($sql);
            if (!$statement) 
            {
                echo ("Problem with: " . $sql);
            }

            $statement->execute($vars);

            // Retrieve the fetched rows as objects
            $result = $statement->fetchAll(PDO::FETCH_OBJ);

            return ($result);
        }

        /**
         * Generates new Findings in the database based on WI_SEARCH_ENGINE_RESULT
         *
         * The generateNewFindings function generates new findings in the database based on the search engine results
         * found within the specified time period between $startdate and $enddate, and originating from the specified search engine.
         *
         * @param string $startdate The start date of the time period for which search engine results should be retrieved (in the 'YYYY-MM-DD' format).
         * @param string $enddate The end date of the time period for which search engine results should be retrieved (in the 'YYYY-MM-DD' format).
         * @param string $search_engine The search engine from which the results should be retrieved. By default, $search_engine = "1" (all search engines).
         * @param int $verbose Specifies whether detailed output should be displayed during execution. By default, $verbose = 0 (no output).
         *
         * @return int Returns 1 if the newly generated findings were successfully inserted into the database, 0 if there were no entries within the specified time period, and -1 if there was a problem with the insertion.
         */
        function generateNewFindings($startdate, $enddate, $search_engine, $verbose = 0)
        {
            // Retrieve the search engine results for the specified time period and search engine
            $search_engine_entries = $this->getNewSearchEngineEntries($startdate, $enddate, $search_engine, $verbose);
            
            // Counter for the newly generated findings
            $count = 0;
            
            // Iterate through the retrieved search engine entries and insert the corresponding findings into the database
            foreach ($search_engine_entries as $entry) 
            {
                // Insert the finding into the database and increment the count of newly generated findings
                $count += $this->insertIntoWiFindings($entry->domain, $entry->last_keywordid, $verbose);
            }

            if ($count == 0)
            {
                // No search engine entries were found within the specified time period
                return 0;
            }
            else if ($count != sizeof($search_engine_entries))
            {
                // There was a problem with the insertion of one or more findings
                return -1;
            }
            // All the newly generated findings were successfully inserted into the database
            return 1;
        }

        /**
         * Inserting search results into the findings table
         *
         * The insertIntoWiFindings function inserts search results into the findings table (wi_findings).
         *
         * @param string $domain The domain of the found search result.
         * @param int $keywordid The ID of the keyword to which the search result belongs.
         * @param int $verbose Specifies whether detailed output should be displayed during execution. By default, $verbose = 0 (no output).
         *
         * @return int The number of affected rows (1 if the insertion was successful, otherwise 0).
         */
        function insertIntoWiFindings($domain, $keywordid, $verbose = 0)
        {
            // Create SQL statement for INSERT
            $sql = "INSERT IGNORE INTO " . self::TABLE_WI_FINDINGS . " (domain, keyword_id, inserted)";
            $sql .= " VALUES (:domain, :keyword_id, NOW()) ON DUPLICATE KEY UPDATE updated = NOW()";

            // Prepare variables for the SQL statement
            $vars["domain"] = $domain;
            $vars["keyword_id"] = $keywordid;

            // If verbose output is enabled, print the verbose message
            if ($verbose) 
            {
                echo("insertIntoWiFindings: " . $this->verbose_output($sql, $vars));
            }

            // Prepare the SQL statement and execute it with the provided variables
            $statement = $this->connection->prepare($sql);
            if (!$statement) 
            {
                echo ("Problem with: " . $sql);
            }
            
            // Number of affected rows
            $numRows = $statement->execute($vars);

            // If verbose output is enabled, print the verbose message
            if ($verbose) 
            {
                echo("Result: " . $numRows);
            }

            return ($numRows);
        }

        /**
         * Insert or update data into the WI_SEARCH_ENGINE_RESULT table
         *
         * The insertIntoWiSearchEngineResult function allows inserting or updating data in WI_SEARCH_ENGINE_RESULT.
         * The function executes an SQL INSERT statement 
         * If a record with the same primary key already exists,
         * the SQL UPDATE statement is used to update the existing data.
         *
         * @param string $domain The domain to which the data belongs.
         * @param string $url The URL of the found webpage.
         * @param string $last_keyword The last used keyword for the search.
         * @param int $last_keywordid The ID of the last used keyword.
         * @param string $last_title The title of the found webpage.
         * @param string $last_addendum An additional text or description for the webpage.
         * @param int $last_position The position of the webpage in the search results.
         * @param string $index_date The date on which the webpage was indexed.
         * @param int $ranking The ranking or relevance of the webpage in the search results.
         * @param string $priority The priority of the webpage.
         * @param string $search_engine The search engine from which the results originate.
         * @param int $verbose (Optional) Controls whether verbose output is enabled or not. If the value is 1, verbose output will be displayed. By default, the value is 0, which disables verbose output.
         *
         * @return int The number of affected rows in the database (0 if no rows are affected, 1 for a new record, and 2 for an update of an existing record).
         *
         * @throws PDOException If an error occurs during database access, a PDOException is thrown to display the error and control the error flow.
         */
        function insertIntoWiSearchEngineResult($domain, $url, $last_keyword, $last_keywordid, $last_title, $last_addendum, $last_position, $index_date, $ranking, $priority, $search_engine, $verbose = 0)
        {
            // Create SQL statement for INSERT or UPDATE
            $sql = "INSERT INTO " . self::TABLE_WI_SEARCH_ENGINE_RESULT . " (domain, url, last_keyword, last_keywordid, last_title, last_addendum, last_position, index_date, ranking, priority, search_engine, inserted)";
            $sql .= " VALUES (:domain, :url, :last_keyword, :last_keywordid, :last_title, :last_addendum, :last_position, :index_date, :ranking, :priority, :search_engine, NOW())";
            $sql .= " ON DUPLICATE KEY UPDATE updated = NOW()";

            // Prepare variables for the SQL statement
            $vars["domain"] = $domain;
            $vars["url"] = $url;
            $vars["last_keyword"] = $last_keyword;
            $vars["last_keywordid"] = $last_keywordid;
            $vars["last_title"] = $last_title;
            $vars["last_addendum"] = $last_addendum;
            $vars["last_position"] = $last_position;
            $vars["index_date"] = $index_date;
            $vars["ranking"] = $ranking;
            $vars["priority"] = $priority;
            $vars["search_engine"] = $search_engine;

            // If verbose output is enabled, print the verbose message
            if ($verbose) 
            {
                echo("insertIntoWiSearchEngineResult: " . $this->verbose_output($sql, $vars) . LF);
            }

            // Prepare the SQL statement and execute it with the provided variables
            $statement = $this->connection->prepare($sql);
            if (!$statement) 
            {
                echo ("Problem with: " . $sql);
            }

            // Number of affected rows
            $numRows = $statement->execute($vars);

            // If verbose output is enabled, print the verbose message
            if ($verbose) 
            {
                echo("Result: " . $numRows);
            }

            return ($numRows);
        }
    }

?>