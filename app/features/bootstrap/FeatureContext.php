<?php

use Behat\Behat\Context\ClosuredContextInterface,
		Behat\Behat\Context\TranslatedContextInterface,
		Behat\Behat\Context\BehatContext,
		Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
		Behat\Gherkin\Node\TableNode;
use Mudasobwa\Eblo\Cache;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Features context.
 */
class FeatureContext extends BehatContext
{
	private $testInput = null, $testOutput = null;
	private $config = null, $className = null;
	private $content = null, $fileName = null;

	private $cacheInstance = null, $arr = null;

	/**
	 * Initializes context.
	 * Every scenario gets its own context object.
	 *
	 * @param array $parameters context parameters (set them up through behat.yml)
	 */
	public function __construct(array $parameters) {
		$this->testInput = array(
				"2014-7-20-1",
				"2014-7-27-1",
				"2014-7-23-1",
				"2014-6-13-1",
				"2014-8-6-1",
				"2014-7-24-1",
				"2014-8-5-1",
				"2014-7-26-1",
				"2014-7-31-1",
				"2014-8-5-2"
		);
		$this->testTemplate = array(
				"2014-8-6-1",
				"2014-8-5-2",
				"2014-8-5-1",
				"2014-7-31-1",
				"2014-7-27-1",
				"2014-7-26-1",
				"2014-7-24-1",
				"2014-7-23-1",
				"2014-7-20-1",
				"2014-6-13-1"
		);
	}

	///////////////////////////////////////////////////////////////////////////
	//	GIVEN
	///////////////////////////////////////////////////////////////////////////

	/**
	 * @Given /^the config is loaded from default location$/
	 */
	public function theConfigIsLoadedFromDefaultLocation() {
		$this->config = \Spyc::YAMLLoad(__DIR__ . '/../../config/.restark.yml');
	}

	/**
	 * @Given /^the Cache instance is retrieven$/
	 */
	public function theCacheInstanceIsRetrieven() {
		$this->cacheInstance = \Mudasobwa\Eblo\Cache::instance();
	}

	/**
	 * @Given the input string is :arg1
	 */
	public function theInputStringIs($input) {
		$this->testInput = $input;
	}

	/**
	 * @Given the input string is
	 */
	public function theInputStringIs2(PyStringNode $string) {
		$this->testInput = $string;
	}

	/**
	 * @Given /^the input string is "([^"]*)"$/
	 */
	public function theInputStringIs3($input)
	{
		$this->testInput = $input;
	}

	///////////////////////////////////////////////////////////////////////////
	//	WHEN
	///////////////////////////////////////////////////////////////////////////

	/**
	 * @When /^sorter class name is requested$/
	 */
	public function sorterClassNameIsRequested() {
		$this->testOutput = $this->className = $this->config['data']['sorter'];
	}

	/**
	 * @When /^sorter function is being called on test input data$/
	 */
	public function sorterFunctionIsBeingCalledOnTestInputData() {
		\usort($this->testInput, $this->className);
	}

	/**
	 * @When /^the files list is requested$/
	 */
	public function theFilesListIsRequested() {
		$this->arr = $this->cacheInstance->collection();
	}

	/**
	 * @When /^the file "([^"]*)" is being loaded$/
	 */
	public function theFileIsBeingLoaded($s) {
		$this->content = $this->cacheInstance->content($s);
	}

	/**
	 * @When /^the files list is requested for filter "([^"]*)"$/
	 */
	public function theFilesListIsRequestedForFilter($s) {
		$this->arr = $this->cacheInstance->find('allfiles', $s);
	}

	/**
	 * @When input string is processed with parser
	 */
	public function inputStringIsProcessedWithParser() {
		$this->testOutput = \Mudasobwa\Eblo\Markright::yo($this->testInput);
	}

	/**
	 * @When /^the file "([^"]*)" is being looked up$/
	 */
	public function theFileIsBeingLookedUp($arg1) {
		$this->fileName = $arg1;
	}

	/**
	 * @When /^the neightborhood of size (\d+) is retrieven$/
	 */
	public function theNeightborhoodOfSizeIsRetrieven($arg1) {
		$this->arr = $this->cacheInstance->lookup($this->fileName, \Mudasobwa\Eblo\Cache::DEFAULT_COLLECTION, 3, true);
	}

	/**
	 * @Then /^the result should be var_dump’ed$/
	 */
	public function theResultShouldBeVarDumpEd() {
		var_export($this->arr);
	}

	///////////////////////////////////////////////////////////////////////////
	//	THEN
	///////////////////////////////////////////////////////////////////////////

	/**
	 * @Then /^the result should equal to "([^"]*)"$/
	 */
	public function theResultShouldEqualTo($s) {
		if ($s !== $this->testOutput)
			throw new Exception("Actual output differs:\n" . $this->testOutput);
	}

	/**
	 * @Then /^the result should equal to test input data$/
	 */
	public function theResultShouldEqualToTestInputData() {
		if (count($this->testInput) !== count($this->testTemplate))
			throw new Exception("Internal error: arrays sizes differ.");

		for ($i = 0; $i < count($this->testInput); $i++) {
			if ($this->testInput[$i] !== $this->testTemplate[$i])
				throw new Exception("Sort failed.");
		}
	}

	/**
	 * @Then /^the result should be an array$/
	 */
	public function theResultShouldBeAnArray() {
		if (!is_array($this->arr))
			throw new Exception("Files were read incorrectly.");
	}

	/**
	 * @Then /^the first element of array should be "([^"]*)"$/
	 */
	public function theFirstElementOfArrayShouldBe($s) {
		if ($this->arr[0] !== $s)
			throw new Exception("Files array was read incorrectly.");
	}

	/**
	 * @Then /^the prev element of "([^"]*)" should be "([^"]*)"$/
	 */
	public function thePrevElementOfItShouldBe($orig, $prev) {
		if ($prev === 'null') $prev = null;
		if ($this->cacheInstance->prev($orig) !== $prev)
			throw new Exception("Previous lookup failed. Got {$prev} instead of {$orig}");
	}

	/**
	 * @Then /^the next element of "([^"]*)" should be "([^"]*)"$/
	 */
	public function theNextElementOfItShouldBe($orig, $next) {
		if ($this->cacheInstance->next($orig) !== $next)
			throw new Exception("Next lookup failed. Got {$next} instead of {$orig}");
	}

	/**
	 * @Then /^the result should begin with "([^"]*)"$/
	 */
	public function theResultShouldBeginWith($s) {
		if (!\preg_match("/{$s}/muxs", $this->content))
			throw new Exception("Lookup failed with {$s}");
	}

	/**
	 * @Then /^the last element of array should be "([^"]*)"$/
	 */
	public function theLastElementOfArrayShouldBe($s) {
		if ($this->arr[\count($this->arr) - 1] !== $s)
			throw new Exception("Last element of filteres array is incorrect.");
	}

	/**
	 * @Then the result should equal to
	 */
	public function theResultShouldEqualTo2(PyStringNode $string) {
		if ((string)$string !== $this->testOutput) {
			throw new Exception(
					"Actual output differs:\n" . $this->testOutput
			);
		}
	}

	/**
	 * @Then /^the result should equal to \'([^\']*)\'$/
	 */
	public function theResultShouldEqualTo3($string) {
		if ((string)$string !== $this->testOutput) {
			throw new Exception(
					"Actual output differs:\n" . $this->testOutput
			);
		}
	}

	/**
	 * @Then print the result out
	 */
	public function printTheResultOut() {
		print("Result: " . $this->testOutput);
	}

}

