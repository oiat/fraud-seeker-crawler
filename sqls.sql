--
-- Table with Phrases / code snippets to check 
--
CREATE TABLE `WI_KEYWORDS` (
  `keywordid` int AUTO_INCREMENT PRIMARY KEY,
  `keyword` varchar(300) COLLATE utf8mb4_bin NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  `language` varchar(2) COLLATE utf8mb4_bin NOT NULL,
  `type` varchar(10) COLLATE utf8mb4_bin NOT NULL,
  `priority` int DEFAULT NULL,
  `last_used` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Example Data for table `WI_KEYWORDS`
--
INSERT INTO `WI_KEYWORDS` (`keywordid`, `keyword`, `category`, `language`, `type`, `priority`, `last_used`) VALUES
(865, '\"Our website is designed to help you find the ideal product in the quickest way.\"', 'Fake Shop Type 1', 'DE', 'Phrase', 3, NULL);

--
-- Table holding results of search engines for the phrases/keywords
--
CREATE TABLE `WI_SEARCH_ENGINE_RESULT` (
  `domain` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  `url` varchar(512) COLLATE utf8mb4_bin NOT NULL PRIMARY KEY,
  `last_keyword` varchar(300) COLLATE utf8mb4_bin NOT NULL,
  `last_keywordid` int NOT NULL,
  `last_title` varchar(512) COLLATE utf8mb4_bin DEFAULT NULL,
  `last_addendum` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL,
  `last_position` int DEFAULT NULL,
  `index_date` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  `ranking` varchar(10) COLLATE utf8mb4_bin NOT NULL,
  `search_engine` varchar(50) COLLATE utf8mb4_bin NOT NULL,
  `updated` date DEFAULT NULL,
  `inserted` date DEFAULT NULL,
  `count` int DEFAULT NULL,
  `priority` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


--
-- Results on domain level with the possibility to set a type fake / whitelist
--
CREATE TABLE `WI_FINDINGS` (
  `domain` varchar(100) COLLATE utf8mb4_bin NOT NULL PRIMARY KEY,
  `keyword_id` int NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL,
  `inserted` date NOT NULL,
  `updated` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;