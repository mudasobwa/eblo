Feature: Eblo backend precesses all the content.

  Scenario Outline: Dates with ranges are packed into array-like string
    Given the input string is <input>
     When input string is processed with packer
     Then the result should equal to <output>

    Examples:
      | input                                         | output                                                      |
      | "2002-12-12"                                  | "[2002[12[12]]]"                                            |
      | "2002-12-12+2002-12-14+2002-11-11"            | "[2002[12[12,14],11[11]]]"                                  |
      | "2002-12-12+2002-12-14+2007+2002-11-11"       | "[2002[12[12,14],11[11]],2007]"                             |
      | "2002-12+2003-11-14+2003-11-11+2002-12-11"    | "[2002[12],2003[11[14,11]]]"                                |
      | "2002-12-12-00-08-12+2002-12-12-00-08-00"     | "[2002[12[12[00[08[12,00]]]]]]"                             |

  Scenario Outline: Previously packed dates with ranges are unpacked correctly
    Given the input string is <input>
     When input string is processed with unpacker
     Then the result should equal to <output>

    Examples:
      | input                                         | output                                                      |
      | "[2007]"                                      | "2007"                                                      |
      | "[2002[12[12]]]"                              | "2002-12-12"                                                |
      | "[2002[12[12,14],11[11]]]"                    | "2002-12-12+2002-12-14+2002-11-11"                          |
      | "[2002[12],2003[11[14,11]]]"                  | "2002-12+2003-11-14+2003-11-11"                             |

  Scenario Outline: Pack and unpack should lead to the same string
    Given the input string is <input>
     When input string is processed with packer
      And result string is processed with unpacker
     Then the result should equal to <input>

    Examples:
      | input                                         |
      | "2006-06-01+2007+2008-01-08-08-15-50+2008-08-13-07-25-24+2009-05-12-21-00-01+2009-09-25-21-00-00+2009-11-05-20-00-09+2011-06-26-07-55-45+2014-03-30-19-11-43" |
      | "2006-05-31-23-17-29+2006-05-31-23-52-55+2006-06-01-00-38-23+2006-06-01-00-43-12+2006-06-01-02-32-15+2007-06-26-07-38-52+2007-08-09-16-38-19+2008-01-08-15-32-50+2008-03-19-08-37-10+2009-07-21-21-00-00+2009-07-31-21-00-00+2009-08-30-21-00-01+2009-10-21-21-00-00" |

  Scenario Outline: Dates with ranges are tinied into hash-like string
    Given the input string is <input>
    When input string is processed with tinier
    Then the result should equal to <output>

  Examples:
    | input                                         | output                                                        |
    | "2002-12-12"                                  | "㷷썰"                                                        |
    | "2002-12-12+2002-12-14+2002-11-11"            | "㷷썰㷺晰㷎"                                                  |
    | "2002-12+2003-11-14+2003-11-11+2002-12-11"    | "㷩䋰㾴೰㾰ᡰ㷶燰"                                               |
    | "2002-12-12-00-08-12+2002-12-12-00-08-00"     | "㷷앜㷷앐"                                                     |
    | "2006-06-01-00-51-26+2007-07-05-06-04-15+2007-07-20-08-57-11+2008-01-08-08-15-50+2008-08-13-07-25-24+2009-05-12-21-00-01+2009-09-25-21-00-00+2009-11-05-20-00-09+2011-06-26-07-55-45+2014-03-30-19-11-43" | "䑾ᷮ䚌涿䚠峇䞃⌦䢢灄䨉윱䪽ጰ䫳₹丆짡匸偏" |

  Scenario Outline: Tiny and untiny should lead to the same string
    Given the input string is <input>
    When input string is processed with tinier
     And result string is processed with untinier
    Then the result should equal to <input>

  Examples:
    | input                                         |
    | "2006-06-01+2007+2008-01-08-08-15-50+2008-08-13-07-25-24+2009-05-12-21-00-01+2009-09-25-21-00-00+2009-11-05-20-00-09+2011-06-26-07-55-45+2014-03-30-19-11-43" |
    | "2006-05-31-23-17-29+2006-05-31-23-52-55+2006-06-01-00-38-23+2006-06-01-00-43-12+2006-06-01-02-32-15+2007-06-26-07-38-52+2007-08-09-16-38-19+2008-01-08-15-32-50+2008-03-19-08-37-10+2009-07-21-21-00-00+2009-07-31-21-00-00+2009-08-30-21-00-01+2009-10-21-21-00-00" |

