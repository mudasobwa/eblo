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
      | "2002-12-12-1+2002-12-12-3"                   | "[2002[12[12[1,3]]]]"                                       |
      | "2006-5-31-23+2006-5-31-24+2006-6-1-1+2006-6-1-2+2006-6-1-5+2007-6-26-52+2007-8-9-16+2008-1-8-15+2008-3-19-8+2009-7-21-21+2009-7-31-21+2009-8-30-22+2009-10-21-3" | "[2006[5[31[23,24]],6[1[1,2,5]]],2007[6[26[52]],8[9[16]]],2008[1[8[15]],3[19[8]]],2009[7[21[21],31[21]],8[30[22]],10[21[3]]]]" |

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
      | "2006-6-1+2007+2008-1-8-2+2008-8-13-1+2009-5-12-4+2009-9-25-13+2009-11-5-22+2011-6-26-45+2014-3-30-19" |
      | "2006-5-31-23+2006-5-31-24+2006-6-1-1+2006-6-1-2+2006-6-1-5+2007-6-26-52+2007-8-9-16+2008-1-8-15+2008-3-19-8+2009-7-21-21+2009-7-31-21+2009-8-30-22+2009-10-21-3" |

  Scenario Outline: Dates with ranges are tinied into hash-like string
    Given the input string is <input>
    When input string is processed with tinier
    Then the result should equal to <output>

  Examples:
    | input                                         | output                                                        |
    | "2002-12-12"                                  | "C_k"                                                        |
    | "2002-12-12+2002-12-14+2002-11-11"            | "C_km^j"                                                  |
    | "2002-12+2003-11-14+2003-11-11+2002-12-11"    | "C_D^mj"                                               |
    | "2002-12-12-1+2002-12-12-3"     | "C_kÂÄ"                                                     |
    | "2006-5-31-23+2006-5-31-24+2006-6-1-1+2006-6-1-2+2006-6-1-5+2007-6-26-52+2007-8-9-16+2008-1-8-15+2008-3-19-8+2009-7-21-21+2009-7-31-21+2009-8-30-22+2009-10-21-3" | "GX~ØÙY`ÂÃÆHYyõ[hÑITgÐVrÉJZtÖ~Ö[}×]tÄ" |

  Scenario Outline: Tiny and untiny should lead to the same string
    Given the input string is <input>
    When input string is processed with tinier
     And result string is processed with untinier
    Then the result should equal to <input>

  Examples:
    | input                                         |
      | "2006-6-1+2007+2008-1-8-2+2008-8-13-1+2009-5-12-4+2009-9-25-13+2009-11-5-22+2011-6-26-45+2014-3-30-19" |
      | "2006-5-31-23+2006-5-31-24+2006-6-1-1+2006-6-1-2+2006-6-1-5+2007-6-26-52+2007-8-9-16+2008-1-8-15+2008-3-19-8+2009-7-21-21+2009-7-31-21+2009-8-30-22+2009-10-21-3" |

