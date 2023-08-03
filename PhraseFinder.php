<?php
    require_once("WiSearchEngineGoogle.class.php");

    /** 
     * Manages the extraction of search engine results for special snippets/phrases. These phrases have been found to be used by certain clusters of fake shops.
     * By monitoring the search engines on a continuous basis new fake shops from the same make can be detected as soon as they can be found by consumers in search engines
     * 
     * Usage: 
     * 
     * php PhraseFinder.php crawl <number of phrases> <number of result pages> <search engine>
     * 
     * this call crawls the results with a number of phrases monitoring a range of result pages. As a standard Google is used as search engine. Other search engines could be implented.
     * 
     * php PhraseFinder.php store <start date (YYYY-MM-DD)> <end date (YYYY-MM-DD)> <search engine>
     * 
     * this call analyzes the results of the crawl with a certain date range and stores new domains in the WI_FINDINGS
     * 
     * later modules can classify (autmatic classification with mal2 classificator) or visualize the results for expert classification setting the type of the result
     */

    /**
     * stores the action to be performed, which can be either "crawl" or "store".
     * 
     * @var string $action
     */
    $action="";
    
    /**
     * stores specific implementation for retrieving search engine results through APIs.
     * 
     * @var WiSearchEngine $searchEngine
     */
    $searchEngine=null;

    /**
     * database access.
     *
     * @var WiDataStorage $databaseConnection
     */
    $databaseConnection = new WiDataStorage();

    // Checking the number of  arguments passed
    // Note: argv[0] represents the script itself, so $argc is incremented by 1
    if ($argc == 2 || $argc == 1)
    {
        // If the argument is "-h" or "--help" or no arguments are given the program displays the instructions and exits.
        if ($argv[1] == "--help" or $argv[1] == "-h")
        {
            print("Usage: php PhraseFinder.php <action> <number of keywords> <number of pages to crawl> <search engine>\n");
            print("       php PhraseFinder.php <action> <start date (YYYY-MM-DD)> <end date (YYYY-MM-DD)> <search engine>\n");
            exit(0);
        } 
    }

    /**
     * If the number of arguments passed ($argc) is not equal to 5, it indicates an invalid call with the wrong number of arguments. 
     * An error message is displayed, showing the correct usage, and the script exits with status code 1.
     */
    if ($argc != 5)
    {
        print("Error: invalid call\n");
        print("Usage: php PhraseFinder.php --help\n");
        print("       php PhraseFinder.php -h\n");
        exit(1);
    }

    /**
     * If the number of passed arguments is correct, the first argument represents the "action", which will be stored in a variable.
     * 
     * @var string $action
     */
    $action = $argv[1];

    /**
     * set the value of the $search_engine variable by converting the fourth command-line argument to lowercase.
     */
    $search_engine = strtolower($argv[4]);

    /**
     * If the selected search engine is not "google"  print an error message indicating that the choice of search engines
     * is currently limited to Google. Exit with an error code (1).
     */
    if ($search_engine != "google")
    {
        print("Error: Currently, the selection of search engines is limited to Google. Further Crawling-Targets can be implemented in future.\n");
        exit(1);
    }

    // Create a new instance of the WiSearchEngineGoogle class for the Google search engine.
    $searchEngine = new WiSearchEngineGoogle();

    // If "crawl" is passed for $action, the search for keywords for the selected search engine is executed.
    if ($action == "crawl") 
    {
        // Extract the number of keywords and the page limit from the arguments.
        $number_of_keywords = $argv[2];
        $limit_of_pages = $argv[3];

        // Check if the passed values for the number of keywords and page limit are valid numbers.
        if (!is_numeric($number_of_keywords) || !is_numeric($limit_of_pages)) 
		{
            print("Error: The input values for the number of keywords and the page limit must be valid numbers.\n");
            exit(1);
        }

        // Initialize the Google search engine with category "1", language "1", and the number of keywords.
        $number_of_selected_keywords = $searchEngine->init("1", "1", $number_of_keywords);        
        // Check if the number of selected keywords is equal to 0
        if ($number_of_selected_keywords == 0) 
        {
            print("Error: There are no keywords in the database.");
            exit(1);
        }

        // Check if the number of selected keywords is less than the total number of keywords
        if ($number_of_selected_keywords < $number_of_keywords) 
        {
            // If the number of selected keywords is less than the total, update the total to be the same as the number of selected keywords
            $number_of_keywords = $number_of_selected_keywords;
        }

        // Perform the keyword search for the specified number of keywords and store the results in the database.
        for ($i = 0; $i < $number_of_keywords; $i++) 
        {
            $selectedKeyword = $searchEngine->selectKeyword();
            $number = $searchEngine->crawl_api($selectedKeyword, $limit_of_pages);
            print("Info: For the keyword '" . $selectedKeyword->keyword . "', " . $number . " results were found and stored.\n");
        }
    }
    // If "store" is passed for $action, the new domains will be inserted into the table for new findings.
    else if ($action == "store") 
    {
        // extract start date, end date, and search engine from the arguments.
        $start_date = $argv[2];
        $end_date = $argv[3];

        // check if the provided dates are in the valid format (YYYY-MM-DD).
        if ((preg_match('/^(20)\d\d-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/', $start_date) != 1) || (preg_match('/^(20)\d\d-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/', $end_date) != 1)) 
        {
            print("Error: The dates are not in the valid format (YYYY-MM-DD).\n");
            exit(1);
        }

        // store new findings into the database.
        $result = $databaseConnection->generateNewFindings($start_date, $end_date, $search_engine);

        // check if insert of new findings was successful.
        if ($result == 1) 
        {
            // If 1, a success message is printed.
            print("Info: The new findings were successfully inserted into the database.\n");
        } 
        else if($result == 0)
        {
            // If 0, a message is printed, indicating that there were no findings within the specified time period.
            print("Info: There were no findings within the specified time period.\n");
        }
        else 
        {
            // If there was an error during insertion, the corresponding error message is printed.
            print("Error: There was an error during the insertion of the new findings.\n");
        }
    }


?>