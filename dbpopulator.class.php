<?php

	/**
	 * DataBase Populator
	 * Class for populating MySQL tables with dummy data
	 * 
	 * @version		1.0
	 * @release		2010-08-03
	 * @author		Rafael Jaques
	 * @url			http://www.phpit.com.br/
	 * @contact		rafa@php.net
	 * @github		rafajaques
	 * 
	 * Any suggestion, request or bug, request on GitHub!
	 *
	 * If you code any class based on this one, please let me now.
	 * I'll be proud of that!
	 *
	 * This file is licensed under BSD License.
	 *
	 * For the full copyright and license information, please view the LICENSE
	 * file that was distributed with this source code.
	 * 
	 */
    class DBPopulator {
        
        /**
         * Configuration
         */
        
        // Define what fields may be recognised
        private $dummyName              = array('name', 'nome', 'nombre', 'nom');
        private $dummyAge               = array('idade', 'age');
        private $dummyLink              = array('link', 'url', 'uri', 'site', 'website');
        private $dummyEmail             = array('email', 'mail');
        private $dummyUsername          = array('user', 'username', 'usuario');
        private $dummyPassword          = array('password', 'pass', 'senha');
        
        // Would you like to encrypt your passwords?
        private $dummyPasswordEncrypt   = 'md5'; // md5, sha1, base64, false
        
        // Define number range
        private $dummyIntRange          = array(0, 256); // Min, max
        private $dummyRealRange         = array(0, 100); // Min, max
        
        // Dummy values catalog
        private $dummyNamesContainer = array(
            'name'      => array(
                'John', 'Jane', 'Mr. Foo', 'Don Juan',
                'Sauron', 'Gandalf', 'Sub-zero', 'Ronaldinho',
                'Michelangelo', 'Ryu', 'Ken', 'Seiya', 'Ikki',
                'Yusuke', 'Naruto', 'Paul', 'Goku', 'Vegetta',
                'Chris', 'Claire', 'Alfred', 'Alexia',
                'Sheldon', 'Leonard', 'Rajesh', 'Howard',
            ),
            'surname'   => array(
                'Doe', 'Bar', 'DeMarco', 'Uzumaki', 'Stanley',
                'Urameshi', 'Spielberg', 'Schwarzenegger',
                'Napalm', 'Stardust', 'Ashford', 'Redfield',
                'Cooper', 'Hofstadter', 'Koothrappali', 'Wolowitz',
            ),
        );
        
        private $dummyLinksContainer = array(
            'domains'  => array(
                'mydomain', 'google', 'example', 'whatever',
            ),
            'toplevel' => array(
                '.com.br', '.com', '.net', '.co.uk', '.org',
            ),
        );
        
        // Usernames receive a random number after to not insert duplicate data
        private $dummyUsernamesContainer = array(
            'foo', 'bar', 'john', 'mrniceguy', 'furry', 'hotdog',
            'mark', 'subzero', 'naruto', 'paul', 'ikki', 'gandalf',
            'sauron', 'frodo', 'doe', 'ronaldo', 'tux', 'gentleman',
            'ranger', 'ehonda', 'fanboy', 'sushi', 'bean', 'chucknorris',
            'amazing', 'mrlegend', 'wonderwall', 'hackme', 'googleboy',
        );
        
        // Emails are generated mixing dummyUsernames and dummyLinks
        
        private $dummyPasswordsContainer = array(
            '123', 'admin', 'pinapple', 'secret', '******', 'lol'
        );
        
        private $dummyStringsContainer = array(
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            'Suspendisse rhoncus tortor ac tortor molestie nec rhoncus purus pretium.',
            'Ut nisi felis, lacinia in ornare at, congue a elit.',
            'Sed molestie semper purus non pellentesque.',
            'Vestibulum velit lacus, lacinia sit amet laoreet vel, pretium vitae diam.',
            'Aliquam viverra, dui id rhoncus iaculis, enim metus ultrices velit, non gravida nulla nibh sit amet eros.',
            'Vestibulum ac orci ipsum. Vivamus dolor libero, vehicula vitae pellentesque nec, consectetur et augue.',
        );
        
        /**
         * Here the trick begins... :)
         */
         
        // DB data
        private $host, $user, $pass, $db, $conn;
        
        public function setDb($host, $user = NULL, $pass = NULL) {
            $this->host = $host;
            $this->user = $user;
            $this->pass = $pass;
        }
        
        public function populate($tables, $inserts = 50, $asScript = false) {
            $this->connect();
            
            if (is_string($tables))
                $tables = array($tables);
            
            if (count($tables)) {
                
                // Hold the tables and its structures
                $mapping = array();
                
                // Organise the tables
                foreach($tables as $table) {
                    
                    // CASE 'database.*'
                    if (substr($table, -1, 1) == '*') {

                        // Gets the DB name
                        $db = explode('.', $table);
                        $db = $db[0];
                        $rs = mysql_query('SHOW TABLES FROM ' . mysql_real_escape_string($db));
                        
                        // If something went wrong, like no permission
                        if (mysql_error($this->conn))
                            $this->showError(mysql_error($this->conn));
                        
                        // Map the tables all way down ;)
                        while ($innerTable = mysql_fetch_row($rs)) {
                            // database.table
                            $tableName = $db . '.' . $innerTable[0];
                            $mapping[$tableName] = $this->getTableFields($tableName);
                        }
                        
                    // CASE 'database.table'
                    } else {
                        
                        $mapping[$table] = $this->getTableFields($table);   
                        
                    }

                }

                // Do the magic
                if (count($mapping)) {
                    // Populate
                    if ($asScript) {
                        $fileContent = '';
                        
                        foreach ($mapping as $table => $fields)
                            $fileContent .= $this->insertData($table, $fields, $inserts, 1);
                            
                        file_put_contents($asScript, $fileContent);
                    } else {
                        foreach ($mapping as $table => $fields)
                            $this->insertData($table, $fields, $inserts);
                    }
                }
                return true;
            } else {
                return false;
            }
        }
        
        /**
         * Constructor and destructor
         * Nothing to worry about ^^
         */
        public function __construct() {
            $this->shuffleDummyValues();
        }
        
        public function __destruct() {
            if (!is_null($this->conn))
                mysql_close($this->conn);
        }
        
        /**
         * The engine :D
         */
         
        /**
         * Map table fields
         */
        private function getTableFields($table) {
            $tableFields = array();

            $rs = mysql_query('SELECT * FROM '.$table);

            if (mysql_error($this->conn))
                $this->showError(mysql_error($this->conn));
            
            $tableNumFields = mysql_num_fields($rs);
            
            for ($i = 0; $i < $tableNumFields; $i++) {
                $name  = mysql_field_name($rs, $i);
                $type  = mysql_field_type($rs, $i);
                $flags = mysql_field_flags($rs, $i);
                
                // We're not adding values into primary keys
                if (strpos($flags, 'primary_key') === false)
                    $tableFields[$name] = $type;

            }
            return $tableFields;
        }

        /**
         * Generate the queries
         */
        private function insertData($table, $fields, $times, $return = false) {
            // Holds the patterns to replace
            $values = array();

            foreach ($fields as $field => $type) {
                $values[] = $this->getFieldPattern($field, $type);
            }
            
            $statement = $this->getStatement($table, $fields, $values);
            
            if ($return) {
                $returnString = '';
                
                for ($i = 0; $i < $times; $i++) {
                    $returnString .= $this->parseStatement($statement) . "; \n";
                }
                
                return $returnString;
            } else {
                for ($i = 0; $i < $times; $i++) {
                    mysql_query($this->parseStatement($statement));
                }
                return true;
            }
        }
        
        /**
         * Identifies the field pattern to replace with compatible data
         */
        private function getFieldPattern($name, $type) {

            switch ($type) {
                case 'real':
                case 'date':
                case 'datetime':
                case 'time':
                case 'timestamp':
                    return '{' . $type . '}';
                    break;
                
                case 'int':
                    // Age
                    if (in_array($name, $this->dummyAge))
                        return '{age}';
                    // Ordinary Integer
                    else
                        return '{int}';
                    break;
                    
                case 'string':
                case 'blob':
                    // Name
                    if (in_array($name, $this->dummyName))
                        return '{name}';
                    // Username
                    elseif (in_array($name, $this->dummyUsername))
                        return '{username}';
                    // Password
                    elseif (in_array($name, $this->dummyPassword))
                        return '{password}';
                    // Link
                    elseif (in_array($name, $this->dummyLink))
                        return '{link}';
                    // Email
                    elseif (in_array($name, $this->dummyEmail))
                        return '{email}';
                    // Ordinary string
                    else
                        return '{string}';
                    break;
                
                default:
                    return '{bool}';
                    break;

            }
            
            return $value;
        }
        
        /**
         * Generate the statement to insert data
         */
        private function getStatement($table, $fields, $values) {
            // Prepare the fields, `joining`,`by`,`comma`
            $fields = '`' . implode('`,`', array_keys($fields)) . '`';
            
            // Prepare the values: "joining","by","comma"
            $values = '"' . implode('","', $values) . '"';
            
            $statement = "INSERT INTO $table ($fields) VALUES ($values)";
            
            return $statement;
        }
        
        /**
         * Parse the statement to insert data :D
         */
        private function parseStatement($statement) {
            $from   = array(
                '{int}', '{real}', '{date}', '{time}', '{datetime}', '{timestamp}',
                '{age}', '{string}', '{blob}', '{name}', '{username}',
                '{password}', '{link}', '{email}', '{string}', '{bool}',
            );
            $to     = array(
                $this->getDummyInt(), $this->getDummyReal(), $this->getDummyDate(), $this->getDummyTime(),
                $this->getDummyDatetime(), $this->getDummyTimestamp(),
                $this->getDummyAge(), $this->getDummyString(), $this->getDummyBlob(),
                $this->getDummyName(), $this->getDummyUsername(), $this->getDummyPassword(),
                $this->getDummyLink(), $this->getDummyEmail(), $this->getDummyString(), $this->getDummyBool(),
            );
            
            $statement = str_replace($from, $to, $statement);
            
            return $statement;
        }
        
        /**
         * Dummy Data Generator
         */
        private function getDummyInt() {
            return mt_rand($this->dummyIntRange[0], $this->dummyIntRange[1]);
        }
        
        // Thanks to 'rok dot kralj at gmail dot com' @ http://php.net/manual/en/function.rand.php
        private function getDummyReal() {
            return ($this->dummyRealRange[0]+lcg_value()*(abs($this->dummyRealRange[1]-$this->dummyRealRange[0])));
        }
        
        private function getDummyDate() {
            return sprintf('%s-%s-%s', mt_rand(1930, date('Y')), mt_rand(1, 12), mt_rand(1, 28));
        }
        
        private function getDummyTime() {
            return sprintf('%s:%s:%s', mt_rand(0, 23), mt_rand(0, 59), mt_rand(0, 59));
        }
        
        private function getDummyDatetime() {
            return $this->getDummyDate() . ' ' . $this->getDummyTime();
        }
        
        private function getDummyTimestamp() {
            return mktime(0, 0, 0, mt_rand(1,12), mt_rand(1,28), mt_rand(1970, date('Y')));
        }
        
        private function getDummyAge() {
            return mt_rand(18, 80);
        }
        
        private function getDummyString() {
            return $this->dummyStringsContainer[mt_rand(0, count($this->dummyStringsContainer)-1)];
        }

        private function getDummyBlob() {
            $paragraphs = mt_rand(1,3);
            
            $blob = '';
            
            for ($i = 1; $i <= $paragraphs; $i++) {
                $blob .= $this->getDummyString() . "\n";
            }
            
            return $blob;
        }
        
        private function getDummyName() {
            shuffle($this->dummyNamesContainer['name']);
            shuffle($this->dummyNamesContainer['surname']);
            return $this->dummyNamesContainer['name'][0] . ' ' . $this->dummyNamesContainer['surname'][0];
        }
        
        private function getDummyUsername() {
            shuffle($this->dummyUsernamesContainer);
            return $this->dummyUsernamesContainer[0] . mt_rand(1, 9999);
        }
        
        private function getDummyPassword() {
            shuffle($this->dummyPasswordsContainer);
            $pass = $this->dummyPasswordsContainer[0];
            
            switch ($this->dummyPasswordEncrypt) {
                case 'md5':
                    $pass = md5($pass);
                    break;
                
                case 'sha1':
                    $pass = sha1($pass);
                    break;
                    
                case 'base64':
                    $pass = base64_encode($pass);
                    break;
            }
            
            return $pass;
        }
        
        private function getDummyLink($http = 1) {
            $link = '';

            if ($http)
                $link = 'http://www.';
                
            $link.= $this->dummyLinksContainer['domains'][0] . $this->dummyLinksContainer['toplevel'][0];
            
            shuffle($this->dummyLinksContainer['domains']);
            shuffle($this->dummyLinksContainer['toplevel']);
            
            return $link;
        }
        
        private function getDummyEmail() {
            return $this->getDummyUsername() . '@' . $this->getDummyLink(0);
        }
        
        private function getDummyBool() {
            return mt_rand(0, 1);
        }
        
        /**
         * The rest...
         */
        private function connect() {
            if (is_null($this->host)) {
                $this->showError('You did not set the database info');
            }
            
            return $this->conn = mysql_connect($this->host, $this->user, $this->pass);
        }
        
        private function shuffleDummyValues() {
            shuffle($this->dummyStringsContainer);
            shuffle($this->dummyNamesContainer['name']);
            shuffle($this->dummyNamesContainer['surname']);
            shuffle($this->dummyLinksContainer['domains']);
            shuffle($this->dummyLinksContainer['toplevel']);
            shuffle($this->dummyUsernamesContainer);
            shuffle($this->dummyPasswordsContainer);
        }
        
        private function showError($error) {
            trigger_error($error, E_USER_ERROR);
            die;
        }
    
    }