Feature: Markup is to be applied properly, producing valid HTML.

  Scenario Outline: Bold, emphasis, code
    Given the input string is <input>
     When input string is processed with parser
     Then the result should equal to <output>

    Examples:
        | input                             | output                                         |
        | "This is *bold* text."            | "<p>This is <b>bold</b> text.</p>"             |
        | "This is **strong** text."        | "<p>This is <strong>strong</strong> text.</p>" |
        | "This is _italic_ text."          | "<p>This is <i>italic</i> text.</p>"           |
        | "This is __emphasized__ text."    | "<p>This is <em>emphasized</em> text.</p>"     |
        | "This is `code`."                 | "<p>This is <code>code</code>.</p>"            |
        | "This is +code+."                 | "<p>This is <small>code</small>.</p>"          |

  Scenario: Blockquotes with text
    Given the input string is
    """
        Some text and other text
        and lorem ipsum and blah blah blah
            ✍ here the signature, goes
    """
    When input string is processed with parser
    Then the result should equal to
    """
    <blockquote>Some text and other text
    and lorem ipsum and blah blah blah
    <p><small>here the signature, goes</small></p></blockquote>
    """

  Scenario: Blockquotes with reference
    Given the input string is
    """
        Some text and other text
        and lorem ipsum and blah blah blah
            ✍ here the signature, http://example.com
    """
    When input string is processed with parser
    Then the result should equal to
    """
    <blockquote>Some text and other text
    and lorem ipsum and blah blah blah
    <p><small><a href='http://example.com'>here the signature</a></small></p></blockquote>
    """

  Scenario: Blockquotes with complicated reference
    Given the input string is
    """
        А город, как я уже упоминал – маленький. А каштаны, соответственно – большие. И вот, огромная ветка каштана, вытянувшаяся над дорогой, даёт майору прикурить по самое не балуйся...
        ✍ ✎ chmyrnovich, ★ (http://chmyrnovich.livejournal.com/127704.html)

Две чудесные истории от экс-капитана КВН МИФИ Чермена Кайтукова.
    """
    When input string is processed with parser
    Then the result should equal to
    """
    <blockquote>А город, как я уже упоминал – маленький. А каштаны, соответственно – большие. И вот, огромная ветка каштана, вытянувшаяся над дорогой, даёт майору прикурить по самое не балуйся...
    <p><small><span style="white-space: nowrap;"><a href="http://chmyrnovich.livejournal.com/profile?mode=full"><img src="http://l-stat.livejournal.com/img/userinfo.gif" alt="[info]" style="border: 0pt none; vertical-align: bottom; padding-right: 1px;" height="17" width="17"></a><a href="http://chmyrnovich.livejournal.com/?style=mine"><b>chmyrnovich</b></a></span>, <a href="http://chmyrnovich.livejournal.com/127704.html">★</small></p></a></blockquote>

<p>Две чудесные истории от экс-капитана КВН МИФИ Чермена Кайтукова.</p>
    """

  Scenario: Data definitions
    Given the input string is
    """
    §4 Если мне задали вопрос, а я в ответ назвал трехзначное число, значит я имел в виду:

    ▶ Барселона — Нет, тут есть — конечно — анклавы
    ▶ Барселона — Нет, тут есть — конечно — анклавы

    Саграда Фамилиа постоянно манифестуют против шума и угарного газа бесчисленных автобусов (мэрия, понятно,
    класть хотела на эти протесты). Но в целом тут совершенно не готовы к туристам. Даже в забытых Зевсом критских
    деревнях продавцы худо-бедно понимали «Hello» и «How much». См. п. 1.
    """
    When input string is processed with parser
    Then the result should equal to
    """
    <h4>Если мне задали вопрос, а я в ответ назвал трехзначное число, значит я имел в виду:</h4>

    <dl><dt>Барселона</dt><dd>Нет, тут есть — конечно — анклавы</dd>
    <dt>Барселона</dt><dd>Нет, тут есть — конечно — анклавы</dd></dl>

    <p>Саграда Фамилиа постоянно манифестуют против шума и угарного газа бесчисленных автобусов (мэрия, понятно,
    класть хотела на эти протесты). Но в целом тут совершенно не готовы к туристам. Даже в забытых Зевсом критских
    деревнях продавцы худо-бедно понимали «Hello» и «How much». См. п. 1.</p>
    """

  Scenario: Paragraphs and main title
    Given the input string is
    """
    Саграда Фамилиа

    Саграда Фамилиа постоянно манифестуют против шума и угарного газа бесчисленных автобусов (мэрия, понятно,
    класть хотела на эти протесты). Но в целом тут совершенно не готовы к туристам. Даже в забытых Зевсом критских
    деревнях продавцы худо-бедно понимали «Hello» и «How much». См. п. 1.

    Саграда Фамилиа постоянно манифестуют против шума и угарного газа бесчисленных автобусов (мэрия, понятно,
    класть хотела на эти протесты). Но в целом тут совершенно не готовы к туристам. Даже в забытых Зевсом критских
    деревнях продавцы худо-бедно понимали «Hello» и «How much». См. п. 1.
    """
    When input string is processed with parser
    Then the result should equal to
    """
    <h1>Саграда Фамилиа</h1>

    <p>Саграда Фамилиа постоянно манифестуют против шума и угарного газа бесчисленных автобусов (мэрия, понятно,
    класть хотела на эти протесты). Но в целом тут совершенно не готовы к туристам. Даже в забытых Зевсом критских
    деревнях продавцы худо-бедно понимали «Hello» и «How much». См. п. 1.</p>

    <p>Саграда Фамилиа постоянно манифестуют против шума и угарного газа бесчисленных автобусов (мэрия, понятно,
    класть хотела на эти протесты). Но в целом тут совершенно не готовы к туристам. Даже в забытых Зевсом критских
    деревнях продавцы худо-бедно понимали «Hello» и «How much». См. п. 1.</p>
    """

  Scenario: Tables
    Given the input string is
    """
    §4 Если мне задали вопрос, а я в ответ назвал трехзначное число, значит я имел в виду:

    		100—199		вопрос понят и обдумывается (втихомолочку)
    		100		тебе, чиста, повезло! я понял вопрос и знаю на него ответ
    		101		да, я говорю по-английски и по-немецки
    		200—299		да, да, скоро отвечу

    """
    When input string is processed with parser
    Then the result should equal to
    """
    <h4>Если мне задали вопрос, а я в ответ назвал трехзначное число, значит я имел в виду:</h4>

    <table><tr><td>100—199</td><td>вопрос понят и обдумывается (втихомолочку)</td></tr>
    <tr><td>100</td><td>тебе, чиста, повезло! я понял вопрос и знаю на него ответ</td></tr>
    <tr><td>101</td><td>да, я говорю по-английски и по-немецки</td></tr>
    <tr><td>200—299</td><td>да, да, скоро отвечу</td></tr>
    </table>
    """

  Scenario: Lists
    Given the input string is
    """
    §4 Если мне задали вопрос, а я в ответ назвал трехзначное число, значит я имел в виду:

    • one
    • two
    • three

    Text here.
    """
    When input string is processed with parser
    Then the result should equal to
    """
    <h4>Если мне задали вопрос, а я в ответ назвал трехзначное число, значит я имел в виду:</h4>

    <ul><li>one</li>
    <li>two</li>
    <li>three</li></ul>

    <p>Text here.</p>
    """


  Scenario: BRs
    Given the input string is
    """
    §4 Если мне задали вопрос, а я в ответ назвал трехзначное число, значит я имел в виду:

    one  
    two  
    three

    Text here.
    """
    When input string is processed with parser
    Then the result should equal to
    """
    <h4>Если мне задали вопрос, а я в ответ назвал трехзначное число, значит я имел в виду:</h4>

    <p>one<br>
    two<br>
    three</p>

    <p>Text here.</p>
    """

  Scenario Outline: Images and videos
    Given the input string is <input>
     When input string is processed with parser
     Then the result should equal to <output>
      And print the result out

    Examples:
        | input                                        | output                                           |
        | "http://example.com/a.png"                   | '<img src="http://example.com/a.png"/>'          |
        | "http://example.com/a.png Caption here"      | '<figure><img src="http://example.com/a.png"/><figcaption><p>Caption here</p></figcaption></figure>'          |
        | "http://youtu.be/SAJ_TzLqy1U?t=6s"           | '<iframe class="youtube" width="560" height="315" src="http://www.youtube.com/embed/SAJ_TzLqy1U" frameborder="0" allowfullscreen></iframe>' |
        | "http://www.youtube.com/watch?v=SAJ_TzLqy1U" | '<iframe class="youtube" width="560" height="315" src="http://www.youtube.com/embed/SAJ_TzLqy1U" frameborder="0" allowfullscreen></iframe>' |
