<?php

use Behat\Behat\Context\SnippetAcceptingContext,
	Behat\Gherkin\Node\PyStringNode,
	Behat\Gherkin\Node\TableNode;

use \Mudasobwa\Eblo\Shortener;

/**
 * Behat context class.
 */
class FeatureContext implements SnippetAcceptingContext
{
	private $input, $output;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context object.
     * You can also pass arbitrary arguments to the context constructor through behat.yml.
     */
    public function __construct()
    {
    }
    /**
     * @Given the input string is :arg1
     */
    public function theInputStringIs($arg1)
    {
        $this->input = $arg1;
    }

    /**
     * @When input string is processed with packer
     */
    public function inputStringIsProcessedWithPacker()
    {
        $this->output = Shortener::pack($this->input);
    }

    /**
     * @When input string is processed with unpacker
     */
    public function inputStringIsProcessedWithUnpacker()
    {
        $this->output = Shortener::unpack($this->input);
    }

    /**
     * @When result string is processed with unpacker
     */
    public function resultStringIsProcessedWithUnpacker()
    {
        $this->output = Shortener::unpack($this->output);
    }

    /**
     * @When input string is processed with tinier
     */
    public function inputStringIsProcessedWithTinier()
    {
        $this->output = Shortener::tiny($this->input);
    }

    /**
     * @When result string is processed with untinier
     */
    public function resultStringIsProcessedWithUntinier()
    {
        $this->output = Shortener::untiny($this->output);
    }

    /**
     * @Then the result should equal to :arg1
     */
    public function theResultShouldEqualTo($arg1)
    {
        if ((string) $arg1 !== $this->output) {
			throw new Exception(
				"Actual output differs:\n" . $this->output
			);
		}
    }


}
