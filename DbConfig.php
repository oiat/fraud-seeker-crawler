<?php
    require_once("Config.php");
    
    /**
     * Include file for DB connection related functions
     */


    /**
     * Takes any assoc array.
     * Expects to hold $dbConf["host"]  => string
     * an sets         $dbConf["hosts"] => array(string)
     * If the string holds "/" $dbConf["hosts"] will hold the exploded hosts, otherwise a size-1 array with one host name.
     */
    function mup_splitDBHosts(&$dbConf)
    {
        $host = $dbConf["host"]; //e.g.:  "my_unix_machine"
        if (strpos($host, "/") !== false)
        {
            $dbConf["hosts"] = explode("/", $host);
        }
        else 
        {
            $dbConf["hosts"] = array($host);
        }
    }

    /**
     * Does connection to MySQL / MariaDB 
     * using mysqli, object oriented interace, real_connect, ssl mode.
     * https://www.php.net/manual/de/mysqli.real-connect.php
     *
     * $dbConf  assoc array with db configuration.
     *      
     *          NOTE:
     *          Usually pass $DB_CONF defined in config.php as assoc array, i.e. a variable while former DB settings have been constants.
     *          Hence, if this function itself shall be called from within another function,
     *          the other function not just had to call:
     *              $pdo = mup_PDO_connectSequential($DB_CONF);
     *          
     *          Instead, it had to call:
     *              global $DB_CONF;
     *              $pdo = mup_PDO_connectSequential($DB_CONF);
     *      
     *          To keep necessary code changes as small as possible
     *          $dbConf is optional and might be null.
     *          If so, internally the global $DB_CONF is used.
     *     
     * $dbConf['host'] may hold a single hostname, e.g.           "my_unix_machine" 
     *                 or several '/' glued hostnames,  e.g.:     "my_unix_machine1/my_unix_machine2"
     *                 In this case, all are tried. The first successful connection wins"
     *          
     *                 This simulates the same behaviour as MariaDB Java Connector does with an url like:
     *                 url=jdbc:mariadb:sequential://my_unix_machine1,my_unix_machine2/somedir?useSsl=true&trustServerCertificate=true
     *         
     */

  

    function mup_mysqli_connectSequential($dbConf=null)
    {
        global $DB_CONF;
        if ($dbConf === null)
        {
            $dbConf = $DB_CONF;
        }
        
        mup_splitDBHosts($dbConf);
        $hosts = $dbConf["hosts"];
        $exceptions=null;
        foreach ($hosts as $host)
        {
            try 
            {
                $mysqli = new mysqli();
                $flags=0;
                if ($dbConf['ssl'] == false)    
                {
                    $flags |= MYSQLI_CLIENT_SSL;
                }
                if (!$mysqli->real_connect($host, $dbConf['user'], $dbConf['password'], $dbConf['dbname'],0,null,$flags)) //!!!!!!!!!! CORE: CONNECTION !!!!!!!!!!!!
                {
                    $mysqli->close();
                }
                else {
                    return $mysqli;
                }
            }
            catch (Exception $e)
            {
                if ($exceptions === null)
                {
                    $exceptions="\n$host -> " . $e->getMessage();
                }
                else 
                {
                    $exceptions .= "\n$host -> " . $e->getMessage();
                }
            }
        }
        if ($exceptions === null)
        {
            throw new Exception("1647602971000-mup_mysqli_connect failed. Host(s) tried: " . implode("/", $hosts));
        }
        else 
        {
            throw new Exception("1647602971001-mup_mysqli_connect failed. Host(s) tried: " . implode("/", $hosts)); // , previous: new Exception($exceptions)
        }
        
    }


    /**
     * Does connection to MySQL / MariaDB 
     * using PDO
     * https://www.php.net/manual/de/book.pdo.php
     *
     * $dbConf  assoc array with db configuration.
     *      
     *          NOTE:
     *          Usually pass $DB_CONF defined in config.php as assoc array, i.e. a variable while former DB settings have been constants.
     *          Hence, if this function itself shall be called from within another function,
     *          the other function not just had to call:
     *              $pdo = mup_PDO_connectSequential($DB_CONF);
     *          
     *          Instead, it had to call:
     *              global $DB_CONF;
     *              $pdo = mup_PDO_connectSequential($DB_CONF);
     *      
     *          To keep necessary code changes as small as possible
     *          $dbConf is optional and might be null.
     *          If so, internally the global $DB_CONF is used.
     *     
     * $dbConf['host'] may hold a single hostname, e.g.           "my_unix_machine" 
     *                 or several '/' glued hostnames,  e.g.:     "my_unix_machine1/my_unix_machine2"
     *                 In this case, all are tried. The first successful connection wins"
     *          
     *                 This simulates the same behaviour as MariaDB Java Connector does with an url like:
     *                 url=jdbc:mariadb:sequential://my_unix_machine1,my_unix_machine2/somedir?useSsl=true&trustServerCertificate=true
      *
     */
    function mup_PDO_connectSequential($dbConf=null)
    {
        global $DB_CONF;
        if ($dbConf === null)
        {
            $dbConf = $DB_CONF;
        }
        
        mup_splitDBHosts($dbConf);
        $hosts = $dbConf["hosts"];
        $exceptions=null;
        foreach ($hosts as $host)
        {
            try 
            {
                // see https://callisto.digital/posts/php/enable-mysql-over-ssl-in-php-pdo/
                // see https://www.php.net/manual/de/ref.pdo-mysql.php
                $options=null;
                $decision=false;
                if(isset($dbConf['ssl']))
                {
                    $decision=$dbConf['ssl'];
                }
                if ($decision) 
                {
                    $options=[
                        PDO::MYSQL_ATTR_SSL_CA => $dbConf['ca_certificate'],                //path to ca, apparently activates SSL at
                                                                                            //use it even (as mentioned above in mysqli it will not be accepted
                                                                                                
                        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,                    //if root_ca-cert.pem is not accepted, don't verify it.
                    ];
                }
                $pdo = new PDO('mysql:host='.$host.';dbname='.$dbConf['dbname'].';charset=utf8',$dbConf['user'], $dbConf['password'], $options);    //!!!!!!!!!! CORE: CONNECTION !!!!!!!!!!!!
                if (!$pdo)
                {
                    //
                }
                else 
                {
                    return $pdo;
                }
            }
            catch (Exception $e)
            {
                if ($exceptions === null)
                {
                    $exceptions="\n$host -> " . $e->getMessage();
                }
                else 
                {
                    $exceptions .= "\n$host -> " . $e->getMessage();
                }
            }
        }
        if ($exceptions === null)
        {
            throw new Exception("1647609196000-mup_PDO_connectSequential failed. Host(s) tried: " . implode("/", $hosts));
        }
        else 
        {
            throw new Exception("1647609196001-mup_PDO_connectSequential failed. Host(s) tried: " . implode("/", $hosts)); 
        }
    }


    function mup_printExc($e)
    {
        echo "Exception: " . $e->getMessage(). "\n";
        $cause = $e->getPrevious();
        if ($cause)
        {
            echo "Cause    : " . $cause->getMessage() . "\n";
        }
    }

?>