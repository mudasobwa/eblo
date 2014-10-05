Feature: Cache is responsible for caching the common data

  Scenario: Config is being loaded properly
    Given the config is loaded from default location
    When sorter class name is requested
    Then the result should equal to "Mudasobwa\Eblo\Cache::sorter"

  Scenario: Sorter function is being called successfully
    Given the config is loaded from default location
    When sorter class name is requested
    And sorter function is being called on test input data
    Then the result should equal to test input data

  Scenario: Default sorter function is being called successfully
    Given the config is loaded from default location
    When sorter class name is requested
    And sorter function is being called on test input data
    Then the result should equal to test input data

  Scenario: Files are being returned with Cache instance
    Given the Cache instance is retrieven
    When the files list is requested
    Then the result should be an array
    And the first element of array should be "2014-8-10-1"
    And the prev element of "2014-8-10-1" should be "null"
    And the next element of "2014-8-10-1" should be "2014-8-6-1"

  Scenario: Files are being found with Cache instance
    Given the Cache instance is retrieven
    When the files list is requested for filter "2014-8"
    Then the result should be an array
    And the first element of array should be "2014-8-10-1"
    And the last element of array should be "2014-8-2-1"

  Scenario: Content of the file is successfully retrieven
	Given the config is loaded from default location
	When the Cache instance is retrieven
	And the file "2014-8-6-1" is being loaded
	Then the result should begin with "mudasobwa"

  Scenario: Neighborhood is successfully retrieven
	Given the config is loaded from default location
	When the Cache instance is retrieven
	And the file "2014-8-6-1" is being looked up
	And the neightborhood of size 3 is retrieven
	Then the result should be var_dump’ed


