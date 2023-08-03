<?php

    /**
     * Please fill in your API keys here: 
     */
    
    // Define Google search engine ID, API key, and API call URL
    DEFINE("GOOGLE_SEARCH_ENGINE_ID", ""); // a google search enginge id looks somethin like this 36de5e0gfef0b4def
    DEFINE("GOOGLE_API_KEY", ""); // a google API KEY looks something like this AIzbSyAZ1QzHi__OGRTS0uKuuIRXz7edJDefF50
    DEFINE("GOOGLE_API_CALL", "https://www.googleapis.com/customsearch/v1?key=".GOOGLE_API_KEY."&cx=".GOOGLE_SEARCH_ENGINE_ID);

    /**
     * Please fill in your variables for the database connection
     */

    // Database configuration array
    $DB_CONF = [
        // ---------------- general settings, required ---------
        "host"              => "localhost", // e.g. localhost
        "dbname"            => "",    // e.g. myDB
        "user"              => "user",      // e.g. my_user
        "password"          => "password",  // e.g. 7G$tnNE43^m!
    ];

    // For backward compatibility, map some constants for legacy non-SSL connections.
    DEFINE("DB_HOST",     $DB_CONF["host"]);
    DEFINE("DB_NAME",     $DB_CONF["dbname"]);
    DEFINE("DB_USER",     $DB_CONF["user"]);
    DEFINE("DB_PASSWORD", $DB_CONF["password"]);

?>